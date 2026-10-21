<?php

namespace App\Http\Resources\Admin;

use App\Support\PermissionCatalog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'is_default' => $this->name === PermissionCatalog::DEFAULT_ROLE,
            // A role just created or changed is answered without a count; the list carries it
            'users_count' => $this->users_count,
            'permissions' => $this->permissions->pluck('name')->values(),
        ];
    }
}
