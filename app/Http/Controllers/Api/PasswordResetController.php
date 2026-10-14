<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Traits\ApiResponse;
use App\Services\PasswordResetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class PasswordResetController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected PasswordResetService $service
    ) {}

    public function sendLink(ForgotPasswordRequest $request): JsonResponse
    {
        $status = $this->service->sendResetLink($request->validated('email'));

        // Same as the login: it already says whether an account uses an email.
        // The only other status is RESET_THROTTLED: one link per minute per account.
        return match ($status) {
            Password::RESET_LINK_SENT => $this->success(null, 'We emailed you a link to reset your password.'),
            Password::INVALID_USER => throw ValidationException::withMessages(['email' => ['No account uses this email.']]),
            default => throw ValidationException::withMessages(['email' => ['We just sent you a link. Wait a minute before asking for another one.']]),
        };
    }

    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $status = $this->service->reset($request->validated());

        return match ($status) {
            Password::PASSWORD_RESET => $this->success(null, 'Your password was changed. Log in with the new one.'),
            Password::INVALID_USER => throw ValidationException::withMessages(['email' => ['No account uses this email.']]),
            default => throw ValidationException::withMessages(['token' => ['This reset link is invalid or has expired. Ask for a new one.']]),
        };
    }
}
