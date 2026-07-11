<?php

use App\Http\Controllers\Common\AppController;
use App\Http\Controllers\Common\FileController;
use App\Http\Controllers\Common\LocaleController;
use App\Http\Controllers\Common\SummernoteController;
use App\Http\Controllers\Frontend\BoletoController;
use App\Http\Controllers\Frontend\ContactController;
use App\Http\Controllers\Frontend\CustomerController;
use App\Http\Controllers\Frontend\CustomerSubscriptionDashboardController;
use App\Http\Controllers\Frontend\DashboardController;
use App\Http\Controllers\Frontend\DepositController;
use App\Http\Controllers\Frontend\ChargeController;
use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Frontend\IPNController;
use App\Http\Controllers\Frontend\KycSubmissionController;
use App\Http\Controllers\Frontend\MerchantController;
use App\Http\Controllers\Frontend\MerchantPaymentReceiveController;
use App\Http\Controllers\Frontend\NotificationController;
use App\Http\Controllers\Frontend\PageController;
use App\Http\Controllers\Frontend\PaymentLinkController;
use App\Http\Controllers\Frontend\PublicPaymentLinkController;
use App\Http\Controllers\Frontend\ReferralController;
use App\Http\Controllers\Frontend\SendMoneyController;
use App\Http\Controllers\Frontend\SettingController;
use App\Http\Controllers\Frontend\StatusController;
use App\Http\Controllers\Frontend\SubscriberController;
use App\Http\Controllers\Frontend\TicketController;
use App\Http\Controllers\Frontend\TransactionController;
use App\Http\Controllers\Frontend\TransferController;
use App\Http\Controllers\Frontend\TwoFactorController;
use App\Http\Controllers\Frontend\UserRankController;
use App\Http\Controllers\Frontend\WalletController;
use App\Http\Controllers\Frontend\WithdrawAccountController;
use App\Http\Controllers\Frontend\WithdrawController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Landing Page Routes
|--------------------------------------------------------------------------
*/
/*
|--------------------------------------------------------------------------
| Landing Page Routes
|--------------------------------------------------------------------------
*/
Route::view('/ecossistema', 'frontend.pages.ecossistema')->name('ecossistema');
Route::view('/precos', 'frontend.pages.precos')->name('precos');

// Desenvolvedores (Novas rotas introdutÃ³rias)
Route::view('/docs/autenticacao', 'frontend.pages.docs.auth')->name('docs.auth');
Route::view('/docs/webhooks-intro', 'frontend.pages.docs.webhooks')->name('docs.webhooks');
Route::view('/docs/openapi', 'frontend.pages.docs.openapi')->name('docs.openapi');

// Empresa
Route::view('/sobre-nos', 'frontend.pages.sobre')->name('sobre');
Route::view('/carreiras', 'frontend.pages.carreiras')->name('carreiras');
Route::view('/contato', 'frontend.pages.contato')->name('contato');

// JurÃ­dico
Route::view('/termos', 'frontend.pages.termos')->name('termos');
Route::view('/privacidade', 'frontend.pages.privacidade')->name('privacidade');
Route::view('/lgpd', 'frontend.pages.lgpd')->name('lgpd');
Route::view('/seguranca', 'frontend.pages.seguranca')->name('seguranca');
Route::get('/', HomeController::class)->name('home');

// Redirect /home to /
Route::redirect('/home', '/');

Route::get('/pay/{slug}', [PublicPaymentLinkController::class, 'show'])
    ->middleware('throttle:60,1')
    ->name('payment-links.public.show');
Route::post('/pay/{slug}', [PublicPaymentLinkController::class, 'submit'])
    ->middleware('throttle:20,1')
    ->name('payment-links.public.submit');

/*
|--------------------------------------------------------------------------
| Public Documentation Routes
|--------------------------------------------------------------------------
*/
Route::prefix('docs')->as('docs.')->controller(\App\Http\Controllers\Frontend\PublicDocsController::class)->group(function () {
    // Current narrative docs
    Route::get('/', 'index')->name('index');
    Route::get('/{page}', 'show')->name('show')->where('page', '[a-zA-Z0-9\-]+');
});

Route::prefix('docs/v1')->as('docs.v1.')->controller(\App\Http\Controllers\Frontend\PublicDocsController::class)->group(function () {
    // API Reference Enterprise
    Route::get('/api-reference', 'apiReferenceIndex')->name('api_reference.index');
    Route::get('/api-reference/{endpoint}', 'apiReferenceShow')->name('api_reference.show')->where('endpoint', '[a-zA-Z0-9\-]+');
    
    // Simulators and Tools
    Route::get('/explorer', 'apiExplorer')->name('explorer');
    Route::get('/webhooks/simulator', 'webhookSimulator')->name('webhook_simulator');
    Route::get('/resources', 'developerResources')->name('resources');
    
    // Additional Guides
    Route::get('/migration-guide', 'migrationGuide')->name('migration');
    Route::get('/release-notes', 'releaseNotes')->name('release_notes');
});


// Blog Routes (Static Mockup)
Route::view('/blog', 'frontend.pages.blog')->name('blog.index');

// Contact Routes
Route::post('/contact-submit', [ContactController::class, 'submit'])->name('contact.submit');

// Subscribe
Route::post('/subscribe', SubscriberController::class)->name('subscribe.submit');

/*
|--------------------------------------------------------------------------
| All Type User Routes Like Normal User, Merchant User
|--------------------------------------------------------------------------
*/
Route::prefix('user')->as('user.')->middleware(['auth', 'account.status.check', 'verified', '2fa', 'block.ip', 'transaction.password'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ========================== User Settings Routes =============================
    Route::prefix('settings')->as('settings.')->controller(SettingController::class)->group(function () {
        Route::get('profile', 'profile')->name('profile');
        Route::post('profile-update', 'profileUpdate')->name('profile.update')->middleware('transaction.verified');
        Route::get('account', 'account')->name('account');
        Route::get('change-password', 'changePassword')->name('password.change');
        Route::post('password-update', 'passwordUpdate')->name('password.update')->middleware('transaction.verified');
        Route::get('verify-email', 'verifyEmail')->name('verify-email');
        Route::get('google-2fa', 'google2fa')->name('google-2fa');
        Route::post('google-2fa-store', 'google2faStore')->name('google-2fa.store');
    });

    // ========================== Transaction Password Routes ==========================
    Route::post('/transaction-password/store', [\App\Http\Controllers\User\TransactionPasswordController::class, 'store'])->name('transaction-password.store');
    Route::post('/transaction-password/update', [\App\Http\Controllers\User\TransactionPasswordController::class, 'update'])->name('transaction-password.update');

    // Two-Factor Authentication
        Route::prefix('2fa')->as('2fa.')->controller(TwoFactorController::class)->group(function () {
            Route::get('setup', 'showSetupForm')->name('setup');
            Route::post('enable', 'enable2fa')->name('enable');
            Route::post('disable', 'disable2fa')->name('disable');
        });

        // KYC Verification
        Route::prefix('kyc')->as('kyc.')->controller(KycSubmissionController::class)->group(function () {
            Route::get('verify', 'kycVerify')->name('verify');
            Route::get('template/details/{id}', 'templateDetails')->name('template.details');
            Route::post('submit', 'kycSubmit')->name('submit');
        });

    // ========================== Billing & Faturamento ============================
    Route::prefix('billing')->as('billing.')->controller(\App\Http\Controllers\User\UserBillingController::class)->group(function () {
        Route::get('/', 'index')->name('index');
    });

    // ========================== Desenvolvedor (Developer) ========================
    Route::prefix('developer')->as('developer.')->group(function () {
        // Root Developer Hub (redirects to first tab)
        Route::get('/', function() { return redirect()->route('user.developer.api-keys.index'); })->name('index');

        // API Keys
        Route::get('/api-keys', [\App\Http\Controllers\User\Developer\ApiKeyController::class, 'index'])->name('api-keys.index');
        Route::post('/api-keys', [\App\Http\Controllers\User\Developer\ApiKeyController::class, 'store'])->name('api-keys.store')->middleware('transaction.verified');
        Route::post('/api-keys/{id}/revoke', [\App\Http\Controllers\User\Developer\ApiKeyController::class, 'revoke'])->name('api-keys.revoke')->middleware('transaction.verified');
        Route::post('/api-keys/{id}/rotate', [\App\Http\Controllers\User\Developer\ApiKeyController::class, 'rotate'])->name('api-keys.rotate')->middleware('transaction.verified');

        // Webhooks
        Route::get('/webhooks', [\App\Http\Controllers\User\Developer\WebhookController::class, 'index'])->name('webhooks.index');
        Route::post('/webhooks', [\App\Http\Controllers\User\Developer\WebhookController::class, 'store'])->name('webhooks.store')->middleware('transaction.verified');
        Route::get('/webhooks/{id}', [\App\Http\Controllers\User\Developer\WebhookController::class, 'show'])->name('webhooks.show');
        Route::post('/webhooks/{id}/test', [\App\Http\Controllers\User\Developer\WebhookController::class, 'test'])->name('webhooks.test');
        Route::post('/webhooks/delivery/{id}/retry', [\App\Http\Controllers\User\Developer\WebhookController::class, 'retry'])->name('webhooks.delivery.retry');

        // Logs da API
        Route::get('/logs', [\App\Http\Controllers\User\Developer\ApiLogController::class, 'index'])->name('logs.index');
        Route::get('/logs/{id}', [\App\Http\Controllers\User\Developer\ApiLogController::class, 'show'])->name('logs.show');

        // Sandbox (API Explorer)
        Route::get('/sandbox', [\App\Http\Controllers\User\Developer\SandboxController::class, 'index'])->name('sandbox.index');
        
        // DocumentaÃ§Ã£o
        Route::get('/docs', [\App\Http\Controllers\User\Developer\SandboxController::class, 'docs'])->name('docs.index');
    });

    // ========================== Wallet Routes =============================
    Route::prefix('wallet')->as('wallet.')->controller(WalletController::class)->group(function () {
        Route::get('list', 'index')->name('index');
        Route::post('create', 'create')->name('create');
        Route::get('currency-info/{currency_id}', 'currencyInfo')->name('currency-info');
        Route::post('status', 'status')->name('status');

        // json response
        Route::get('supported-payment-methods/{wallet_id}', 'supportedPaymentMethods')->name('supported-payment-methods');
        Route::get('info/{role}/{wallet_id}', 'getWalletInfo')->name('info');
        Route::get('validate-recipient/{role}/{emailOrWalletId}', 'validateRecipient')->name('validate.recipient');
    });

    // ========================== Deposit Money Routes =============================
    Route::prefix('deposit')->as('deposit.')->controller(DepositController::class)->middleware(['prevent.duplicate'])->group(function () {
        Route::get('create', 'create')->name('create');
        Route::post('store', 'store')->name('store')->middleware('feature:deposit');
        Route::get('credentials/{method_id}', 'credentials')->name('credentials');
        Route::get('history', 'history')->name('history');
    });

    // ========================== Charge / CobranÃ§as Routes =============================
    Route::prefix('charge')->as('charge.')->controller(ChargeController::class)->group(function () {
        Route::get('index', 'index')->name('index');
        Route::get('create', 'create')->name('create');
        Route::post('store', 'store')->name('store');
        Route::get('{id}', 'show')->name('show');
    });

    Route::get('/pix', function (\Illuminate\Http\Request $request) {
        return redirect()->route('user.charge.index', array_merge($request->query(), ['method' => 'pix']), 301);
    })->name('pix.redirect');

    Route::get('/cartoes', function (\Illuminate\Http\Request $request) {
        return redirect()->route('user.charge.index', array_merge($request->query(), ['method' => 'card']), 301);
    })->name('card.redirect');

    Route::prefix('payment-links')->as('payment-links.')->controller(PaymentLinkController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        
        // Backward compatibility
        Route::get('/charges/create', function () {
            return redirect()->route('user.payment-links.create', ['type' => 'charge'], 301);
        })->name('charges.create');
        Route::post('/charges', 'storeCharge')->name('charges.store');
        
        Route::get('/subscriptions/create', function () {
            return redirect()->route('user.payment-links.create', ['type' => 'subscription'], 301);
        })->name('subscriptions.create');
        Route::post('/subscriptions', 'storeSubscription')->name('subscriptions.store');
        
        Route::post('/{paymentLink}/cancel', 'cancel')->name('cancel');
        Route::get('/{paymentLink}', 'show')->name('show');
    });

    // ========================== Boletos Routes =============================
    Route::prefix('boletos')->as('boleto.')->controller(BoletoController::class)->group(function () {
        Route::get('/', function (\Illuminate\Http\Request $request) {
            return redirect()->route('user.charge.index', array_merge($request->query(), ['method' => 'boleto']), 301);
        })->name('index');
        Route::post('{charge}/segunda-via', 'secondCopy')->name('second-copy');
    });

    // ========================== Customers Routes =============================
    Route::prefix('clientes')->as('customer.')->controller(CustomerController::class)->group(function () {
        Route::get('/', 'index')->name('index');
    });

    // ========================== Transfers Routes =============================
    Route::prefix('transferencias')->as('transfer.')->controller(TransferController::class)->group(function () {
        Route::get('/', 'index')->name('index');
    });

    // ========================== Customer Subscriptions Routes =============================
    Route::get('/subscriptions', function () {
        return redirect()->route('user.subscriptions.index', [], 301);
    })->name('subscriptions.redirect');

    Route::prefix('assinaturas')->as('subscriptions.')->controller(CustomerSubscriptionDashboardController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('{id}', 'show')->name('show');
        Route::post('{id}/cancel', 'cancel')->name('cancel');
    });

    // ========================== Transfer/Send Money Routes =============================
    Route::prefix('send-money')->as('send-money.')->controller(SendMoneyController::class)->middleware(['prevent.duplicate'])->group(function () {
        Route::get('create', 'create')->name('create');
        Route::post('store', 'store')->name('store')->middleware(['transaction.verified', 'kyc.verified', 'feature:send_money']);
    });

    // ========================== Withdraw Money Routes =============================
    Route::prefix('withdraw')->as('withdraw.')->controller(WithdrawController::class)->group(function () {
        Route::get('create', function () {
            return redirect()->route('user.transfer.index', [], 301);
        })->name('create');
        Route::post('store', 'store')->name('store')->middleware(['transaction.verified', 'prevent.duplicate', 'feature:withdraw']);
        Route::get('credentials-fields/{method_id}', 'credentialsFields')->name('credentials.fields');
        Route::get('account-info/{id}', [WithdrawAccountController::class, 'accountInfo'])->name('account.info');
        Route::resource('account', WithdrawAccountController::class)->except(['show', 'destroy']);
    });

    // ========================== Pix Keys Routes =============================
    Route::prefix('pix-keys')->as('pix-keys.')->controller(\App\Http\Controllers\Frontend\User\PixKeyController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('store', 'store')->name('store')->middleware('transaction.verified');
        Route::post('{id}/set-primary', 'setPrimary')->name('set-primary')->middleware('transaction.verified');
        Route::post('{id}/destroy', 'destroy')->name('destroy')->middleware('transaction.verified');
    });

    // ========================== Support Chat Routes (Floating Widget) =============================
    Route::prefix('support-chat')->as('support-chat.')->controller(\App\Http\Controllers\Frontend\SupportChatController::class)->group(function () {
        Route::get('state', 'state')->name('state');
        Route::get('conversations/{id}', 'conversationMessages')->name('conversations.show');
        Route::get('messages', 'messages')->name('messages');
        Route::post('send', 'send')->name('send');
        Route::get('unread-count', 'unreadCount')->name('unread-count');
        Route::get('attachment/{uuid}', 'downloadAttachment')
            ->name('attachment.download')
            ->middleware('throttle:60,1');
    });

    // ========================== Support Ticket Routes (kept for backward compat, not in menu) =============================
    Route::prefix('support-ticket')->as('support-ticket.')->controller(TicketController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('create', 'create')->name('create');
        Route::post('store', 'store')->name('store');
        Route::get('show/{ticket}', 'show')->name('show');
        Route::post('reply/{ticket}', 'reply')->name('reply');
        Route::get('close/{ticket}', 'close')->name('close');
    });

    // ========================== Transaction Routes =============================
    Route::prefix('transaction')->as('transaction.')->controller(TransactionController::class)->group(function () {
        Route::get('index', [TransactionController::class, 'index'])->name('index');
        Route::get('download-pdf/{trx_id}', [TransactionController::class, 'downloadPdf'])->name('download-pdf');
        Route::post('action', [TransactionController::class, 'handleAction'])->name('action');
    });

    // ========================== Referral Routes =============================
    Route::prefix('referral')->as('referral.')->controller(ReferralController::class)->group(function () {
        Route::get('index', 'index')->name('index');
    });

    // ========================== User Rank Routes =============================
    Route::get('rank-showcase', [UserRankController::class, 'showcase'])->name('rank.showcase');

    // ========================== Credentials Routes =============================
    Route::group(['prefix' => 'credentials'], function () {
        Route::get('/docs', [\App\Http\Controllers\Frontend\HomeController::class, 'docs'])->name('docs');

        Route::get('/health', function () {
            return response()->json([
                'status' => 'ok',
                'timestamp' => now()->toIso8601String(),
                'version' => 'v1.0.5'
            ]);
        });
        Route::get('/change/{lang?}', [\App\Http\Controllers\Frontend\HomeController::class, 'changeLanguage'])->name('lang');
        Route::get('/', [\App\Http\Controllers\Frontend\CredentialsController::class, 'index'])->name('credentials');
        Route::post('/webhook', [\App\Http\Controllers\Frontend\CredentialsController::class, 'updateWebhook'])->name('credentials.webhook')->middleware('transaction.verified');
    });


    // ========================== Merchant Routes (Descontinuado) =============================
    Route::any('merchant/{any?}', function () {
        abort(404, 'Módulo de Canais de Recebimento descontinuado.');
    })->where('any', '.*');


    // ========================== Merchant Disputes Routes =============================
    Route::controller(\App\Http\Controllers\User\DisputeController::class)->prefix('disputes')->as('disputes.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('{uuid}', 'show')->name('show');
        Route::post('{uuid}/message', 'sendMessage')->name('message.send');
        Route::post('{uuid}/evidence/{evidence_id}', 'uploadEvidence')->name('evidence.upload');
    });

    // Notification Management Routes
    Route::controller(\App\Http\Controllers\User\NotificationCenterController::class)->prefix('notifications')->as('notifications.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('{id}/read', 'markAsRead')->name('markAsRead');
        Route::post('read-all', 'markAllAsRead')->name('read-all');
    });
});

require __DIR__.'/connect.php';

/*
|--------------------------------------------------------------------------
| Instant Payment Notification (IPN)
|--------------------------------------------------------------------------
*/
// Changelog
Route::get('/changelog', function () {
    return view('frontend.changelog');
})->name('changelog');

// System Status
Route::get('/status', [\App\Http\Controllers\Frontend\SystemStatusController::class, 'index'])->name('status');


Route::match(['get', 'post'], '/ipn/{gateway}', [IPNController::class, 'handleIPN'])->name('ipn.handle');

// Payment Status Routes
Route::prefix('status')->as('status.')->controller(StatusController::class)->group(function () {
    Route::match(['get', 'post'], 'success', 'success')->name('success');
    Route::match(['get', 'post'], 'cancel', 'cancel')->name('cancel');
    Route::match(['get', 'post'], 'pending', 'pending')->name('pending');
    Route::match(['get', 'post'], 'callback', 'callback')->name('callback');
});

// ========================== Merchant Payment Routes =============================
Route::prefix('payment')->as('payment.')->controller(MerchantPaymentReceiveController::class)->group(function () {
    Route::get('checkout', 'paymentCheckoutSigned')->name('checkout')->middleware(['signed', 'throttle:30,1']);
    Route::get('pay/{merchant}/{token}', 'paymentCheckoutPublic')->name('pay')->middleware('throttle:30,1');
    Route::post('process', 'processPayment')->name('process')->middleware('throttle:8,1');
    Route::get('wallet-pay/{token}', 'walletPayment')->name('wallet.pay');
    Route::post('complete', 'completePayment')->name('complete');
    Route::match(['get', 'post'], 'with-account', 'payWithAccount')->name('with.account')->middleware('auth');
});

/*
|--------------------------------------------------------------------------
| Common Routes
|--------------------------------------------------------------------------
*/
Route::get('locale-set/{locale}', [LocaleController::class, 'setLocale'])->name('locale-set');
// Get currency rate with JSON response
Route::get('currency-rate/{fromCurrency}/{toCurrency}', [AppController::class, 'getCurrencyRate'])->name('get-currency-rate');
// Download File
Route::get('/file/download/{filePath}', [FileController::class, 'download'])->where('filePath', '.*')->name('file.download');

Route::prefix('summernote')->as('summernote.')->controller(SummernoteController::class)->group(function () {
    Route::post('image-upload', 'imageUpload')->name('image-upload');
    Route::post('image-delete', 'imageDelete')->name('image-delete');
});

/*
|--------------------------------------------------------------------------
| Merchant Api Documentation
|--------------------------------------------------------------------------
*/
Route::prefix('api-docs')->as('api-docs.')->group(function () {
    Route::get('/', function () {
        return redirect()->route('docs.index', [], 301);
    })->name('index');
});

/*
|--------------------------------------------------------------------------
| LAST: CMS Dynamic Page (Slug-Based)
|--------------------------------------------------------------------------
*/

Route::get('{slug}', PageController::class)
    ->where('slug', '^(?!admin|user|merchant|api|dashboard|payment|login|register).*$')
    ->name('page.view');
