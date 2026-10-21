<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

// Categories are a read-only shared catalog: there is nothing to grant for writing them.
class CategoryPolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return $this->permitted($user, 'categories.view');
    }

    public function view(User $user, Category $category): bool
    {
        return $this->permitted($user, 'categories.view');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Category $category): bool
    {
        return false;
    }

    public function delete(User $user, Category $category): bool
    {
        return false;
    }
}
