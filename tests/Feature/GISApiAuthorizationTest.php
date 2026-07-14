<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ViolationReport;
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
            ->assertJsonPath('data.0.tracking_id', 'RCV-2026-9101');
    }

    public function test_public_tracking_response_does_not_expose_identity_or_internal_fields(): void
    {
        $report = $this->report('RCV-2026-9103', null);
        $report->update([
            'contact_number' => '09171234567',
            'remarks' => 'Internal barangay note',
            'manual_assignment_reason' => 'Internal routing evidence',
        ]);

        $response = $this->getJson('/api/mobile/reports/status/RCV-2026-9103');

        $response->assertOk()
            ->assertJsonMissingPath('data.contact_number')
            ->assertJsonMissingPath('data.remarks')
            ->assertJsonMissingPath('data.manual_assignment_reason')
            ->assertJsonMissingPath('data.submitted_by');
    }

    private function report(string $reportId, ?string $manualBarangay): ViolationReport
    {
        return ViolationReport::create([
            'report_id' => $reportId,
            'submitted_by' => 'Anonymous Citizen',
            'description' => 'Test report',
            'selected_violation_type' => 'Road Obstruction',
            'latitude' => 14.2800,
            'longitude' => 121.4100,
            'timestamp' => now(),
            'status' => 'Submitted',
            'verification_status' => 'Unverified',
            'municipality_validated' => true,
            'municipality_name' => 'Santa Cruz',
            'barangay_detection_status' => 'barangay_boundary_unavailable',
            'needs_manual_barangay_review' => $manualBarangay === null,
            'manually_assigned_barangay' => $manualBarangay,
            'date_submitted' => now()->toDateString(),
        ]);
    }
}
