<?php

namespace App\Models;

use App\Enums\ExerciseUnit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exercise extends Model
{
    protected $fillable = [
        'name',
        'unit',
        'category_id',
        'user_id',
    ];

    protected $attributes = [
        'unit' => 'kg',
    ];

    protected function casts(): array
    {
        return [
            'unit' => ExerciseUnit::class,
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** The other muscle groups a compound exercise works; empty for an isolation exercise. */
    public function secondaryCategories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'exercise_secondary_categories')
            ->orderByPivot('id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function workoutExercises(): HasMany
    {
        return $this->hasMany(WorkoutExercise::class);
    }

    /** Shared catalog exercises plus the ones this user created. */
    public function scopeVisibleTo(Builder $query, int $userId): Builder
    {
        return $query->where(fn (Builder $q) => $q->whereNull('user_id')->orWhere('user_id', $userId));
    }
}
