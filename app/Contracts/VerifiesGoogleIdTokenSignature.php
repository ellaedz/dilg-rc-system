<?php

namespace App\Contracts;

interface VerifiesGoogleIdTokenSignature
{
    /** @return array<string, mixed>|false */
    public function verify(string $token, string $audience, string $issuer): array|false;
}
