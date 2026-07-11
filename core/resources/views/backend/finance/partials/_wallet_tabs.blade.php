<ul class="nav nav-pills mb-4" style="gap: 0.5rem;">
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.finance.balances') ? 'active' : '' }}" 
           href="{{ route('admin.finance.balances') }}"
           style="border-radius: 6px; padding: 0.5rem 1rem;">
            Wallets (Saldos)
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.finance.ledger') || request()->routeIs('ledger.*') ? 'active' : '' }}" 
           href="{{ route('admin.finance.ledger') ?? route('ledger.index') }}"
           style="border-radius: 6px; padding: 0.5rem 1rem;">
            Ledger Financeiro
        </a>
    </li>
</ul>
