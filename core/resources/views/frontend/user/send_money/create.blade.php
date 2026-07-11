@extends('frontend.layouts.user-v2')
@section('title', __('Enviar / Sacar'))

@section('content')

{{-- Page Header --}}
<div class="ds-page-header">
    <h4 style="font-size:1.1rem;font-weight:700;margin:0 0 2px;color:var(--ds-text-primary);">
        <i class="fas fa-paper-plane" style="color:var(--ds-teal);margin-right:8px;font-size:0.95rem;"></i>
        Enviar Dinheiro
    </h4>
    <p style="color:var(--ds-text-muted);font-size:0.8rem;margin:0;">Transfira para outros usuários da plataforma</p>
</div>

<div class="ds-tx-grid">

    {{-- ── FORM CARD ──────────────────────────────────────────── --}}
    <div class="ds-card">
        <div class="ds-card-header">
            <span class="ds-v2-card-header">
                <i class="fas fa-paper-plane" style="color:var(--ds-teal);margin-right:6px;"></i>
                Enviar Dinheiro
            </span>
            <a href="{{ route('user.transaction.index', ['type' => \App\Enums\TrxType::SEND_MONEY]) }}" class="ds-card-link">
                <i class="fas fa-history" style="font-size:0.75rem;"></i> Extrato
            </a>
        </div>
        <div class="ds-card-body padded">
            <form action="{{ route('user.send-money.store') }}" method="POST"
                  onsubmit="disableSubmitButton(this, '{{ __('Processing...') }}')">
                @csrf

                <div class="ds-form-group">
                    <label class="ds-label">Destinatário</label>
                    <div class="ds-input-group">
                        <span class="ds-input-addon"><i class="fas fa-at"></i></span>
                        <input type="text" class="v2-input recipient-input" name="recipient"
                               placeholder="Digite o usuário ou e-mail" style="border-left:none;">
                    </div>
                    <span class="ds-field-hint recipient-info"></span>
                </div>

                <div style="display:none;">
                    <select class="v2-input wallet-select" name="wallet_id">
                        <option disabled selected>Selecionar Carteira</option>
                    </select>
                    <span class="ds-field-hint wallet-info"></span>
                </div>

                <div class="ds-form-group">
                    <label class="ds-label">Valor</label>
                    <div class="ds-input-group">
                        <input type="text" class="v2-input amount-input" name="amount"
                               placeholder="0,00"
                               oninput="this.value = validateDouble(this.value)">
                        <span class="ds-input-addon">{{ siteCurrency() }}</span>
                    </div>
                    <span class="ds-field-hint send-amount-info"></span>
                </div>

                <div class="ds-form-group">
                    <label class="ds-label">Observação <span style="color:var(--ds-text-muted);font-weight:400;">(opcional)</span></label>
                    <textarea class="v2-input" name="note" rows="2" placeholder="Ex: Almoço, divisão de conta..."></textarea>
                </div>

                <button type="submit" class="ds-btn-submit" style="background:var(--ds-purple);box-shadow:0 0 20px rgba(124,110,255,0.25);">
                    <i class="fas fa-paper-plane"></i>
                    Enviar Agora
                </button>
            </form>
        </div>
    </div>

    {{-- ── SUMMARY ────────────────────────────────────────────── --}}
    <div>
        @include('frontend.user.send_money.partials._summary')
    </div>
</div>

@endsection

@push('scripts')
    @include('frontend.user.send_money.partials._script')
@endpush