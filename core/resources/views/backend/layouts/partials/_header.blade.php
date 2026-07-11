<header class="header header-sticky p-0 d-flex flex-column align-items-stretch" style="border-bottom: 1px solid var(--ds-border); background: var(--ds-surface);">
    {{-- Main Header Bar --}}
    <div class="ds-header-bar d-flex align-items-center w-100 px-3" style="min-height: 64px;">

        {{-- Sidebar Toggle --}}
        <button class="btn btn-ghost btn-icon me-2 d-lg-none" type="button"
                onclick="coreui.Sidebar.getInstance(document.querySelector('#sidebar')).toggle()">
            <x-icon name="cil-menu" class="icon"/>
        </button>

        {{-- Search Trigger / Command Palette --}}
        <button type="button" class="ds-search-trigger d-none d-md-flex btn btn-secondary align-items-center me-auto"
                data-coreui-toggle="modal" data-coreui-target="#commandPaletteModal" style="color: var(--ds-text-muted); gap: 10px; border-radius: 8px;">
            <x-icon name="search" class="icon icon-sm"/>
            <span class="ds-search-text">Buscar usuário, cobrança, TXID...</span>
            <kbd class="ms-3" style="background: var(--ds-bg); padding: 2px 6px; border-radius: 4px; font-size: 0.7rem; border: 1px solid var(--ds-border);">Ctrl K</kbd>
        </button>

        {{-- Right side nav --}}
        <ul class="header-nav ms-auto d-flex align-items-center gap-2">

            {{-- System Status Badge --}}
            <li class="nav-item d-none d-md-flex align-items-center me-3">
                <div class="d-flex align-items-center gap-2" style="font-size: 0.75rem; color: var(--ds-text-muted); background: var(--ds-bg); padding: 4px 10px; border-radius: 20px; border: 1px solid var(--ds-border);">
                    <span class="ds-status-dot online" style="background-color: #10b981; width: 6px; height: 6px; border-radius: 50%; display: inline-block;"></span>
                    <span>Prod • v{{ config('app.version', '1.05') }}</span>
                </div>
            </li>

            {{-- Support Chat Notifications --}}
            <li class="nav-item dropdown">
                <a class="nav-link position-relative btn btn-icon" href="#" id="ds-support-notif-toggle" role="button" data-coreui-toggle="dropdown" aria-expanded="false" style="color: var(--ds-text-secondary);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                    <span class="position-absolute" id="ds-support-badge" style="top:2px;right:2px;width:12px;height:12px;background:var(--ds-warning);color:#fff;border-radius:50%;font-size:9px;font-weight:bold;display:none;align-items:center;justify-content:center;border:2px solid var(--ds-surface);"></span>
                </a>
                <div class="dropdown-menu dropdown-menu-end p-0" id="ds-support-panel" style="width:320px;">
                    <div class="ds-notification-header d-flex justify-content-between p-3 border-bottom">
                        <h6 class="mb-0" style="font-size: 0.85rem; font-weight: 600;">Suporte</h6>
                        <span class="badge bg-warning text-dark" id="ds-support-count-badge" style="display:none;"></span>
                    </div>
                    <div class="ds-notification-scroll" id="ds-support-list" style="max-height:300px;overflow-y:auto;">
                        <div class="p-4 text-center text-muted" style="font-size: 0.85rem;">Carregando...</div>
                    </div>
                    <div class="p-2 text-center border-top bg-light">
                        <a href="{{ route('admin.support-chat.index') }}" style="font-size: 0.8rem; font-weight: 600; text-decoration: none;">Ver todas as conversas &rarr;</a>
                    </div>
                </div>
            </li>

            {{-- System Notifications --}}
            <li class="nav-item dropdown" id="append-new-admin-notification">
                @include('backend.layouts.partials._notifications', ['notifications' => auth()->user()->getRecentNotifications()])
            </li>

            {{-- Avatar / User Menu --}}
            <li class="nav-item dropdown ms-2">
                <a class="nav-link p-0 d-flex align-items-center gap-2" data-coreui-toggle="dropdown" href="#" role="button" aria-expanded="false">
                    <div class="avatar avatar-md" style="border: 2px solid var(--ds-border); border-radius: 50%; overflow: hidden; width: 36px; height: 36px;">
                        <img class="avatar-img" src="{{ asset(auth()->user()->avatar_alt) }}" alt="{{ auth()->user()->name }}" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                </a>
                <div class="dropdown-menu dropdown-menu-end" style="min-width:180px;">
                    <div class="dropdown-header">Conta de Admin</div>
                    <a class="dropdown-item" href="{{ route('admin.profile.view') }}">Meu Perfil</a>
                    <a class="dropdown-item" href="{{ route('admin.settings.platform.index') }}">Configurações</a>
                    <div class="dropdown-divider"></div>
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item text-danger">Sair da Plataforma</button>
                    </form>
                </div>
            </li>

        </ul>
    </div>
</header>

@push('scripts')
<script>
    (function(){
        const fetchSupportNotifications = async () => {
            try {
                const res = await fetch("{{ route('admin.support-chat.notifications') }}");
                if(res.status === 401 || res.status === 419) {
                    clearInterval(window.dsSupportInterval);
                    return;
                }
                const contentType = res.headers.get('content-type') || '';
                if(!res.ok || !contentType.includes('application/json')) return;

                const data = await res.json();
                if(data.success) {
                    const badge = document.getElementById('ds-support-badge');
                    const countBadge = document.getElementById('ds-support-count-badge');
                    const list = document.getElementById('ds-support-list');
                    
                    if(data.unread_count > 0) {
                        badge.style.display = 'flex';
                        badge.textContent = data.unread_count > 9 ? '9+' : data.unread_count;
                        countBadge.style.display = 'inline-block';
                        countBadge.textContent = data.unread_count + ' nova(s)';
                    } else {
                        badge.style.display = 'none';
                        countBadge.style.display = 'none';
                    }

                    if(data.latest && data.latest.length > 0) {
                        let html = '';
                        data.latest.forEach(conv => {
                            html += `
                                <a href="${conv.url}" class="dropdown-item p-3 border-bottom d-flex gap-3 align-items-start text-wrap">
                                    <div class="avatar bg-primary text-white d-flex align-items-center justify-content-center" style="width:32px; height:32px; border-radius:50%; flex-shrink:0;">
                                        ${conv.user_name.charAt(0).toUpperCase()}
                                    </div>
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <strong style="font-size: 0.85rem;">${conv.user_name}</strong>
                                            <small class="text-muted" style="font-size: 0.7rem;">${conv.created_at}</small>
                                        </div>
                                        <div class="text-muted mb-1" style="font-size: 0.75rem;">${conv.user_email}</div>
                                        <div class="text-truncate" style="font-size: 0.8rem;">${conv.message || 'Nova conversa iniciada...'}</div>
                                    </div>
                                </a>
                            `;
                        });
                        list.innerHTML = html;
                    } else {
                        list.innerHTML = `
                            <div class="p-4 text-center text-muted" style="font-size: 0.85rem;">
                                Nenhuma conversa pendente
                            </div>
                        `;
                    }
                }
            } catch(e) {}
        };
        fetchSupportNotifications();
        window.dsSupportInterval = setInterval(fetchSupportNotifications, 30000);
    })();
</script>
@endpush

@include('backend.layouts.partials._command_palette')
