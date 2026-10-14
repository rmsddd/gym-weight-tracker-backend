<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_emails_a_reset_link_that_opens_the_vue_page(): void
    {
        Notification::fake();
        config(['app.frontend_url' => 'https://gym.test']);
        $user = User::factory()->create(['email' => 'lifter@example.com']);

        $this->postJson('/api/forgot-password', ['email' => 'lifter@example.com'])
            ->assertOk()
            ->assertJsonPath('message', 'We emailed you a link to reset your password.');

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
            $mail = $notification->toMail($user);

            return str_starts_with($mail->actionUrl, 'https://gym.test/reset-password?token=')
                && str_contains($mail->actionUrl, 'email=lifter%40example.com');
        });
    }

    public function test_it_says_when_no_account_uses_the_email(): void
    {
        Notification::fake();

        $this->postJson('/api/forgot-password', ['email' => 'nobody@example.com'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.email.0', 'No account uses this email.');

        Notification::assertNothingSent();
    }

    public function test_a_valid_token_changes_the_password_and_logs_out_everywhere(): void
    {
        $user = User::factory()->unverified()->create(['email' => 'lifter@example.com']);
        $user->createToken('access-token', ['access']);
        $token = Password::createToken($user);

        $this->postJson('/api/reset-password', [
            'token' => $token,
            'email' => 'lifter@example.com',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertOk();

        $user->refresh();
        $this->assertTrue(Hash::check('new-password', $user->password));
        $this->assertSame(0, $user->tokens()->count());
        // Opening the emailed link proved the address is theirs
        $this->assertTrue($user->hasVerifiedEmail());
    }

    public function test_a_wrong_or_used_token_is_rejected(): void
    {
        $user = User::factory()->create(['email' => 'lifter@example.com']);
        $token = Password::createToken($user);
        $payload = [
            'email' => 'lifter@example.com',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ];

        $this->postJson('/api/reset-password', [...$payload, 'token' => 'not-the-token'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.token.0', 'This reset link is invalid or has expired. Ask for a new one.');

        $this->postJson('/api/reset-password', [...$payload, 'token' => $token])->assertOk();

        $this->postJson('/api/reset-password', [...$payload, 'token' => $token])
            ->assertUnprocessable()
            ->assertJsonPath('errors.token.0', 'This reset link is invalid or has expired. Ask for a new one.');
    }

    public function test_the_new_password_must_be_confirmed(): void
    {
        $user = User::factory()->create(['email' => 'lifter@example.com']);

        $this->postJson('/api/reset-password', [
            'token' => Password::createToken($user),
            'email' => 'lifter@example.com',
            'password' => 'new-password',
            'password_confirmation' => 'other-password',
        ])->assertUnprocessable()->assertJsonPath('errors.password.0', 'The passwords do not match.');
    }
}
