<?php

namespace App\Services;

use App\Enums\ExerciseUnit;
use App\Models\WorkoutSet;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PersonalRecordService
{
    /**
     * The single best set per exercise, across all of the user's workouts: heaviest weight, then
     * most reps, or the longest one for exercises logged in minutes.
     */
    public function getForUser(int $userId): Collection
    {
        return WorkoutSet::query()
            ->whereIn('id', $this->bestSetIds($userId))
            ->with(['workoutExercise.exercise.category', 'workoutExercise.workout'])
            ->get()
            ->sortBy(fn (WorkoutSet $set) => $set->workoutExercise->exercise->name)
            ->values();
    }

    /** How many exercises the user has a record for, without loading the records themselves. */
    public function countForUser(int $userId): int
    {
        return $this->bestSetIds($userId)->count();
    }

    /**
     * The database ranks each exercise's sets and keeps the top one, so only the winners are
     * loaded instead of the user's whole set history. Ties go to the earliest logged set.
     */
    private function bestSetIds(int $userId): Collection
    {
        $ranked = DB::table('workout_sets')
            ->join('workout_exercises', 'workout_exercises.id', '=', 'workout_sets.workout_exercise_id')
            ->join('workouts', 'workouts.id', '=', 'workout_exercises.workout_id')
            ->join('exercises', 'exercises.id', '=', 'workout_exercises.exercise_id')
            ->where('workouts.user_id', $userId)
            // A deleted exercise keeps its history but no longer counts as a record
            ->whereNull('exercises.deleted_at')
            ->selectRaw(
                'workout_sets.id, ROW_NUMBER() OVER ('
                .'PARTITION BY workout_exercises.exercise_id '
                // Duration only decides for timed exercises; everywhere else it is NULL for every set
                .'ORDER BY CASE WHEN exercises.unit = ? THEN workout_sets.duration_minutes END DESC, '
                .'workout_sets.weight DESC, workout_sets.reps DESC, workout_sets.id ASC'
                .') AS position',
                [ExerciseUnit::Minutes->value]
            );

        return DB::query()->fromSub($ranked, 'ranked')->where('position', 1)->pluck('id');
    }
}
