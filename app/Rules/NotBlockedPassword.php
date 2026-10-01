<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NotBlockedPassword implements ValidationRule
{
    /**
     * @param  array<int, string>  $blockedHashes
     */
    public function __construct(
        private readonly array $blockedHashes,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        $candidateHash = hash('sha256', mb_strtolower($value, 'UTF-8'));

        if (in_array($candidateHash, $this->blockedHashes, true)) {
            $fail('The password is too common or specific to CIVICLEAR. Choose a different passphrase.');
        }
    }
}
