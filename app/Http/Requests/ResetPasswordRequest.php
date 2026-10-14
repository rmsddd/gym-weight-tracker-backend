<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ];
    }

    public function messages(): array
    {
        return [
            'token.required' => 'This reset link is incomplete. Open the link from the email again.',
            'email.required' => 'Enter your email.',
            'email.email' => 'Enter a valid email address, like name@example.com.',
            'password.required' => 'Enter a new password.',
            'password.min' => 'The password must be at least 8 characters.',
            'password.confirmed' => 'The passwords do not match.',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
