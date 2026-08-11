<?php

namespace App\Services;

use RuntimeException;

class CloudTasksConfiguration
{
    public function assertDispatchReady(): void
    {
        foreach ([
            'project',
            'location',
            'queue',
            'handler_url',
            'oidc_service_account_email',
            'oidc_audience',
        ] as $key) {
            if ($this->string($key) === '') {
                throw new RuntimeException('Cloud Tasks configuration is incomplete.');
            }
        }

        if (! preg_match('/\A[a-z][a-z0-9-]{4,62}\z/D', $this->string('project'))
            || ! preg_match('/\A[a-z0-9-]{1,40}\z/D', $this->string('location'))
            || ! preg_match('/\A[a-zA-Z0-9_-]{1,100}\z/D', $this->string('queue'))
            || ! filter_var($this->string('oidc_service_account_email'), FILTER_VALIDATE_EMAIL)
            || ! $this->isHttpsUrl($this->string('handler_url'))
            || ! $this->isHttpsUrl($this->string('oidc_audience'))) {
            throw new RuntimeException('Cloud Tasks configuration is invalid.');
        }

        $aiTimeout = (int) config('ai_inference.timeout_seconds', 20);
        $dispatchDeadline = (int) config('cloud_tasks.dispatch_deadline_seconds', 45);
        $lease = (int) config('ai_inference.processing_lease_seconds', 60);

        if ($dispatchDeadline < 15
            || $dispatchDeadline > 1800
            || $aiTimeout >= $dispatchDeadline
            || $dispatchDeadline >= $lease
            || (int) config('cloud_tasks.create_timeout_seconds', 10) >= $dispatchDeadline) {
            throw new RuntimeException('Cloud Tasks timeout hierarchy is unsafe.');
        }
    }

    public function assertOidcReady(): void
    {
        $audience = $this->string('oidc_audience');
        $email = $this->string('oidc_service_account_email');
        $subject = $this->string('oidc_service_account_subject');

        if (! $this->isHttpsUrl($audience)
            || ($email === '' && $subject === '')
            || ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL))) {
            throw new RuntimeException('Cloud Tasks OIDC configuration is incomplete.');
        }
    }

    public function string(string $key): string
    {
        return trim((string) config("cloud_tasks.{$key}", ''));
    }

    private function isHttpsUrl(string $value): bool
    {
        $parts = parse_url($value);

        return is_array($parts)
            && strtolower((string) ($parts['scheme'] ?? '')) === 'https'
            && is_string($parts['host'] ?? null)
            && $parts['host'] !== ''
            && ! isset($parts['user'])
            && ! isset($parts['pass']);
    }
}
