<?php

namespace App\Services;

use App\Models\Exercise;
use App\Models\Workout;
use App\Models\WorkoutSet;
use App\Models\WorkoutTemplate;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
class WorkoutService
{
   /**
    * @param array{from?: string, to?: string, exercise_id?: int, category_id?: int, finished?: bool, include_exercises?: bool} $filters
    */
   public function getAll(int $userId, array $filters = []): Collection{
       return Workout::where('user_id', $userId)
           ->when($filters['from'] ?? null, fn ($q, $from) => $q->whereDate('date', '>=', $from))
           ->when($filters['to'] ?? null, fn ($q, $to) => $q->whereDate('date', '<=', $to))
           ->when($filters['finished'] ?? false, fn ($q) => $q->whereNotNull('finished_at'))
           ->when($filters['exercise_id'] ?? null, fn ($q, $id) => $q->whereHas(
               'workoutExercises', fn ($we) => $we->where('exercise_id', $id)
           ))
           ->when($filters['category_id'] ?? null, fn ($q, $id) => $q->whereHas(
               'workoutExercises.exercise', fn ($ex) => $ex->where('category_id', $id)
           ))
           ->when($filters['include_exercises'] ?? false, fn ($q) => $q->with('workoutExercises.exercise.category', 'workoutExercises.sets'))
           ->orderByDesc('date')
           ->orderByDesc('id')
           ->get();
   }
   public function findById(int $id): Workout{
       return Workout::findOrFail($id);
   }
   public function create(array $data, ?WorkoutTemplate $template = null): Workout{
       unset($data['template_id']);
       $data['started_at'] = now();
       return DB::transaction(function () use ($data, $template) {
           $workout = Workout::create($data);
           if ($template) {
               app(WorkoutTemplateService::class)->applyToWorkout($template, $workout);
           }
           return $workout;
       });
   }
    /** Undoes an accidental stop: the timer keeps counting from the original start. */
    public function resume(Workout $workout): Workout
    {
        if ($workout->finished_at !== null && $workout->completed_at === null) {
            $workout->update(['finished_at' => null, 'duration_minutes' => null]);
        }
        return $workout->fresh();
    }
    public function finish(Workout $workout): Workout
    {
        if ($workout->finished_at === null) {
            $finishedAt = now();
            $startedAt = $workout->started_at ?? $finishedAt;
            $workout->update([
                'finished_at' => $finishedAt,
                'duration_minutes' => (int) round($startedAt->diffInSeconds($finishedAt, true) / 60),
            ]);
        }
        return $workout->fresh();
    }
    /** Ends the workout for good (stopping it first if it is still running); it cannot be resumed afterwards. */
    public function complete(Workout $workout): Workout
    {
        $workout = $this->finish($workout);
        if ($workout->completed_at === null) {
            $workout->update(['completed_at' => now()]);
        }
        return $workout->fresh();
    }
    public function update(Workout $workout, array $data): Workout
    {
        $workout->update($data);
        return $workout->fresh();
    }

    public function delete(Workout $workout): void
    {
        DB::transaction(function () use ($workout) {
            // One query for all the sets, instead of one per exercise
            WorkoutSet::whereIn('workout_exercise_id', $workout->workoutExercises()->select('id'))->delete();
            $workout->workoutExercises()->delete();
            $workout->delete();
        });
    }
}
