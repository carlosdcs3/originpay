<ul class="nav nav-pills mb-4" style="gap: 0.5rem;">
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.gateway-fees.index') ? 'active' : '' }}" 
           href="{{ route('admin.gateway-fees.index') }}"
           style="border-radius: 6px; padding: 0.5rem 1rem;">
            Tarifas de Gateway
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.platform-fees.index') ? 'active' : '' }}" 
           href="{{ route('admin.platform-fees.index') }}"
           style="border-radius: 6px; padding: 0.5rem 1rem;">
            Taxas da Plataforma
        </a>
    </li>
</ul>
