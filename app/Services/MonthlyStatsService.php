<?php

namespace App\Services;

use App\Models\Workout;

class MonthlyStatsService
{
    public const MONTHS = 12;

    /**
     * Finished workouts per calendar month for the last 12 months, newest first.
     * Months without a workout are included with zeros, so breaks stay visible.
     *
     * @return list<array{month: string, workouts_count: int, total_minutes: int}>
     */
    public function getForUser(int $userId): array
    {
        $first = now()->startOfMonth()->subMonths(self::MONTHS - 1);

        // SUBSTR instead of a date function: it reads "YYYY-MM" the same way on MySQL and SQLite
        $totals = Workout::where('user_id', $userId)
            ->whereNotNull('finished_at')
            ->whereDate('date', '>=', $first->toDateString())
            ->whereDate('date', '<=', now()->endOfMonth()->toDateString())
            ->selectRaw('SUBSTR(date, 1, 7) AS month, COUNT(*) AS workouts_count, COALESCE(SUM(duration_minutes), 0) AS total_minutes')
            ->groupBy('month')
            ->get()
            ->keyBy('month');

        $months = [];
        for ($i = self::MONTHS - 1; $i >= 0; $i--) {
            $month = $first->copy()->addMonths($i)->format('Y-m');
            $months[] = [
                'month' => $month,
                'workouts_count' => (int) ($totals[$month]->workouts_count ?? 0),
                'total_minutes' => (int) ($totals[$month]->total_minutes ?? 0),
            ];
        }

        return $months;
    }
}
