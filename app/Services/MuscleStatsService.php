<?php

namespace App\Services;

use App\Models\Category;
use App\Models\WorkoutExercise;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class MuscleStatsService
{
    public const MAX_LEVEL = 3;

    /**
     * How often the user trained each muscle group (category) this week. The count starts over
     * every Monday. A workout counts for a group once, as soon as it has at least one logged set of it.
     *
     * @return Collection<int, array{category: Category, sessions: int, level: int}>
     */
    public function getForUser(int $userId): Collection
    {
        $since = now()->startOfWeek(CarbonInterface::MONDAY)->toDateString();

        $sessions = WorkoutExercise::query()
            ->whereHas('sets')
            ->whereHas('workout', fn ($query) => $query
                ->where('user_id', $userId)
                ->whereDate('date', '>=', $since)
                ->whereDate('date', '<=', now()->toDateString()))
            ->with('exercise:id,category_id')
            ->get()
            ->groupBy(fn (WorkoutExercise $workoutExercise) => $workoutExercise->exercise->category_id)
            ->map(fn (Collection $items) => $items->pluck('workout_id')->unique()->count());

        return Category::orderBy('id')->get()->map(function (Category $category) use ($sessions) {
            $count = $sessions->get($category->id, 0);

            return [
                'category' => $category,
                'sessions' => $count,
                // One level per session this week: 0 = not trained, 3 = three sessions or more
                'level' => min($count, self::MAX_LEVEL),
            ];
        });
    }
}
