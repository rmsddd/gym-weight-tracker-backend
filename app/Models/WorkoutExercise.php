<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class WorkoutExercise extends Model
{
    protected $fillable = [
        'exercise_id',
        'workout_id',
    ];

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }

    public function workout(): BelongsTo
    {
        return $this->belongsTo(Workout::class);
    }
    public function sets(): HasMany
    {
        return $this->hasMany(WorkoutSet::class);
    }
}
