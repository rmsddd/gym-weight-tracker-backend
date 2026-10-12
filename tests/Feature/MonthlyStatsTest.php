<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MonthlyStatsTest extends TestCase
{
    use RefreshDatabase;

    private function logWorkout(User $user, string $date, ?int $minutes = 60): void
    {
        Workout::create([
            'user_id' => $user->id,
            'date' => $date,
            'duration_minutes' => $minutes,
            'finished_at' => $minutes === null ? null : "$date 19:00:00",
        ]);
    }

    public function test_monthly_stats_count_finished_workouts_per_month(): void
    {
        $this->travelTo('2026-10-12 12:00:00');

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        foreach (['05-01', '05-04', '05-11', '05-18', '05-25', '05-31'] as $day) {
            $this->logWorkout($user, "2026-$day", 45);
        }
        $this->logWorkout($user, '2026-10-01');
        $this->logWorkout($user, '2026-10-12', 30);
        $this->logWorkout($user, '2025-11-01');

        // Ignored: still in progress, older than 12 months, and another user's workout
        $this->logWorkout($user, '2026-10-12', null);
        $this->logWorkout($user, '2025-10-31');
        $this->logWorkout(User::factory()->create(), '2026-05-10');

        $this->getJson('/api/monthly-stats')
            ->assertOk()
            ->assertJsonCount(12, 'data')
            ->assertJsonPath('data.0', ['month' => '2026-10', 'workouts_count' => 2, 'total_minutes' => 90])
            ->assertJsonPath('data.1', ['month' => '2026-09', 'workouts_count' => 0, 'total_minutes' => 0])
            ->assertJsonPath('data.5', ['month' => '2026-05', 'workouts_count' => 6, 'total_minutes' => 270])
            ->assertJsonPath('data.11', ['month' => '2025-11', 'workouts_count' => 1, 'total_minutes' => 60]);
    }

    public function test_monthly_stats_require_authentication(): void
    {
        $this->getJson('/api/monthly-stats')->assertUnauthorized();
    }
}
