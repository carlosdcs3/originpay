<?php

namespace Tests\Feature\Connect;

use App\Http\Middleware\Connect\EnsureConnectEnabled;
use App\Http\Middleware\Connect\EnsureConnectSubscriptionActive;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class R71ConnectRouteHardeningTest extends TestCase
{
    public function test_every_connect_route_requires_module_and_subscription_guards(): void
    {
        $routes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route) => str_starts_with($route->uri(), 'user/connect'));

        $this->assertNotEmpty($routes);

        foreach ($routes as $route) {
            $middleware = $route->gatherMiddleware();
            $this->assertContains(EnsureConnectEnabled::class, $middleware, $route->uri());
            $this->assertContains(EnsureConnectSubscriptionActive::class, $middleware, $route->uri());
        }
    }

    public function test_stub_mutation_and_special_action_routes_are_not_exposed(): void
    {
        $routes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route) => str_starts_with($route->uri(), 'user/connect'));

        foreach ($routes as $route) {
            $this->assertSame(['GET', 'HEAD'], $route->methods(), $route->uri());
        }

        $uris = $routes->pluck('uri');
        $this->assertFalse($uris->contains('user/connect/journeys/{id}/publish'));
        $this->assertFalse($uris->contains('user/connect/providers/{id}/test'));
        $this->assertFalse($uris->contains('user/connect/dlq/{id}/reprocess'));
    }
}
