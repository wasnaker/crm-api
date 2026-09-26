<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Smoke test for the API host.
 *
 * Replaces the Laravel scaffold test, which asserted a `welcome` view that an
 * API-only application does not have -- it failed on every run and taught
 * everyone to ignore a red suite.
 *
 * These assertions cover the three things that fail silently when the host or
 * the package is misconfigured: routing, the package being loaded at all, and
 * the auth:sanctum middleware actually being applied.
 */
class ApiSmokeTest extends TestCase
{
    public function test_root_returns_a_service_descriptor_instead_of_a_view(): void
    {
        $response = $this->getJson('/');

        $response->assertOk();
        $response->assertJsonStructure(['service', 'api', 'health', 'docs']);
    }

    public function test_health_endpoint_responds(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertOk();
        $response->assertExactJson(['status' => 'healthy']);
    }

    public function test_package_routes_are_registered(): void
    {
        // Registered by spine/laravel-spine, so their absence means the package
        // was never discovered by the host. Most package routes are unnamed
        // closures, so they are matched by method + URI rather than by name.
        foreach ([
            'GET api/v1/health',
            'GET api/v1/test',
            'POST api/v1/auth/login',
            'GET api/v1/dashboard',
            'GET api/v1/files/limits',
        ] as $signature) {
            [$method, $uri] = explode(' ', $signature);

            $this->assertTrue(
                $this->routeExists($method, $uri),
                "package route {$signature} is not registered"
            );
        }
    }

    public function test_every_api_v1_controller_route_is_served_by_the_package(): void
    {
        $routes = collect(\Illuminate\Support\Facades\Route::getRoutes()->getRoutes())
            ->filter(fn ($route) => str_starts_with($route->uri(), 'api/v1'))
            ->filter(fn ($route) => $route->getControllerClass() !== null);

        $this->assertGreaterThan(
            50,
            $routes->count(),
            'expected the package to register the bulk of the api/v1 surface'
        );

        // Framework-owned routes that the package mounts on purpose.
        $frameworkOwned = [
            // Reverb/Echo channel auth; the package only registers the route.
            'api/v1/broadcasting/auth' => \Illuminate\Broadcasting\BroadcastController::class,
        ];

        $foreign = $routes
            ->reject(fn ($route) => str_starts_with(ltrim($route->getControllerClass(), '\\'), 'Spine\\')
                || ltrim($frameworkOwned[$route->uri()] ?? '', '\\') === ltrim($route->getControllerClass(), '\\'))
            ->map(fn ($route) => $route->uri() . ' -> ' . $route->getControllerClass())
            ->values()
            ->all();

        $this->assertSame(
            [],
            $foreign,
            'api/v1 routes must be served by Spine\\ controllers; the host owns no domain logic'
        );

        // A framework allowlist that is never consulted is a silent loophole.
        foreach (array_keys($frameworkOwned) as $uri) {
            $this->assertTrue(
                $this->routeExists('POST', $uri) || str_contains($uri, '{'),
                "framework allowlist entry {$uri} does not match any registered route"
            );
        }
    }

    protected function routeExists(string $method, string $uri): bool
    {
        return collect(\Illuminate\Support\Facades\Route::getRoutes()->getRoutes())
            ->contains(fn ($route) => $route->uri() === $uri
                && in_array($method, $route->methods(), true));
    }

    public function test_protected_routes_reject_anonymous_callers(): void
    {
        $this->getJson('/api/v1/auth/me')->assertUnauthorized();
        $this->getJson('/api/v1/dashboard')->assertUnauthorized();
        $this->getJson('/api/v1/files/limits')->assertUnauthorized();
    }

    public function test_unknown_api_path_is_a_json_404(): void
    {
        $this->getJson('/api/v1/does-not-exist')->assertNotFound();
    }
}
