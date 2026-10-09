<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorkoutTemplateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'exercise_ids' => 'required|array|min:1',
            'exercise_ids.*' => [
                'integer',
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
