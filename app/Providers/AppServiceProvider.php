<?php

namespace App\Providers;

use App\Enums\TokenAbility;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;
use Symfony\Component\Mailer\Bridge\Brevo\Transport\BrevoTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->overrideSanctumConfigurationToSupportRefreshToken();
        $this->configureRateLimiting();
        $this->checkDatabaseOnHealthCheck();
        $this->registerBrevoMailer();
        $this->pointEmailLinksAtTheFrontend();
    }

    private function registerBrevoMailer(): void
    {
        // MAIL_MAILER=brevo sends through Brevo's HTTP API with BREVO_API_KEY
        Mail::extend('brevo', function () {
            return (new BrevoTransportFactory)->create(
                new Dsn('brevo+api', 'default', config('services.brevo.key'))
            );
        });
    }

    private function pointEmailLinksAtTheFrontend(): void
    {
        // The sign up confirmation email is App\Notifications\ConfirmRegistration
        // The reset link opens the Vue page with the form for the new password
        ResetPassword::toMailUsing(function (User $user, string $token) {
            $url = config('app.frontend_url').'/reset-password?'.http_build_query([
                'token' => $token,
                'email' => $user->getEmailForPasswordReset(),
            ]);

            return (new MailMessage)
                ->subject('Reset your password')
                ->greeting("Hi {$user->name}!")
                ->line('Someone asked to reset the password of your account. If it was you, tap the button below.')
                ->action('Choose a new password', $url)
                ->line('The link works for 60 minutes. If you did not ask for this, you can ignore this email: your password stays the same.');
        });
    }

    private function checkDatabaseOnHealthCheck(): void
    {
        // /up runs a real query, so an uptime pinger keeps the free Aiven database from being
        // powered off as inactive, and the check fails when the database is unreachable
        Event::listen(DiagnosingHealth::class, function () {
            DB::select('select 1');
        });
    }

    private function configureRateLimiting(): void
    {
        // Login, register and refresh: few attempts per minute, counted per IP and per email,
        // so one address cannot guess passwords and one account cannot be hammered from many
        RateLimiter::for('auth', function (Request $request) {
            return [
                Limit::perMinute(10)->by('auth-ip:'.$request->ip()),
                Limit::perMinute(5)->by('auth-email:'.strtolower((string) $request->input('email')).'|'.$request->ip()),
            ];
        });

        // Authenticated API: generous enough for logging sets quickly, counted per user
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(180)->by($request->user()?->id ?: $request->ip());
        });
    }

    private function overrideSanctumConfigurationToSupportRefreshToken(): void
    {
        Sanctum::$accessTokenAuthenticationCallback = function ($accessToken, $isValid) {
            $abilities = collect($accessToken->abilities);
            if (! empty($abiauthlities) && $abilities[0] === TokenAbility::ISSUE_ACCESS_TOKEN->value) {
                return $accessToken->expires_at && $accessToken->expires_at->isFuture();
            }

            return $isValid;
        };

        Sanctum::$accessTokenRetrievalCallback = function ($request) {
            if (! $request->routeIs('refresh')) {
                return str_replace('Bearer ', '', $request->headers->get('Authorization'));
            }

            return $request->cookie('refreshToken') ?? '';
        };
    }
}
