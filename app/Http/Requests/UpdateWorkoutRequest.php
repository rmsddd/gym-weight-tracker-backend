<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkoutRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'nullable|string|max:255',
            'date' => 'sometimes|required|date',
            'note' => 'nullable|string',
            'duration_minutes' => 'nullable|integer|min:0',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
