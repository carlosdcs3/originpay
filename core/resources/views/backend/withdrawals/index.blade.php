@extends('backend.layouts.app')
@section('title', __('Withdrawal Queue'))

@section('content')
<div class="row g-3 mb-4">
    @foreach(['pending', 'manual_review', 'dual_approval', 'approved', 'processing', 'completed', 'failed'] as $stat)
    <div class="col">
        <div class="card h-100 text-center py-2">
            <h6 class="text-uppercase text-muted small">{{ str_replace('_', ' ', $stat) }}</h6>
            <h4 class="mb-0">{{ $stats[$stat] }}</h4>
        </div>
    </div>
    @endforeach
    <div class="col">
        <div class="card h-100 text-center py-2 bg-warning text-dark">
            <h6 class="text-uppercase small">Total Reserved</h6>
            <h5 class="mb-0">BRL {{ number_format($stats['total_reserved'], 2) }}</h5>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>PIX Key (Masked)</th>
                    <th>Requested At</th>
                </tr>
            </thead>
            <tbody>
                @foreach($withdrawals as $w)
                <tr>
                    <td>#{{ $w->id }}</td>
                    <td>{{ $w->user->email }}</td>
                    <td>BRL {{ number_format($w->amount, 2) }}</td>
                    <td><span class="badge bg-secondary">{{ $w->status }}</span></td>
                    <td>{{ Str::mask($w->pix_key_snapshot, '*', 3, -3) }}</td>
                    <td>{{ $w->created_at->format('Y-m-d H:i') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        {{ $withdrawals->links() }}
    </div>
</div>
@endsection
