<ul class="nav nav-pills mb-4" style="gap: 0.5rem;">
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.gateway.logs') ? 'active' : '' }}" 
           href="{{ route('admin.gateway.logs') }}"
           style="border-radius: 6px; padding: 0.5rem 1rem;">
            Logs de Gateway
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.webhooks.index') ? 'active' : '' }}" 
           href="{{ route('admin.webhooks.index') }}"
           style="border-radius: 6px; padding: 0.5rem 1rem;">
            Logs de Webhooks
        </a>
    </li>
</ul>
