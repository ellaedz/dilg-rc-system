<?php

namespace Tests\Feature;

use App\Models\ViolationReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegacyBarangayReconciliationCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_dry_run_is_read_only_and_apply_reconciles_a_safe_polygon_match(): void
    {
        $report = $this->legacyReport('RCV-2026-9201', 14.2721853, 121.4082521);

        $this->artisan('gis:reconcile-legacy-barangays')
            ->expectsOutputToContain('RCV-2026-9201 -> Calios')
            ->expectsOutputToContain('Dry run only')
            ->assertSuccessful();

        $this->assertTrue($report->fresh()->needs_manual_barangay_review);

        $this->artisan('gis:reconcile-legacy-barangays', ['--apply' => true])
            ->expectsOutputToContain('RCV-2026-9201 -> Calios')
            ->assertSuccessful();

        $report->refresh();
        $this->assertSame('Calios', $report->detected_barangay);
        $this->assertSame('auto_detected', $report->barangay_detection_status);
        $this->assertSame('auto_detected', $report->barangay_assignment_status);
        $this->assertFalse($report->needs_manual_barangay_review);
        $this->assertSame('Barangay Hall - Calios', $report->assigned_barangay_office);
        $this->assertDatabaseHas('report_timelines', [
            'report_id' => $report->id,
            'remarks' => 'Barangay automatically reconciled from the validated MPDO polygon: Calios.',
        ]);
    }

    public function test_apply_does_not_overwrite_an_existing_citizen_or_staff_assignment(): void
    {
        $citizen = $this->legacyReport('RCV-2026-9202', 14.2721853, 121.4082521);
        $citizen->update(['citizen_reported_barangay' => 'Calios']);

        $staff = $this->legacyReport('RCV-2026-9203', 14.2721853, 121.4082521);
        $staff->update(['manually_assigned_barangay' => 'Calios']);

        $this->artisan('gis:reconcile-legacy-barangays', ['--apply' => true])
            ->expectsOutput('No legacy barangay assignments require reconciliation.')
            ->assertSuccessful();

        $this->assertSame('barangay_boundary_unavailable', $citizen->fresh()->barangay_detection_status);
        $this->assertSame('barangay_boundary_unavailable', $staff->fresh()->barangay_detection_status);
    }

    private function legacyReport(string $reportId, float $latitude, float $longitude): ViolationReport
    {
        return ViolationReport::create([
            'report_id' => $reportId,
            'submitted_by' => 'Anonymous Citizen',
            'description' => 'Legacy location report',
            'selected_violation_type' => 'Road Obstruction',
            'latitude' => $latitude,
            'longitude' => $longitude,
            'timestamp' => now(),
            'status' => 'Submitted',
            'verification_status' => 'Pending',
            'municipality_validated' => true,
            'municipality_name' => 'Santa Cruz',
            'barangay_detection_status' => 'barangay_boundary_unavailable',
            'barangay_assignment_status' => 'barangay_boundary_unavailable',
            'needs_manual_barangay_review' => true,
            'date_submitted' => now()->toDateString(),
        ]);
    }
}
