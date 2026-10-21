<?php

namespace App\Policies;

use App\Models\Friendship;
use App\Models\User;

class FriendshipPolicy
{
    // Only the person a request was sent to can accept it
    public function accept(User $user, Friendship $friendship): bool
    {
        return $friendship->isPending() && $friendship->addressee_id === $user->id;
    }

    // Either side of a pending request: the addressee declines it, the requester cancels it
    public function delete(User $user, Friendship $friendship): bool
    {
        return $friendship->isPending()
            && in_array($user->id, [$friendship->requester_id, $friendship->addressee_id], true);
    }
}
