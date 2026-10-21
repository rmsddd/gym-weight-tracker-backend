<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use Tests\Support\GuardedRoutes;
use Tests\TestCase;

class RouteProtectionTest extends TestCase
{
    use RefreshDatabase;

    /** The only API routes a guest may reach. Adding to this list is a deliberate decision. */
    private const PUBLIC_API_ROUTES = [
        'api/register',
        'api/login',
        'api/refresh',
        'api/forgot-password',
        'api/reset-password',
        'api/email/verify/{id}/{hash}',
    ];

    public function test_every_api_route_except_the_auth_ones_requires_a_token(): void
    {
        $apiRoutes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn (RoutingRoute $route) => str_starts_with($route->uri(), 'api/'));

        $this->assertGreaterThan(20, $apiRoutes->count());

        foreach ($apiRoutes as $route) {
            $middleware = $route->gatherMiddleware();

            if (in_array($route->uri(), self::PUBLIC_API_ROUTES, true)) {
                $this->assertContains('throttle:auth', $middleware, "{$route->uri()} must be rate limited");

                continue;
            }

            $this->assertContains(
                'auth:sanctum',
                $middleware,
                implode('|', $route->methods())." {$route->uri()} is reachable without logging in"
            );
        }
    }

    public function test_every_logged_in_route_is_guarded_by_a_permission(): void
    {
        $routes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn (RoutingRoute $route) => in_array('auth:sanctum', $route->gatherMiddleware(), true))
            // The admin API is root's alone, behind its own middleware
            ->reject(fn (RoutingRoute $route) => str_starts_with($route->uri(), 'api/admin'))
            ->map(fn (RoutingRoute $route) => "{$route->methods()[0]} {$route->uri()}");

        $known = [...array_keys(GuardedRoutes::all()), ...GuardedRoutes::UNGUARDED];

        foreach ($routes as $route) {
            $this->assertContains($route, $known, "{$route} has no permission in Tests\\Support\\GuardedRoutes");
        }

        // And the list holds no route that no longer exists, nor a permission that guards nothing
        $this->assertEqualsCanonicalizing($routes->values()->all(), $known);
        $this->assertEqualsCanonicalizing(
            PermissionCatalog::names(),
            array_values(array_unique(array_column(GuardedRoutes::all(), 0)))
        );
    }

    public function test_guests_get_401_from_protected_routes(): void
    {
        $this->getJson('/api/workouts')->assertUnauthorized();
        $this->getJson('/api/user')->assertUnauthorized();
        $this->postJson('/api/workouts', ['date' => '2026-10-08'])->assertUnauthorized();
        $this->deleteJson('/api/workouts/1')->assertUnauthorized();
        $this->getJson('/api/workout-templates')->assertUnauthorized();
        $this->getJson('/api/muscle-stats')->assertUnauthorized();
    }

    public function test_login_says_which_field_is_wrong(): void
    {
        User::factory()->create(['email' => 'lifter@example.com']);

        $this->postJson('/api/login', ['email' => 'nobody@example.com', 'password' => 'whatever'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.email.0', 'No account uses this email.')
            ->assertJsonMissingPath('errors.password');

        $this->postJson('/api/login', ['email' => 'lifter@example.com', 'password' => 'wrong-password'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.password.0', 'Incorrect password.')
            ->assertJsonMissingPath('errors.email');

        $this->postJson('/api/login', ['email' => '', 'password' => ''])
            ->assertUnprocessable()
            ->assertJsonPath('errors.email.0', 'Enter your email or username.')
            ->assertJsonPath('errors.password.0', 'Enter your password.');

        $this->postJson('/api/login', ['email' => 'nobody', 'password' => 'whatever'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.email.0', 'No account uses this username.');
    }

    public function test_login_also_takes_the_username(): void
    {
        User::factory()->create(['username' => 'root', 'email' => 'root@example.test']);
        // Logging in starts a session, which only the Vue app's stateful origin gets
        $this->withHeader('Origin', 'http://localhost:5173');

        $this->postJson('/api/login', ['email' => 'Root', 'password' => 'wrong-password'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.password.0', 'Incorrect password.');

        $this->postJson('/api/login', ['email' => 'Root', 'password' => 'password'])
            ->assertOk()
            ->assertJsonPath('data.user.username', 'root');
    }

    public function test_logout_ends_the_session(): void
    {
        User::factory()->create(['email' => 'lifter@example.com']);

        // The Vue app is a stateful origin: it is authenticated by the session cookie, not a token
        $this->withHeader('Origin', 'http://localhost:5173');

        $this->postJson('/api/login', ['email' => 'lifter@example.com', 'password' => 'password'])->assertOk();
        $this->getJson('/api/user')->assertOk();

        $this->postJson('/api/logout')->assertOk();

        // Each request of the test shares the app, so drop the user the guard remembers from the last one
        $this->app['auth']->forgetGuards();

        $this->getJson('/api/user')->assertUnauthorized();
    }

    public function test_login_is_rate_limited_after_repeated_failures(): void
    {
        User::factory()->create(['email' => 'lifter@example.com']);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->postJson('/api/login', ['email' => 'lifter@example.com', 'password' => 'wrong-password'])
                ->assertUnprocessable();
        }

        $this->postJson('/api/login', ['email' => 'lifter@example.com', 'password' => 'wrong-password'])
            ->assertTooManyRequests();
    }
}
