<div id="v2-notification-dropdown" class="v2-notification-dropdown" style="display: none;">
    <div class="v2-notification-header">
        <div>
            <h3>Notificações</h3>
            <p>Tudo que acontece na sua conta.</p>
        </div>
        <button type="button" class="v2-btn-link" onclick="markAllNotificationsAsRead()" aria-label="Marcar todas as notificações como lidas" style="font-size: 0.75rem; color: var(--ds-primary); background: transparent; border: none; cursor: pointer; padding: 0;">
            Marcar todas como lidas
        </button>
        <button type="button" id="v2-enable-device-notifications" class="v2-btn-link" aria-label="Ativar notificações neste dispositivo" style="display:none; font-size: 0.75rem; color: var(--ds-success, #22c55e); background: transparent; border: none; cursor: pointer; padding: 0;">
            Ativar no dispositivo
        </button>
    </div>

    <div class="v2-notification-filters hide-scrollbar">
        <button type="button" class="v2-filter-btn active" data-module="all" onclick="filterNotifications('all')">Todas</button>
        <button type="button" class="v2-filter-btn" data-module="financeiro" onclick="filterNotifications('financeiro')">Financeiro</button>
        <button type="button" class="v2-filter-btn" data-module="connect" onclick="filterNotifications('connect')">Origin Connect</button>
        <button type="button" class="v2-filter-btn" data-module="sistema" onclick="filterNotifications('sistema')">Sistema</button>
        <button type="button" class="v2-filter-btn" data-module="seguranca" onclick="filterNotifications('seguranca')">Segurança</button>
    </div>

    <div class="v2-notification-body" id="global-notifications-list" style="max-height: 400px; overflow-y: auto;">
        <!-- Loading state -->
        <div class="v2-notification-loading" style="padding: 40px 24px; text-align: center;">
            <div style="width: 14px; height: 14px; background: var(--ds-primary, #7C3AED); border-radius: 50%; display: inline-block; animation: pulseLoading 1.4s infinite ease-in-out;"></div>
            <div style="font-size: 0.75rem; color: var(--ds-text-muted); margin-top: 12px; font-weight: 500;">Buscando notificações...</div>
        </div>

        <!-- Empty state -->
        <div class="v2-notification-empty" style="display: none; padding: 32px 24px; text-align: center;">
            <div style="width: 48px; height: 48px; border-radius: 50%; background: var(--ds-surface); color: var(--ds-text-muted); display: flex; align-items: center; justify-content: center; margin: 0 auto 12px; font-size: 1.2rem;">
                <i class="far fa-bell-slash"></i>
            </div>
            <h4 style="margin: 0; font-size: 0.85rem; font-weight: 600; color: var(--ds-text);">Nenhuma notificação.</h4>
            <p style="margin: 4px 0 0; font-size: 0.75rem; color: var(--ds-text-muted); line-height: 1.4;">Quando houver atividades na sua conta elas aparecerão aqui.</p>
        </div>

        <!-- Content populated by JS -->
        <div id="notifications-content-container"></div>
    </div>

    <div class="v2-notification-footer" style="padding: 12px 16px; border-top: 1px solid var(--ds-border); text-align: center;">
        <a href="{{ route('user.notifications.index') }}" style="font-size: 0.8rem; font-weight: 600; color: var(--ds-text); text-decoration: none; transition: color 0.2s;">
            Ver todas as notificações
        </a>
    </div>
</div>

<style>
    .v2-notification-dropdown {
        position: absolute;
        top: calc(100% + 16px);
        right: -10px;
        width: 420px;
        background: var(--ds-surface, #1A1A27);
        border: 1px solid var(--ds-border, rgba(255,255,255,0.1));
        border-radius: 12px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.6);
        z-index: 1000;
        display: flex;
        flex-direction: column;
    }
    
    .v2-notification-badge {
        position: absolute;
        top: -4px;
        right: -4px;
        background: var(--ds-danger, #EF4444);
        color: white;
        font-size: 0.65rem;
        font-weight: 700;
        padding: 2px 6px;
        border-radius: 10px;
        line-height: 1;
        border: 2px solid var(--ds-surface, #1A1A27);
    }

    .v2-notification-header {
        padding: 16px;
        border-bottom: 1px solid var(--ds-border, rgba(255,255,255,0.1));
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }

    .v2-notification-header h3 {
        margin: 0; font-size: 1rem; font-weight: 700; color: var(--ds-text, #fff);
    }
    .v2-notification-header p {
        margin: 2px 0 0; font-size: 0.75rem; color: var(--ds-text-muted, #9ca3af);
    }

    .v2-notification-filters {
        display: flex;
        gap: 8px;
        padding: 12px 16px;
        border-bottom: 1px solid var(--ds-border, rgba(255,255,255,0.1));
        overflow-x: auto;
    }
    
    .v2-notification-filters.hide-scrollbar::-webkit-scrollbar {
        display: none;
    }
    .v2-notification-filters.hide-scrollbar {
        -ms-overflow-style: none;
    }

    .v2-filter-btn {
        background: transparent;
        border: 1px solid var(--ds-border, rgba(255,255,255,0.1));
        color: var(--ds-text-muted, #9ca3af);
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 0.75rem;
        font-weight: 600;
        cursor: pointer;
        white-space: nowrap;
        transition: 0.2s ease;
    }

    .v2-filter-btn:hover {
        border-color: rgba(255,255,255,0.2);
        color: var(--ds-text);
    }

    .v2-filter-btn.active {
        background: rgba(255,255,255,0.05);
        border-color: rgba(255,255,255,0.15);
        color: var(--ds-text);
    }

    @keyframes pulseLoading {
        0% { transform: scale(0.9); opacity: 0.6; }
        50% { transform: scale(1.1); opacity: 1; }
        100% { transform: scale(0.9); opacity: 0.6; }
    }

    .v2-notification-group-title {
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--ds-text-muted);
        padding: 12px 16px 6px;
    }

    .v2-notif-item {
        display: flex;
        gap: 12px;
        padding: 12px 16px;
        border-bottom: 1px solid var(--ds-border);
        text-decoration: none;
        transition: background 0.2s;
        cursor: pointer;
    }

    .v2-notif-item:last-child {
        border-bottom: none;
    }

    .v2-notif-item:hover {
        background: rgba(255,255,255,0.02);
    }
    
    .v2-notif-item.unread {
        background: rgba(var(--ds-primary-rgb, 124,58,237), 0.05);
    }

    .v2-notif-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background: var(--ds-surface);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 0.85rem;
    }

    .v2-notif-content {
        flex: 1;
        min-width: 0;
    }

    .v2-notif-title {
        font-size: 0.82rem;
        font-weight: 600;
        color: var(--ds-text);
        margin: 0 0 2px 0;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .v2-notif-module {
        font-size: 0.6rem;
        font-weight: 700;
        padding: 1px 6px;
        border-radius: 4px;
        background: var(--ds-surface);
        color: var(--ds-text-muted);
        text-transform: uppercase;
    }

    .v2-notif-desc {
        font-size: 0.75rem;
        color: var(--ds-text-muted);
        margin: 0 0 4px 0;
        line-height: 1.4;
    }

    .v2-notif-time {
        font-size: 0.65rem;
        color: rgba(255,255,255,0.4);
    }

    @media (max-width: 768px) {
        .v2-notification-dropdown {
            position: fixed;
            top: 60px;
            left: 12px;
            right: 12px;
            width: auto;
            max-height: calc(100vh - 80px);
        }
    }
</style>

<script>
    let globalNotifDropdownOpen = false;
    let currentNotifModule = 'all';

    function toggleNotificationDropdown() {
        const dropdown = document.getElementById('v2-notification-dropdown');
        globalNotifDropdownOpen = !globalNotifDropdownOpen;
        dropdown.style.display = globalNotifDropdownOpen ? 'flex' : 'none';
        
        if (globalNotifDropdownOpen) {
            fetchNotifications(currentNotifModule);
        }
    }

    function updateGlobalNotificationBadge(count) {
        const badge = document.getElementById('global-notif-badge');
        if (!badge) return;

        const unreadCount = Number(count || 0);
        if (unreadCount > 0) {
            badge.textContent = unreadCount > 99 ? '99+' : unreadCount;
            badge.style.display = 'inline-block';
        } else {
            badge.style.display = 'none';
        }
    }

    function incrementGlobalNotificationBadge() {
        const badge = document.getElementById('global-notif-badge');
        const current = badge ? parseInt(badge.textContent, 10) || 0 : 0;
        updateGlobalNotificationBadge(current + 1);
    }

    // Close on click outside
    document.addEventListener('click', function(event) {
        const wrapper = document.querySelector('.v2-notification-wrapper');
        if (globalNotifDropdownOpen && wrapper && !wrapper.contains(event.target)) {
            globalNotifDropdownOpen = false;
            document.getElementById('v2-notification-dropdown').style.display = 'none';
        }
    });

    function filterNotifications(module) {
        currentNotifModule = module;
        
        // Update active button
        document.querySelectorAll('.v2-filter-btn').forEach(btn => {
            btn.classList.remove('active');
            if (btn.dataset.module === module) btn.classList.add('active');
        });

        fetchNotifications(module);
    }

    function fetchNotifications(module) {
        const container = document.getElementById('notifications-content-container');
        const loading = document.querySelector('.v2-notification-loading');
        const empty = document.querySelector('.v2-notification-empty');
        
        loading.style.display = 'block';
        container.innerHTML = '';
        empty.style.display = 'none';

        fetch(`{{ route('user.notifications.index') }}?module=${module}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            loading.style.display = 'none';
            if (data.status === 'success') {
                renderNotifications(data.data.grouped);
                
                updateGlobalNotificationBadge(data.unread_count);
            }
        })
        .catch(err => {
            loading.style.display = 'none';
            console.error('Error fetching notifications:', err);
        });
    }

    function renderNotifications(grouped) {
        const container = document.getElementById('notifications-content-container');
        const empty = document.querySelector('.v2-notification-empty');
        
        let hasAny = false;
        let html = '';

        const groupLabels = {
            'today': 'Hoje',
            'yesterday': 'Ontem',
            'last_7_days': 'Últimos 7 dias',
            'older': 'Mais antigas'
        };

        for (const [key, items] of Object.entries(grouped)) {
            if (items.length > 0) {
                hasAny = true;
                html += `<div class="v2-notification-group-title">${groupLabels[key]}</div>`;
                
                items.forEach(item => {
                    const data = item.data;
                    const unreadClass = !item.read_at ? 'unread' : '';
                    
                    // Format module name beautifully
                    let moduleName = data.module;
                    if (moduleName.toLowerCase() === 'financeiro') moduleName = 'Financeiro';
                    else if (moduleName.toLowerCase() === 'connect') moduleName = 'Origin Connect';
                    else if (moduleName.toLowerCase() === 'seguranca') moduleName = 'Segurança';
                    else if (moduleName.toLowerCase() === 'sistema') moduleName = 'Sistema';

                    // Color based on severity
                    let iconColor = 'var(--ds-text)';
                    let iconBg = 'var(--ds-surface)';
                    if (data.severity === 'success') { iconColor = '#22C55E'; iconBg = 'rgba(34,197,94,0.1)'; }
                    if (data.severity === 'error') { iconColor = '#EF4444'; iconBg = 'rgba(239,68,68,0.1)'; }
                    if (data.severity === 'warning') { iconColor = '#F59E0B'; iconBg = 'rgba(245,158,11,0.1)'; }
                    
                    const actionAttr = data.action_url ? `href="${data.action_url}"` : `onclick="markNotificationAsRead('${item.id}')"`;
                    const tag = data.action_url ? 'a' : 'div';

                    html += `
                        <${tag} ${actionAttr} class="v2-notif-item ${unreadClass}" id="notif-${item.id}">
                            <div class="v2-notif-icon" style="color: ${iconColor}; background: ${iconBg};">
                                <i class="${data.icon || 'fas fa-bell'}"></i>
                            </div>
                            <div class="v2-notif-content">
                                <div class="v2-notif-title">
                                    <span class="v2-notif-module">${moduleName}</span>
                                    ${data.title}
                                </div>
                                <p class="v2-notif-desc">${data.message}</p>
                                <div class="v2-notif-time">${item.diff_for_humans}</div>
                            </div>
                        </${tag}>
                    `;
                });
            }
        }

        if (hasAny) {
            container.innerHTML = html;
        } else {
            empty.style.display = 'block';
        }
    }

    function markNotificationAsRead(id) {
        fetch(`{{ url('user/notifications') }}/${id}/read`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        }).then(res => res.json()).then(data => {
            if (data.status === 'success') {
                document.getElementById(`notif-${id}`)?.classList.remove('unread');
                fetchNotifications(currentNotifModule); // Refresh badge
            }
        });
    }

    function markAllNotificationsAsRead() {
        fetch(`{{ route('user.notifications.read-all') }}`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        }).then(res => res.json()).then(data => {
            if (data.status === 'success') {
                document.querySelectorAll('.v2-notif-item.unread').forEach(el => el.classList.remove('unread'));
                updateGlobalNotificationBadge(0);
            }
        });
    }

    window.originPayNotifications = window.originPayNotifications || {};
    window.originPayNotifications.refresh = function () {
        if (typeof fetchNotifications === 'function') {
            fetchNotifications(currentNotifModule || 'all');
        }
    };
    window.originPayNotifications.incrementBadge = incrementGlobalNotificationBadge;
    window.originPayNotifications.updateBadge = updateGlobalNotificationBadge;
</script>





