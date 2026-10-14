<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function verificationUrl(User $user, ?string $email = null, int $minutes = 60): string
    {
        return URL::temporarySignedRoute('verification.verify', now()->addMinutes($minutes), [
            'id' => $user->id,
            'hash' => sha1($email ?? $user->email),
        ]);
    }

    public function test_registering_sends_a_confirmation_email(): void
    {
        Notification::fake();

        $this->postJson('/api/register', [
            'name' => 'Lifter',
            'email' => 'lifter@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertCreated()->assertJsonPath('data.user.email_verified_at', null);

        Notification::assertSentTo(User::where('email', 'lifter@example.com')->first(), VerifyEmail::class);
    }

    public function test_registering_still_works_when_the_email_cannot_be_sent(): void
    {
        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => 'unreachable.invalid', 'mail.mailers.smtp.port' => 1]);

        $this->postJson('/api/register', [
            'name' => 'Lifter',
            'email' => 'lifter@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertCreated();

        $this->assertDatabaseHas('users', ['email' => 'lifter@example.com']);
    }

    public function test_an_unconfirmed_account_only_reaches_the_confirmation_screen_routes(): void
    {
        Sanctum::actingAs(User::factory()->unverified()->create());

        $this->getJson('/api/user')->assertOk();
        $this->getJson('/api/workouts')
            ->assertForbidden()
            ->assertJsonPath('message', 'Your email address is not verified.');
    }

    public function test_the_link_confirms_the_email_and_returns_to_the_vue_app(): void
    {
        config(['app.frontend_url' => 'https://gym.test']);
        $user = User::factory()->unverified()->create();

        // Not logged in: the link is usually opened on a phone
        $this->get($this->verificationUrl($user))->assertRedirect('https://gym.test/verify-email?status=verified');

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_an_expired_tampered_or_wrong_link_does_not_confirm(): void
    {
        config(['app.frontend_url' => 'https://gym.test']);
        $user = User::factory()->unverified()->create();

        $this->get($this->verificationUrl($user, minutes: -1))
            ->assertRedirect('https://gym.test/verify-email?status=expired');

        $this->get($this->verificationUrl($user).'x')
            ->assertRedirect('https://gym.test/verify-email?status=expired');

        $this->get($this->verificationUrl($user, email: 'someone-else@example.com'))
            ->assertRedirect('https://gym.test/verify-email?status=invalid');

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_the_confirmation_email_can_be_sent_again_once_a_minute(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/email/verification-notification')
            ->assertOk()
            ->assertJsonPath('message', 'We sent you a new confirmation link.');
        $this->postJson('/api/email/verification-notification')->assertTooManyRequests();

        Notification::assertSentToTimes($user, VerifyEmail::class, 1);
    }

    public function test_the_email_link_points_at_the_api_route(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/email/verification-notification')->assertOk();

        Notification::assertSentTo($user, VerifyEmail::class, function (VerifyEmail $notification) use ($user) {
            $mail = $notification->toMail($user);

            return $mail->subject === 'Confirm your email'
                && str_contains($mail->actionUrl, "/api/email/verify/{$user->id}/")
                && str_contains($mail->actionUrl, 'signature=');
        });
    }

    public function test_register_rejects_a_malformed_email(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Lifter',
            'email' => 'lifter@@example',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertUnprocessable()->assertJsonPath('errors.email.0', 'This email address does not exist. Check it for typos.');
    }
}
