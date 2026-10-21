<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            // An email or a username
            'email' => 'required|string|max:255',
            'password' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Enter your email or username.',
            'password.required' => 'Enter your password.',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
