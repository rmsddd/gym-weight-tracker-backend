<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property array{category: \App\Models\Category, sessions: int, level: int} $resource */
class MuscleStatResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'category' => new CategoryResource($this->resource['category']),
            'sessions' => $this->resource['sessions'],
            'level' => $this->resource['level'],
        ];
    }
}
