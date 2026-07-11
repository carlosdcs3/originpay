@extends('frontend.layouts.user-v2')
@section('title', __('Minha Conta'))

@section('user_setting_content')

<style>
/* ── Design Tokens ─────────────────────────────── */
:root {
    --ac-teal:    #7c3aed;
    --ac-purple:  #8B5CF6;
    --ac-amber:   #F59E0B;
    --ac-red:     #EF4444;
    --ac-surface: rgba(255,255,255,0.03);
    --ac-border:  rgba(255,255,255,0.07);
    --ac-border-h:rgba(124,58,237,0.25);
}

/* ── Hero Header ───────────────────────────────── */
.ac-hero {
    position: relative;
    overflow: hidden;
    background: linear-gradient(135deg, #0d1f1c 0%, #0a0b10 60%, #140f1f 100%);
    border: 1px solid var(--ac-border);
    border-radius: 16px;
    padding: 28px 28px 24px;
    margin-bottom: 28px;
}
.ac-hero::before {
    content: '';
    position: absolute;
    top: -70px; right: -70px;
    width: 230px; height: 230px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(124,58,237,0.1) 0%, transparent 70%);
    pointer-events: none;
}
.ac-hero-top {
    display: flex; align-items: flex-start;
    justify-content: space-between; gap: 16px;
}
.ac-hero-avatar {
    width: 54px; height: 54px; border-radius: 14px; flex-shrink: 0;
    background: linear-gradient(135deg, rgba(124,58,237,0.2), rgba(124,58,237,0.04));
    border: 1.5px solid rgba(124,58,237,0.22);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.5rem; color: var(--ac-teal);
}
.ac-hero-name { font-size: 1.15rem; font-weight: 700; color: #fff; margin-bottom: 3px; }
.ac-hero-meta { font-size: 0.77rem; color: rgba(255,255,255,0.4); line-height: 1.5; }
.ac-hero-badge {
    display: inline-flex; align-items: center; gap: 5px;
    margin-top: 8px;
    font-size: 0.67rem; font-weight: 700; letter-spacing: 0.06em;
    padding: 4px 10px; border-radius: 50px; text-transform: uppercase;
    background: rgba(124,58,237,0.1); color: var(--ac-teal);
    border: 1px solid rgba(124,58,237,0.18);
}
.ac-hero-icon-bg {
    opacity: 0.04; font-size: 5rem; line-height: 1; color: #fff;
    pointer-events: none;
}

/* ── Status Row ────────────────────────────────── */
.ac-status-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
    margin-top: 20px;
}
.ac-status-item {
    background: rgba(255,255,255,0.025);
    border: 1px solid rgba(255,255,255,0.07);
    border-radius: 10px;
    padding: 12px 14px;
    transition: border-color 0.2s;
}
.ac-status-item:hover { border-color: rgba(124,58,237,0.2); }
.ac-status-label { font-size: 0.69rem; color: rgba(255,255,255,0.38); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px; }
.ac-status-val { font-size: 0.85rem; font-weight: 600; display: flex; align-items: center; gap: 6px; }
.ac-status-val.ok    { color: var(--ac-teal); }
.ac-status-val.warn  { color: var(--ac-amber); }
.ac-status-val.err   { color: var(--ac-red); }
.ac-status-val.muted { color: rgba(255,255,255,0.4); }

/* ── Section Divider ───────────────────────────── */
.ac-section {
    display: flex; align-items: center; gap: 10px;
    font-size: 0.7rem; font-weight: 700; color: rgba(255,255,255,0.35);
    text-transform: uppercase; letter-spacing: 0.09em;
    margin: 32px 0 14px;
}
.ac-section::after {
    content: ''; flex: 1; height: 1px;
    background: rgba(255,255,255,0.06);
}
.ac-section i { color: var(--ac-teal); font-size: 0.7rem; }

/* ── Fee Cards ─────────────────────────────────── */
.ac-fees { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }
.ac-fee-card {
    background: var(--ac-surface);
    border: 1px solid var(--ac-border);
    border-radius: 14px;
    padding: 20px;
    position: relative;
    overflow: hidden;
    transition: border-color 0.2s, transform 0.2s;
}
.ac-fee-card:hover { border-color: var(--ac-border-h); transform: translateY(-2px); }
.ac-fee-card::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0;
    height: 2px; background: var(--card-color, #7c3aed); opacity: 0.4;
}
.ac-fee-icon {
    width: 38px; height: 38px; border-radius: 10px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.95rem; margin-bottom: 14px;
    background: var(--icon-bg, rgba(124,58,237,0.1));
    color: var(--card-color, var(--ac-teal));
}
.ac-fee-label { font-size: 0.7rem; color: rgba(255,255,255,0.38); margin-bottom: 7px; text-transform: uppercase; letter-spacing: 0.05em; }
.ac-fee-main { font-size: 1.35rem; font-weight: 800; color: #fff; line-height: 1.1; }
.ac-fee-main.dim { font-size: 0.95rem; font-weight: 500; color: rgba(255,255,255,0.38); }
.ac-fee-sub { font-size: 0.7rem; color: rgba(255,255,255,0.32); margin-top: 6px; }
.ac-fee-divider { border-top: 1px solid rgba(255,255,255,0.06); margin: 10px 0; }
.ac-fee-row2-label { font-size: 0.68rem; color: rgba(255,255,255,0.3); margin-bottom: 3px; }
.ac-fee-row2-val { font-size: 0.95rem; font-weight: 700; color: #fff; }

/* ── Volume Grid ───────────────────────────────── */
.ac-volume-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 14px; }
.ac-volume-card {
    background: var(--ac-surface);
    border: 1px solid var(--ac-border);
    border-radius: 14px;
    padding: 22px 24px;
    transition: border-color 0.2s;
}
.ac-volume-card:hover { border-color: var(--ac-border-h); }
.ac-vol-label { font-size: 0.7rem; color: rgba(255,255,255,0.38); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 9px; }
.ac-vol-number { font-size: 1.85rem; font-weight: 800; color: #fff; letter-spacing: -0.02em; }
.ac-vol-sub { font-size: 0.7rem; color: rgba(255,255,255,0.3); margin-top: 4px; }
.ac-progress { height: 4px; border-radius: 10px; background: rgba(255,255,255,0.05); margin-top: 16px; overflow: hidden; }
.ac-progress-bar {
    height: 100%; border-radius: 10px;
    background: linear-gradient(90deg, var(--ac-teal), #00b48e);
    transition: width 1.2s cubic-bezier(0.4, 0, 0.2, 1);
}

/* ── Features Grid ─────────────────────────────── */
.ac-feats { display: grid; grid-template-columns: repeat(auto-fill, minmax(182px, 1fr)); gap: 10px; }
.ac-feat {
    display: flex; align-items: center; gap: 10px;
    padding: 11px 13px;
    background: var(--ac-surface);
    border: 1px solid var(--ac-border);
    border-radius: 10px;
    transition: border-color 0.2s;
}
.ac-feat:hover { border-color: rgba(124,58,237,0.2); }
.ac-feat.dashed { border-style: dashed; }
.ac-feat-icon {
    width: 30px; height: 30px; border-radius: 8px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center; font-size: 0.82rem;
}
.ac-feat-icon.enabled { background: rgba(124,58,237,0.1); color: var(--ac-teal); }
.ac-feat-icon.soon    { background: rgba(139,92,246,0.1); color: var(--ac-purple); }
.ac-feat-icon.ent     { background: rgba(245,158,11,0.1); color: var(--ac-amber); }
.ac-feat-name { font-size: 0.83rem; font-weight: 500; color: rgba(255,255,255,0.8); flex: 1; }
.ac-feat-tag {
    font-size: 0.6rem; font-weight: 700; padding: 2px 7px;
    border-radius: 50px; letter-spacing: 0.04em; white-space: nowrap;
}
.ac-feat-tag.soon { background: rgba(139,92,246,0.14); color: var(--ac-purple); }
.ac-feat-tag.ent  { background: rgba(245,158,11,0.14); color: var(--ac-amber); }

/* ── CTA Banner ────────────────────────────────── */
.ac-cta {
    margin-top: 36px;
    background: linear-gradient(100deg, rgba(139,92,246,0.07) 0%, rgba(124,58,237,0.07) 100%);
    border: 1px solid rgba(139,92,246,0.16);
    border-radius: 14px;
    padding: 24px 28px;
    display: flex; align-items: center; justify-content: space-between; gap: 20px;
}
.ac-cta h5 { font-size: 0.98rem; font-weight: 700; color: #fff; margin: 0 0 5px; }
.ac-cta p  { font-size: 0.79rem; color: rgba(255,255,255,0.42); margin: 0; }
.ac-cta-btn {
    display: inline-flex; align-items: center; gap: 8px; white-space: nowrap;
    padding: 10px 20px; border-radius: 10px; font-size: 0.84rem; font-weight: 700;
    background: linear-gradient(135deg, #25d366, #128c7e);
    color: #fff; text-decoration: none;
    transition: opacity 0.2s, transform 0.15s;
}
.ac-cta-btn:hover { opacity: 0.88; transform: translateY(-1px); color: #fff; }

@media (max-width: 768px) {
    .ac-fees        { grid-template-columns: 1fr; }
    .ac-volume-grid { grid-template-columns: 1fr; }
    .ac-status-row  { grid-template-columns: repeat(2, 1fr); }
    .ac-cta         { flex-direction: column; align-items: flex-start; }
}
</style>

{{-- ── HERO ──────────────────────────────────────── --}}
<div class="ac-hero">
    <div class="ac-hero-top">
        <div style="display:flex;align-items:flex-start;gap:14px;">
            <div class="ac-hero-avatar"><i class="fas fa-building-columns"></i></div>
            <div>
                <div class="ac-hero-name">{{ $user->full_name ?? $user->username }}</div>
                <div class="ac-hero-meta">
                    ID #{{ str_pad($user->id, 6, '0', STR_PAD_LEFT) }}
                    &nbsp;·&nbsp;
                    Membro desde {{ $user->created_at->translatedFormat('M Y') }}
                </div>
                <div class="ac-hero-badge"><i class="fas fa-server" style="font-size:0.55rem;"></i> Gateway Padrão</div>
            </div>
        </div>
        <div class="ac-hero-icon-bg"><i class="fas fa-building-columns"></i></div>
    </div>

    <div class="ac-status-row">
        <div class="ac-status-item">
            <div class="ac-status-label">Conta</div>
            @if($user->status->value === 'active')
                <div class="ac-status-val ok"><i class="fas fa-circle" style="font-size:0.5rem;"></i> Ativa</div>
            @elseif($user->status->value === 'banned')
                <div class="ac-status-val err"><i class="fas fa-ban"></i> Suspensa</div>
            @else
                <div class="ac-status-val warn"><i class="fas fa-exclamation-circle"></i> Em análise</div>
            @endif
        </div>

        <div class="ac-status-item">
            <div class="ac-status-label">KYC</div>
            @php $kyc = $user->kycProfile; @endphp
            @if(!$kyc || $kyc->status->value === 'unverified')
                <div class="ac-status-val warn"><i class="fas fa-exclamation-triangle" style="font-size:0.75rem;"></i> Pendente</div>
            @elseif($kyc->status->value === 'verified')
                <div class="ac-status-val ok"><i class="fas fa-shield-alt" style="font-size:0.75rem;"></i> Aprovado</div>
            @elseif($kyc->status->value === 'pending')
                <div class="ac-status-val warn"><i class="fas fa-clock" style="font-size:0.75rem;"></i> Em análise</div>
            @else
                <div class="ac-status-val err"><i class="fas fa-times-circle" style="font-size:0.75rem;"></i> Rejeitado</div>
            @endif
        </div>

        <div class="ac-status-item">
            <div class="ac-status-label">Tipo</div>
            <div class="ac-status-val ok"><i class="fas fa-code-branch" style="font-size:0.7rem;"></i> Gateway</div>
        </div>

        <div class="ac-status-item">
            <div class="ac-status-label">2FA</div>
            @if($user->two_factor_enabled ?? false)
                <div class="ac-status-val ok"><i class="fas fa-shield-alt" style="font-size:0.7rem;"></i> Ativo</div>
            @else
                <div class="ac-status-val muted"><i class="fas fa-shield-alt" style="font-size:0.7rem;"></i> Inativo</div>
            @endif
        </div>
    </div>
</div>

{{-- ── TAXAS APLICADAS ──────────────────────────── --}}
<div class="ac-section"><i class="fas fa-percentage"></i> Taxas Aplicadas</div>
<div class="ac-fees">

    <div class="ac-fee-card" style="--card-color:#7c3aed;--icon-bg:rgba(124,58,237,0.1);">
        <div class="ac-fee-icon"><i class="fas fa-qrcode"></i></div>
        <div class="ac-fee-label">PIX — Cobrança</div>
        @if($globalFees)
            @php
                $limit = (float)($globalFees->small_transaction_limit ?? 0);
                $fixedSmall = (float)($globalFees->small_fixed_fee ?? 0.35);
                $pct = (float)$globalFees->standard_percentage_fee;
                $fixed = (float)$globalFees->standard_fixed_fee;
            @endphp
            @if($limit > 0)
                <div class="ac-fee-main" style="font-size:0.95rem;">Até R$ {{ number_format($limit, 2, ',', '.') }}</div>
                <div class="ac-fee-sub">R$ {{ number_format($fixedSmall, 2, ',', '.') }} por transação</div>
                <div class="ac-fee-divider"></div>
                <div class="ac-fee-row2-label">Acima do limite</div>
                <div class="ac-fee-row2-val">{{ $pct }}% + R$ {{ number_format($fixed, 2, ',', '.') }}</div>
            @else
                <div class="ac-fee-main">{{ $pct }}%</div>
                <div class="ac-fee-sub">+ R$ {{ number_format($fixed, 2, ',', '.') }} por transação</div>
            @endif
        @else
            <div class="ac-fee-main dim">Não configurado</div>
        @endif
    </div>

    <div class="ac-fee-card" style="--card-color:#6366f1;--icon-bg:rgba(99,102,241,0.1);">
        <div class="ac-fee-icon"><i class="fas fa-credit-card"></i></div>
        <div class="ac-fee-label">Cartão de Crédito</div>
        <div class="ac-fee-main dim">Em breve</div>
        <div class="ac-fee-sub">Aguardando integração</div>
    </div>

    <div class="ac-fee-card" style="--card-color:#F59E0B;--icon-bg:rgba(245,158,11,0.1);">
        <div class="ac-fee-icon"><i class="fas fa-arrow-up"></i></div>
        <div class="ac-fee-label">Saque via PIX</div>
        <div class="ac-fee-main">Grátis</div>
        <div class="ac-fee-sub">Sem custo para a conta titular</div>
    </div>
</div>

{{-- ── VOLUME ───────────────────────────────────── --}}
<div class="ac-section"><i class="fas fa-chart-bar"></i> Volume e Limites</div>
<div class="ac-volume-grid">
    <div class="ac-volume-card">
        <div class="ac-vol-label">Volume Processado — {{ now()->translatedFormat('F Y') }}</div>
        <div class="ac-vol-number">{{ siteCurrency() }} {{ number_format($monthlyVolume, 2, ',', '.') }}</div>
        <div class="ac-vol-sub">Soma das cobranças com status <em>Pago</em> no mês corrente</div>
        @php $pct = $monthlyVolume > 0 ? min(100, ($monthlyVolume / 50000) * 100) : 2; @endphp
        <div class="ac-progress">
            <div class="ac-progress-bar" style="width:{{ $pct }}%;"></div>
        </div>
    </div>
    <div class="ac-volume-card" style="display:flex;flex-direction:column;justify-content:center;align-items:center;text-align:center;gap:6px;">
        <i class="fas fa-infinity" style="font-size:2.4rem;color:var(--ac-teal);opacity:0.35;"></i>
        <div class="ac-vol-label" style="text-align:center;margin:0;">Limite Mensal</div>
        <div style="font-size:1.1rem;font-weight:700;color:#fff;">Ilimitado</div>
    </div>
</div>

{{-- ── RECURSOS DISPONÍVEIS ─────────────────────── --}}
<div class="ac-section"><i class="fas fa-cubes"></i> Recursos Disponíveis</div>
<div class="ac-feats">
    <div class="ac-feat">
        <div class="ac-feat-icon enabled"><i class="fas fa-qrcode"></i></div>
        <span class="ac-feat-name">Cobranças PIX</span>
    </div>
    <div class="ac-feat">
        <div class="ac-feat-icon enabled"><i class="fas fa-link"></i></div>
        <span class="ac-feat-name">Links de Pagamento</span>
    </div>
    <div class="ac-feat">
        <div class="ac-feat-icon enabled"><i class="fas fa-code"></i></div>
        <span class="ac-feat-name">API Restful</span>
    </div>
    <div class="ac-feat">
        <div class="ac-feat-icon enabled"><i class="fas fa-bolt"></i></div>
        <span class="ac-feat-name">Webhooks</span>
    </div>
    <div class="ac-feat dashed">
        <div class="ac-feat-icon soon"><i class="fas fa-clock"></i></div>
        <span class="ac-feat-name">Cartão de Crédito</span>
        <span class="ac-feat-tag soon">Em breve</span>
    </div>
    <div class="ac-feat dashed">
        <div class="ac-feat-icon soon"><i class="fas fa-window-maximize"></i></div>
        <span class="ac-feat-name">Checkout Transparente</span>
        <span class="ac-feat-tag soon">Em breve</span>
    </div>
    <div class="ac-feat dashed">
        <div class="ac-feat-icon ent"><i class="fas fa-code-branch"></i></div>
        <span class="ac-feat-name">Split de Pagamentos</span>
        <span class="ac-feat-tag ent">Enterprise</span>
    </div>
    <div class="ac-feat dashed">
        <div class="ac-feat-icon ent"><i class="fas fa-paintbrush"></i></div>
        <span class="ac-feat-name">White Label</span>
        <span class="ac-feat-tag ent">Enterprise</span>
    </div>
</div>

{{-- ── CTA ───────────────────────────────────────── --}}
<div class="ac-cta">
    <div>
        <h5>Precisa de taxas menores ou recursos Enterprise?</h5>
        <p>Nosso time comercial pode criar um plano personalizado baseado no seu volume de transações.</p>
    </div>
    <a href="https://wa.me/5511999999999" target="_blank" class="ac-cta-btn">
        <i class="fab fa-whatsapp"></i> Falar com Comercial
    </a>
</div>

@endsection
