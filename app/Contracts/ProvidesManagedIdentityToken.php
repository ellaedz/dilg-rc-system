<?php

namespace App\Contracts;

interface ProvidesManagedIdentityToken
{
    public function forResource(string $resource, string $clientId): string;
}
