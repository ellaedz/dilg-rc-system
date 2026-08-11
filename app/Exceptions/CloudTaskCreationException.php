<?php

namespace App\Exceptions;

use RuntimeException;

class CloudTaskCreationException extends RuntimeException
{
    public const DEFINITIVE = 'definitive';

    public const UNCERTAIN = 'uncertain';

    public function __construct(public readonly string $outcome)
    {
        parent::__construct('Cloud Task creation did not complete safely.');
    }

    public static function definitive(): self
    {
        return new self(self::DEFINITIVE);
    }

    public static function uncertain(): self
    {
        return new self(self::UNCERTAIN);
    }
}
