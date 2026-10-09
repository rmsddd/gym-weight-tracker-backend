<?php

namespace App\Services;

use App\Models\Workout;
use App\Models\WorkoutExercise;
use Illuminate\Support\Facades\DB;

class PreviousSetsService
{
    /**
     * For each exercise in the given workout, the sets from the user's most recent earlier workout
     * that logged that exercise, keyed by exercise id.
     *
     * @return array<int, array{date: string|null, sets: array<int, array{weight: float, reps: int}>}>
     */
    public function forWorkout(Workout $workout): array
    {
        $exerciseIds = $workout->workoutExercises()->pluck('exercise_id');
        if ($exerciseIds->isEmpty()) {
            return [];
        }

        // The database picks the latest earlier entry per exercise, so only those are loaded
        // (with their sets) instead of the whole history of every exercise in this workout.
        $ranked = DB::table('workout_exercises')
            ->join('workouts', 'workouts.id', '=', 'workout_exercises.workout_id')
            ->whereIn('workout_exercises.exercise_id', $exerciseIds)
            ->where('workouts.user_id', $workout->user_id)
            ->where('workouts.id', '!=', $workout->id)
            ->where(fn ($query) => $query
                ->whereDate('workouts.date', '<', $workout->date)
                ->orWhere(fn ($query) => $query
                    ->whereDate('workouts.date', $workout->date)
                    ->where('workouts.id', '<', $workout->id)))
            ->whereExists(fn ($query) => $query
                ->selectRaw('1')
                ->from('workout_sets')
                ->whereColumn('workout_sets.workout_exercise_id', 'workout_exercises.id'))
            ->selectRaw(
                'workout_exercises.id, ROW_NUMBER() OVER ('
                .'PARTITION BY workout_exercises.exercise_id '
                .'ORDER BY workouts.date DESC, workouts.id DESC, workout_exercises.id ASC'
                .') AS position'
            );

        $latestIds = DB::query()->fromSub($ranked, 'ranked')->where('position', 1)->pluck('id');

        $result = [];
        foreach (WorkoutExercise::whereIn('id', $latestIds)->with(['workout', 'sets'])->get() as $workoutExercise) {
            $result[$workoutExercise->exercise_id] = [
                'date' => $workoutExercise->workout->date?->toDateString(),
                'sets' => $workoutExercise->sets->sortBy('id')->map(fn ($set) => [
                    'weight' => (float) $set->weight,
                    'reps' => $set->reps,
                ])->values()->all(),
            ];
        }

        return $result;
    }
}
