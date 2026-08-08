<?php

namespace App\Exceptions;

use RuntimeException;

class PhotoValidationException extends RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        public readonly string $safeMessage,
    ) {
        parent::__construct($safeMessage);
    }
}
