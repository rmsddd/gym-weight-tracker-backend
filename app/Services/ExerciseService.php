<?php

namespace App\Services;

use App\Enums\ExerciseUnit;
use App\Models\Category;
use App\Models\Exercise;
use App\Models\WorkoutTemplateExercise;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ExerciseService
{

    public function getAll(int $userId): Collection
    {
        return Exercise::visibleTo($userId)->with('category', 'secondaryCategories')->get();
    }

    /** The user's own exercises they deleted, which their workout history can still be filtered by. */
    public function getDeleted(int $userId): Collection
    {
        return Exercise::onlyTrashed()->where('user_id', $userId)->with('category', 'secondaryCategories')->get();
    }

    public function findById(int $id): Exercise
    {
        return Exercise::with('category', 'secondaryCategories')->findOrFail($id);
    }

    public function create(array $data): Exercise
    {
        // Cardio is logged in minutes unless the caller says otherwise
        $data['unit'] ??= Category::find($data['category_id'])?->name === Category::CARDIO
            ? ExerciseUnit::Minutes
            : ExerciseUnit::Kilograms;

        $exercise= Exercise::create($data);
        return $exercise->load('category', 'secondaryCategories');
    }

    public function update(Exercise $exercise, array $data): Exercise
    {
        $exercise->update($data);
        return $exercise->fresh()->load('category', 'secondaryCategories');
    }

    public function delete(Exercise $exercise): void
    {
        // Soft deleted: the workouts that logged it keep their sets. Templates only plan
        // future workouts, so it is taken out of them.
        DB::transaction(function () use ($exercise) {
            WorkoutTemplateExercise::where('exercise_id', $exercise->id)->delete();
            $exercise->delete();
        });
    }
}
