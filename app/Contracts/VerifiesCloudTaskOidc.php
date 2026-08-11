<?php

namespace App\Contracts;

interface VerifiesCloudTaskOidc
{
    /** @return array<string, mixed> */
    public function verify(string $token): array;
}
