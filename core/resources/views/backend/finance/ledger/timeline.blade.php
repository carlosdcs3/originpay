@extends('backend.layouts.app')
@section('title', 'Ledger Timeline - ' . $charge->id)

@section('content')
<x-ds.page 
    title="Ledger Timeline: Charge #{{ $charge->id }}" 
    desc="Rastreabilidade financeira absoluta. Visualize o ciclo de vida completo desta cobrança."
    :breadcrumb="[
        ['title' => 'Financeiro'],
        ['title' => 'Ledger'],
        ['title' => 'Timeline']
    ]">

    <div class="row">
        <div class="col-lg-4">
            <x-ds.card title="Detalhes da Cobrança">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Valor:</span>
                        <strong>R$ {{ number_format($charge->amount, 2, ',', '.') }}</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Gateway:</span>
                        <strong>{{ $charge->gateway->name ?? 'N/A' }}</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Status Atual:</span>
                        <strong><x-ds.badge type="primary">{{ \App\Enums\ChargeStatus::getKey($charge->status) }}</x-ds.badge></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Lojista:</span>
                        <strong>{{ $charge->user->username ?? 'N/A' }}</strong>
                    </li>
                </ul>
            </x-ds.card>
        </div>

        <div class="col-lg-8">
            <x-ds.card title="Linha do Tempo (Eventos Processados)">
                <div class="timeline">
                    {{-- Criação --}}
                    <div class="timeline-item">
                        <div class="text-muted text-sm">{{ $charge->created_at }}</div>
                        <h6>Cobrança Criada</h6>
                        <p class="text-sm">Aguardando pagamento pelo Gateway.</p>
                    </div>

                    {{-- Webhooks/Eventos --}}
                    @foreach($processedEvents as $evt)
                        <div class="timeline-item mt-3 border-start ps-3 border-info">
                            <div class="text-muted text-sm">{{ $evt->created_at }}</div>
                            <h6>Idempotência: Evento Recebido</h6>
                            <p class="text-sm mb-0">Type: <code>{{ $evt->event_type }}</code> | Status: <code>{{ $evt->status }}</code></p>
                            <span class="text-xs text-muted">Idempotency Key: {{ $evt->idempotency_key }}</span>
                        </div>
                    @endforeach

                    {{-- Ledger Transactions --}}
                    @foreach($walletTx as $tx)
                        <div class="timeline-item mt-3 border-start ps-3 border-success">
                            <div class="text-muted text-sm">{{ $tx->created_at }}</div>
                            <h6>Lançamento no Ledger ({{ $tx->type }})</h6>
                            <p class="text-sm mb-0">
                                Valor: <strong>R$ {{ number_format($tx->amount, 2, ',', '.') }}</strong> | 
                                Desc: {{ $tx->description }}
                            </p>
                            <p class="text-xs text-muted mb-0">
                                HMAC: <code>{{ substr($tx->integrity_hash, 0, 16) }}...</code>
                            </p>
                            <p class="text-xs text-muted">
                                Correlation ID: {{ $tx->correlation_id }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </x-ds.card>
        </div>
    </div>
</x-ds.page>
@endsection
