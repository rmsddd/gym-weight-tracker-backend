<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Throwable;

class EmailVerificationService
{
    /**
     * Sends the confirmation email. A mail provider outage must not break registration:
     * the account still gets created and the user can ask for another link.
     */
    public function sendLink(User $user): bool
    {
        try {
            $user->sendEmailVerificationNotification();

            return true;
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }

    /** Marks the email as verified if the link's hash matches the user's current address. */
    public function verify(User $user, string $hash): bool
    {
        if (! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            return false;
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        return true;
    }
}
