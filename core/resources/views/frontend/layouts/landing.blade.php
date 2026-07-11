<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield("title", "OriginPay — Infraestrutura completa de pagamentos para o seu negócio")</title>
    <meta property="og:title" content="@yield('title', 'OriginPay')">
    <meta property="og:type" content="website">
    <meta name="description" content="@yield('description', 'Receba, integre, gerencie, proteja e escale com OriginPay.')">
    <meta property="og:description" content="@yield('description', 'Receba, integre, gerencie, proteja e escale com OriginPay.')">

    {{-- Favicon --}}
    <link rel="icon" href="{{ asset('frontend/images/originpay/originpay-app-icon.svg') }}" type="image/svg+xml">
    <link rel="shortcut icon" href="{{ asset('frontend/images/originpay/originpay-app-icon.png') }}" type="image/png">
    <link rel="apple-touch-icon" href="{{ asset('frontend/images/originpay/apple-touch-icon.png') }}">

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('general/css/fontawesome.min.css') }}">

    {{-- Landing CSS --}}
    <link rel="stylesheet" href="{{ asset('frontend/css/OriginPay.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('frontend/css/originpay-core.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('frontend/css/originpay-polish.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('frontend/css/originpay-qa.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('frontend/css/originpay-impeccable-fixes.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('frontend/css/originpay-rc.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('frontend/css/originpay-motion-responsive.css') }}?v={{ time() }}">

    {{-- 3D hero model renderer. The Blade keeps a PNG fallback while the GLB is not present. --}}
    <script type="module" src="https://unpkg.com/@google/model-viewer/dist/model-viewer.min.js"></script>
</head>
<body class="{{ request()->routeIs('home') ? 'op-landing-redesign' : 'op-institutional-v2' }}">

    {{-- =============== NAVIGATION =============== --}}
    <nav class="op-nav" id="opNav">
        <div class="op-nav-inner">
            <a href="{{ route('home') }}" class="op-logo" style="display:flex;flex-direction:column;align-items:flex-start;gap:4px;text-decoration:none;">
                <img src="{{ asset('frontend/images/originpay/originpay-logo-correct.png') }}" alt="OriginPay" class="op-logo-image">
                <span class="op-logo-wordmark">Origin<span>Pay</span></span>
                <span style="color:var(--text-muted);font-size:0.68rem;font-weight:500;line-height:1;">Infraestrutura para receber.</span>
            </a>

            <div class="op-nav-links">
                <a href="#ecossistema" class="op-nav-link">Ecossistema</a>
                <a href="#desenvolvedores" class="op-nav-link">Desenvolvedores</a>
                <a href="#seguranca" class="op-nav-link">Segurança</a>
                <a href="#precos" class="op-nav-link">Preços</a>
                <a href="{{ route('docs.index') }}" class="op-nav-link">Documentação</a>
            </div>

            <div class="op-nav-actions">
                @auth
                    <a href="{{ route('user.dashboard') }}" class="btn btn-primary">Dashboard <i class="fas fa-arrow-right" style="font-size:.75rem;" aria-hidden="true"></i></a>
                @else
                    <a href="{{ route('user.login') }}" class="btn btn-secondary">Entrar</a>
                    <a href="{{ route('user.register') }}" class="btn btn-primary ">Criar conta<i class="fas fa-arrow-right" style="font-size:.75rem;" aria-hidden="true"></i></a>
                @endauth
            </div>

            <button class="op-hamburger" id="opHamburger" aria-label="Menu">
                <span></span><span></span><span></span>
            </button>
        </div>
    </nav>

    {{-- Mobile Menu --}}
    <div class="op-mobile-menu" id="opMobileMenu">
        <a href="#ecossistema">Ecossistema</a>
        <a href="#desenvolvedores">Desenvolvedores</a>
        <a href="#seguranca">Segurança</a>
        <a href="#precos">Preços</a>
        <a href="{{ route('docs.index') }}">Documentação</a>
        <div class="op-mobile-actions">
            @auth
                <a href="{{ route('user.dashboard') }}" class="btn btn-primary" style="flex:1;justify-content:center;">Dashboard</a>
            @else
                <a href="{{ route('user.login') }}" class="btn btn-secondary" style="flex:1;justify-content:center;">Entrar</a>
                <a href="{{ route('user.register') }}" class="btn btn-primary" style="flex:1;justify-content:center;">Criar conta</a>
            @endauth
        </div>
    </div>

    {{-- =============== ANNOUNCEMENT BAR =============== --}}
    

    {{-- =============== HERO SECTION =============== --}}
    
    @yield('content')

    <footer class="op-footer">
        <div class="container">
            <div class="op-footer-grid">
                <div class="op-footer-brand">
                    <a href="{{ route('home') }}" class="op-logo" style="display:flex;flex-direction:column;align-items:flex-start;gap:4px;text-decoration:none;margin-bottom:20px;">
                        <span class="op-logo-wordmark" style="font-size:1.5rem;">Origin<span>Pay</span></span>
                        <span style="color:var(--text-muted);font-size:0.72rem;font-weight:500;">Infraestrutura para receber.</span>
                    </a>
                    <p style="color:var(--text-muted);font-size:0.9rem;margin-bottom:0;max-width:340px;">Infraestrutura de pagamentos robusta e escalável para plataformas de tecnologia.</p>
                    <div class="op-footer-socials" style="margin-top:20px;">
                        <a href="https://github.com/originpay" class="op-footer-social" aria-label="GitHub"><i class="fab fa-github" aria-hidden="true"></i></a>
                        <a href="https://twitter.com/originpay" class="op-footer-social" aria-label="Twitter"><i class="fab fa-twitter" aria-hidden="true"></i></a>
                        <a href="https://linkedin.com/company/originpay" class="op-footer-social" aria-label="LinkedIn"><i class="fab fa-linkedin-in" aria-hidden="true"></i></a>
                    </div>
                </div>

                <div>
                    <div class="op-footer-col-title">Produto</div>
                    <ul class="op-footer-links">
                        <li><a href="{{ route('ecossistema') }}">Ecossistema</a></li>
                        <li><a href="{{ route('precos') }}">Preços</a></li>
                        <li><a href="{{ route('changelog') }}">Changelog</a></li>
                        <li><a href="{{ route('status') }}">Status</a></li>
                    </ul>
                </div>

                <div>
                    <div class="op-footer-col-title">Desenvolvedores</div>
                    <ul class="op-footer-links">
                        <li><a href="{{ route('docs.index') }}">Documentação</a></li>
                        <li><a href="{{ route('docs.auth') }}">Autenticação</a></li>
                        <li><a href="{{ route('docs.webhooks') }}">Webhooks</a></li>
                        <li><a href="{{ route('docs.openapi') }}">OpenAPI Spec</a></li>
                    </ul>
                </div>

                <div>
                    <div class="op-footer-col-title">Empresa</div>
                    <ul class="op-footer-links">
                        <li><a href="{{ route('sobre') }}">Sobre nós</a></li>
                        <li><a href="{{ route('blog.index') }}">Blog</a></li>
                        <li><a href="{{ route('carreiras') }}">Carreiras</a></li>
                        <li><a href="{{ route('contato') }}">Contato</a></li>
                    </ul>
                </div>

                <div>
                    <div class="op-footer-col-title">Jurídico</div>
                    <ul class="op-footer-links">
                        <li><a href="{{ route('termos') }}">Termos de Uso</a></li>
                        <li><a href="{{ route('privacidade') }}">Privacidade</a></li>
                        <li><a href="{{ route('lgpd') }}">LGPD</a></li>
                        <li><a href="{{ route('seguranca') }}">Segurança</a></li>
                    </ul>
                </div>
            </div>

            <div class="op-footer-bottom">
                <div>&copy; {{ date('Y') }} OriginPay. Todos os direitos reservados.</div>
                <div class="op-footer-bottom-right">
                    <a href="{{ route('termos') }}">Termos</a>
                    <a href="{{ route('privacidade') }}">Privacidade</a>
                </div>
            </div>
        </div>
    </footer>

    {{-- =============== SCRIPTS =============== --}}
    <script>
    (function() {
        'use strict';

        // -- Navbar scroll effect --
        const nav = document.getElementById('opNav');
        const announcement = document.getElementById('opAnnouncement');
        let announcementHeight = announcement ? announcement.offsetHeight : 0;

        function onScroll() {
            if (window.scrollY > 20) {
                nav.classList.add('scrolled');
                if (announcement) announcement.style.transform = 'translateY(-100%)';
            } else {
                nav.classList.remove('scrolled');
                if (announcement) announcement.style.transform = '';
            }
        }

        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();

        // -- Mobile hamburger --
        const hamburger = document.getElementById('opHamburger');
        const mobileMenu = document.getElementById('opMobileMenu');

        if (hamburger && mobileMenu) {
            hamburger.setAttribute('aria-expanded', 'false');

            hamburger.addEventListener('click', function() {
                const isOpen = mobileMenu.classList.toggle('open');
                hamburger.classList.toggle('active', isOpen);
                hamburger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            });

            // Close mobile menu on link click
            mobileMenu.querySelectorAll('a').forEach(link => {
                link.addEventListener('click', () => {
                    hamburger.classList.remove('active');
                    hamburger.setAttribute('aria-expanded', 'false');
                    mobileMenu.classList.remove('open');
                });
            });
        }

        // -- Smooth anchor scroll with offset --
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    e.preventDefault();
                    const offset = 90;
                    window.scrollTo({ top: target.offsetTop - offset, behavior: 'smooth' });
                }
            });
        });

        // -- Scroll Reveal --
        const revealElements = document.querySelectorAll('.reveal, .reveal-left, .reveal-right');

        const revealObserver = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    revealObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

        revealElements.forEach(el => revealObserver.observe(el));

        // -- Pillar tabs --
        const tabs = document.querySelectorAll('.op-pillar-tab');
        const contents = document.querySelectorAll('.op-pillar-content');

        function activatePillarTab(tab) {
            const pillar = tab.dataset.pillar;

            tabs.forEach(t => {
                t.classList.remove('active');
                t.setAttribute('aria-selected', 'false');
            });
            tab.classList.add('active');
            tab.setAttribute('aria-selected', 'true');

            contents.forEach(c => c.classList.remove('active'));
            const target = document.getElementById('pillar-' + pillar);
            if (target) {
                target.classList.add('active');
                target.querySelectorAll('.reveal, .reveal-left, .reveal-right').forEach(el => {
                    el.classList.remove('visible');
                    setTimeout(() => el.classList.add('visible'), 50);
                });
            }
        }

        tabs.forEach((tab, index) => {
            tab.setAttribute('tabindex', tab.classList.contains('active') ? '0' : '-1');
            tab.addEventListener('click', function() {
                activatePillarTab(this);
                tabs.forEach(t => t.setAttribute('tabindex', '-1'));
                this.setAttribute('tabindex', '0');
            });
            tab.addEventListener('keydown', function(event) {
                const nextKeys = ['ArrowRight', 'ArrowDown'];
                const prevKeys = ['ArrowLeft', 'ArrowUp'];
                if (![...nextKeys, ...prevKeys, 'Home', 'End'].includes(event.key)) return;

                event.preventDefault();
                let nextIndex = index;
                if (nextKeys.includes(event.key)) nextIndex = (index + 1) % tabs.length;
                if (prevKeys.includes(event.key)) nextIndex = (index - 1 + tabs.length) % tabs.length;
                if (event.key === 'Home') nextIndex = 0;
                if (event.key === 'End') nextIndex = tabs.length - 1;

                const nextTab = tabs[nextIndex];
                if (nextTab) {
                    nextTab.focus();
                    activatePillarTab(nextTab);
                    tabs.forEach(t => t.setAttribute('tabindex', '-1'));
                    nextTab.setAttribute('tabindex', '0');
                }
            });
        });

        // -- FAQ Accordion --
        document.querySelectorAll('.op-faq-question').forEach((question, index) => {
            const item = question.parentElement;
            const answer = item.querySelector('.op-faq-answer');
            const answerId = answer.id || 'op-faq-answer-' + index;
            answer.id = answerId;
            question.setAttribute('role', 'button');
            question.setAttribute('tabindex', '0');
            question.setAttribute('aria-controls', answerId);
            question.setAttribute('aria-expanded', item.classList.contains('open') ? 'true' : 'false');

            function toggleFaq() {
                const isOpen = item.classList.contains('open');

                // Close all
                document.querySelectorAll('.op-faq-item').forEach(i => {
                    i.classList.remove('open');
                    const itemQuestion = i.querySelector('.op-faq-question');
                    if (itemQuestion) itemQuestion.setAttribute('aria-expanded', 'false');
                });

                // Open clicked (if wasn't open)
                if (!isOpen) {
                    item.classList.add('open');
                    question.setAttribute('aria-expanded', 'true');
                }
            }

            question.addEventListener('click', toggleFaq);
            question.addEventListener('keydown', function(event) {
                if (event.key !== 'Enter' && event.key !== ' ') return;
                event.preventDefault();
                toggleFaq();
            });
        });

        // -- Animated counters --
        function animateCounter(el, target, duration, suffix) {
            const start = performance.now();
            const isDecimal = target % 1 !== 0;

            function update(timestamp) {
                const elapsed = timestamp - start;
                const progress = Math.min(elapsed / duration, 1);
                const eased = 1 - Math.pow(1 - progress, 3);
                const current = eased * target;

                el.textContent = isDecimal ? current.toFixed(2) : Math.floor(current);

                if (progress < 1) requestAnimationFrame(update);
                else el.textContent = isDecimal ? target.toFixed(2) : target;
            }

            requestAnimationFrame(update);
        }

        const counterObserver = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const el = entry.target;
                    const target = parseFloat(el.dataset.target);
                    animateCounter(el, target, 1800);
                    counterObserver.unobserve(el);
                }
            });
        }, { threshold: 0.5 });

        document.querySelectorAll('.count-up').forEach(el => counterObserver.observe(el));

        // Interactive pricing simulator
        const pricingAmount = document.getElementById('opPricingAmount');
        const currency = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });

        function setPricingValue(id, value) {
            const el = document.getElementById(id);
            if (el) el.textContent = value;
        }

        function updatePricingSimulator() {
            if (!pricingAmount) return;

            const gross = Math.max(parseFloat(String(pricingAmount.value).replace(',', '.')) || 0, 0);
            const pixPercent = parseFloat(pricingAmount.dataset.pixPercent || '1.5') / 100;
            const pixFixed = parseFloat(pricingAmount.dataset.pixFixed || '0.30');
            const boletoFixed = parseFloat(pricingAmount.dataset.boletoFixed || '3.99');
            const cryptoPercent = parseFloat(pricingAmount.dataset.cryptoPercent || '2') / 100;

            const pixFee = (gross * pixPercent) + pixFixed;
            const boletoFee = boletoFixed;
            const cryptoFee = Math.max(gross * cryptoPercent, 0.30);

            setPricingValue('opPixFee', currency.format(pixFee));
            setPricingValue('opPixNet', 'líquido ' + currency.format(Math.max(gross - pixFee, 0)));
            setPricingValue('opBoletoFee', currency.format(boletoFee));
            setPricingValue('opBoletoNet', 'líquido ' + currency.format(Math.max(gross - boletoFee, 0)));
            setPricingValue('opCryptoFee', currency.format(cryptoFee));
            setPricingValue('opCryptoNet', 'líquido ' + currency.format(Math.max(gross - cryptoFee, 0)));
        }

        if (pricingAmount) {
            pricingAmount.addEventListener('input', updatePricingSimulator);
            updatePricingSimulator();
        }

        // -- Live API log animation --
        const apiRows = document.querySelectorAll('.op-api-row');
        let rowIndex = 0;

        function pulseRow() {
            apiRows.forEach(r => r.style.opacity = '1');
            if (apiRows[rowIndex]) {
                apiRows[rowIndex].style.opacity = '0.4';
                setTimeout(() => { if (apiRows[rowIndex]) apiRows[rowIndex].style.opacity = '1'; }, 300);
            }
            rowIndex = (rowIndex + 1) % apiRows.length;
        }

        if (apiRows.length) setInterval(pulseRow, 1800);

    })();
    </script>
</body>
</html>

