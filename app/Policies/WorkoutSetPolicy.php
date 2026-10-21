<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkoutExercise;
use App\Models\WorkoutSet;
use App\Policies\Concerns\ChecksPermissions;

// A set belongs to whoever owns its workout. Ownership is checked here directly rather than through
// WorkoutExercisePolicy, which would also demand the workout-exercises.* permissions.
class WorkoutSetPolicy
{
    use ChecksPermissions;

    public function viewAny(User $user, WorkoutExercise $workoutExercise): bool
    {
        return $this->permitted($user, 'workout-sets.view') && $workoutExercise->workout->user_id === $user->id;
    }

    public function view(User $user, WorkoutSet $workoutSet): bool
    {
        return $this->permitted($user, 'workout-sets.view') && $this->owns($user, $workoutSet);
    }

    public function create(User $user, WorkoutExercise $workoutExercise): bool
    {
        return $this->permitted($user, 'workout-sets.create') && $workoutExercise->workout->user_id === $user->id;
    }

    public function update(User $user, WorkoutSet $workoutSet): bool
    {
        return $this->permitted($user, 'workout-sets.update') && $this->owns($user, $workoutSet);
    }

    public function delete(User $user, WorkoutSet $workoutSet): bool
    {
        return $this->permitted($user, 'workout-sets.delete') && $this->owns($user, $workoutSet);
    }

    private function owns(User $user, WorkoutSet $workoutSet): bool
    {
        return $workoutSet->workoutExercise->workout->user_id === $user->id;
    }
}
