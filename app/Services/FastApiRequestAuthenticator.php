<?php

namespace App\Services;

use App\Contracts\ProvidesManagedIdentityToken;
use App\Exceptions\AiProcessingException;
use Illuminate\Http\Client\PendingRequest;
use Throwable;

class FastApiRequestAuthenticator
{
    public function __construct(
        private readonly ProvidesManagedIdentityToken $tokens,
    ) {}

    public function authenticate(PendingRequest $request, string $endpoint): PendingRequest
    {
        $mode = strtolower(trim((string) config('ai_inference.auth_mode', 'none')));
        if ($mode === 'none') {
            return $request;
        }
        if ($mode !== 'azure_entra' || ! $this->isApprovedEndpoint($endpoint)) {
            throw $this->unavailable();
        }

        $resource = trim((string) config('ai_inference.entra_resource'));
        $clientId = trim((string) config('azure.identity.laravel_client_id'));
        try {
            return $request->withToken($this->tokens->forResource($resource, $clientId));
        } catch (Throwable) {
            throw $this->unavailable();
        }
    }

    private function isApprovedEndpoint(string $endpoint): bool
    {
        $base = rtrim(trim((string) config('ai_inference.url')), '/');
        $endpointParts = parse_url($endpoint);
        $baseParts = parse_url($base);

        return is_array($endpointParts)
            && is_array($baseParts)
            && strtolower((string) ($endpointParts['scheme'] ?? '')) === 'https'
            && strtolower((string) ($baseParts['scheme'] ?? '')) === 'https'
            && hash_equals(
                strtolower((string) ($baseParts['host'] ?? '')),
                strtolower((string) ($endpointParts['host'] ?? '')),
            )
            && (int) ($baseParts['port'] ?? 443) === (int) ($endpointParts['port'] ?? 443)
            && ! isset($endpointParts['user'])
            && ! isset($endpointParts['pass'])
            && trim((string) config('ai_inference.entra_resource')) !== '';
    }

    private function unavailable(): AiProcessingException
    {
        return new AiProcessingException(
            'FASTAPI_AUTH_UNAVAILABLE',
            'AI processing authentication is temporarily unavailable.'
        );
    }
}
