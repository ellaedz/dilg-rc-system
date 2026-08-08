<?php

namespace App\Exceptions;

use RuntimeException;

class AiProcessingException extends RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        public readonly string $safeMessage,
    ) {
        parent::__construct($safeMessage);
    }
}
