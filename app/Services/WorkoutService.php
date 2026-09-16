<?php

namespace App\Services;

use App\Models\Exercise;
use App\Models\Workout;
use Illuminate\Database\Eloquent\Collection;
class WorkoutService
{
   public function getAll(int $userId): Collection{
       return Workout::where('user_id', $userId)->get();
   }
   public function findById(int $id): Workout{
       return Workout::findOrFail($id);
   }
   public function create(array $data): Workout{
       return Workout::create($data);
   }
    public function update(Workout $workout, array $data): Workout
    {
        $workout->update($data);
        return $workout->fresh();
    }

    public function delete(Workout $workout): void
    {
        $workout->delete();
    }
}
