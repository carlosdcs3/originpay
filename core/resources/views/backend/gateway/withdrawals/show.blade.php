@extends('backend.layouts.app')
@section('title', 'Detalhes do Saque')

@section('content')
<div class="row">
    <div class="col-xl-8 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Saque #{{ $withdrawal->id }}</h5>
                <div>
                    @if($withdrawal->status == 'SUCCESS')
                        <span class="badge bg-success">Pago</span>
                    @elseif($withdrawal->status == 'PENDING' || $withdrawal->status == 'PROCESSING')
                        <span class="badge bg-warning text-dark">Em Processamento</span>
                    @else
                        <span class="badge bg-danger">{{ $withdrawal->status }}</span>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-sm-6">
                        <p class="mb-1 text-muted">Tenant / Usuário</p>
                        <h6><a href="{{ $withdrawal->user ? route('admin.user.manage', $withdrawal->user->username) : '#' }}">{{ $withdrawal->user->username ?? 'N/A' }}</a></h6>
                    </div>
                    <div class="col-sm-6">
                        <p class="mb-1 text-muted">Data da Solicitação</p>
                        <h6>{{ $withdrawal->created_at->format('d/m/Y H:i:s') }}</h6>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-sm-4">
                        <p class="mb-1 text-muted">Valor Solicitado</p>
                        <h5 class="text-primary">R$ {{ number_format($withdrawal->amount, 2, ',', '.') }}</h5>
                    </div>
                    <div class="col-sm-4">
                        <p class="mb-1 text-muted">Taxa de Saque</p>
                        <h5 class="text-danger">-R$ {{ number_format($withdrawal->charge, 2, ',', '.') }}</h5>
                    </div>
                    <div class="col-sm-4">
                        <p class="mb-1 text-muted">Valor a Transferir</p>
                        <h5 class="text-success">R$ {{ number_format($withdrawal->final_amount, 2, ',', '.') }}</h5>
                    </div>
                </div>

                <h5 class="mb-3 border-bottom pb-2">Dados do Recebedor</h5>
                @php
                    $credentials = json_decode($withdrawal->credentials, true);
                @endphp
                <div class="row mb-4">
                    <div class="col-sm-6">
                        <p class="mb-1 text-muted">Chave PIX</p>
                        <h6>{{ $credentials['pix_key'] ?? 'N/A' }}</h6>
                    </div>
                    <div class="col-sm-6">
                        <p class="mb-1 text-muted">Tipo da Chave</p>
                        <h6>{{ $credentials['pix_key_type'] ?? 'N/A' }}</h6>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
