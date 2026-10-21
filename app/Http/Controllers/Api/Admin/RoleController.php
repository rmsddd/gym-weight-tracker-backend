<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RoleNameRequest;
use App\Http\Requests\Admin\RolePermissionsRequest;
use App\Http\Resources\Admin\RoleResource;
use App\Services\RoleService;
use App\Support\PermissionCatalog;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function __construct(protected RoleService $service) {}

    /** The catalog the role checkboxes are drawn from, grouped by area. */
    public function permissions(): JsonResponse
    {
        return response()->json(['data' => collect(PermissionCatalog::GROUPS)->map(fn (array $group, string $key) => [
            'key' => $key,
            'label' => $group['label'],
            'permissions' => collect($group['permissions'])
                ->map(fn (string $label, string $name) => ['name' => $name, 'label' => $label])
                ->values(),
        ])->values()]);
    }

    public function index()
    {
        return RoleResource::collection($this->service->all());
    }

    public function store(RoleNameRequest $request)
    {
        $role = $this->service->create($request->validated('name'));

        return (new RoleResource($role->load('permissions')))->response()->setStatusCode(201);
    }

    public function update(RoleNameRequest $request, Role $role)
    {
        $this->ensureManageable($role);
        abort_if($role->name === PermissionCatalog::DEFAULT_ROLE, 422, 'The default role cannot be renamed.');

        return new RoleResource($this->service->rename($role, $request->validated('name'))->load('permissions'));
    }

    public function destroy(Role $role)
    {
        $this->ensureManageable($role);
        abort_if($role->name === PermissionCatalog::DEFAULT_ROLE, 422, 'The default role cannot be deleted.');

        $this->service->delete($role);

        return response()->json(null, 204);
    }

    public function updatePermissions(RolePermissionsRequest $request, Role $role)
    {
        $this->ensureManageable($role);

        return new RoleResource($this->service->setPermissions($role, $request->validated('permissions'))->load('permissions'));
    }

    // Root is not a role the page manages: it does not exist as far as these routes go
    private function ensureManageable(Role $role): void
    {
        abort_if($role->name === PermissionCatalog::ROOT, 404);
    }
}
