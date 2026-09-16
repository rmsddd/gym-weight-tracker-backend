<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkoutSetRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'weight' => 'sometimes|required|numeric|min:0',
            'reps' => 'sometimes|required|integer|min:0',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
