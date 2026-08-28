<?php

namespace App\Services;

use RuntimeException;

class AzureQueueConfiguration
{
    public function assertSenderReady(): void
    {
        $this->assertCommon();
        if ($this->string('laravel_client_id') === '') {
            throw new RuntimeException('Azure Queue sender identity is incomplete.');
        }
    }

    public function assertWorkerReady(): void
    {
        $this->assertCommon();
        foreach (['worker_client_id', 'quarantine_queue', 'task_api_resource', 'task_handler_url'] as $key) {
            if ($this->string($key) === '') {
                throw new RuntimeException('Azure Queue worker configuration is incomplete.');
            }
        }

        if (! $this->isHttpsUrl($this->string('task_handler_url'))) {
            throw new RuntimeException('Azure Queue worker endpoint is invalid.');
        }
    }

    public function string(string $key): string
    {
        return trim((string) config("azure.queue.{$key}", ''));
    }

    public function endpoint(string $queue): string
    {
        $this->assertQueueName($queue);

        return sprintf(
            'https://%s.queue.core.windows.net/%s',
            $this->string('account'),
            $queue,
        );
    }

    private function assertCommon(): void
    {
        if (! preg_match('/\A[a-z0-9]{3,24}\z/D', $this->string('account'))) {
            throw new RuntimeException('Azure Queue account configuration is invalid.');
        }
        $this->assertQueueName($this->string('primary_queue'));
        if ($this->string('storage_resource') !== 'https://storage.azure.com/') {
            throw new RuntimeException('Azure Queue token resource is invalid.');
        }
    }

    private function assertQueueName(string $queue): void
    {
        if (! preg_match('/\A[a-z0-9](?:[a-z0-9-]{1,61}[a-z0-9])?\z/D', $queue)
            || str_contains($queue, '--')) {
            throw new RuntimeException('Azure Queue name is invalid.');
        }
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
