<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['nullable', 'string', 'required_without:otp'],
            'otp' => ['nullable', 'digits:4', 'required_without:token'],
            'email' => ['required', 'email', 'exists:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'token.required_without' => 'Reset token or OTP is required.',
            'otp.required_without' => 'OTP or reset token is required.',
            'otp.digits' => 'OTP must be 4 digits.',
        ];
    }
}
