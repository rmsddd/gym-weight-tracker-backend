<?php

namespace Tests\Feature;

use App\Models\PendingRegistration;
use App\Models\User;
use App\Models\Workout;
use App\Services\RegistrationService;
use App\Support\PermissionCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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

    public function test_a_sportiv_without_a_permission_is_refused_it(): void
    {
        Role::findByName(PermissionCatalog::DEFAULT_ROLE, 'web')->revokePermissionTo('workouts.delete');

        $this->assertFalse(User::factory()->create()->can('workouts.delete'));
    }
}
