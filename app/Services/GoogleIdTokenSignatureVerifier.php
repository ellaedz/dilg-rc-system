<?php

namespace App\Services;

use App\Contracts\VerifiesGoogleIdTokenSignature;
use Google\Auth\AccessToken;

class GoogleIdTokenSignatureVerifier implements VerifiesGoogleIdTokenSignature
{
    public function __construct(private readonly AccessToken $accessToken) {}

    public function verify(string $token, string $audience, string $issuer): array|false
    {
        return $this->accessToken->verify($token, [
            'audience' => $audience,
            'issuer' => $issuer,
        ]);
    }
}
