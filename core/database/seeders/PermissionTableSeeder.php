<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * This seeder populates the permissions table with predefined permissions.
     * It also creates a super-admin role and assigns all permissions to it.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Define the permissions
        $permissions = [

            // 🧩 Dashboard
            ['category' => 'dashboard', 'name' => 'dashboard-stats'],
            ['category' => 'dashboard', 'name' => 'transactions-chart'],
            ['category' => 'dashboard', 'name' => 'wallet-balance'],
            ['category' => 'dashboard', 'name' => 'earning-chart'],
            ['category' => 'dashboard', 'name' => 'wallet-growth'],
            ['category' => 'dashboard', 'name' => 'wallet-latest-transactions'],
            ['category' => 'dashboard', 'name' => 'wallet-latest-users'],

            // 📦 User Management
            ['category' => 'user', 'name' => 'user-list'],
            ['category' => 'user', 'name' => 'user-create'],
            ['category' => 'user', 'name' => 'user-manage'],
            ['category' => 'user', 'name' => 'user-delete'],
            ['category' => 'user', 'name' => 'user-activity-log'],
            ['category' => 'user', 'name' => 'user-login-as'],
            ['category' => 'user', 'name' => 'user-balance-manage'],
            ['category' => 'user', 'name' => 'user-features-manage'],

            // 🛡️ Role Management
            ['category' => 'role', 'name' => 'role-list'],
            ['category' => 'role', 'name' => 'role-create'],
            ['category' => 'role', 'name' => 'role-edit'],
            ['category' => 'role', 'name' => 'role-delete'],

            // 👥 Staff
            ['category' => 'staff', 'name' => 'staff-list'],
            ['category' => 'staff', 'name' => 'staff-create'],
            ['category' => 'staff', 'name' => 'staff-edit'],

            // 🛍️ Merchant
            ['category' => 'merchant', 'name' => 'merchant-list'],
            ['category' => 'merchant', 'name' => 'merchant-manage'],
            ['category' => 'merchant', 'name' => 'merchant-request-notification'],

            // 🧾 KYC
            ['category' => 'kyc', 'name' => 'kyc-list'],
            ['category' => 'kyc', 'name' => 'kyc-action'],
            ['category' => 'kyc', 'name' => 'kyc-notification'],
            ['category' => 'kyc', 'name' => 'kyc-template-list'],
            ['category' => 'kyc', 'name' => 'kyc-template-manage'],

            // 🃏 Virtual Card
            ['category' => 'virtual-card', 'name' => 'virtual-card-list'],
            ['category' => 'virtual-card', 'name' => 'virtual-card-action'],
            ['category' => 'virtual-card', 'name' => 'virtual-card-notification'],
            ['category' => 'virtual-card', 'name' => 'virtual-card-provider-manage'],

            // 💰 Deposit
            ['category' => 'deposit', 'name' => 'deposit-list'],
            ['category' => 'deposit', 'name' => 'deposit-action'],
            ['category' => 'deposit', 'name' => 'deposit-method-list'],
            ['category' => 'deposit', 'name' => 'deposit-method-manage'],
            ['category' => 'deposit', 'name' => 'deposit-notification'],

            // 💸 Withdraw
            ['category' => 'withdraw', 'name' => 'withdraw-list'],
            ['category' => 'withdraw', 'name' => 'withdraw-action'],
            ['category' => 'withdraw', 'name' => 'withdraw-method-list'],
            ['category' => 'withdraw', 'name' => 'withdraw-method-manage'],
            ['category' => 'withdraw', 'name' => 'withdraw-schedule'],
            ['category' => 'withdraw', 'name' => 'withdraw-notification'],

            // 💳 Payment Gateway
            ['category' => 'payment', 'name' => 'payment-gateway-list'],
            ['category' => 'payment', 'name' => 'payment-gateway-configure'],

            // ⚙️ Settings
            ['category' => 'site-settings', 'name' => 'site-setting-view'],
            ['category' => 'site-settings', 'name' => 'site-setting-update'],

            // 🌍 Language
            ['category' => 'language', 'name' => 'language-list'],
            ['category' => 'language', 'name' => 'language-create'],
            ['category' => 'language', 'name' => 'language-manage'],

            // 🗂️ Navigation
            ['category' => 'navigation', 'name' => 'navigation-manage'],

            // 🌐 Pages
            ['category' => 'page', 'name' => 'page-list'],
            ['category' => 'page', 'name' => 'page-create'],
            ['category' => 'page', 'name' => 'page-edit'],
            ['category' => 'page', 'name' => 'page-delete'],
            ['category' => 'page', 'name' => 'page-footer-manage'],

            // 🧩 Components
            ['category' => 'component', 'name' => 'component-list'],
            ['category' => 'component', 'name' => 'component-manage'],

            // 📰 Blog
            ['category' => 'blog', 'name' => 'blog-list'],
            ['category' => 'blog', 'name' => 'blog-create'],
            ['category' => 'blog', 'name' => 'blog-edit'],
            ['category' => 'blog', 'name' => 'blog-delete'],
            ['category' => 'blog', 'name' => 'blog-category-list'],
            ['category' => 'blog', 'name' => 'blog-category-manage'],

            // 📬 Subscribers
            ['category' => 'subscriber', 'name' => 'subscriber-list'],
            ['category' => 'subscriber', 'name' => 'subscriber-manage'],

            // 🔗 Social
            ['category' => 'social', 'name' => 'social-list'],
            ['category' => 'social', 'name' => 'social-manage'],

            // 🧾 Transaction
            ['category' => 'transaction', 'name' => 'transaction-list'],

            // 📈 Ranking
            ['category' => 'ranking', 'name' => 'ranking-manage'],

            // 🤝 Referral
            ['category' => 'referral', 'name' => 'referral-manage'],

            // 📢 Notification
            ['category' => 'user', 'name' => 'custom-notify-users'],
            ['category' => 'notification', 'name' => 'notification-list'],
            ['category' => 'notification', 'name' => 'notification-plugin-list'],
            ['category' => 'notification', 'name' => 'notification-template-list'],
            ['category' => 'notification', 'name' => 'notification-template-manage'],

            // 🎟️ Support
            ['category' => 'support', 'name' => 'support-ticket-list'],
            ['category' => 'support', 'name' => 'support-ticket-category-manage'],
            ['category' => 'support', 'name' => 'support-ticket-manage'],
            ['category' => 'support', 'name' => 'support-ticket-notification'],

            // 🔐 SEO
            ['category' => 'seo', 'name' => 'seo-manage'],

            // 🧾 Currency
            ['category' => 'currency', 'name' => 'currency-manage'],

            // 🧩 Plugins
            ['category' => 'plugins', 'name' => 'plugins-manage'],

            // 🛠️ Application
            ['category' => 'app', 'name' => 'app-info'],
            ['category' => 'app', 'name' => 'style-manager'],
            ['category' => 'app', 'name' => 'app-clear-cache'],
            ['category' => 'app', 'name' => 'app-optimize'],
        ];

        // Truncate removed to preserve Enterprise RBAC permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name'], 'guard_name' => 'admin'],
                ['category' => $permission['category']]
            );
        }

        // Create or retrieve the super-admin role
        $superRole = Role::firstOrCreate(
            ['guard_name' => 'admin', 'name' => 'super-admin'],
            ['description' => 'Acesso total ao sistema']
        );

        // Assign all permissions to the super-admin role
        $superRole->givePermissionTo(Permission::all());
    }
}
