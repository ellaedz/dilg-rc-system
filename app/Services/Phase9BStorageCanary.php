<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use RuntimeException;
use Throwable;

class Phase9BStorageCanary
{
    private const EXPIRY_SECONDS = 30;

    public function __construct(
        private readonly SupabasePrivateReportPhotoStorage $storage,
    ) {}

    /**
     * @return array<string, bool|int>
     */
    public function run(): array
    {
        $this->storage->assertReady();
        $bytes = $this->generatedPng();
        $objectKey = $this->storage->generateObjectKey('png');
        $cleanupRequired = false;
        $cleanupVerified = false;
        $failure = null;

        try {
            // A network error can occur after the remote service accepted bytes.
            // From this point onward, always inspect and clean the opaque key.
            $cleanupRequired = true;
            $this->storage->put($objectKey, $bytes);
            if (! $this->storage->exists($objectKey)) {
                throw new RuntimeException('The canary object was not found after upload.');
            }

            $this->verifyStoredBytes($objectKey, $bytes);
            $public = $this->request($this->publicObjectUrl($objectKey));
            if ($public->successful()) {
                throw new RuntimeException('The private canary was accessible through a public URL.');
            }

            $signedUrl = $this->storage->temporaryUrl(
                $objectKey,
                now()->addSeconds(self::EXPIRY_SECONDS)
            );
            $signed = $this->request($signedUrl);
            if (! $signed->successful() || ! hash_equals(hash('sha256', $bytes), hash('sha256', $signed->body()))) {
                throw new RuntimeException('The signed canary download failed integrity verification.');
            }

            $cacheControl = strtolower((string) $signed->header('Cache-Control'));
            if (! str_contains($cacheControl, 'private')
                || ! str_contains($cacheControl, 'no-store')) {
                throw new RuntimeException('The signed canary response did not preserve the private cache policy.');
            }

            Sleep::sleep(self::EXPIRY_SECONDS + 2);
            if ($this->request($signedUrl)->successful()) {
                throw new RuntimeException('The canary signed URL remained usable after expiration.');
            }
        } catch (Throwable $exception) {
            $failure = $exception;
        } finally {
            if ($cleanupRequired) {
                try {
                    $cleanupVerified = ! $this->storage->exists($objectKey)
                        || ($this->storage->delete($objectKey)
                            && ! $this->storage->exists($objectKey));
                } catch (Throwable) {
                    $cleanupVerified = false;
                }
            }
        }

        if (! $cleanupVerified) {
            throw new RuntimeException(
                'The Phase 9B canary cleanup could not be verified. Stop and inspect the private bucket.'
            );
        }
        if ($failure instanceof Throwable) {
            throw new RuntimeException($failure->getMessage(), previous: $failure);
        }

        return [
            'uploaded' => true,
            'stored_integrity_verified' => true,
            'public_access_denied' => true,
            'signed_access_verified' => true,
            'signed_expiry_verified' => true,
            'private_cache_policy_verified' => true,
            'cleanup_verified' => true,
            'expiry_seconds' => self::EXPIRY_SECONDS,
        ];
    }

    private function generatedPng(): string
    {
        if (! function_exists('imagecreatetruecolor')) {
            throw new RuntimeException('GD is required to generate the disposable canary image.');
        }

        $image = imagecreatetruecolor(2, 2);
        if ($image === false) {
            throw new RuntimeException('The disposable canary image could not be generated.');
        }

        $color = imagecolorallocate($image, 30, 64, 175);
        imagefill($image, 0, 0, $color);
        ob_start();
        try {
            if (! imagepng($image, null, 9)) {
                throw new RuntimeException('The disposable canary image could not be encoded.');
            }

            $bytes = ob_get_contents();
        } finally {
            ob_end_clean();
            imagedestroy($image);
        }

        if (! is_string($bytes) || $bytes === '') {
            throw new RuntimeException('The disposable canary image is empty.');
        }

        return $bytes;
    }

    private function verifyStoredBytes(string $objectKey, string $expected): void
    {
        $stream = $this->storage->readStream($objectKey);
        $hash = hash_init('sha256');
        $bytes = 0;
        try {
            while (! feof($stream)) {
                $chunk = fread($stream, 8192);
                if ($chunk === false) {
                    throw new RuntimeException('The canary object could not be read completely.');
                }
                $bytes += strlen($chunk);
                hash_update($hash, $chunk);
            }
        } finally {
            fclose($stream);
        }

        if ($bytes !== strlen($expected)
            || ! hash_equals(hash('sha256', $expected), hash_final($hash))) {
            throw new RuntimeException('The stored canary failed integrity verification.');
        }
    }

    private function request(string $url): Response
    {
        return Http::accept('*/*')
            ->connectTimeout(5)
            ->timeout(15)
            ->withOptions(['allow_redirects' => false])
            ->get($url);
    }

    private function publicObjectUrl(string $objectKey): string
    {
        $endpoint = parse_url((string) config(
            'filesystems.disks.supabase_report_photos.endpoint'
        ));
        $host = (string) ($endpoint['host'] ?? '');
        $projectRef = explode('.', $host, 2)[0] ?? '';
        if (! preg_match('/\A[a-z0-9]{20}\z/D', $projectRef)) {
            throw new RuntimeException('The Supabase project reference is invalid.');
        }

        $encodedKey = implode('/', array_map('rawurlencode', explode('/', $objectKey)));

        return 'https://'.$projectRef.'.supabase.co/storage/v1/object/public/'
            .'civiclear-report-photos/'.$encodedKey;
    }
}
