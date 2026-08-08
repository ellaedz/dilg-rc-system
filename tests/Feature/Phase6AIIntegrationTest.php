<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ViolationReport;
use App\Services\LocalPrivateReportPhotoStorage;
use App\Services\ReportCredentialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CreatesPhase8cResponses;
use Tests\Support\CreatesTestImages;
use Tests\TestCase;

class Phase6AIIntegrationTest extends TestCase
{
    use CreatesPhase8cResponses;
    use CreatesTestImages;
    use RefreshDatabase;

    private function aiResponse(array $overrides = []): array
    {
        return $this->phase8cResponse($overrides);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('report_photos');
        Storage::fake('report_photo_quarantine');
    }

    private function report(array $overrides = []): ViolationReport
    {
        $credentials = app(ReportCredentialService::class)->issue();

        $bytes = $this->pngBytes();
        $storage = app(LocalPrivateReportPhotoStorage::class);
        $objectKey = $storage->generateObjectKey('png');
        $storage->put($objectKey, $bytes);

        return ViolationReport::create(array_merge([
            'report_id' => ViolationReport::generateReportId(),
            'token_derivation_nonce' => $credentials['token_derivation_nonce'],
            'tracking_token_hash' => $credentials['tracking_token_hash'],
            'idempotency_key_hash' => app(ReportCredentialService::class)
                ->hashIdempotencyKey('phase6-test-idempotency-key-'.bin2hex(random_bytes(8))),
            'submitted_by' => 'Anonymous Citizen',
            'description' => 'May sasakyan na nakaharang sa kalsada.',
            'selected_violation_type' => 'Illegal Parking',
            'photo_object_key' => $objectKey,
            'photo_storage_disk' => 'report_photos',
            'photo_mime_type' => 'image/png',
            'photo_size_bytes' => strlen($bytes),
            'photo_width' => 4,
            'photo_height' => 3,
            'photo_sha256' => hash('sha256', $bytes),
            'photo_upload_status' => 'uploaded',
            'latitude' => 14.281,
            'longitude' => 121.416,
            'timestamp' => now(),
            'status' => 'Submitted',
            'verification_status' => 'Pending',
            'date_submitted' => now()->toDateString(),
            'date_updated' => now()->toDateString(),
            'municipality_validated' => true,
            'municipality_name' => 'Santa Cruz',
            'barangay_detection_status' => 'barangay_boundary_unavailable',
            'needs_manual_barangay_review' => true,
            'ai_processing_status' => 'pending',
        ], $overrides));
    }

    public function test_mobile_report_is_saved_and_fastapi_result_is_persisted(): void
    {
        Http::fake(['*/predict/multimodal' => Http::response($this->aiResponse())]);

        $response = $this->post('/api/mobile/reports', [
            'description' => 'May sasakyan na nakaharang sa kalsada.',
            'selected_violation_type' => 'Illegal Parking',
            'latitude' => 14.281,
            'longitude' => 121.416,
            'timestamp' => now()->toISOString(),
            'image_result' => 'illegal_parking',
            'image_confidence' => 0.92,
            'image_validation_status' => 'accepted',
            'photo' => $this->uploadedImage(
                'evidence.png',
                $this->pngBytes(),
                'image/png'
            ),
        ], ['Accept' => 'application/json']);

        $response->assertCreated()
            ->assertJsonPath('data.ai_processing_status', 'completed')
            ->assertJsonPath('data.final_ai_category', 'illegal_parking');

        $this->assertDatabaseHas('violation_reports', [
            'report_number' => $response->json('data.report_number'),
            'text_prediction' => 'illegal_parking',
            'final_ai_prediction' => 'illegal_parking',
            'ai_processing_status' => 'completed',
        ]);
    }

    public function test_fastapi_offline_never_loses_the_report(): void
    {
        Http::fake(fn () => throw new ConnectionException('FastAPI offline'));

        $response = $this->post('/api/mobile/reports', [
            'description' => 'May sasakyan na nakaharang sa kalsada.',
            'selected_violation_type' => 'Illegal Parking',
            'latitude' => 14.281,
            'longitude' => 121.416,
            'timestamp' => now()->toISOString(),
            'image_result' => 'illegal_parking',
            'image_confidence' => 0.92,
            'photo' => $this->uploadedImage(
                'evidence.png',
                $this->pngBytes(),
                'image/png'
            ),
        ], ['Accept' => 'application/json']);

        $response->assertCreated()->assertJsonPath('data.ai_processing_status', 'failed');
        $this->assertDatabaseHas('violation_reports', [
            'report_number' => $response->json('data.report_number'),
            'ai_processing_status' => 'failed',
        ]);
    }

    public function test_dilg_admin_can_retry_failed_ai_and_dashboard_displays_advisory(): void
    {
        $admin = User::factory()->create(['role' => 'dilg_admin', 'assigned_barangay' => null]);
        $report = $this->report(['ai_processing_status' => 'failed']);
        Http::fake(['*/predict/multimodal' => Http::response($this->aiResponse())]);

        $this->actingAs($admin)
            ->post(route('violation-reports.retry-ai', $report))
            ->assertRedirect();

        $this->assertSame('completed', $report->refresh()->ai_processing_status);
        $this->actingAs($admin)
            ->get(route('violation-reports.show', $report))
            ->assertOk()
            ->assertSee('AI Initial Assessment')
            ->assertSee('AI Suggested Violation')
            ->assertSee('Illegal Parking')
            ->assertSee('Photo and description suggest the same violation')
            ->assertSee('AI suggestions are for initial assessment only. Staff must review the evidence and confirm the official violation.')
            ->assertDontSee('Final AI Classification')
            ->assertDontSee('NLP Prediction')
            ->assertDontSee('illegal_parking')
            ->assertDontSee('image_text_agreement');
    }

    public function test_public_tracking_exposes_safe_ai_summary_only(): void
    {
        $report = $this->report([
            'ai_processing_status' => 'completed',
            'final_ai_prediction' => 'illegal_parking',
            'final_ai_confidence' => 0.81,
            'ai_possible_violation' => 'illegal_parking',
            'ai_possible_violation_confidence' => 0.81,
            'ai_needs_manual_review' => false,
            'ai_raw_response' => ['internal' => 'must remain private'],
        ]);

        $trackingToken = app(ReportCredentialService::class)->replayToken($report);

        $this->getJson('/api/mobile/reports/status/'.$trackingToken)
            ->assertOk()
            ->assertJsonPath('data.final_ai_category', 'illegal_parking')
            ->assertJsonMissing(['internal' => 'must remain private'])
            ->assertJsonMissingPath('data.ai_raw_response');
    }
}
