<?php

namespace Tests\Feature;

use App\Models\PendingRegistration;
use App\Models\User;
use App\Models\Workout;
use App\Services\RegistrationService;
use App\Support\PermissionCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolesTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_user_is_a_sportiv(): void
    {
        $user = User::factory()->create();

        $this->assertSame([PermissionCatalog::DEFAULT_ROLE], $user->getRoleNames()->all());
    }

    public function test_confirming_a_sign_up_makes_a_sportiv(): void
    {
        $pending = PendingRegistration::create([
            'name' => 'Lifter',
            'username' => 'lifter',
            'email' => 'lifter@example.com',
            'password' => Hash::make('password123'),
        ]);

        app(RegistrationService::class)->confirm($pending->id, sha1('lifter@example.com'));

        $this->assertTrue(User::where('email', 'lifter@example.com')->first()->hasRole(PermissionCatalog::DEFAULT_ROLE));
    }

    public function test_root_passes_permissions_it_has_no_grant_for(): void
    {
        $root = User::factory()->create();
        $root->syncRoles(PermissionCatalog::ROOT);

        $this->assertCount(0, Role::findByName(PermissionCatalog::ROOT, 'web')->permissions);
        $this->assertTrue($root->can('workouts.delete'));
    }

    public function test_root_still_cannot_touch_someone_elses_workout(): void
    {
        $root = User::factory()->create();
        $root->syncRoles(PermissionCatalog::ROOT);
        $workout = Workout::create(['user_id' => User::factory()->create()->id, 'name' => 'Push Day', 'date' => '2026-10-21']);

        $this->assertFalse($root->can('delete', $workout));
    }

    public function test_the_app_learns_the_role_and_permissions_of_the_user(): void
    {
        Role::findByName(PermissionCatalog::DEFAULT_ROLE, 'web')->revokePermissionTo('workouts.delete');
        Sanctum::actingAs(User::factory()->create());

        $response = $this->getJson('/api/user')->assertOk()->assertJsonPath('role', PermissionCatalog::DEFAULT_ROLE);

        $this->assertNotContains('workouts.delete', $response->json('permissions'));
        $this->assertContains('workouts.view', $response->json('permissions'));
        $this->assertArrayNotHasKey('roles', $response->json());
    }

    public function test_root_is_told_it_may_do_everything(): void
    {
        $root = User::factory()->create();
        $root->syncRoles(PermissionCatalog::ROOT);
        Sanctum::actingAs($root);

        $this->getJson('/api/user')
            ->assertJsonPath('role', PermissionCatalog::ROOT)
            ->assertJsonPath('permissions', PermissionCatalog::names());
    }

    public function test_updating_the_profile_keeps_the_role_and_permissions_in_the_answer(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->patchJson('/api/user/name', ['name' => 'New Name'])
            ->assertJsonPath('name', 'New Name')
            ->assertJsonPath('role', PermissionCatalog::DEFAULT_ROLE)
            ->assertJsonCount(count(PermissionCatalog::names()), 'permissions');
    }

    public function test_nobody_can_sign_up_or_choose_the_username_root(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Sneaky',
            'username' => 'ROOT',
            'email' => 'sneaky@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertJsonValidationErrors(['username' => 'This username is taken.']);

        Sanctum::actingAs(User::factory()->create(['username' => null]));
        $this->patchJson('/api/user/username', ['username' => 'root'])
            ->assertJsonValidationErrors(['username' => 'This username is taken.']);
    }

    public function test_a_sportiv_without_a_permission_is_refused_it(): void
    {
        Role::findByName(PermissionCatalog::DEFAULT_ROLE, 'web')->revokePermissionTo('workouts.delete');

        $this->assertFalse(User::factory()->create()->can('workouts.delete'));
    }
}
