<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExerciseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'unit' => $this->unit->value,
            // Created by the user rather than part of the shared catalog, so they may delete it
            'is_custom' => $this->user_id !== null,
            'is_deleted' => $this->trashed(),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'secondary_categories' => CategoryResource::collection($this->whenLoaded('secondaryCategories')),
        ];
    }
}
