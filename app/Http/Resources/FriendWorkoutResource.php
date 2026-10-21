<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** A workout as a friend sees it: every exercise and set, but not the owner's note. */
class FriendWorkoutResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'date' => $this->date,
            'duration_minutes' => $this->duration_minutes,
            'started_at' => $this->started_at?->toIso8601String(),
            'resumed_at' => $this->resumed_at?->toIso8601String(),
            'finished_at' => $this->finished_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'exercises' => $this->whenLoaded('workoutExercises', fn () => $this->workoutExercises->map(fn ($we) => [
                'id' => $we->id,
                'name' => $we->exercise->name,
                'unit' => $we->exercise->unit->value,
                'category' => $we->exercise->category?->name,
                'sets_count' => $we->sets->count(),
                'volume' => (float) $we->sets->sum(fn ($set) => $set->weight * $set->reps),
                'minutes' => (float) $we->sets->sum('duration_minutes'),
                'sets' => WorkoutSetResource::collection($we->sets),
            ])->values()),
        ];
    }
}
