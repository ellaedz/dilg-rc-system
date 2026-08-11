<?php

namespace App\Data;

final readonly class CloudTaskCreationResult
{
    public const CREATED = 'created';

    public const ALREADY_EXISTS = 'already_exists';

    public function __construct(public string $outcome) {}

    public static function created(): self
    {
        return new self(self::CREATED);
    }

    public static function alreadyExists(): self
    {
        return new self(self::ALREADY_EXISTS);
    }
}
