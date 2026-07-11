<div class="single-card-box d-lg-block d-none">
    <ul class="left-menu-box">
        <li>
            <a href="{{ route('user.dashboard') }}" class="{{ isActive('user.dashboard') }}">
                <x-icon name="dashboard-2" class="icon"/>
                {{ __('Dashboard') }}
                <i class="fa fa-angle-right arrow"></i>
            </a>
        </li>
        <li>
            <a href="{{ route('user.charge.index') }}" class="{{ isActive('user.charge.*') }}">
                <x-icon name="transaction-4" class="icon"/>
                {{ __('Cobranças') }}
                <i class="fa fa-angle-right arrow"></i>
            </a>
        </li>
        <li>
            <a href="{{ route('user.withdraw.create') }}" class="{{ isActive('user.withdraw.create') }}">
                <x-icon name="withdraw" class="icon"/>
                {{ __('Withdrawals') }}
                <i class="fa fa-angle-right arrow"></i>
            </a>
        </li>
        <li>
            <a href="{{ route('user.transaction.index') }}" class="{{ isActive('user.transaction.index') }}">
                <x-icon name="transaction-4" class="icon"/>
                {{ __('Transactions') }}
                <i class="fa fa-angle-right arrow"></i>
            </a>
        </li>
        <li class="mt-4 mb-2 px-3 text-muted text-uppercase" style="font-size: 0.75rem; font-weight: 700;">
            Transferências
        </li>
        <li>
            <a href="{{ route('user.deposit.create') }}" class="{{ isActive('user.deposit.create') }}">
                <x-icon name="add-money" class="icon"/>
                {{ __('Deposit Money') }}
                <i class="fa fa-angle-right arrow"></i>
            </a>
        </li>
        <li>
            <a href="{{ route('user.send-money.create') }}" class="{{ isActive('user.send-money.create') }}">
                <x-icon name="send-money" class="icon"/>
                {{ __('Send Money') }}
                <i class="fa fa-angle-right arrow"></i>
            </a>
        </li>
        @can('merchant')
            <li class="mt-4 mb-2 px-3 text-muted text-uppercase" style="font-size: 0.75rem; font-weight: 700;">
                Integração
            </li>
            <li>
                <a href="{{ route('user.merchant.index') }}" class="{{ isActive('user.merchant.index') }}">
                    <x-icon name="merchant" class="icon"/>
                    {{ __('Merchant') }}
                    <i class="fa fa-angle-right arrow"></i>
                </a>
            </li>
        @endcan
        <li class="mt-4 mb-2 px-3 text-muted text-uppercase" style="font-size: 0.75rem; font-weight: 700;">
            Conta
        </li>
        <li>
            <a href="{{ route('user.referral.index') }}" class="{{ isActive('user.referral.index') }}">
                <x-icon name="referral" class="icon"/>
                {{ __('Referrals') }}
                <i class="fa fa-angle-right arrow"></i>
            </a>
        </li>
        <li>
            <a href="{{ route('user.support-ticket.index') }}" class="{{ isActive('user.support-ticket.index') }}">
                <x-icon name="ticket" class="icon"/>
                {{ __('Support') }}
                <i class="fa fa-angle-right arrow"></i>
            </a>
        </li>
    </ul>
</div>