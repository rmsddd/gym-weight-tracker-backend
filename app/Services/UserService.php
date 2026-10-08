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
}
