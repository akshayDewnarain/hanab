<?php

namespace Application\API\Auth\Requests;

use Domain\Auth\Support\PasswordPolicy;
use Illuminate\Foundation\Http\FormRequest;

class ApiResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed', 'max:'.PasswordPolicy::MAX, PasswordPolicy::rule()],
            'password_confirmation' => ['required', 'string'],
        ];
    }
}
