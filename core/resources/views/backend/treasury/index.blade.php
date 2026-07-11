@extends('backend.layouts.app')
@section('title', __('Treasury Dashboard'))

@section('content')
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark text-white h-100">
            <div class="card-body">
                <h6>Efí Real Balance</h6>
                <h3>BRL {{ number_format($realBalance, 2) }}</h3>
                <small class="text-muted">Last synced via API</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-secondary text-white h-100">
            <div class="card-body">
                <h6>Ledger Balance (Obligations)</h6>
                <h3>BRL {{ number_format($ledgerBalance, 2) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-{{ $difference > 0 ? 'danger' : 'success' }} text-white h-100">
            <div class="card-body">
                <h6>Difference (Ledger - Bank)</h6>
                <h3>BRL {{ number_format($difference, 2) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100 border-{{ $lcrStatus == 'GREEN' ? 'success' : ($lcrStatus == 'YELLOW' ? 'warning' : 'danger') }}">
            <div class="card-body">
                <h6>Liquidity Coverage Ratio (LCR)</h6>
                <h3 class="text-{{ $lcrStatus == 'GREEN' ? 'success' : ($lcrStatus == 'YELLOW' ? 'warning' : 'danger') }}">
                    {{ number_format($lcr, 2) }}%
                </h3>
                <span class="badge bg-{{ $lcrStatus == 'GREEN' ? 'success' : ($lcrStatus == 'YELLOW' ? 'warning' : 'danger') }}">{{ $lcrStatus }}</span>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Liability Breakdown</div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Reserved Money (Active withdrawals)
                        <span class="badge bg-warning rounded-pill">BRL {{ number_format($reservedMoney, 2) }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Pending Withdrawals Volume
                        <span class="badge bg-info rounded-pill">BRL {{ number_format($pendingWithdrawals, 2) }}</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">System Wallets</div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Platform Revenue (SYSTEM_REVENUE)
                        <span class="badge bg-success rounded-pill">BRL {{ number_format($systemRevenue, 2) }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Gateway Costs Holding
                        <span class="badge bg-secondary rounded-pill">BRL {{ number_format($gatewayCosts, 2) }}</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
