<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateNameRequest;
use App\Http\Requests\UpdateUsernameRequest;
use App\Services\UserService;

class UserProfileController extends Controller
{
    public function __construct(protected UserService $service) {}

    public function updateName(UpdateNameRequest $request)
    {
        $user = $request->user();
        $this->authorize('update', $user);

        return $this->service->updateName($user, $request->validated('name'));
    }

    /** Only for an account that has none yet: once chosen, a username never changes. */
    public function setUsername(UpdateUsernameRequest $request)
    {
        $user = $request->user();
        $this->authorize('setUsername', $user);

        return $this->service->setUsername($user, $request->validated('username'));
    }
}
