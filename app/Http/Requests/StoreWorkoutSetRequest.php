<?php

namespace App\Http\Requests;

use App\Enums\ExerciseUnit;
use Illuminate\Foundation\Http\FormRequest;

class StoreWorkoutSetRequest extends FormRequest
{
    public function rules(): array
    {
        // Timed exercises (plank, cardio) log how long the set lasted instead of weight and reps
        if ($this->route('workoutExercise')?->exercise?->unit === ExerciseUnit::Minutes) {
            return [
                'duration_minutes' => 'required|numeric|gt:0|max:1440',
            ];
        }

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
