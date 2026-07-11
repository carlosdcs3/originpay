@extends('backend.layouts.app')

@section('title', 'Edit Gateway Fee: ' . $config->provider)

@section('content')
<div class="row">
    <div class="col-md-7">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Edit Fees - {{ $config->provider }}</h6>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.gateway-fees.update', $config->id) }}" method="POST">
                    @csrf
                    
                    <h5 class="text-info mt-3"><i class="fas fa-arrow-down"></i> Transaction Fee (IN)</h5>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label>Type</label>
                            <select name="transaction_fee_type" class="form-control">
                                <option value="fixed" {{ $config->transaction_fee_type == 'fixed' ? 'selected' : '' }}>Fixed</option>
                                <option value="percent" {{ $config->transaction_fee_type == 'percent' ? 'selected' : '' }}>Percent</option>
                                <option value="fixed_plus_percent" {{ $config->transaction_fee_type == 'fixed_plus_percent' ? 'selected' : '' }}>Fixed + Percent</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Fixed (R$)</label>
                            <input type="number" step="0.01" name="transaction_fixed_fee" class="form-control" value="{{ $config->transaction_fixed_fee }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Percent (%)</label>
                            <input type="number" step="0.01" name="transaction_percent_fee" class="form-control" value="{{ $config->transaction_percent_fee }}">
                        </div>
                    </div>

                    <h5 class="text-warning mt-3"><i class="fas fa-arrow-up"></i> Withdraw Fee (OUT)</h5>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label>Type</label>
                            <select name="withdraw_fee_type" class="form-control">
                                <option value="fixed" {{ $config->withdraw_fee_type == 'fixed' ? 'selected' : '' }}>Fixed</option>
                                <option value="percent" {{ $config->withdraw_fee_type == 'percent' ? 'selected' : '' }}>Percent</option>
                                <option value="fixed_plus_percent" {{ $config->withdraw_fee_type == 'fixed_plus_percent' ? 'selected' : '' }}>Fixed + Percent</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Fixed (R$)</label>
                            <input type="number" step="0.01" name="withdraw_fixed_fee" class="form-control" value="{{ $config->withdraw_fixed_fee }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Percent (%)</label>
                            <input type="number" step="0.01" name="withdraw_percent_fee" class="form-control" value="{{ $config->withdraw_percent_fee }}">
                        </div>
                    </div>

                    <h5 class="text-danger mt-3"><i class="fas fa-building"></i> Provider Fee (Gateway Cost)</h5>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label>Mode</label>
                            <select name="provider_fee_mode" class="form-control">
                                <option value="estimated" {{ $config->provider_fee_mode == 'estimated' ? 'selected' : '' }}>Estimated</option>
                                <option value="real" {{ $config->provider_fee_mode == 'real' ? 'selected' : '' }}>Real (Wait for Webhook)</option>
                                <option value="manual" {{ $config->provider_fee_mode == 'manual' ? 'selected' : '' }}>Manual</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Fixed (R$)</label>
                            <input type="number" step="0.01" name="provider_fixed_fee" class="form-control" value="{{ $config->provider_fixed_fee }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Percent (%)</label>
                            <input type="number" step="0.01" name="provider_percent_fee" class="form-control" value="{{ $config->provider_percent_fee }}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Status</label>
                        <select name="is_active" class="form-control">
                            <option value="1" {{ $config->is_active ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ !$config->is_active ? 'selected' : '' }}>Disabled</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 mt-3">Save Configuration</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Simulator -->
    <div class="col-md-5">
        <div class="card shadow mb-4 bg-dark text-white">
            <div class="card-header py-3 bg-dark border-bottom border-secondary">
                <h6 class="m-0 font-weight-bold text-success"><i class="fas fa-calculator"></i> Fee Simulator</h6>
            </div>
            <div class="card-body">
                <p class="text-sm text-secondary mb-4">Save the form first, then simulate the values below based on the database settings.</p>
                
                <div class="form-group mb-3">
                    <label>Gross Amount (R$)</label>
                    <input type="number" id="sim_amount" class="form-control bg-secondary text-white border-0" value="100.00">
                </div>
                
                <div class="form-group mb-3">
                    <label>Operation</label>
                    <select id="sim_type" class="form-control bg-secondary text-white border-0">
                        <option value="deposit">Pix In (Deposit)</option>
                        <option value="withdraw">Cash Out (Withdraw)</option>
                    </select>
                </div>

                <button type="button" class="btn btn-success w-100 mb-4" onclick="runSimulation()">Simulate Split</button>

                <div id="sim_result" style="display:none;">
                    <ul class="list-group list-group-flush text-dark">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Gross Amount
                            <span class="badge badge-primary badge-pill" id="res_gross">0.00</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Platform Revenue
                            <span class="badge badge-success badge-pill" id="res_plat">0.00</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Gateway Fee
                            <span class="badge badge-danger badge-pill" id="res_gate">0.00</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <strong>Net (Sent to Merchant/Bank)</strong>
                            <span class="badge badge-warning badge-pill text-dark" id="res_net">0.00</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function runSimulation() {
    const amount = document.getElementById('sim_amount').value;
    const type = document.getElementById('sim_type').value;

    fetch('{{ route('admin.gateway-fees.simulate') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            provider: '{{ $config->provider }}',
            amount: amount,
            type: type
        })
    })
    .then(r => r.json())
    .then(data => {
        if(data.success) {
            document.getElementById('res_gross').innerText = data.gross_amount.toFixed(2);
            document.getElementById('res_plat').innerText = data.platform_fee.toFixed(2);
            document.getElementById('res_gate').innerText = data.provider_fee.toFixed(2);
            document.getElementById('res_net').innerText = data.net_amount.toFixed(2);
            document.getElementById('sim_result').style.display = 'block';
        } else {
            alert(data.message);
        }
    });
}
</script>
@endsection
