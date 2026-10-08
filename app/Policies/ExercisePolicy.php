<?php

namespace App\Policies;

use App\Models\Exercise;
use App\Models\User;

class ExercisePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    // Shared catalog exercises have no owner; custom ones are private to their creator.
    public function view(User $user, Exercise $exercise): bool
    {
        return $exercise->user_id === null || $exercise->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Exercise $exercise): bool
    {
        return $exercise->user_id === $user->id;
    }

    public function delete(User $user, Exercise $exercise): bool
    {
        return $exercise->user_id === $user->id;
    }
}
