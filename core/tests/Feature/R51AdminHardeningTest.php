<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureApiKeyScope;
use App\Models\Admin;
use App\Models\ApiKey;
use App\Models\Merchant;
use App\Models\User;
use App\Models\WebhookDeadLetter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class R51AdminHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_dlq_reprocess_api_route_denies_merchant_and_common_user_but_allows_authorized_admin(): void
    {
        $merchantUser = User::factory()->create();
        Merchant::factory()->create(['user_id' => $merchantUser->id]);
        $commonUser = User::factory()->create();

        $this->actingAs($merchantUser)
            ->postJson('/api/admin/webhooks/dead-letters/1/reprocess', ['reason' => 'replay operacional'])
            ->assertForbidden();

        $this->actingAs($commonUser)
            ->postJson('/api/admin/webhooks/dead-letters/1/reprocess', ['reason' => 'replay operacional'])
            ->assertForbidden();

        Permission::firstOrCreate(
            ['name' => 'webhooks.dlq.reprocess', 'guard_name' => 'admin'],
            ['category' => 'enterprise']
        );
        $admin = Admin::factory()->create();
        $admin->givePermissionTo('webhooks.dlq.reprocess');
        Queue::fake();
        $deadLetter = WebhookDeadLetter::create([
            'gateway_code' => 'efi',
            'payload' => ['event' => 'test'],
            'headers' => [],
            'status' => 'failed',
            'error_message' => 'erro inicial',
        ]);

        $this->actingAs($admin, 'admin')
            ->postJson("/api/admin/webhooks/dead-letters/{$deadLetter->id}/reprocess", ['reason' => 'replay operacional'])
            ->assertOk();
    }

    public function test_dlq_reprocess_route_has_explicit_admin_permission_middleware(): void
    {
        $route = collect(app('router')->getRoutes())->first(fn ($route) => $route->uri() === 'api/admin/webhooks/dead-letters/{id}/reprocess');

        $this->assertNotNull($route);
        $this->assertContains('admin.permission:webhooks.dlq.reprocess', $route->gatherMiddleware());
    }

    public function test_api_key_scope_middleware_denies_missing_scope_and_allows_matching_scope(): void
    {
        $user = User::factory()->create();
        $key = ApiKey::factory()->create([
            'user_id' => $user->id,
            'permissions' => ['charges.read'],
            'status' => true,
        ]);

        $denied = Request::create('/api/v1/charges', 'POST');
        $denied->merge(['api_key_id' => $key->id]);

        $deniedResponse = app(EnsureApiKeyScope::class)->handle($denied, fn () => response()->json(['ok' => true]), 'charges.write');
        $this->assertSame(403, $deniedResponse->getStatusCode());

        $allowed = Request::create('/api/v1/charges', 'GET');
        $allowed->merge(['api_key_id' => $key->id]);

        $allowedResponse = app(EnsureApiKeyScope::class)->handle($allowed, fn () => response()->json(['ok' => true]), 'charges.read');
        $this->assertSame(200, $allowedResponse->getStatusCode());
    }
}
