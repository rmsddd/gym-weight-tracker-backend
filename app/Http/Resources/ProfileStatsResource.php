<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property array{workouts_count: int, total_minutes: int, records_count: int} $resource */
class ProfileStatsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'workouts_count' => $this->resource['workouts_count'],
            'total_minutes' => $this->resource['total_minutes'],
            'records_count' => $this->resource['records_count'],
        ];
    }
}
