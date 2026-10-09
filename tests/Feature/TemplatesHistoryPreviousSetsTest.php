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

class TemplatesHistoryPreviousSetsTest extends TestCase
{
    use RefreshDatabase;

    private function exercise(string $name, string $category = 'Chest'): Exercise
    {
        return Exercise::create([
            'name' => $name,
            'category_id' => Category::firstOrCreate(['name' => $category])->id,
        ]);
    }

    private function workoutWithSet(User $user, Exercise $exercise, string $date, float $weight, int $reps): Workout
    {
        $workout = Workout::create(['user_id' => $user->id, 'date' => $date, 'finished_at' => now()]);
        $we = $workout->workoutExercises()->create(['exercise_id' => $exercise->id]);
        WorkoutSet::create(['workout_exercise_id' => $we->id, 'weight' => $weight, 'reps' => $reps]);

        return $workout;
    }

    public function test_template_can_be_created_and_starts_a_workout_with_its_exercises(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $bench = $this->exercise('Bench Press');
        $fly = $this->exercise('Cable Fly');

        $templateId = $this->postJson('/api/workout-templates', [
            'name' => 'Push day',
            'exercise_ids' => [$bench->id, $fly->id],
        ])->assertCreated()
            ->assertJsonPath('data.exercises.0.name', 'Bench Press')
            ->assertJsonPath('data.exercises.1.name', 'Cable Fly')
            ->json('data.id');

        $this->getJson('/api/workout-templates')->assertOk()->assertJsonCount(1, 'data');

        $workoutId = $this->postJson('/api/workouts', ['date' => '2026-10-08', 'template_id' => $templateId])
            ->assertCreated()->json('data.id');

        $this->getJson("/api/workouts/$workoutId/exercises")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.exercise.name', 'Bench Press');
    }

    public function test_workout_can_be_saved_as_template(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $workout = $this->workoutWithSet($user, $this->exercise('Squat', 'Legs'), '2026-10-01', 100, 5);

        $this->postJson("/api/workouts/{$workout->id}/template", ['name' => 'Leg day'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Leg day')
            ->assertJsonPath('data.exercises.0.name', 'Squat');
    }

    public function test_templates_are_private_to_their_owner(): void
    {
        $owner = User::factory()->create();
        Sanctum::actingAs($owner);
        $exercise = $this->exercise('Bench Press');
        $templateId = $this->postJson('/api/workout-templates', ['name' => 'Push', 'exercise_ids' => [$exercise->id]])
            ->json('data.id');

        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/workout-templates')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson("/api/workout-templates/$templateId")->assertForbidden();
        $this->deleteJson("/api/workout-templates/$templateId")->assertForbidden();
        $this->postJson('/api/workouts', ['date' => '2026-10-08', 'template_id' => $templateId])
            ->assertUnprocessable();
    }

    public function test_template_can_be_deleted(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $exercise = $this->exercise('Bench Press');
        $templateId = $this->postJson('/api/workout-templates', ['name' => 'Push', 'exercise_ids' => [$exercise->id]])
            ->json('data.id');

        $this->deleteJson("/api/workout-templates/$templateId")->assertNoContent();
        $this->getJson('/api/workout-templates')->assertJsonCount(0, 'data');
    }

    public function test_history_can_be_filtered_and_includes_exercise_summary(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $bench = $this->exercise('Bench Press', 'Chest');
        $squat = $this->exercise('Squat', 'Legs');
        $this->workoutWithSet($user, $bench, '2026-09-01', 60, 10);
        $this->workoutWithSet($user, $squat, '2026-09-15', 100, 5);
        $this->workoutWithSet($user, $bench, '2026-10-01', 62.5, 8);
        Workout::create(['user_id' => $user->id, 'date' => '2026-10-02']); // still running
        $this->workoutWithSet(User::factory()->create(), $bench, '2026-10-01', 200, 1); // someone else's

        $this->getJson('/api/workouts?finished=1')->assertOk()->assertJsonCount(3, 'data');
        $this->getJson('/api/workouts?exercise_id=' . $bench->id)->assertJsonCount(2, 'data');
        $this->getJson('/api/workouts?category_id=' . $squat->category_id)->assertJsonCount(1, 'data');
        $this->getJson('/api/workouts?from=2026-09-10&to=2026-09-30')->assertJsonCount(1, 'data');

        $this->getJson('/api/workouts?finished=1&include=exercises')
            ->assertOk()
            ->assertJsonPath('data.0.date', '2026-10-01T00:00:00.000000Z')
            ->assertJsonPath('data.0.exercises.0.name', 'Bench Press')
            ->assertJsonPath('data.0.exercises.0.sets_count', 1)
            ->assertJsonPath('data.0.exercises.0.volume', 500);
    }

    public function test_previous_sets_come_from_the_latest_earlier_workout(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $bench = $this->exercise('Bench Press');
        $this->workoutWithSet($user, $bench, '2026-09-01', 60, 10);
        $this->workoutWithSet($user, $bench, '2026-09-20', 70, 8);
        $this->workoutWithSet($user, $bench, '2026-11-01', 999, 1); // later than the current one

        $current = Workout::create(['user_id' => $user->id, 'date' => '2026-10-08']);
        $current->workoutExercises()->create(['exercise_id' => $bench->id]);

        $this->getJson("/api/workouts/{$current->id}/previous-sets")
            ->assertOk()
            ->assertJsonPath("data.{$bench->id}.date", '2026-09-20')
            ->assertJsonPath("data.{$bench->id}.sets.0.weight", 70)
            ->assertJsonPath("data.{$bench->id}.sets.0.reps", 8);
    }

    public function test_a_stopped_workout_can_be_resumed_by_its_owner_only(): void
    {
        $owner = User::factory()->create();
        $workout = Workout::create([
            'user_id' => $owner->id,
            'date' => '2026-10-08',
            'started_at' => now()->subMinutes(30),
            'finished_at' => now(),
            'duration_minutes' => 30,
        ]);

        Sanctum::actingAs(User::factory()->create());
        $this->postJson("/api/workouts/{$workout->id}/resume")->assertForbidden();

        Sanctum::actingAs($owner);
        $this->postJson("/api/workouts/{$workout->id}/resume")
            ->assertOk()
            ->assertJsonPath('data.finished_at', null)
            ->assertJsonPath('data.duration_minutes', null);

        $this->assertNotNull($workout->fresh()->started_at);
    }

    public function test_completing_a_workout_is_final_and_cannot_be_resumed(): void
    {
        $owner = User::factory()->create();
        $running = Workout::create(['user_id' => $owner->id, 'date' => '2026-10-08', 'started_at' => now()->subMinutes(20)]);
        $stopped = Workout::create([
            'user_id' => $owner->id,
            'date' => '2026-10-08',
            'started_at' => now()->subMinutes(40),
            'finished_at' => now()->subMinutes(10),
            'duration_minutes' => 30,
        ]);

        Sanctum::actingAs(User::factory()->create());
        $this->postJson("/api/workouts/{$running->id}/complete")->assertForbidden();

        Sanctum::actingAs($owner);

        // Finishing a running workout stops it and locks it
        $this->postJson("/api/workouts/{$running->id}/complete")
            ->assertOk()
            ->assertJsonPath('data.duration_minutes', 20)
            ->assertJsonStructure(['data' => ['finished_at', 'completed_at']]);
        $this->assertNotNull($running->fresh()->completed_at);
        $this->postJson("/api/workouts/{$running->id}/resume")->assertForbidden();

        // Finishing an already stopped workout keeps the duration from the stop
        $this->postJson("/api/workouts/{$stopped->id}/complete")
            ->assertOk()
            ->assertJsonPath('data.duration_minutes', 30);
        $this->postJson("/api/workouts/{$stopped->id}/resume")->assertForbidden();
        $this->assertNotNull($stopped->fresh()->finished_at);
    }

    public function test_previous_sets_are_empty_without_history_and_forbidden_for_others(): void
    {
        $user = User::factory()->create();
        $workout = Workout::create(['user_id' => $user->id, 'date' => '2026-10-08']);

        Sanctum::actingAs(User::factory()->create());
        $this->getJson("/api/workouts/{$workout->id}/previous-sets")->assertForbidden();

        Sanctum::actingAs($user);
        $this->getJson("/api/workouts/{$workout->id}/previous-sets")->assertOk()->assertExactJson(['data' => []]);
    }
}
