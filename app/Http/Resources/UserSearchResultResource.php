<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A search result and where the viewer stands with them: null (can add), "outgoing" (request sent),
 * "incoming" (they asked, can accept) or "friends". request_id is the row to accept or cancel.
 */
class UserSearchResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $friendship = $this->friendship;

        return [
            ...(new PublicUserResource($this->resource))->toArray($request),
            'friendship' => match (true) {
                $friendship === null => null,
                ! $friendship->isPending() => 'friends',
                $friendship->requester_id === $request->user()->id => 'outgoing',
                default => 'incoming',
            },
            'request_id' => $friendship?->id,
        ];
    }
}
