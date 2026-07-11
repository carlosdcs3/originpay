<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'API Reference') â€” OriginPay</title>
    <meta name="description" content="ReferÃªncia completa da API OriginPay v1.">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Favicon --}}
    <link rel="icon" href="{{ asset('frontend/images/originpay/originpay-app-icon.svg') }}" type="image/svg+xml">
    <link rel="shortcut icon" href="{{ asset('frontend/images/originpay/originpay-app-icon.png') }}" type="image/png">
    <link rel="apple-touch-icon" href="{{ asset('frontend/images/originpay/apple-touch-icon.png') }}">

    {{-- Fonts & Icons --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('general/css/fontawesome.min.css') }}">

    {{-- Lucide Icons for Deep Links --}}
    <script src="https://unpkg.com/lucide@latest"></script>

    {{-- Docs CSS --}}
    <link rel="stylesheet" href="{{ asset('frontend/css/docs.css') }}">

    <style>
        /* 3-Column API Reference Layout overrides */
        .api-layout {
            display: flex;
            margin-top: var(--doc-nav-height);
            min-height: calc(100vh - var(--doc-nav-height));
        }
        .api-main {
            flex: 1;
            margin-left: var(--doc-sidebar-width);
            display: flex;
        }
        .api-content {
            flex: 1;
            padding: 52px 64px;
            max-width: 800px;
        }
        .api-code-panel {
            width: 480px;
            background: #0d0d14;
            border-left: 1px solid var(--doc-border);
            position: sticky;
            top: var(--doc-nav-height);
            height: calc(100vh - var(--doc-nav-height));
            overflow-y: auto;
            padding: 52px 32px;
        }
        @media (max-width: 1400px) {
            .api-code-panel { width: 400px; padding: 52px 24px; }
        }
        @media (max-width: 1100px) {
            .api-main { flex-direction: column; }
            .api-code-panel {
                width: 100%;
                height: auto;
                position: static;
                border-left: none;
                border-top: 1px solid var(--doc-border);
            }
        }
        @media (max-width: 900px) {
            .api-main { margin-left: 0; }
            .api-content { padding: 32px 24px; }
        }

        /* Deep Links */
        .deep-link-icon {
            opacity: 0;
            color: var(--doc-muted);
            margin-left: 8px;
            cursor: pointer;
            transition: opacity 0.2s, color 0.2s;
            width: 18px;
            height: 18px;
            vertical-align: middle;
        }
        h2:hover .deep-link-icon, h3:hover .deep-link-icon {
            opacity: 1;
        }
        .deep-link-icon:hover {
            color: var(--doc-primary);
        }
    </style>
    @yield('styles')
</head>
<body>

    {{-- NAVBAR --}}
    <nav class="doc-navbar" id="docNavbar">
        <div class="doc-nav-inner">
            <div style="display:flex; align-items:center; gap:40px;">
                <a href="{{ route('home') }}" class="doc-logo">
                    <img src="{{ asset('frontend/images/OriginPay-logo.png') }}" alt="OriginPay">
                    OriginPay
                </a>
                <div class="doc-nav-links">
                    <a href="{{ route('docs.index') }}" class="doc-nav-link">Guias</a>
                    <a href="{{ route('docs.v1.api_reference.index') }}" class="doc-nav-link active-nav">API Reference</a>
                    <a href="{{ route('docs.v1.explorer') }}" class="doc-nav-link">API Explorer</a>

                </div>
            </div>

            <div class="doc-nav-right">
                <div class="doc-search-wrap" id="docSearchTrigger" style="cursor: pointer;">
                    <i class="fas fa-search"></i>
                    <div class="doc-search-input" style="display:flex; align-items:center; justify-content:space-between; width:220px; color:var(--doc-muted);">
                        <span>Buscar (Ctrl+K)</span>
                        <span style="background: rgba(255,255,255,0.1); padding: 2px 6px; border-radius: 4px; font-size: 0.65rem;">âŒ˜K</span>
                    </div>
                </div>

                <button class="doc-nav-mobile-btn" id="docSidebarToggle" aria-label="Menu">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </nav>

    {{-- LAYOUT SHELL --}}
    <div class="api-layout">

        {{-- SIDEBAR --}}
        <aside class="doc-sidebar" id="docSidebar">
            <div class="doc-sidebar-inner">

                {{-- Version Selector --}}
                <div style="margin-bottom: 24px;">
                    <select style="width: 100%; background: rgba(255,255,255,0.05); border: 1px solid var(--doc-border); color: #fff; padding: 8px 12px; border-radius: 6px; outline: none; font-family: var(--doc-font); cursor: pointer;">
                        <option value="v1">API Version v1</option>
                    </select>
                </div>

                <div class="doc-sidebar-group">
                    <div class="doc-sidebar-title">Getting Started</div>
                    <ul class="doc-sidebar-nav">
                        <li><a href="{{ route('docs.v1.api_reference.index') }}" class="doc-sidebar-item {{ request()->routeIs('docs.v1.api_reference.index') ? 'active' : '' }}">Introduction</a></li>
                        <li><a href="{{ route('docs.v1.migration') }}" class="doc-sidebar-item {{ request()->routeIs('docs.v1.migration') ? 'active' : '' }}">Migration Guide</a></li>
                    </ul>
                </div>

                <div class="doc-sidebar-group">
                    <div class="doc-sidebar-title">Payments</div>
                    <ul class="doc-sidebar-nav">
                        <li><a href="{{ route('docs.v1.api_reference.show', 'create-payment') }}" class="doc-sidebar-item {{ request()->is('docs/v1/api-reference/create-payment') ? 'active' : '' }}">Create a Payment</a></li>
                        <li><a href="{{ route('docs.v1.api_reference.show', 'get-payment') }}" class="doc-sidebar-item {{ request()->is('docs/v1/api-reference/get-payment') ? 'active' : '' }}">Retrieve a Payment</a></li>
                        <li><a href="{{ route('docs.v1.api_reference.show', 'create-refund') }}" class="doc-sidebar-item {{ request()->is('docs/v1/api-reference/create-refund') ? 'active' : '' }}">Create a Refund</a></li>
                    </ul>
                </div>

                <div class="doc-sidebar-group">
                    <div class="doc-sidebar-title">Payouts</div>
                    <ul class="doc-sidebar-nav">
                        <li><a href="{{ route('docs.v1.api_reference.show', 'create-payout') }}" class="doc-sidebar-item {{ request()->is('docs/v1/api-reference/create-payout') ? 'active' : '' }}">Create a Payout</a></li>
                    </ul>
                </div>

                <div class="doc-sidebar-group">
                    <div class="doc-sidebar-title">Core</div>
                    <ul class="doc-sidebar-nav">
                        <li><a href="{{ route('docs.v1.api_reference.show', 'get-balance') }}" class="doc-sidebar-item {{ request()->is('docs/v1/api-reference/get-balance') ? 'active' : '' }}">Retrieve Balance</a></li>
                        <li><a href="{{ route('docs.v1.api_reference.show', 'create-customer') }}" class="doc-sidebar-item {{ request()->is('docs/v1/api-reference/create-customer') ? 'active' : '' }}">Create a Customer</a></li>
                    </ul>
                </div>

                <div class="doc-sidebar-group">
                    <div class="doc-sidebar-title">Webhooks</div>
                    <ul class="doc-sidebar-nav">
                        <li><a href="{{ route('docs.v1.api_reference.show', 'test-webhook') }}" class="doc-sidebar-item {{ request()->is('docs/v1/api-reference/test-webhook') ? 'active' : '' }}">Test Webhook</a></li>
                    </ul>
                </div>

                <div class="doc-sidebar-group">
                    <div class="doc-sidebar-title">Tools</div>
                    <ul class="doc-sidebar-nav">
                        <li><a href="{{ route('docs.v1.explorer') }}" class="doc-sidebar-item {{ request()->routeIs('docs.v1.explorer') ? 'active' : '' }}"><i class="fas fa-terminal"></i> API Explorer</a></li>
                        <li><a href="{{ route('docs.v1.webhook_simulator') }}" class="doc-sidebar-item {{ request()->routeIs('docs.v1.webhook_simulator') ? 'active' : '' }}"><i class="fas fa-satellite-dish"></i> Webhook Simulator</a></li>
                        <li><a href="{{ route('docs.v1.resources') }}" class="doc-sidebar-item {{ request()->routeIs('docs.v1.resources') ? 'active' : '' }}"><i class="fas fa-box-open"></i> Dev Resources</a></li>
                        <li><a href="{{ route('docs.v1.release_notes') }}" class="doc-sidebar-item {{ request()->routeIs('docs.v1.release_notes') ? 'active' : '' }}"><i class="fas fa-clipboard-list"></i> Release Notes</a></li>
                    </ul>
                </div>

            </div>
        </aside>

        {{-- MAIN CONTENT --}}
        <main class="api-main">
            <div class="api-content">
                @yield('content')
            </div>

            @hasSection('code_panel')
                <div class="api-code-panel">
                    @yield('code_panel')
                </div>
            @endif
        </main>

    </div>

    {{-- Search Modal --}}
    <div id="docSearchModal" style="display: none; position: fixed; inset: 0; z-index: 1000; background: rgba(0,0,0,0.8); backdrop-filter: blur(4px); align-items: flex-start; justify-content: center; padding-top: 10vh;">
        <div style="background: var(--doc-surface); border: 1px solid var(--doc-border); border-radius: 12px; width: 100%; max-width: 600px; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.5);">
            <div style="padding: 16px 20px; border-bottom: 1px solid var(--doc-border); display: flex; align-items: center; gap: 12px;">
                <i class="fas fa-search" style="color: var(--doc-primary);"></i>
                <input type="text" id="docSearchInputNative" placeholder="Search documentation..." style="flex: 1; background: transparent; border: none; outline: none; color: #fff; font-size: 1.1rem; font-family: var(--doc-font);">
                <button onclick="closeSearch()" style="background: none; border: none; color: var(--doc-muted); cursor: pointer;"><i class="fas fa-times"></i></button>
            </div>
            <div id="docSearchResults" style="max-height: 400px; overflow-y: auto; padding: 12px;">
                <div style="padding: 24px; text-align: center; color: var(--doc-muted); font-size: 0.9rem;">
                    Type to search endpoints, guides, and SDKs...
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('frontend/js/docs.js') }}"></script>
    <script src="{{ asset('frontend/js/docs-search.js') }}"></script>

    <script>
        // Initialize Lucide Icons
        lucide.createIcons();

        // Deep Links Injection
        document.querySelectorAll('.api-content h2, .api-content h3').forEach(heading => {
            if(!heading.id) return;
            const icon = document.createElement('i');
            icon.setAttribute('data-lucide', 'link');
            icon.classList.add('deep-link-icon');
            heading.appendChild(icon);
            
            icon.addEventListener('click', () => {
                const url = window.location.origin + window.location.pathname + '#' + heading.id;
                navigator.clipboard.writeText(url).then(() => {
                    const originalColor = icon.style.color;
                    icon.style.color = '#10b981';
                    setTimeout(() => icon.style.color = originalColor, 1500);
                });
            });
        });
        // Re-init lucide for newly added icons
        lucide.createIcons();

    </script>

    @yield('scripts')
</body>
</html>
