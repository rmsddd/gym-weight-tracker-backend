<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorkoutRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'nullable|string|max:255',
            'date' => 'required|date',
            'note' => 'nullable|string',
            'template_id' => [
                'nullable',
                Rule::exists('workout_templates', 'id')->where('user_id', $this->user()->id),
            ],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
