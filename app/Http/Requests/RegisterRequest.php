<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                // The domain must exist and accept mail, which catches typos like gmial.com.
                // Skipped in tests: it needs a real DNS lookup.
                Rule::email()->rfcCompliant()->when(! app()->runningUnitTests(), fn ($rule) => $rule->validateMxRecord()),
                'unique:users,email',
            ],
            'password' => 'required|string|min:8|confirmed',
        ];
    }

    public function messages(): array
    {
        return [
            'email.email' => 'This email address does not exist. Check it for typos.',
            'email.unique' => 'An account already uses this email.',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
