<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkoutExercise;
use App\Models\WorkoutSet;

// A set belongs to whoever owns its workout exercise, so defer to WorkoutExercisePolicy.
class WorkoutSetPolicy
{
    public function viewAny(User $user, WorkoutExercise $workoutExercise): bool
    {
        return $user->can('view', $workoutExercise);
    }

    public function view(User $user, WorkoutSet $workoutSet): bool
    {
        return $user->can('view', $workoutSet->workoutExercise);
    }

    public function create(User $user, WorkoutExercise $workoutExercise): bool
    {
        return $user->can('update', $workoutExercise);
    }

    public function update(User $user, WorkoutSet $workoutSet): bool
    {
        return $user->can('update', $workoutSet->workoutExercise);
    }

    public function delete(User $user, WorkoutSet $workoutSet): bool
    {
        return $user->can('update', $workoutSet->workoutExercise);
    }
}
