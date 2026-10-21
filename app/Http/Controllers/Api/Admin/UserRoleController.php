<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserRoleRequest;
use App\Http\Resources\Admin\AdminUserResource;
use App\Models\User;
use App\Services\RoleService;
use App\Support\PermissionCatalog;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class UserRoleController extends Controller
{
    public function __construct(protected RoleService $service) {}

    public function index(Request $request)
    {
        $search = $request->validate(['search' => 'nullable|string|max:100'])['search'] ?? null;

        $users = User::with('roles')
            ->when($search, fn ($query) => $query->where(fn ($query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('username', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->orderBy('name')
            ->paginate(20);

        return AdminUserResource::collection($users);
    }

    public function update(UserRoleRequest $request, User $user)
    {
        // Root keeps root: nothing on the page can take it away
        abort_if($user->hasRole(PermissionCatalog::ROOT), 422, "Root's role cannot be changed.");

        $role = Role::findByName($request->validated('role'), 'web');

        return new AdminUserResource($this->service->moveUser($user, $role)->load('roles'));
    }
}
