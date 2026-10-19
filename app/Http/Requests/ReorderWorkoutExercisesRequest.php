<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ReorderWorkoutExercisesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'ids' => 'required|array',
            'ids.*' => 'integer|distinct',
        ];
    }

    /** The new order must list every exercise of the workout exactly once, and nothing else. */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $current = $this->route('workout')->workoutExercises()->pluck('id')->sort()->values()->all();
                $sent = collect($this->input('ids', []))->map(fn ($id) => (int) $id)->sort()->values()->all();

                if ($current !== $sent) {
                    $validator->errors()->add('ids', 'The order must list every exercise of the workout once.');
                }
            },
        ];
    }

    // Checked here rather than in the controller, so another user's workout is refused before its exercises are compared
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('workout'));
    }
}
