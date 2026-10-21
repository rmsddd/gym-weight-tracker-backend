<?php

namespace Tests\Feature;

use App\Models\PendingRegistration;
use App\Models\User;
use App\Notifications\ConfirmRegistration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function register(string $email = 'lifter@example.com', string $password = 'password123')
    {
        return $this->postJson('/api/register', [
            'name' => 'Lifter',
            'username' => 'lifter',
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $password,
        ]);
    }

    private function verificationUrl(PendingRegistration $registration, ?string $email = null, int $minutes = 60): string
    {
        return URL::temporarySignedRoute('verification.verify', now()->addMinutes($minutes), [
            'id' => $registration->id,
            'hash' => sha1($email ?? $registration->email),
        ]);
    }

    public function test_registering_emails_a_link_and_creates_no_account(): void
    {
        Notification::fake();

        $this->register()
            ->assertAccepted()
            ->assertJsonPath('data.email', 'lifter@example.com')
            ->assertJsonMissingPath('data.accessToken')
            ->assertCookieMissing('refreshToken');

        $this->assertDatabaseMissing('users', ['email' => 'lifter@example.com']);
        $this->assertDatabaseHas('pending_registrations', ['email' => 'lifter@example.com']);

        Notification::assertSentOnDemand(
            ConfirmRegistration::class,
            fn ($notification, $channels, AnonymousNotifiable $notifiable) => $notifiable->routes['mail'] === 'lifter@example.com'
        );
    }

    public function test_cannot_log_in_before_opening_the_link(): void
    {
        Notification::fake();
        $this->register();

        $this->withHeader('Referer', 'http://localhost:5173')->postJson('/api/login', ['email' => 'lifter@example.com', 'password' => 'password123'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.email.0', 'No account uses this email.');
    }

    public function test_the_link_creates_a_confirmed_account_that_can_log_in(): void
    {
        config(['app.frontend_url' => 'https://gym.test']);
        Notification::fake();
        $this->register();
        $registration = PendingRegistration::first();

        // Not logged in: the link is usually opened on a phone
        $this->get($this->verificationUrl($registration))
            ->assertRedirect('https://gym.test/verify-email?status=verified');

        $user = User::where('email', 'lifter@example.com')->first();
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Hash::check('password123', $user->password));
        $this->assertDatabaseMissing('pending_registrations', ['email' => 'lifter@example.com']);

        // Logging in sends nothing
        $this->withHeader('Referer', 'http://localhost:5173')->postJson('/api/login', ['email' => 'lifter@example.com', 'password' => 'password123'])->assertOk();
        Notification::assertSentOnDemandTimes(ConfirmRegistration::class, 1);
    }

    public function test_an_expired_tampered_or_wrong_link_creates_nothing(): void
    {
        config(['app.frontend_url' => 'https://gym.test']);
        Notification::fake();
        $this->register();
        $registration = PendingRegistration::first();

        $this->get($this->verificationUrl($registration, minutes: -1))
            ->assertRedirect('https://gym.test/verify-email?status=expired');

        $this->get($this->verificationUrl($registration).'x')
            ->assertRedirect('https://gym.test/verify-email?status=expired');

        $this->get($this->verificationUrl($registration, email: 'someone-else@example.com'))
            ->assertRedirect('https://gym.test/verify-email?status=invalid');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_signing_up_again_replaces_the_pending_one(): void
    {
        Notification::fake();

        $this->register(password: 'first-password')->assertAccepted();
        $this->register(password: 'second-password')->assertAccepted();

        $this->assertDatabaseCount('pending_registrations', 1);
        $this->assertTrue(Hash::check('second-password', PendingRegistration::first()->password));
    }

    public function test_nothing_is_kept_when_the_email_cannot_be_sent(): void
    {
        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => 'unreachable.invalid', 'mail.mailers.smtp.port' => 1]);

        $this->register()->assertStatus(503);

        $this->assertDatabaseCount('pending_registrations', 0);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_the_email_link_points_at_the_api_route(): void
    {
        Notification::fake();
        $this->register();

        Notification::assertSentOnDemand(ConfirmRegistration::class, function (ConfirmRegistration $notification) {
            $mail = $notification->toMail(new AnonymousNotifiable);

            return $mail->subject === 'Confirm your email'
                && str_contains($mail->actionUrl, "/api/email/verify/{$notification->registration->id}/")
                && str_contains($mail->actionUrl, 'signature=');
        });
    }

    public function test_an_existing_account_is_not_asked_to_confirm(): void
    {
        $user = User::factory()->create(['email' => 'lifter@example.com', 'password' => 'password123']);

        $token = $this->withHeader('Referer', 'http://localhost:5173')->postJson('/api/login', ['email' => 'lifter@example.com', 'password' => 'password123'])
            ->assertOk()
            ->json('data.accessToken');

        $this->withToken($token)->getJson('/api/workouts')->assertOk();
    }

    public function test_register_rejects_a_taken_or_malformed_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->register('taken@example.com')
            ->assertUnprocessable()
            ->assertJsonPath('errors.email.0', 'An account already uses this email.');

        $this->register('lifter@@example')
            ->assertUnprocessable()
            ->assertJsonPath('errors.email.0', 'This email address does not exist. Check it for typos.');
    }
}
