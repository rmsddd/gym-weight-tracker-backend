<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkoutResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            "id"=>$this->id,
            "name"=>$this->name,
            "date"=>$this->date,
            "note"=>$this->note,
            "duration_minutes"=>$this->duration_minutes,
            "started_at"=>$this->started_at?->toIso8601String(),
            "finished_at"=>$this->finished_at?->toIso8601String(),
            "completed_at"=>$this->completed_at?->toIso8601String(),
            "exercises"=>$this->whenLoaded('workoutExercises', fn () => $this->workoutExercises->map(fn ($we) => [
                'name' => $we->exercise->name,
                'category' => $we->exercise->category?->name,
                'sets_count' => $we->sets->count(),
                'volume' => (float) $we->sets->sum(fn ($set) => $set->weight * $set->reps),
            ])->values()),
        ];
    }
}
