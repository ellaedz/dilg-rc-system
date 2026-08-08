<?php

namespace Tests\Feature;

use App\Contracts\PrivateReportPhotoStorage;
use App\Models\ReportTimeline;
use App\Models\User;
use App\Models\ViolationReport;
use App\Services\LocalPrivateReportPhotoStorage;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\Support\CreatesTestImages;
use Tests\TestCase;

class InspectableFailingPhotoStorage implements PrivateReportPhotoStorage
{
    public function __construct(private readonly Closure $beforeFailure) {}

    public function diskName(): string
    {
        return 'report_photos';
    }

    public function generateObjectKey(string $extension): string
    {
        return 'reports/AA/'.str_repeat('A', 43).'.'.$extension;
    }

    public function put(string $objectKey, string $contents): void
    {
        ($this->beforeFailure)();

        throw new RuntimeException('simulated private storage failure');
    }

    public function readStream(string $objectKey)
    {
        throw new RuntimeException('unavailable');
    }

    public function exists(string $objectKey): bool
    {
        return false;
    }

    public function delete(string $objectKey): bool
    {
        return true;
    }
}

class PartialWriteFailingPhotoStorage extends InspectableFailingPhotoStorage
{
    public function __construct()
    {
        parent::__construct(fn () => null);
    }

    public function put(string $objectKey, string $contents): void
    {
        Storage::disk($this->diskName())->put($objectKey, $contents);

        throw new RuntimeException('simulated failure after partial write');
    }

    public function exists(string $objectKey): bool
    {
        return Storage::disk($this->diskName())->exists($objectKey);
    }

    public function delete(string $objectKey): bool
    {
        return false;
    }
}

class Phase8DDurablePhotoPipelineTest extends TestCase
{
    use CreatesTestImages;
    use RefreshDatabase;

    private const IDEMPOTENCY_KEY = 'phase-8d-idempotency-key-000001';

    private const TIMESTAMP = '2026-07-29T08:00:00Z';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('report_photos');
        Storage::fake('report_photo_quarantine');
        Storage::fake('public');
        Http::fake([
            '*/predict/multimodal' => Http::response([
                'final_violation_type' => 'illegal_parking',
                'final_confidence' => 0.81,
                'decision_source' => 'image_text_agreement',
                'needs_manual_review' => false,
                'text_result' => [
                    'prediction' => 'illegal_parking',
                    'confidence' => 0.74,
                    'model_version' => 'phase-8d-transitional-ai',
                ],
            ]),
        ]);
    }

    public function test_new_photo_is_sanitized_privately_before_legacy_ai_runs(): void
    {
        $original = $this->pngWithTextMetadata(true);
        $response = $this->submit(
            self::IDEMPOTENCY_KEY,
            $this->uploadedImage('citizen-name.png', $original, 'image/png')
        );

        $response->assertCreated()
            ->assertJsonPath('data.photo_upload_status', 'uploaded')
            ->assertJsonPath('data.photo_available', true)
            ->assertJsonPath('data.photo_result.status', 'uploaded')
            ->assertJsonMissingPath('data.image_url')
            ->assertJsonMissingPath('data.photo_object_key')
            ->assertJsonMissingPath('data.photo_storage_disk')
            ->assertJsonMissingPath('data.image_path');

        $report = ViolationReport::firstOrFail();
        $this->assertNull($report->image_path);
        $this->assertNotNull($report->photo_object_key);
        $this->assertStringNotContainsString('citizen-name', $report->photo_object_key);
        $this->assertNull($report->photo_pending_object_key);
        $this->assertNull($report->photo_processing_token_hash);
        $this->assertSame('image/png', $report->photo_mime_type);
        $this->assertSame(hash(
            'sha256',
            Storage::disk('report_photos')->get($report->photo_object_key)
        ), $report->photo_sha256);
        $this->assertNotSame(
            $original,
            Storage::disk('report_photos')->get($report->photo_object_key)
        );
        Storage::disk('report_photos')->assertExists($report->photo_object_key);
        $this->assertSame([], Storage::disk('public')->allFiles());
        Http::assertSentCount(1);
    }

    public function test_phase_8d_schema_is_additive_and_separates_legacy_paths(): void
    {
        foreach ([
            'submission_payload_hash',
            'photo_object_key',
            'photo_pending_object_key',
            'photo_storage_disk',
            'photo_mime_type',
            'photo_size_bytes',
            'photo_width',
            'photo_height',
            'photo_sha256',
            'photo_upload_attempts',
            'photo_upload_error_code',
            'photo_uploaded_at',
            'photo_processing_token_hash',
            'photo_processing_started_at',
            'photo_processing_expires_at',
            'photo_compensation_status',
        ] as $column) {
            $this->assertTrue(Schema::hasColumn('violation_reports', $column), $column);
        }

        $this->assertTrue(Schema::hasColumn('violation_reports', 'image_path'));
    }

    public function test_invalid_photo_preserves_report_token_and_timeline_without_ai(): void
    {
        $response = $this->submit(
            self::IDEMPOTENCY_KEY,
            $this->uploadedImage('fake.jpg', 'not an image', 'image/jpeg')
        );

        $response->assertCreated()
            ->assertJsonPath('data.photo_upload_status', 'failed_validation')
            ->assertJsonPath('data.photo_result.error_code', 'PHOTO_UNSUPPORTED_TYPE');

        $this->assertMatchesRegularExpression(
            '/^[A-Za-z0-9_-]{43}$/',
            $response->json('data.tracking_token')
        );
        $this->assertSame(1, ViolationReport::count());
        $this->assertSame(1, ReportTimeline::count());
        $this->assertSame(
            'failed_validation',
            ViolationReport::firstOrFail()->photo_upload_status
        );
        Http::assertNothingSent();
    }

    public function test_oversized_application_photo_fails_after_report_creation(): void
    {
        config()->set('report_photos.max_bytes', 32);

        $response = $this->submit(
            self::IDEMPOTENCY_KEY,
            $this->uploadedImage('large.png', $this->pngBytes(20, 20), 'image/png')
        );

        $response->assertCreated()
            ->assertJsonPath('data.photo_upload_status', 'failed_validation')
            ->assertJsonPath('data.photo_result.error_code', 'PHOTO_TOO_LARGE');
        $this->assertSame(1, ViolationReport::count());
    }

    public function test_runtime_discard_boundary_returns_safe_413_without_durability_claim(): void
    {
        $response = $this->withServerVariables([
            'CONTENT_LENGTH' => 50 * 1024 * 1024,
        ])->postJson('/api/mobile/reports', []);

        $response->assertStatus(413)
            ->assertJsonPath('error.code', 'REQUEST_BODY_TOO_LARGE');
        $this->assertSame(0, ViolationReport::count());
    }

    public function test_storage_failure_is_report_first_and_same_key_retry_resumes_one_report(): void
    {
        $storageSawDurableReport = false;
        $this->app->instance(
            PrivateReportPhotoStorage::class,
            new InspectableFailingPhotoStorage(function () use (&$storageSawDurableReport): void {
                $storageSawDurableReport = ViolationReport::count() === 1
                    && ReportTimeline::count() === 1;
            })
        );

        $first = $this->submit(
            self::IDEMPOTENCY_KEY,
            $this->uploadedImage('evidence.jpg', $this->jpegBytes(), 'image/jpeg')
        );
        $first->assertCreated()
            ->assertJsonPath('data.photo_upload_status', 'failed_storage')
            ->assertJsonPath('data.photo_result.error_code', 'PHOTO_STORAGE_FAILED');
        $this->assertTrue($storageSawDurableReport);
        $firstToken = $first->json('data.tracking_token');

        $this->app->forgetInstance(PrivateReportPhotoStorage::class);
        $this->app->bind(
            PrivateReportPhotoStorage::class,
            LocalPrivateReportPhotoStorage::class
        );

        $second = $this->submit(
            self::IDEMPOTENCY_KEY,
            $this->uploadedImage('evidence.jpg', $this->jpegBytes(), 'image/jpeg')
        );
        $second->assertOk()
            ->assertJsonPath('data.idempotent_replay', true)
            ->assertJsonPath('data.photo_upload_status', 'uploaded')
            ->assertJsonPath('data.tracking_token', $firstToken);

        $this->assertSame(1, ViolationReport::count());
        $this->assertSame(1, ReportTimeline::count());
        $this->assertCount(1, Storage::disk('report_photos')->allFiles());
        Http::assertSentCount(1);
    }

    public function test_failed_validation_replay_with_new_photo_recovers_same_report(): void
    {
        $first = $this->submit(
            self::IDEMPOTENCY_KEY,
            $this->uploadedImage('invalid.jpg', 'invalid evidence', 'image/jpeg')
        );
        $token = $first->json('data.tracking_token');

        $second = $this->submit(
            self::IDEMPOTENCY_KEY,
            $this->uploadedImage('valid.png', $this->pngBytes(), 'image/png')
        );

        $first->assertCreated()
            ->assertJsonPath('data.photo_upload_status', 'failed_validation');
        $second->assertOk()
            ->assertJsonPath('data.photo_upload_status', 'uploaded')
            ->assertJsonPath('data.tracking_token', $token);
        $report = ViolationReport::firstOrFail();
        $this->assertSame(2, $report->photo_upload_attempts);
        $this->assertNull($report->photo_upload_error_code);
        $this->assertSame(1, ViolationReport::count());
        $this->assertSame(1, ReportTimeline::count());
    }

    public function test_partial_write_remains_traceable_until_safe_retry_cleanup(): void
    {
        $this->app->instance(
            PrivateReportPhotoStorage::class,
            new PartialWriteFailingPhotoStorage
        );
        $first = $this->submit(
            self::IDEMPOTENCY_KEY,
            $this->uploadedImage('evidence.jpg', $this->jpegBytes(), 'image/jpeg')
        );

        $first->assertCreated()
            ->assertJsonPath('data.photo_upload_status', 'failed_storage');
        $report = ViolationReport::firstOrFail();
        $staleKey = $report->photo_pending_object_key;
        $this->assertNotNull($staleKey);
        $this->assertSame('delete_failed', $report->photo_compensation_status);
        Storage::disk('report_photos')->assertExists($staleKey);

        $this->app->forgetInstance(PrivateReportPhotoStorage::class);
        $this->app->bind(
            PrivateReportPhotoStorage::class,
            LocalPrivateReportPhotoStorage::class
        );
        $retry = $this->submit(
            self::IDEMPOTENCY_KEY,
            $this->uploadedImage('evidence.jpg', $this->jpegBytes(), 'image/jpeg')
        );

        $retry->assertOk()->assertJsonPath('data.photo_upload_status', 'uploaded');
        $report->refresh();
        Storage::disk('report_photos')->assertMissing($staleKey);
        Storage::disk('report_photos')->assertExists($report->photo_object_key);
        $this->assertNull($report->photo_pending_object_key);
    }

    public function test_completed_identical_replay_is_ok_but_replacement_is_conflict(): void
    {
        $first = $this->submit(
            self::IDEMPOTENCY_KEY,
            $this->uploadedImage('same.png', $this->pngBytes(), 'image/png')
        );
        $second = $this->submit(
            self::IDEMPOTENCY_KEY,
            $this->uploadedImage('same.png', $this->pngBytes(), 'image/png')
        );
        $replacement = $this->submit(
            self::IDEMPOTENCY_KEY,
            $this->uploadedImage('different.jpg', $this->jpegBytes(), 'image/jpeg')
        );

        $first->assertCreated();
        $second->assertOk()
            ->assertJsonPath('data.tracking_token', $first->json('data.tracking_token'))
            ->assertJsonPath('data.photo_result.status', 'uploaded');
        $replacement->assertConflict()
            ->assertJsonPath('error.code', 'PHOTO_REPLACEMENT_NOT_ALLOWED');
        $this->assertSame(1, ViolationReport::count());
        $this->assertCount(1, Storage::disk('report_photos')->allFiles());
        Http::assertSentCount(1);
    }

    public function test_conflicting_non_photo_replay_is_rejected_before_photo_processing(): void
    {
        $first = $this->submit(self::IDEMPOTENCY_KEY);
        $second = $this->submit(
            self::IDEMPOTENCY_KEY,
            $this->uploadedImage('evidence.png', $this->pngBytes(), 'image/png'),
            ['description' => 'Changed complaint details must not replace the original.']
        );

        $first->assertCreated();
        $second->assertConflict()
            ->assertJsonPath('error.code', 'IDEMPOTENCY_PAYLOAD_CONFLICT');
        $this->assertSame(1, ViolationReport::count());
        $this->assertSame('A vehicle is blocking the public road.', ViolationReport::first()->description);
        $this->assertSame([], Storage::disk('report_photos')->allFiles());
    }

    public function test_live_lease_returns_accepted_without_starting_another_attempt(): void
    {
        $first = $this->submit(self::IDEMPOTENCY_KEY);
        $report = ViolationReport::firstOrFail();
        $report->forceFill([
            'photo_upload_status' => 'processing',
            'photo_processing_token_hash' => hash('sha256', 'active-owner'),
            'photo_processing_started_at' => now(),
            'photo_processing_expires_at' => now()->addMinutes(5),
        ])->save();

        $replay = $this->submit(
            self::IDEMPOTENCY_KEY,
            $this->uploadedImage('evidence.png', $this->pngBytes(), 'image/png')
        );

        $replay->assertStatus(202)
            ->assertJsonPath('data.photo_result.status', 'processing');
        $this->assertSame([], Storage::disk('report_photos')->allFiles());
    }

    public function test_expired_lease_cleans_traceable_object_and_recovers(): void
    {
        $this->submit(self::IDEMPOTENCY_KEY);
        $report = ViolationReport::firstOrFail();
        $storage = app(LocalPrivateReportPhotoStorage::class);
        $staleKey = $storage->generateObjectKey('jpg');
        $storage->put($staleKey, 'stale partial object');
        $report->forceFill([
            'photo_upload_status' => 'processing',
            'photo_pending_object_key' => $staleKey,
            'photo_storage_disk' => 'report_photos',
            'photo_processing_token_hash' => hash('sha256', 'crashed-owner'),
            'photo_processing_started_at' => now()->subMinutes(10),
            'photo_processing_expires_at' => now()->subMinutes(5),
        ])->save();

        $replay = $this->submit(
            self::IDEMPOTENCY_KEY,
            $this->uploadedImage('recovery.png', $this->pngBytes(), 'image/png')
        );

        $replay->assertOk()->assertJsonPath('data.photo_upload_status', 'uploaded');
        $report->refresh();
        Storage::disk('report_photos')->assertMissing($staleKey);
        Storage::disk('report_photos')->assertExists($report->photo_object_key);
        $this->assertNotSame($staleKey, $report->photo_object_key);
        $this->assertNull($report->photo_processing_token_hash);
    }

    public function test_private_photo_stream_requires_correct_staff_scope(): void
    {
        $this->assertGuest()
            ->get('/violation-reports/999/photo')
            ->assertRedirect(route('login'));

        $this->submit(
            self::IDEMPOTENCY_KEY,
            $this->uploadedImage('evidence.png', $this->pngBytes(), 'image/png')
        );
        $report = ViolationReport::firstOrFail();
        $report->forceFill(['manually_assigned_barangay' => 'Pagsawitan'])->save();
        $admin = User::factory()->create(['role' => 'dilg_admin']);
        $correctStaff = User::factory()->create([
            'role' => 'barangay_staff',
            'assigned_barangay' => 'Pagsawitan',
        ]);
        $wrongStaff = User::factory()->create([
            'role' => 'barangay_staff',
            'assigned_barangay' => 'Bubukal',
        ]);

        $this->actingAs($admin)
            ->get(route('violation-reports.photo', $report))
            ->assertOk()
            ->assertHeader('content-type', 'image/png')
            ->assertHeader('x-content-type-options', 'nosniff')
            ->assertHeader('cache-control', 'max-age=0, no-store, private');
        $this->actingAs($wrongStaff)
            ->get(route('violation-reports.photo', $report))
            ->assertForbidden();
        $this->actingAs($correctStaff)
            ->get(route('violation-reports.photo', $report))
            ->assertOk();
    }

    public function test_public_tracking_exposes_status_but_no_private_photo_data(): void
    {
        $submission = $this->submit(
            self::IDEMPOTENCY_KEY,
            $this->uploadedImage('evidence.png', $this->pngBytes(), 'image/png')
        );

        $this->getJson('/api/mobile/reports/status/'.$submission->json('data.tracking_token'))
            ->assertOk()
            ->assertJsonPath('data.photo_upload_status', 'uploaded')
            ->assertJsonPath('data.photo_available', true)
            ->assertJsonMissingPath('data.photo_object_key')
            ->assertJsonMissingPath('data.photo_storage_disk')
            ->assertJsonMissingPath('data.image_path')
            ->assertJsonMissingPath('data.image_url');
    }

    public function test_quarantine_cleanup_deletes_only_expired_allowlisted_objects(): void
    {
        $disk = Storage::disk('report_photo_quarantine');
        $expired = 'quarantine/'.str_repeat('A', 43).'.bin';
        $current = 'quarantine/'.str_repeat('B', 43).'.jpg';
        $unrecognized = 'quarantine/not-allowlisted.tmp';
        $disk->put($expired, 'old');
        $disk->put($current, 'new');
        $disk->put($unrecognized, 'keep');
        touch($disk->path($expired), now()->subHours(48)->getTimestamp());

        $this->artisan('photos:purge-quarantine')->assertSuccessful();

        $disk->assertMissing($expired);
        $disk->assertExists($current);
        $disk->assertExists($unrecognized);
    }

    private function submit(
        string $idempotencyKey,
        mixed $photo = null,
        array $overrides = []
    ) {
        $payload = array_merge([
            'description' => 'A vehicle is blocking the public road.',
            'selected_violation_type' => 'Illegal Parking',
            'latitude' => 14.281,
            'longitude' => 121.416,
            'gps_accuracy' => 8.5,
            'timestamp' => self::TIMESTAMP,
        ], $overrides);
        if ($photo !== null) {
            $payload['photo'] = $photo;
        }

        return $this->post('/api/mobile/reports', $payload, [
            'Accept' => 'application/json',
            'Idempotency-Key' => $idempotencyKey,
        ]);
    }
}
