<?php

namespace App\Services;

use App\Models\Exercise;
use App\Models\Workout;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
class WorkoutService
{
   public function getAll(int $userId): Collection{
       return Workout::where('user_id', $userId)->get();
   }
   public function findById(int $id): Workout{
       return Workout::findOrFail($id);
   }
   public function create(array $data): Workout{
       $data['started_at'] = now();
       return Workout::create($data);
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
    public function update(Workout $workout, array $data): Workout
    {
        $workout->update($data);
        return $workout->fresh();
    }

    public function delete(Workout $workout): void
    {
        DB::transaction(function () use ($workout) {
            foreach ($workout->workoutExercises as $workoutExercise) {
                $workoutExercise->sets()->delete();
            }
            $workout->workoutExercises()->delete();
            $workout->delete();
        });
    }
}
