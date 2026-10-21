<?php

namespace App\Policies;

use App\Models\Exercise;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class ExercisePolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return $this->permitted($user, 'exercises.view');
    }

    // Shared catalog exercises have no owner; custom ones are private to their creator.
    public function view(User $user, Exercise $exercise): bool
    {
        return $this->permitted($user, 'exercises.view')
            && ($exercise->user_id === null || $exercise->user_id === $user->id);
    }

    public function create(User $user): bool
    {
        return $this->permitted($user, 'exercises.create');
    }

    public function update(User $user, Exercise $exercise): bool
    {
        return $this->permitted($user, 'exercises.update') && $exercise->user_id === $user->id;
    }

    public function delete(User $user, Exercise $exercise): bool
    {
        return $this->permitted($user, 'exercises.delete') && $exercise->user_id === $user->id;
    }
}
