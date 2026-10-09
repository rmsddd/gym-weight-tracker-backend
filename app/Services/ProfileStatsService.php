<?php

namespace App\Services;

use App\Models\Workout;

class ProfileStatsService
{
    public function __construct(protected PersonalRecordService $records) {}

    /**
     * Lifetime totals for the profile page, computed by the database so the page does not
     * have to download every workout just to count them.
     *
     * @return array{workouts_count: int, total_minutes: int, records_count: int}
     */
    public function getForUser(int $userId): array
    {
        $totals = Workout::where('user_id', $userId)
            ->selectRaw('COUNT(*) AS workouts_count, COALESCE(SUM(duration_minutes), 0) AS total_minutes')
            ->first();

        return [
            'workouts_count' => (int) $totals->workouts_count,
            'total_minutes' => (int) $totals->total_minutes,
            'records_count' => $this->records->countForUser($userId),
        ];
    }
}
