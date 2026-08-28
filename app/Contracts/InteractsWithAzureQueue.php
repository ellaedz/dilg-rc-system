<?php

namespace App\Contracts;

use App\Data\AzureQueueMessage;

interface InteractsWithAzureQueue
{
    /** @param array<string, mixed> $payload */
    public function send(string $queue, array $payload): void;

    public function receive(string $queue): ?AzureQueueMessage;

    public function updateVisibility(
        string $queue,
        AzureQueueMessage $message,
        int $visibilityTimeoutSeconds,
    ): void;

    public function delete(string $queue, AzureQueueMessage $message): void;
}
