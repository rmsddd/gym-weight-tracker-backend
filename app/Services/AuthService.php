<?php

namespace App\Services;

use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

class AuthService
{
    private int $expirationMinutes = 10;

    public function generateTokens(User $user): array
    {
        // Access token
        $accessToken = $user->createToken(
            'access-token',
            ['access'],
            now()->addMinutes($this->expirationMinutes)
        );

        // Refresh token
        $refreshToken = $user->createToken(
            'refresh-token',
            ['refresh'],
            now()->addMinutes($this->expirationMinutes)
        );

        return [
            'accessToken' => $accessToken->plainTextToken,
            'refreshToken' => $refreshToken->plainTextToken,
        ];
    }

    public function refreshTokens(string $refreshToken): ?array
    {
        $token = PersonalAccessToken::findToken($refreshToken);

        if (!$token) {
            return null;
        }


        if (!$token->can('refresh')) {
            return null;
        }

        if ($token->expires_at && $token->expires_at->isPast()) {
            $token->delete();

            return null;
        }

        $user = $token->tokenable;

        $token->delete();


        return $this->generateTokens($user);
    }
}
