<?php

namespace App\Services;

use App\Models\User;
use App\Rules\NotBlockedPassword;
use App\Rules\NotCurrentPassword;
use App\Rules\NotRecentlyUsedPassword;
use Illuminate\Validation\Rules\Password;

class CiviclearPasswordPolicy
{
    /**
     * @return array<int, mixed>
     */
    public function rules(User $user, bool $confirmed = true): array
    {
        $password = Password::min((int) config('security.password.minimum_length', 16))
            ->max((int) config('security.password.maximum_length', 128));

        if ((bool) config('security.password.check_compromised', true)) {
            $password->uncompromised();
        }

        $rules = [
            'required',
            'string',
            $password,
            new NotBlockedPassword(
                config('security.password.blocked_sha256', []),
            ),
            new NotCurrentPassword($user),
            new NotRecentlyUsedPassword(
                $user,
                (int) config('security.password.history_limit', 5),
            ),
        ];

        if ($confirmed) {
            $rules[] = 'confirmed';
        }

        return $rules;
    }
}
