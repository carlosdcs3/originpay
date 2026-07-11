<?php

namespace Tests\Feature;

use Tests\TestCase;

class AdminContentAuditTest extends TestCase
{
    public function test_primary_admin_views_do_not_use_legacy_brand_or_fake_ctas(): void
    {
        $files = [
            resource_path('views/backend/dashboard/index.blade.php'),
            resource_path('views/backend/payment_gateway/index.blade.php'),
            resource_path('views/backend/payment_gateway/settings.blade.php'),
            resource_path('views/backend/reports/index.blade.php'),
            resource_path('views/backend/marketing/campaigns/index.blade.php'),
            resource_path('views/backend/notifications/template/index.blade.php'),
            resource_path('views/backend/gateway/withdrawals/index.blade.php'),
            resource_path('views/backend/gateway/charges/index.blade.php'),
            resource_path('views/backend/operations/command_center.blade.php'),
            resource_path('views/backend/operations/alerts.blade.php'),
            resource_path('views/backend/system/health.blade.php'),
            resource_path('views/backend/settings/site/index.blade.php'),
            resource_path('views/backend/webhooks/index.blade.php'),
            resource_path('views/backend/finance/balances.blade.php'),
        ];

        $forbiddenStrings = [
            'DigiSynk',
            'Developer Portal',
            'Developer Overview',
            'API Keys',
            'Audit Logs',
            'Site Settings',
            'Gateway Settings:',
            'javascript:void(0)',
            'onclick="alert(',
            '(Em breve)',
        ];

        foreach ($files as $file) {
            $this->assertFileExists($file);
            $contents = file_get_contents($file);

            foreach ($forbiddenStrings as $forbiddenString) {
                $this->assertStringNotContainsString($forbiddenString, $contents, "Found forbidden string [{$forbiddenString}] in [{$file}]");
            }
        }
    }

    public function test_legacy_admin_api_and_gateway_artifacts_were_removed(): void
    {
        $deletedPaths = [
            app_path('Http/Controllers/Backend/ApiController.php'),
            app_path('Http/Controllers/Backend/ApiLogController.php'),
            app_path('Http/Controllers/Backend/DigisynkGatewayController.php'),
            resource_path('views/backend/api/index.blade.php'),
            resource_path('views/backend/api/overview.blade.php'),
            resource_path('views/backend/api/keys.blade.php'),
            resource_path('views/backend/api/sandbox.blade.php'),
            resource_path('views/backend/api/docs.blade.php'),
            resource_path('views/backend/api/logs.blade.php'),
            resource_path('views/backend/gateway/digisynk.blade.php'),
            resource_path('views/backend/payment_gateway/partials/_global_tabs.blade.php'),
            resource_path('views/backend/gateway/capabilities.blade.php'),
            resource_path('views/backend/gateway/connectivity.blade.php'),
            resource_path('views/backend/gateway/fallback.blade.php'),
            resource_path('views/backend/gateway/monitor.blade.php'),
            resource_path('views/backend/gateway/routing.blade.php'),
            resource_path('views/backend/gateway/show.blade.php'),
        ];

        foreach ($deletedPaths as $path) {
            $this->assertFileDoesNotExist($path, "Legacy artifact still exists: [{$path}]");
        }
    }

    public function test_admin_route_file_no_longer_declares_legacy_api_cluster(): void
    {
        $routes = file_get_contents(base_path('routes/admin.php'));

        $this->assertStringNotContainsString("Route::prefix('api-dev')", $routes);
        $this->assertStringNotContainsString("DigisynkGatewayController", $routes);
        $this->assertStringNotContainsString("redirect()->route('admin.api.logs')", $routes);
    }
}
