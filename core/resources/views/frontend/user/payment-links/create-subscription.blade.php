@extends('frontend.layouts.user-v2')
@section('title', 'Nova Assinatura')

@section('styles')
<style>
    .pl-form{max-width:920px}.pl-card{background:var(--ds-bg-card);border:1px solid var(--ds-border-light);border-radius:10px;padding:18px}.pl-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.pl-field label{display:block;color:var(--ds-text-muted);font-size:.72rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em;margin-bottom:7px}.pl-field input,.pl-field select,.pl-field textarea{width:100%;height:40px;border-radius:9px;border:1px solid var(--ds-border-medium);background:rgba(255,255,255,.03);color:var(--ds-text-secondary);padding:0 12px;outline:none}.pl-field textarea{height:82px;padding-top:10px;resize:vertical}.pl-field option{background:#11151e;color:#e2e8f0}.pl-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:16px}
</style>
@endsection

@section('content')
<div class="pl-form">
    <div class="v2-page-header" style="margin:0 0 14px;justify-content:space-between;align-items:center;">
        <div>
            <h1 class="v2-page-title" style="margin-bottom:2px;">Nova assinatura</h1>
            <p class="v2-page-subtitle" style="margin:0;">Crie um checkout de assinatura; o cliente preencherá os dados e iniciará o pagamento.</p>
        </div>
        <a href="{{ route('user.payment-links.index') }}" class="v2-btn-secondary" style="height:36px;padding:0 14px;text-decoration:none;"><i class="fas fa-arrow-left"></i> Voltar</a>
    </div>
    <form method="POST" action="{{ route('user.payment-links.subscriptions.store') }}" class="pl-card">
        @csrf
        <div class="pl-grid">
            <div class="pl-field"><label>Titulo do link</label><input name="title" value="{{ old('title') }}" required></div>
            <div class="pl-field"><label>Valor recorrente</label><input name="amount" type="number" step="0.01" min="1" value="{{ old('amount') }}" required></div>
            <div class="pl-field">
                <label>Metodos aceitos</label>
                @if($paymentMethods->isNotEmpty())
                    <select name="allowed_payment_methods[]" multiple required>
                        @foreach($paymentMethods as $method)
                            <option value="{{ $method['code'] }}" selected>{{ $method['label'] }}</option>
                        @endforeach
                    </select>
                @else
                    <div style="border:1px dashed var(--ds-border-medium);border-radius:9px;padding:12px;color:var(--ds-text-muted);font-size:.8rem;">
                        Nenhum metodo de pagamento esta disponivel. Entre em contato com o administrador.
                    </div>
                @endif
            </div>
            <div class="pl-field"><label>Moeda</label><input name="currency" value="{{ old('currency', 'BRL') }}" maxlength="3"></div>
            <div class="pl-field"><label>Intervalo</label><select name="interval" required><option value="month">Mensal</option><option value="week">Semanal</option><option value="year">Anual</option><option value="day">Diario</option></select></div>
            <div class="pl-field"><label>A cada</label><input name="interval_count" type="number" min="1" max="24" value="{{ old('interval_count', 1) }}"></div>
            <div class="pl-field"><label>Inicio</label><input name="start_at" type="date" value="{{ old('start_at') }}"></div>
            <div class="pl-field" style="grid-column:1/-1;"><label>Descricao</label><textarea name="description">{{ old('description') }}</textarea></div>
        </div>
        <div class="pl-actions">
            <a href="{{ route('user.payment-links.index') }}" class="v2-btn-secondary" style="height:38px;padding:0 16px;text-decoration:none;">Cancelar</a>
            <button class="v2-btn-primary" style="height:38px;padding:0 16px;" @disabled($paymentMethods->isEmpty())><i class="fas fa-link"></i> Criar link</button>
        </div>
    </form>
</div>
@endsection
