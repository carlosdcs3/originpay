<?php

use App\Http\Controllers\Admin\ApiCredentialController;
use App\Http\Controllers\Admin\Billing\BillingDashboardController;
use App\Http\Controllers\Admin\Billing\PlanController;
use App\Http\Controllers\Admin\ComplianceController;
use App\Http\Controllers\Admin\OpsController;
use App\Http\Controllers\Admin\WebhookEndpointController;
use App\Http\Controllers\Backend\ActivityController;
use App\Http\Controllers\Backend\AdminController;
use App\Http\Controllers\Backend\AlertController;
use App\Http\Controllers\Backend\CardholdersController;
use App\Http\Controllers\Backend\CommandCenterController;
use App\Http\Controllers\Backend\ComplianceRiskController;
use App\Http\Controllers\Backend\CurrencyController;
use App\Http\Controllers\Backend\CustomLandingController;
use App\Http\Controllers\Backend\DashboardController;
use App\Http\Controllers\Backend\DepositController;
use App\Http\Controllers\Backend\DepositMethodController;
use App\Http\Controllers\Backend\Finance\DisputeController;
use App\Http\Controllers\Backend\Finance\FeeController;
use App\Http\Controllers\Backend\Finance\LedgerController;
use App\Http\Controllers\Backend\Finance\ReconciliationController;
use App\Http\Controllers\Backend\Finance\SettlementController;
use App\Http\Controllers\Backend\FinanceController;
use App\Http\Controllers\Backend\FooterItemController;
use App\Http\Controllers\Backend\FooterSectionController;
use App\Http\Controllers\Backend\Gateway\AdminChargeController;
use App\Http\Controllers\Backend\Gateway\AdminWithdrawalController;
use App\Http\Controllers\Backend\Gateway\GatewayLogController;
use App\Http\Controllers\Backend\GatewayFeeController;
use App\Http\Controllers\Backend\GatewayManagerController;
use App\Http\Controllers\Backend\IdentitySecurityController;
use App\Http\Controllers\Backend\KycController;
use App\Http\Controllers\Backend\KycDocumentAdminController;
use App\Http\Controllers\Backend\KycTemplateController;
use App\Http\Controllers\Backend\LanguageController;
use App\Http\Controllers\Backend\MerchantController;
use App\Http\Controllers\Backend\NavigationController;
use App\Http\Controllers\Backend\NotificationController;
use App\Http\Controllers\Backend\NotificationTemplateController;
use App\Http\Controllers\Backend\OpsIncidentController;
use App\Http\Controllers\Backend\PageComponentController;
use App\Http\Controllers\Backend\PageComponentRepeatedContentController;
use App\Http\Controllers\Backend\PageController;
use App\Http\Controllers\Backend\PaymentGatewayController;
use App\Http\Controllers\Backend\PlatformController;
use App\Http\Controllers\Backend\PlatformFeeController;
use App\Http\Controllers\Backend\PlatformFeeRuleController;
use App\Http\Controllers\Backend\ReferralController;
use App\Http\Controllers\Backend\ReportController;
use App\Http\Controllers\Backend\RoleController;
use App\Http\Controllers\Backend\SettingController;
use App\Http\Controllers\Backend\SiteSeoController;
use App\Http\Controllers\Backend\SocialController;
use App\Http\Controllers\Backend\StaffController;
use App\Http\Controllers\Backend\SubscriberController;
use App\Http\Controllers\Backend\SupportCategoryController;
use App\Http\Controllers\Backend\SupportChatAdminController;
use App\Http\Controllers\Backend\SupportInboxController;
use App\Http\Controllers\Backend\SystemAdminController;
use App\Http\Controllers\Backend\SystemHealthController;
use App\Http\Controllers\Backend\TicketController;
use App\Http\Controllers\Backend\TransactionController;
use App\Http\Controllers\Backend\UserController;
use App\Http\Controllers\Backend\UserManageController;
use App\Http\Controllers\Backend\UserRankController;
use App\Http\Controllers\Backend\VirtualCardController;
use App\Http\Controllers\Backend\VirtualCardFeeSettingController;
use App\Http\Controllers\Backend\WebhookAdminController;
use App\Http\Controllers\Backend\WithdrawController;
use App\Http\Controllers\Backend\WithdrawMethodController;
use App\Http\Controllers\Backend\WithdrawScheduleController;

$adminPrefix = setting('admin_prefix', 'admin') ?: 'admin';
$retiredAdminModule = static function (...$args) {
    abort(404);
};
Route::prefix($adminPrefix)->as('admin.')->group(function () use ($retiredAdminModule) {

    // ========================== ðŸš§ Stub Route =============================
    Route::get('/stub', $retiredAdminModule)->name('stub');

    // ========================== ðŸ’° Finance (Enterprise) =============================
    Route::prefix('finance')->as('finance.')->group(function () {
        Route::get('/ledger', [LedgerController::class, 'index'])->name('ledger');
        Route::get('/reconciliation', [ReconciliationController::class, 'index'])->name('reconciliation');
        Route::get('/transactions', [App\Http\Controllers\Backend\Finance\TransactionController::class, 'index'])->name('transactions.index');
        Route::get('/chargebacks', [DisputeController::class, 'index'])->name('chargebacks');
        Route::get('/chargebacks/{uuid}', [DisputeController::class, 'show'])->name('chargebacks.show');
        Route::post('/chargebacks/{uuid}/message', [DisputeController::class, 'sendMessage'])->name('chargebacks.message');
        Route::post('/chargebacks/{uuid}/request-document', [DisputeController::class, 'requestDocument'])->name('chargebacks.request_document');
        Route::post('/chargebacks/{uuid}/notify-merchant', [DisputeController::class, 'notifyMerchant'])->name('chargebacks.notify_merchant');
        Route::post('/chargebacks/{uuid}/send-gateway', [DisputeController::class, 'sendGateway'])->name('chargebacks.send_gateway');
        Route::post('/chargebacks/{uuid}/release-retention', [DisputeController::class, 'releaseRetention'])->name('chargebacks.release_retention');
        Route::post('/chargebacks/{uuid}/close', [DisputeController::class, 'closeDispute'])->name('chargebacks.close');
        Route::get('/settlements', [SettlementController::class, 'index'])->name('settlements.index');
        Route::post('/settlements/{settlement}/pay', [SettlementController::class, 'pay'])->middleware('permission:settlements.pay')->name('settlements.pay');
        Route::get('/fees', [FeeController::class, 'index'])->name('fees.index');
    });

    // ========================== ðŸŒŸ Dashboard =============================
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Ops Dashboard & Quick Actions
    Route::get('/ops-dashboard', fn () => redirect()->route('admin.operations.command'))->name('ops.legacy_dashboard');
    Route::post('/ops-dashboard/kill-withdrawals', $retiredAdminModule)->name('ops.toggle_withdrawals');
    Route::post('/ops-dashboard/run-reconciliation', $retiredAdminModule)->name('ops.run_reconciliation');
    Route::post('/ops-dashboard/verify-ledger', $retiredAdminModule)->name('ops.verify_ledger');

    Route::get('/command-center', [CommandCenterController::class, 'index'])->name('operations.command');
    Route::get('/alerts', [AlertController::class, 'index'])->name('alerts.index');

    Route::prefix('platform-fees')->as('platform-fees.')->controller(PlatformFeeRuleController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/global', 'storeGlobal')->name('global.store');
        Route::post('/merchant', 'storeMerchant')->name('merchant.store');
        Route::post('/simulate', 'simulate')->name('simulate');
        Route::patch('/{rule}/deactivate', 'deactivate')->name('deactivate');
    });

    // ========================== ðŸ”Œ Gateways =============================
    Route::prefix('gateway')->as('gateway.')->controller(GatewayManagerController::class)->group(function () use ($retiredAdminModule) {
        Route::get('/routing', fn () => redirect()->route('admin.payment.gateway.index'))->name('routing');
        Route::post('/routing', $retiredAdminModule)->name('routing.store');
        Route::get('/fallback', fn () => redirect()->route('admin.payment.gateway.index'))->name('fallback');
        Route::get('/capabilities', fn () => redirect()->route('admin.payment.gateway.index'))->name('capabilities');
        Route::get('/connectivity', fn () => redirect()->route('admin.payment.gateway.index'))->name('connectivity');
        Route::get('/monitor/{id}', fn ($id) => redirect()->route('admin.payment.gateway.overview', $id))->name('monitor.show');
    });

    // ========================== ðŸ’° Financeiro =============================
    Route::prefix('finance')->as('finance.')->controller(FinanceController::class)->group(function () {
        Route::get('/liquidacao', 'liquidacao')->name('liquidacao');
        Route::get('/repasses', 'repasses')->name('repasses');
        Route::get('/balances', 'balances')->name('balances');
        Route::get('/refunds', 'refunds')->name('refunds');
    });

    // Ledger Engine (Novas views v2.0)
    Route::prefix('ledger')->as('ledger.')->controller(App\Http\Controllers\Admin\LedgerController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/timeline/{charge_id}', 'timeline')->name('timeline');
        Route::post('/export', 'export')->name('export');
    });

    // ========================== ðŸ›¡ï¸ Compliance & Security ==========================
    Route::prefix('compliance')->as('compliance.')->controller(ComplianceController::class)->group(function () {
        Route::get('/', 'index')->name('dashboard');
    });

    Route::prefix('identity')->as('identity.')->controller(IdentitySecurityController::class)->group(function () {
        Route::get('/sessions', 'sessions')->name('sessions');
        Route::get('/login-logs', 'loginLogs')->name('login_logs');
        Route::get('/devices', 'devices')->name('devices');
    });

    // ========================== ðŸ‘¥ User Management ==========================
    Route::prefix('user')->as('user.')->controller(UserManageController::class)->group(function () {
        Route::get('manage/{username}/{param?}', 'manageUser')->name('manage');
        Route::get('login/{id}', 'loginAsUser')->name('login');
        Route::get('mail-send/all', 'mailSendAll')->name('mail-send.all');

        Route::post('feature-status/update', 'updateFeatureStatus')->name('feature-status.update');
        Route::post('update-balance', 'updateBalance')->middleware('permission:balances.adjust')->name('update-balance');
        Route::post('status-update/{id}', 'statusUpdate')->name('status-update');
        Route::post('password-update/{id}', 'passwordUpdate')->name('password-update');
        Route::post('mail-send', 'mailSend')->name('mail-send');

        Route::put('update-info/{id}', 'infoUpdate')->name('update-info');
    });

    // ðŸ”¹ User Listings (GET)
    Route::prefix('user')->as('user.')->controller(UserController::class)->group(function () {
        Route::get('active', 'activeUser')->name('active');
        Route::get('suspended', 'suspendedUser')->name('suspended');
        Route::get('unverified', 'unverifiedUser')->name('unverified');
        Route::get('kyc-unverified', 'kycUnverifiedUser')->name('kyc-unverified');
        Route::get('{id}/transaction-stats', 'transactionStats')->name('transaction-stats');
        Route::post('{id}/convert-to-merchant', 'convertToMerchant')->name('convert-to-merchant');
    });

    // ðŸ”¹ User Resources
    Route::resource('user', UserController::class)->except(['show', 'create', 'edit']);

    // =============================== ðŸª Merchant Management =================================
    Route::prefix('merchant')->as('merchant.')->controller(MerchantController::class)->group(function () {
        Route::get('pending', 'pendingMerchant')->name('pending');
        Route::get('approved', 'approvedMerchant')->name('approved');
        Route::get('rejected', 'rejectedMerchant')->name('rejected');
        Route::post('request-action', 'merchantAction')->name('request-action');
    });

    // ðŸ”¹ Merchant Resources
    Route::resource('merchant', MerchantController::class);

    // =============================== ðŸ”‘ API Credentials (Sprint 7) ========================
    Route::prefix('api-credentials')->as('api-credentials.')->controller(ApiCredentialController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/generate', 'generate')->middleware('permission:api-credentials.issue')->name('generate');
        Route::post('/{id}/rotate', 'rotate')->middleware('permission:api-credentials.rotate')->name('rotate');
        Route::post('/{id}/revoke', 'revoke')->middleware('permission:api-credentials.revoke')->name('revoke');
    });

    // =============================== ðŸ”‘ Webhooks (Sprint 8) ========================
    Route::prefix('webhook-endpoints')->as('webhook-endpoints.')->controller(WebhookEndpointController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->middleware('permission:webhooks.manage')->name('store');
        Route::put('/{id}', 'update')->middleware('permission:webhooks.manage')->name('update');
        Route::delete('/{id}', 'destroy')->middleware('permission:webhooks.manage')->name('destroy');
        Route::post('/{id}/rotate-secret', 'rotateSecret')->middleware('permission:webhooks.manage')->name('rotate-secret');
        Route::post('/{id}/test', 'testWebhook')->name('test');
    });

    // ================================ ðŸ”‘ KYC Management   =================================
    Route::prefix('kyc')->as('kyc.')->group(function () {
        Route::controller(KycController::class)->group(function () {
            Route::get('pending', 'pending')->name('pending');
            Route::get('index', 'index')->name('index');
            Route::post('action', 'requestAction')->name('request-action');
        });
        Route::get('document', [KycDocumentAdminController::class, 'download'])->name('document.download');
        Route::resource('template', KycTemplateController::class)->except(['show', 'create']);
    });

    // ================================ ðŸ›¡ï¸ Compliance & Risk =================================
    Route::prefix('compliance')->as('compliance.')->controller(ComplianceRiskController::class)->group(function () {
        Route::get('risk-score', 'riskScore')->name('risk_score');
        Route::get('fraud-engine', 'fraudEngine')->name('fraud_engine');
        Route::get('anomalies', 'anomalies')->name('anomalies');
        Route::post('anomalies/{id}/resolve', 'resolveAnomaly')->name('anomalies.resolve');
        Route::get('blacklist', 'blacklist')->name('blacklist');
        Route::get('whitelist', 'whitelist')->name('whitelist');
        Route::get('audit', 'audit')->name('audit');
    });

    // ================================ ðŸ“ User/Merchant Activity History  =================================
    Route::get('activity-log', [ActivityController::class, 'index'])->name('activity-log');

    // ================================ ðŸ‘¤ Admin Profile  =================================
    Route::prefix('profile')->as('profile.')->controller(AdminController::class)->group(function () {
        Route::get('profile', 'profile')->name('view');
        Route::post('info-update', 'updateInfo')->name('info.update');
        Route::post('password-update', 'updatePassword')->name('password.update');

        // Two-Factor Authentication
        Route::prefix('2fa')->as('2fa.')->group(function () {
            Route::post('enable', 'enable2fa')->name('enable');
            Route::post('disable', 'disable2fa')->name('disable');
        });
    });

    // ======================== Ã°Å¸â€˜Â¨Ã¢â‚¬ Ã°Å¸â€™Â¼ Staff Management  ==============================
    Route::resource('staff', StaffController::class)->except(['show', 'create', 'destroy']);
    Route::resource('role', RoleController::class);

    // ======================== ðŸ’° Currency Management  ==============================
    Route::resource('currency', CurrencyController::class);

    // ================================== ðŸ’³ Payment Gateway ===============================
    Route::prefix('payment')->as('payment.')->group(function () {
        Route::resource('gateway', PaymentGatewayController::class)->only(['index', 'store', 'edit', 'update']);
        Route::get('gateway-currency/{gateway_id}', [PaymentGatewayController::class, 'gatewayCurrency'])->name('gateway-currency');

        // UX Enterprise Settings
        Route::get('gateway/{id}/settings', function ($id) {
            return redirect()->route('admin.payment.gateway.overview', $id);
        })->name('gateway.settings');

        Route::get('gateway/{id}/overview', [PaymentGatewayController::class, 'settings'])->name('gateway.overview');
        Route::get('gateway/{id}/credentials', [PaymentGatewayController::class, 'settings'])->name('gateway.credentials');
        Route::get('gateway/{id}/charge-methods', [PaymentGatewayController::class, 'settings'])->name('gateway.charge-methods');
        Route::get('gateway/{id}/withdraw-methods', [PaymentGatewayController::class, 'settings'])->name('gateway.withdraw-methods');
        Route::get('gateway/{id}/fees-limits', [PaymentGatewayController::class, 'settings'])->name('gateway.fees-limits');
        Route::get('gateway/{id}/webhooks', [PaymentGatewayController::class, 'settings'])->name('gateway.webhooks');
        Route::get('gateway/{id}/health', [PaymentGatewayController::class, 'settings'])->name('gateway.health');
        Route::get('gateway/{id}/routing', [PaymentGatewayController::class, 'settings'])->name('gateway.routing');
        Route::get('gateway/{id}/logs', [PaymentGatewayController::class, 'settings'])->name('gateway.logs');
        Route::post('gateway/{id}/credentials', [PaymentGatewayController::class, 'updateCredentials'])->middleware('permission:gateway-credentials.manage')->name('gateway.update-credentials');
        Route::post('gateway/{id}/pix-charge', [PaymentGatewayController::class, 'updatePixCharge'])->name('gateway.update-pix-charge');
        Route::post('gateway/{id}/pix-withdraw', [PaymentGatewayController::class, 'updatePixWithdraw'])->name('gateway.update-pix-withdraw');
        Route::post('gateway/{id}/deposit-method', [PaymentGatewayController::class, 'storeDepositMethod'])->name('gateway.store-deposit-method');
        Route::post('gateway/{id}/withdraw-method', [PaymentGatewayController::class, 'storeWithdrawMethod'])->name('gateway.store-withdraw-method');
        Route::post('gateway/{id}/taxes', [PaymentGatewayController::class, 'updateTaxes'])->middleware('permission:gateway-fees.manage')->name('gateway.update-taxes');
        Route::post('gateway/{id}/routing', [PaymentGatewayController::class, 'updateRouting'])->middleware('permission:gateway-limits.manage')->name('gateway.update-routing');
    });

    // ======================== ðŸ’³ Virtual Card Management  ===============================
    Route::prefix('virtual-card')->name('virtual-card.')->controller(VirtualCardController::class)->group(function () {
        // Card Requests
        Route::prefix('requests')->name('requests.')->group(function () {
            Route::get('awaiting', 'requestAwaiting')->name('awaiting');
            Route::get('all', 'requestAll')->name('all');
            Route::post('{uuid}/review', 'review')->name('review');
        });

        // Cardholder management routes
        Route::prefix('cardholders')->name('cardholders.')->controller(CardholdersController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/{id}/action', 'action')->name('action');
        });

        // Card Management
        Route::get('list', 'virtualCardList')->name('list');
        Route::post('update-status', 'statusUpdate')->name('update-status');

        // Provider Configuration
        Route::prefix('provider')->name('provider.')->group(function () {
            Route::get('/', 'provider')->name('index');
            Route::get('manage/{id}', 'providerManage')->name('manage');
            Route::put('update/{provider}', 'providerUpdate')->name('update');
        });

        // Virtual Card Settings
        Route::resource('fee-settings', VirtualCardFeeSettingController::class)
            ->names('fee-settings');
    });

    // ======================== Ã°Å¸â€™Â° Deposit Management  ===============================
    Route::prefix('deposit')->as('deposit.')->group(function () {
        Route::controller(DepositController::class)->group(function () {
            Route::get('manual-request', 'manualRequest')->name('manual-request');
            Route::get('history', 'history')->name('history');
            Route::post('request-action', 'requestAction')->name('request-action');
        });
        Route::resource('method', DepositMethodController::class)->except('show');
    });

    // ======================== Ã°Å¸ Â¦ Withdraw Management   ===============================
    Route::prefix('withdraw')->as('withdraw.')->group(function () {
        Route::controller(WithdrawController::class)->group(function () {
            Route::get('manual-request', 'manualRequest')->name('manual-request');
            Route::post('request-action', 'requestAction')->name('request-action');
        });
        Route::resource('method', WithdrawMethodController::class)->except('show');
        Route::controller(WithdrawScheduleController::class)->group(function () {
            Route::get('schedule', 'index')->name('schedule');
            Route::post('schedule-update', 'update')->name('schedule.update');
        });
    });

    // ======================== Ã°Å¸â€Å’ Gateway / OperaÃƒÂ§ÃƒÂ£o ===============================

    Route::prefix('gateway')->as('gateway.')->group(function () {
        Route::get('charges', [AdminChargeController::class, 'index'])->name('charges.index');
        Route::get('charges/{id}', [AdminChargeController::class, 'show'])->name('charges.show');

        Route::get('withdrawals', [AdminWithdrawalController::class, 'index'])->name('withdrawals.index');
        Route::get('withdrawals/{id}', [AdminWithdrawalController::class, 'show'])->name('withdrawals.show');

        // Dashboard de SaÃºde / Multi-PSP Monitor
        Route::get('monitor', fn () => redirect()->route('admin.payment.gateway.index'))->name('monitor.index');
    });

    // ======================== ðŸ† Referral Management   ===============================
    Route::prefix('referral')->as('referral.')->group(function () {
        Route::get('index', [ReferralController::class, 'index'])->name('index');
        Route::post('store', [ReferralController::class, 'store'])->name('store');
        Route::get('edit/{id}', [ReferralController::class, 'edit'])->name('edit');
        Route::post('update/{id}', [ReferralController::class, 'update'])->name('update');
        Route::patch('status-update/{type}/{status}', [ReferralController::class, 'statusUpdate'])->name('status-update');
        Route::delete('delete/{id}', [ReferralController::class, 'destroy'])->name('delete');
        Route::get('card-content', [ReferralController::class, 'cardContent'])->name('card.content');
        Route::post('content-update', [ReferralController::class, 'contentUpdate'])->name('content.update');
    });

    // ======================== User Ranking Management   ===============================
    Route::resource('ranking', UserRankController::class)->except(['create', 'show', 'destroy']);

    // ======================== ðŸ”„ Transaction Management  ===============================
    Route::get('transaction', [TransactionController::class, 'index'])->name('transaction');

    // ======================== âš™ï¸ Site Management  ==============================
    Route::prefix('settings')->as('settings.')->group(function () {
        Route::resource('site', SettingController::class)->only(['index', 'update']);
        Route::get('platform', function () {
            return view('backend.settings.platform');
        })->name('platform.index');

        // Legacy platform fee settings redirect to the canonical commercial fee rules screen.
        Route::get('platform-fee', fn () => redirect()->route('admin.platform-fees.index'))->name('platform_fee.index');
        Route::post('platform-fee/pix', [PlatformFeeController::class, 'updatePix'])->name('platform_fee.update_pix');
        Route::post('platform-fee/boleto', [PlatformFeeController::class, 'updateBoleto'])->name('platform_fee.update_boleto');
        Route::post('platform-fee/card', [PlatformFeeController::class, 'updateCard'])->name('platform_fee.update_card');

    });

    // ======================== ðŸŽ« Support Ticket  ==============================
    Route::prefix('support-ticket')->as('support-ticket.')->controller(TicketController::class)->group(function () {
        Route::resource('category', SupportCategoryController::class)->except(['show', 'create']);
        Route::get('pending', 'pendingTicket')->name('new');
        Route::get('inprogress', 'inprogress')->name('inprogress');
        Route::get('close', 'closeTicket')->name('close');
        Route::get('history', 'history')->name('history');
        Route::get('show/{ticket}', 'ticketShow')->name('show');
        Route::post('reply/{ticket}', 'ticketReplyStore')->name('reply');
        Route::put('status-update/{ticket_id}', 'statusUpdate')->name('status-update');
    });

    // ======================== ðŸ”” Notification Management  ==============================
    Route::prefix('notifications')->name('notifications.')->group(function () {

        // ðŸ”¹ Notification management routes (prefix: notification)
        Route::controller(NotificationController::class)->group(function () {
            // ðŸ”¹ Admin-triggered user notification
            Route::get('to-users', 'notifyUsers')->name('notifyToUser');
            Route::post('to-users/send', 'sendNotification')->name('notifyToUser.send');

            // ðŸ”¹ Display notifications
            Route::get('/', 'index')->name('index');
            Route::get('/recent', 'recent')->name('recent');

            // ðŸ”¹ State-changing actions (use PATCH)
            Route::patch('/{notification}/read', 'markAsRead')->name('markAsRead');
            Route::patch('/read-all', 'markAllAsRead')->name('markAllAsRead');
        });

        // ðŸ”¹ Template management routes (prefix: template)
        Route::prefix('template')->name('template.')->controller(NotificationTemplateController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('{template}/edit', 'edit')->name('edit');
            Route::put('{template}/channel/{channel}', 'updateChannel')->name('update');
        });

    });

    // ======================= ðŸŒ Language Management =======================
    Route::prefix('language')->name('language.')->controller(LanguageController::class)->group(function () {
        Route::get('translate/{code}', 'translate')->name('translate');
        Route::post('translate-update', 'translatedUpdate')->name('translate-update');
        Route::get('sync-missing-keys', 'syncMissingKeys')->name('sync-missing-keys');
    });
    // ðŸ”¹ Resource Language CRUD
    Route::resource('language', LanguageController::class);

    // ======================= ðŸŽ‰ Custom Landing Page =======================
    Route::resource('custom-landing', CustomLandingController::class);
    Route::prefix('custom-landing')->name('custom-landing.')->controller(CustomLandingController::class)->group(function () {
        Route::get('{id}/manage-html', 'manageHtml')->name('manage-html');
        Route::post('{id}/manage-html-update', 'manageHtmlUpdate')->name('manage-html-update')->withoutMiddleware('XSS');
    });

    // ======================= ðŸ§­ Navigation Management =======================
    Route::prefix('navigation')->as('navigation.')->controller(NavigationController::class)->group(function () {
        Route::resource('site', NavigationController::class)->except(['create', 'show']);
        Route::post('position-update', 'positionUpdate')->name('position-update');
    });
    // ======================= ðŸ“¢ Marketing =======================
    Route::prefix('marketing')->as('marketing.')->group(function () {
        Route::get('/campaigns', function () {
            return view('backend.marketing.campaigns.index');
        })->name('campaigns.index');
    });

    // ======================= ðŸ“„ Page Management =======================
    Route::prefix('page')->as('page.')->group(function () {
        Route::resource('site', PageController::class)->except('show');
        Route::resource('component', PageComponentController::class)->except('show')->withoutMiddleware('XSS');
        Route::resource('component-repeated-content', PageComponentRepeatedContentController::class)->only(['edit', 'store', 'update', 'destroy']);

        // ðŸ”¹ Page Footer
        Route::prefix('footer')->as('footer.')->group(function () {

            // Footer Section Routes
            Route::resource('section', FooterSectionController::class)->except(['show', 'create']);
            Route::post('section/position-update', [FooterSectionController::class, 'positionUpdate'])->name('section.position-update');

            // Footer Item Routes
            Route::resource('item', FooterItemController::class)->except(['show', 'create']);
            Route::post('item/position-update', [FooterItemController::class, 'positionUpdate'])->name('item.position-update');

        });
    });

    // ======================== ðŸ“± Social Management ========================
    Route::resource('social', SocialController::class)->except(['create', 'show']);

    // ======================= ðŸ” Site SEO Management =======================
    Route::resource('site-seo', SiteSeoController::class)->except(['show']);

    // ======================= ðŸ“§ Subscriber Management =======================
    Route::prefix('subscriber')->as('subscriber.')->controller(SubscriberController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('send-mail', 'sendMail')->name('send-mail');
        Route::delete('delete/{id}', 'deleteSubscriber')->name('delete');
    });

    // ======================= ðŸš€ Application Tools =======================

    // ======================= ðŸ›¡ï¸  Webhooks & DLQ Management =======================
    Route::prefix('webhooks')->as('webhooks.')->controller(WebhookAdminController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/events/{id}', 'showEvent')->name('showEvent');
        Route::get('/dlqs/{id}', 'showDlq')->name('showDlq');
        Route::post('/reprocess/single/{id}', 'reprocessSingle')->middleware('permission:webhooks.dlq.reprocess')->name('reprocessSingle');
        Route::post('/reprocess/batch', 'reprocessBatch')->middleware('permission:webhooks.dlq.reprocess')->name('reprocessBatch');
        Route::post('/resolve/{id}', 'resolveManual')->name('resolveManual');
    });

    // ======================= ðŸŒ¡ï¸ System Health =======================
    Route::prefix('system')->as('system.')->group(function () {
        Route::get('/health', [SystemHealthController::class, 'index'])->name('health.index');
        Route::post('/health-check', [SystemHealthController::class, 'healthCheck'])->name('healthCheck');
    });

    // ======================= ðŸ’° Gateway Fees & Tariffs =======================
    Route::get('finance/tariffs', function () {
        return redirect()->route('admin.gateway-fees.index');
    })->name('finance.tariffs');

    Route::get('logs', $retiredAdminModule)->name('logs.index');

    Route::prefix('gateway-fees')->as('gateway-fees.')->group(function () {
        Route::get('/', [GatewayFeeController::class, 'index'])->name('index');
        Route::get('/{id}/edit', [GatewayFeeController::class, 'edit'])->name('edit');
        Route::post('/{id}', [GatewayFeeController::class, 'update'])->middleware('permission:gateway-fees.manage')->name('update');
        Route::post('/simulate/run', [GatewayFeeController::class, 'simulate'])->name('simulate');
    });

    // ======================= ðŸ’¬ INBOX & ATENDIMENTO (Epic 6) =======================
    Route::prefix('inbox')->as('inbox.')->controller(SupportInboxController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/unread', 'unread')->name('unread');
        Route::get('/active', 'active')->name('active');
        Route::get('/resolved', 'resolved')->name('resolved');
        Route::get('/knowledge-base', 'knowledgeBase')->name('knowledge_base');
        Route::get('/macros', 'macros')->name('macros');
        Route::get('/metrics', 'metrics')->name('metrics');
    });

    // ======================= âš™ï¸ PLATAFORMA (Epic 7) =======================
    Route::prefix('platform')->as('platform.')->controller(PlatformController::class)->group(function () {
        Route::get('/feature-flags', 'featureFlags')->name('feature_flags');
        Route::get('/versioning', 'versioning')->name('versioning');
        Route::get('/changelog', 'changelog')->name('changelog');
    });

    // ======================= ðŸ’¬ Support Chat =======================
    Route::prefix('support-chat')->as('support-chat.')->controller(SupportChatAdminController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/unread-count', 'unreadCount')->name('unread-count');
        Route::get('/notifications', 'notifications')->name('notifications');
        Route::get('/attachment/{uuid}', 'downloadAttachment')->name('attachment.download');
        Route::get('/{conversationId}', 'show')->name('show');
        Route::get('/{conversationId}/fetch', 'fetch')->name('fetch');
        Route::post('/{conversationId}/reply', 'reply')->name('reply');
        Route::post('/{conversationId}/close', 'close')->name('close');
        Route::post('/{conversationId}/reopen', 'reopen')->name('reopen');
    });

    // ======================= ðŸ†• Refactored Architecture Routes =======================
    Route::prefix('gateway')->as('gateway.')->group(function () {
        Route::get('logs', [GatewayLogController::class, 'index'])->name('logs');
    });

    Route::prefix('reports')->as('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
    });

    Route::prefix('system')->as('system.')->group(function () {
        Route::get('queues', [SystemAdminController::class, 'queues'])->name('queues');
    });

    // ======================= ðŸ’³ Billing Enterprise =======================
    Route::prefix('billing')->as('billing.')->group(function () {
        Route::get('/', [BillingDashboardController::class, 'index'])->name('dashboard');
        Route::resource('plans', PlanController::class)->except(['show']);
    });

    // ========================== ðŸ› ï¸ Platform Operations (Ops) =======================
    Route::prefix('ops')->as('ops.')->controller(OpsController::class)->group(function () {
        Route::get('dashboard', 'dashboard')->name('dashboard');
        Route::get('alerts', 'getAlerts')->name('alerts');
        Route::post('alerts/{id}/resolve', 'resolveAlert')->name('alerts.resolve');
        Route::get('metrics/api', 'getApiMetrics')->name('metrics.api');
        Route::get('metrics/gateways', 'getGatewayMetrics')->name('metrics.gateways');
        Route::get('metrics/queues', 'getQueueMetrics')->name('metrics.queues');
        Route::get('metrics/scheduler', 'getSchedulerMetrics')->name('metrics.scheduler');

        // Sprint 3.1: Enterprise Expansion
        Route::get('incidents/data', 'getIncidents')->name('incidents.data');
        Route::get('incidents', [OpsIncidentController::class, 'index'])->name('incidents');
        Route::get('maintenance', 'getMaintenanceWindows')->name('maintenance');
        Route::get('metrics/sla', 'getSlaMetrics')->name('metrics.sla');
        Route::get('metrics/costs', 'getPlatformCosts')->name('metrics.costs');
        Route::get('metrics/features', 'getFeatureUsage')->name('metrics.features');
        Route::get('metrics/circuit-breaker', 'getCircuitBreakerStates')->name('metrics.circuit_breaker');
    });

});
