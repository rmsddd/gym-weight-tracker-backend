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

class DeletedExerciseTest extends TestCase
{
    use RefreshDatabase;

    private function customExercise(User $user, string $name): Exercise
    {
        return Exercise::create([
            'name' => $name,
            'category_id' => Category::firstOrCreate(['name' => 'Chest'])->id,
            'user_id' => $user->id,
        ]);
    }

    private function workoutWithSet(User $user, Exercise $exercise, string $date, float $weight, int $reps): Workout
    {
        $workout = Workout::create(['user_id' => $user->id, 'date' => $date, 'finished_at' => now()]);
        $we = $workout->workoutExercises()->create(['exercise_id' => $exercise->id]);
        WorkoutSet::create(['workout_exercise_id' => $we->id, 'weight' => $weight, 'reps' => $reps]);

        return $workout;
    }

    public function test_deleted_exercise_stays_in_history_but_leaves_the_picker_and_the_records(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $press = $this->customExercise($user, 'Floor Press');
        $fly = $this->customExercise($user, 'Cable Fly');
        $workout = $this->workoutWithSet($user, $press, '2026-10-01', 70, 6);
        $this->workoutWithSet($user, $fly, '2026-10-02', 20, 12);

        $this->getJson('/api/personal-records')->assertJsonCount(2, 'data');

        $this->deleteJson("/api/exercises/{$press->id}")->assertNoContent();
        $this->assertSoftDeleted($press);

        // Gone from the picker and the records
        $this->getJson('/api/exercises')->assertOk()
            ->assertJsonMissing(['name' => 'Floor Press'])
            ->assertJsonFragment(['name' => 'Cable Fly']);
        $this->getJson('/api/personal-records')->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.exercise.name', 'Cable Fly');

        // Still in the workout that logged it, with its sets
        $this->assertDatabaseCount('workout_sets', 2);
        $this->getJson("/api/workouts/{$workout->id}/exercises")->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.exercise.name', 'Floor Press')
            ->assertJsonPath('data.0.exercise.is_deleted', true);

        // And the history can still be filtered by it
        $this->getJson('/api/exercises?deleted=1')->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $press->id);
        $this->getJson("/api/workouts?exercise_id={$press->id}")->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $workout->id);
    }

    public function test_deleted_exercise_cannot_be_added_to_a_new_workout(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $press = $this->customExercise($user, 'Floor Press');
        $press->delete();

        $workoutId = $this->postJson('/api/workouts', ['date' => '2026-10-19'])->json('data.id');
        $this->postJson("/api/workouts/$workoutId/exercises", ['exercise_id' => $press->id])
            ->assertUnprocessable();
    }

    public function test_deleted_exercises_of_other_users_are_not_listed(): void
    {
        $other = User::factory()->create();
        $this->customExercise($other, 'Floor Press')->delete();
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/exercises?deleted=1')->assertOk()->assertJsonCount(0, 'data');
    }
}
