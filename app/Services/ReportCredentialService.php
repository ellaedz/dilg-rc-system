<?php

namespace App\Services;

use App\Models\ViolationReport;
use RuntimeException;

class ReportCredentialService
{
    private const TOKEN_DOMAIN = 'civiclear:tracking-token:v1:';

    private const LOOKUP_DOMAIN = 'civiclear:tracking-lookup:v1:';

    private const IDEMPOTENCY_DOMAIN = 'civiclear:idempotency:v1:';

    public function issue(): array
    {
        $nonce = bin2hex(random_bytes(32));
        $token = $this->deriveTrackingToken($nonce);

        return [
            'token_derivation_nonce' => $nonce,
            'raw_tracking_token' => $token,
            'tracking_token_hash' => $this->hashTrackingToken($token),
        ];
    }

    public function replayToken(ViolationReport $report): string
    {
        if (! is_string($report->token_derivation_nonce)
            || ! preg_match('/^[a-f0-9]{64}$/', $report->token_derivation_nonce)) {
            throw new RuntimeException('This report does not have replayable tracking credentials.');
        }

        return $this->deriveTrackingToken($report->token_derivation_nonce);
    }

    public function hashTrackingToken(string $rawToken): string
    {
        return hash_hmac(
            'sha256',
            self::LOOKUP_DOMAIN.$rawToken,
            $this->key('tracking_token_lookup_key')
        );
    }

    public function hashIdempotencyKey(string $rawKey): string
    {
        return hash_hmac(
            'sha256',
            self::IDEMPOTENCY_DOMAIN.$rawKey,
            $this->key('idempotency_hmac_key')
        );
    }

    public function generateFallbackIdempotencyKey(): string
    {
        return $this->base64UrlEncode(random_bytes(32));
    }

    private function deriveTrackingToken(string $nonce): string
    {
        $bytes = hash_hmac(
            'sha256',
            self::TOKEN_DOMAIN.$nonce,
            $this->key('tracking_token_derivation_key'),
            true
        );

        return $this->base64UrlEncode($bytes);
    }

    private function key(string $name): string
    {
        $key = config('report_security.'.$name);

        if (! is_string($key) || strlen($key) < 32) {
            throw new RuntimeException('Report security configuration is unavailable.');
        }

        return $key;
    }

    private function base64UrlEncode(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }
}
