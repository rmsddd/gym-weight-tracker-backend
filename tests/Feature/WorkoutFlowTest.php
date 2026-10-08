<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Exercise;
use App\Models\User;
use App\Models\Workout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WorkoutFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_add_exercise_and_set_then_finish_workout(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $exercise = Exercise::create(['name' => 'Push-Up', 'category_id' => Category::create(['name' => 'Chest'])->id]);

        $workoutId = $this->postJson('/api/workouts', ['date' => '2026-10-06'])
            ->assertCreated()->json('data.id');

        $weId = $this->postJson("/api/workouts/$workoutId/exercises", ['exercise_id' => $exercise->id])
            ->assertCreated()->json('data.id');

        $this->postJson("/api/workouts/$workoutId/exercises/$weId/sets", ['weight' => 20, 'reps' => 10])
            ->assertCreated();

        $this->getJson("/api/workouts/$workoutId/exercises")
            ->assertOk()
            ->assertJsonPath('data.0.sets.0.reps', 10)
            ->assertJsonPath('data.0.exercise.category.name', 'Chest');

        $this->postJson("/api/workouts/$workoutId/finish")
            ->assertOk()
            ->assertJsonPath('data.duration_minutes', 0);
    }

    public function test_other_users_cannot_access_workout_exercises(): void
    {
        $owner = User::factory()->create();
        $workout = Workout::create(['user_id' => $owner->id, 'date' => '2026-10-06']);
        $exercise = Exercise::create(['name' => 'Push-Up', 'category_id' => Category::create(['name' => 'Chest'])->id]);

        Sanctum::actingAs(User::factory()->create());

        $this->getJson("/api/workouts/{$workout->id}/exercises")->assertForbidden();
        $this->postJson("/api/workouts/{$workout->id}/exercises", ['exercise_id' => $exercise->id])->assertForbidden();
    }

    public function test_other_users_cannot_touch_a_workout(): void
    {
        $workout = Workout::create(['user_id' => User::factory()->create()->id, 'date' => '2026-10-06']);

        Sanctum::actingAs(User::factory()->create());

        $this->getJson("/api/workouts/{$workout->id}")->assertForbidden();
        $this->putJson("/api/workouts/{$workout->id}", ['name' => 'x'])->assertForbidden();
        $this->postJson("/api/workouts/{$workout->id}/finish")->assertForbidden();
        $this->deleteJson("/api/workouts/{$workout->id}")->assertForbidden();
        $this->assertDatabaseHas('workouts', ['id' => $workout->id]);
    }

    public function test_workout_list_only_contains_own_workouts(): void
    {
        $other = User::factory()->create();
        Workout::create(['user_id' => $other->id, 'date' => '2026-10-06']);
        $me = User::factory()->create();
        Workout::create(['user_id' => $me->id, 'date' => '2026-10-06']);

        Sanctum::actingAs($me);

        $this->getJson('/api/workouts')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_child_from_another_workout_in_the_url_is_not_found(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $exercise = Exercise::create(['name' => 'Push-Up', 'category_id' => Category::create(['name' => 'Chest'])->id]);

        $first = $this->postJson('/api/workouts', ['date' => '2026-10-06'])->json('data.id');
        $second = $this->postJson('/api/workouts', ['date' => '2026-10-06'])->json('data.id');
        $weId = $this->postJson("/api/workouts/$first/exercises", ['exercise_id' => $exercise->id])->json('data.id');
        $setId = $this->postJson("/api/workouts/$first/exercises/$weId/sets", ['weight' => 20, 'reps' => 10])->json('data.id');

        $this->getJson("/api/workouts/$second/exercises/$weId")->assertNotFound();
        $this->getJson("/api/workouts/$first/exercises/$weId/sets/$setId")->assertOk();
        $this->deleteJson("/api/workouts/$second/exercises/$weId/sets/$setId")->assertNotFound();
    }

    public function test_custom_exercises_are_private_and_catalog_is_read_only(): void
    {
        $category = Category::create(['name' => 'Chest']);
        $catalog = Exercise::create(['name' => 'Push-Up', 'category_id' => $category->id]);
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        Sanctum::actingAs($alice);
        $mine = $this->postJson('/api/exercises', ['name' => 'Al meu', 'category_id' => $category->id])
            ->assertCreated()->json('data.id');
        $this->putJson("/api/exercises/$mine", ['name' => 'Redenumit', 'category_id' => $category->id])->assertOk();
        $this->putJson("/api/exercises/{$catalog->id}", ['name' => 'Hack', 'category_id' => $category->id])->assertForbidden();
        $this->deleteJson("/api/exercises/{$catalog->id}")->assertForbidden();

        Sanctum::actingAs($bob);
        $this->getJson('/api/exercises')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson("/api/exercises/$mine")->assertForbidden();
        $this->putJson("/api/exercises/$mine", ['name' => 'Hack', 'category_id' => $category->id])->assertForbidden();
        $this->deleteJson("/api/exercises/$mine")->assertForbidden();

        $workoutId = $this->postJson('/api/workouts', ['date' => '2026-10-06'])->json('data.id');
        $this->postJson("/api/workouts/$workoutId/exercises", ['exercise_id' => $mine])->assertUnprocessable();
    }

    public function test_personal_records_return_the_best_set_per_exercise_for_the_current_user_only(): void
    {
        $exercise = Exercise::create(['name' => 'Push-Up', 'category_id' => Category::create(['name' => 'Chest'])->id]);

        $addSets = function (User $user, array $sets) use ($exercise) {
            Sanctum::actingAs($user);
            $workoutId = $this->postJson('/api/workouts', ['date' => '2026-10-06'])->json('data.id');
            $weId = $this->postJson("/api/workouts/$workoutId/exercises", ['exercise_id' => $exercise->id])->json('data.id');
            foreach ($sets as [$weight, $reps]) {
                $this->postJson("/api/workouts/$workoutId/exercises/$weId/sets", ['weight' => $weight, 'reps' => $reps]);
            }
        };

        $me = User::factory()->create();
        $addSets($me, [[20, 12], [30, 5], [30, 8], [25, 20]]);
        $addSets(User::factory()->create(), [[100, 1]]);

        Sanctum::actingAs($me);
        $this->getJson('/api/personal-records')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.exercise.name', 'Push-Up')
            ->assertJsonPath('data.0.weight', 30)
            ->assertJsonPath('data.0.reps', 8);
    }

    public function test_user_gets_a_default_avatar_and_can_change_it_to_a_valid_one(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/user')->assertOk()->assertJsonPath('avatar', 'dino');
        $this->patchJson('/api/user/avatar', ['avatar' => 'lion'])->assertOk()->assertJsonPath('avatar', 'lion');
        $this->getJson('/api/user')->assertJsonPath('avatar', 'lion');
        $this->patchJson('/api/user/avatar', ['avatar' => 'anything'])->assertUnprocessable();
        $this->patchJson('/api/user/avatar', [])->assertUnprocessable();
    }

    public function test_categories_are_read_only(): void
    {
        $category = Category::create(['name' => 'Chest']);
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/categories')->assertOk()->assertJsonCount(1, 'data');
        $this->postJson('/api/categories', ['name' => 'Nou'])->assertForbidden();
        $this->putJson("/api/categories/{$category->id}", ['name' => 'Hack'])->assertForbidden();
        $this->deleteJson("/api/categories/{$category->id}")->assertForbidden();
    }
}
