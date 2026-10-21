<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** A pending request, with the person on the other side of it from the viewer. */
class FriendRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $other = $this->requester_id === $request->user()->id ? $this->addressee : $this->requester;

        return [
            'id' => $this->id,
            'user' => new PublicUserResource($other),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
