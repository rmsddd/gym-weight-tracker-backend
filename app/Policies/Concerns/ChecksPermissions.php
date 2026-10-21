<?php

namespace App\Policies\Concerns;

use App\Exceptions\PermissionDenied;
use App\Models\User;

trait ChecksPermissions
{
    /**
     * The role's permission comes first and fails loudly; the policy's own rule (who owns the record,
     * what state it is in) follows it: `return $this->permitted($user, 'x.y') && <rule>;`
     */
    protected function permitted(User $user, string $permission): bool
    {
        if (! $user->can($permission)) {
            throw new PermissionDenied($permission);
        }

        return true;
    }
}
