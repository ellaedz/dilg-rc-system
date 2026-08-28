<?php

namespace App\Exceptions;

use RuntimeException;

class AzureQueueOperationException extends RuntimeException
{
    public function __construct(public readonly bool $definitive)
    {
        parent::__construct('Azure Queue operation did not complete safely.');
    }
}
