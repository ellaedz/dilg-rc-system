<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ViolationReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManualBarangayRoutingTest extends TestCase
{
    use RefreshDatabase;

    public function test_dilg_can_route_and_barangay_sees_effective_assignment(): void
    {
        $admin = User::factory()->create(['role' => 'dilg_admin']);
        $staff = User::factory()->create(['role' => 'barangay_staff', 'assigned_barangay' => 'Alipit']);
        $report = $this->unassignedReport('RCV-2026-9001');

        $this->actingAs($admin)->post(route('dilg.needs-barangay-review.route', $report), [
            'selected_barangay' => 'Alipit',
            'assignment_reason' => 'GPS evidence was manually reviewed by DILG.',
            'confirm_assignment' => '1',
        ])->assertRedirect(route('dilg.needs-barangay-review.index'));

        $report->refresh();
        $this->assertSame('Alipit', $report->effective_barangay);
        $this->assertNotNull($report->manual_assignment_at);

        $this->actingAs($staff)
            ->get(route('barangay.incoming-reports', 'Alipit'))
            ->assertOk()
            ->assertSee('RCV-2026-9001');
    }

    public function test_barangay_staff_cannot_route_reports(): void
    {
        $staff = User::factory()->create(['role' => 'barangay_staff', 'assigned_barangay' => 'Alipit']);
        $report = $this->unassignedReport('RCV-2026-9002');

        $this->actingAs($staff)->post(route('dilg.needs-barangay-review.route', $report), [
            'selected_barangay' => 'Alipit',
            'assignment_reason' => 'This attempt must not be authorized.',
            'confirm_assignment' => '1',
        ])->assertForbidden();

        $this->assertNull($report->fresh()->manually_assigned_barangay);
    }

    public function test_dilg_cannot_route_an_outside_coverage_report(): void
    {
        $admin = User::factory()->create(['role' => 'dilg_admin']);
        $report = $this->unassignedReport('RCV-2026-9003');
        $report->update([
            'municipality_validated' => false,
            'municipality_name' => null,
            'barangay_detection_status' => 'outside_coverage',
        ]);

        $this->actingAs($admin)->post(route('dilg.needs-barangay-review.route', $report), [
            'selected_barangay' => 'Alipit',
            'assignment_reason' => 'This outside-coverage route must be rejected.',
            'confirm_assignment' => '1',
        ])->assertStatus(422);

        $this->assertNull($report->fresh()->manually_assigned_barangay);
    }

    private function unassignedReport(string $reportId): ViolationReport
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
            'needs_manual_barangay_review' => true,
            'date_submitted' => now()->toDateString(),
        ]);
    }
}
