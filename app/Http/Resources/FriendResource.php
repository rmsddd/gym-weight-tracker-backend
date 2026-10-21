<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** A friend in the list: online while they have a workout that is not finished for good. */
class FriendResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $live = $this->liveWorkout;

        return [
            ...(new PublicUserResource($this->resource))->toArray($request),
            'online' => $live !== null,
            'live_workout' => $live === null ? null : [
                'id' => $live->id,
                'name' => $live->name,
                'started_at' => $live->started_at?->toIso8601String(),
                'resumed_at' => $live->resumed_at?->toIso8601String(),
                'finished_at' => $live->finished_at?->toIso8601String(),
            ],
        ];
    }
}
