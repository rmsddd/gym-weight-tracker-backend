<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutExercise;

// A workout exercise belongs to whoever owns its workout, so defer to WorkoutPolicy.
class WorkoutExercisePolicy
{
    public function viewAny(User $user, Workout $workout): bool
    {
        return $user->can('view', $workout);
    }

    public function view(User $user, WorkoutExercise $workoutExercise): bool
    {
        return $user->can('view', $workoutExercise->workout);
    }

    public function create(User $user, Workout $workout): bool
    {
        return $user->can('update', $workout);
    }

    public function update(User $user, WorkoutExercise $workoutExercise): bool
    {
        return $user->can('update', $workoutExercise->workout);
    }

    public function delete(User $user, WorkoutExercise $workoutExercise): bool
    {
        return $user->can('update', $workoutExercise->workout);
    }
}
