@extends('backend.finance.index')
@section('finance_title', 'Taxas de Gateway')
@section('finance_desc', 'Configuração de taxas e limites por gateway de pagamento.')

@section('finance_content')
@include('backend.finance.partials._tariffs_tabs')

<div class="row">
    <div class="col-12">
        <x-ds.card class="border-0 shadow-sm rounded-3 overflow-hidden">
            <div class="row align-items-center mb-4 px-3 pt-3">
                <div class="col-md-4 mb-3 mb-md-0">
                </div>
                <div class="col-md-8 text-md-end">
                    <button type="button" class="btn btn-outline-primary shadow-sm" data-bs-toggle="collapse" data-bs-target="#filterCard">
                        <i class="la la-filter me-1"></i> Filtros
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Provider</th>
                                <th>Transaction (IN)</th>
                                <th>Withdraw (OUT)</th>
                                <th>Provider Cost</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($configs as $config)
                            <tr>
                                <td><strong>{{ $config->provider }}</strong></td>
                                <td>
                                    {{ $config->transaction_fee_type }}<br>
                                    <small class="text-muted">Fix: {{ $config->transaction_fixed_fee }} | %: {{ $config->transaction_percent_fee }}</small>
                                </td>
                                <td>
                                    {{ $config->withdraw_fee_type }}<br>
                                    <small class="text-muted">Fix: {{ $config->withdraw_fixed_fee }} | %: {{ $config->withdraw_percent_fee }}</small>
                                </td>
                                <td>
                                    Mode: {{ $config->provider_fee_mode }}<br>
                                    <small class="text-muted">Fix: {{ $config->provider_fixed_fee }} | %: {{ $config->provider_percent_fee }}</small>
                                </td>
                                <td>
                                    @if($config->is_active)
                                        <span class="badge badge-success">Active</span>
                                    @else
                                        <span class="badge badge-danger">Disabled</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.gateway-fees.edit', $config->id) }}" class="btn btn-sm btn-primary">Edit / Simulate</a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center">No configs found. Run the seeder.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </x-ds.card>
    </div>
</div>
@endsection
