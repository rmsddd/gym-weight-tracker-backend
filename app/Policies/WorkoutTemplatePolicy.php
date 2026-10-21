<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutTemplate;
use App\Policies\Concerns\ChecksPermissions;

class WorkoutTemplatePolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return $this->permitted($user, 'templates.view');
    }

    public function view(User $user, WorkoutTemplate $template): bool
    {
        return $this->permitted($user, 'templates.view') && $template->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $this->permitted($user, 'templates.create');
    }

    // Saving one of your own workouts as a template: only templates.create, not workouts.view
    public function createFromWorkout(User $user, Workout $workout): bool
    {
        return $this->permitted($user, 'templates.create') && $workout->user_id === $user->id;
    }

    public function delete(User $user, WorkoutTemplate $template): bool
    {
        return $this->permitted($user, 'templates.delete') && $template->user_id === $user->id;
    }
}
