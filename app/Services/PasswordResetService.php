<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class PasswordResetService
{
    /** Emails a reset link; returns one of the Password::* status constants. */
    public function sendResetLink(string $email): string
    {
        return Password::sendResetLink(['email' => $email]);
    }

    /** Sets the new password if the token is valid; returns one of the Password::* status constants. */
    public function reset(array $data): string
    {
        return Password::reset($data, function (User $user, string $password) {
            $user->forceFill([
                'password' => Hash::make($password),
                'remember_token' => Str::random(60),
            ]);

            // Opening the link proved the address belongs to them
            if (! $user->hasVerifiedEmail()) {
                $user->email_verified_at = now();
            }

            $user->save();

            // Log out everywhere: whoever knew the old password loses access
            $user->tokens()->delete();

            event(new PasswordReset($user));
        });
    }
}
