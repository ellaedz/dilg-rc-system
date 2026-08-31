<?php

namespace Tests\Feature;

use App\Models\ReportTimeline;
use App\Models\User;
use App\Models\ViolationReport;
use App\Services\ProcessReportAi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CreatesPhase8cResponses;
use Tests\Support\CreatesTestImages;
use Tests\TestCase;

class Phase8ELaravelAiOrchestrationTest extends TestCase
{
    use CreatesPhase8cResponses;
    use CreatesTestImages;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('report_photos');
        Storage::fake('report_photo_quarantine');
        Storage::fake('public');
        Http::preventStrayRequests();
    }

    public function test_phase_8e_schema_is_additive(): void
    {
        foreach ([
            'ai_processing_attempts',
            'ai_request_id',
            'ai_processing_token_hash',
            'ai_processing_started_at',
            'ai_processing_expires_at',
            'ai_last_attempted_at',
            'ai_image_prediction',
            'ai_image_confidence',
            'ai_image_status',
            'ai_image_detections',
            'ai_gis_result',
            'ai_model_metadata',
            'ai_timing',
            'ai_manual_review_reasons',
        ] as $column) {
            $this->assertTrue(Schema::hasColumn('violation_reports', $column), $column);
        }

        $this->assertTrue(Schema::hasColumn('violation_reports', 'official_violation_type'));
        $this->assertTrue(Schema::hasColumn('violation_reports', 'photo_object_key'));
    }

    public function test_stored_sanitized_photo_is_sent_and_complete_result_is_persisted(): void
    {
        $outbound = null;
        Http::fake(function (Request $request) use (&$outbound) {
            $file = collect($request->data())->firstWhere('name', 'image');
            $attachedBytes = is_resource($file['contents'] ?? null)
                ? stream_get_contents($file['contents'])
                : null;
            if (is_resource($file['contents'] ?? null)) {
                rewind($file['contents']);
            }
            $outbound = [
                'url' => $request->url(),
                'headers' => $request->headers(),
                'data' => $request->data(),
                'filename' => $file['filename'] ?? null,
                'bytes' => $attachedBytes,
            ];

            return Http::response($this->phase8cResponse());
        });

        $submission = $this->submit([
            'image_result' => 'road_obstruction',
            'image_confidence' => 0.999,
            'image_validation_status' => 'accepted',
            'image_model_version' => 'untrusted-mobile-model',
            'needs_manual_review' => false,
        ]);

        $submission->assertCreated()
            ->assertJsonPath('data.photo_upload_status', 'uploaded')
            ->assertJsonPath('data.ai_processing_status', 'completed')
            ->assertJsonPath('data.final_ai_category', 'illegal_parking');

        $report = ViolationReport::firstOrFail();
        $storedBytes = Storage::disk('report_photos')->get($report->photo_object_key);

        $this->assertSame(
            'http://127.0.0.1:9000/v1/predict/multimodal',
            $outbound['url']
        );
        $this->assertArrayHasKey('Accept', $outbound['headers']);
        $this->assertArrayHasKey('X-Request-ID', $outbound['headers']);
        $this->assertSame('report-evidence.png', $outbound['filename']);
        $this->assertSame($storedBytes, $outbound['bytes']);
        $this->assertTrue(collect($outbound['data'])->contains(
            fn (array $part): bool => ($part['name'] ?? null) === 'text_report'
                && ($part['contents'] ?? null) === 'A vehicle is blocking the public road.'
        ));
        $serializedOutbound = serialize($outbound['data']);
        $this->assertStringNotContainsString((string) $report->photo_object_key, $serializedOutbound);
        $this->assertStringNotContainsString((string) $report->tracking_token_hash, $serializedOutbound);

        $this->assertSame('illegal_parking', $report->ai_image_prediction);
        $this->assertSame('0.820000', $report->ai_image_confidence);
        $this->assertSame('illegal_parking', $report->text_prediction);
        $this->assertSame('illegal_parking', $report->final_ai_prediction);
        $this->assertSame('illegal_parking', $report->ai_possible_violation);
        $this->assertSame(1, $report->ai_processing_attempts);
        $this->assertNotNull($report->ai_request_id);
        $this->assertNull($report->ai_processing_token_hash);
        $this->assertNull($report->processing_error_code);
        $this->assertNull($report->official_violation_type);
        $this->assertNull($report->verified_by);
        $this->assertNull($report->verified_at);
        $this->assertSame('Pending', $report->verification_status);
        $this->assertSame('barangay_boundary_unavailable', $report->barangay_detection_status);
        $this->assertSame(
            'barangay_boundary_unavailable',
            $report->ai_gis_result['barangay_assignment_status']
        );
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_mobile_ai_fields_are_ignored_even_when_fastapi_fails(): void
    {
        Http::fake([
            '*/v1/predict/multimodal' => Http::response(['unexpected' => true]),
        ]);

        $this->submit([
            'image_result' => 'road_obstruction',
            'image_confidence' => 0.999,
            'image_validation_status' => 'accepted',
            'image_model_version' => 'untrusted-mobile-model',
        ])->assertCreated()->assertJsonPath('data.ai_processing_status', 'failed');

        $report = ViolationReport::firstOrFail();
        $this->assertNull($report->predicted_violation_category);
        $this->assertNull($report->confidence_score);
        $this->assertNull($report->image_model_version);
        $this->assertNull($report->official_violation_type);
        $this->assertSame('FASTAPI_SCHEMA_INVALID', $report->processing_error_code);
    }

    public function test_fastapi_offline_preserves_report_credentials_timeline_and_photo(): void
    {
        Http::fake(fn () => throw new ConnectionException('offline'));

        $submission = $this->submit();
        $submission->assertCreated()
            ->assertJsonPath('data.ai_processing_status', 'failed')
            ->assertJsonPath('data.photo_upload_status', 'uploaded');

        $report = ViolationReport::firstOrFail();
        $this->assertMatchesRegularExpression(
            '/\A[A-Za-z0-9_-]{43}\z/D',
            $submission->json('data.tracking_token')
        );
        $this->assertSame(1, ReportTimeline::count());
        Storage::disk('report_photos')->assertExists($report->photo_object_key);
        $this->assertSame('FASTAPI_UNAVAILABLE', $report->processing_error_code);
        $this->assertSame(1, $report->ai_processing_attempts);
    }

    public function test_public_replay_does_not_retry_failed_ai(): void
    {
        $attempts = 0;
        Http::fake(function () use (&$attempts) {
            $attempts++;

            throw new ConnectionException('offline');
        });
        $key = 'phase-8e-public-replay-00000001';

        $first = $this->submit([], $key);
        $second = $this->submit([], $key);

        $first->assertCreated()->assertJsonPath('data.ai_processing_status', 'failed');
        $second->assertOk()
            ->assertJsonPath('data.idempotent_replay', true)
            ->assertJsonPath('data.ai_processing_status', 'failed');
        $this->assertSame(1, ViolationReport::count());
        $this->assertSame(1, ViolationReport::firstOrFail()->ai_processing_attempts);
        $this->assertSame(1, $attempts);
    }

    public function test_replay_that_recovers_first_photo_upload_may_start_first_ai_attempt(): void
    {
        Http::fake([
            '*/v1/predict/multimodal' => Http::response($this->phase8cResponse()),
        ]);
        $key = 'phase-8e-photo-recovery-000000001';

        $first = $this->submitWithPhoto(
            $this->uploadedImage('invalid.jpg', 'not an image', 'image/jpeg'),
            [],
            $key
        );
        $second = $this->submit([], $key);

        $first->assertCreated()
            ->assertJsonPath('data.photo_upload_status', 'failed_validation')
            ->assertJsonPath('data.ai_processing_status', 'pending');
        $second->assertOk()
            ->assertJsonPath('data.idempotent_replay', true)
            ->assertJsonPath('data.photo_upload_status', 'uploaded')
            ->assertJsonPath('data.ai_processing_status', 'completed');
        $this->assertSame(1, ViolationReport::firstOrFail()->ai_processing_attempts);
        Http::assertSentCount(1);
    }

    public function test_invalid_success_schema_is_controlled_without_partial_persistence(): void
    {
        $invalid = $this->phase8cResponse([
            'image' => [
                'detections' => [[
                    'class_id' => 2,
                    'class_name' => 'illegal_parking',
                    'confidence' => 0.82,
                    'x_center' => 2.0,
                    'y_center' => 1.5,
                    'width' => 4.0,
                    'height' => 3.0,
                    'x_min' => 3.0,
                    'y_min' => 0.0,
                    'x_max' => 1.0,
                    'y_max' => 3.0,
                ]],
            ],
        ]);
        Http::fake([
            '*/v1/predict/multimodal' => Http::response($invalid),
        ]);

        $this->submit()->assertCreated()->assertJsonPath('data.ai_processing_status', 'failed');

        $report = ViolationReport::firstOrFail();
        $this->assertSame('FASTAPI_SCHEMA_INVALID', $report->processing_error_code);
        $this->assertNull($report->ai_image_prediction);
        $this->assertNull($report->final_ai_prediction);
        $this->assertNull($report->ai_raw_response);
    }

    public function test_fastapi_422_is_request_rejection_not_model_outage(): void
    {
        Http::fake([
            '*/v1/predict/multimodal' => Http::response([
                'error' => [
                    'code' => 'invalid_coordinates',
                    'message' => 'Coordinates are outside valid ranges.',
                ],
            ], 422),
        ]);

        $this->submit()->assertCreated()->assertJsonPath('data.ai_processing_status', 'failed');
        $this->assertSame(
            'FASTAPI_REQUEST_REJECTED',
            ViolationReport::firstOrFail()->processing_error_code
        );
    }

    #[DataProvider('httpFailureCases')]
    public function test_fastapi_http_failures_are_classified(
        int $status,
        string $expectedCode
    ): void {
        Http::fake([
            '*/v1/predict/multimodal' => Http::response([
                'error' => [
                    'code' => 'documented_error',
                    'message' => 'A safe documented operational error.',
                ],
            ], $status),
        ]);

        $this->submit(
            [],
            'phase-8e-http-'.$status.'-'.bin2hex(random_bytes(8))
        )->assertCreated()->assertJsonPath('data.ai_processing_status', 'failed');

        $this->assertSame(
            $expectedCode,
            ViolationReport::firstOrFail()->processing_error_code
        );
    }

    public static function httpFailureCases(): array
    {
        return [
            'bad request' => [400, 'FASTAPI_REQUEST_REJECTED'],
            'access rejected' => [403, 'FASTAPI_ACCESS_REJECTED'],
            'endpoint missing' => [404, 'FASTAPI_ENDPOINT_NOT_FOUND'],
            'request timeout' => [408, 'FASTAPI_TIMEOUT'],
            'rate limited' => [429, 'FASTAPI_RATE_LIMITED'],
            'internal error' => [500, 'FASTAPI_INTERNAL_ERROR'],
            'service unavailable' => [503, 'FASTAPI_UNAVAILABLE'],
        ];
    }

    public function test_malformed_and_excessive_success_responses_are_rejected(): void
    {
        $attempt = 0;
        Http::fake(function () use (&$attempt) {
            $attempt++;

            return $attempt === 1
                ? Http::response('{not-json', 200, ['Content-Type' => 'application/json'])
                : Http::response(str_repeat('x', 4097), 200);
        });

        $this->submit([], 'phase-8e-malformed-json-000001')
            ->assertCreated()
            ->assertJsonPath('data.ai_processing_status', 'failed');
        $first = ViolationReport::firstOrFail();
        $this->assertSame('FASTAPI_INVALID_JSON', $first->processing_error_code);

        config()->set('ai_inference.max_response_bytes', 4096);
        $this->submit([], 'phase-8e-large-response-000001')
            ->assertCreated()
            ->assertJsonPath('data.ai_processing_status', 'failed');
        $second = ViolationReport::latest('id')->firstOrFail();
        $this->assertSame('FASTAPI_RESPONSE_TOO_LARGE', $second->processing_error_code);
        $this->assertNull($second->ai_raw_response);
    }

    public function test_nullable_text_and_fusion_results_follow_phase_8c_contract(): void
    {
        $response = $this->phase8cResponse([
            'image' => [
                'prediction' => null,
                'confidence' => 0.0,
                'detection_count' => 0,
                'status' => 'no_detection',
            ],
            'text' => [
                'prediction' => null,
                'confidence' => 0.0,
                'needs_manual_review' => true,
            ],
            'fusion' => [
                'final_violation_type' => null,
                'final_confidence' => 0.0,
                'decision_source' => 'weak_evidence_manual_review',
            ],
            'review' => [
                'ai_needs_manual_review' => true,
                'ai_manual_review_reasons' => [
                    'no_image_detection',
                    'low_text_confidence',
                    'insufficient_fusion_confidence',
                ],
            ],
        ]);
        $response['image']['detections'] = [];
        Http::fake([
            '*/v1/predict/multimodal' => Http::response($response),
        ]);

        $this->submit()->assertCreated()
            ->assertJsonPath('data.ai_processing_status', 'completed')
            ->assertJsonPath('data.ai_needs_manual_review', true);

        $report = ViolationReport::firstOrFail();
        $this->assertNull($report->ai_image_prediction);
        $this->assertNull($report->text_prediction);
        $this->assertNull($report->final_ai_prediction);
        $this->assertNull($report->ai_possible_violation);
        $this->assertSame(
            [
                'no_image_detection',
                'low_text_confidence',
                'insufficient_fusion_confidence',
            ],
            $report->ai_manual_review_reasons
        );
    }

    public function test_fastapi_reported_image_dimensions_must_match_stored_photo(): void
    {
        Http::fake([
            '*/v1/predict/multimodal' => Http::response(
                $this->phase8cResponse([], 100, 100)
            ),
        ]);

        $this->submit()->assertCreated()->assertJsonPath('data.ai_processing_status', 'failed');
        $this->assertSame(
            'FASTAPI_IMAGE_DIMENSIONS_MISMATCH',
            ViolationReport::firstOrFail()->processing_error_code
        );
    }

    public function test_staff_retry_reuses_same_report_and_photo(): void
    {
        $attempt = 0;
        Http::fake(function () use (&$attempt) {
            $attempt++;
            if ($attempt === 1) {
                throw new ConnectionException('offline');
            }

            return Http::response($this->phase8cResponse());
        });
        $submission = $this->submit();
        $report = ViolationReport::firstOrFail();
        $objectKey = $report->photo_object_key;
        $tokenHash = $report->tracking_token_hash;
        $admin = User::factory()->create(['role' => 'dilg_admin']);

        $this->actingAs($admin)
            ->post(route('violation-reports.retry-ai', $report))
            ->assertRedirect();

        $report->refresh();
        $this->assertSame(
            'completed',
            $report->ai_processing_status,
            (string) $report->processing_error_code
        );
        $this->assertSame(2, $report->ai_processing_attempts);
        $this->assertSame($objectKey, $report->photo_object_key);
        $this->assertSame($tokenHash, $report->tracking_token_hash);
        $this->assertSame(1, ViolationReport::count());
        $this->assertSame(
            $submission->json('data.report_number'),
            $report->report_number
        );
    }

    public function test_non_dilg_user_cannot_trigger_retry(): void
    {
        Http::fake(fn () => throw new ConnectionException('offline'));
        $this->submit();
        $report = ViolationReport::firstOrFail();
        $staff = User::factory()->create([
            'role' => 'barangay_staff',
            'assigned_barangay' => 'Pagsawitan',
        ]);

        $this->actingAs($staff)
            ->post(route('violation-reports.retry-ai', $report))
            ->assertForbidden();
        $this->assertSame(1, $report->refresh()->ai_processing_attempts);
    }

    public function test_live_lease_is_not_claimed_and_expired_lease_requires_staff(): void
    {
        $attempt = 0;
        Http::fake(function () use (&$attempt) {
            $attempt++;
            if ($attempt === 1) {
                throw new ConnectionException('offline');
            }

            return Http::response($this->phase8cResponse());
        });
        $this->submit();
        $report = ViolationReport::firstOrFail();
        $report->forceFill([
            'ai_processing_status' => 'processing',
            'ai_processing_token_hash' => hash('sha256', 'live-owner'),
            'ai_processing_started_at' => now(),
            'ai_processing_expires_at' => now()->addMinute(),
        ])->save();

        $result = app(ProcessReportAi::class)->process(
            $report,
            ProcessReportAi::TRIGGER_STAFF_RETRY
        );
        $this->assertSame('already_processing', $result->outcome);
        $this->assertSame(1, $report->refresh()->ai_processing_attempts);

        $report->forceFill([
            'ai_processing_started_at' => now()->subMinutes(2),
            'ai_processing_expires_at' => now()->subMinute(),
        ])->save();
        $initial = app(ProcessReportAi::class)->process(
            $report,
            ProcessReportAi::TRIGGER_INITIAL
        );
        $this->assertSame('not_eligible', $initial->outcome);

        $retry = app(ProcessReportAi::class)->process(
            $report,
            ProcessReportAi::TRIGGER_STAFF_RETRY
        );
        $this->assertTrue($retry->completed(), (string) $retry->errorCode);
        $this->assertSame(2, $report->refresh()->ai_processing_attempts);
    }

    public function test_stale_worker_cannot_overwrite_newer_owner(): void
    {
        Http::fake(function () {
            $report = ViolationReport::firstOrFail();
            $report->forceFill([
                'ai_processing_token_hash' => hash('sha256', 'new-owner'),
                'ai_request_id' => '00000000-0000-4000-8000-000000000001',
                'ai_processing_expires_at' => now()->addMinute(),
            ])->save();

            return Http::response($this->phase8cResponse());
        });

        $this->submit()->assertCreated();

        $report = ViolationReport::firstOrFail();
        $this->assertSame('processing', $report->ai_processing_status);
        $this->assertSame(hash('sha256', 'new-owner'), $report->ai_processing_token_hash);
        $this->assertNull($report->final_ai_prediction);
    }

    public function test_integrity_mismatch_fails_without_deleting_private_photo(): void
    {
        $attempts = 0;
        Http::fake(function () use (&$attempts) {
            $attempts++;

            throw new ConnectionException('offline');
        });
        $this->submit();
        $report = ViolationReport::firstOrFail();
        Storage::disk('report_photos')->put($report->photo_object_key, 'tampered bytes');

        $result = app(ProcessReportAi::class)->process(
            $report,
            ProcessReportAi::TRIGGER_STAFF_RETRY
        );

        $this->assertSame('failed', $result->outcome);
        $this->assertSame(
            'AI_PHOTO_INTEGRITY_MISMATCH',
            $report->refresh()->processing_error_code
        );
        Storage::disk('report_photos')->assertExists($report->photo_object_key);
        $this->assertSame(1, $attempts);
    }

    public function test_public_responses_hide_phase_8e_internal_fields(): void
    {
        Http::fake([
            '*/v1/predict/multimodal' => Http::response($this->phase8cResponse()),
        ]);
        $submission = $this->submit();

        $submission->assertJsonMissingPath('data.ai_request_id')
            ->assertJsonMissingPath('data.ai_processing_token_hash')
            ->assertJsonMissingPath('data.ai_raw_response')
            ->assertJsonMissingPath('data.ai_image_detections')
            ->assertJsonMissingPath('data.ai_gis_result')
            ->assertJsonMissingPath('data.photo_object_key');

        $this->withToken($submission->json('data.tracking_token'))
            ->getJson('/api/mobile/reports/status')
            ->assertOk()
            ->assertJsonMissingPath('data.ai_request_id')
            ->assertJsonMissingPath('data.ai_processing_token_hash')
            ->assertJsonMissingPath('data.ai_raw_response')
            ->assertJsonMissingPath('data.photo_object_key');
    }

    private function submit(
        array $overrides = [],
        string $idempotencyKey = 'phase-8e-submission-key-000000001'
    ) {
        return $this->submitWithPhoto(
            $this->uploadedImage('evidence.png', $this->pngBytes(), 'image/png'),
            $overrides,
            $idempotencyKey
        );
    }

    private function submitWithPhoto(
        mixed $photo,
        array $overrides,
        string $idempotencyKey
    ) {
        return $this->post('/api/mobile/reports', array_merge([
            'description' => 'A vehicle is blocking the public road.',
            'selected_violation_type' => 'Illegal Parking',
            'latitude' => 14.281,
            'longitude' => 121.416,
            'gps_accuracy' => 8.5,
            'timestamp' => '2026-07-29T08:00:00Z',
            'photo' => $photo,
        ], $overrides), [
            'Accept' => 'application/json',
            'Idempotency-Key' => $idempotencyKey,
        ]);
    }
}
