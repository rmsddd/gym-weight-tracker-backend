<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workout;

class WorkoutPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Workout $workout): bool
    {
        return $workout->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Workout $workout): bool
    {
        return $workout->user_id === $user->id;
    }

    public function finish(User $user, Workout $workout): bool
    {
        return $workout->user_id === $user->id;
    }

    // A completed workout is final and can no longer be started again
    public function resume(User $user, Workout $workout): bool
    {
        return $workout->user_id === $user->id && $workout->completed_at === null;
    }

    public function complete(User $user, Workout $workout): bool
    {
        return $workout->user_id === $user->id;
    }

    public function delete(User $user, Workout $workout): bool
    {
        return $workout->user_id === $user->id;
    }
}
