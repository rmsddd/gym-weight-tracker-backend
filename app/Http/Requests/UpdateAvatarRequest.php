<?php

namespace App\Http\Requests;

use App\Enums\Avatar;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAvatarRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'avatar' => ['required', Rule::enum(Avatar::class)],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
