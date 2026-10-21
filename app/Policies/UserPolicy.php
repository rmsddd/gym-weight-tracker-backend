<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function update(User $user, User $model): bool
    {
        return $user->id === $model->id;
    }

    // A username is permanent: it can only be chosen while the account has none
    public function setUsername(User $user, User $model): bool
    {
        return $user->id === $model->id && $model->username === null;
    }

    // Friends see each other's workouts; nobody else does
    public function viewWorkouts(User $user, User $model): bool
    {
        return $user->isFriendsWith($model);
    }
}
