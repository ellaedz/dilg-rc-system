<?php

namespace App\Services;

use App\Contracts\PrivateReportPhotoStorage;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class LocalPrivateReportPhotoStorage implements PrivateReportPhotoStorage
{
    private const OBJECT_KEY_PATTERN = '/\Areports\/[A-Za-z0-9_-]{2}\/[A-Za-z0-9_-]{43}\.(?:jpg|png)\z/D';

    public function diskName(): string
    {
        return (string) config('report_photos.disk', 'report_photos');
    }

    public function generateObjectKey(string $extension): string
    {
        if (! in_array($extension, ['jpg', 'png'], true)) {
            throw new RuntimeException('Unsupported sanitized photograph extension.');
        }

        $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');

        return 'reports/'.substr($token, 0, 2).'/'.$token.'.'.$extension;
    }

    public function put(string $objectKey, string $contents): void
    {
        $this->assertValidObjectKey($objectKey);

        if (! Storage::disk($this->diskName())->put($objectKey, $contents)) {
            throw new RuntimeException('Private photograph storage failed.');
        }
    }

    public function readStream(string $objectKey)
    {
        $this->assertValidObjectKey($objectKey);
        $stream = Storage::disk($this->diskName())->readStream($objectKey);

        if (! is_resource($stream)) {
            throw new RuntimeException('Private photograph is unavailable.');
        }

        return $stream;
    }

    public function exists(string $objectKey): bool
    {
        $this->assertValidObjectKey($objectKey);

        return Storage::disk($this->diskName())->exists($objectKey);
    }

    public function delete(string $objectKey): bool
    {
        $this->assertValidObjectKey($objectKey);

        return Storage::disk($this->diskName())->delete($objectKey);
    }

    private function assertValidObjectKey(string $objectKey): void
    {
        if (! preg_match(self::OBJECT_KEY_PATTERN, $objectKey)) {
            throw new RuntimeException('Invalid private photograph object key.');
        }
    }
}
