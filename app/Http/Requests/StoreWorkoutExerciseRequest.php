<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkoutExerciseRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'exercise_id' => 'required|exists:exercises,id',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
