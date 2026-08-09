<?php

namespace Tests\Unit;

use App\Contracts\PrivateReportPhotoStorage;
use App\Services\LocalPrivateReportPhotoStorage;
use App\Services\ReportPhotoStorageResolver;
use App\Services\SupabasePrivateReportPhotoStorage;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class Phase9BPrivatePhotoStorageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('report_photos');
        Storage::fake('supabase_report_photos');
        $this->configureSafeSupabaseDisk();
    }

    public function test_trusted_selector_defaults_to_local_and_rejects_unknown_values(): void
    {
        config()->set('report_photos.driver', 'local');
        $this->app->forgetInstance(PrivateReportPhotoStorage::class);
        $this->assertInstanceOf(
            LocalPrivateReportPhotoStorage::class,
            app(PrivateReportPhotoStorage::class)
        );

        config()->set('report_photos.driver', 'invalid-citizen-value');
        $this->app->forgetInstance(PrivateReportPhotoStorage::class);
        $this->expectException(RuntimeException::class);
        app(PrivateReportPhotoStorage::class);
    }

    public function test_trusted_selector_can_choose_supabase_without_changing_local_identity(): void
    {
        config()->set('report_photos.driver', 'supabase');
        $this->app->forgetInstance(PrivateReportPhotoStorage::class);

        $selected = app(PrivateReportPhotoStorage::class);

        $this->assertInstanceOf(SupabasePrivateReportPhotoStorage::class, $selected);
        $this->assertSame('supabase_report_photos', $selected->diskName());
        $this->assertSame('report_photos', app(LocalPrivateReportPhotoStorage::class)->diskName());
    }

    public function test_supabase_selector_fails_closed_when_server_configuration_is_incomplete(): void
    {
        config()->set('report_photos.driver', 'supabase');
        config()->set('filesystems.disks.supabase_report_photos.secret', null);
        $this->app->forgetInstance(PrivateReportPhotoStorage::class);

        $this->expectException(RuntimeException::class);
        app(PrivateReportPhotoStorage::class);
    }

    public function test_resolver_uses_persisted_disk_identity_and_fails_closed(): void
    {
        $resolver = app(ReportPhotoStorageResolver::class);

        $this->assertInstanceOf(
            LocalPrivateReportPhotoStorage::class,
            $resolver->forDisk('report_photos')
        );
        $this->assertInstanceOf(
            SupabasePrivateReportPhotoStorage::class,
            $resolver->forDisk('supabase_report_photos')
        );

        $this->expectException(RuntimeException::class);
        $resolver->forDisk('../public');
    }

    public function test_supabase_adapter_checks_collision_and_verifies_complete_content(): void
    {
        $storage = app(SupabasePrivateReportPhotoStorage::class);
        $key = $storage->generateObjectKey('jpg');
        $contents = 'sanitized test photograph bytes';

        $storage->put($key, $contents);

        Storage::disk('supabase_report_photos')->assertExists($key);
        $this->assertSame($contents, Storage::disk('supabase_report_photos')->get($key));

        $this->expectException(RuntimeException::class);
        $storage->put($key, 'replacement must not overwrite');
    }

    public function test_supabase_adapter_rejects_unsafe_endpoint_before_storage_access(): void
    {
        config()->set(
            'filesystems.disks.supabase_report_photos.endpoint',
            'http://example.test/storage/v1/s3'
        );

        $this->expectException(RuntimeException::class);
        app(SupabasePrivateReportPhotoStorage::class)->exists(
            'reports/AA/'.str_repeat('A', 43).'.jpg'
        );
    }

    public function test_supabase_upload_sets_private_cache_and_safe_content_metadata(): void
    {
        $contents = 'verified sanitized image bytes';
        $key = 'reports/AA/'.str_repeat('A', 43).'.jpg';
        $capturedOptions = null;
        $stream = fopen('php://temp', 'w+b');
        fwrite($stream, $contents);
        rewind($stream);

        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('exists')->once()->with($key)->andReturnFalse();
        $disk->shouldReceive('put')->once()->withArgs(
            function ($actualKey, $actualContents, $options) use (
                $key,
                $contents,
                &$capturedOptions
            ): bool {
                $capturedOptions = $options;

                return $actualKey === $key
                && $actualContents === $contents
                && ($options['CacheControl'] ?? null) === 'private, no-store'
                && ($options['ContentType'] ?? null) === 'image/jpeg'
                && ($options['ContentDisposition'] ?? null)
                    === 'inline; filename="report-evidence.jpg"';
            }
        )->andReturnTrue();
        $disk->shouldReceive('readStream')->once()->with($key)->andReturn($stream);
        Storage::shouldReceive('disk')
            ->twice()
            ->with('supabase_report_photos')
            ->andReturn($disk);

        app(SupabasePrivateReportPhotoStorage::class)->put($key, $contents);

        $this->assertIsCallable($capturedOptions['before_upload'] ?? null);
        $command = new \ArrayObject(['ACL' => 'private']);
        ($capturedOptions['before_upload'])($command);
        $this->assertFalse($command->offsetExists('ACL'));
    }

    public function test_supabase_signed_url_must_use_exact_https_storage_host(): void
    {
        $key = 'reports/AA/'.str_repeat('A', 43).'.png';
        $expected = sprintf(
            'https://%s.storage.supabase.co/storage/v1/s3/%s/%s?%s=%s&%s=%s',
            'abcdefghijklmnopqrst',
            'civiclear-report-photos',
            $key,
            'X-Amz-Algorithm',
            'AWS4-HMAC-SHA256',
            'X-Amz-Signature',
            'test'
        );
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('temporaryUrl')->once()->andReturn($expected);
        Storage::shouldReceive('disk')
            ->once()
            ->with('supabase_report_photos')
            ->andReturn($disk);

        $actual = app(SupabasePrivateReportPhotoStorage::class)
            ->temporaryUrl($key, now()->addSeconds(60));

        $this->assertSame($expected, $actual);
    }

    private function configureSafeSupabaseDisk(): void
    {
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
}
