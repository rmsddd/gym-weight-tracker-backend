<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property array{month: string, workouts_count: int, total_minutes: int} $resource */
class MonthlyStatResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'month' => $this->resource['month'],
            'workouts_count' => $this->resource['workouts_count'],
            'total_minutes' => $this->resource['total_minutes'],
        ];
    }
}
