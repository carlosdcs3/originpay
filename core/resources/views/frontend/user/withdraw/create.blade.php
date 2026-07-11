@extends('frontend.layouts.user-v2')
@section('title', __('Sacar'))

@section('content')

{{-- Page Header --}}
<div class="ds-page-header">
    <h4 style="font-size:1.1rem;font-weight:700;margin:0 0 2px;color:var(--ds-text-primary);">
        <i class="fas fa-arrow-down" style="color:var(--ds-primary-light);margin-right:8px;font-size:0.95rem;"></i>
        Sacar Dinheiro
    </h4>
    <p style="color:var(--ds-text-muted);font-size:0.8rem;margin:0;">Transfira para sua conta bancaria cadastrada</p>
</div>

<div class="ds-tx-grid">

    {{-- FORM CARD --}}
    <div class="ds-card">
        <div class="ds-card-header">
            <span class="ds-v2-card-header">
                <i class="fas fa-arrow-down" style="color:var(--ds-primary-light);margin-right:6px;"></i>
                Sacar Dinheiro
            </span>
            <div style="display:flex;gap:8px;">
                <a href="{{ route('user.transaction.index', ['type' => \App\Enums\TrxType::WITHDRAW]) }}" class="ds-card-link">
                    <i class="fas fa-history" style="font-size:0.75rem;"></i> Extrato
                </a>
                <span style="color:var(--ds-border-medium);">|</span>
                <a href="{{ route('user.withdraw.account.index') }}" class="ds-card-link">
                    <i class="fas fa-university" style="font-size:0.75rem;"></i> Contas
                </a>
            </div>
        </div>
        <div class="ds-card-body padded">
            <form action="{{ route('user.withdraw.store') }}" method="POST" id="withdraw-form">
                @csrf

                <div class="ds-form-group">
                    <label class="ds-label">Selecionar Chave PIX</label>
                    @if($pixKeys->isEmpty())
                        <div style="background: rgba(255, 77, 106, 0.1); border-left: 3px solid var(--ds-error); padding: 12px; border-radius: 4px; margin-bottom: 10px;">
                            <span style="color: var(--ds-error); font-size: 0.85rem; display: block; margin-bottom: 5px;">Voce nao possui nenhuma chave PIX cadastrada.</span>
                            <a href="{{ route('user.pix-keys.index') }}" class="ds-btn" style="font-size: 0.8rem; padding: 6px 12px; background: var(--ds-error); color: white; display: inline-block;">
                                Cadastrar Chave PIX
                            </a>
                        </div>
                    @else
                        <select class="v2-input" name="pix_key_id" required>
                            @foreach($pixKeys as $key)
                                <option value="{{ $key->id }}" @selected($key->is_primary)>
                                    {{ $key->pix_key }} ({{ strtoupper($key->key_type) }}) {{ $key->is_primary ? ' - Principal' : '' }}
                                </option>
                            @endforeach
                        </select>
                        <div style="background: rgba(255, 193, 7, 0.1); border-left: 3px solid #ffc107; padding: 8px 12px; margin-top: 8px; border-radius: 4px;">
                            <span style="color: #ffc107; font-size: 0.75rem; font-weight: 600; display: block; margin-bottom: 2px;">
                                <i class="fas fa-shield-alt"></i> Regra de Seguranca Antifraude
                            </span>
                            <span style="color: var(--ds-text-muted); font-size: 0.75rem;">
                                Por motivos de seguranca, o saque <strong>so sera aprovado</strong> se a chave PIX pertencer ao <strong>mesmo CPF ou CNPJ</strong> cadastrado e verificado na sua conta.
                            </span>
                        </div>
                    @endif
                </div>

                <div class="ds-form-group">
                    <label class="ds-label">Valor do Saque</label>
                    <div class="ds-input-group">
                        <input type="text" class="v2-input amount-input" name="amount"
                               placeholder="0,00"
                               oninput="this.value = validateDouble(this.value)" required>
                        <span class="ds-input-addon">{{ siteCurrency() }}</span>
                    </div>
                    <span class="ds-field-hint withdraw-amount-info"></span>
                </div>

                <button type="submit" class="ds-btn-submit" data-tp-confirm="true">
                    <i class="fas fa-arrow-down"></i>
                    Sacar Agora
                </button>
            </form>
        </div>
    </div>

    {{-- SUMMARY --}}
    <div>
        @include('frontend.user.withdraw.partials._summary')
    </div>
</div>

@endsection

@push('scripts')
    @include('frontend.user.withdraw.partials._script')
@endpush
