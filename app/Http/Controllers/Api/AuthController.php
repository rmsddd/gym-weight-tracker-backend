<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Traits\ApiResponse;
use App\Models\User;
use App\Services\AuthService;
use App\Services\RegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected AuthService $service,
        protected RegistrationService $registration
    ) {}

    /** Only emails the confirmation link: the account is created when the link is opened. */
    public function register(RegisterRequest $request): JsonResponse
    {
        $email = $request->validated('email');

        $sent = $this->registration->start(
            $request->validated('name'),
            $request->validated('username'),
            $email,
            $request->validated('password')
        );

        if (! $sent) {
            return $this->error('We could not send the confirmation email right now. Try again in a few minutes.', 503);
        }

        return $this->success(
            ['email' => $email],
            'We sent you a confirmation link. Open it to finish creating your account.',
            202
        );
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        if (! Auth::attempt($credentials)) {
            // Say which field is wrong; registering already reveals whether an email is taken
            throw ValidationException::withMessages(
                User::where('email', $credentials['email'])->exists()
                    ? ['password' => ['Incorrect password.']]
                    : ['email' => ['No account uses this email.']]
            );
        }

        $user = Auth::user();

        // A session left behind by another account (one deleted while logged in) still holds that
        // account's password hash; Sanctum would compare it with this user's and reject the next request
        $request->session()->forget('password_hash_'.Auth::getDefaultDriver());

        $user->tokens()->delete();

        $tokens = $this->service->generateTokens($user);

        return $this->sendResponseWithTokens(
            $tokens,
            ['user' => $user->toCurrentUserArray()]
        );
    }

    public function refresh(Request $request): JsonResponse
    {
        $refreshToken = $request->cookie('refreshToken');

        if (! $refreshToken) {
            return response()->json([
                'message' => 'Refresh token missing.',
            ], 401);
        }

        $tokens = $this->service->refreshTokens($refreshToken);

        if (! $tokens) {
            return response()->json([
                'message' => 'Refresh token invalid or expired.',
            ], 401);
        }

        return $this->sendResponseWithTokens($tokens);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        // Delete all of the user's tokens
        $user->tokens()->delete();

        // The Vue app is logged in through the session cookie: end the session too, or the next
        // request is still authenticated by it
        if ($request->hasSession()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()
            ->json([
                'success' => true,
                'message' => 'Logged out successfully.',
            ])
            ->withoutCookie('refreshToken');
    }

    private function sendResponseWithTokens(
        array $tokens,
        array $body = [],
        int $status = 200
    ): JsonResponse {
        $rtExpireTime = 10;

        $cookie = cookie(
            'refreshToken',
            $tokens['refreshToken'],
            $rtExpireTime,
            '/',
            null,
            app()->environment('production'),
            true,
            false,
            'lax'
        );

        return $this->success(
            array_merge($body, [
                'accessToken' => $tokens['accessToken'],
            ]),
            'Login successful.',
            $status
        )->withCookie($cookie);
    }
}
