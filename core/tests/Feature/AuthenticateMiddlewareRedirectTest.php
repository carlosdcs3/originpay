<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

class AuthenticateMiddlewareRedirectTest extends TestCase
{
    public function test_unauthenticated_user_accessing_protected_user_route_redirects_to_user_login()
    {
        $response = $this->get('/user/boletos');
        $response->assertStatus(302);
        $response->assertRedirect(route('user.login'));
    }

    public function test_unauthenticated_admin_accessing_admin_route_redirects_to_admin_login()
    {
        Route::get('/admin/fake-protected', function () {
            return 'secret';
        })->middleware('auth:admin');

        $response = $this->get('/admin/fake-protected');
        $response->assertStatus(302);
        $response->assertRedirect(route('admin.login-view'));
    }

    public function test_unauthenticated_api_request_returns_401_json()
    {
        Route::get('/api/test-auth-middleware', function () {
            return response()->json(['success' => true]);
        })->middleware('auth');

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get('/api/test-auth-middleware');

        $response->assertStatus(401);
    }
}
