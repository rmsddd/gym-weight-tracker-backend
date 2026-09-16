<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkoutSetRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'weight' => 'required|numeric|min:0',
            'reps' => 'required|integer|min:0',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
