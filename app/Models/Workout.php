<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workout extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'date',
        'note',
        'duration_minutes',
        'started_at',
        'resumed_at',
        'finished_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'started_at' => 'datetime',
            'resumed_at' => 'datetime',
            'finished_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Workouts that make their owner show online to friends: started and not finished for good.
     * A stopped one still counts; only Finish ends it. The latest started or resumed comes first.
     */
    public function scopeLive(Builder $query): void
    {
        $query->whereNotNull('started_at')
            ->whereNull('completed_at')
            ->orderByRaw('COALESCE(resumed_at, started_at) DESC')
            ->orderByDesc('id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function workoutExercises(): HasMany
    {
        // In the order the user arranged them; id breaks ties between rows from before positions
        return $this->hasMany(WorkoutExercise::class)->orderBy('position')->orderBy('id');
    }
}
