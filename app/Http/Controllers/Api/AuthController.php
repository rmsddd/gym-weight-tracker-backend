<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Traits\ApiResponse;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected AuthService $service
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => Hash::make($request->validated('password')),
        ]);

        $tokens = $this->service->generateTokens($user);

        return $this->sendResponseWithTokens(
            $tokens,
            ['user' => $user],
            201
        );
    }

    public function login(LoginRequest $request): JsonResponse
    {
        if (!Auth::attempt($request->validated())) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user = Auth::user();

        $user->tokens()->delete();

        $tokens = $this->service->generateTokens($user);

        return $this->sendResponseWithTokens(
            $tokens,
            ['user' => $user]
        );
    }

    public function refresh(Request $request): JsonResponse
    {
        $refreshToken = $request->cookie('refreshToken');

        if (!$refreshToken) {
            return response()->json([
                'message' => 'Refresh token missing.'
            ], 401);
        }

        $tokens = $this->service->refreshTokens($refreshToken);

        if (!$tokens) {
            return response()->json([
                'message' => 'Refresh token invalid or expired.'
            ], 401);
        }

        return $this->sendResponseWithTokens($tokens);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        // Delete all of the user's tokens
        $user->tokens()->delete();

        return response()
            ->json([
                'success' => true,
                'message' => 'Logged out successfully.'
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
