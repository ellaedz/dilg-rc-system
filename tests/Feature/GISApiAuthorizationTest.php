<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ViolationReport;
use App\Services\ReportCredentialService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GISApiAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_gis_report_data_requires_authentication(): void
    {
        $this->getJson('/api/gis/reports')->assertUnauthorized();
        $this->getJson('/api/gis/hotspots-summary')->assertUnauthorized();
    }

    public function test_barangay_gis_only_returns_matching_effective_barangay(): void
    {
        $staff = User::factory()->create(['role' => 'barangay_staff', 'assigned_barangay' => 'Alipit']);
        $this->report('RCV-2026-9101', 'Alipit');
        $this->report('RCV-2026-9102', 'Bagumbayan');

        $response = $this->actingAs($staff)->getJson('/api/gis/reports');

        $response->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.tracking_id', 'RCV-2026-9101')
            ->assertJsonPath('data.0.details_url', route('violation-reports.show', 1));
    }

    public function test_barangay_staff_can_open_only_their_scoped_gis_workspace(): void
    {
        $staff = User::factory()->create(['role' => 'barangay_staff', 'assigned_barangay' => 'Alipit']);

        $this->actingAs($staff)
            ->get(route('barangay.gis.index', 'Alipit'))
            ->assertOk()
            ->assertSee('Barangay Alipit GIS Workspace')
            ->assertSee('Assigned-barangay reports only')
            ->assertSee('GIS Map');

        $this->actingAs($staff)
            ->get(route('barangay.gis.index', 'Bagumbayan'))
            ->assertForbidden();
    }

    public function test_barangay_staff_cannot_request_another_barangay_through_gis_filters(): void
    {
        $staff = User::factory()->create(['role' => 'barangay_staff', 'assigned_barangay' => 'Alipit']);

        $this->actingAs($staff)
            ->getJson('/api/gis/reports?barangay=Bagumbayan')
            ->assertForbidden();

        $this->actingAs($staff)
            ->getJson('/api/gis/hotspots-summary?barangay=Bagumbayan')
            ->assertForbidden();
    }

    public function test_barangay_gis_only_returns_its_verified_hall(): void
    {
        $staff = User::factory()->create(['role' => 'barangay_staff', 'assigned_barangay' => 'Calios']);

        $this->actingAs($staff)
            ->getJson('/api/gis/barangay-offices')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.barangay', 'Calios')
            ->assertJsonPath('meta.total_offices', 1)
            ->assertJsonPath('meta.needs_validation', 0);
    }

    public function test_every_configured_barangay_account_has_a_scoped_gis_workspace(): void
    {
        foreach (config('santa_cruz_barangays.barangays', []) as $barangay) {
            $barangayName = $barangay['name'];
            $staff = User::factory()->create([
                'role' => 'barangay_staff',
                'assigned_barangay' => $barangayName,
            ]);

            $this->actingAs($staff)
                ->get(route('barangay.gis.index', $barangayName))
                ->assertOk()
                ->assertSee('Barangay '.$barangayName.' GIS Workspace');

            $this->actingAs($staff)
                ->getJson('/api/gis/barangay-offices')
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.barangay', $barangayName);
        }
    }

    public function test_gis_date_filters_apply_to_reports_and_hotspot_totals(): void
    {
        $staff = User::factory()->create(['role' => 'barangay_staff', 'assigned_barangay' => 'Alipit']);
        $this->report('RCV-2026-9104', 'Alipit', '2026-09-01');
        $this->report('RCV-2026-9105', 'Alipit', '2026-09-10');

        $query = '?date_from=2026-09-05&date_to=2026-09-15';

        $this->actingAs($staff)
            ->getJson('/api/gis/reports'.$query)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.tracking_id', 'RCV-2026-9105');

        $this->actingAs($staff)
            ->getJson('/api/gis/hotspots-summary'.$query)
            ->assertOk()
            ->assertJsonPath('data.total_mapped_reports', 1)
            ->assertJsonPath('data.top_hotspot_barangay', 'Alipit');
    }

    public function test_barangay_office_api_exposes_all_final_validated_markers(): void
    {
        $admin = User::factory()->create(['role' => 'dilg_admin']);

        $response = $this->actingAs($admin)->getJson('/api/gis/barangay-offices');

        $response->assertOk()
            ->assertJsonCount(26, 'data')
            ->assertJsonPath('meta.total_offices', 26)
            ->assertJsonPath('meta.needs_validation', 0)
            ->assertJsonPath('meta.note', 'All office coordinates are validated.');
    }

    public function test_public_tracking_response_does_not_expose_identity_or_internal_fields(): void
    {
        $report = $this->report('RCV-2026-9103', null);
        $report->update([
            'contact_number' => '09171234567',
            'remarks' => 'Internal barangay note',
            'manual_assignment_reason' => 'Internal routing evidence',
        ]);

        $token = app(ReportCredentialService::class)->replayToken($report);
        $response = $this->withToken($token)->getJson('/api/mobile/reports/status');

        $response->assertOk()
            ->assertJsonMissingPath('data.contact_number')
            ->assertJsonMissingPath('data.remarks')
            ->assertJsonMissingPath('data.manual_assignment_reason')
            ->assertJsonMissingPath('data.submitted_by');
    }

    private function report(
        string $reportId,
        ?string $manualBarangay,
        ?string $dateSubmitted = null
    ): ViolationReport {
        $credentials = app(ReportCredentialService::class)->issue();
        $submittedAt = Carbon::parse($dateSubmitted ?? now()->toDateString())->setTime(12, 0);

        return ViolationReport::create([
            'report_id' => $reportId,
            'report_number' => $reportId,
            'token_derivation_nonce' => $credentials['token_derivation_nonce'],
            'tracking_token_hash' => $credentials['tracking_token_hash'],
            'idempotency_key_hash' => app(ReportCredentialService::class)
                ->hashIdempotencyKey('gis-auth-test-'.bin2hex(random_bytes(12))),
            'submitted_by' => 'Anonymous Citizen',
            'description' => 'Test report',
            'selected_violation_type' => 'Road Obstruction',
            'latitude' => 14.2800,
            'longitude' => 121.4100,
            'timestamp' => $submittedAt,
            'status' => 'Submitted',
            'verification_status' => 'Pending',
            'municipality_validated' => true,
            'municipality_name' => 'Santa Cruz',
            'barangay_detection_status' => 'barangay_boundary_unavailable',
            'needs_manual_barangay_review' => $manualBarangay === null,
            'manually_assigned_barangay' => $manualBarangay,
            'date_submitted' => $submittedAt->toDateString(),
        ]);
    }
}
