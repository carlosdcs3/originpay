<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Documentação OriginPay — API de Pagamentos, PIX, Cartão e Webhooks')</title>
    <meta name="description" content="Integre pagamentos PIX e cartão com a API da OriginPay. Documentação para desenvolvedores com sandbox, webhooks, HMAC, idempotência, SDKs e OpenAPI.">
    <meta name="keywords" content="API de pagamentos, gateway de pagamento, PIX API, API PIX, cartão API, webhooks, split de pagamento, sandbox, idempotência, OpenAPI, OriginPay">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Open Graph / Twitter Cards --}}
    <meta property="og:title" content="Documentação OriginPay">
    <meta property="og:description" content="APIs, webhooks, sandbox e ferramentas para integrar pagamentos com segurança e escala.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ asset('frontend/images/og-docs.jpg') }}">
    <meta name="twitter:card" content="summary_large_image">

    {{-- Structured Data (JSON-LD) --}}
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "WebSite",
      "name": "Documentação OriginPay",
      "url": "{{ url('/docs') }}"
    }
    </script>

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

    {{-- â”€â”€â”€ NAVBAR — Idêntica Ã  Landing Page â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ --}}
    <nav class="doc-navbar" id="docNavbar">
        <div class="doc-nav-inner">

            {{-- Left: Logo + Links --}}
            <div class="doc-nav-left">
                <a href="{{ route('home') }}" class="doc-logo" style="display:flex;align-items:center;gap:10px;text-decoration:none;">
                    <img src="{{ asset('frontend/images/originpay/originpay-icon-transparent.svg') }}" alt="OriginPay Icon" style="height: 32px; width: auto; border-radius: 8px; ">
                    <span style="font-size:1.1rem;font-weight:700;color:#f4f0ff;letter-spacing:-.02em;">Origin<span style="color:#a78bfa;">Pay</span></span>
                </a>
                <div class="doc-nav-links">
                    <a href="{{ route('home') }}"                class="doc-nav-link">Home</a>
                    <a href="{{ route('home') }}#ecossistema"    class="doc-nav-link">Ecossistema</a>
                    <a href="{{ route('home') }}#precos"         class="doc-nav-link">Preços</a>
                    <a href="{{ route('docs.index') }}"          class="doc-nav-link active-nav">Documentação</a>
                    <a href="{{ route('status') }}"              class="doc-nav-link">Status</a>
                </div>
            </div>

            {{-- Right: Search + CTAs --}}
            <div class="doc-nav-right">
                <div class="doc-search-wrap" id="docSearchTrigger" style="cursor: pointer;">
                    <i class="fas fa-search"></i>
                    <div class="doc-search-input" style="display:flex; align-items:center; justify-content:space-between; width:220px; color:var(--doc-muted);">
                        <span>Buscar (Ctrl+K)</span>
                        <span style="background: rgba(255,255,255,0.1); padding: 2px 6px; border-radius: 4px; font-size: 0.65rem;">âŒ˜K</span>
                    </div>
                </div>

                {{-- Mobile hamburger --}}
                <button class="doc-nav-mobile-btn" id="docSidebarToggle" aria-label="Menu">
                    <i class="fas fa-bars"></i>
                </button>

                @auth
                    <a href="{{ route('user.dashboard') }}" class="btn-doc btn-doc-primary">Dashboard</a>
                @else
                    <a href="{{ route('user.login') }}"    class="btn-doc btn-doc-secondary">Entrar</a>
                    <a href="{{ route('user.register') }}" class="btn-doc btn-doc-primary">Criar conta</a>
                @endauth
            </div>

        </div>
    </nav>

    {{-- â”€â”€â”€ LAYOUT SHELL â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ --}}
    <div class="doc-layout">

        {{-- â”€â”€â”€ SIDEBAR â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ --}}
        <aside class="doc-sidebar" id="docSidebar">
            <div class="doc-sidebar-inner">

                <div class="doc-sidebar-group">
                    <div class="doc-sidebar-title">Introdução</div>
                    <ul class="doc-sidebar-nav">
                        <li><a href="{{ route('docs.index') }}"               class="doc-sidebar-item {{ request()->routeIs('docs.index') ? 'active' : '' }}">Visão Geral</a></li>
                        <li><a href="{{ route('docs.show', 'authentication') }}" class="doc-sidebar-item {{ request()->is('docs/authentication') ? 'active' : '' }}">Autenticação</a></li>
                        <li><a href="{{ route('docs.show', 'environments') }}"     class="doc-sidebar-item {{ request()->is('docs/environments') ? 'active' : '' }}">Ambientes</a></li>
                    </ul>
                </div>

                <div class="doc-sidebar-group">
                    <div class="doc-sidebar-title">Pagamentos</div>
                    <ul class="doc-sidebar-nav">
                        <li><a href="{{ route('docs.show', 'charges') }}" class="doc-sidebar-item {{ request()->is('docs/charges') ? 'active' : '' }}">Cobranças</a></li>
                        <li><a href="{{ route('docs.show', 'pix') }}"      class="doc-sidebar-item {{ request()->is('docs/pix') ? 'active' : '' }}">PIX</a></li>
                        <li><a href="{{ route('docs.show', 'card') }}"     class="doc-sidebar-item {{ request()->is('docs/card') ? 'active' : '' }}">Cartão</a></li>
                        <li><a href="{{ route('docs.show', 'refunds') }}"  class="doc-sidebar-item {{ request()->is('docs/refunds') ? 'active' : '' }}">Reembolsos</a></li>
                        <li><a href="{{ route('docs.show', 'payouts') }}"  class="doc-sidebar-item {{ request()->is('docs/payouts') ? 'active' : '' }}">Saques</a></li>
                    </ul>
                </div>

                <div class="doc-sidebar-group">
                    <div class="doc-sidebar-title">Recursos Avançados</div>
                    <ul class="doc-sidebar-nav">
                        <li><a href="{{ route('docs.show', 'webhooks') }}" class="doc-sidebar-item {{ request()->is('docs/webhooks') ? 'active' : '' }}">Webhooks</a></li>
                        <li><a href="{{ route('docs.show', 'idempotency') }}" class="doc-sidebar-item {{ request()->is('docs/idempotency') ? 'active' : '' }}">Idempotência</a></li>
                        <li><a href="{{ route('docs.show', 'rate-limits') }}" class="doc-sidebar-item {{ request()->is('docs/rate-limits') ? 'active' : '' }}">Rate Limits</a></li>
                        <li><a href="{{ route('docs.show', 'errors') }}" class="doc-sidebar-item {{ request()->is('docs/errors') ? 'active' : '' }}">Erros</a></li>
                    </ul>
                </div>

                <div class="doc-sidebar-group">
                    <div class="doc-sidebar-title">Referência (API v1)</div>
                    <ul class="doc-sidebar-nav">
                        <li><a href="{{ route('docs.v1.api_reference.index') }}" class="doc-sidebar-item {{ request()->routeIs('docs.v1.api_reference.*') ? 'active' : '' }}">Endpoints</a></li>
                        <li><a href="{{ route('docs.show', 'changelog') }}" class="doc-sidebar-item {{ request()->is('docs/changelog') ? 'active' : '' }}">Changelog</a></li>
                        <li><a href="{{ route('docs.v1.explorer') }}" class="doc-sidebar-item {{ request()->routeIs('docs.v1.explorer') ? 'active' : '' }}"><i class="fas fa-flask me-2"></i> API Explorer</a></li>
                    </ul>
                </div>

                <div style="margin-top: 40px; padding: 16px; background: rgba(255,255,255,0.02); border: 1px solid var(--doc-border); border-radius: 8px;">
                    <div style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--doc-muted); margin-bottom: 12px; font-weight: 600;">What's New</div>
                    <div style="font-size: 0.9rem; margin-bottom: 8px;">
                        <span style="background: rgba(124, 58, 237, 0.1); color: #a78bfa; padding: 2px 6px; border-radius: 4px; font-size: 0.65rem; font-weight: 600; margin-right: 6px;">Added</span>
                        <a href="{{ route('docs.v1.release_notes') }}" style="color: #fff; text-decoration: none;">API Reference Enterprise</a>
                    </div>
                    <div style="font-size: 0.9rem;">
                        <span style="background: rgba(124, 58, 237, 0.1); color: #a78bfa; padding: 2px 6px; border-radius: 4px; font-size: 0.65rem; font-weight: 600; margin-right: 6px;">Added</span>
                        <a href="{{ route('docs.v1.release_notes') }}" style="color: #fff; text-decoration: none;">Webhook Simulator</a>
                    </div>
                </div>

            </div>
        </aside>

        {{-- â”€â”€â”€ MAIN CONTENT â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ --}}
        <main class="doc-main">
            {{-- Contextual Top Navigation --}}
            <div class="doc-top-nav">
                <a href="{{ route('home') }}" class="doc-top-nav-link">
                    <i class="fas fa-arrow-left"></i> Voltar ao Início
                </a>
            </div>

            @yield('content')

            <footer class="doc-footer">
                <div>&copy; {{ date('Y') }} OriginPay. Todos os direitos reservados.</div>
                <div class="doc-footer-links">
                    <a href="#">Status</a>
                    <a href="#">Changelog</a>
                    <a href="#">Termos</a>
                    <a href="#">Privacidade</a>
                    <a href="#">LGPD</a>
                </div>
            </footer>
        </main>

        {{-- â”€â”€â”€ RIGHT TOC â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ --}}
        @hasSection('toc')
            <aside class="doc-toc">
                <div class="doc-toc-title">Nesta página</div>
                @yield('toc')
            </aside>
        @endif

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
        // Mobile sidebar toggle
        const sidebarToggle = document.getElementById('docSidebarToggle');
        const docSidebar    = document.getElementById('docSidebar');
        if (sidebarToggle && docSidebar) {
            sidebarToggle.addEventListener('click', () => {
                docSidebar.classList.toggle('open');
            });
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (e) => {
            if (docSidebar && docSidebar.classList.contains('open')) {
                if (!docSidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                    docSidebar.classList.remove('open');
                }
            }
        });

        // Initialize Lucide Icons
        lucide.createIcons();

        // Deep Links Injection
        document.querySelectorAll('.doc-main h2, .doc-main h3').forEach(heading => {
            if(!heading.id) return;
            const icon = document.createElement('i');
            icon.setAttribute('data-lucide', 'link');
            icon.classList.add('deep-link-icon');
            heading.appendChild(icon);
            
            icon.addEventListener('click', () => {
                const url = window.location.origin + window.location.pathname + '#' + heading.id;
                navigator.clipboard.writeText(url).then(() => {
                    const originalColor = icon.style.color;
                    icon.style.color = '#a78bfa';
                    setTimeout(() => icon.style.color = originalColor, 1500);
                });
            });
        });
        lucide.createIcons();

    </script>

    @yield('scripts')
</body>
</html>
