<?php

namespace App\Http\Requests;

use App\Enums\ExerciseUnit;
use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkoutSetRequest extends FormRequest
{
    public function rules(): array
    {
        if ($this->route('workoutExercise')?->exercise?->unit === ExerciseUnit::Minutes) {
            return [
                'duration_minutes' => 'sometimes|required|numeric|gt:0|max:1440',
            ];
        }

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
