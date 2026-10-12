<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property \App\Models\WorkoutSet $resource */
class PersonalRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $workoutExercise = $this->workoutExercise;

        return [
            'exercise' => new ExerciseResource($workoutExercise->exercise),
            'weight' => $this->weight === null ? null : (float) $this->weight,
            'reps' => $this->reps,
            'duration_minutes' => $this->duration_minutes === null ? null : (float) $this->duration_minutes,
            'workout_id' => $workoutExercise->workout_id,
            'date' => $workoutExercise->workout->date?->toDateString(),
        ];
    }
}
