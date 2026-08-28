<?php

namespace App\Services;

use RuntimeException;

class CloudTasksConfiguration
{
    public function __construct(private readonly AzureQueueConfiguration $azure) {}

    public function assertDispatchReady(): void
    {
        if ((string) config('cloud_tasks.dispatcher') === 'azure_queue') {
            $this->azure->assertSenderReady();
            $this->assertAzureTimeouts();

            return;
        }

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

    private function assertAzureTimeouts(): void
    {
        $aiTimeout = (int) config('ai_inference.timeout_seconds', 30);
        $serviceTimeout = (int) config('ai_inference.service_timeout_seconds', 40);
        $handlerTimeout = (int) config('azure.queue.handler_timeout_seconds', 50);
        $visibility = (int) config('azure.queue.receive_visibility_seconds', 120);
        $lease = (int) config('ai_inference.processing_lease_seconds', 90);

        if ($aiTimeout > $serviceTimeout
            || $aiTimeout + 10 >= $handlerTimeout
            || $handlerTimeout >= $lease
            || $handlerTimeout >= $visibility) {
            throw new RuntimeException('Azure AI task timeout hierarchy is unsafe.');
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
