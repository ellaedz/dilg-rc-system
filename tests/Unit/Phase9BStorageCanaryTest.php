<?php

namespace Tests\Unit;

use App\Services\Phase9BStorageCanary;
use App\Services\SupabasePrivateReportPhotoStorage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Mockery;
use Tests\TestCase;

class Phase9BStorageCanaryTest extends TestCase
{
    public function test_canary_proves_private_signed_expiring_access_and_cleanup(): void
    {
        Sleep::fake(syncWithCarbon: true);
        $key = 'reports/AA/'.str_repeat('A', 43).'.png';
        $stored = null;
        $storage = Mockery::mock(SupabasePrivateReportPhotoStorage::class);
        $storage->shouldReceive('assertReady')->once();
        $storage->shouldReceive('generateObjectKey')->once()->with('png')->andReturn($key);
        $storage->shouldReceive('put')->once()->withArgs(
            function ($actualKey, $bytes) use ($key, &$stored): bool {
                $stored = $bytes;

                return $actualKey === $key && is_string($bytes) && $bytes !== '';
            }
        );
        $storage->shouldReceive('exists')->times(3)->with($key)->andReturn(true, true, false);
        $storage->shouldReceive('readStream')->once()->with($key)->andReturnUsing(
            function () use (&$stored) {
                $stream = fopen('php://temp', 'w+b');
                fwrite($stream, $stored);
                rewind($stream);

                return $stream;
            }
        );
        $storage->shouldReceive('temporaryUrl')->once()->withArgs(
            fn ($actualKey, $expiration) => $actualKey === $key
                && $expiration->getTimestamp() > now()->getTimestamp()
        )->andReturn('https://example.test/private-canary?signature=test');
        $storage->shouldReceive('delete')->once()->with($key)->andReturnTrue();
        $this->app->instance(SupabasePrivateReportPhotoStorage::class, $storage);

        config()->set(
            'filesystems.disks.supabase_report_photos.endpoint',
            'https://abcdefghijklmnopqrst.storage.supabase.co/storage/v1/s3'
        );
        Http::fake(function ($request) use (&$stored) {
            if (str_contains($request->url(), '/object/public/')) {
                return Http::response(['message' => 'not found'], 404);
            }

            static $signedRequests = 0;
            $signedRequests++;

            return $signedRequests === 1
                ? Http::response($stored, 200, [
                    'Cache-Control' => 'private, no-store',
                    'Content-Type' => 'image/png',
                ])
                : Http::response('expired', 403);
        });

        $result = app(Phase9BStorageCanary::class)->run();

        $this->assertTrue($result['uploaded']);
        $this->assertTrue($result['public_access_denied']);
        $this->assertTrue($result['signed_access_verified']);
        $this->assertTrue($result['signed_expiry_verified']);
        $this->assertTrue($result['private_cache_policy_verified']);
        $this->assertTrue($result['cleanup_verified']);
        Sleep::assertSleptTimes(1);
    }

    public function test_canary_attempts_cleanup_when_upload_throws_after_the_remote_key_exists(): void
    {
        $key = 'reports/AA/'.str_repeat('A', 43).'.png';
        $storage = Mockery::mock(SupabasePrivateReportPhotoStorage::class);
        $storage->shouldReceive('assertReady')->once();
        $storage->shouldReceive('generateObjectKey')->once()->with('png')->andReturn($key);
        $storage->shouldReceive('put')->once()->with($key, Mockery::type('string'))
            ->andThrow(new \RuntimeException('simulated transport interruption'));
        $storage->shouldReceive('exists')->twice()->with($key)->andReturn(true, false);
        $storage->shouldReceive('delete')->once()->with($key)->andReturnTrue();
        $this->app->instance(SupabasePrivateReportPhotoStorage::class, $storage);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('simulated transport interruption');

        app(Phase9BStorageCanary::class)->run();
    }
}
