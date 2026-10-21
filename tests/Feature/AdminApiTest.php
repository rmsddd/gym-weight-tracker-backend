<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminApiTest extends TestCase
{
    use RefreshDatabase;

    private User $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = User::factory()->create(['name' => 'Root']);
        $this->root->syncRoles(PermissionCatalog::ROOT);
    }

    private function role(string $name): Role
    {
        return Role::findByName($name, 'web');
    }

    public function test_only_root_reaches_the_admin_routes(): void
    {
        $routes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn (RoutingRoute $route) => str_starts_with($route->uri(), 'api/admin'));
        $this->assertCount(8, $routes);

        Sanctum::actingAs(User::factory()->create());

        foreach ($routes as $route) {
            $uri = str_replace(['{role}', '{user}'], [$this->role(PermissionCatalog::DEFAULT_ROLE)->id, $this->root->id], $route->uri());
            $this->json($route->methods()[0], "/{$uri}")
                ->assertForbidden()
                ->assertJsonPath('message', 'Only root can do this.');
        }
    }

    public function test_the_catalog_comes_grouped_with_labels(): void
    {
        Sanctum::actingAs($this->root);

        $this->getJson('/api/admin/permissions')
            ->assertOk()
            ->assertJsonCount(count(PermissionCatalog::GROUPS), 'data')
            ->assertJsonPath('data.0.key', 'workouts')
            ->assertJsonPath('data.0.permissions.0', ['name' => 'workouts.view', 'label' => 'See workouts']);
    }

    public function test_roles_are_listed_without_root_and_sportiv_first(): void
    {
        Role::create(['name' => 'antrenor', 'guard_name' => 'web']);
        User::factory()->count(2)->create();
        Sanctum::actingAs($this->root);

        $this->getJson('/api/admin/roles')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', PermissionCatalog::DEFAULT_ROLE)
            ->assertJsonPath('data.0.is_default', true)
            ->assertJsonPath('data.0.users_count', 2)
            ->assertJsonCount(count(PermissionCatalog::names()), 'data.0.permissions')
            ->assertJsonPath('data.1.name', 'antrenor');
    }

    public function test_a_role_can_be_created_renamed_given_permissions_and_deleted(): void
    {
        Sanctum::actingAs($this->root);

        $id = $this->postJson('/api/admin/roles', ['name' => ' limitat '])
            ->assertCreated()
            ->assertJsonPath('data.name', 'limitat')
            ->assertJsonPath('data.permissions', [])
            ->json('data.id');

        $this->patchJson("/api/admin/roles/{$id}", ['name' => 'restrans'])->assertOk()->assertJsonPath('data.name', 'restrans');

        $this->putJson("/api/admin/roles/{$id}/permissions", ['permissions' => ['workouts.view', 'workouts.create']])
            ->assertOk()
            ->assertJsonPath('data.permissions', ['workouts.view', 'workouts.create']);

        $this->deleteJson("/api/admin/roles/{$id}")->assertNoContent();
        $this->assertFalse(Role::where('id', $id)->exists());
    }

    public function test_role_names_are_checked(): void
    {
        Sanctum::actingAs($this->root);

        $this->postJson('/api/admin/roles', ['name' => 'x'])->assertJsonValidationErrors(['name' => 'at least 2']);
        $this->postJson('/api/admin/roles', ['name' => 'sportiv'])->assertJsonValidationErrors(['name' => 'already exists']);
        $this->postJson('/api/admin/roles', ['name' => 'ROOT'])->assertJsonValidationErrors(['name' => 'reserved']);
        $this->putJson("/api/admin/roles/{$this->role('sportiv')->id}/permissions", ['permissions' => ['made.up']])
            ->assertJsonValidationErrors('permissions.0');
    }

    public function test_sportiv_keeps_its_name_but_its_permissions_can_change(): void
    {
        $sportiv = $this->role(PermissionCatalog::DEFAULT_ROLE);
        Sanctum::actingAs($this->root);

        $this->patchJson("/api/admin/roles/{$sportiv->id}", ['name' => 'altul'])->assertUnprocessable();
        $this->deleteJson("/api/admin/roles/{$sportiv->id}")->assertUnprocessable();
        $this->putJson("/api/admin/roles/{$sportiv->id}/permissions", ['permissions' => []])
            ->assertOk()
            ->assertJsonPath('data.permissions', []);
    }

    public function test_the_root_role_is_out_of_reach(): void
    {
        $root = $this->role(PermissionCatalog::ROOT);
        Sanctum::actingAs($this->root);

        $this->patchJson("/api/admin/roles/{$root->id}", ['name' => 'boss'])->assertNotFound();
        $this->deleteJson("/api/admin/roles/{$root->id}")->assertNotFound();
        $this->putJson("/api/admin/roles/{$root->id}/permissions", ['permissions' => []])->assertNotFound();
        $this->putJson('/api/admin/users/'.User::factory()->create()->id.'/role', ['role' => 'root'])
            ->assertJsonValidationErrors('role');
        $this->putJson("/api/admin/users/{$this->root->id}/role", ['role' => 'sportiv'])->assertUnprocessable();

        $this->assertTrue($this->root->fresh()->hasRole(PermissionCatalog::ROOT));
    }

    public function test_deleting_a_role_moves_its_users_to_sportiv(): void
    {
        $limitat = Role::create(['name' => 'limitat', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->syncRoles($limitat);
        Sanctum::actingAs($this->root);

        $this->deleteJson("/api/admin/roles/{$limitat->id}")->assertNoContent();

        $this->assertSame([PermissionCatalog::DEFAULT_ROLE], $user->fresh()->getRoleNames()->all());
    }

    public function test_moving_a_user_replaces_their_role_and_takes_effect_at_once(): void
    {
        $limitat = Role::create(['name' => 'limitat', 'guard_name' => 'web']);
        $limitat->givePermissionTo('workouts.view');
        $user = User::factory()->create();
        Sanctum::actingAs($this->root);

        $this->putJson("/api/admin/users/{$user->id}/role", ['role' => 'limitat'])
            ->assertOk()
            ->assertJsonPath('data.role', 'limitat');
        $this->assertSame(['limitat'], $user->fresh()->getRoleNames()->all());

        Sanctum::actingAs($user->fresh());
        $this->getJson('/api/workouts')->assertOk();
        $this->getJson('/api/workout-templates')->assertForbidden()->assertJsonPath('code', 'permission_denied');
    }

    public function test_users_can_be_searched_and_show_their_role(): void
    {
        User::factory()->create(['name' => 'Ana Pop', 'username' => 'ana']);
        User::factory()->create(['name' => 'Bogdan', 'username' => 'bogdan', 'email' => 'bogdan@example.com']);
        Sanctum::actingAs($this->root);

        $this->getJson('/api/admin/users?search=ana')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.username', 'ana')
            ->assertJsonPath('data.0.role', PermissionCatalog::DEFAULT_ROLE);

        $this->getJson('/api/admin/users?search=bogdan@example')->assertJsonCount(1, 'data');

        $this->getJson('/api/admin/users')
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.per_page', 20)
            ->assertJsonFragment(['name' => 'Root', 'role' => PermissionCatalog::ROOT]);
    }
}
