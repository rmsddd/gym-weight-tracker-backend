<?php

namespace App\Services;

use App\Models\Workout;
use App\Models\WorkoutTemplate;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class WorkoutTemplateService
{
    public function getAll(int $userId): Collection
    {
        return WorkoutTemplate::where('user_id', $userId)
            ->with('exercises.exercise.category')
            ->orderBy('name')
            ->get();
    }

    public function create(int $userId, string $name, array $exerciseIds): WorkoutTemplate
    {
        return DB::transaction(function () use ($userId, $name, $exerciseIds) {
            $template = WorkoutTemplate::create(['user_id' => $userId, 'name' => $name]);
            foreach (array_values($exerciseIds) as $position => $exerciseId) {
                $template->exercises()->create(['exercise_id' => $exerciseId, 'position' => $position]);
            }
            return $template->load('exercises.exercise.category');
        });
    }

    public function createFromWorkout(Workout $workout, string $name): WorkoutTemplate
    {
        $exerciseIds = $workout->workoutExercises()->orderBy('id')->pluck('exercise_id')->unique()->all();
        return $this->create($workout->user_id, $name, $exerciseIds);
    }

    /** Copies the template exercises (without sets) into a freshly created workout. */
    public function applyToWorkout(WorkoutTemplate $template, Workout $workout): void
    {
        foreach ($template->exercises as $item) {
            $workout->workoutExercises()->create(['exercise_id' => $item->exercise_id]);
        }
    }

    public function delete(WorkoutTemplate $template): void
    {
        DB::transaction(function () use ($template) {
            $template->exercises()->delete();
            $template->delete();
        });
    }
}
