<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RouteProtectionTest extends TestCase
{
    use RefreshDatabase;

    /** The only API routes a guest may reach. Adding to this list is a deliberate decision. */
    private const PUBLIC_API_ROUTES = ['api/register', 'api/login', 'api/refresh'];

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

    public function test_guests_get_401_from_protected_routes(): void
    {
        $this->getJson('/api/workouts')->assertUnauthorized();
        $this->getJson('/api/user')->assertUnauthorized();
        $this->postJson('/api/workouts', ['date' => '2026-10-08'])->assertUnauthorized();
        $this->deleteJson('/api/workouts/1')->assertUnauthorized();
        $this->getJson('/api/workout-templates')->assertUnauthorized();
        $this->getJson('/api/muscle-stats')->assertUnauthorized();
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
