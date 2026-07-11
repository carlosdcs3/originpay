<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class EnterpriseRbacSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Permissions
        $permissions = [
            'dashboard.view',
            'users.view',
            'users.manage',
            'merchants.view',
            'merchants.manage',
            'charges.view',
            'charges.refund',
            'withdrawals.view',
            'withdrawals.approve',
            'ledger.view',
            'gateways.view',
            'gateways.manage',
            'webhooks.view',
            'webhooks.replay',
            'kyc.view',
            'kyc.approve',
            'kyc.reject',
            'fraud.view',
            'fraud.manage',
            'audit.view',
            'settings.manage',
            'roles.manage',
            'ops.view',
            'dlq.view',
            'logs.view',
            // --- NEW MODULES ---
            'platform-fees.view',
            'platform-fees.manage',
            'subscriptions.view',
            'subscriptions.manage',
            'billing.view',
            'billing.manage',
            'compliance.view',
            'compliance.manage',
            'system.view',
            'settlements.read',
            'settlements.pay',
            'balances.adjust',
            'gateway-credentials.manage',
            'gateway-fees.manage',
            'gateway-limits.manage',
            'api-credentials.issue',
            'api-credentials.rotate',
            'api-credentials.revoke',
            'webhooks.manage',
            'webhooks.dlq.reprocess',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'admin'],
                ['category' => 'enterprise']
            );
        }

        // Create Roles and Assign Permissions
        $rolesData = [
            'Owner' => $permissions, // Total access
            'Financeiro' => [
                'dashboard.view', 'charges.view', 'charges.refund', 'withdrawals.view', 'withdrawals.approve', 'ledger.view', 'platform-fees.view', 'settlements.read', 'settlements.pay', 'balances.adjust', 'gateway-fees.manage', 'gateway-limits.manage',
            ],
            'Compliance' => [
                'dashboard.view', 'users.view', 'merchants.view', 'kyc.view', 'kyc.approve', 'kyc.reject', 'fraud.view', 'fraud.manage', 'audit.view',
            ],
            'Suporte' => [
                'dashboard.view', 'users.view', 'merchants.view', 'charges.view', 'withdrawals.view',
            ],
            'Operações' => [
                'dashboard.view', 'ops.view', 'gateways.view', 'logs.view', 'webhooks.dlq.reprocess',
            ],
            'Developer' => [
                'dashboard.view', 'webhooks.view', 'webhooks.replay', 'logs.view', 'dlq.view', 'gateway-credentials.manage', 'api-credentials.issue', 'api-credentials.rotate', 'api-credentials.revoke', 'webhooks.manage', 'webhooks.dlq.reprocess',
            ],
            'Auditoria' => [
                'dashboard.view', 'audit.view', 'users.view', 'merchants.view', 'charges.view', 'ledger.view', 'logs.view',
            ],
        ];

        foreach ($rolesData as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(
                ['name' => $roleName, 'guard_name' => 'admin'],
                ['description' => 'Acesso '.$roleName]
            );
            // Add new permissions without detaching old ones (if any manual overrides exist)
            // But usually we just sync
            $role->syncPermissions($rolePermissions);
        }

        // Atribui Owner a todos os Super Admins (Admin ID = 1 por exemplo)
        // Isso é opcional, mas garante que o primeiro admin não fique bloqueado.
        $firstAdmin = Admin::find(1);
        if ($firstAdmin && ! $firstAdmin->hasRole('Owner')) {
            $firstAdmin->assignRole('Owner');
        }
    }
}
