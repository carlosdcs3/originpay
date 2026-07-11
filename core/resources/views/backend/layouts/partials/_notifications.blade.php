@php
    $notificationCount = $notifications->count();
@endphp

{{-- Bell icon --}}
<a class="nav-link position-relative p-2" href="javascript:void(0)" id="ds-notif-toggle" role="button">
    <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" style="color:var(--ds-text-secondary);display:block;">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
    </svg>
    @if($notificationCount > 0)
        <span class="position-absolute" style="top:4px;right:4px;width:8px;height:8px;background:var(--ds-danger);border-radius:50%;border:2px solid #fff;"></span>
    @endif
</a>

{{-- Notification Panel --}}
<div class="dropdown-menu dropdown-menu-end p-0 ds-notification-dropdown" id="ds-notif-panel" style="border-radius:var(--ds-radius-lg)!important;border:1px solid var(--ds-border)!important;box-shadow:var(--ds-shadow-xl)!important; width:320px; max-width:calc(100vw - 20px); right:0 !important; left:auto !important; top:100% !important; transform:none !important; margin-top:0.5rem !important; display:none;">

    {{-- Header --}}
    <div class="ds-notification-header">
        <div>
            <h6 style="font-size:var(--ds-text-sm);font-weight:700;color:var(--ds-text);margin:0;">Notificações</h6>
        </div>
        @if($notificationCount > 0)
            <span class="badge ds-badge-accent">{{ $notificationCount }} novas</span>
        @endif
    </div>

    {{-- Category Tabs --}}
    <div style="display:flex;gap:0;border-bottom:1px solid var(--ds-border);padding:0 1.25rem;overflow-x:auto;scrollbar-width:none;">
        <button class="ds-tab-btn active" data-target="all" style="padding:.5rem 0;margin-right:1.25rem;font-size:var(--ds-text-xs);">Todas</button>
        <button class="ds-tab-btn" data-target="incidentes" style="padding:.5rem 0;margin-right:1.25rem;font-size:var(--ds-text-xs);">Incidentes</button>
        <button class="ds-tab-btn" data-target="sistema" style="padding:.5rem 0;margin-right:1.25rem;font-size:var(--ds-text-xs);">Sistema</button>
    </div>

    {{-- Scrollable list --}}
    <div class="ds-notification-scroll">
        @if($notifications->isEmpty())
            <div class="ds-empty" style="padding:2.5rem 1rem;">
                <div class="ds-empty-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                </div>
                <div class="ds-empty-title">Tudo em dia</div>
                <div class="ds-empty-desc">Nenhuma notificação pendente no momento.</div>
            </div>
        @else
            @foreach($notifications->take(5) as $notification)
                @php
                    $data   = $notification->data;
                    $title  = $data['title'] ?? 'Alerta';
                    $isHigh = str_contains(strtolower($title), 'offline') || str_contains(strtolower($title), 'chargeback');
                    $cat    = $isHigh ? 'incidentes' : 'sistema';
                @endphp
                <a href="{{ $data['action_link'] ?? '#' }}"
                   class="ds-notification-item {{ !$notification->read_at ? 'unread' : '' }} read-notification ds-notif-item-{{ $cat }}"
                   data-id="{{ $notification->id }}" style="text-decoration:none;">
                    <div class="ds-notification-icon" style="background:{{ $isHigh ? 'var(--ds-danger-bg)' : 'var(--ds-accent-muted)' }};color:{{ $isHigh ? 'var(--ds-danger)' : 'var(--ds-accent)' }};">
                        @if($isHigh)
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        @else
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        @endif
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2px;">
                            <span style="font-size:var(--ds-text-sm);font-weight:600;color:{{ $isHigh ? 'var(--ds-danger)' : 'var(--ds-text)' }};white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:200px;">{{ $title }}</span>
                            <span style="font-size:.65rem;color:var(--ds-text-muted);flex-shrink:0;margin-left:.5rem;">{{ $notification->created_at->diffForHumans(null,true,true) }}</span>
                        </div>
                        <div style="font-size:var(--ds-text-xs);color:var(--ds-text-muted);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $data['message'] ?? '' }}</div>
                    </div>
                    @if(!$notification->read_at)
                        <div style="width:6px;height:6px;background:var(--ds-accent);border-radius:50%;flex-shrink:0;margin-top:4px;"></div>
                    @endif
                </a>
            @endforeach
        @endif
    </div>

    {{-- Footer --}}
    <div class="ds-notif-footer">
        <a href="{{ route('admin.dashboard') }}" style="font-size:var(--ds-text-xs);color:var(--ds-accent);text-decoration:none;font-weight:600;">Ver todas as notificações →</a>
    </div>

</div>

<script>
(function() {
    var toggleBtn = document.getElementById('ds-notif-toggle');
    var panel = document.getElementById('ds-notif-panel');
    var tabBtns = document.querySelectorAll('#ds-notif-panel .ds-tab-btn');
    var items = document.querySelectorAll('#ds-notif-panel .ds-notification-item');
    var emptyState = document.querySelector('#ds-notif-panel .ds-empty');

    if(toggleBtn && panel) {
        // Remove old listeners to avoid duplicates if re-injected
        var newToggle = toggleBtn.cloneNode(true);
        if (toggleBtn.parentNode) {
            toggleBtn.parentNode.replaceChild(newToggle, toggleBtn);
        }
        toggleBtn = newToggle;

        toggleBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            if (panel.style.display === 'none' || panel.style.display === '') {
                panel.style.display = 'block';
            } else {
                panel.style.display = 'none';
            }
        });

        if (!window.dsNotifDocListenerAttached) {
            document.addEventListener('click', function(e) {
                var p = document.getElementById('ds-notif-panel');
                var t = document.getElementById('ds-notif-toggle');
                if (p && t) {
                    if (!p.contains(e.target) && !t.contains(e.target)) {
                        p.style.display = 'none';
                    }
                }
            });
            window.dsNotifDocListenerAttached = true;
        }
        
        panel.addEventListener('click', function(e) {
            if (e.target.tagName !== 'A' && !e.target.closest('a')) {
                e.stopPropagation();
            }
        });
    }

    tabBtns.forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            
            tabBtns.forEach(function(b) { 
                b.classList.remove('active'); 
            });
            this.classList.add('active');
            
            var target = this.getAttribute('data-target');
            
            if (items.length > 0) {
                var visibleCount = 0;
                items.forEach(function(item) {
                    if (target === 'all') {
                        item.style.display = 'flex';
                        visibleCount++;
                    } else if (item.classList.contains('ds-notif-item-' + target)) {
                        item.style.display = 'flex';
                        visibleCount++;
                    } else {
                        item.style.display = 'none';
                    }
                });
            }
        });
    });
})();
</script>
