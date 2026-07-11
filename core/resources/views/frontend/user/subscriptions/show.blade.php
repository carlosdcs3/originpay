@extends('frontend.layouts.user-v2')
@section('title', 'Detalhes da Assinatura')

@section('styles')
<style>
    .sub-detail-shell { display:flex; flex-direction:column; gap:12px; }
    .sub-detail-grid { display:grid; grid-template-columns:1.1fr .9fr; gap:12px; }
    .sub-card { background:var(--ds-bg-card); border:1px solid var(--ds-border-light); border-radius:10px; padding:16px; }
    .sub-v2-card-header { color:var(--ds-text-main); font-weight:850; margin-bottom:12px; }
    .sub-row { display:flex; justify-content:space-between; gap:16px; padding:9px 0; border-bottom:1px solid var(--ds-border-light); color:var(--ds-text-muted); font-size:.82rem; }
    .sub-row:last-child { border-bottom:0; }
    .sub-row strong { color:var(--ds-text-main); text-align:right; }
    .sub-status { display:inline-flex; align-items:center; justify-content:center; padding:5px 10px; border-radius:999px; font-size:.72rem; font-weight:850; }
    .sub-status.active { color:#22c55e; background:rgba(34,197,94,.12); }
    .sub-status.pending { color:#fbbf24; background:rgba(245,158,11,.12); }
    .sub-status.past_due,.sub-status.incomplete { color:#ef4444; background:rgba(239,68,68,.12); }
    .sub-status.canceled { color:#94a3b8; background:rgba(148,163,184,.12); }
    .sub-table { width:100%; border-collapse:collapse; table-layout:fixed; }
    .sub-table th,.sub-table td { padding:11px 10px; border-bottom:1px solid var(--ds-border-light); color:var(--ds-text-secondary); font-size:.8rem; }
    .sub-table th { color:var(--ds-text-muted); font-size:.68rem; text-transform:uppercase; letter-spacing:.07em; }
</style>
@endsection

@section('content')
@php
    $money = fn ($value) => 'R$ ' . number_format((float) $value, 2, ',', '.');
    $status = $subscription->status?->value ?? (string)$subscription->status;
    $latestInvoice = $subscription->invoices->first();
    $latestCharge = $latestInvoice?->charge;
    $description = $subscription->items->first()?->description ?: $subscription->description ?: 'Assinatura recorrente';
@endphp
<div class="sub-detail-shell">
    <div class="v2-page-header" style="margin:0;justify-content:space-between;align-items:center;">
        <div>
            <h1 class="v2-page-title" style="margin-bottom:2px;">Detalhes da assinatura</h1>
            <p class="v2-page-subtitle" style="margin:0;">{{ $subscription->uuid }}</p>
        </div>
        <div style="display:flex;gap:10px;">
            <a href="{{ route('user.subscriptions.index') }}" class="v2-btn-secondary" style="height:36px;padding:0 14px;text-decoration:none;"><i class="fas fa-arrow-left"></i> Voltar</a>
            @if($status !== 'canceled')
                <form method="POST" action="{{ route('user.subscriptions.cancel', $subscription->uuid) }}">@csrf<input type="hidden" name="cancel_at_period_end" value="1"><button class="v2-btn-secondary" style="height:36px;padding:0 14px;color:#fbbf24;" onclick="return confirm('Cancelar no fim do periodo atual?')"><i class="far fa-clock"></i> Cancelar no fim</button></form>
                <form method="POST" action="{{ route('user.subscriptions.cancel', $subscription->uuid) }}">@csrf<button class="v2-btn-secondary" style="height:36px;padding:0 14px;color:#f87171;" onclick="return confirm('Cancelar imediatamente?')"><i class="fas fa-ban"></i> Cancelar agora</button></form>
            @endif
        </div>
    </div>

    <div class="sub-detail-grid">
        <div class="sub-card">
            <div class="sub-v2-card-header">Assinatura</div>
            <div class="sub-row"><span>Status</span><strong><span class="sub-status {{ $status }}">{{ uiStatusLabel($status) }}</span></strong></div>
            <div class="sub-row"><span>Cliente</span><strong>{{ $subscription->customer_name }}</strong></div>
            <div class="sub-row"><span>E-mail</span><strong>{{ $subscription->customer_email }}</strong></div>
            <div class="sub-row"><span>Documento</span><strong>{{ $subscription->customer_document ?: '-' }}</strong></div>
            <div class="sub-row"><span>Descricao</span><strong>{{ $description }}</strong></div>
            <div class="sub-row"><span>Valor</span><strong>{{ $money($subscription->amount) }} {{ $subscription->currency }}</strong></div>
            <div class="sub-row"><span>Metodo</span><strong>{{ ucfirst($subscription->payment_method) }}</strong></div>
        </div>
        <div class="sub-card">
            <div class="sub-v2-card-header">Ciclo</div>
            <div class="sub-row"><span>Inicio atual</span><strong>{{ $subscription->current_period_start?->format('d/m/Y H:i') ?: '-' }}</strong></div>
            <div class="sub-row"><span>Fim atual</span><strong>{{ $subscription->current_period_end?->format('d/m/Y H:i') ?: '-' }}</strong></div>
            <div class="sub-row"><span>Proxima cobranca</span><strong>{{ $subscription->next_billing_at?->format('d/m/Y H:i') ?: '-' }}</strong></div>
            <div class="sub-row"><span>Cancelar no fim</span><strong>{{ $subscription->cancel_at_period_end ? 'Sim' : 'Nao' }}</strong></div>
            <div class="sub-row"><span>Cancelada em</span><strong>{{ $subscription->canceled_at?->format('d/m/Y H:i') ?: '-' }}</strong></div>
            <div class="sub-row"><span>Ultimo erro</span><strong>{{ $subscription->last_error ?: '-' }}</strong></div>
        </div>
    </div>

    <div class="sub-card">
        <div class="sub-v2-card-header">Ultima invoice e cobranca</div>
        <div class="sub-row"><span>Invoice</span><strong>{{ $latestInvoice?->uuid ?: '-' }}</strong></div>
        <div class="sub-row"><span>Status da invoice</span><strong>{{ uiStatusLabel($latestInvoice?->status?->value ?? $latestInvoice?->status) }}</strong></div>
        <div class="sub-row"><span>Charge</span><strong>@if($latestCharge && Route::has('user.charge.show'))<a href="{{ route('user.charge.show', $latestCharge->id) }}" style="color:#a78bfa;">{{ $latestCharge->uuid }}</a>@else {{ $latestCharge?->uuid ?: '-' }} @endif</strong></div>
        <div class="sub-row"><span>Status da charge</span><strong>{{ uiStatusLabel($latestCharge?->status?->value ?? $latestCharge?->status) }}</strong></div>
        <div class="sub-row"><span>Ultimo pagamento</span><strong>{{ $latestInvoice?->paid_at?->format('d/m/Y H:i') ?: $latestCharge?->paid_at?->format('d/m/Y H:i') ?: '-' }}</strong></div>
        <div class="sub-row"><span>Proxima cobranca</span><strong>{{ $subscription->next_billing_at?->format('d/m/Y H:i') ?: '-' }}</strong></div>
        <div class="sub-row"><span>Ultima tentativa</span><strong>{{ $latestInvoice?->failed_at?->format('d/m/Y H:i') ?: $latestInvoice?->updated_at?->format('d/m/Y H:i') ?: '-' }}</strong></div>
    </div>

    <div class="sub-card">
        <div class="sub-v2-card-header">Invoices e cobranças</div>
        <table class="sub-table">
            <thead><tr><th>Invoice</th><th>Status</th><th>Periodo</th><th>Valor</th><th>Pago</th><th>Ultima tentativa</th><th>Charge</th></tr></thead>
            <tbody>
            @forelse($subscription->invoices as $invoice)
                <tr>
                    <td>{{ $invoice->uuid }}</td>
                    <td>{{ uiStatusLabel($invoice->status?->value ?? $invoice->status) }}</td>
                    <td>{{ $invoice->period_start?->format('d/m/Y') }} - {{ $invoice->period_end?->format('d/m/Y') }}</td>
                    <td>{{ $money($invoice->amount_due) }}</td>
                    <td>{{ $money($invoice->amount_paid) }}</td>
                    <td>{{ $invoice->failed_at?->format('d/m/Y H:i') ?: $invoice->updated_at?->format('d/m/Y H:i') ?: '-' }}</td>
                    <td>@if($invoice->charge && Route::has('user.charge.show'))<a href="{{ route('user.charge.show', $invoice->charge->id) }}" style="color:#a78bfa;">{{ $invoice->charge->uuid }}</a>@else - @endif</td>
                </tr>
            @empty
                <tr><td colspan="7">Nenhuma invoice vinculada.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
