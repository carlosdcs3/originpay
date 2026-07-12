<?php

use App\Http\Controllers\Api\MeController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\StripeController;
use App\Http\Controllers\Api\V1\BalanceController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\CustomerSubscriptionController;
use App\Http\Controllers\Api\V1\Payments\ChargeController;
use App\Http\Controllers\Api\V1\Payments\PaymentMethodController;
use App\Http\Controllers\Api\V1\Payments\SessionController;
use App\Http\Controllers\Api\V1\PayoutController;
use App\Http\Controllers\Api\V1\RefundController;
use App\Http\Controllers\Api\V1\Webhooks\EfiWebhookController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Frontend\PublicStatusController;
use App\Http\Controllers\Gateway\ModernWebhookController;
use App\Http\Controllers\HealthCheckController;
use App\Http\Controllers\Webhook\GatewayWebhookController;
use App\Http\Middleware\AuthenticateApiRequest;
use App\Http\Middleware\IdempotencyMiddleware;
use Illuminate\Support\Facades\Route;

// Merchant Legacy Routes
Route::middleware('merchant.auth')->group(function () {
    Route::group(['prefix' => 'v1'], function () {
        Route::post('initiate-payment', [PaymentController::class, 'initiatePayment']);
        Route::get('verify-payment/{trxId}', [PaymentController::class, 'verifyPayment']);
    });
});

// Stripe
Route::middleware('auth:sanctum')
    ->post('/stripe/issuing/ephemeral-key', [StripeController::class, 'createEphemeralKey'])->name('stripe.issuing.ephemeral-key');

// Webhooks Gateway
Route::post('/webhook/modern/{provider}', [ModernWebhookController::class, 'handle']);
Route::post('/webhooks/gateway/{provider}', [GatewayWebhookController::class, 'handle']);
Route::post('/webhooks/efi', [EfiWebhookController::class, 'handle']);

// Admin Route for Reprocessing Dead Letters
Route::post('/admin/webhooks/dead-letters/{id}/reprocess', [GatewayWebhookController::class, 'reprocess'])
    ->middleware('admin.permission:webhooks.dlq.reprocess');

// ==========================================
// DIGISYNK API V1 (Public Developer API)
// ==========================================
Route::prefix('v1')->middleware(['api.request_id', 'api.log', 'api.auth', 'throttle:api', 'api.idempotency'])->group(function () {

    // Payments (More restricted rate limit)
    Route::post('/charges', [App\Http\Controllers\Api\V1\PaymentController::class, 'store'])->middleware(['throttle:payments', 'api.scope:charges.write']);
    Route::get('/charges/{id}', [App\Http\Controllers\Api\V1\PaymentController::class, 'show'])->middleware('api.scope:charges.read');
    Route::get('/charges', [App\Http\Controllers\Api\V1\PaymentController::class, 'index'])->middleware('api.scope:charges.read');
    Route::post('/payments', [App\Http\Controllers\Api\V1\PaymentController::class, 'store'])->middleware(['throttle:payments', 'api.scope:charges.write']);
    Route::get('/payments/{id}', [App\Http\Controllers\Api\V1\PaymentController::class, 'show'])->middleware('api.scope:charges.read');
    Route::get('/payments', [App\Http\Controllers\Api\V1\PaymentController::class, 'index'])->middleware('api.scope:charges.read');

    // Refunds
    Route::post('/refunds', [RefundController::class, 'store'])->middleware(['api.transaction_password', 'api.scope:refunds.write']);

    // Payouts (Withdrawals)
    Route::post('/payouts', [PayoutController::class, 'store'])->middleware(['api.transaction_password', 'api.scope:settlements.write']);
    Route::get('/payouts', [PayoutController::class, 'index'])->middleware('api.scope:settlements.read');

    // Balance
    Route::get('/balance', [BalanceController::class, 'index'])->middleware('api.scope:settlements.read');

    // Customers
    Route::post('/customers', [CustomerController::class, 'store'])->middleware('api.scope:customers.write');
    Route::get('/customers/{id}', [CustomerController::class, 'show'])->middleware('api.scope:customers.read');

    // Webhook Testing
    Route::post('/webhooks/test', [App\Http\Controllers\Api\V1\WebhookController::class, 'test'])->middleware('api.scope:webhooks.write');

    // Customer Subscriptions
    Route::post('/customer-subscriptions', [CustomerSubscriptionController::class, 'store']);
    Route::get('/customer-subscriptions', [CustomerSubscriptionController::class, 'index']);
    Route::get('/customer-subscriptions/{id}', [CustomerSubscriptionController::class, 'show']);
    Route::post('/customer-subscriptions/{id}/cancel', [CustomerSubscriptionController::class, 'cancel']);
});

// ==========================================
// ORIGINPAY PAYMENTS API V1 (Sprint 5 Skeleton)
// ==========================================
Route::prefix('v1')->middleware([
    AuthenticateApiRequest::class,
    'throttle:originpay_api',
    IdempotencyMiddleware::class,
])->group(function () {
    // Sessions
    Route::post('sessions', [SessionController::class, 'store']);
    Route::get('sessions/{id}', [SessionController::class, 'show']);

    // Internal payment-core charges
    Route::post('core/charges', [ChargeController::class, 'store']);
    Route::get('core/charges/{id}', [ChargeController::class, 'show']);

    // Payment Methods
    Route::post('/payment-methods', [PaymentMethodController::class, 'store']);
    Route::get('/payment-methods/{id}', [PaymentMethodController::class, 'show']);

    // Me
    Route::get('/me', [MeController::class, 'show']);
});

// Public Status API (For status.digisynk.com)
Route::get('/v1/status', [PublicStatusController::class, 'getStatus']);

// Observability Health Checks
Route::get('/health/live', [HealthCheckController::class, 'live'])->name('health.live');
Route::get('/health/ready', [HealthCheckController::class, 'ready'])->name('health.ready');
Route::get('/health/deep', [HealthCheckController::class, 'deep'])->name('health.deep');
Route::post('webhooks/{gateway}', [WebhookController::class, 'handle']);
