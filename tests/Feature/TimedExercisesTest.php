<?php

namespace Tests\Feature;

use App\Enums\ExerciseUnit;
use App\Models\Category;
use App\Models\Exercise;
use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutSet;
use Database\Seeders\CategorySeeder;
use Database\Seeders\ExerciseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TimedExercisesTest extends TestCase
{
    use RefreshDatabase;

    private function timedExercise(string $name = 'Plank', string $category = 'Abs'): Exercise
    {
        return Exercise::create([
            'name' => $name,
            'unit' => ExerciseUnit::Minutes,
            'category_id' => Category::firstOrCreate(['name' => $category])->id,
        ]);
    }

    public function test_timed_exercise_logs_minutes_instead_of_weight_and_reps(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $plank = $this->timedExercise();

        $workoutId = $this->postJson('/api/workouts', ['date' => '2026-10-12'])->json('data.id');
        $weId = $this->postJson("/api/workouts/$workoutId/exercises", ['exercise_id' => $plank->id])
            ->assertCreated()
            ->assertJsonPath('data.exercise.unit', 'min')
            ->json('data.id');
        $sets = "/api/workouts/$workoutId/exercises/$weId/sets";

        $this->postJson($sets, ['weight' => 20, 'reps' => 10])->assertUnprocessable();
        $this->postJson($sets, ['duration_minutes' => 0])->assertUnprocessable();

        $setId = $this->postJson($sets, ['duration_minutes' => 1.5])
            ->assertCreated()
            ->assertJsonPath('data.duration_minutes', 1.5)
            ->assertJsonPath('data.weight', null)
            ->assertJsonPath('data.reps', null)
            ->json('data.id');

        $this->putJson("$sets/$setId", ['duration_minutes' => 2])
            ->assertOk()
            ->assertJsonPath('data.duration_minutes', 2);
    }

    public function test_weighted_exercise_still_requires_weight_and_reps(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $bench = Exercise::create(['name' => 'Bench Press', 'category_id' => Category::create(['name' => 'Chest'])->id]);

        $workoutId = $this->postJson('/api/workouts', ['date' => '2026-10-12'])->json('data.id');
        $weId = $this->postJson("/api/workouts/$workoutId/exercises", ['exercise_id' => $bench->id])
            ->assertJsonPath('data.exercise.unit', 'kg')
            ->json('data.id');

        $this->postJson("/api/workouts/$workoutId/exercises/$weId/sets", ['duration_minutes' => 5])->assertUnprocessable();
        $this->postJson("/api/workouts/$workoutId/exercises/$weId/sets", ['weight' => 60, 'reps' => 8])
            ->assertCreated()
            ->assertJsonPath('data.duration_minutes', null);
    }

    public function test_reps_can_be_logged_as_a_range_when_the_last_one_was_a_struggle(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $bench = Exercise::create(['name' => 'Bench Press', 'category_id' => Category::create(['name' => 'Chest'])->id]);

        $workoutId = $this->postJson('/api/workouts', ['date' => '2026-10-11'])->json('data.id');
        $weId = $this->postJson("/api/workouts/$workoutId/exercises", ['exercise_id' => $bench->id])->json('data.id');
        $sets = "/api/workouts/$workoutId/exercises/$weId/sets";

        $this->postJson($sets, ['weight' => 62.5, 'reps' => 9, 'reps_max' => 9])->assertUnprocessable();
        $this->postJson($sets, ['weight' => 62.5, 'reps' => 9, 'reps_max' => 10])
            ->assertCreated()
            ->assertJsonPath('data.reps', 9)
            ->assertJsonPath('data.reps_max', 10);
        $this->postJson($sets, ['weight' => 62.5, 'reps' => 8])
            ->assertCreated()
            ->assertJsonPath('data.reps_max', null);

        $this->getJson('/api/personal-records')
            ->assertOk()
            ->assertJsonPath('data.0.reps', 9)
            ->assertJsonPath('data.0.reps_max', 10);

        $nextId = $this->postJson('/api/workouts', ['date' => '2026-10-12'])->json('data.id');
        $this->postJson("/api/workouts/$nextId/exercises", ['exercise_id' => $bench->id]);

        $this->getJson("/api/workouts/$nextId/previous-sets")
            ->assertOk()
            ->assertJsonPath("data.{$bench->id}.sets.0.reps_max", 10)
            ->assertJsonPath("data.{$bench->id}.sets.1.reps_max", null);
    }

    public function test_record_history_and_previous_sets_use_the_duration_of_timed_sets(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $plank = $this->timedExercise();

        $earlier = Workout::create(['user_id' => $user->id, 'date' => '2026-10-10', 'finished_at' => now()]);
        $we = $earlier->workoutExercises()->create(['exercise_id' => $plank->id]);
        // Logged with weight and reps before the exercise became a timed one
        WorkoutSet::create(['workout_exercise_id' => $we->id, 'weight' => 0, 'reps' => 60]);
        WorkoutSet::create(['workout_exercise_id' => $we->id, 'duration_minutes' => 1]);
        WorkoutSet::create(['workout_exercise_id' => $we->id, 'duration_minutes' => 2.5]);

        $current = Workout::create(['user_id' => $user->id, 'date' => '2026-10-12']);
        $current->workoutExercises()->create(['exercise_id' => $plank->id]);

        $this->getJson('/api/personal-records')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.duration_minutes', 2.5)
            ->assertJsonPath('data.0.weight', null);

        $this->getJson("/api/workouts/{$current->id}/previous-sets")
            ->assertOk()
            ->assertJsonPath("data.{$plank->id}.sets.2.duration_minutes", 2.5)
            ->assertJsonPath("data.{$plank->id}.sets.2.weight", null);

        $this->getJson('/api/workouts?finished=1&include=exercises')
            ->assertOk()
            ->assertJsonPath('data.0.exercises.0.minutes', 3.5)
            ->assertJsonPath('data.0.exercises.0.volume', 0);
    }

    public function test_new_cardio_exercise_defaults_to_minutes_and_the_unit_can_be_chosen(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $cardio = Category::create(['name' => Category::CARDIO]);
        $abs = Category::create(['name' => 'Abs']);

        $this->postJson('/api/exercises', ['name' => 'Hiking', 'category_id' => $cardio->id])
            ->assertCreated()->assertJsonPath('data.unit', 'min');
        $this->postJson('/api/exercises', ['name' => 'Cable Crunch', 'category_id' => $abs->id])
            ->assertCreated()->assertJsonPath('data.unit', 'kg');
        $this->postJson('/api/exercises', ['name' => 'Side Plank', 'category_id' => $abs->id, 'unit' => 'min'])
            ->assertCreated()->assertJsonPath('data.unit', 'min');
        $this->postJson('/api/exercises', ['name' => 'Wrong', 'category_id' => $abs->id, 'unit' => 'hours'])
            ->assertUnprocessable();
    }

    public function test_compound_exercises_list_the_other_muscle_groups_they_work(): void
    {
        $this->seed([CategorySeeder::class, ExerciseSeeder::class]);
        $this->seed([CategorySeeder::class, ExerciseSeeder::class]);

        Sanctum::actingAs(User::factory()->create());
        $exercises = collect($this->getJson('/api/exercises')->assertOk()->json('data'))->keyBy('name');

        $this->assertSame('Triceps', $exercises['Dips']['category']['name']);
        $this->assertSame(['Chest', 'Shoulders'], array_column($exercises['Dips']['secondary_categories'], 'name'));
        $this->assertSame([], $exercises['Dumbbell Curl']['secondary_categories']);

        // Every name on the compound list is an exercise of the catalog
        $this->assertSame(41, $exercises->filter(fn ($exercise) => $exercise['secondary_categories'] !== [])->count());
    }

    public function test_seeders_add_the_cardio_catalog_in_minutes_and_keep_muscle_stats_to_muscles(): void
    {
        // An older catalog row that was created before units existed
        $abs = Category::create(['name' => 'Abs']);
        Exercise::create(['name' => 'Plank', 'category_id' => $abs->id]);

        $this->seed([CategorySeeder::class, ExerciseSeeder::class]);
        $this->seed([CategorySeeder::class, ExerciseSeeder::class]);

        $cardio = Category::where('name', Category::CARDIO)->sole();
        $this->assertSame(10, Exercise::where('category_id', $cardio->id)->count());
        $this->assertSame(0, Exercise::where('category_id', $cardio->id)->where('unit', '!=', 'min')->count());
        $this->assertSame(ExerciseUnit::Minutes, Exercise::where('name', 'Plank')->sole()->unit);
        $this->assertSame(ExerciseUnit::Kilograms, Exercise::where('name', 'Crunch')->sole()->unit);

        Sanctum::actingAs(User::factory()->create());
        $stats = $this->getJson('/api/muscle-stats')->assertOk()->assertJsonCount(9, 'data')->json('data');
        $this->assertNotContains(Category::CARDIO, array_column(array_column($stats, 'category'), 'name'));
    }
}
