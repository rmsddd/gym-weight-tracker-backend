<?php

namespace App\Services;

use App\Enums\Avatar;
use App\Models\User;

class UserService
{
    public function updateAvatar(User $user, Avatar $avatar): User
    {
        $user->update(['avatar' => $avatar->value]);
        return $user->fresh();
    }

    public function updateName(User $user, string $name): User
    {
        $user->update(['name' => $name]);
        return $user->fresh();
    }

    public function setUsername(User $user, string $username): User
    {
        $user->forceFill(['username' => $username])->save();
        return $user->fresh();
    }
}
