<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ForgotPasswordRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => 'required|email',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Enter your email.',
            'email.email' => 'Enter a valid email address, like name@example.com.',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
