<?php

namespace App\Http\Controllers\Api;

use App\Enums\Avatar;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateAvatarRequest;
use App\Services\UserService;

class UserAvatarController extends Controller
{
    public function __construct(protected UserService $service) {}

    public function update(UpdateAvatarRequest $request)
    {
        $user = $request->user();
        $this->authorize('update', $user);

        return $this->service->updateAvatar($user, Avatar::from($request->validated('avatar')));
    }
}
