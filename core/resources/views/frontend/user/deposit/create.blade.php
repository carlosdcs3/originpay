@extends('frontend.layouts.user-v2')
@section('title', __('Depositar'))

@section('content')

{{-- Page Header --}}
<div class="ds-page-header">
    <h4 style="font-size:1.1rem;font-weight:700;margin:0 0 2px;color:var(--ds-text-primary);">
        <i class="fas fa-wallet" style="color:var(--ds-primary-light);margin-right:8px;font-size:0.95rem;"></i>
        Depositar
    </h4>
    <p style="color:var(--ds-text-muted);font-size:0.8rem;margin:0;">Adicione saldo à sua carteira instantaneamente</p>
</div>

<div class="ds-tx-grid">

    {{-- ── FORM CARD ──────────────────────────────────────────── --}}
    <div class="ds-card">
        <div class="ds-card-header">
            <span class="ds-v2-card-header">
                <i class="fas fa-wallet" style="color:var(--ds-primary-light);margin-right:6px;"></i>
                Depositar
            </span>
            <a href="{{ route('user.transaction.index', ['type' => \App\Enums\TrxType::DEPOSIT]) }}" class="ds-card-link">
                <i class="fas fa-history" style="font-size:0.75rem;"></i> Extrato
            </a>
        </div>
        <div class="ds-card-body padded">
            <form action="{{ route('user.deposit.store') }}" method="post" enctype="multipart/form-data"
                  onsubmit="disableSubmitButton(this, '{{ __('Processing...') }}')">
                @csrf

                {{-- Wallet is implicitly BRL and hidden --}}
                <div style="display:none;">
                    <select class="v2-input wallet-select" name="wallet_id">
                        @foreach($wallets as $wallet)
                            <option value="{{ $wallet->id }}" @selected($wallet->currency->code == 'BRL' || $loop->first)>
                                {{ $wallet->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="ds-form-group">
                    <label class="ds-label">Método de Pagamento</label>

                    <div id="loading-method-display" class="skeleton-loader" style="height:52px;border-radius:12px;background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.05);position:relative;overflow:hidden;"></div>

                    <div id="single-method-display" class="ds-input-group" style="display:none;align-items:center;height:52px;padding:0 16px;background:#0a0b10;border:1px solid rgba(255,255,255,0.1);border-radius:12px;">
                        <svg width="18" height="18" viewBox="0 0 512 512" fill="none" xmlns="http://www.w3.org/2000/svg" style="margin-right:10px;flex-shrink:0;">
                            <path d="M116.7 394.4c19.5 0 37.9-7.6 51.7-21.4l73.9-73.9c6.6-6.6 17.4-6.6 24 0l74.2 74.2c13.8 13.8 32.2 21.4 51.7 21.4h14.5l-93.5 93.5c-29.6 29.6-77.6 29.6-107.2 0L112 394.4h4.7zM392.2 117.6c-19.5 0-37.9 7.6-51.7 21.4l-74.2 74.2c-6.6 6.6-17.4 6.6-24 0l-73.9-73.9c-13.8-13.8-32.2-21.4-51.7-21.4H112L206 23.4c29.6-29.6 77.6-29.6 107.2 0l93.5 93.5-14.5.7zM23.4 206l55.4-55.4h37.9c13.2 0 25.8 5.2 35.1 14.5l73.9 73.9c18.3 18.3 48 18.3 66.3 0l74.2-74.2c9.3-9.3 21.9-14.5 35.1-14.5h42.3L488.6 206c29.6 29.6 29.6 77.6 0 107.2l-44.9 44.9h-42.3c-13.2 0-25.8-5.2-35.1-14.5l-74.2-74.2c-18.3-18.3-48-18.3-66.3 0l-73.9 73.9c-9.3 9.3-21.9 14.5-35.1 14.5H78.8L23.4 313.2c-29.6-29.6-29.6-77.6 0-107.2z" fill="var(--ds-teal)"/>
                        </svg>
                        <span id="single-method-name" style="color:#e2e8f0;font-size:1rem;">PIX</span>
                    </div>

                    <div id="multi-method-display" style="display:none;">
                        <select class="v2-input deposit-method-list" name="payment_method"></select>
                    </div>
                    <span class="ds-field-hint deposit-method-info"></span>
                </div>

                <div id="manual-deposit-credentials"></div>

                <div class="ds-form-group">
                    <label class="ds-label">Valor do Depósito</label>
                    <div class="ds-input-group">
                        <input type="text" class="v2-input deposit-amount" name="amount"
                               oninput="this.value = validateDouble(this.value)"
                               placeholder="0,00">
                        <span class="ds-input-addon">{{ siteCurrency() }}</span>
                    </div>
                    <span class="ds-field-hint deposit-amount-info"></span>
                </div>

                <button type="submit" class="ds-btn-submit">
                    <i class="fas fa-wallet"></i>
                    Depositar Agora
                </button>
            </form>
        </div>
    </div>

    {{-- ── SUMMARY ────────────────────────────────────────────── --}}
    <div>
        @include('frontend.user.deposit.partials._summary')
    </div>
</div>

@endsection

@push('scripts')
    @include('frontend.user.deposit.partials._script')
@endpush