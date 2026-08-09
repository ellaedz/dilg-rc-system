<?php

namespace Tests\Feature;

use App\Models\ViolationReport;
use App\Services\LocalPrivateReportPhotoStorage;
use App\Services\Phase9BPhotoMigrationService;
use App\Services\SupabasePrivateReportPhotoStorage;
use ArrayObject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class Phase9BPhotoMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_migration_switches_only_disk_reference_and_preserves_local_files(): void
    {
        Storage::fake('report_photos');
        $bytes = 'verified-sanitized-photo';
        $key = 'reports/AA/'.str_repeat('A', 43).'.jpg';
        $orphan = 'reports/BB/'.str_repeat('B', 43).'.jpg';
        Storage::disk('report_photos')->put($key, $bytes);
        Storage::disk('report_photos')->put($orphan, 'classified-later');
        $remoteObjects = new ArrayObject;
        $service = new Phase9BPhotoMigrationService(
            new Phase9BTestLocalStorage,
            new Phase9BTestSupabaseStorage($remoteObjects),
        );
        $report = $this->report($key, $bytes);

        $before = $service->inspect();
        $this->assertSame(1, $before['local_references']);
        $this->assertSame(2, $before['local_files']);
        $this->assertSame(1, $before['local_orphans']);

        $result = $service->migrate();

        $this->assertSame(1, $result['migrated']);
        $this->assertSame(0, $result['local_references']);
        $this->assertSame(1, $result['supabase_references']);
        $this->assertSame('supabase_report_photos', $report->fresh()->photo_storage_disk);
        $this->assertSame($bytes, $remoteObjects[$key]);
        Storage::disk('report_photos')->assertExists([$key, $orphan]);

        $replay = $service->migrate();
        $this->assertSame(0, $replay['migrated']);
        $this->assertSame(1, $replay['supabase_references']);
    }

    public function test_mismatched_preexisting_remote_object_stops_without_database_change(): void
    {
        Storage::fake('report_photos');
        $bytes = 'verified-sanitized-photo';
        $key = 'reports/CC/'.str_repeat('C', 43).'.jpg';
        Storage::disk('report_photos')->put($key, $bytes);
        $remoteObjects = new ArrayObject([$key => 'different-remote-bytes']);
        $service = new Phase9BPhotoMigrationService(
            new Phase9BTestLocalStorage,
            new Phase9BTestSupabaseStorage($remoteObjects),
        );
        $report = $this->report($key, $bytes);

        try {
            $service->migrate();
            $this->fail('A mismatched preexisting remote object must stop migration.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('SHA-256', $exception->getMessage());
        }

        $this->assertSame('report_photos', $report->fresh()->photo_storage_disk);
        $this->assertSame('different-remote-bytes', $remoteObjects[$key]);
        Storage::disk('report_photos')->assertExists($key);
    }

    private function report(string $key, string $bytes): ViolationReport
    {
        return ViolationReport::create([
            'report_id' => 'RCV-2026-'.random_int(1000, 9999),
            'submitted_by' => 'Anonymous Citizen',
            'description' => 'Phase 9B migration test.',
            'selected_violation_type' => 'Unclassified',
            'status' => 'Submitted',
            'report_status' => 'Submitted',
            'verification_status' => 'Pending',
            'date_submitted' => '2026-08-09',
            'photo_object_key' => $key,
            'photo_storage_disk' => 'report_photos',
            'photo_mime_type' => 'image/jpeg',
            'photo_size_bytes' => strlen($bytes),
            'photo_width' => 1,
            'photo_height' => 1,
            'photo_sha256' => hash('sha256', $bytes),
            'photo_upload_status' => 'uploaded',
        ]);
    }
}

class Phase9BTestLocalStorage extends LocalPrivateReportPhotoStorage
{
    public function diskName(): string
    {
        return 'report_photos';
    }
}

class Phase9BTestSupabaseStorage extends SupabasePrivateReportPhotoStorage
{
    public function __construct(private readonly ArrayObject $objects) {}

    public function diskName(): string
    {
        return 'supabase_report_photos';
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
}
