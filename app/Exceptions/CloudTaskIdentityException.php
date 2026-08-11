<?php

namespace App\Exceptions;

use RuntimeException;

class CloudTaskIdentityException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Cloud Task identity could not be verified.');
    }
}
