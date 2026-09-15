<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ViolationReport;
use App\Services\ReportCredentialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase12AOperationalGISAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_operational_map_exposes_validation_state_for_every_report_category(): void
    {
        $admin = User::factory()->create(['role' => 'dilg_admin']);

        $this->report('RCV-2026-1201', ['status' => 'Assigned']);
        $this->report('RCV-2026-1202', [
            'status' => 'Submitted',
            'verification_status' => 'Pending',
            'official_violation_type' => null,
            'verified_at' => null,
        ]);
        $this->report('RCV-2026-1203', ['status' => 'Rejected']);
        $this->report('RCV-2026-1204', ['is_duplicate' => true]);
        $this->report('RCV-2026-1205', [
            'municipality_validated' => false,
            'barangay_assignment_status' => 'outside_coverage',
        ]);
        $this->report('RCV-2026-1206', ['is_test_data' => true]);
        $this->report('RCV-2026-1207', [
            'status' => 'Submitted',
            'verification_status' => 'Pending',
            'official_violation_type' => null,
            'verified_at' => null,
            'ai_processing_status' => ViolationReport::AI_STATUS_PROCESSING,
        ]);

        $response = $this->actingAs($admin)->getJson('/api/gis/reports?dataset=operational');

        $response->assertOk()->assertJsonCount(7, 'data');
        $states = collect($response->json('data'))->pluck('operational_state', 'tracking_id');

        $this->assertSame('verified_valid', $states['RCV-2026-1201']);
        $this->assertSame('pending_verification', $states['RCV-2026-1202']);
        $this->assertSame('rejected', $states['RCV-2026-1203']);
        $this->assertSame('duplicate', $states['RCV-2026-1204']);
        $this->assertSame('outside_jurisdiction', $states['RCV-2026-1205']);
        $this->assertSame('test_data', $states['RCV-2026-1206']);
        $this->assertSame('ai_pending', $states['RCV-2026-1207']);
    }

    public function test_official_map_only_includes_eligible_verified_reports_and_official_classes(): void
    {
        $admin = User::factory()->create(['role' => 'dilg_admin']);

        foreach (['Verified', 'Assigned', 'Resolved', 'Closed'] as $index => $status) {
            $this->report('RCV-2026-12'.(10 + $index), [
                'status' => $status,
                'selected_violation_type' => 'Road Obstruction',
                'official_violation_type' => 'Illegal Parking',
            ]);
        }

        $this->report('RCV-2026-1220', [
            'status' => 'Submitted',
            'verification_status' => 'Pending',
            'official_violation_type' => null,
            'verified_at' => null,
        ]);
        $this->report('RCV-2026-1221', ['status' => 'Rejected']);
        $this->report('RCV-2026-1222', ['is_duplicate' => true]);
        $this->report('RCV-2026-1223', ['municipality_validated' => false]);
        $this->report('RCV-2026-1224', ['is_test_data' => true]);

        $reports = $this->actingAs($admin)->getJson('/api/gis/reports?dataset=official');

        $reports->assertOk()->assertJsonCount(4, 'data');
        $this->assertEqualsCanonicalizing(
            ['Assigned', 'Closed', 'Resolved', 'Verified'],
            collect($reports->json('data'))->pluck('status')->all()
        );
        $this->assertTrue(collect($reports->json('data'))->every(
            fn (array $report): bool => $report['is_official_statistic']
                && $report['official_violation_type'] === 'Illegal Parking'
        ));

        $summary = $this->actingAs($admin)->getJson('/api/gis/hotspots-summary?dataset=official');
        $summary->assertOk()
            ->assertJsonPath('data.total_mapped_reports', 4)
            ->assertJsonPath('data.most_common_violation_type', 'Illegal Parking')
            ->assertJsonPath('data.violation_type_counts.Illegal Parking', 4);
    }

    public function test_barangay_official_dataset_remains_restricted_to_assigned_barangay(): void
    {
        $staff = User::factory()->create([
            'role' => 'barangay_staff',
            'assigned_barangay' => 'Alipit',
        ]);

        $this->report('RCV-2026-1230', ['manually_assigned_barangay' => 'Alipit']);
        $this->report('RCV-2026-1231', ['manually_assigned_barangay' => 'Bagumbayan']);

        $this->actingAs($staff)
            ->getJson('/api/gis/reports?dataset=official')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.tracking_id', 'RCV-2026-1230');

        $this->actingAs($staff)
            ->getJson('/api/gis/reports?dataset=official&barangay=Bagumbayan')
            ->assertForbidden();
    }

    public function test_dashboard_verified_and_resolved_totals_use_the_official_scope(): void
    {
        $admin = User::factory()->create(['role' => 'dilg_admin']);
        $staff = User::factory()->create([
            'role' => 'barangay_staff',
            'assigned_barangay' => 'Alipit',
        ]);

        $this->report('RCV-2026-1240', [
            'status' => 'Assigned',
            'manually_assigned_barangay' => 'Alipit',
        ]);
        $this->report('RCV-2026-1241', [
            'status' => 'Resolved',
            'manually_assigned_barangay' => 'Alipit',
        ]);
        $this->report('RCV-2026-1242', [
            'status' => 'Rejected',
            'manually_assigned_barangay' => 'Alipit',
        ]);
        $this->report('RCV-2026-1243', [
            'status' => 'Resolved',
            'manually_assigned_barangay' => 'Bagumbayan',
        ]);

        $this->actingAs($admin)
            ->getJson('/api/dilg-dashboard-stats')
            ->assertOk()
            ->assertJsonPath('verified_reports', 3)
            ->assertJsonPath('resolved_reports', 2);

        $this->actingAs($staff)
            ->getJson('/api/barangay/Alipit/dashboard-stats')
            ->assertOk()
            ->assertJsonPath('verified_reports', 2)
            ->assertJsonPath('resolved_reports', 1);
    }

    public function test_dilg_and_barangay_violation_charts_use_only_official_classifications(): void
    {
        $admin = User::factory()->create(['role' => 'dilg_admin']);
        $staff = User::factory()->create([
            'role' => 'barangay_staff',
            'assigned_barangay' => 'Alipit',
        ]);

        $this->report('RCV-2026-1250', [
            'selected_violation_type' => 'Road Obstruction',
            'official_violation_type' => 'Illegal Parking',
            'manually_assigned_barangay' => 'Alipit',
        ]);
        $this->report('RCV-2026-1251', [
            'selected_violation_type' => 'Vendor Encroachment',
            'official_violation_type' => null,
            'verification_status' => 'Pending',
            'verified_at' => null,
            'manually_assigned_barangay' => 'Alipit',
        ]);

        $dilgResponse = $this->actingAs($admin)->get(route('analytics-reports.index'));
        $dilgResponse->assertOk();
        $this->assertSame(
            ['Illegal Parking' => 1],
            $dilgResponse->viewData('officialReportsByViolationType')
                ->pluck('count', 'selected_violation_type')
                ->map(fn ($count): int => (int) $count)
                ->all()
        );

        $barangayResponse = $this->actingAs($staff)
            ->get(route('barangay.analytics-reports', 'Alipit'));
        $barangayResponse->assertOk();
        $this->assertSame(
            ['Illegal Parking' => 1],
            $barangayResponse->viewData('officialReportsByViolationType')
                ->pluck('count', 'selected_violation_type')
                ->map(fn ($count): int => (int) $count)
                ->all()
        );
    }

    public function test_gis_rejects_an_unknown_dataset(): void
    {
        $admin = User::factory()->create(['role' => 'dilg_admin']);

        $this->actingAs($admin)
            ->getJson('/api/gis/reports?dataset=untrusted')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('dataset');
    }

    public function test_gis_workspace_explains_operational_and_official_marker_states(): void
    {
        $admin = User::factory()->create(['role' => 'dilg_admin']);

        $this->actingAs($admin)
            ->get(route('gis.index'))
            ->assertOk()
            ->assertSee('Operational reports')
            ->assertSee('Official verified statistics')
            ->assertSee('AI Processing')
            ->assertSee('Awaiting Review')
            ->assertSee('Duplicate')
            ->assertSee('Report Status')
            ->assertSee('isolation: isolate', false)
            ->assertSee('scrollWheelZoom: true', false)
            ->assertDontSee('Outside supported jurisdiction')
            ->assertDontSee('Test data');
    }

    private function report(string $reportId, array $overrides = []): ViolationReport
    {
        $credentials = app(ReportCredentialService::class)->issue();
        $submittedAt = now()->startOfMinute();

        return ViolationReport::create(array_merge([
            'report_id' => $reportId,
            'report_number' => $reportId,
            'token_derivation_nonce' => $credentials['token_derivation_nonce'],
            'tracking_token_hash' => $credentials['tracking_token_hash'],
            'idempotency_key_hash' => app(ReportCredentialService::class)
                ->hashIdempotencyKey('phase-12a-'.bin2hex(random_bytes(12))),
            'submitted_by' => 'Anonymous Citizen',
            'description' => 'GIS official-statistics test report',
            'selected_violation_type' => 'Road Obstruction',
            'official_violation_type' => 'Road Obstruction',
            'latitude' => 14.2800,
            'longitude' => 121.4100,
            'timestamp' => $submittedAt,
            'date_submitted' => $submittedAt->toDateString(),
            'status' => 'Verified',
            'verification_status' => 'Valid Violation',
            'ai_processing_status' => ViolationReport::AI_STATUS_COMPLETED,
            'verified_at' => $submittedAt,
            'municipality_validated' => true,
            'municipality_name' => 'Santa Cruz',
            'barangay_detection_status' => 'matched',
            'barangay_assignment_status' => 'assigned',
            'needs_manual_barangay_review' => false,
            'manually_assigned_barangay' => 'Alipit',
            'is_duplicate' => false,
            'is_test_data' => false,
        ], $overrides));
    }
}
