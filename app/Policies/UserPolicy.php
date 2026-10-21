<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class UserPolicy
{
    use ChecksPermissions;

    public function updateName(User $user, User $model): bool
    {
        return $this->permitted($user, 'profile.update-name') && $user->id === $model->id;
    }

    public function updateAvatar(User $user, User $model): bool
    {
        return $this->permitted($user, 'profile.update-avatar') && $user->id === $model->id;
    }

    // A username is permanent: it can only be chosen while the account has none. No permission:
    // an account without one is held on that page, so denying it would trap the user there
    public function setUsername(User $user, User $model): bool
    {
        return $user->id === $model->id && $model->username === null;
    }

    // Friends see each other's workouts; nobody else does
    public function viewWorkouts(User $user, User $model): bool
    {
        return $this->permitted($user, 'friends.view') && $user->isFriendsWith($model);
    }

    public function searchUsers(User $user): bool
    {
        return $this->permitted($user, 'friends.search');
    }

    public function viewFriends(User $user): bool
    {
        return $this->permitted($user, 'friends.view');
    }

    public function viewFriendRequests(User $user): bool
    {
        return $this->permitted($user, 'friends.requests');
    }

    public function sendFriendRequest(User $user): bool
    {
        return $this->permitted($user, 'friends.send-request');
    }

    public function removeFriend(User $user): bool
    {
        return $this->permitted($user, 'friends.remove');
    }
}
