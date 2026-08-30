<?php

namespace App\Services;

use App\Contracts\VerifiesCloudTaskOidc;
use App\Exceptions\CloudTaskIdentityException;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Http;
use Throwable;

class AzureEntraAccessTokenVerifier implements VerifiesCloudTaskOidc
{
    /** @var array<string, array{keys:array<string, mixed>,expires_at:int}> */
    private static array $keySets = [];

    public function verify(string $token): array
    {
        try {
            $tenant = $this->guid('tenant_id');
            $audience = $this->required('task_api_audience');
            $expectedClient = strtolower($this->guid('task_worker_client_id'));
            $expectedPrincipal = strtolower($this->guid('task_worker_principal_id'));
            $requiredRole = $this->required('task_api_role');
            $previousLeeway = JWT::$leeway;
            JWT::$leeway = (int) config('azure.entra.clock_skew_seconds', 60);
            try {
                $decoded = $this->decodeToken($token, $tenant);
            } finally {
                JWT::$leeway = $previousLeeway;
            }
            $claims = json_decode(json_encode($decoded, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);
            if (! is_array($claims)) {
                throw new CloudTaskIdentityException;
            }

            $issuer = $this->issuer($tenant, $claims['ver'] ?? null);
            $actualAudience = $claims['aud'] ?? null;
            $actualClient = strtolower((string) ($claims['azp'] ?? $claims['appid'] ?? ''));
            $actualPrincipal = strtolower((string) ($claims['oid'] ?? ''));
            $roles = $claims['roles'] ?? [];
            if (! is_string($actualAudience)
                || ! hash_equals($audience, $actualAudience)
                || ! is_string($claims['iss'] ?? null)
                || ! hash_equals($issuer, (string) $claims['iss'])
                || ! is_string($claims['tid'] ?? null)
                || ! hash_equals($tenant, strtolower((string) $claims['tid']))
                || ! hash_equals($expectedClient, $actualClient)
                || ! hash_equals($expectedPrincipal, $actualPrincipal)
                || ! is_array($roles)
                || ! in_array($requiredRole, $roles, true)) {
                throw new CloudTaskIdentityException;
            }

            return $claims;
        } catch (CloudTaskIdentityException $exception) {
            throw $exception;
        } catch (Throwable) {
            throw new CloudTaskIdentityException;
        }
    }

    protected function decodeToken(string $token, string $tenant): object
    {
        $version = $this->unsignedTokenVersion($token);

        return JWT::decode($token, $this->keys($tenant, $version));
    }

    /** @return array<string, Key> */
    private function keys(string $tenant, string $version): array
    {
        $cacheKey = "{$tenant}:{$version}";
        $cached = self::$keySets[$cacheKey] ?? null;
        if ($cached && $cached['expires_at'] > time()) {
            return $cached['keys'];
        }

        $keyPath = $version === '1.0' ? 'discovery/keys' : 'discovery/v2.0/keys';
        $url = "https://login.microsoftonline.com/{$tenant}/{$keyPath}";
        $response = Http::acceptJson()->connectTimeout(3)->timeout(8)->get($url);
        if (! $response->successful() || ! is_array($response->json())) {
            throw new CloudTaskIdentityException;
        }
        $keys = JWK::parseKeySet($response->json());
        if ($keys === []) {
            throw new CloudTaskIdentityException;
        }
        self::$keySets[$cacheKey] = [
            'keys' => $keys,
            'expires_at' => time() + 3600,
        ];

        return $keys;
    }

    private function unsignedTokenVersion(string $token): string
    {
        $segments = explode('.', $token);
        if (count($segments) !== 3) {
            throw new CloudTaskIdentityException;
        }

        $claims = json_decode(
            JWT::urlsafeB64Decode($segments[1]),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        if (! is_array($claims)) {
            throw new CloudTaskIdentityException;
        }

        return $this->tokenVersion($claims['ver'] ?? null);
    }

    private function issuer(string $tenant, mixed $version): string
    {
        return match ($this->tokenVersion($version)) {
            '1.0' => "https://sts.windows.net/{$tenant}/",
            '2.0' => "https://login.microsoftonline.com/{$tenant}/v2.0",
        };
    }

    private function tokenVersion(mixed $version): string
    {
        if ($version !== '1.0' && $version !== '2.0') {
            throw new CloudTaskIdentityException;
        }

        return $version;
    }

    private function required(string $key): string
    {
        $value = trim((string) config("azure.entra.{$key}"));
        if ($value === '') {
            throw new CloudTaskIdentityException;
        }

        return $value;
    }

    private function guid(string $key): string
    {
        $value = strtolower($this->required($key));
        if (! preg_match('/\A[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\z/D', $value)) {
            throw new CloudTaskIdentityException;
        }

        return $value;
    }
}
