<?php

namespace App\Data;

final readonly class AiProcessingResult
{
    public function __construct(
        public string $outcome,
        public ?string $errorCode,
        public string $message,
        public ?string $requestId = null,
    ) {}

    public function completed(): bool
    {
        return $this->outcome === 'completed';
    }
}
