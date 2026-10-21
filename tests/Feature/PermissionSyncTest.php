<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PermissionSyncTest extends TestCase
{
    use RefreshDatabase;

    private function sportiv(): Role
    {
        return Role::findByName(PermissionCatalog::DEFAULT_ROLE, 'web');
    }

    public function test_the_migration_leaves_the_catalog_and_both_roles_in_place(): void
    {
        $this->assertEqualsCanonicalizing(PermissionCatalog::names(), Permission::pluck('name')->all());
        $this->assertEqualsCanonicalizing(PermissionCatalog::names(), $this->sportiv()->permissions->pluck('name')->all());
        $this->assertCount(0, Role::findByName(PermissionCatalog::ROOT, 'web')->permissions);
    }

    public function test_running_it_again_changes_nothing(): void
    {
        $this->artisan('permissions:sync')->assertSuccessful();

        $this->assertSame(count(PermissionCatalog::names()), Permission::count());
        $this->assertSame(2, Role::count());
        $this->assertCount(count(PermissionCatalog::names()), $this->sportiv()->permissions);
    }

    public function test_it_deletes_permissions_no_longer_in_the_catalog(): void
    {
        Permission::create(['name' => 'old.thing', 'guard_name' => 'web']);

        $this->artisan('permissions:sync');

        $this->assertFalse(Permission::where('name', 'old.thing')->exists());
    }

    public function test_a_new_permission_goes_to_sportiv_only_and_unticked_ones_stay_unticked(): void
    {
        $custom = Role::create(['name' => 'limitat', 'guard_name' => 'web']);
        $this->sportiv()->revokePermissionTo('workouts.delete');
        // Deleting it from the database makes the next sync see it as a newly added permission
        Permission::findByName('stats.view', 'web')->delete();

        $this->artisan('permissions:sync');

        $sportiv = $this->sportiv()->fresh();
        $this->assertTrue($sportiv->hasPermissionTo('stats.view'));
        $this->assertFalse($sportiv->hasPermissionTo('workouts.delete'));
        $this->assertCount(0, $custom->fresh()->permissions);
    }

    public function test_users_without_a_role_get_sportiv(): void
    {
        $user = User::factory()->create();
        $user->syncRoles([]);

        $this->artisan('permissions:sync');

        $this->assertTrue($user->fresh()->hasRole(PermissionCatalog::DEFAULT_ROLE));
    }
}
