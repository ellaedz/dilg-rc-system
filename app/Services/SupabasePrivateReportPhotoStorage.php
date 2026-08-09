<?php

namespace App\Services;

use App\Contracts\PrivateReportPhotoStorage;
use App\Contracts\TemporaryPrivateReportPhotoUrlProvider;
use DateTimeInterface;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class SupabasePrivateReportPhotoStorage implements PrivateReportPhotoStorage, TemporaryPrivateReportPhotoUrlProvider
{
    private const OBJECT_KEY_PATTERN = '/\Areports\/[A-Za-z0-9_-]{2}\/[A-Za-z0-9_-]{43}\.(?:jpg|png)\z/D';

    private const BUCKET = 'civiclear-report-photos';

    public function diskName(): string
    {
        return (string) config('report_photos.supabase_disk', 'supabase_report_photos');
    }

    public function assertReady(): void
    {
        $this->assertConfigured();
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
        $this->assertConfigured();
        $this->assertValidObjectKey($objectKey);
        $disk = Storage::disk($this->diskName());

        if ($disk->exists($objectKey)) {
            throw new RuntimeException('Private photograph object-key collision.');
        }

        $extension = str_ends_with($objectKey, '.png') ? 'png' : 'jpg';
        $mimeType = $extension === 'png' ? 'image/png' : 'image/jpeg';
        $stored = $disk->put($objectKey, $contents, [
            'ContentType' => $mimeType,
            'CacheControl' => 'private, no-store',
            'ContentDisposition' => 'inline; filename="report-evidence.'.$extension.'"',
            'before_upload' => static function ($command): void {
                unset($command['ACL']);
            },
        ]);

        if (! $stored || ! $this->matchesStoredContent($objectKey, $contents)) {
            try {
                $disk->delete($objectKey);
            } catch (\Throwable) {
                // Leave a traceable orphan for the guarded reconciliation command.
            }

            throw new RuntimeException('Private photograph storage verification failed.');
        }
    }

    public function readStream(string $objectKey)
    {
        $this->assertConfigured();
        $this->assertValidObjectKey($objectKey);
        $stream = Storage::disk($this->diskName())->readStream($objectKey);

        if (! is_resource($stream)) {
            throw new RuntimeException('Private photograph is unavailable.');
        }

        return $stream;
    }

    public function exists(string $objectKey): bool
    {
        $this->assertConfigured();
        $this->assertValidObjectKey($objectKey);

        return Storage::disk($this->diskName())->exists($objectKey);
    }

    public function delete(string $objectKey): bool
    {
        $this->assertConfigured();
        $this->assertValidObjectKey($objectKey);

        return Storage::disk($this->diskName())->delete($objectKey);
    }

    public function temporaryUrl(string $objectKey, DateTimeInterface $expiration): string
    {
        $this->assertConfigured();
        $this->assertValidObjectKey($objectKey);

        $maximumTtl = max(30, min(
            900,
            (int) config('report_photos.signed_url_ttl_seconds', 120)
        ));
        $remaining = $expiration->getTimestamp() - now()->getTimestamp();
        if ($remaining < 1 || $remaining > $maximumTtl) {
            throw new RuntimeException('Invalid private photograph signed URL lifetime.');
        }

        $extension = str_ends_with($objectKey, '.png') ? 'png' : 'jpg';
        $mimeType = $extension === 'png' ? 'image/png' : 'image/jpeg';
        $url = Storage::disk($this->diskName())->temporaryUrl($objectKey, $expiration, [
            'ResponseContentType' => $mimeType,
            'ResponseContentDisposition' => 'inline; filename="report-evidence.'.$extension.'"',
        ]);

        $endpoint = parse_url((string) config('filesystems.disks.'.$this->diskName().'.endpoint'));
        $signed = parse_url($url);
        if (! is_array($endpoint)
            || ! is_array($signed)
            || ($signed['scheme'] ?? null) !== 'https'
            || ! hash_equals((string) ($endpoint['host'] ?? ''), (string) ($signed['host'] ?? ''))
            || ! isset($signed['query'])
            || stripos((string) $signed['query'], 'X-Amz-Signature=') === false) {
            throw new RuntimeException('Private photograph signed URL validation failed.');
        }

        return $url;
    }

    private function matchesStoredContent(string $objectKey, string $expected): bool
    {
        $stream = Storage::disk($this->diskName())->readStream($objectKey);
        if (! is_resource($stream)) {
            return false;
        }

        $hash = hash_init('sha256');
        $bytes = 0;
        try {
            while (! feof($stream)) {
                $chunk = fread($stream, 8192);
                if ($chunk === false) {
                    return false;
                }
                $bytes += strlen($chunk);
                hash_update($hash, $chunk);
            }
        } finally {
            fclose($stream);
        }

        return $bytes === strlen($expected)
            && hash_equals(hash('sha256', $expected), hash_final($hash));
    }

    private function assertConfigured(): void
    {
        $configuration = config('filesystems.disks.'.$this->diskName());
        if (! is_array($configuration)
            || ($configuration['driver'] ?? null) !== 's3'
            || ($configuration['bucket'] ?? null) !== self::BUCKET
            || ($configuration['use_path_style_endpoint'] ?? null) !== true
            || empty($configuration['key'])
            || empty($configuration['secret'])
            || empty($configuration['region'])) {
            throw new RuntimeException('Supabase private photograph storage is not configured safely.');
        }

        $endpoint = parse_url((string) ($configuration['endpoint'] ?? ''));
        if (! is_array($endpoint)
            || ($endpoint['scheme'] ?? null) !== 'https'
            || ! preg_match('/\A[a-z0-9]{20}\.storage\.supabase\.co\z/D', (string) ($endpoint['host'] ?? ''))
            || ($endpoint['path'] ?? null) !== '/storage/v1/s3'
            || isset($endpoint['port'])
            || isset($endpoint['user'])
            || isset($endpoint['pass'])
            || isset($endpoint['query'])
            || isset($endpoint['fragment'])) {
            throw new RuntimeException('Supabase private photograph endpoint is not configured safely.');
        }
    }

    private function assertValidObjectKey(string $objectKey): void
    {
        if (! preg_match(self::OBJECT_KEY_PATTERN, $objectKey)) {
            throw new RuntimeException('Invalid private photograph object key.');
        }
    }
}
