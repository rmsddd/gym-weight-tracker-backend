<?php

namespace App\Services;

use App\Models\WorkoutSet;
use Illuminate\Support\Collection;

class PersonalRecordService
{
    /**
     * The single best set (heaviest weight, then most reps) per exercise, across all of the user's workouts.
     */
    public function getForUser(int $userId): Collection
    {
        return WorkoutSet::query()
            ->whereHas('workoutExercise.workout', fn ($query) => $query->where('user_id', $userId))
            ->with(['workoutExercise.exercise.category', 'workoutExercise.workout'])
            ->get()
            ->groupBy(fn (WorkoutSet $set) => $set->workoutExercise->exercise_id)
            ->map(fn (Collection $sets) => $sets->sortByDesc(fn (WorkoutSet $set) => [(float) $set->weight, $set->reps])->first())
            ->sortBy(fn (WorkoutSet $set) => $set->workoutExercise->exercise->name)
            ->values();
    }
}
