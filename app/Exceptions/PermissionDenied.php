<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

/**
 * The user's role lacks a permission. Told apart from the other 403s (not the owner, not friends,
 * a completed workout) by its code, so the app can refresh what the user may do.
 */
class PermissionDenied extends Exception
{
    public function __construct(public readonly string $permission)
    {
        parent::__construct("Missing the {$permission} permission.");
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => "You're not allowed to do this.",
            'code' => 'permission_denied',
            'permission' => $this->permission,
        ], 403);
    }
}
