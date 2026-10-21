<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutExercise;
use App\Policies\Concerns\ChecksPermissions;

// A workout exercise belongs to whoever owns its workout. Ownership is checked here directly rather
// than through WorkoutPolicy, which would also demand the workouts.* permissions.
class WorkoutExercisePolicy
{
    use ChecksPermissions;

    public function viewAny(User $user, Workout $workout): bool
    {
        return $this->permitted($user, 'workout-exercises.view') && $workout->user_id === $user->id;
    }

    public function view(User $user, WorkoutExercise $workoutExercise): bool
    {
        return $this->permitted($user, 'workout-exercises.view') && $workoutExercise->workout->user_id === $user->id;
    }

    public function create(User $user, Workout $workout): bool
    {
        return $this->permitted($user, 'workout-exercises.create') && $workout->user_id === $user->id;
    }

    public function update(User $user, WorkoutExercise $workoutExercise): bool
    {
        return $this->permitted($user, 'workout-exercises.update') && $workoutExercise->workout->user_id === $user->id;
    }

    public function delete(User $user, WorkoutExercise $workoutExercise): bool
    {
        return $this->permitted($user, 'workout-exercises.delete') && $workoutExercise->workout->user_id === $user->id;
    }
}
