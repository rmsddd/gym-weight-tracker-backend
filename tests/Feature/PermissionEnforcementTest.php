<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Exercise;
use App\Models\Friendship;
use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutTemplate;
use App\Support\PermissionCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\Support\GuardedRoutes;
use Tests\TestCase;

class PermissionEnforcementTest extends TestCase
{
    use RefreshDatabase;

    private User $me;

    /** @var array<string, int|string> what each {placeholder} in GuardedRoutes stands for */
    private array $fixtures;

    protected function setUp(): void
    {
        parent::setUp();

        $this->me = User::factory()->create(['username' => 'me']);
        $friend = User::factory()->create(['username' => 'buddy']);
        $asker = User::factory()->create(['username' => 'asker']);
        $stranger = User::factory()->create(['username' => 'stranger']);

        Friendship::create(['requester_id' => $this->me->id, 'addressee_id' => $friend->id, 'status' => 'accepted']);
        $incoming = Friendship::create(['requester_id' => $asker->id, 'addressee_id' => $this->me->id, 'status' => 'pending']);

        $category = Category::create(['name' => 'Chest']);
        $exercise = Exercise::create(['name' => 'Bench Press', 'category_id' => $category->id, 'user_id' => $this->me->id]);
        $workout = Workout::create(['user_id' => $this->me->id, 'name' => 'Push Day', 'date' => '2026-10-21', 'started_at' => now()->subHour()]);
        $workoutExercise = $workout->workoutExercises()->create(['exercise_id' => $exercise->id]);
        $workoutSet = $workoutExercise->sets()->create(['weight' => 80, 'reps' => 8]);
        $template = WorkoutTemplate::create(['user_id' => $this->me->id, 'name' => 'Push']);

        $this->fixtures = [
            'workout' => $workout->id,
            'workoutExercise' => $workoutExercise->id,
            'workoutSet' => $workoutSet->id,
            'exercise' => $exercise->id,
            'category' => $category->id,
            'template' => $template->id,
            'user' => $friend->username,
            'friendship' => $incoming->id,
            'stranger' => $stranger->id,
        ];

        Sanctum::actingAs($this->me);
    }

    public static function guardedRoutes(): array
    {
        return collect(GuardedRoutes::all())
            ->mapWithKeys(fn (array $guard, string $route) => [$route => [$route, ...$guard]])
            ->all();
    }

    #[DataProvider('guardedRoutes')]
    public function test_the_route_needs_its_permission(string $route, string $permission, array $body): void
    {
        $this->setPermission($permission, false);
        $this->hit($route, $body)
            ->assertForbidden()
            ->assertJson(['code' => 'permission_denied', 'permission' => $permission]);

        $this->setPermission($permission, true);
        $response = $this->hit($route, $body);
        $this->assertLessThan(400, $response->status(), "{$route} with {$permission}: ".$response->getContent());
    }

    public function test_root_passes_a_permission_its_role_never_had(): void
    {
        $this->me->syncRoles(PermissionCatalog::ROOT);
        $this->setPermission('workouts.delete', false);

        $this->deleteJson("/api/workouts/{$this->fixtures['workout']}")->assertNoContent();
    }

    public function test_someone_elses_workout_is_a_plain_403_even_for_root(): void
    {
        $this->me->syncRoles(PermissionCatalog::ROOT);
        $theirs = Workout::create(['user_id' => User::factory()->create()->id, 'name' => 'Legs', 'date' => '2026-10-21']);

        $response = $this->deleteJson("/api/workouts/{$theirs->id}")->assertForbidden();
        $this->assertNull($response->json('code'));
    }

    public function test_a_stranger_page_is_still_a_plain_403(): void
    {
        $response = $this->getJson('/api/friends/stranger')->assertForbidden();
        $this->assertNull($response->json('code'));
    }

    public function test_resuming_a_completed_workout_is_still_a_plain_403(): void
    {
        Workout::find($this->fixtures['workout'])->update(['finished_at' => now(), 'completed_at' => now()]);

        $response = $this->postJson("/api/workouts/{$this->fixtures['workout']}/resume")->assertForbidden();
        $this->assertNull($response->json('code'));
    }

    private function setPermission(string $permission, bool $granted): void
    {
        $role = Role::findByName(PermissionCatalog::DEFAULT_ROLE, 'web');
        $granted ? $role->givePermissionTo($permission) : $role->revokePermissionTo($permission);
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->me->unsetRelation('roles')->unsetRelation('permissions');
    }

    /** Sends "METHOD uri" with its placeholders filled in, in the URI and in the body. */
    private function hit(string $route, array $body): TestResponse
    {
        [$method, $uri] = explode(' ', $route);
        $fill = fn ($value) => is_string($value) && preg_match('/^\{(\w+)\}$/', $value, $m) ? $this->fixtures[$m[1]] : $value;
        $uri = preg_replace_callback('/\{(\w+)\}/', fn ($m) => $this->fixtures[$m[1]], $uri);
        $body = array_map(fn ($value) => is_array($value) ? array_map($fill, $value) : $fill($value), $body);

        return $method === 'GET'
            ? $this->getJson('/'.$uri.($body ? '?'.http_build_query($body) : ''))
            : $this->json($method, '/'.$uri, $body);
    }
}
