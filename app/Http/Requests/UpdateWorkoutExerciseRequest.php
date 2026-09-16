<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkoutExerciseRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'exercise_id' => 'sometimes|required|exists:exercises,id',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
