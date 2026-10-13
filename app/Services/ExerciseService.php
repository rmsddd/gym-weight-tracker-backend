<?php

namespace App\Services;

use App\Enums\ExerciseUnit;
use App\Models\Category;
use App\Models\Exercise;
use Illuminate\Support\Collection;

class ExerciseService
{

    public function getAll(int $userId): Collection
    {
        return Exercise::visibleTo($userId)->with('category', 'secondaryCategories')->get();
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
        $exercise->delete();
    }
}
