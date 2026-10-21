<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUsernameRequest extends FormRequest
{
    // Stored lowercase, so Remus and remus are the same username
    protected function prepareForValidation(): void
    {
        $this->merge(['username' => mb_strtolower(trim((string) $this->input('username')))]);
    }

    public function rules(): array
    {
        return [
            'username' => User::usernameRules(),
        ];
    }

    public function messages(): array
    {
        return User::usernameMessages();
    }

    public function authorize(): bool
    {
        return true;
    }
}
