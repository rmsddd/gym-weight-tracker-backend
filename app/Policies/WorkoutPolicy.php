<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workout;
use App\Policies\Concerns\ChecksPermissions;

class WorkoutPolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return $this->permitted($user, 'workouts.view');
    }

    public function view(User $user, Workout $workout): bool
    {
        return $this->permitted($user, 'workouts.view') && $workout->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $this->permitted($user, 'workouts.create');
    }

    public function update(User $user, Workout $workout): bool
    {
        return $this->permitted($user, 'workouts.update') && $workout->user_id === $user->id;
    }

    public function finish(User $user, Workout $workout): bool
    {
        return $this->permitted($user, 'workouts.finish') && $workout->user_id === $user->id;
    }

    // A completed workout is final and can no longer be started again
    public function resume(User $user, Workout $workout): bool
    {
        return $this->permitted($user, 'workouts.resume')
            && $workout->user_id === $user->id && $workout->completed_at === null;
    }

    public function complete(User $user, Workout $workout): bool
    {
        return $this->permitted($user, 'workouts.complete') && $workout->user_id === $user->id;
    }

    public function delete(User $user, Workout $workout): bool
    {
        return $this->permitted($user, 'workouts.delete') && $workout->user_id === $user->id;
    }

    // Stats, records and muscle stats are derived from the caller's own workouts only
    public function viewStats(User $user): bool
    {
        return $this->permitted($user, 'stats.view');
    }
}
