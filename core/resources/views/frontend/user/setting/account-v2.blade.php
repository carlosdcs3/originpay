@extends('frontend.user.setting.index')
@section('title', __('Minha Conta'))

@section('user_setting_content')

<style>
:root {
    --ac-primary: #7c3aed;
    --ac-surface: rgba(255,255,255,0.03);
    --ac-surface-2: rgba(255,255,255,0.045);
    --ac-border: rgba(255,255,255,0.07);
    --ac-border-hover: rgba(124,58,237,0.25);
    --ac-text: rgba(255,255,255,0.92);
    --ac-muted: rgba(255,255,255,0.48);
}

.ac-hero,
.ac-panel,
.ac-fee-card {
    background: var(--ac-surface);
    border: 1px solid var(--ac-border);
    border-radius: 16px;
}

.ac-hero {
    position: relative;
    overflow: hidden;
    padding: 24px;
    margin-bottom: 24px;
}

.ac-hero-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
}

.ac-hero-avatar {
    width: 56px;
    height: 56px;
    border-radius: 16px;
    flex-shrink: 0;
    background: rgba(124,58,237,0.08);
    border: 1px solid rgba(124,58,237,0.15);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.45rem;
    color: var(--ac-primary);
}

.ac-hero-name {
    font-size: 1.15rem;
    font-weight: 700;
    color: #fff;
    margin-bottom: 4px;
}

.ac-hero-meta {
    font-size: 0.78rem;
    color: var(--ac-muted);
    line-height: 1.5;
}

.ac-hero-badge,
.ac-rule-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.68rem;
    font-weight: 800;
    letter-spacing: 0.05em;
    padding: 5px 10px;
    border-radius: 999px;
    text-transform: uppercase;
}

.ac-hero-badge {
    margin-top: 10px;
    background: rgba(124,58,237,0.12);
    color: #c4b5fd;
    border: 1px solid rgba(124,58,237,0.22);
}

.ac-rule-badge.default {
    background: rgba(148,163,184,0.12);
    color: #cbd5e1;
    border: 1px solid rgba(148,163,184,0.18);
}

.ac-rule-badge.negotiated {
    background: rgba(16,185,129,0.12);
    color: #86efac;
    border: 1px solid rgba(16,185,129,0.2);
}

.ac-rule-badge.fallback {
    background: rgba(245,158,11,0.12);
    color: #fbbf24;
    border: 1px solid rgba(245,158,11,0.2);
}

.ac-status-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-top: 24px;
}

.ac-status-item {
    background: rgba(255,255,255,0.02);
    border: 1px solid rgba(255,255,255,0.05);
    border-radius: 12px;
    padding: 16px;
}

.ac-status-label {
    font-size: 0.69rem;
    color: rgba(255,255,255,0.38);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 6px;
}

.ac-status-val {
    font-size: 0.85rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 6px;
    color: var(--ac-text);
}

.ac-status-val.ok { color: #86efac; }
.ac-status-val.warn { color: #fbbf24; }
.ac-status-val.muted { color: var(--ac-muted); }

.ac-section {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 0.7rem;
    font-weight: 800;
    color: rgba(255,255,255,0.45);
    text-transform: uppercase;
    letter-spacing: 0.09em;
    margin: 28px 0 16px;
}

.ac-section::after {
    content: '';
    flex: 1;
    height: 1px;
    background: rgba(255,255,255,0.06);
}

.ac-section i { color: var(--ac-primary); }

.ac-explain {
    padding: 18px 20px;
    margin-bottom: 18px;
    color: rgba(255,255,255,0.72);
    line-height: 1.55;
}

.ac-fees {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 16px;
}

.ac-fee-card {
    padding: 20px;
    position: relative;
    overflow: hidden;
    transition: border-color 0.2s, transform 0.2s;
}

.ac-fee-card:hover {
    border-color: var(--ac-border-hover);
    transform: translateY(-2px);
}

.ac-fee-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 2px;
    background: var(--method-color);
    opacity: 0.55;
}

.ac-fee-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 18px;
}

.ac-fee-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: color-mix(in srgb, var(--method-color) 18%, transparent);
    color: var(--method-color);
}

.ac-fee-title {
    color: #fff;
    font-weight: 800;
    margin: 0;
}

.ac-fee-main {
    font-size: 1.25rem;
    font-weight: 900;
    color: #fff;
    margin-bottom: 14px;
}

.ac-fee-list {
    display: grid;
    gap: 10px;
}

.ac-fee-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding-top: 10px;
    border-top: 1px solid rgba(255,255,255,0.06);
    color: var(--ac-muted);
    font-size: 0.78rem;
}

.ac-fee-row strong {
    color: rgba(255,255,255,0.86);
    font-weight: 700;
    text-align: right;
}

.ac-fallback-note {
    margin-top: 14px;
    color: #fbbf24;
    font-size: 0.74rem;
    line-height: 1.45;
}

.ac-simulator-grid {
    display: grid;
    grid-template-columns: 320px 1fr;
    gap: 16px;
}

.ac-panel {
    padding: 20px;
}

.ac-field label {
    display: block;
    color: var(--ac-muted);
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 8px;
}

.ac-input,
.ac-select {
    width: 100%;
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.09);
    border-radius: 12px;
    color: #fff;
    padding: 12px 14px;
    outline: none;
}

.ac-select option {
    background: #111827;
    color: #fff;
}

.ac-sim-result {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 14px;
    height: 100%;
}

.ac-sim-card {
    background: var(--ac-surface-2);
    border: 1px solid rgba(255,255,255,0.06);
    border-radius: 14px;
    padding: 18px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.ac-sim-label {
    color: var(--ac-muted);
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    margin-bottom: 8px;
}

.ac-sim-value {
    color: #fff;
    font-size: 1.35rem;
    font-weight: 900;
}

@media (max-width: 1200px) {
    .ac-fees { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .ac-simulator-grid { grid-template-columns: 1fr; }
}

@media (max-width: 768px) {
    .ac-hero {
        padding: 14px !important;
        margin-bottom: 14px !important;
        border-radius: 10px !important;
    }

    .ac-hero-top > div {
        gap: 10px !important;
        min-width: 0 !important;
    }

    .ac-hero-avatar {
        width: 40px !important;
        height: 40px !important;
        border-radius: 10px !important;
        font-size: 1rem !important;
    }

    .ac-hero-name {
        font-size: .98rem !important;
        line-height: 1.25 !important;
        overflow-wrap: anywhere !important;
    }

    .ac-hero-meta {
        font-size: .7rem !important;
        line-height: 1.35 !important;
    }

    .ac-hero-badge,
    .ac-rule-badge {
        font-size: .61rem !important;
        padding: 4px 8px !important;
    }

    .ac-status-row {
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        gap: 8px !important;
        margin-top: 14px !important;
    }

    .ac-status-item {
        padding: 10px !important;
        border-radius: 9px !important;
    }

    .ac-status-label {
        font-size: .61rem !important;
        margin-bottom: 4px !important;
    }

    .ac-status-val {
        font-size: .74rem !important;
        line-height: 1.25 !important;
        overflow-wrap: anywhere !important;
    }

    .ac-section {
        margin: 16px 0 8px !important;
        font-size: .64rem !important;
        letter-spacing: .06em !important;
    }

    .ac-panel {
        padding: 12px !important;
        border-radius: 10px !important;
    }

    .ac-explain {
        margin-bottom: 10px !important;
        padding: 12px !important;
        font-size: .78rem !important;
        line-height: 1.45 !important;
    }

    .ac-fees,
    .ac-simulator-grid,
    .ac-sim-result {
        grid-template-columns: 1fr !important;
        gap: 10px !important;
    }

    .ac-fee-card {
        padding: 12px !important;
        border-radius: 10px !important;
    }

    .ac-fee-head {
        align-items: center !important;
        margin-bottom: 8px !important;
    }

    .ac-fee-icon {
        width: 30px !important;
        height: 30px !important;
        border-radius: 8px !important;
        font-size: .82rem !important;
    }

    .ac-fee-title {
        font-size: .9rem !important;
    }

    .ac-fee-main {
        font-size: .95rem !important;
        margin-bottom: 8px !important;
    }

    .ac-fee-list {
        gap: 6px !important;
    }

    .ac-fee-row {
        min-height: 28px !important;
        padding-top: 6px !important;
        font-size: .7rem !important;
    }

    .ac-fallback-note {
        margin-top: 8px !important;
        font-size: .68rem !important;
    }

    .ac-simulator-grid {
        gap: 8px !important;
    }

    .ac-sim-card {
        padding: 10px 12px !important;
        border-radius: 10px !important;
    }

    .ac-sim-label {
        font-size: .62rem !important;
        margin-bottom: 5px !important;
    }

    .ac-sim-value {
        font-size: 1rem !important;
    }

    .ac-input,
    .ac-select {
        height: 38px !important;
        padding: 0 12px !important;
        border-radius: 8px !important;
        font-size: .84rem !important;
    }

    .ac-field label {
        font-size: .66rem !important;
        margin-bottom: 6px !important;
    }
}
</style>

@php
    $accountStatus = $user->status instanceof \App\Enums\UserStatus ? $user->status : null;
    $kycValue = $user->kyc_status instanceof \App\Enums\KycStatus ? $user->kyc_status->value : $user->kyc_status;
    $feeJson = $appliedFees->mapWithKeys(fn ($fee) => [$fee['method'] => $fee])->toArray();
@endphp

<div class="ac-hero">
    <div class="ac-hero-top">
        <div style="display:flex;align-items:flex-start;gap:14px;">
            <div class="ac-hero-avatar"><i class="fas fa-building-columns"></i></div>
            <div>
                <div class="ac-hero-name">{{ $user->name ?? $user->username ?? $user->email }}</div>
                <div class="ac-hero-meta">
                    ID #{{ str_pad($user->id, 6, '0', STR_PAD_LEFT) }}
                    &nbsp;·&nbsp;
                    Membro desde {{ $user->created_at->format('m/Y') }}
                </div>
                <div class="ac-hero-badge"><i class="fas fa-receipt"></i> Taxas transacionais</div>
            </div>
        </div>
    </div>

    <div class="ac-status-row">
        <div class="ac-status-item">
            <div class="ac-status-label">Conta</div>
            @if($accountStatus === \App\Enums\UserStatus::ACTIVE)
                <div class="ac-status-val ok"><i class="fas fa-circle" style="font-size:0.5rem;"></i> Ativa</div>
            @else
                <div class="ac-status-val warn"><i class="fas fa-exclamation-circle"></i> Em análise</div>
            @endif
        </div>

        <div class="ac-status-item">
            <div class="ac-status-label">KYC</div>
            @if(in_array($kycValue, ['approved', 'verified', 1], true))
                <div class="ac-status-val ok"><i class="fas fa-shield-alt"></i> Aprovado</div>
            @elseif(in_array($kycValue, ['pending', 0], true))
                <div class="ac-status-val warn"><i class="fas fa-clock"></i> Em análise</div>
            @else
                <div class="ac-status-val muted"><i class="fas fa-id-card"></i> Pendente</div>
            @endif
        </div>

        <div class="ac-status-item">
            <div class="ac-status-label">Modelo comercial</div>
            <div class="ac-status-val ok"><i class="fas fa-percentage"></i> Transacional</div>
        </div>

        <div class="ac-status-item">
            <div class="ac-status-label">2FA</div>
            @if($user->two_factor_enabled ?? false)
                <div class="ac-status-val ok"><i class="fas fa-shield-alt"></i> Ativo</div>
            @else
                <div class="ac-status-val muted"><i class="fas fa-shield-alt"></i> Inativo</div>
            @endif
        </div>
    </div>
</div>

<div class="ac-section"><i class="fas fa-percentage"></i> Taxas aplicadas</div>

<div class="ac-panel ac-explain">
    Essas são as taxas atualmente aplicadas às suas transações na OriginPay. Caso tenha uma taxa negociada,
    ela substitui a taxa padrão da plataforma.
</div>

<div class="ac-fees">
    @foreach($appliedFees as $fee)
        @php
            $badgeClass = $fee['source'] === 'merchant' ? 'negotiated' : ($fee['is_fallback'] ? 'fallback' : 'default');
        @endphp
        <div class="ac-fee-card" style="--method-color: {{ $fee['color'] }};">
            <div class="ac-fee-head">
                <div>
                    <div class="ac-fee-icon"><i class="{{ $fee['icon'] }}"></i></div>
                    <p class="ac-fee-title">{{ $fee['label'] }}</p>
                </div>
                <span class="ac-rule-badge {{ $badgeClass }}">{{ $fee['source_label'] }}</span>
            </div>

            <div class="ac-fee-main">
                {{ number_format($fee['percentage_fee'], 2, ',', '.') }}% + R$ {{ number_format($fee['fixed_fee'], 2, ',', '.') }}
            </div>

            <div class="ac-fee-list">
                <div class="ac-fee-row">
                    <span>Taxa fixa</span>
                    <strong>R$ {{ number_format($fee['fixed_fee'], 2, ',', '.') }}</strong>
                </div>
                <div class="ac-fee-row">
                    <span>Taxa percentual</span>
                    <strong>{{ number_format($fee['percentage_fee'], 2, ',', '.') }}%</strong>
                </div>
                @if($fee['minimum_fee'] !== null && (float) $fee['minimum_fee'] > 0)
                    <div class="ac-fee-row">
                        <span>Taxa mínima</span>
                        <strong>R$ {{ number_format($fee['minimum_fee'], 2, ',', '.') }}</strong>
                    </div>
                @endif
                @if($fee['maximum_fee'] !== null)
                    <div class="ac-fee-row">
                        <span>Taxa máxima</span>
                        <strong>R$ {{ number_format($fee['maximum_fee'], 2, ',', '.') }}</strong>
                    </div>
                @endif
                <div class="ac-fee-row">
                    <span>Liquidação</span>
                    <strong>{{ $fee['settlement_delay_days'] }} dia(s)</strong>
                </div>
                @if((float) $fee['reserve_percentage'] > 0)
                    <div class="ac-fee-row">
                        <span>Reserva</span>
                        <strong>{{ number_format($fee['reserve_percentage'], 2, ',', '.') }}%</strong>
                    </div>
                @endif
            </div>

            @if($fee['is_fallback'])
                <div class="ac-fallback-note">
                    Regra padrão temporária aplicada. Em caso de dúvida, fale com o suporte.
                </div>
            @endif
        </div>
    @endforeach
</div>

<div class="ac-section"><i class="fas fa-calculator"></i> Simulador somente leitura</div>

<div class="ac-simulator-grid">
    <div class="ac-panel">
        <div class="ac-field" style="margin-bottom:16px;">
            <label for="feeSimulatorAmount">Valor da transação</label>
            <input id="feeSimulatorAmount" class="ac-input" type="number" min="0" step="0.01" value="100.00">
        </div>
        <div class="ac-field">
            <label for="feeSimulatorMethod">Método</label>
            <select id="feeSimulatorMethod" class="ac-select">
                @foreach($appliedFees as $fee)
                    <option value="{{ $fee['method'] }}">{{ $fee['label'] }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="ac-sim-result">
        <div class="ac-sim-card">
            <div class="ac-sim-label">Valor bruto</div>
            <div class="ac-sim-value" id="simGross">R$ 100,00</div>
        </div>
        <div class="ac-sim-card">
            <div class="ac-sim-label">Taxa OriginPay</div>
            <div class="ac-sim-value" id="simFee">R$ 0,00</div>
        </div>
        <div class="ac-sim-card">
            <div class="ac-sim-label">Líquido estimado</div>
            <div class="ac-sim-value" id="simNet">R$ 0,00</div>
        </div>
    </div>
</div>

<script>
    (() => {
        const fees = @json($feeJson);
        const amountInput = document.getElementById('feeSimulatorAmount');
        const methodInput = document.getElementById('feeSimulatorMethod');
        const grossEl = document.getElementById('simGross');
        const feeEl = document.getElementById('simFee');
        const netEl = document.getElementById('simNet');

        const money = (value) => Number(value || 0).toLocaleString('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        });

        const calculate = () => {
            const amount = Math.max(0, Number(amountInput.value || 0));
            const fee = fees[methodInput.value] || {};
            let platformFee = Number(fee.fixed_fee || 0) + (amount * (Number(fee.percentage_fee || 0) / 100));

            if (fee.minimum_fee !== null && fee.minimum_fee !== undefined) {
                platformFee = Math.max(platformFee, Number(fee.minimum_fee));
            }

            if (fee.maximum_fee !== null && fee.maximum_fee !== undefined) {
                platformFee = Math.min(platformFee, Number(fee.maximum_fee));
            }

            platformFee = Math.min(amount, Math.round(platformFee * 100) / 100);
            const net = Math.max(0, Math.round((amount - platformFee) * 100) / 100);

            grossEl.textContent = money(amount);
            feeEl.textContent = money(platformFee);
            netEl.textContent = money(net);
        };

        amountInput.addEventListener('input', calculate);
        methodInput.addEventListener('change', calculate);
        calculate();
    })();
</script>

@endsection
