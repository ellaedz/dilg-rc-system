<?php

namespace Tests\Feature;

use App\Contracts\PrivateReportPhotoStorage;
use App\Contracts\ResolvesPrivateReportPhotoStorage;
use App\Contracts\TemporaryPrivateReportPhotoUrlProvider;
use App\Models\User;
use App\Models\ViolationReport;
use DateTimeInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class Phase9BSignedStorage implements PrivateReportPhotoStorage, TemporaryPrivateReportPhotoUrlProvider
{
    public function diskName(): string
    {
        return 'phase9b_signed';
    }

    public function generateObjectKey(string $extension): string
    {
        throw new RuntimeException('not used');
    }

    public function put(string $objectKey, string $contents): void
    {
        throw new RuntimeException('not used');
    }

    public function readStream(string $objectKey)
    {
        throw new RuntimeException('not used');
    }

    public function exists(string $objectKey): bool
    {
        return true;
    }

    public function delete(string $objectKey): bool
    {
        throw new RuntimeException('not used');
    }

    public function temporaryUrl(string $objectKey, DateTimeInterface $expiration): string
    {
        return sprintf(
            'https://%s.storage.supabase.co/storage/v1/s3/%s/%s?%s=%s',
            str_repeat('a', 20),
            'civiclear-report-photos',
            $objectKey,
            'X-Amz-Signature',
            'phase9b-test-signature'
        );
    }
}

class Phase9BFixedStorageResolver implements ResolvesPrivateReportPhotoStorage
{
    public function __construct(private readonly PrivateReportPhotoStorage $storage) {}

    public function forDisk(string $diskName): PrivateReportPhotoStorage
    {
        if ($diskName !== $this->storage->diskName()) {
            throw new RuntimeException('unknown test disk');
        }

        return $this->storage;
    }
}

class Phase9BPrivateStorageAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('report_photos');
        Storage::fake('supabase_report_photos');
        config()->set('filesystems.disks.supabase_report_photos', [
            'driver' => 's3',
            'key' => str_repeat('A', 20),
            'secret' => str_repeat('S', 40),
            'region' => 'ap-southeast-1',
            'bucket' => 'civiclear-report-photos',
            'endpoint' => 'https://abcdefghijklmnopqrst.storage.supabase.co/storage/v1/s3',
            'use_path_style_endpoint' => true,
            'visibility' => 'private',
            'throw' => true,
            'report' => false,
        ]);
    }

    public function test_streaming_reads_each_report_from_its_persisted_disk(): void
    {
        $admin = User::factory()->create(['role' => 'dilg_admin']);
        $local = $this->report('RCV-2026-9101', 'report_photos');
        $remote = $this->report('RCV-2026-9102', 'supabase_report_photos');
        Storage::disk('report_photos')->put($local->photo_object_key, 'local-bytes');
        Storage::disk('supabase_report_photos')->put($remote->photo_object_key, 'remote-bytes');

        $this->actingAs($admin)
            ->get(route('violation-reports.photo', $local))
            ->assertOk()
            ->assertStreamedContent('local-bytes');
        $this->actingAs($admin)
            ->get(route('violation-reports.photo', $remote))
            ->assertOk()
            ->assertStreamedContent('remote-bytes');
    }

    public function test_signed_access_is_separate_authorized_short_lived_redirect(): void
    {
        $storage = new Phase9BSignedStorage;
        $this->app->instance(
            ResolvesPrivateReportPhotoStorage::class,
            new Phase9BFixedStorageResolver($storage)
        );
        $report = $this->report('RCV-2026-9103', $storage->diskName());
        $admin = User::factory()->create(['role' => 'dilg_admin']);
        $wrongStaff = User::factory()->create([
            'role' => 'barangay_staff',
            'assigned_barangay' => 'Bubukal',
        ]);

        $this->get(route('violation-reports.photo.signed', $report))
            ->assertRedirect(route('login'));
        $this->actingAs($wrongStaff)
            ->get(route('violation-reports.photo.signed', $report))
            ->assertForbidden();
        $response = $this->actingAs($admin)
            ->get(route('violation-reports.photo.signed', $report));

        $response->assertRedirect();
        $response->assertHeader('cache-control', 'max-age=0, no-store, private');
        $response->assertHeader('referrer-policy', 'no-referrer');
        $this->assertStringContainsString(
            'X-Amz-Signature=',
            (string) $response->headers->get('location')
        );
    }

    private function report(string $number, string $disk): ViolationReport
    {
        $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');

        return ViolationReport::create([
            'report_id' => $number,
            'report_number' => $number,
            'submitted_by' => 'Anonymous Citizen',
            'description' => 'Phase 9B isolated private photograph test.',
            'selected_violation_type' => 'Unclassified',
            'status' => 'Submitted',
            'report_status' => 'Submitted',
            'verification_status' => 'Pending',
            'date_submitted' => '2026-08-09',
            'photo_object_key' => 'reports/'.substr($token, 0, 2).'/'.$token.'.jpg',
            'photo_storage_disk' => $disk,
            'photo_mime_type' => 'image/jpeg',
            'photo_size_bytes' => 11,
            'photo_width' => 1,
            'photo_height' => 1,
            'photo_sha256' => hash('sha256', 'local-bytes'),
            'photo_upload_status' => 'uploaded',
            'manually_assigned_barangay' => 'Pagsawitan',
        ]);
    }
}
