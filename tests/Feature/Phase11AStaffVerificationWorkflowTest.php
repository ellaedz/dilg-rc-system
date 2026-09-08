<?php

namespace Tests\Feature;

use App\Models\ReportTimeline;
use App\Models\User;
use App\Models\ViolationReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase11AStaffVerificationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigned_barangay_staff_can_confirm_the_ai_suggestion_as_official(): void
    {
        $staff = $this->staff('Alipit');
        $report = $this->pendingReport('RCV-2026-9101', 'Alipit');

        $this->assertNull($report->official_violation_type);
        $this->assertNull($report->verified_at);

        $this->actingAs($staff)
            ->get(route('barangay.incoming-reports', 'Alipit'))
            ->assertOk()
            ->assertSee('AI suggestion')
            ->assertSee('Illegal Parking')
            ->assertSee('87.0% confidence')
            ->assertSee('Confirm official class')
            ->assertDontSee('illegal_parking');

        $this->actingAs($staff)->post(
            route('barangay.incoming-reports.verify', ['barangay' => 'Alipit', 'report' => $report]),
            ['official_violation_type' => 'Illegal Parking']
        )->assertRedirect(route('barangay.incoming-reports', 'Alipit'));

        $report->refresh();
        $this->assertSame('Verified', $report->status);
        $this->assertSame('Valid Violation', $report->verification_status);
        $this->assertSame('Illegal Parking', $report->official_violation_type);
        $this->assertSame($staff->id, $report->verified_by);
        $this->assertNotNull($report->verified_at);
        $this->assertSame('illegal_parking', $report->ai_prediction_at_verification);
        $this->assertSame('0.8700', $report->ai_confidence_at_verification);
        $this->assertTrue($report->staff_agreed_with_ai);

        $timeline = ReportTimeline::where('report_id', $report->id)->sole();
        $this->assertSame('Verified', $timeline->status);
        $this->assertStringContainsString('agreed with AI', $timeline->remarks);
    }

    public function test_staff_correction_requires_a_reason_and_preserves_the_ai_result(): void
    {
        $staff = $this->staff('Alipit');
        $report = $this->pendingReport('RCV-2026-9102', 'Alipit');
        $url = route('barangay.incoming-reports.verify', ['barangay' => 'Alipit', 'report' => $report]);

        $this->actingAs($staff)->from(route('barangay.incoming-reports', 'Alipit'))->post($url, [
            'official_violation_type' => 'Road Obstruction',
        ])->assertSessionHasErrors('correction_reason');

        $this->assertSame('Pending', $report->fresh()->verification_status);

        $this->actingAs($staff)->post($url, [
            'official_violation_type' => 'Road Obstruction',
            'correction_reason' => 'The vehicle is moving; the fixed barrier is the actual obstruction.',
        ])->assertRedirect(route('barangay.incoming-reports', 'Alipit'));

        $report->refresh();
        $this->assertSame('Road Obstruction', $report->official_violation_type);
        $this->assertSame('illegal_parking', $report->final_ai_prediction);
        $this->assertFalse($report->staff_agreed_with_ai);
        $this->assertSame(
            'The vehicle is moving; the fixed barrier is the actual obstruction.',
            $report->staff_verification_reason
        );
    }

    public function test_only_the_assigned_barangay_staff_can_submit_a_decision(): void
    {
        $report = $this->pendingReport('RCV-2026-9103', 'Alipit');
        $otherStaff = $this->staff('Bagumbayan');
        $admin = User::factory()->create(['role' => 'dilg_admin', 'assigned_barangay' => null]);
        $url = route('barangay.incoming-reports.verify', ['barangay' => 'Alipit', 'report' => $report]);

        $this->actingAs($otherStaff)->post($url, [
            'official_violation_type' => 'Illegal Parking',
        ])->assertForbidden();

        $this->actingAs($admin)->post($url, [
            'official_violation_type' => 'Illegal Parking',
        ])->assertForbidden();

        $this->assertSame('Pending', $report->fresh()->verification_status);
        $this->assertNull($report->official_violation_type);
    }

    public function test_staff_can_classify_a_duplicate_without_creating_an_official_violation(): void
    {
        $staff = $this->staff('Alipit');
        $report = $this->pendingReport('RCV-2026-9104', 'Alipit');

        $this->actingAs($staff)->post(
            route('barangay.incoming-reports.reject', ['barangay' => 'Alipit', 'report' => $report]),
            [
                'verification_status' => 'Duplicate',
                'reason' => 'Same photograph and location as RCV-2026-9000.',
            ]
        )->assertRedirect(route('barangay.incoming-reports', 'Alipit'));

        $report->refresh();
        $this->assertSame('Rejected', $report->status);
        $this->assertSame('Duplicate', $report->verification_status);
        $this->assertTrue($report->is_duplicate);
        $this->assertNull($report->official_violation_type);
        $this->assertSame($staff->id, $report->verified_by);
        $this->assertNotNull($report->verified_at);
        $this->assertSame('Duplicate: Same photograph and location as RCV-2026-9000.', $report->timelines()->sole()->remarks);
    }

    public function test_a_generic_status_update_cannot_bypass_staff_verification(): void
    {
        $staff = $this->staff('Alipit');
        $report = $this->pendingReport('RCV-2026-9105', 'Alipit');

        $this->actingAs($staff)->put(
            route('barangay.report.update', ['barangay' => 'Alipit', 'report' => $report]),
            ['status' => 'Verified']
        )->assertStatus(422);

        $report->refresh();
        $this->assertSame('Submitted', $report->status);
        $this->assertSame('Pending', $report->verification_status);
        $this->assertNull($report->official_violation_type);
        $this->assertNull($report->verified_at);
    }

    public function test_dilg_can_monitor_but_cannot_update_barangay_response_status(): void
    {
        $admin = User::factory()->create(['role' => 'dilg_admin', 'assigned_barangay' => null]);
        $report = $this->pendingReport('RCV-2026-9107', 'Alipit');

        $this->actingAs($admin)
            ->get(route('barangay.incoming-reports', 'Alipit'))
            ->assertOk()
            ->assertSee('DILG monitoring view')
            ->assertDontSee('Confirm official class');

        $this->actingAs($admin)->put(
            route('barangay.report.update', ['barangay' => 'Alipit', 'report' => $report]),
            ['status' => 'In Progress']
        )->assertForbidden();

        $this->assertSame('Submitted', $report->fresh()->status);
    }

    public function test_a_report_cannot_receive_two_verification_decisions(): void
    {
        $staff = $this->staff('Alipit');
        $report = $this->pendingReport('RCV-2026-9106', 'Alipit');
        $url = route('barangay.incoming-reports.verify', ['barangay' => 'Alipit', 'report' => $report]);

        $this->actingAs($staff)->post($url, [
            'official_violation_type' => 'Illegal Parking',
        ])->assertRedirect();

        $this->actingAs($staff)->post($url, [
            'official_violation_type' => 'Road Obstruction',
            'correction_reason' => 'Attempted second decision.',
        ])->assertSessionHasErrors('report');

        $this->assertSame('Illegal Parking', $report->fresh()->official_violation_type);
        $this->assertSame(1, ReportTimeline::where('report_id', $report->id)->count());
    }

    private function staff(string $barangay): User
    {
        return User::factory()->create([
            'role' => 'barangay_staff',
            'assigned_barangay' => $barangay,
        ]);
    }

    private function pendingReport(string $reportId, string $barangay): ViolationReport
    {
        return ViolationReport::create([
            'report_id' => $reportId,
            'submitted_by' => 'Anonymous Citizen',
            'description' => 'A vehicle is blocking the road.',
            'selected_violation_type' => 'Unclassified',
            'latitude' => 14.2800,
            'longitude' => 121.4100,
            'timestamp' => now(),
            'citizen_reported_barangay' => $barangay,
            'status' => 'Submitted',
            'verification_status' => 'Pending',
            'ai_processing_status' => 'completed',
            'final_ai_prediction' => 'illegal_parking',
            'final_ai_confidence' => 0.87,
            'ai_possible_violation' => 'illegal_parking',
            'ai_possible_violation_confidence' => 0.87,
            'municipality_validated' => true,
            'municipality_name' => 'Santa Cruz',
            'barangay_detection_status' => 'barangay_boundary_unavailable',
            'date_submitted' => now()->toDateString(),
        ]);
    }
}
