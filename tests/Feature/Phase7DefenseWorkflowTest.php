<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ViolationReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CreatesPhase8cResponses;
use Tests\TestCase;

class Phase7DefenseWorkflowTest extends TestCase
{
    use CreatesPhase8cResponses;
    use RefreshDatabase;

    public function test_complete_defense_submission_routing_status_and_tracking_workflow(): void
    {
        Storage::fake('public');
        Storage::fake('report_photos');
        Http::fake([
            '*/v1/predict/multimodal' => Http::response(
                $this->phase8cResponse([], 1, 1)
            ),
        ]);

        $submission = $this->post('/api/mobile/reports', [
            'description' => 'May sasakyan na nakaharang sa kalsada at humahadlang sa trapiko.',
            'selected_violation_type' => 'Illegal Parking',
            'latitude' => 14.281,
            'longitude' => 121.416,
            'gps_accuracy' => 8.5,
            'timestamp' => now()->toISOString(),
            'photo' => UploadedFile::fake()->createWithContent(
                'road-clearing-evidence.png',
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=')
            ),
            'image_result' => 'illegal_parking',
            'image_confidence' => 0.88,
            'image_validation_status' => 'accepted',
            'image_model_version' => 'yolov8s-float16-defense-test',
            'needs_manual_review' => false,
        ], ['Accept' => 'application/json']);

        $submission->assertCreated()
            ->assertJsonPath('data.ai_processing_status', 'completed')
            ->assertJsonPath('data.final_ai_category', 'illegal_parking')
            ->assertJsonPath('data.barangay_detection_status', 'barangay_boundary_unavailable');

        $trackingToken = $submission->json('data.tracking_id');
        $reportNumber = $submission->json('data.report_number');
        $report = ViolationReport::where('report_number', $reportNumber)->firstOrFail();

        $this->assertNull($report->image_path);
        $this->assertNotNull($report->photo_object_key);
        Storage::disk('report_photos')->assertExists($report->photo_object_key);
        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertSame('completed', $report->ai_processing_status);
        $this->assertSame('illegal_parking', $report->text_prediction);
        $this->assertSame('illegal_parking', $report->final_ai_prediction);

        $admin = User::factory()->create([
            'role' => 'dilg_admin',
            'assigned_barangay' => null,
        ]);

        $this->actingAs($admin)
            ->get(route('dilg.dashboard'))
            ->assertOk()
            ->assertSee($reportNumber);

        $this->actingAs($admin)
            ->get(route('violation-reports.show', $report))
            ->assertOk()
            ->assertSee('Photo Evidence')
            ->assertSee('GPS Location Information')
            ->assertSee('Suggested From Photo')
            ->assertSee('Photo Match Score')
            ->assertSee('Suggested From Description')
            ->assertSee('Description Match Score')
            ->assertSee('AI Suggested Violation')
            ->assertSee('Overall Match Score')
            ->assertSee('Reason for Suggestion')
            ->assertSee('Staff Review Needed')
            ->assertSee('Analysis Complete');

        $this->actingAs($admin)
            ->post(route('dilg.needs-barangay-review.route', $report), [
                'selected_barangay' => 'Alipit',
                'assignment_reason' => 'Defense validation routing based on reviewed GPS and photo evidence.',
                'confirm_assignment' => '1',
            ])
            ->assertRedirect(route('dilg.needs-barangay-review.index'));

        $staff = User::factory()->create([
            'role' => 'barangay_staff',
            'assigned_barangay' => 'Alipit',
        ]);

        $this->actingAs($staff)
            ->get(route('barangay.dashboard', 'Alipit'))
            ->assertOk()
            ->assertSee($reportNumber);

        $this->actingAs($staff)
            ->put(route('barangay.report.update', ['barangay' => 'Alipit', 'report' => $report]), [
                'status' => 'In Progress',
                'assigned_personnel' => 'Defense Validation Team',
                'action_taken' => 'Road-clearing verification team dispatched.',
                'remarks' => 'Defense workflow status update.',
            ])
            ->assertRedirect(route('violation-reports.show', $report));

        $this->getJson('/api/mobile/reports/status/'.$trackingToken)
            ->assertOk()
            ->assertJsonPath('data.current_status', 'In Progress')
            ->assertJsonPath('data.barangay', 'Alipit')
            ->assertJsonPath('data.latest_action', 'Road-clearing verification team dispatched.')
            ->assertJsonFragment([
                'status' => 'In Progress',
                'action' => 'Road-clearing verification team dispatched.',
            ]);
    }
}
