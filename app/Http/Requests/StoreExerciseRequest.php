<?php

namespace App\Http\Requests;

use App\Enums\ExerciseUnit;
use App\Models\Exercise;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExerciseRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'unit' => ['sometimes', Rule::enum(ExerciseUnit::class)],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
