<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ForgotPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Intentionally no `exists:users,email` rule — AuthService::sendPasswordResetLink
        // always returns the same generic "if an account exists" message regardless of
        // whether the address is registered. Validating existence here would let an
        // attacker enumerate registered accounts via the distinct 422 response.
        return [
            'email' => ['required', 'email'],
        ];
    }
}
