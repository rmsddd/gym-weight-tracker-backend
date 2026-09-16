<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkoutExerciseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'workout_id' => $this->workout_id,
            'exercise' => new ExerciseResource($this->whenLoaded('exercise')),
        ];
    }
}
