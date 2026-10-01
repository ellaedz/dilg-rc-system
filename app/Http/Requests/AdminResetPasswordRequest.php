<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Services\CiviclearPasswordPolicy;
use Illuminate\Foundation\Http\FormRequest;

class AdminResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        $target = $this->route('account');

        return $this->user()?->role === 'dilg_admin'
            && $target instanceof User
            && $target->role === 'barangay_staff';
    }

    public function rules(CiviclearPasswordPolicy $policy): array
    {
        /** @var User $target */
        $target = $this->route('account');

        return [
            'password' => $policy->rules($target),
        ];
    }
}
