<?php

namespace App\Services;

use App\Models\WorkoutExercise;
use App\Models\WorkoutSet;
use Illuminate\Database\Eloquent\Collection;

class WorkoutSetService
{
    public function getAllForWorkoutExercise(WorkoutExercise $workoutExercise): Collection
    {
        return $workoutExercise->sets()->get();
    }

    public function create(WorkoutExercise $workoutExercise, array $data): WorkoutSet
    {
        return $workoutExercise->sets()->create($data);
    }

    public function update(WorkoutSet $workoutSet, array $data): WorkoutSet
    {
        $workoutSet->update($data);
        return $workoutSet->fresh();
    }

    public function delete(WorkoutSet $workoutSet): void
    {
        $workoutSet->delete();
    }
}
