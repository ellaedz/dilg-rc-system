<?php

namespace App\Services;

use App\Contracts\ProvidesManagedIdentityToken;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class AzureManagedIdentityTokenProvider implements ProvidesManagedIdentityToken
{
    /** @var array<string, array{token:string,expires_at:int}> */
    private static array $tokens = [];

    public function forResource(string $resource, string $clientId): string
    {
        $this->assertTrustedSelection($resource, $clientId);
        $cacheKey = hash('sha256', $clientId."\0".$resource);
        $cached = self::$tokens[$cacheKey] ?? null;
        if ($cached && $cached['expires_at'] > time() + 60) {
            return $cached['token'];
        }

        $endpoint = trim((string) config('azure.identity.endpoint'));
        $header = trim((string) config('azure.identity.header'));
        $this->assertEndpoint($endpoint, $header);

        try {
            $response = Http::acceptJson()
                ->withHeaders(['X-IDENTITY-HEADER' => $header])
                ->connectTimeout((int) config('azure.identity.connect_timeout_seconds', 2))
                ->timeout((int) config('azure.identity.timeout_seconds', 5))
                ->get($endpoint, [
                    'api-version' => '2019-08-01',
                    'client_id' => $clientId,
                    'resource' => $resource,
                ]);
        } catch (Throwable) {
            throw new RuntimeException('Managed identity token acquisition failed.');
        }

        if (! $response->successful()) {
            throw new RuntimeException('Managed identity token acquisition failed.');
        }

        $payload = $response->json();
        $token = is_array($payload) ? ($payload['access_token'] ?? null) : null;
        $expiresOn = is_array($payload) ? ($payload['expires_on'] ?? null) : null;
        if (! is_string($token)
            || ! preg_match('/\A[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\z/D', $token)
            || ! is_numeric($expiresOn)
            || (int) $expiresOn <= time() + 60) {
            throw new RuntimeException('Managed identity returned an invalid token contract.');
        }

        self::$tokens[$cacheKey] = [
            'token' => $token,
            'expires_at' => (int) $expiresOn,
        ];

        return $token;
    }

    private function assertTrustedSelection(string $resource, string $clientId): void
    {
        $resources = array_values(array_filter(array_map(
            static fn ($value): string => trim((string) $value),
            (array) config('azure.identity.allowed_resources', [])
        )));
        $clientIds = array_values(array_filter(array_map(
            static fn ($value): string => strtolower(trim((string) $value)),
            (array) config('azure.identity.allowed_client_ids', [])
        )));

        if (! in_array($resource, $resources, true)
            || ! preg_match('/\A[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\z/D', strtolower($clientId))
            || ! in_array(strtolower($clientId), $clientIds, true)) {
            throw new RuntimeException('Managed identity token selection is not approved.');
        }
    }

    private function assertEndpoint(string $endpoint, string $header): void
    {
        $parts = parse_url($endpoint);
        $host = strtolower((string) ($parts['host'] ?? ''));
        if (! is_array($parts)
            || strtolower((string) ($parts['scheme'] ?? '')) !== 'http'
            || ! in_array($host, ['127.0.0.1', 'localhost'], true)
            || ! isset($parts['port'])
            || $header === '') {
            throw new RuntimeException('Managed identity endpoint configuration is invalid.');
        }
    }
}
