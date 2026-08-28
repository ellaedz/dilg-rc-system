<?php

namespace App\Data;

final readonly class AzureQueueMessage
{
    public function __construct(
        public string $messageId,
        public string $popReceipt,
        public int $dequeueCount,
        public string $body,
    ) {}
}
