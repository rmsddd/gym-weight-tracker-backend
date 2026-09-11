<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workout extends Model
{
    protected $fillable = [
        'name',
        'date',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }
    public function workoutExercises(): HasMany
    {
        return $this->hasMany(WorkoutExercise::class);
    }
}
