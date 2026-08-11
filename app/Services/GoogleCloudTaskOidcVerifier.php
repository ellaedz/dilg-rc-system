<?php

namespace App\Services;

use App\Contracts\VerifiesCloudTaskOidc;
use App\Contracts\VerifiesGoogleIdTokenSignature;
use App\Exceptions\CloudTaskIdentityException;
use Throwable;

class GoogleCloudTaskOidcVerifier implements VerifiesCloudTaskOidc
{
    public function __construct(
        private readonly CloudTasksConfiguration $configuration,
        private readonly VerifiesGoogleIdTokenSignature $signatureVerifier,
    ) {}

    public function verify(string $token): array
    {
        try {
            $this->configuration->assertOidcReady();
            $claims = false;

            foreach ((array) config('cloud_tasks.allowed_issuers', []) as $issuer) {
                $claims = $this->signatureVerifier->verify(
                    $token,
                    $this->configuration->string('oidc_audience'),
                    (string) $issuer,
                );
                if (is_array($claims)) {
                    break;
                }
            }

            if (! is_array($claims)) {
                throw new CloudTaskIdentityException;
            }

            $this->assertTimeClaims($claims);
            $this->assertExpectedIdentity($claims);

            return $claims;
        } catch (CloudTaskIdentityException $exception) {
            throw $exception;
        } catch (Throwable) {
            throw new CloudTaskIdentityException;
        }
    }

    /** @param array<string, mixed> $claims */
    private function assertTimeClaims(array $claims): void
    {
        $issuedAt = $claims['iat'] ?? null;
        $expiresAt = $claims['exp'] ?? null;
        if (! is_numeric($issuedAt) || ! is_numeric($expiresAt)) {
            throw new CloudTaskIdentityException;
        }

        $now = time();
        $skew = (int) config('cloud_tasks.clock_skew_seconds', 60);
        $maximumAge = (int) config('cloud_tasks.maximum_token_age_seconds', 3600);
        if ((int) $issuedAt > $now + $skew
            || $now - (int) $issuedAt > $maximumAge + $skew
            || (int) $expiresAt < $now - $skew) {
            throw new CloudTaskIdentityException;
        }
    }

    /** @param array<string, mixed> $claims */
    private function assertExpectedIdentity(array $claims): void
    {
        $expectedEmail = $this->configuration->string('oidc_service_account_email');
        if (array_key_exists('email', $claims)) {
            if (! is_string($claims['email'])
                || ! hash_equals($expectedEmail, $claims['email'])
                || ($claims['email_verified'] ?? null) !== true) {
                throw new CloudTaskIdentityException;
            }

            return;
        }

        $expectedSubject = $this->configuration->string('oidc_service_account_subject');
        if ($expectedSubject === ''
            || ! is_string($claims['sub'] ?? null)
            || ! hash_equals($expectedSubject, $claims['sub'])) {
            throw new CloudTaskIdentityException;
        }
    }
}
