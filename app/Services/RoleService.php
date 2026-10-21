<?php

namespace App\Services;

use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class RoleService
{
    /** Every role the admin page manages: all but root, sportiv first. */
    public function all(): Collection
    {
        // Counted by hand: Spatie's users relation looks the model up from the default guard, which
        // is sanctum (it has no provider) during an API request
        $usersCount = DB::table(config('permission.table_names.model_has_roles'))
            ->selectRaw('count(*)')
            ->whereColumn('role_id', 'roles.id')
            ->where('model_type', (new User)->getMorphClass());

        return Role::where('name', '!=', PermissionCatalog::ROOT)
            ->select('roles.*')
            ->selectSub($usersCount, 'users_count')
            ->with('permissions')
            ->orderByRaw('name = ? desc', [PermissionCatalog::DEFAULT_ROLE])
            ->orderBy('name')
            ->get();
    }

    /** A new role starts with nothing ticked. */
    public function create(string $name): Role
    {
        return Role::create(['name' => $name, 'guard_name' => 'web']);
    }

    public function rename(Role $role, string $name): Role
    {
        $role->update(['name' => $name]);

        return $role;
    }

    /** Its users fall back to sportiv, so nobody is ever left without a role. */
    public function delete(Role $role): void
    {
        DB::transaction(function () use ($role) {
            User::role($role->name)->get()->each->syncRoles(PermissionCatalog::DEFAULT_ROLE);
            $role->delete();
        });
    }

    /** @param list<string> $permissions */
    public function setPermissions(Role $role, array $permissions): Role
    {
        return $role->syncPermissions($permissions);
    }

    public function moveUser(User $user, Role $role): User
    {
        return $user->syncRoles($role);
    }
}
