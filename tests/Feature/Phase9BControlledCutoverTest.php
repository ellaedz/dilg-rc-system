<?php

namespace Tests\Feature;

use App\Contracts\PrivateReportPhotoStorage;
use App\Contracts\ReportAiDispatcher;
use App\Contracts\ResolvesPrivateReportPhotoStorage;
use App\Data\AiProcessingResult;
use App\Models\User;
use App\Models\ViolationReport;
use App\Services\LocalPrivateReportPhotoStorage;
use App\Services\Phase9BControlledCutoverService;
use App\Services\ReportPhotoStorageResolver;
use App\Services\SupabasePrivateReportPhotoStorage;
use ArrayObject;
use DateTimeInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class Phase9BControlledCutoverTest extends TestCase
{
    use RefreshDatabase;

    public function test_controlled_cutover_uses_normal_pipeline_marks_test_data_and_preserves_rollback(): void
    {
        Storage::fake('report_photos');
        config()->set('report_photos.driver', 'supabase');
        config()->set(
            'filesystems.disks.supabase_report_photos.endpoint',
            'https://abcdefghijklmnopqrst.storage.supabase.co/storage/v1/s3',
        );
        $objects = new ArrayObject;
        $remote = new Phase9BStage4TestSupabaseStorage($objects);
        $local = new LocalPrivateReportPhotoStorage;
        $resolver = new ReportPhotoStorageResolver($local, $remote);
        $this->app->instance(SupabasePrivateReportPhotoStorage::class, $remote);
        $this->app->instance(PrivateReportPhotoStorage::class, $remote);
        $this->app->instance(ReportPhotoStorageResolver::class, $resolver);
        $this->app->instance(ResolvesPrivateReportPhotoStorage::class, $resolver);
        $this->app->instance(
            ReportAiDispatcher::class,
            new Phase9BStage4TestAiDispatcher($remote),
        );
        User::create([
            'name' => 'Phase 9B Test Admin',
            'email' => 'phase9b-stage4@example.test',
            'password' => Hash::make('not-used'),
            'role' => 'dilg_admin',
        ]);

        $first = $this->app->make(Phase9BControlledCutoverService::class)->run();

        $this->assertTrue($first['new_test_row_created']);
        $this->assertTrue($first['is_test_data']);
        $this->assertTrue($first['remote_integrity_verified']);
        $this->assertTrue($first['local_rollback_copy_verified']);
        $this->assertTrue($first['authorized_staff_stream_verified']);
        $this->assertTrue($first['unauthorized_staff_denied']);
        $this->assertTrue($first['public_tracking_storage_safe']);
        $this->assertSame(1, $first['uploaded_photos']);
        $this->assertSame(1, $first['supabase_references']);
        $this->assertSame(1, ViolationReport::query()->where('is_test_data', true)->count());
        $report = ViolationReport::query()->firstOrFail();
        $this->assertSame('supabase_report_photos', $report->photo_storage_disk);
        $this->assertSame('failed', $report->ai_processing_status);
        Storage::disk('report_photos')->assertExists((string) $report->photo_object_key);

        $second = $this->app->make(Phase9BControlledCutoverService::class)->run();
        $this->assertFalse($second['new_test_row_created']);
        $this->assertSame(1, ViolationReport::query()->count());
        $this->assertCount(1, $objects);
    }
}

class Phase9BStage4TestSupabaseStorage extends SupabasePrivateReportPhotoStorage
{
    public function __construct(private readonly ArrayObject $objects) {}

    public function assertReady(): void {}

    public function diskName(): string
    {
        return 'supabase_report_photos';
    }

    public function generateObjectKey(string $extension): string
    {
        $token = str_repeat('S', 43);

        return 'reports/SS/'.$token.'.'.$extension;
    }

    public function exists(string $objectKey): bool
    {
        return $this->objects->offsetExists($objectKey);
    }

    public function put(string $objectKey, string $contents): void
    {
        if ($this->exists($objectKey)) {
            throw new RuntimeException('Private photograph object-key collision.');
        }
        $this->objects[$objectKey] = $contents;
    }

    public function readStream(string $objectKey)
    {
        $stream = fopen('php://temp', 'w+b');
        fwrite($stream, (string) $this->objects[$objectKey]);
        rewind($stream);

        return $stream;
    }

    public function temporaryUrl(string $objectKey, DateTimeInterface $expiration): string
    {
        return 'https://abcdefghijklmnopqrst.storage.supabase.co/storage/v1/s3/'
            .$objectKey.'?X-Amz-Signature=test';
    }
}

class Phase9BStage4TestAiDispatcher implements ReportAiDispatcher
{
    public function __construct(
        private readonly Phase9BStage4TestSupabaseStorage $storage,
    ) {}

    public function dispatch(
        ViolationReport $report,
        string $trigger = ReportAiDispatcher::TRIGGER_INITIAL,
    ): AiProcessingResult {
        $stream = $this->storage->readStream((string) $report->photo_object_key);
        try {
            $bytes = stream_get_contents($stream);
        } finally {
            fclose($stream);
        }
        if (! is_string($bytes)
            || ! hash_equals((string) $report->photo_sha256, hash('sha256', $bytes))) {
            throw new RuntimeException('The AI storage read failed.');
        }
        $report->forceFill([
            'ai_processing_status' => ViolationReport::AI_STATUS_FAILED,
            'ai_processing_attempts' => 1,
            'processing_error_code' => 'FASTAPI_UNAVAILABLE',
            'processing_error_message' => 'Controlled test dispatcher verified remote evidence.',
        ])->saveQuietly();

        return new AiProcessingResult(
            'failed',
            'FASTAPI_UNAVAILABLE',
            'Controlled test dispatcher verified remote evidence.',
        );
    }
}
