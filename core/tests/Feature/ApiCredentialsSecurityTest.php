<?php

namespace Tests\Feature;

use App\Http\Middleware\AuthenticateApiKey;
use App\Http\Middleware\LogApiRequests;
use App\Models\ApiKey;
use App\Models\Merchant;
use App\Models\User;
use App\Services\Auth\ApiAuthenticationService;
use App\Services\Auth\ApiKeyManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ApiCredentialsSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_merchant_serialization_hides_legacy_credentials(): void
    {
        $merchant = Merchant::factory()->create();

        $serialized = $merchant->fresh()->toArray();

        $this->assertArrayNotHasKey('api_key', $serialized);
        $this->assertArrayNotHasKey('api_secret', $serialized);
        $this->assertArrayNotHasKey('merchant_key', $serialized);
        $this->assertArrayNotHasKey('test_api_key', $serialized);
        $this->assertArrayNotHasKey('test_api_secret', $serialized);
        $this->assertArrayNotHasKey('test_merchant_key', $serialized);
    }

    public function test_merchant_mass_assignment_cannot_set_legacy_credentials(): void
    {
        $user = User::factory()->create();

        $merchant = Merchant::create([
            'user_id' => $user->id,
            'business_name' => 'Secure Merchant',
            'site_url' => 'https://secure-merchant.test',
            'currency_id' => \App\Models\Currency::factory()->create()->id,
            'business_email' => 'secure@example.test',
            'status' => \App\Enums\MerchantStatus::APPROVED,
            'api_key' => 'pk_live_plain_should_not_persist',
            'api_secret' => 'sk_live_plain_should_not_persist',
            'test_api_key' => 'pk_test_plain_should_not_persist',
            'test_api_secret' => 'sk_test_plain_should_not_persist',
            'test_merchant_key' => 'test_merchant_plain_should_not_persist',
        ]);

        $merchant = $merchant->fresh();

        $this->assertNotSame('pk_live_plain_should_not_persist', $merchant->getRawOriginal('api_key'));
        $this->assertNotSame('sk_live_plain_should_not_persist', $merchant->getRawOriginal('api_secret'));
        $this->assertNotSame('pk_test_plain_should_not_persist', $merchant->getRawOriginal('test_api_key'));
        $this->assertNotSame('sk_test_plain_should_not_persist', $merchant->getRawOriginal('test_api_secret'));
        $this->assertNotSame('test_merchant_plain_should_not_persist', $merchant->getRawOriginal('test_merchant_key'));
    }

    public function test_hash_based_api_credentials_do_not_store_plain_secret_and_authenticate(): void
    {
        $merchant = Merchant::factory()->create();
        $keys = app(ApiKeyManagementService::class)->generateKeys($merchant->id, 'sandbox');

        $this->assertArrayHasKey('secret_key', $keys);

        $this->assertDatabaseHas('api_credentials', [
            'id' => $keys['id'],
            'merchant_id' => $merchant->id,
            'environment' => 'sandbox',
            'status' => 'active',
        ]);

        $this->assertDatabaseMissing('api_credentials', [
            'secret_key_hash' => $keys['secret_key'],
        ]);

        $context = app(ApiAuthenticationService::class)->authenticate('Bearer ' . $keys['secret_key'], 'req_test');

        $this->assertNotNull($context);
        $this->assertSame((string) $merchant->id, $context->merchantId);
        $this->assertSame('sandbox', $context->environment);
    }

    public function test_public_api_key_is_not_accepted_as_bearer_secret(): void
    {
        $merchant = Merchant::factory()->create();
        $keys = app(ApiKeyManagementService::class)->generateKeys($merchant->id, 'sandbox');

        $context = app(ApiAuthenticationService::class)->authenticate('Bearer ' . $keys['public_key'], 'req_test');

        $this->assertNull($context);
    }

    public function test_rotation_and_revocation_preserve_hash_based_authentication_rules(): void
    {
        $merchant = Merchant::factory()->create();
        $service = app(ApiKeyManagementService::class);
        $old = $service->generateKeys($merchant->id, 'sandbox');

        $new = $service->rotateKey($old['id'], 60);

        $this->assertNotNull($new);
        $this->assertNotSame($old['secret_key'], $new['secret_key']);
        $this->assertNotNull(app(ApiAuthenticationService::class)->authenticate('Bearer ' . $old['secret_key'], 'req_old_grace'));
        $this->assertNotNull(app(ApiAuthenticationService::class)->authenticate('Bearer ' . $new['secret_key'], 'req_new'));

        $service->revokeKey($new['id']);

        $this->assertNull(app(ApiAuthenticationService::class)->authenticate('Bearer ' . $new['secret_key'], 'req_new_revoked'));
    }

    public function test_sha256_api_key_model_authenticates_without_plain_secret_storage(): void
    {
        $user = User::factory()->create();
        $merchant = Merchant::factory()->create(['user_id' => $user->id]);
        $plainSecret = 'sk_test_' . str_repeat('a', 24);

        $apiKey = ApiKey::create([
            'user_id' => $user->id,
            'name' => 'Test key',
            'key_prefix' => 'pk_test_example',
            'key_hash' => hash('sha256', $plainSecret),
            'environment' => 'test',
            'permissions' => ['*'],
            'status' => true,
        ]);

        $request = Request::create('/api/v1/example', 'GET');
        $request->headers->set('Authorization', 'Bearer ' . $plainSecret);

        $response = app(AuthenticateApiKey::class)->handle($request, function (Request $request) use ($merchant) {
            $this->assertSame($merchant->user_id, $request->input('api_user_id'));
            $this->assertSame($merchant->id, $request->input('api_merchant_id'));

            return response()->json(['ok' => true]);
        });

        $this->assertSame(200, $response->getStatusCode());
        $this->assertDatabaseHas('api_keys', [
            'id' => $apiKey->id,
            'key_hash' => hash('sha256', $plainSecret),
        ]);
        $this->assertDatabaseMissing('api_keys', [
            'key_hash' => $plainSecret,
        ]);
    }

    public function test_developer_portal_rotation_stores_sha256_hash_compatible_with_api_authentication(): void
    {
        $user = User::factory()->create();
        Merchant::factory()->create(['user_id' => $user->id]);
        $oldSecret = 'sk_test_' . str_repeat('b', 24);

        $apiKey = ApiKey::create([
            'user_id' => $user->id,
            'name' => 'Rotatable key',
            'key_prefix' => 'pk_test_rotation',
            'key_hash' => hash('sha256', $oldSecret),
            'environment' => 'test',
            'permissions' => ['*'],
            'status' => true,
        ]);

        $this->mock(\App\Services\TransactionPasswordService::class, function ($mock) {
            $mock->shouldReceive('verifyRequest')->once()->andReturn(true);
        });

        $request = Request::create("/user/developer/api-keys/{$apiKey->id}/rotate", 'POST', [
            'transaction_password' => '1234',
        ]);
        $request->setUserResolver(fn () => $user);
        $this->actingAs($user);

        app(\App\Http\Controllers\User\Developer\ApiKeyController::class)->rotate($request, $apiKey->id);

        $rotated = $apiKey->fresh();
        $this->assertSame(64, strlen($rotated->key_hash));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $rotated->key_hash);
        $this->assertNotSame(hash('sha256', $oldSecret), $rotated->key_hash);
    }

    public function test_api_log_sanitizer_masks_credential_field_names(): void
    {
        $middleware = new LogApiRequests();
        $sanitize = \Closure::bind(function (array $payload) {
            return $this->sanitizePayload($payload);
        }, $middleware, LogApiRequests::class);

        $sanitized = $sanitize([
            'api_secret' => 'secret-value',
            'secret_key' => 'secret-value',
            'merchant_key' => 'merchant-value',
            'test_api_secret' => 'test-secret-value',
            'x-api-key' => 'header-secret',
            'nested' => [
                'x-signature' => 'signature-value',
            ],
            'safe' => 'visible',
        ]);

        $this->assertSame('***', $sanitized['api_secret']);
        $this->assertSame('***', $sanitized['secret_key']);
        $this->assertSame('***', $sanitized['merchant_key']);
        $this->assertSame('***', $sanitized['test_api_secret']);
        $this->assertSame('***', $sanitized['x-api-key']);
        $this->assertSame('***', $sanitized['nested']['x-signature']);
        $this->assertSame('visible', $sanitized['safe']);
    }
}
