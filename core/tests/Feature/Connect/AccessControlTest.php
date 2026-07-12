<?php

namespace Tests\Feature\Connect;

use App\Http\Middleware\Connect\EnsureConnectEnabled;
use App\Http\Middleware\Connect\EnsureConnectSubscriptionActive;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    public function test_unauthenticated_connect_request_is_denied(): void
    {
        $this->get('/user/connect')->assertRedirect();
    }

    public function test_connect_routes_enforce_guards_server_side(): void
    {
        $routes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route) => str_starts_with($route->uri(), 'user/connect'));

        $this->assertNotEmpty($routes);
        foreach ($routes as $route) {
            $middleware = $route->gatherMiddleware();
            $this->assertContains('auth', $middleware);
            $this->assertContains(EnsureConnectEnabled::class, $middleware);
            $this->assertContains(EnsureConnectSubscriptionActive::class, $middleware);
        }
    }

    public function test_mutable_stub_routes_are_unavailable_without_side_effects(): void
    {
        $before = collect(Route::getRoutes()->getRoutes())->count();

        $this->post('/user/connect/campaigns')->assertMethodNotAllowed();
        $this->post('/user/connect/journeys/1/publish')->assertNotFound();
        $this->delete('/user/connect/contacts/1')->assertNotFound();

        $this->assertSame($before, collect(Route::getRoutes()->getRoutes())->count());
    }

    public function test_retained_data_routes_do_not_expose_unscoped_detail_endpoints(): void
    {
        $uris = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route) => str_starts_with($route->uri(), 'user/connect'))
            ->pluck('uri');

        $this->assertFalse($uris->contains('user/connect/contacts/{contact}'));
        $this->assertFalse($uris->contains('user/connect/templates/{template}'));
        $this->assertFalse($uris->contains('user/connect/campaigns/{campaign}'));
    }
}
