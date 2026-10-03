<?php

namespace App\Http\Requests;

use App\Services\CiviclearPasswordPolicy;
use Illuminate\Foundation\Http\FormRequest;

class ChangeOwnPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(CiviclearPasswordPolicy $policy): array
    {
        return [
            'current_password' => ['required', 'string', 'current_password:web'],
            'password' => $policy->rules($this->user()),
        ];
    }
}
