<?php

namespace App\Services;

use App\Models\PendingRegistration;
use App\Models\User;
use App\Notifications\ConfirmRegistration;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Throwable;

class RegistrationService
{
    /**
     * Keeps the sign up aside and emails the confirmation link; no account exists until it is
     * opened. Signing up again with the same email replaces the pending one and sends a new link.
     * Returns false when the email could not be sent, and then nothing is kept.
     */
    public function start(string $name, string $email, string $password): bool
    {
        // Links last an hour; whatever was never confirmed is just forgotten
        PendingRegistration::where('updated_at', '<', now()->subDay())->delete();

        $registration = PendingRegistration::updateOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => Hash::make($password)]
        );

        try {
            Notification::route('mail', $email)->notify(new ConfirmRegistration($registration));

            return true;
        } catch (Throwable $e) {
            report($e);
            $registration->delete();

            return false;
        }
    }

    /** Creates the account from the pending sign up if the link's hash matches its email. */
    public function confirm(int $id, string $hash): bool
    {
        $registration = PendingRegistration::find($id);

        if (! $registration || ! hash_equals(sha1($registration->email), $hash)) {
            return false;
        }

        DB::transaction(function () use ($registration) {
            // Already created through an earlier link: nothing more to do
            if (! User::where('email', $registration->email)->exists()) {
                // The password was hashed when the sign up was kept; the hashed cast leaves it as is
                $user = User::forceCreate([
                    'name' => $registration->name,
                    'email' => $registration->email,
                    'password' => $registration->password,
                    'email_verified_at' => now(),
                ]);

                event(new Registered($user));
            }

            $registration->delete();
        });

        return true;
    }
}
