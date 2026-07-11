@extends('backend.layouts.app')
@section('title', 'Detalhes da Cobrança')

@section('content')
<div class="row">
    <div class="col-xl-8 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Cobrança #{{ $charge->id }}</h5>
                <div>
                    @if($charge->status->value == 'paid')
                        <span class="badge bg-success">Pago</span>
                    @elseif($charge->status->value == 'waiting_payment')
                        <span class="badge bg-warning text-dark">Aguardando Pagamento</span>
                    @else
                        <span class="badge bg-secondary">{{ $charge->status->label() }}</span>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-sm-6">
                        <p class="mb-1 text-muted">Tenant / Usuário</p>
                        <h6><a href="{{ $charge->user ? route('admin.user.manage', $charge->user->username) : '#' }}">{{ $charge->user->username ?? 'N/A' }} ({{ $charge->user->email ?? '' }})</a></h6>
                    </div>
                    <div class="col-sm-6">
                        <p class="mb-1 text-muted">ID no Gateway ({{ $charge->gateway_id }})</p>
                        <h6>{{ $charge->gateway_charge_id }}</h6>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-sm-4">
                        <p class="mb-1 text-muted">Valor Bruto</p>
                        <h5 class="text-primary">R$ {{ number_format($charge->amount, 2, ',', '.') }}</h5>
                    </div>
                    <div class="col-sm-4">
                        <p class="mb-1 text-muted">Taxa da Plataforma</p>
                        <h5 class="text-danger">-R$ {{ number_format($charge->platform_fee, 2, ',', '.') }}</h5>
                    </div>
                    <div class="col-sm-4">
                        <p class="mb-1 text-muted">Líquido Repassado</p>
                        <h5 class="text-success">R$ {{ number_format($charge->net_amount, 2, ',', '.') }}</h5>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-sm-4">
                        <p class="mb-1 text-muted">Cliente Nome</p>
                        <h6>{{ $charge->customer_name ?: 'N/A' }}</h6>
                    </div>
                    <div class="col-sm-4">
                        <p class="mb-1 text-muted">Cliente Documento</p>
                        <h6>{{ $charge->customer_document ?: 'N/A' }}</h6>
                    </div>
                    <div class="col-sm-4">
                        <p class="mb-1 text-muted">Cliente E-mail</p>
                        <h6>{{ $charge->customer_email ?: 'N/A' }}</h6>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Logs de Webhook</h5>
            </div>
            <div class="card-body">
                <div class="timeline">
                    @forelse($charge->events as $event)
                    <div class="border-bottom mb-3 pb-3">
                        <div class="d-flex justify-content-between">
                            <strong>{{ $event->event }}</strong>
                            <small class="text-muted">{{ $event->processed_at->format('d/m/Y H:i:s') }}</small>
                        </div>
                        <p class="mb-1 text-muted small">Event ID: {{ $event->gateway_event_id }}</p>
                        <pre class="bg-light p-2 rounded small" style="max-height: 150px; overflow-y: auto;">{{ json_encode($event->payload, JSON_PRETTY_PRINT) }}</pre>
                    </div>
                    @empty
                    <p class="text-muted">Nenhum log registrado.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
