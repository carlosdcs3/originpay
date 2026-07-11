@php use App\Enums\KycStatus; @endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" data-theme="dark" style="color-scheme: dark;">

{{-- Head --}}
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name') }} | @yield('title', 'Dashboard')</title>

    {{-- Favicon & Web Manifest --}}
    <link rel="icon" href="{{ asset('frontend/images/originpay/originpay-app-icon.svg') }}" type="image/svg+xml">
    <link rel="shortcut icon" href="{{ asset('frontend/images/originpay/originpay-app-icon.png') }}" type="image/png">
    <link rel="apple-touch-icon" href="{{ asset('frontend/images/originpay/apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}?v={{ @filemtime(public_path('site.webmanifest')) ?: time() }}">
    <meta name="application-name" content="OriginPay">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="OriginPay">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="format-detection" content="telephone=no">

    <link rel="stylesheet" href="{{ asset('general/css/bootstrap.min.css') }}?v={{ @filemtime(public_path('general/css/bootstrap.min.css')) ?: time() }}">
    <link rel="stylesheet" href="{{ asset('general/css/fontawesome.min.css') }}?v={{ @filemtime(public_path('general/css/fontawesome.min.css')) ?: time() }}">
    <link rel="stylesheet" href="{{ asset('general/css/simple-notify.min.css') }}?v={{ @filemtime(public_path('general/css/simple-notify.min.css')) ?: time() }}">
    <link rel="stylesheet" href="{{ asset('general/css/originpay-notify.css') }}?v={{ @filemtime(public_path('general/css/originpay-notify.css')) ?: time() }}">
    <link rel="stylesheet" href="{{ asset('frontend/css/originpay-dashboard-v2.css') }}?v={{ @filemtime(public_path('frontend/css/originpay-dashboard-v2.css')) ?: time() }}">
    
    {{-- Font --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @yield('styles')
    @stack('styles')
    <style>
        @media (max-width: 768px) {
            html,
            body.v2-dashboard,
            body.v2-dashboard .v2-main,
            body.v2-dashboard .v2-content {
                width: 100% !important;
                max-width: 100% !important;
                min-width: 0 !important;
                height: auto !important;
                min-height: 100dvh !important;
                overflow-x: hidden !important;
                overflow-y: visible !important;
            }

            body.v2-dashboard .v2-content {
                display: block !important;
                padding: 12px max(12px, env(safe-area-inset-right)) calc(76px + env(safe-area-inset-bottom)) max(12px, env(safe-area-inset-left)) !important;
            }

            body.v2-dashboard .v2-content::-webkit-scrollbar,
            body.v2-dashboard .v2-main::-webkit-scrollbar,
            body.v2-dashboard [class*="-shell"]::-webkit-scrollbar,
            body.v2-dashboard [class*="-shell"] *::-webkit-scrollbar {
                width: initial !important;
                height: initial !important;
                display: initial !important;
            }

            body.v2-dashboard .v2-kyc-alert {
                margin: 0 0 12px !important;
                padding: 8px 10px !important;
                border-radius: 8px !important;
                align-items: center !important;
                justify-content: flex-start !important;
                gap: 8px !important;
            }

            body.v2-dashboard .v2-kyc-alert-link {
                flex: 0 0 auto !important;
                margin-left: auto !important;
                white-space: nowrap !important;
            }

            body.v2-dashboard .v2-content [class$="-shell"],
            body.v2-dashboard .v2-content [class*="-shell "] {
                min-height: 0 !important;
                height: auto !important;
                overflow: visible !important;
                gap: 14px !important;
            }

            body.v2-dashboard .cmf-header-title,
            body.v2-dashboard .sub-header-title,
            body.v2-dashboard .cx-header-title,
            body.v2-dashboard .boleto-page-header,
            body.v2-dashboard .disputes-header,
            body.v2-dashboard .op-header,
            body.v2-dashboard .ch-page-header,
            body.v2-dashboard .ac-hero-top,
            body.v2-dashboard .ds-page-header,
            body.v2-dashboard .v2-header-actions {
                display: flex !important;
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 10px !important;
            }

            body.v2-dashboard .cmf-title h1,
            body.v2-dashboard .sub-title h1,
            body.v2-dashboard .cx-title h1,
            body.v2-dashboard .boleto-title h1,
            body.v2-dashboard .disputes-header h1,
            body.v2-dashboard .v2-page-title,
            body.v2-dashboard .v2-header-title {
                font-size: 1.25rem !important;
                line-height: 1.18 !important;
                overflow-wrap: anywhere !important;
            }

            body.v2-dashboard .cmf-title p,
            body.v2-dashboard .sub-title p,
            body.v2-dashboard .cx-title p,
            body.v2-dashboard .boleto-title p,
            body.v2-dashboard .disputes-header p,
            body.v2-dashboard .v2-header-subtitle {
                font-size: 0.78rem !important;
                line-height: 1.45 !important;
            }

            body.v2-dashboard .cmf-kpis,
            body.v2-dashboard .sub-kpis,
            body.v2-dashboard .cx-kpis,
            body.v2-dashboard .boleto-kpis,
            body.v2-dashboard .boleto-kpi-grid,
            body.v2-dashboard .disputes-kpis,
            body.v2-dashboard .op-stats-grid,
            body.v2-dashboard .wallet-grid,
            body.v2-dashboard .ac-status-row,
            body.v2-dashboard .ac-fees,
            body.v2-dashboard .ac-sim-result,
            body.v2-dashboard .v2-kpi-grid,
            body.v2-dashboard .pix-kpi-grid {
                display: grid !important;
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                gap: 10px !important;
                overflow: visible !important;
            }

            body.v2-dashboard .cmf-kpi,
            body.v2-dashboard .sub-kpi,
            body.v2-dashboard .cx-kpi,
            body.v2-dashboard .boleto-kpi,
            body.v2-dashboard .boleto-kpi-card,
            body.v2-dashboard .kpi-dense,
            body.v2-dashboard .op-kpi-card,
            body.v2-dashboard .wallet-card,
            body.v2-dashboard .ac-status-item,
            body.v2-dashboard .ac-fee-card,
            body.v2-dashboard .ac-sim-card,
            body.v2-dashboard .v2-kpi-card {
                min-width: 0 !important;
                width: 100% !important;
                padding: 12px !important;
            }

            body.v2-dashboard .cmf-kpi-label,
            body.v2-dashboard .sub-kpi-label,
            body.v2-dashboard .cx-kpi-label,
            body.v2-dashboard .boleto-kpi-label,
            body.v2-dashboard .kpi-title,
            body.v2-dashboard .v2-kpi-title {
                font-size: 0.65rem !important;
                line-height: 1.25 !important;
                letter-spacing: 0.02em !important;
                word-break: normal !important;
            }

            body.v2-dashboard .cmf-kpi-value,
            body.v2-dashboard .sub-kpi-value,
            body.v2-dashboard .cx-kpi-value,
            body.v2-dashboard .boleto-kpi-value,
            body.v2-dashboard .kpi-dense .v2-kpi-value,
            body.v2-dashboard .v2-kpi-value {
                font-size: 1.05rem !important;
                line-height: 1.2 !important;
                overflow-wrap: anywhere !important;
            }

            body.v2-dashboard .cmf-grid,
            body.v2-dashboard .sub-grid,
            body.v2-dashboard .cx-grid,
            body.v2-dashboard .boleto-grid,
            body.v2-dashboard .disputes-workspace,
            body.v2-dashboard .disputes-main-row,
            body.v2-dashboard .op-grid,
            body.v2-dashboard .op-stats-grid,
            body.v2-dashboard .wallet-grid,
            body.v2-dashboard .ac-simulator-grid,
            body.v2-dashboard .ac-status-row,
            body.v2-dashboard .ac-fees,
            body.v2-dashboard .ac-sim-result,
            body.v2-dashboard .pl-grid,
            body.v2-dashboard .method-grid,
            body.v2-dashboard .sh-layout,
            body.v2-dashboard .sub-detail-grid,
            body.v2-dashboard .v2-settings-shell,
            body.v2-dashboard .v2-dashboard-grid,
            body.v2-dashboard .v2-content [style*="grid-template-columns"] {
                display: grid !important;
                grid-template-columns: 1fr !important;
                width: 100% !important;
                min-width: 0 !important;
                overflow: visible !important;
            }

            body.v2-dashboard .cmf-col-left,
            body.v2-dashboard .cmf-col-right,
            body.v2-dashboard .sub-panel,
            body.v2-dashboard .cx-panel,
            body.v2-dashboard .boleto-panel,
            body.v2-dashboard .disputes-col-left,
            body.v2-dashboard .disputes-col-right,
            body.v2-dashboard .disputes-table-card,
            body.v2-dashboard .sidebar-scroll-area,
            body.v2-dashboard .v2-settings-card,
            body.v2-dashboard .pl-card,
            body.v2-dashboard .op-card,
            body.v2-dashboard .wallet-card,
            body.v2-dashboard .ac-hero,
            body.v2-dashboard .ac-panel,
            body.v2-dashboard .ac-fee-card,
            body.v2-dashboard .chk-card,
            body.v2-dashboard .chart-container,
            body.v2-dashboard .ch-card {
                width: 100% !important;
                min-width: 0 !important;
                max-width: 100% !important;
                overflow: visible !important;
            }

            body.v2-dashboard .cmf-type-cards,
            body.v2-dashboard .ch-choice-grid {
                display: grid !important;
                grid-template-columns: 1fr !important;
                gap: 10px !important;
            }

            body.v2-dashboard .cmf-type-card,
            body.v2-dashboard .ch-choice-card {
                min-height: 96px !important;
                padding: 12px !important;
            }

            body.v2-dashboard .cmf-radio {
                top: 14px !important;
                right: 14px !important;
            }

            body.v2-dashboard .cmf-form,
            body.v2-dashboard .op-form,
            body.v2-dashboard form {
                position: relative !important;
                width: 100% !important;
                min-width: 0 !important;
            }

            body.v2-dashboard .cmf-form-container {
                display: grid !important;
            }

            body.v2-dashboard .cmf-form:not(.active) {
                position: absolute !important;
                inset: 0 auto auto 0 !important;
                width: 100% !important;
                height: 0 !important;
                min-height: 0 !important;
                padding: 0 !important;
                margin: 0 !important;
                border: 0 !important;
                overflow: hidden !important;
                opacity: 0 !important;
                visibility: hidden !important;
                pointer-events: none !important;
            }

            body.v2-dashboard .cmf-form.active {
                position: relative !important;
                height: auto !important;
                min-height: 0 !important;
                opacity: 1 !important;
                visibility: visible !important;
                pointer-events: auto !important;
            }

            body.v2-dashboard .cmf-filters,
            body.v2-dashboard .sub-filter,
            body.v2-dashboard .cx-filter,
            body.v2-dashboard .ch-filters,
            body.v2-dashboard .filters-wrapper,
            body.v2-dashboard .v2-filters,
            body.v2-dashboard .op-row,
            body.v2-dashboard .op-footer,
            body.v2-dashboard .op-actions,
            body.v2-dashboard .wallet-header,
            body.v2-dashboard .wallet-footer,
            body.v2-dashboard .source-bar-wrap,
            body.v2-dashboard .chk-head-top,
            body.v2-dashboard .chk-method-tabs,
            body.v2-dashboard .ch-method-tabs,
            body.v2-dashboard .ch-pagination,
            body.v2-dashboard .ac-fee-head,
            body.v2-dashboard .ac-fee-row,
            body.v2-dashboard .chk-form-grid {
                display: grid !important;
                grid-template-columns: 1fr !important;
                gap: 10px !important;
                width: 100% !important;
            }

            body.v2-dashboard input,
            body.v2-dashboard select,
            body.v2-dashboard textarea,
            body.v2-dashboard button {
                max-width: 100% !important;
                font-size: 16px !important;
            }

            body.v2-dashboard .cmf-input,
            body.v2-dashboard .cmf-filter-input,
            body.v2-dashboard .sub-control,
            body.v2-dashboard .cx-control,
            body.v2-dashboard .op-input,
            body.v2-dashboard .op-btn-primary,
            body.v2-dashboard .op-btn-secondary,
            body.v2-dashboard .op-btn-doc,
            body.v2-dashboard .op-method,
            body.v2-dashboard .op-segment,
            body.v2-dashboard .ac-input,
            body.v2-dashboard .ac-select,
            body.v2-dashboard .ch-filter-input,
            body.v2-dashboard .ch-filter-select,
            body.v2-dashboard .ch-filter-btn,
            body.v2-dashboard input[type="text"],
            body.v2-dashboard input[type="email"],
            body.v2-dashboard input[type="password"],
            body.v2-dashboard input[type="number"],
            body.v2-dashboard input[type="date"],
            body.v2-dashboard select,
            body.v2-dashboard textarea {
                width: 100% !important;
                min-width: 0 !important;
            }

            body.v2-dashboard .v2-btn-primary,
            body.v2-dashboard .v2-btn-outline,
            body.v2-dashboard .v2-btn-secondary,
            body.v2-dashboard .cmf-btn-submit,
            body.v2-dashboard .ch-filter-btn,
            body.v2-dashboard .op-btn-primary,
            body.v2-dashboard .op-btn-secondary,
            body.v2-dashboard .op-btn-doc,
            body.v2-dashboard button[type="submit"] {
                min-height: 42px !important;
                width: 100% !important;
                justify-content: center !important;
                text-align: center !important;
                white-space: normal !important;
            }

            body.v2-dashboard .cmf-tabs,
            body.v2-dashboard .sub-tabs,
            body.v2-dashboard .cx-tabs,
            body.v2-dashboard .boleto-tabs,
            body.v2-dashboard .trx-tabs {
                overflow-x: auto !important;
                padding: 0 12px !important;
                gap: 14px !important;
                -webkit-overflow-scrolling: touch;
            }

            body.v2-dashboard .cmf-tab,
            body.v2-dashboard .sub-tab,
            body.v2-dashboard .cx-tab,
            body.v2-dashboard .boleto-tab,
            body.v2-dashboard .trx-tab {
                flex: 0 0 auto !important;
                white-space: nowrap !important;
            }

            body.v2-dashboard .cmf-table-wrap,
            body.v2-dashboard .sub-table-wrap,
            body.v2-dashboard .cx-table-wrap,
            body.v2-dashboard .boleto-table-wrap,
            body.v2-dashboard .disputes-table-wrapper,
            body.v2-dashboard .v2-table-wrapper,
            body.v2-dashboard .table-responsive,
            body.v2-dashboard [class*="table-wrap"] {
                width: 100% !important;
                max-width: 100% !important;
                overflow-x: auto !important;
                overflow-y: visible !important;
                padding-left: 0 !important;
                padding-right: 0 !important;
                -webkit-overflow-scrolling: touch;
            }

            body.v2-dashboard table:not(.v2-mobile-card-table) {
                min-width: 640px !important;
            }

            body.v2-dashboard .v2-mobile-card-table {
                min-width: 0 !important;
            }

            body.v2-dashboard .v2-mobile-card-table tr {
                border-radius: 10px !important;
                padding: 12px !important;
            }

            body.v2-dashboard .v2-mobile-card-table td {
                display: block !important;
                text-align: left !important;
                word-break: break-word !important;
            }

            body.v2-dashboard .v2-mobile-card-table td::before {
                display: block !important;
                max-width: none !important;
                margin: 0 0 4px !important;
            }

            body.v2-dashboard .modal-dialog,
            body.v2-dashboard [role="dialog"],
            body.v2-dashboard .cmf-modal > div {
                width: calc(100vw - 24px) !important;
                max-width: calc(100vw - 24px) !important;
                max-height: calc(100dvh - 24px) !important;
                overflow-y: auto !important;
                padding: 18px !important;
            }

            body.v2-dashboard .cx-footer,
            body.v2-dashboard .sub-footer,
            body.v2-dashboard .boleto-footer,
            body.v2-dashboard .cx-help,
            body.v2-dashboard .sub-help {
                display: flex !important;
                flex-direction: column !important;
                align-items: stretch !important;
                gap: 10px !important;
            }

            body.v2-dashboard .cx-pages,
            body.v2-dashboard .sub-pages,
            body.v2-dashboard .boleto-pages {
                width: 100% !important;
                overflow-x: auto !important;
                justify-content: flex-start !important;
                -webkit-overflow-scrolling: touch;
            }

            body.v2-dashboard .row {
                margin-left: 0 !important;
                margin-right: 0 !important;
            }

            body.v2-dashboard [class^="col-"],
            body.v2-dashboard [class*=" col-"] {
                padding-left: 0 !important;
                padding-right: 0 !important;
            }

            body.v2-dashboard .wallet-currency-info,
            body.v2-dashboard .wallet-transaction-info {
                min-width: 0 !important;
            }

            body.v2-dashboard .wallet-balance,
            body.v2-dashboard .wallet-currency-info p,
            body.v2-dashboard .wallet-transaction-info p,
            body.v2-dashboard .op-kpi-val,
            body.v2-dashboard .op-kpi-title,
            body.v2-dashboard .source-bar-label,
            body.v2-dashboard .source-bar-val,
            body.v2-dashboard .chk-title,
            body.v2-dashboard .chk-price,
            body.v2-dashboard .chk-seller,
            body.v2-dashboard .chk-fake-input span,
            body.v2-dashboard .ac-hero-name,
            body.v2-dashboard .ac-hero-meta,
            body.v2-dashboard .ac-sim-value,
            body.v2-dashboard .ac-fee-main,
            body.v2-dashboard .ac-fee-title {
                overflow-wrap: anywhere !important;
                white-space: normal !important;
            }

            body.v2-dashboard .op-preview-wrapper {
                position: static !important;
                width: 100% !important;
            }

            body.v2-dashboard .chk-amount-col {
                width: 100% !important;
                text-align: left !important;
            }

            body.v2-dashboard .chk-method-tab,
            body.v2-dashboard .ch-method-tab {
                flex: 0 0 auto !important;
                min-width: max-content !important;
            }

            body.v2-dashboard .op-segments,
            body.v2-dashboard .op-methods {
                display: grid !important;
                grid-template-columns: 1fr !important;
                gap: 8px !important;
                height: auto !important;
            }

            body.v2-dashboard .source-bar-track {
                width: 100% !important;
                margin: 6px 0 !important;
            }

            body.v2-dashboard .source-bar-label,
            body.v2-dashboard .source-bar-val {
                width: auto !important;
                text-align: left !important;
            }
        }

        @media (max-width: 340px) {
            body.v2-dashboard .cmf-kpis,
            body.v2-dashboard .sub-kpis,
            body.v2-dashboard .cx-kpis,
            body.v2-dashboard .boleto-kpis,
            body.v2-dashboard .boleto-kpi-grid,
            body.v2-dashboard .disputes-kpis,
            body.v2-dashboard .op-stats-grid,
            body.v2-dashboard .wallet-grid,
            body.v2-dashboard .ac-status-row,
            body.v2-dashboard .ac-fees,
            body.v2-dashboard .ac-sim-result,
            body.v2-dashboard .v2-kpi-grid,
            body.v2-dashboard .pix-kpi-grid {
                grid-template-columns: 1fr !important;
            }
        }
    </style>
</head>

<body class="v2-dashboard">

<div class="v2-shell">
    <button type="button" class="v2-mobile-sidebar-backdrop" data-sidebar-close aria-label="Fechar menu lateral"></button>

    {{-- Sidebar --}}
    <aside class="v2-sidebar">
        
        <div class="v2-sidebar-header">
            <a href="{{ route('home') }}" style="text-decoration: none; display: flex; flex-direction: column; justify-content: center;">
                <div style="font-size: 1.75rem; font-weight: 800; letter-spacing: -0.5px; line-height: 1; font-family: 'Inter', sans-serif;">
                    <span style="color: #ffffff;">Origin</span><span style="color: var(--ds-primary, #7C3AED);">Pay</span>
                </div>
                <div style="font-size: 0.7rem; color: var(--ds-text-muted, #94a3b8); font-weight: 500; margin-top: 4px; letter-spacing: 0.2px;">
                    Sua gateway sem limites.
                </div>
            </a>
            <button type="button" class="v2-sidebar-close" data-sidebar-close aria-label="Fechar menu">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <nav class="v2-sidebar-nav">
            
            <div class="v2-nav-group">Visão geral</div>
            
            <a href="{{ route('user.dashboard') }}" class="v2-nav-item {{ request()->routeIs('user.dashboard') ? 'active' : '' }}">
                <i class="fas fa-th-large"></i> Dashboard
            </a>

            <div class="v2-nav-group">Recebimentos</div>

            <a href="{{ route('user.charge.index') }}" class="v2-nav-item {{ request()->routeIs('user.charge.*') ? 'active' : '' }}">
                <i class="fas fa-receipt"></i> Cobranças
            </a>

            <a href="{{ route('user.payment-links.index') }}" class="v2-nav-item {{ request()->routeIs('user.payment-links.*') ? 'active' : '' }}">
                <i class="fas fa-link"></i> Links de pagamento
            </a>

            <a href="{{ Route::has('user.subscriptions.index') ? route('user.subscriptions.index') : '#' }}" class="v2-nav-item {{ request()->routeIs('user.subscriptions.*') ? 'active' : '' }}">
                <i class="fas fa-sync-alt"></i> Assinaturas
            </a>


            <a href="{{ route('user.transaction.index') }}" class="v2-nav-item {{ request()->routeIs('user.transaction.*') ? 'active' : '' }}">
                <i class="fas fa-file-invoice-dollar"></i> Extrato
            </a>
            


            <div class="v2-nav-group">Gestão</div>

            <a href="{{ Route::has('user.customer.index') ? route('user.customer.index') : '#' }}" class="v2-nav-item {{ request()->routeIs('user.customer.*') ? 'active' : '' }}">
                <i class="fas fa-users"></i> Clientes
            </a>

            <a href="{{ Route::has('user.transfer.index') ? route('user.transfer.index') : '#' }}" class="v2-nav-item {{ request()->routeIs('user.transfer.*') ? 'active' : '' }}">
                <i class="fas fa-paper-plane"></i> Transferências
            </a>

            <a href="{{ route('user.disputes.index') }}" class="v2-nav-item {{ request()->routeIs('user.disputes.*') ? 'active' : '' }}">
                <i class="fas fa-undo-alt"></i>
                <span>Reembolsos e disputas</span>
            </a>
            


            <div class="v2-nav-group">Origin Connect</div>

            <a href="{{ route('user.connect.dashboard') }}" class="v2-nav-item {{ request()->routeIs('user.connect.*') ? 'active' : '' }}">
                <i class="fas fa-network-wired"></i> Origin Connect
            </a>

            <div class="v2-nav-group">Desenvolvedor</div>

            <a href="{{ route('user.developer.api-keys.index') }}" class="v2-nav-item {{ request()->routeIs('user.developer.api-keys.*') ? 'active' : '' }}">
                <i class="fas fa-code"></i> API
            </a>

            <form id="v2-logout" action="{{ route('logout') }}" method="POST" style="display:none;">@csrf</form>
            
        </nav>

        <div class="v2-sidebar-bottom" style="padding: 12px 16px;">
            <div class="v2-balance-card" style="margin-bottom: 0; padding: 12px;">
                <div style="font-size: 0.75rem; color: var(--ds-text-muted); margin-bottom: 4px; display: flex; align-items: center; justify-content: space-between;">
                    <span style="font-weight: 500; letter-spacing: 0.02em;">Saldo disponível</span>
                    <button type="button" id="toggle-balance-btn-v2" class="v2-balance-toggle" aria-label="Alternar visibilidade do saldo" aria-pressed="false">
                        <i class="fas fa-eye-slash"></i>
                    </button>
                </div>
                @php
                    $brlWallet  = isset($userWallets) ? $userWallets->where('currency.code', 'BRL')->first() : null;
                    $primaryWallet = $brlWallet ?? (isset($userWallets) ? $userWallets->first() : null);
                    $balanceValue = $primaryWallet ? number_format((float) $primaryWallet->available_balance, 2, ',', '.') : '0,00';
                @endphp
                <div style="font-size: 1.15rem; font-weight: 700; color: var(--ds-text-main); margin-bottom: 10px; letter-spacing: -0.02em; display: flex; align-items: center; height: 28px;">
                    <span id="balance-visible-v2" style="display: none;">R$ {{ $balanceValue }}</span>
                    <span id="balance-hidden-v2" style="letter-spacing: 0.15em; font-size: 1.3rem; padding-top: 4px;">&bull;&bull;&bull;&bull;&bull;&bull;</span>
                </div>
                
                @if(Route::has('user.transfer.index'))
                <a href="{{ route('user.transfer.index') }}" class="v2-btn-outline" style="width: 100%; justify-content: center; font-size: 0.75rem; padding: 4px;">
                    Sacar agora
                </a>
                @else
                <button type="button" class="v2-btn-outline" aria-label="Sacar agora" style="width: 100%; justify-content: center; font-size: 0.75rem; padding: 4px;">
                    Sacar agora
                </button>
                @endif
            </div>

            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const btn = document.getElementById('toggle-balance-btn-v2');
                    const visible = document.getElementById('balance-visible-v2');
                    const hidden = document.getElementById('balance-hidden-v2');
                    
                    if (btn && visible && hidden) {
                        let isHidden = localStorage.getItem('hideBalance') !== 'false';
                        const btnIcon = btn.querySelector('i');
                        
                        function updateBalanceView() {
                            if (isHidden) {
                                if (btnIcon) {
                                    btnIcon.className = 'fas fa-eye-slash';
                                }
                                btn.setAttribute('aria-pressed', 'true');
                                visible.style.display = 'none';
                                hidden.style.display = 'block';
                            } else {
                                if (btnIcon) {
                                    btnIcon.className = 'fas fa-eye';
                                }
                                btn.setAttribute('aria-pressed', 'false');
                                visible.style.display = 'block';
                                hidden.style.display = 'none';
                            }
                        }
                        
                        updateBalanceView();
                        
                        btn.addEventListener('click', function() {
                            isHidden = !isHidden;
                            localStorage.setItem('hideBalance', isHidden);
                            updateBalanceView();
                        });
                    }
                });
            </script>
        </div>
        
    </aside>

    {{-- Main area --}}
    <main class="v2-main">
        <header class="v2-mobile-topbar">
            <button type="button" class="v2-icon-btn v2-mobile-nav-toggle" data-sidebar-toggle aria-label="Abrir menu">
                <i class="fas fa-bars"></i>
            </button>
            <div class="v2-mobile-topbar-meta">
                <span class="v2-mobile-kicker">OriginPay</span>
                <span class="v2-mobile-title">@yield('title', 'Painel')</span>
            </div>
            <div class="v2-user-menu-wrapper" style="position: relative;">
                <div class="v2-mobile-topbar-avatar" tabindex="0" style="width: 44px; height: 44px; border-radius: 50%; background: var(--ds-primary); color: white; display: flex; align-items: center; justify-content: center; font-weight: 600; cursor: pointer;">
                    {{ substr(auth()->user()->name ?? auth()->user()->first_name ?? 'U', 0, 1) }}
                </div>
                <div class="v2-user-dropdown" style="display: none; position: absolute; top: 100%; right: 0; margin-top: 8px; background: var(--ds-card-bg, #11151E); border: 1px solid var(--ds-border-light, rgba(255,255,255,0.05)); border-radius: 8px; min-width: 160px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); z-index: 1000; padding: 8px;">
                    <a href="{{ route('user.settings.profile') }}" style="display: flex; align-items: center; gap: 10px; padding: 10px 12px; color: var(--ds-text-main, #F8FAFC); text-decoration: none; font-size: 0.85rem; border-radius: 6px; transition: background 0.2s;">
                        <i class="fas fa-cog"></i> Configurações
                    </a>
                    <a href="#" onclick="event.preventDefault(); document.getElementById('v2-logout').submit();" style="display: flex; align-items: center; gap: 10px; padding: 10px 12px; color: var(--ds-text-main, #F8FAFC); text-decoration: none; font-size: 0.85rem; border-radius: 6px; transition: background 0.2s;">
                        <i class="fas fa-sign-out-alt"></i> Sair
                    </a>
                </div>
            </div>
        </header>

        <header class="v2-header">
            <div>
                @if(request()->routeIs('user.dashboard'))
                    <h1 class="v2-header-title">
                        Olá, {{ auth()->user()->name ?? auth()->user()->first_name ?? 'Usuário' }}
                    </h1>
                    <p class="v2-header-subtitle">Aqui está um resumo da sua operação hoje.</p>
                @else
                    <h1 class="v2-header-title">
                        @yield('title', 'Painel')
                    </h1>
                @endif
            </div>
            
            <div class="v2-header-actions">
                @if(request()->routeIs('user.dashboard'))
                <form id="filter-form" action="{{ route('user.dashboard') }}" method="GET" style="display: none;">
                    <input type="hidden" name="start_date" id="start_date" value="{{ request('start_date') }}">
                    <input type="hidden" name="end_date" id="end_date" value="{{ request('end_date') }}">
                </form>
                <button type="button" class="v2-btn-outline" id="dashboard-daterange" aria-label="Selecionar período do dashboard">
                    <i class="far fa-calendar"></i>
                    <span>{{ isset($startDate) ? $startDate->format('d/m/Y') : date('d/m/Y') }} - {{ isset($endDate) ? $endDate->format('d/m/Y') : date('d/m/Y') }}</span>
                    <i class="fas fa-chevron-down" style="font-size: 0.75rem; margin-left: 4px;"></i>
                </button>
                @endif
                
                <div class="v2-notification-wrapper" style="position: relative;">
                    <button class="v2-icon-btn" type="button" aria-label="Central de notificações" title="Central de notificações" onclick="toggleNotificationDropdown()">
                        <i class="far fa-bell"></i>
                        @if(auth()->user()->unreadNotifications->count() > 0)
                            <span class="v2-notification-badge" id="global-notif-badge">{{ auth()->user()->unreadNotifications->count() }}</span>
                        @endif
                    </button>
                    
                    @include('frontend.layouts.user-v2.partials._notifications')
                </div>
                
                <style>
                    .v2-user-menu-wrapper::after {
                        content: '';
                        position: absolute;
                        top: 100%;
                        left: 0;
                        right: 0;
                        height: 15px;
                        z-index: 999;
                    }
                    .v2-user-menu-wrapper:hover .v2-user-dropdown {
                        display: block !important;
                    }
                </style>
                <div class="v2-user-menu-wrapper" style="position: relative; margin-left: 8px;">
                    <div style="width: 36px; height: 36px; border-radius: 8px; background: var(--ds-primary); color: white; display: flex; align-items: center; justify-content: center; font-weight: 600; cursor: pointer; overflow: hidden;">
                        @php
                            $userAvatarUrl = null;
                            if (auth()->user()->avatar) {
                                if (str_starts_with(auth()->user()->avatar, 'http')) {
                                    $userAvatarUrl = auth()->user()->avatar;
                                } elseif (str_contains(auth()->user()->avatar, '/')) {
                                    $userAvatarUrl = asset('storage/' . auth()->user()->avatar);
                                } else {
                                    $userAvatarUrl = asset('assets/images/user/profile/' . auth()->user()->avatar);
                                }
                            }
                        @endphp
                        @if($userAvatarUrl)
                            <img src="{{ $userAvatarUrl }}" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover;">
                        @else
                            {{ substr(auth()->user()->name ?? auth()->user()->first_name ?? 'U', 0, 1) }}
                        @endif
                    </div>

                    <div class="v2-user-dropdown" style="display: none; position: absolute; top: 100%; right: 0; margin-top: 8px; background: var(--ds-card-bg, #11151E); border: 1px solid var(--ds-border-light, rgba(255,255,255,0.05)); border-radius: 8px; min-width: 160px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); z-index: 1000; padding: 8px;">
                        <a href="{{ route('user.settings.profile') }}" style="display: flex; align-items: center; gap: 10px; padding: 10px 12px; color: var(--ds-text-main, #F8FAFC); text-decoration: none; font-size: 0.85rem; border-radius: 6px; transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.05)'; this.style.color='var(--ds-primary)'" onmouseout="this.style.background='transparent'; this.style.color='var(--ds-text-main, #F8FAFC)'">
                            <i class="fas fa-cog"></i> Configurações
                        </a>
                        <a href="#" onclick="event.preventDefault(); document.getElementById('v2-logout').submit();" style="display: flex; align-items: center; gap: 10px; padding: 10px 12px; color: var(--ds-text-main, #F8FAFC); text-decoration: none; font-size: 0.85rem; border-radius: 6px; transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.05)'; this.style.color='var(--ds-primary)'" onmouseout="this.style.background='transparent'; this.style.color='var(--ds-text-main, #F8FAFC)'">
                            <i class="fas fa-sign-out-alt"></i> Sair
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <div class="v2-content">
            
            {{-- KYC Notice --}}
            @if(auth()->user()->kyc_status !== \App\Enums\KycStatus::APPROVED)
                @if(!request()->routeIs('user.kyc.verify'))
                    <div class="v2-kyc-alert" style="display: flex; align-items: center; gap: 8px; justify-content: flex-start; padding: 8px 10px; background: rgba(245,158,11,0.05); border: 1px solid rgba(245,158,11,0.18); border-radius: 8px; margin-bottom: 14px;">
                        <i class="fas fa-exclamation-circle" style="color: var(--ds-boleto); font-size: 0.9rem; flex-shrink: 0;"></i>
                        <span class="v2-kyc-alert-title" style="font-weight: 700; color: var(--ds-boleto); font-size: 0.78rem; line-height: 1.2;">KYC pendente</span>
                        <a href="{{ route('user.kyc.verify') }}" class="v2-kyc-alert-link" style="margin-left: auto; color: var(--ds-boleto); font-size: 0.76rem; font-weight: 700; line-height: 1.2; text-decoration: underline; text-underline-offset: 3px; white-space: nowrap;">
                            Verificar
                        </a>
                    </div>
                @endif
            @endif

            @yield('content')

            <style>
                @media (max-width: 768px) {
                    body.v2-dashboard .disputes-workspace,
                    body.v2-dashboard .disputes-main-row,
                    body.v2-dashboard .disputes-col-left,
                    body.v2-dashboard .disputes-col-right,
                    body.v2-dashboard .disputes-table-card,
                    body.v2-dashboard .sidebar-scroll-area,
                    body.v2-dashboard .pl-shell,
                    body.v2-dashboard .op-grid,
                    body.v2-dashboard .op-header,
                    body.v2-dashboard .op-footer,
                    body.v2-dashboard .op-actions,
                    body.v2-dashboard .op-preview-wrapper,
                    body.v2-dashboard .chk-head-top,
                    body.v2-dashboard .source-bar-wrap {
                        width: 100% !important;
                        max-width: 100% !important;
                        min-width: 0 !important;
                        height: auto !important;
                        max-height: none !important;
                        overflow: visible !important;
                    }

                    body.v2-dashboard .disputes-main-row,
                    body.v2-dashboard .op-header,
                    body.v2-dashboard .op-footer,
                    body.v2-dashboard .op-actions,
                    body.v2-dashboard .chk-head-top,
                    body.v2-dashboard .source-bar-wrap {
                        display: grid !important;
                        grid-template-columns: 1fr !important;
                        gap: 10px !important;
                    }

                    body.v2-dashboard .disputes-col-left,
                    body.v2-dashboard .disputes-col-right {
                        flex: 1 1 auto !important;
                    }

                    body.v2-dashboard .disputes-kpis,
                    body.v2-dashboard .op-stats-grid {
                        display: grid !important;
                        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                        gap: 10px !important;
                    }

                    body.v2-dashboard .kpi-dense,
                    body.v2-dashboard .op-kpi-card {
                        height: auto !important;
                        min-width: 0 !important;
                        padding: 12px !important;
                    }

                    body.v2-dashboard .source-bar-label,
                    body.v2-dashboard .source-bar-track,
                    body.v2-dashboard .source-bar-val {
                        width: 100% !important;
                        min-width: 0 !important;
                        margin: 0 !important;
                        text-align: left !important;
                    }
                }
            </style>
            

        </div>
        
    </main>

</div>

@if(Route::has('user.support-ticket.index'))
<div class="v2-support-wrapper">
    <div class="v2-support-tooltip">
        <div class="v2-support-tooltip-title">Olá</div>
        <div class="v2-support-tooltip-desc" style="font-size: 0.75rem; color: rgba(255,255,255,0.7); margin-bottom: 6px;">Como podemos ajudar?<br>Abra uma conversa com o suporte.</div>
        <div class="v2-support-tooltip-status" style="display: none;">
            <!-- Removed redundant status dot since it's in desc -->
        </div>
    </div>
    <button type="button" class="v2-support-btn" onclick="if(typeof dsChatUI !== 'undefined') dsChatUI.toggleWidget();" title="Abrir atendimento" aria-label="Abrir atendimento">
        <span class="v2-support-btn-icon"><i class="fas fa-headset"></i></span>
        <span class="v2-support-btn-label">Suporte</span>
        <span class="v2-support-btn-dot" aria-hidden="true"></span>
    </button>
</div>
@endif

<script src="{{ asset('general/js/jquery.min.js') }}"></script>
<script src="{{ asset('general/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('general/js/simple-notify.min.js') }}"></script>
<script src="{{ asset('general/js/helpers.js') }}"></script>
@include('general._notify_evs')
@include('frontend.layouts.partials._confirm_modal')
@includeIf('frontend.layouts.partials._transaction_password_modal')
@includeIf('frontend.layouts.partials._transaction_password_confirm_modal')
@includeIf('frontend.layouts.user-v2.partials._support_chat')

@php
    try {
        $originPayPusherConfig = function_exists('pluginCredentials') ? pluginCredentials('pusher') : [];
    } catch (\Throwable $exception) {
        $originPayPusherConfig = [];
    }

    $originPayPusherEnabled = (int)($originPayPusherConfig['status'] ?? 0) === 1
        && ! empty($originPayPusherConfig['pusher_app_key'])
        && ! empty($originPayPusherConfig['pusher_app_cluster'])
        && auth()->check();
@endphp

@if($originPayPusherEnabled)
<script src="{{ asset('general/js/pusher.min.js') }}"></script>
<script>
    window.OriginPayRealtime = {
        enabled: true,
        userId: @json(auth()->id()),
        key: @json($originPayPusherConfig['pusher_app_key']),
        cluster: @json($originPayPusherConfig['pusher_app_cluster'] ?? 'mt1'),
        authEndpoint: @json(url('/broadcasting/auth')),
        csrfToken: @json(csrf_token()),
        icon: @json(asset('frontend/images/originpay/android-chrome-192x192.png')),
        badge: @json(asset('frontend/images/originpay/android-chrome-192x192.png')),
    };
</script>
@endif

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const shell = document.querySelector('.v2-shell');
        const sidebarOpeners = document.querySelectorAll('[data-sidebar-toggle]');
        const sidebarClosers = document.querySelectorAll('[data-sidebar-close]');
        const navLinks = document.querySelectorAll('.v2-sidebar .v2-nav-item:not(.disabled)');

        if (!shell) {
            return;
        }

        document.querySelectorAll('.modal:not(.show)').forEach(function (modal) {
            modal.removeAttribute('aria-hidden');
            modal.setAttribute('inert', '');
        });

        document.addEventListener('show.bs.modal', function (event) {
            if (event.target && event.target.classList.contains('modal')) {
                event.target.removeAttribute('aria-hidden');
                event.target.removeAttribute('inert');
            }
        });

        document.addEventListener('hidden.bs.modal', function (event) {
            if (event.target && event.target.classList.contains('modal')) {
                event.target.removeAttribute('aria-hidden');
                event.target.setAttribute('inert', '');
            }
        });

        function setSidebarState(isOpen) {
            shell.classList.toggle('sidebar-open', isOpen);
            document.body.classList.toggle('v2-mobile-sidebar-open', isOpen);
        }

        sidebarOpeners.forEach(function (button) {
            button.addEventListener('click', function () {
                setSidebarState(true);
            });
        });

        sidebarClosers.forEach(function (button) {
            button.addEventListener('click', function () {
                setSidebarState(false);
            });
        });

        navLinks.forEach(function (link) {
            link.addEventListener('click', function () {
                if (window.innerWidth <= 1024) {
                    setSidebarState(false);
                }
            });
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && shell.classList.contains('sidebar-open')) {
                setSidebarState(false);
            }
        });

        window.addEventListener('resize', function () {
            if (window.innerWidth > 1024) {
                setSidebarState(false);
            }
        });

        if ('serviceWorker' in navigator && window.isSecureContext) {
            window.addEventListener('load', function () {
                navigator.serviceWorker.register('{{ asset('sw.js') }}').catch(function () {
                    // PWA enhancement only. The dashboard must keep working without it.
                });
            });
        }
    });
</script>

@if($originPayPusherEnabled)
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const realtime = window.OriginPayRealtime || {};
        const permissionButton = document.getElementById('v2-enable-device-notifications');

        function canUseDeviceNotifications() {
            return window.isSecureContext && 'Notification' in window && 'serviceWorker' in navigator;
        }

        function updatePermissionButton() {
            if (!permissionButton || !canUseDeviceNotifications()) return;
            permissionButton.style.display = Notification.permission === 'default' ? 'inline-flex' : 'none';
        }

        async function requestDeviceNotifications() {
            if (!canUseDeviceNotifications()) return false;

            const permission = await Notification.requestPermission();
            updatePermissionButton();
            return permission === 'granted';
        }

        async function showDeviceNotification(payload) {
            if (!canUseDeviceNotifications() || Notification.permission !== 'granted') return;

            const registration = await navigator.serviceWorker.ready;
            if (!registration || !registration.active) return;

            registration.active.postMessage({
                type: 'ORIGINPAY_SHOW_NOTIFICATION',
                payload: {
                    title: payload.title || 'OriginPay',
                    message: payload.message || 'Nova notificação recebida.',
                    icon: realtime.icon,
                    badge: realtime.badge,
                    action_link: payload.action_link || '{{ route('user.dashboard') }}',
                    timestamp: payload.timestamp || new Date().toISOString(),
                }
            });
        }

        function showInAppToast(payload) {
            const message = payload.message || payload.title || 'Nova notificação recebida.';

            if (typeof notify === 'function') {
                notify('info', message);
                return;
            }

            if (window.Notify) {
                new Notify({
                    status: 'info',
                    title: payload.title || 'OriginPay',
                    text: message,
                    effect: 'fade',
                    speed: 300,
                    autoclose: true,
                    autotimeout: 5000,
                });
            }
        }

        function handleRealtimeNotification(payload) {
            if (window.originPayNotifications?.incrementBadge) {
                window.originPayNotifications.incrementBadge();
            }

            if (window.originPayNotifications?.refresh && window.globalNotifDropdownOpen) {
                window.originPayNotifications.refresh();
            }

            showInAppToast(payload || {});
            showDeviceNotification(payload || {});
        }

        if (permissionButton) {
            permissionButton.addEventListener('click', requestDeviceNotifications);
            updatePermissionButton();
        }

        if (!realtime.enabled || typeof Pusher === 'undefined') return;

        try {
            const pusher = new Pusher(realtime.key, {
                cluster: realtime.cluster,
                encrypted: true,
                forceTLS: true,
                authEndpoint: realtime.authEndpoint,
                auth: {
                    headers: {
                        'X-CSRF-TOKEN': realtime.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    }
                }
            });

            const channel = pusher.subscribe(`private-App.Models.User.${realtime.userId}`);
            channel.bind('notification.received', handleRealtimeNotification);
            channel.bind('Illuminate\\Notifications\\Events\\BroadcastNotificationCreated', handleRealtimeNotification);

            if (typeof channel.bind_global === 'function') {
                channel.bind_global(function (eventName, data) {
                    if (eventName && eventName.includes('BroadcastNotificationCreated')) {
                        handleRealtimeNotification(data || {});
                    }
                });
            }
        } catch (error) {
            console.warn('OriginPay realtime notifications unavailable.', error);
        }
    });
</script>
@endif

@yield('scripts')
@stack('scripts')

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (window.innerWidth <= 768) {
            document.querySelectorAll('table').forEach(function(table) {
                // Check if it's already a card table or if we want to skip it
                if (table.classList.contains('no-mobile-cards')) return;
                
                table.classList.add('v2-mobile-card-table');
                
                let headers = [];
                table.querySelectorAll('thead th').forEach(function(th) {
                    headers.push(th.innerText.trim());
                });
                
                table.querySelectorAll('tbody tr').forEach(function(tr) {
                    tr.querySelectorAll('td').forEach(function(td, index) {
                        if (headers[index]) {
                            td.setAttribute('data-label', headers[index]);
                        }
                    });
                });
            });
        }
    });
    </script>
</body>
</html>





