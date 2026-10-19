<?php

namespace App\Services;

use App\Models\Workout;
use App\Models\WorkoutExercise;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class WorkoutExerciseService
{
    public function getAllForWorkout(Workout $workout): Collection
    {
        return $workout->workoutExercises()->with(['exercise.category', 'sets'])->get();
    }

    public function create(Workout $workout, array $data): WorkoutExercise
    {
        // A new exercise goes to the end of the workout
        $data['position'] = ($workout->workoutExercises()->max('position') ?? -1) + 1;
        return $workout->workoutExercises()->create($data);
    }

    /** @param int[] $ids every workout exercise id of the workout, in the new order */
    public function reorder(Workout $workout, array $ids): Collection
    {
        DB::transaction(function () use ($workout, $ids) {
            foreach (array_values($ids) as $position => $id) {
                $workout->workoutExercises()->whereKey($id)->update(['position' => $position]);
            }
        });
        return $this->getAllForWorkout($workout);
    }

    public function update(WorkoutExercise $workoutExercise, array $data): WorkoutExercise
    {
        $workoutExercise->update($data);
        return $workoutExercise->fresh();
    }

    public function delete(WorkoutExercise $workoutExercise): void
    {
        // Sets go with their exercise; they used to be left behind as orphan rows
        DB::transaction(function () use ($workoutExercise) {
            $workoutExercise->sets()->delete();
            $workoutExercise->delete();
        });
    }
}
