<?php

namespace App\Services;

use App\Models\Workout;
use App\Models\WorkoutExercise;
use Illuminate\Database\Eloquent\Collection;

class WorkoutExerciseService
{
    public function getAllForWorkout(Workout $workout): Collection
    {
        return $workout->workoutExercises()->with(['exercise.category', 'sets'])->get();
    }

    public function create(Workout $workout, array $data): WorkoutExercise
    {
        return $workout->workoutExercises()->create($data);
    }

    public function update(WorkoutExercise $workoutExercise, array $data): WorkoutExercise
    {
        $workoutExercise->update($data);
        return $workoutExercise->fresh();
    }

    public function delete(WorkoutExercise $workoutExercise): void
    {
        $workoutExercise->delete();
    }
}
