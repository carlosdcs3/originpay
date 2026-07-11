<?php

return [
    [
        'label' => 'Dashboard',
        'icon'  => 'dashboard',
        'route' => 'admin.dashboard',
        'menus' => [
            [
                'label' => 'Visão Geral',
                'icon'  => 'dashboard',
                'type'  => 'single',
                'route' => 'admin.dashboard',
            ],
        ],
    ],

    [
        'label' => 'Financeiro',
        'icon'  => 'currency',
        'route' => 'admin.gateway.charges.index',
        'menus' => [
            [
                'label' => 'Cobranças',
                'icon'  => 'currency',
                'type'  => 'single',
                'route' => 'admin.gateway.charges.index',
            ],
            [
                'label' => 'Saques',
                'icon'  => 'wallet',
                'type'  => 'single',
                'route' => 'admin.gateway.withdrawals.index',
            ],
            [
                'label' => 'Transações',
                'icon'  => 'fee',
                'type'  => 'single',
                'route' => 'admin.transaction',
            ],
            [
                'label' => 'Tarifas',
                'icon'  => 'fee',
                'type'  => 'single',
                'route' => 'admin.finance.tariffs',
            ],
            [
                'label' => 'Chargebacks',
                'icon'  => 'back',
                'type'  => 'single',
                'route' => 'admin.finance.chargebacks',
            ],
            [
                'label' => 'Conciliações',
                'icon'  => 'exchange',
                'type'  => 'single',
                'route' => 'admin.finance.reconciliation',
            ],
            [
                'label' => 'Carteiras',
                'icon'  => 'wallet',
                'type'  => 'single',
                'route' => 'admin.finance.balances',
            ],
        ],
    ],

    [
        'label' => 'Gateways',
        'icon'  => 'payment',
        'route' => 'admin.payment.gateway.index',
        'menus' => [
            [
                'label' => 'Todos Gateways',
                'icon'  => 'payment',
                'type'  => 'single',
                'route' => 'admin.payment.gateway.index',
            ],
        ],
    ],

    [
        'label' => 'Clientes',
        'icon'  => 'users-1',
        'route' => 'admin.user.index',
        'menus' => [
            [
                'label' => 'Clientes',
                'icon'  => 'users-1',
                'type'  => 'single',
                'route' => 'admin.user.index',
            ],
            [
                'label' => 'Lojistas',
                'icon'  => 'merchant',
                'type'  => 'single',
                'route' => 'admin.merchant.index',
            ],
            [
                'label' => 'KYC',
                'icon'  => 'kyc',
                'type'  => 'single',
                'route' => 'admin.kyc.index',
            ],
            [
                'label' => 'Compliance',
                'icon'  => 'security',
                'type'  => 'single',
                'route' => 'admin.compliance.dashboard',
            ],
            [
                'label' => 'Conversas',
                'icon'  => 'chat',
                'type'  => 'single',
                'route' => 'admin.support-chat.index',
            ],
        ],
    ],

    [
        'label' => 'Operações',
        'icon'  => 'window',
        'route' => 'admin.operations.command',
        'menus' => [
            [
                'label' => 'Centro de Operações',
                'icon'  => 'window',
                'type'  => 'single',
                'route' => 'admin.operations.command',
            ],
            [
                'label' => 'Alertas',
                'icon'  => 'warning',
                'type'  => 'single',
                'route' => 'admin.alerts.index',
            ],
            [
                'label' => 'Incidentes',
                'icon'  => 'error',
                'type'  => 'single',
                'route' => 'admin.ops.incidents',
            ],
            [
                'label' => 'Agendador',
                'icon'  => 'clock',
                'type'  => 'single',
                'route' => 'admin.system.queues',
            ],
            [
                'label' => 'Status Público',
                'icon'  => 'site-setting',
                'type'  => 'single',
                'route' => 'admin.system.health.index',
            ],
        ],
    ],

    [
        'label' => 'Marketing',
        'icon'  => 'reward',
        'route' => 'admin.marketing.campaigns.index',
        'menus' => [
            [
                'label' => 'Campanhas',
                'icon'  => 'reward',
                'type'  => 'single',
                'route' => 'admin.marketing.campaigns.index',
            ],
            [
                'label' => 'Landing Pages',
                'icon'  => 'page',
                'type'  => 'single',
                'route' => 'admin.page.site.index',
            ],
            [
                'label' => 'Modelos de E-mail',
                'icon'  => 'email',
                'type'  => 'single',
                'route' => 'admin.notifications.template.index',
            ],
        ],
    ],

    [
        'label' => 'Relatórios',
        'icon'  => 'chart',
        'route' => 'admin.reports.index',
        'menus' => [
            [
                'label' => 'Central de Relatórios',
                'icon'  => 'chart',
                'type'  => 'single',
                'route' => 'admin.reports.index',
            ],
        ],
    ],

    [
        'label' => 'Configurações',
        'icon'  => 'site-settings',
        'route' => 'admin.settings.site.index',
        'menus' => [
            [
                'label' => 'Empresa',
                'icon'  => 'site-settings',
                'type'  => 'single',
                'route' => 'admin.settings.site.index',
            ],
            [
                'label' => 'Plataforma',
                'icon'  => 'site-settings',
                'type'  => 'single',
                'route' => 'admin.settings.platform.index',
            ],
            [
                'label' => 'Equipe',
                'icon'  => 'staff',
                'type'  => 'single',
                'route' => 'admin.staff.index',
            ],
        ],
    ],
];
