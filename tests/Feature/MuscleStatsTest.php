<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Exercise;
use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutSet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MuscleStatsTest extends TestCase
{
    use RefreshDatabase;

    private function logSet(User $user, Exercise $exercise, string $date, bool $withSet = true): void
    {
        $workout = Workout::create(['user_id' => $user->id, 'date' => $date]);
        $workoutExercise = $workout->workoutExercises()->create(['exercise_id' => $exercise->id]);
        if ($withSet) {
            WorkoutSet::create(['workout_exercise_id' => $workoutExercise->id, 'weight' => 50, 'reps' => 8]);
        }
    }

    public function test_muscle_stats_count_sessions_per_group_in_the_current_week(): void
    {
        // A Thursday: the week started on Monday 2026-10-05
        $this->travelTo('2026-10-08 12:00:00');

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $chest = Category::create(['name' => 'Chest']);
        $legs = Category::create(['name' => 'Legs']);
        $biceps = Category::create(['name' => 'Biceps']);
        Category::create(['name' => 'Abs']);

        $bench = Exercise::create(['name' => 'Bench Press', 'category_id' => $chest->id]);
        $fly = Exercise::create(['name' => 'Cable Fly', 'category_id' => $chest->id]);
        $squat = Exercise::create(['name' => 'Squat', 'category_id' => $legs->id]);
        $curl = Exercise::create(['name' => 'Curl', 'category_id' => $biceps->id]);

        // Chest: 4 sessions this week -> level capped at 3
        foreach (['10-05', '10-06', '10-07', '10-08'] as $day) {
            $this->logSet($user, $bench, "2026-$day");
        }
        // Legs: 2 sessions -> level 2
        foreach (['10-05', '10-07'] as $day) {
            $this->logSet($user, $squat, "2026-$day");
        }
        // Biceps: 1 session -> level 1
        $this->logSet($user, $curl, '2026-10-06');

        // Ignored: last week's Sunday, an exercise without sets, and another user's workout
        $this->logSet($user, $curl, '2026-10-04');
        $this->logSet($user, $curl, '2026-10-05', withSet: false);
        $this->logSet(User::factory()->create(), $curl, '2026-10-06');

        // Two chest exercises in the same workout count as one chest session
        $sameDay = Workout::where('user_id', $user->id)->whereDate('date', '2026-10-08')->first();
        $extra = $sameDay->workoutExercises()->create(['exercise_id' => $fly->id]);
        WorkoutSet::create(['workout_exercise_id' => $extra->id, 'weight' => 10, 'reps' => 12]);

        $this->getJson('/api/muscle-stats')
            ->assertOk()
            ->assertJsonCount(4, 'data')
            ->assertJsonPath('data.0.category.name', 'Chest')
            ->assertJsonPath('data.0.sessions', 4)
            ->assertJsonPath('data.0.level', 3)
            ->assertJsonMissingPath('data.0.per_week')
            ->assertJsonPath('data.1.category.name', 'Legs')
            ->assertJsonPath('data.1.sessions', 2)
            ->assertJsonPath('data.1.level', 2)
            ->assertJsonPath('data.2.category.name', 'Biceps')
            ->assertJsonPath('data.2.sessions', 1)
            ->assertJsonPath('data.2.level', 1)
            ->assertJsonPath('data.3.category.name', 'Abs')
            ->assertJsonPath('data.3.sessions', 0)
            ->assertJsonPath('data.3.level', 0);
    }

    public function test_muscle_stats_reset_on_monday(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $chest = Category::create(['name' => 'Chest']);
        $bench = Exercise::create(['name' => 'Bench Press', 'category_id' => $chest->id]);

        $this->logSet($user, $bench, '2026-10-10');
        $this->logSet($user, $bench, '2026-10-11');

        // Sunday: both of the weekend's sessions still count
        $this->travelTo('2026-10-11 23:30:00');
        $this->getJson('/api/muscle-stats')
            ->assertJsonPath('data.0.sessions', 2)
            ->assertJsonPath('data.0.level', 2);

        // Monday: a new week starts from zero
        $this->travelTo('2026-10-12 00:30:00');
        $this->getJson('/api/muscle-stats')
            ->assertJsonPath('data.0.sessions', 0)
            ->assertJsonPath('data.0.level', 0);

        $this->logSet($user, $bench, '2026-10-12');
        $this->getJson('/api/muscle-stats')
            ->assertJsonPath('data.0.sessions', 1)
            ->assertJsonPath('data.0.level', 1);
    }

    public function test_muscle_stats_require_authentication(): void
    {
        $this->getJson('/api/muscle-stats')->assertUnauthorized();
    }
}
