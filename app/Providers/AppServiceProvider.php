<?php

namespace App\Providers;

use App\Enums\TokenAbility;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

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
            if (!empty($abiauthlities) && $abilities[0] === TokenAbility::ISSUE_ACCESS_TOKEN->value) {
                return $accessToken->expires_at && $accessToken->expires_at->isFuture();
            }

            return $isValid;
        };

        Sanctum::$accessTokenRetrievalCallback = function ($request) {
            if (!$request->routeIs('refresh')) {
                return str_replace('Bearer ', '', $request->headers->get('Authorization'));
            }

            return $request->cookie('refreshToken') ?? '';
        };
    }
}
