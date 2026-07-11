@extends('backend.layouts.app')
@section('title', 'Todos Gateways')

@section('content')
<div class="py-4">
    <div class="d-flex justify-content-between align-items-center w-100 flex-wrap gap-3">
        <div class="mb-3 mb-lg-0">
            <h1 class="h3 fw-bold mb-1">
                <x-icon name="payment" height="28" width="28" class="me-1 text-primary align-middle"/>
                Todos Gateways
            </h1>
            <div class="text-muted small lh-sm">
                Centro de configuração e acompanhamento dos gateways conectados à OriginPay.
            </div>
        </div>
        <button class="btn btn-primary d-flex align-items-center px-4 shadow-sm" data-coreui-toggle="modal" data-bs-toggle="modal" data-coreui-target="#new-payment-gateway-modal" data-bs-target="#new-payment-gateway-modal">
            <x-icon name="add" height="20" width="20" class="me-2"/>
            <span class="fw-semibold">Novo gateway</span>
        </button>
    </div>
</div>

<x-ds.table 
    title="Lista de Gateways" 
    :count="$paymentGateways->total() ?? $paymentGateways->count()"
    :isEmpty="$paymentGateways->isEmpty()"
    :action="route('admin.payment.gateway.index')">
    
    <x-slot name="thead">
        <th>Gateway</th>
        <th>Ambiente</th>
        <th>Métodos</th>
        <th>Credenciais</th>
        <th>Status</th>
        <th class="text-end">Ações</th>
    </x-slot>

    @forelse($paymentGateways as $paymentGateway)
        @php
            $supportedMethods = collect([
                $paymentGateway->supports_pix ? 'Pix' : null,
                $paymentGateway->supports_card ? 'Cartão' : null,
                $paymentGateway->supports_boleto ? 'Boleto' : null,
                $paymentGateway->is_withdraw ? 'Saque' : null,
            ])->filter()->values();
            $credentialValues = collect(is_array($paymentGateway->credentials) ? $paymentGateway->credentials : [])
                ->filter(fn ($value) => filled($value));
        @endphp
        <tr>
            <td>
                <div class="d-flex align-items-center gap-3">
                    @if($paymentGateway->logo)
                        <img src="{{ asset($paymentGateway->logo) }}" height="28" alt="{{ $paymentGateway->name }}" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="gateway-logo-fallback" style="display:none; width: 28px; height: 28px; background: rgba(148,163,184,.10); border-radius: 4px; align-items: center; justify-content: center; color: #cbd5e1;"><i class="fa-solid fa-plug"></i></div>
                    @else
                        <div class="gateway-logo-fallback" style="width: 28px; height: 28px; background: rgba(148,163,184,.10); border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #cbd5e1;"><i class="fa-solid fa-plug"></i></div>
                    @endif
                    <div>
                        <div class="fw-semibold">{{ $paymentGateway->name }}</div>
                        <div style="font-family:var(--ds-font-mono);font-size:var(--ds-text-sm); color:var(--ds-text-muted);">{{ $paymentGateway->code }}</div>
                    </div>
                </div>
            </td>
            <td>
                @if($paymentGateway->is_sandbox)
                    <x-ds.badge status="pending" label="Sandbox" />
                @else
                    <x-ds.badge status="paid" label="Produção" />
                @endif
            </td>
            <td>
                <div class="d-flex flex-wrap gap-1">
                    @forelse($supportedMethods as $method)
                        <span class="badge op-badge">{{ $method }}</span>
                    @empty
                        <span class="text-muted small">Nenhum</span>
                    @endforelse
                </div>
            </td>
            <td>
                @if($credentialValues->isNotEmpty())
                    <x-ds.badge status="paid" label="Configuradas" />
                @else
                    <x-ds.badge status="pending" label="Pendentes" />
                @endif
            </td>
            <td>
                @if($paymentGateway->status)
                    <x-ds.badge status="paid" label="Ativo" />
                @else
                    <x-ds.badge status="cancelled" label="Inativo" />
                @endif
            </td>
            <td class="text-end">
                <a href="{{ route('admin.payment.gateway.settings', $paymentGateway->id) }}" class="btn btn-primary btn-sm">
                    Configurar
                </a>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="6">
                <x-ds.empty-state 
                    title="Nenhum gateway cadastrado" 
                    desc="Adicione um gateway para começar a processar pagamentos." 
                    icon='<path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>'
                />
            </td>
        </tr>
    @endforelse

    <x-slot name="pagination">
        <x-ds.pagination :paginator="$paymentGateways" />
    </x-slot>
</x-ds.table>

@include('backend.payment_gateway.partial._new_payment_gateway_modal')
@endsection
