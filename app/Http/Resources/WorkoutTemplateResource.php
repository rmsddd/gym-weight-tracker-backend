<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkoutTemplateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'exercises' => $this->whenLoaded('exercises', fn () => $this->exercises->map(
                fn ($item) => new ExerciseResource($item->exercise)
            )),
        ];
    }
}
