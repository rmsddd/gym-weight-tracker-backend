<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Exercise;
use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutExercise;
use App\Models\WorkoutSet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OptimizedQueriesTest extends TestCase
{
    use RefreshDatabase;

    private function exercise(string $name): Exercise
    {
        return Exercise::create(['name' => $name, 'category_id' => Category::firstOrCreate(['name' => 'Chest'])->id]);
    }

    /** @param array<int, array{0: float, 1: int}> $sets */
    private function workout(User $user, Exercise $exercise, string $date, array $sets, ?int $minutes = null): Workout
    {
        $workout = Workout::create(['user_id' => $user->id, 'date' => $date, 'duration_minutes' => $minutes]);
        $workoutExercise = $workout->workoutExercises()->create(['exercise_id' => $exercise->id]);
        foreach ($sets as [$weight, $reps]) {
            WorkoutSet::create(['workout_exercise_id' => $workoutExercise->id, 'weight' => $weight, 'reps' => $reps]);
        }

        return $workout;
    }

    public function test_personal_records_pick_heaviest_then_most_reps_then_earliest(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $bench = $this->exercise('Bench Press');
        $curl = $this->exercise('Curl');

        $this->workout($user, $bench, '2026-09-01', [[80, 5], [100, 3]]);
        $tied = $this->workout($user, $bench, '2026-09-10', [[100, 6], [90, 10]]);
        $this->workout($user, $bench, '2026-09-20', [[100, 6]]); // same weight and reps, logged later
        $this->workout($user, $curl, '2026-09-05', [[20, 12]]);
        $this->workout(User::factory()->create(), $bench, '2026-09-15', [[300, 1]]); // someone else

        $this->getJson('/api/personal-records')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.exercise.name', 'Bench Press')
            ->assertJsonPath('data.0.weight', 100)
            ->assertJsonPath('data.0.reps', 6)
            ->assertJsonPath('data.0.workout_id', $tied->id)
            ->assertJsonPath('data.0.date', '2026-09-10')
            ->assertJsonPath('data.1.exercise.name', 'Curl')
            ->assertJsonPath('data.1.weight', 20);
    }

    public function test_previous_sets_cover_every_exercise_and_keep_set_order(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $bench = $this->exercise('Bench Press');
        $curl = $this->exercise('Curl');
        $squat = $this->exercise('Squat');

        $this->workout($user, $bench, '2026-09-01', [[60, 10]]);
        $this->workout($user, $bench, '2026-09-20', [[70, 8], [72.5, 6], [75, 4]]);
        $this->workout($user, $curl, '2026-09-10', [[15, 12]]);
        $this->workout($user, $curl, '2026-10-08', [[99, 1]]); // same day, but created before the current one
        $this->workout(User::factory()->create(), $squat, '2026-09-01', [[200, 1]]); // someone else

        $current = Workout::create(['user_id' => $user->id, 'date' => '2026-10-08']);
        foreach ([$bench, $curl, $squat] as $exercise) {
            $current->workoutExercises()->create(['exercise_id' => $exercise->id]);
        }

        $response = $this->getJson("/api/workouts/{$current->id}/previous-sets")->assertOk();

        $response->assertJsonPath("data.{$bench->id}.date", '2026-09-20')
            ->assertJsonPath("data.{$bench->id}.sets.0.weight", 70)
            ->assertJsonPath("data.{$bench->id}.sets.1.weight", 72.5)
            ->assertJsonPath("data.{$bench->id}.sets.2.reps", 4)
            ->assertJsonPath("data.{$curl->id}.date", '2026-10-08')
            ->assertJsonPath("data.{$curl->id}.sets.0.weight", 99)
            ->assertJsonMissingPath("data.{$squat->id}");
    }

    public function test_profile_stats_total_the_users_own_workouts(): void
    {
        $user = User::factory()->create();
        $bench = $this->exercise('Bench Press');
        $curl = $this->exercise('Curl');

        Sanctum::actingAs($user);
        $this->getJson('/api/profile-stats')
            ->assertOk()
            ->assertExactJson(['data' => ['workouts_count' => 0, 'total_minutes' => 0, 'records_count' => 0]]);

        $this->workout($user, $bench, '2026-09-01', [[80, 5]], minutes: 45);
        $this->workout($user, $bench, '2026-09-08', [[85, 5]], minutes: 50);
        $this->workout($user, $curl, '2026-09-10', [[20, 10]]); // still running: no duration yet
        $this->workout(User::factory()->create(), $curl, '2026-09-10', [[30, 10]], minutes: 500);

        $this->getJson('/api/profile-stats')
            ->assertOk()
            ->assertExactJson(['data' => ['workouts_count' => 3, 'total_minutes' => 95, 'records_count' => 2]]);
    }

    public function test_deleting_a_workout_or_an_exercise_removes_its_sets_and_nothing_else(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $bench = $this->exercise('Bench Press');

        $kept = $this->workout($user, $bench, '2026-09-01', [[80, 5], [80, 4]]);
        $deleted = $this->workout($user, $bench, '2026-09-08', [[85, 5], [85, 4], [85, 3]]);
        $deleted->workoutExercises()->create(['exercise_id' => $this->exercise('Curl')->id]);

        $this->deleteJson("/api/workouts/{$deleted->id}")->assertNoContent();

        $this->assertDatabaseMissing('workouts', ['id' => $deleted->id]);
        $this->assertSame(1, WorkoutExercise::count());
        $this->assertSame(2, WorkoutSet::count());

        $keptExercise = $kept->workoutExercises()->first();
        $this->deleteJson("/api/workouts/{$kept->id}/exercises/{$keptExercise->id}")->assertNoContent();

        $this->assertDatabaseHas('workouts', ['id' => $kept->id]);
        $this->assertSame(0, WorkoutExercise::count());
        $this->assertSame(0, WorkoutSet::count());
    }
}
