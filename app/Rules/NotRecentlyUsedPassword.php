<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Hash;

class NotRecentlyUsedPassword implements ValidationRule
{
    public function __construct(
        private readonly User $user,
        private readonly int $historyLimit,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $this->historyLimit < 1) {
            return;
        }

        $wasRecentlyUsed = $this->user->passwordHistories()
            ->latest('id')
            ->limit($this->historyLimit)
            ->pluck('password_hash')
            ->contains(static fn (string $hash): bool => Hash::check($value, $hash));

        if ($wasRecentlyUsed) {
            $fail('The new password was used recently. Choose a different password.');
        }
    }
}
