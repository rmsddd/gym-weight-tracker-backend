<?php

namespace App\Services;

use App\Models\Exercise;
use Illuminate\Support\Collection;

class ExerciseService
{

    public function getAll(): Collection
    {
        return Exercise::with('category')->get();
    }

    public function findById(int $id): Exercise
    {
        return Exercise::with('category')->findOrFail($id);
    }

    public function create(array $data): Exercise
    {
        return Exercise::create($data);
    }

    public function update(Exercise $exercise, array $data): Exercise
    {
        $exercise->update($data);
        return $exercise->fresh();
    }

    public function delete(Exercise $exercise): void
    {
        $exercise->delete();
    }
}
