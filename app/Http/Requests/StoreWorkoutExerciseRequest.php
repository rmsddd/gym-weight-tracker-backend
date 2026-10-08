<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorkoutExerciseRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'exercise_id' => [
                'required',
                Rule::exists('exercises', 'id')->where(
                    fn ($query) => $query->whereNull('user_id')->orWhere('user_id', $this->user()->id)
                ),
            ],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
