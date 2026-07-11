@extends('backend.layouts.app')
@section('title', __('Financial Compliance & Risk Dashboard'))

@section('content')
<div class="row g-4 mb-4">
    <!-- Exposure Metrics -->
    <div class="col-12">
        <h5 class="mb-3">Daily Exposure Metrics</h5>
        <div class="row row-cols-1 row-cols-md-4 g-3">
            <div class="col">
                <div class="card bg-primary text-white h-100">
                    <div class="card-body">
                        <h6>Requested Today</h6>
                        <h3>BRL {{ number_format($exposureMetrics['requested_today'], 2) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card bg-success text-white h-100">
                    <div class="card-body">
                        <h6>Approved Today</h6>
                        <h3>BRL {{ number_format($exposureMetrics['approved_today'], 2) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card bg-info text-white h-100">
                    <div class="card-body">
                        <h6>Paid Today</h6>
                        <h3>BRL {{ number_format($exposureMetrics['paid_today'], 2) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card bg-warning h-100">
                    <div class="card-body">
                        <h6>Waiting 2nd Approval</h6>
                        <h3>BRL {{ number_format($exposureMetrics['waiting_second_approval_value'], 2) }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header bg-danger text-white">Critical Anomalies</div>
            <div class="card-body">
                <ul class="list-group">
                    @forelse($criticalAnomalies as $anomaly)
                        <li class="list-group-item">
                            <strong>{{ $anomaly->type }}</strong> ({{ $anomaly->severity }})<br>
                            <small>{{ $anomaly->description }}</small>
                        </li>
                    @empty
                        <li class="list-group-item text-muted">No critical anomalies.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header bg-warning">Pending Dual Approvals</div>
            <div class="card-body">
                <ul class="list-group">
                    @forelse($pendingDualApprovals as $request)
                        <li class="list-group-item">
                            <strong>ID: {{ $request->id }}</strong> - BRL {{ number_format($request->amount, 2) }}<br>
                            <small>User: {{ $request->user->email }}</small>
                        </li>
                    @empty
                        <li class="list-group-item text-muted">No pending 2nd approvals.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
