@extends('frontend.layouts.user-v2')

@section('content')

<style>
    /* Hub Global CSS (Settings & Developer) */
    .v2-settings-shell { display: flex; flex-direction: row; align-items: flex-start; gap: 32px; }
    
    /* Nav & Sidebar */
    .v2-settings-nav { width: 220px; flex-shrink: 0; }
    .v2-settings-panel { flex-grow: 1; min-width: 0; }
    
    .cfg-nav-section, .v2-settings-nav-section { 
        font-size: 0.65rem; font-weight: 700; color: var(--ds-text-muted); 
        text-transform: uppercase; letter-spacing: 0.05em; margin: 12px 0 6px; 
    }
    .cfg-nav-item, .v2-settings-nav-item { 
        display: flex; align-items: center; padding: 6px 10px; gap: 10px;
        color: var(--ds-text-secondary); font-size: 0.8125rem; font-weight: 500; 
        text-decoration: none; border-radius: 6px; transition: all 0.15s;
        border-left: 2px solid transparent; margin-bottom: 2px;
    }
    .cfg-nav-item:hover, .v2-settings-nav-item:hover { 
        background: rgba(124, 58, 237, 0.04); color: var(--ds-text-main); 
    }
    .cfg-nav-item.active, .v2-settings-nav-item.active { 
        background: rgba(124, 58, 237, 0.06); color: var(--ds-primary-light); 
        border-left-color: var(--ds-primary); font-weight: 600;
    }
    .cfg-nav-icon, .v2-settings-nav-icon { width: 16px; text-align: center; font-size: 0.85rem; opacity: 0.8; }
    .cfg-nav-item.active .cfg-nav-icon, .v2-settings-nav-item.active .v2-settings-nav-icon { opacity: 1; }
    .cfg-nav-divider { height: 1px; background: var(--ds-border-light); margin: 12px 0; }
    
    /* Badges */
    .cfg-nav-badge, .v2-badge { 
        padding: 2px 6px; border-radius: 8px; font-size: 0.65rem; font-weight: 700; margin-left: auto; display: inline-flex; align-items: center; justify-content: center; line-height: 1;
    }
    .cfg-badge-warn, .v2-badge-warning { background: rgba(245, 158, 11, 0.12); color: #f59e0b; }
    .cfg-badge-ok, .v2-badge-success { background: rgba(16, 185, 129, 0.12); color: #10b981; }
    
    /* Cards */
    .v2-settings-card { 
        background: var(--ds-bg-card); border: 1px solid var(--ds-border-light); 
        border-radius: 10px; overflow: hidden; margin-bottom: 20px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1), 0 1px 2px rgba(0, 0, 0, 0.06);
    }
    .v2-settings-header { 
        padding: 16px 20px; border-bottom: 1px solid var(--ds-border-light); 
        display: flex; align-items: center; gap: 12px; 
    }
    .v2-settings-header-icon { 
        width: 32px; height: 32px; border-radius: 8px; display: flex; 
        align-items: center; justify-content: center; font-size: 1rem; 
    }
    .v2-settings-title { font-size: 0.95rem; font-weight: 600; color: var(--ds-text-main); margin: 0 0 2px; }
    .v2-settings-desc { font-size: 0.75rem; color: var(--ds-text-muted); margin: 0; }
    .v2-settings-body { padding: 20px; }
    
    /* Form Elements */
    .v2-label { display: block; font-size: 0.75rem; font-weight: 600; color: var(--ds-text-secondary); margin-bottom: 6px; }
    
    .v2-input, select.v2-input, textarea.v2-input { 
        width: 100%; background: rgba(255,255,255,0.02); border: 1px solid var(--ds-border-medium); 
        border-radius: 6px; padding: 0 12px; color: var(--ds-text-main); font-size: 0.875rem; 
        outline: none; transition: border-color 0.15s, box-shadow 0.15s; 
    }
    .v2-input { height: 42px; }
    textarea.v2-input { padding: 10px 12px; min-height: 80px; resize: vertical; }
    select.v2-input { appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23a1a1aa'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 12px center; background-size: 14px; padding-right: 36px; }
    select.v2-input option { background: #11151e; color: #e2e8f0; }
    
    .v2-input:focus, select.v2-input:focus, textarea.v2-input:focus { 
        border-color: var(--ds-primary); box-shadow: 0 0 0 2px rgba(124, 58, 237, 0.15); 
    }
    .v2-input:disabled, select.v2-input:disabled, textarea.v2-input:disabled { 
        background: rgba(255,255,255,0.01); color: var(--ds-text-muted); cursor: not-allowed; border-color: rgba(255,255,255,0.05);
    }
    .v2-input[type="file"] { padding: 8px 12px; font-size: 0.8125rem; }
    .v2-input[type="file"]::file-selector-button {
        background: rgba(255,255,255,0.04); border: 1px solid var(--ds-border-medium); 
        color: var(--ds-text-main); padding: 4px 10px; border-radius: 4px; 
        margin-right: 10px; cursor: pointer; transition: background 0.15s; font-size: 0.8125rem;
    }
    .v2-input[type="file"]::file-selector-button:hover { background: rgba(255,255,255,0.08); }

    /* Checkbox / Toggle */
    .v2-checkbox-wrapper { display: flex; align-items: center; gap: 8px; cursor: pointer; }
    .v2-checkbox { 
        appearance: none; width: 16px; height: 16px; border: 1px solid var(--ds-border-medium); 
        border-radius: 4px; background: rgba(255,255,255,0.02); cursor: pointer; 
        position: relative; transition: all 0.15s; margin: 0;
    }
    .v2-checkbox:checked { background: var(--ds-primary); border-color: var(--ds-primary); }
    .v2-checkbox:checked::after {
        content: ''; position: absolute; left: 4px; top: 1px; width: 4px; height: 8px;
        border: solid white; border-width: 0 2px 2px 0; transform: rotate(45deg);
    }
    
    /* Buttons */
    .v2-btn-primary, .v2-btn-secondary { 
        height: 42px; padding: 0 16px; border-radius: 6px; font-size: 0.875rem; font-weight: 600; 
        display: inline-flex; align-items: center; justify-content: center; gap: 8px; 
        cursor: pointer; transition: all 0.15s; border: none; outline: none;
    }
    .v2-btn-primary { background: var(--ds-primary); color: #fff; }
    .v2-btn-primary:hover { background: var(--ds-primary-hover); }
    .v2-btn-secondary { background: rgba(255,255,255,0.04); color: var(--ds-text-main); border: 1px solid var(--ds-border-medium); }
    .v2-btn-secondary:hover { background: rgba(255,255,255,0.08); border-color: var(--ds-border-light); }
    .v2-btn-primary:disabled, .v2-btn-secondary:disabled { opacity: 0.5; cursor: not-allowed; }
    
    /* Validations & Alerts */
    .text-danger { color: var(--ds-error) !important; font-size: 0.75rem; margin-top: 4px; display: block; }
    .is-invalid { border-color: var(--ds-error) !important; }
    
    .alert-success { background: rgba(16, 185, 129, 0.08); border: 1px solid rgba(16, 185, 129, 0.2); color: #10b981; padding: 10px 14px; border-radius: 6px; margin-bottom: 16px; display: flex; align-items: center; gap: 10px; font-size: 0.875rem; }
    .alert-danger { background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.2); color: #ef4444; padding: 10px 14px; border-radius: 6px; margin-bottom: 16px; display: flex; align-items: center; gap: 10px; font-size: 0.875rem; }
    
    /* Grid Helpers */
    .v2-row { display: grid; gap: 20px; }
    .v2-col-2 { grid-template-columns: repeat(2, 1fr); }

    .v2-settings-panel {
        transition: opacity .16s ease, transform .16s ease;
    }

    .v2-settings-shell.is-loading .v2-settings-panel {
        opacity: .45;
        transform: translateY(2px);
        pointer-events: none;
    }

    .v2-settings-nav-item.is-loading {
        opacity: .75;
        pointer-events: none;
    }

    @media (max-width: 768px) { 
        .v2-col-2 { grid-template-columns: 1fr; } 
        .v2-settings-shell { flex-direction: column; gap: 14px; }
        .v2-settings-nav {
            width: 100%;
            display: flex;
            gap: 8px;
            overflow-x: auto;
            overflow-y: hidden;
            padding: 0 0 8px;
            margin-bottom: 2px;
            scrollbar-width: none;
        }
        .v2-settings-nav::-webkit-scrollbar { display: none; }
        .cfg-nav-section,
        .v2-settings-nav-section {
            display: none !important;
        }
        .cfg-nav-item,
        .v2-settings-nav-item {
            position: relative;
            flex: 0 0 96px;
            min-height: 54px;
            padding: 8px 7px;
            margin: 0;
            border: 1px solid transparent;
            border-left-width: 1px;
            border-radius: 8px;
            display: inline-flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            gap: 7px;
            text-align: center;
            white-space: normal;
            line-height: 1.18;
            font-size: .75rem;
        }
        .cfg-nav-item.active,
        .v2-settings-nav-item.active {
            border-color: rgba(124, 58, 237, .55);
            background: rgba(124, 58, 237, .14);
        }
        .cfg-nav-icon,
        .v2-settings-nav-icon {
            width: 24px;
            height: 24px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: .86rem;
        }
        .cfg-nav-item .cfg-nav-badge,
        .v2-settings-nav-item .v2-settings-nav-badge,
        .v2-settings-nav-item .v2-badge {
            position: absolute !important;
            top: 5px !important;
            right: 7px !important;
            min-width: 16px !important;
            height: 16px !important;
            padding: 0 5px !important;
            margin: 0 !important;
            border-radius: 999px !important;
            font-size: .58rem !important;
            line-height: 16px !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
        }
        .v2-settings-panel { width: 100%; }
        .v2-page-header[style] {
            margin-bottom: 18px !important;
        }
        .v2-page-header p[style] {
            margin-left: 0 !important;
            line-height: 1.45 !important;
        }
        .v2-settings-card {
            margin-bottom: 12px !important;
            border-radius: 10px !important;
        }
        .v2-settings-header {
            padding: 12px 14px !important;
            gap: 10px !important;
        }
        .v2-settings-header-icon {
            width: 30px !important;
            height: 30px !important;
            border-radius: 8px !important;
            font-size: .82rem !important;
            flex: 0 0 30px !important;
        }
        .v2-settings-title {
            font-size: .86rem !important;
            line-height: 1.25 !important;
            margin-bottom: 2px !important;
        }
        .v2-settings-desc {
            font-size: .7rem !important;
            line-height: 1.35 !important;
        }
        .v2-settings-body {
            padding: 14px !important;
        }
        .v2-settings-footer {
            display: grid !important;
            grid-template-columns: 1fr !important;
            gap: 10px !important;
            padding: 12px 14px 14px !important;
            border-top: 1px solid var(--ds-border-light) !important;
        }
        .v2-settings-footer .v2-btn-primary,
        .v2-settings-footer .v2-btn-secondary,
        .v2-settings-footer .v2-btn-tertiary,
        .v2-settings-footer button {
            width: 100% !important;
            min-height: 40px !important;
            margin-left: 0 !important;
            justify-content: center !important;
            border-radius: 8px !important;
        }
    }
</style>
<div class="v2-page-header" style="margin-bottom: 28px;">
    <h4 style="font-size: 1.25rem; font-weight: 700; margin: 0 0 4px; color: var(--ds-text-main);">
        <i class="@yield('hub_icon')" style="color: var(--ds-primary-light); margin-right: 10px; font-size: 1rem;"></i>
        @yield('hub_title')
    </h4>
    <p style="color: var(--ds-text-muted); font-size: 0.9rem; margin: 0; margin-left: 28px;">@yield('hub_desc')</p>
</div>

<div class="v2-settings-shell" data-hub-shell>
    {{-- ── SIDEBAR NAV ────────────────────────────────────────── --}}
    <nav class="v2-settings-nav @yield('hub_nav_class')" data-hub-nav>
        @yield('hub_nav')
    </nav>

    {{-- ── CONTENT PANEL ──────────────────────────────────────── --}}
    <div class="v2-settings-panel" data-hub-panel>
        @yield('hub_content')
    </div>
</div>

@push('scripts')
<script>
(() => {
    const shell = document.querySelector('[data-hub-shell]');
    if (!shell || shell.dataset.asyncReady === 'true') return;

    shell.dataset.asyncReady = 'true';
    let activeRequest = null;

    function enhanceMobileTables(root = document) {
        if (window.innerWidth > 768) return;

        root.querySelectorAll('table').forEach((table) => {
            if (table.classList.contains('no-mobile-cards')) return;
            table.classList.add('v2-mobile-card-table');

            const headers = Array.from(table.querySelectorAll('thead th')).map((th) => th.innerText.trim());
            table.querySelectorAll('tbody tr').forEach((tr) => {
                tr.querySelectorAll('td').forEach((td, index) => {
                    if (headers[index]) td.setAttribute('data-label', headers[index]);
                });
            });
        });
    }

    function executeScripts(root) {
        root.querySelectorAll('script').forEach((oldScript) => {
            const script = document.createElement('script');

            Array.from(oldScript.attributes).forEach((attr) => {
                script.setAttribute(attr.name, attr.value);
            });

            if (!oldScript.src) {
                script.textContent = oldScript.textContent;
            }

            oldScript.replaceWith(script);
        });
    }

    function syncAsyncStyles(doc) {
        document.querySelectorAll('style[data-hub-async-style]').forEach((style) => style.remove());

        doc.querySelectorAll('head style').forEach((style) => {
            const clone = style.cloneNode(true);
            clone.setAttribute('data-hub-async-style', 'true');
            document.head.appendChild(clone);
        });
    }

    function setLoading(link, loading) {
        shell.classList.toggle('is-loading', loading);
        shell.querySelectorAll('.v2-settings-nav-item.is-loading').forEach((item) => item.classList.remove('is-loading'));
        if (loading && link) link.classList.add('is-loading');
    }

    async function loadHubUrl(url, options = {}) {
        const currentUrl = new URL(window.location.href);
        const nextUrl = new URL(url, window.location.origin);
        if (currentUrl.pathname === nextUrl.pathname && currentUrl.search === nextUrl.search && !options.force) return;

        if (activeRequest) activeRequest.abort();
        activeRequest = new AbortController();

        const clickedLink = Array.from(shell.querySelectorAll('a.v2-settings-nav-item'))
            .find((link) => {
                const linkUrl = new URL(link.href, window.location.origin);
                return linkUrl.pathname === nextUrl.pathname && linkUrl.search === nextUrl.search;
            });
        setLoading(clickedLink, true);

        try {
            const response = await fetch(nextUrl.href, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html',
                },
                signal: activeRequest.signal,
            });

            if (!response.ok) throw new Error(`Hub navigation failed: ${response.status}`);

            const html = await response.text();
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const nextShell = doc.querySelector('[data-hub-shell]');
            const nextNav = doc.querySelector('[data-hub-nav]');
            const nextPanel = doc.querySelector('[data-hub-panel]');

            if (!nextShell || !nextNav || !nextPanel) {
                window.location.href = nextUrl.href;
                return;
            }

            const nav = shell.querySelector('[data-hub-nav]');
            const panel = shell.querySelector('[data-hub-panel]');

            syncAsyncStyles(doc);
            nav.innerHTML = nextNav.innerHTML;
            panel.innerHTML = nextPanel.innerHTML;

            document.title = doc.title || document.title;
            const nextMobileTitle = doc.querySelector('.v2-mobile-title');
            const mobileTitle = document.querySelector('.v2-mobile-title');
            if (nextMobileTitle && mobileTitle) mobileTitle.textContent = nextMobileTitle.textContent;

            executeScripts(panel);
            enhanceMobileTables(panel);

            if (!options.replace) {
                window.history.pushState({ hubUrl: nextUrl.href }, '', nextUrl.href);
            }

            panel.focus?.({ preventScroll: true });
        } catch (error) {
            if (error.name === 'AbortError') return;
            console.warn(error);
            window.location.href = nextUrl.href;
        } finally {
            setLoading(null, false);
            activeRequest = null;
        }
    }

    shell.addEventListener('click', (event) => {
        const link = event.target.closest('a.v2-settings-nav-item');
        if (!link) return;
        if (link.target === '_blank' || link.hasAttribute('download') || link.dataset.noHubAjax === 'true') return;
        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0) return;

        const nextUrl = new URL(link.href, window.location.origin);
        if (nextUrl.origin !== window.location.origin) return;

        event.preventDefault();
        loadHubUrl(nextUrl.href);
    });

    window.addEventListener('popstate', () => {
        loadHubUrl(window.location.href, { replace: true, force: true });
    });
})();
</script>
@endpush

@endsection
