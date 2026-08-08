<?php

namespace Tests\Unit;

use App\Services\LocalPrivateReportPhotoStorage;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class LocalPrivateReportPhotoStorageTest extends TestCase
{
    public function test_opaque_keys_store_and_stream_only_from_private_disk(): void
    {
        Storage::fake('report_photos');
        Storage::fake('public');
        $storage = app(LocalPrivateReportPhotoStorage::class);
        $key = $storage->generateObjectKey('jpg');

        $storage->put($key, 'sanitized');

        $this->assertMatchesRegularExpression(
            '/\Areports\/[A-Za-z0-9_-]{2}\/[A-Za-z0-9_-]{43}\.jpg\z/',
            $key
        );
        Storage::disk('report_photos')->assertExists($key);
        $this->assertSame([], Storage::disk('public')->allFiles());
        $stream = $storage->readStream($key);
        $this->assertSame('sanitized', stream_get_contents($stream));
        fclose($stream);
    }

    public function test_traversal_and_unapproved_extensions_are_rejected(): void
    {
        Storage::fake('report_photos');
        $storage = app(LocalPrivateReportPhotoStorage::class);

        try {
            $storage->exists('../private.jpg');
            $this->fail('Traversal key was accepted.');
        } catch (RuntimeException) {
            $this->assertTrue(true);
        }

        $this->expectException(RuntimeException::class);
        $storage->generateObjectKey('webp');
    }
}
