@extends('frontend.layouts.user-v2')
@section('title', 'Detalhe da Cobrança')

@section('content')

<style>
:root {
    --sh-teal:   #00D4AA;
    --sh-purple: #8B5CF6;
    --sh-amber:  #F59E0B;
    --sh-red:    #EF4444;
    --sh-surf:   rgba(255,255,255,0.025);
    --sh-bord:   rgba(255,255,255,0.07);
}

/* ── Page Header ─────────────── */
.sh-back {
    display: inline-flex; align-items: center; gap: 7px;
    font-size: 0.8rem; color: rgba(255,255,255,0.4);
    text-decoration: none; margin-bottom: 20px;
    transition: color 0.2s;
}
.sh-back:hover { color: var(--sh-teal); }

/* ── Layout ──────────────────── */
.sh-layout { display: grid; grid-template-columns: 1fr 340px; gap: 20px; align-items: start; }
@media(max-width:900px) { .sh-layout { grid-template-columns: 1fr; } }

/* ── Cards ───────────────────── */
.sh-card {
    background: var(--sh-surf);
    border: 1px solid var(--sh-bord);
    border-radius: 14px;
    overflow: hidden;
    margin-bottom: 16px;
}
.sh-card:last-child { margin-bottom: 0; }
.sh-card-header {
    padding: 16px 20px;
    border-bottom: 1px solid rgba(255,255,255,0.06);
    display: flex; align-items: center; justify-content: space-between;
}
.sh-v2-card-header {
    font-size: 0.78rem; font-weight: 700; color: rgba(255,255,255,0.45);
    text-transform: uppercase; letter-spacing: 0.08em;
    display: flex; align-items: center; gap: 8px;
}
.sh-v2-card-header i { color: var(--sh-teal); font-size: 0.72rem; }
.sh-card-body { padding: 20px; }

/* ── Amount Hero ─────────────── */
.sh-amount-hero {
    background: linear-gradient(135deg, #0d1f1c 0%, #0a0b10 70%, #140f1f 100%);
    border: 1px solid var(--sh-bord);
    border-radius: 14px;
    padding: 24px 24px 20px;
    position: relative;
    overflow: hidden;
    margin-bottom: 16px;
}
.sh-amount-hero::before {
    content: '';
    position: absolute; top: -50px; right: -50px;
    width: 160px; height: 160px; border-radius: 50%;
    background: radial-gradient(circle, rgba(0,212,170,0.1) 0%, transparent 70%);
}
.sh-status-row { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
.sh-status-badge {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 5px 12px; border-radius: 50px; font-size: 0.75rem; font-weight: 700;
}
.sh-status-badge.paid     { background: rgba(0,212,170,0.12);  color: var(--sh-teal);   border: 1px solid rgba(0,212,170,0.2); }
.sh-status-badge.waiting  { background: rgba(245,158,11,0.12); color: var(--sh-amber);  border: 1px solid rgba(245,158,11,0.2); }
.sh-status-badge.expired  { background: rgba(239,68,68,0.12);  color: var(--sh-red);    border: 1px solid rgba(239,68,68,0.2); }
.sh-status-badge.cancelled{ background: rgba(239,68,68,0.12);  color: var(--sh-red);    border: 1px solid rgba(239,68,68,0.2); }
.sh-status-badge.refunded { background: rgba(99,102,241,0.12); color: #6366f1;          border: 1px solid rgba(99,102,241,0.2); }
.sh-status-badge.pending  { background: rgba(139,92,246,0.12); color: var(--sh-purple); border: 1px solid rgba(139,92,246,0.2); }

.sh-method-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 10px; border-radius: 50px; font-size: 0.69rem; font-weight: 700;
}
.sh-method-badge.pix  { background: rgba(0,212,170,0.1); color: var(--sh-teal); }
.sh-method-badge.card { background: rgba(99,102,241,0.1); color: #6366f1; }

.sh-amount-label { font-size: 0.72rem; color: rgba(255,255,255,0.38); text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 6px; }
.sh-amount-value { font-size: 2.2rem; font-weight: 800; color: #fff; letter-spacing: -0.02em; }
.sh-amount-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-top: 16px; }
.sh-amount-item {
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.06);
    border-radius: 8px; padding: 10px 14px;
}
.sh-amount-item-label { font-size: 0.68rem; color: rgba(255,255,255,0.35); margin-bottom: 4px; }
.sh-amount-item-val { font-size: 0.9rem; font-weight: 700; color: #fff; }
.sh-amount-item-val.teal { color: var(--sh-teal); }
.sh-amount-item-val.red  { color: var(--sh-red); }

/* ── Info Grid ───────────────── */
.sh-info-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; }
.sh-info-item {}
.sh-info-label { font-size: 0.69rem; color: rgba(255,255,255,0.35); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px; }
.sh-info-value { font-size: 0.85rem; font-weight: 600; color: rgba(255,255,255,0.8); word-break: break-all; font-family: monospace; }
.sh-info-value.normal { font-family: inherit; }
.sh-info-divider { grid-column: 1/-1; height: 1px; background: rgba(255,255,255,0.05); margin: 4px 0; }

/* ── PIX Box ─────────────────── */
.sh-pix-box {
    background: rgba(0,212,170,0.05);
    border: 1px solid rgba(0,212,170,0.15);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 16px;
}
.sh-pix-box-title {
    font-size: 0.78rem; font-weight: 700; color: var(--sh-teal);
    text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 14px;
    display: flex; align-items: center; gap: 7px;
}
.sh-copy-row {
    display: flex; align-items: center; gap: 8px;
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 8px; overflow: hidden;
    margin-bottom: 10px;
}
.sh-copy-input {
    flex: 1; background: transparent; border: none; outline: none;
    color: rgba(255,255,255,0.7); font-size: 0.78rem; padding: 10px 12px;
    font-family: monospace; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.sh-copy-btn {
    padding: 8px 14px; border: none; border-left: 1px solid rgba(255,255,255,0.08);
    background: rgba(255,255,255,0.04); color: rgba(255,255,255,0.5);
    font-size: 0.75rem; font-weight: 600; cursor: pointer; white-space: nowrap;
    transition: background 0.2s, color 0.2s; display: flex; align-items: center; gap: 5px;
}
.sh-copy-btn:hover { background: rgba(0,212,170,0.1); color: var(--sh-teal); }
.sh-open-link {
    display: inline-flex; align-items: center; gap: 7px; width: 100%;
    padding: 10px 14px; border-radius: 8px; font-size: 0.82rem; font-weight: 700;
    background: var(--sh-teal); color: #0a0b10; text-decoration: none;
    justify-content: center; transition: opacity 0.2s;
    margin-top: 4px;
}
.sh-open-link:hover { opacity: 0.88; color: #0a0b10; }

/* ── QR Code ─────────────────── */
.sh-qr-wrap {
    text-align: center; padding: 16px;
    background: rgba(255,255,255,0.03); border-radius: 10px;
    border: 1px solid rgba(255,255,255,0.07); margin-top: 12px;
}
.sh-qr-wrap img { max-width: 160px; border-radius: 8px; }
.sh-qr-label { font-size: 0.7rem; color: rgba(255,255,255,0.3); margin-top: 8px; }

/* ── Timeline ────────────────── */
.sh-timeline { }
.sh-timeline-item {
    display: flex; gap: 12px; padding-bottom: 16px;
    position: relative;
}
.sh-timeline-item::before {
    content: '';
    position: absolute; left: 11px; top: 24px; bottom: 0;
    width: 1px; background: rgba(255,255,255,0.06);
}
.sh-timeline-item:last-child::before { display: none; }
.sh-timeline-dot {
    width: 24px; height: 24px; border-radius: 50%; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.6rem; background: rgba(0,212,170,0.12); color: var(--sh-teal);
    border: 1px solid rgba(0,212,170,0.2); z-index: 1;
}
.sh-timeline-content { flex: 1; }
.sh-timeline-event { font-size: 0.82rem; font-weight: 600; color: rgba(255,255,255,0.8); }
.sh-timeline-date  { font-size: 0.7rem; color: rgba(255,255,255,0.32); margin-top: 2px; }
.sh-timeline-id    { font-size: 0.68rem; color: rgba(255,255,255,0.22); margin-top: 2px; font-family: monospace; }
.sh-timeline-empty { font-size: 0.82rem; color: rgba(255,255,255,0.28); text-align: center; padding: 20px 0; }
</style>

{{-- Back --}}
<a href="{{ route('user.charge.index') }}" class="sh-back">
    <i class="fas fa-arrow-left" style="font-size:0.75rem;"></i> Voltar para Cobranças
</a>

<div class="sh-layout">

    {{-- ── LEFT COLUMN ─────────────── --}}
    <div>

        {{-- Amount Hero --}}
        <div class="sh-amount-hero">
            <div class="sh-status-row">
                @switch($charge->status->value)
                    @case('paid')
                        <span class="sh-status-badge paid"><i class="fas fa-check-circle"></i> Pago</span>
                        @break
                    @case('waiting_payment')
                        <span class="sh-status-badge waiting"><i class="fas fa-clock"></i> Aguardando Pagamento</span>
                        @break
                    @case('expired')
                        <span class="sh-status-badge expired"><i class="fas fa-times-circle"></i> Expirado</span>
                        @break
                    @case('cancelled')
                        <span class="sh-status-badge cancelled"><i class="fas fa-ban"></i> Cancelado</span>
                        @break
                    @case('refunded')
                        <span class="sh-status-badge refunded"><i class="fas fa-undo"></i> Reembolsado</span>
                        @break
                    @default
                        <span class="sh-status-badge pending"><i class="fas fa-circle"></i> Pendente</span>
                @endswitch

                @if($charge->payment_method->value === 'pix')
                    <span class="sh-method-badge pix"><i class="fas fa-bolt"></i> PIX</span>
                @else
                    <span class="sh-method-badge card"><i class="fas fa-credit-card"></i> Cartão</span>
                @endif
            </div>
            <div class="sh-amount-label">Valor da Cobrança</div>
            <div class="sh-amount-value">{{ siteCurrency() }} {{ number_format($charge->amount, 2, ',', '.') }}</div>
            <div class="sh-amount-grid">
                <div class="sh-amount-item">
                    <div class="sh-amount-item-label">Líquido Creditado</div>
                    <div class="sh-amount-item-val teal">{{ siteCurrency() }} {{ number_format($charge->net_amount, 2, ',', '.') }}</div>
                </div>
                <div class="sh-amount-item">
                    <div class="sh-amount-item-label">Taxa da Plataforma</div>
                    <div class="sh-amount-item-val red">-{{ siteCurrency() }} {{ number_format($charge->platform_fee, 2, ',', '.') }}</div>
                </div>
            </div>
        </div>

        {{-- PIX Payment Block --}}
        @if(in_array($charge->status->value, ['waiting_payment', 'pending']) && $charge->payment_method->value === 'pix')
        <div class="sh-pix-box">
            <div class="sh-pix-box-title"><i class="fas fa-qrcode"></i> Dados para Pagamento via PIX</div>

            @if($charge->payment_link)
            <div style="font-size:0.68rem;color:rgba(255,255,255,0.35);margin-bottom:4px;text-transform:uppercase;letter-spacing:0.05em;">Link de Pagamento</div>
            <div class="sh-copy-row">
                <input type="text" class="sh-copy-input" value="{{ $charge->payment_link }}" id="ch-link" readonly>
                <button type="button" class="sh-copy-btn" onclick="chCopy('ch-link', this)"><i class="fas fa-copy"></i> Copiar</button>
            </div>
            @endif

            @if($charge->pix_copy_paste)
            <div style="font-size:0.68rem;color:rgba(255,255,255,0.35);margin-bottom:4px;margin-top:10px;text-transform:uppercase;letter-spacing:0.05em;">PIX Copia e Cola</div>
            <div class="sh-copy-row">
                <input type="text" class="sh-copy-input" value="{{ $charge->pix_copy_paste }}" id="ch-pix" readonly>
                <button type="button" class="sh-copy-btn" onclick="chCopy('ch-pix', this)"><i class="fas fa-copy"></i> Copiar</button>
            </div>
            @endif

            @if($charge->payment_link)
            <a href="{{ $charge->payment_link }}" target="_blank" class="sh-open-link">
                <i class="fas fa-external-link-alt" style="font-size:0.8rem;"></i> Abrir Link de Pagamento
            </a>
            @endif

            @if($charge->qr_code)
            <div class="sh-qr-wrap">
                <img src="{{ $charge->qr_code }}" alt="QR Code PIX">
                <div class="sh-qr-label">Escaneie com o app do seu banco</div>
            </div>
            @endif
        </div>
        @endif

        {{-- Identificadores --}}
        <div class="sh-card">
            <div class="sh-card-header">
                <div class="sh-v2-card-header"><i class="fas fa-fingerprint"></i> Identificadores</div>
            </div>
            <div class="sh-card-body">
                <div class="sh-info-grid">
                    <div class="sh-info-item">
                        <div class="sh-info-label">UUID (Sistema)</div>
                        <div class="sh-info-value">{{ $charge->uuid }}</div>
                    </div>
                    <div class="sh-info-item">
                        <div class="sh-info-label">ID no Gateway</div>
                        <div class="sh-info-value">{{ $charge->gateway_charge_id ?? '—' }}</div>
                    </div>
                    <div class="sh-info-divider"></div>
                    <div class="sh-info-item">
                        <div class="sh-info-label">Criado em</div>
                        <div class="sh-info-value normal">{{ $charge->created_at->format('d/m/Y H:i:s') }}</div>
                    </div>
                    <div class="sh-info-item">
                        <div class="sh-info-label">Expira em</div>
                        <div class="sh-info-value normal">{{ $charge->expires_at ? $charge->expires_at->format('d/m/Y H:i:s') : 'N/A' }}</div>
                    </div>
                    <div class="sh-info-item">
                        <div class="sh-info-label">Adquirente</div>
                        <div class="sh-info-value normal">{{ strtoupper($charge->gateway_id ?? '—') }}</div>
                    </div>
                    @if($charge->description)
                    <div class="sh-info-item">
                        <div class="sh-info-label">Descrição</div>
                        <div class="sh-info-value normal">{{ $charge->description }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Cliente --}}
        @if($charge->customer_name || $charge->customer_email || $charge->customer_document)
        <div class="sh-card">
            <div class="sh-card-header">
                <div class="sh-v2-card-header"><i class="fas fa-user"></i> Dados do Pagador</div>
            </div>
            <div class="sh-card-body">
                <div class="sh-info-grid">
                    @if($charge->customer_name)
                    <div class="sh-info-item">
                        <div class="sh-info-label">Nome</div>
                        <div class="sh-info-value normal">{{ $charge->customer_name }}</div>
                    </div>
                    @endif
                    @if($charge->customer_email)
                    <div class="sh-info-item">
                        <div class="sh-info-label">E-mail</div>
                        <div class="sh-info-value normal">{{ $charge->customer_email }}</div>
                    </div>
                    @endif
                    @if($charge->customer_document)
                    <div class="sh-info-item">
                        <div class="sh-info-label">Documento</div>
                        <div class="sh-info-value">{{ $charge->customer_document }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endif

    </div>

    {{-- ── RIGHT COLUMN ─────────────── --}}
    <div>
        <div class="sh-card">
            <div class="sh-card-header">
                <div class="sh-v2-card-header"><i class="fas fa-stream"></i> Histórico de Eventos</div>
            </div>
            <div class="sh-card-body">
                @if($charge->events && $charge->events->count())
                    <div class="sh-timeline">
                        @foreach($charge->events as $event)
                        <div class="sh-timeline-item">
                            <div class="sh-timeline-dot"><i class="fas fa-bolt"></i></div>
                            <div class="sh-timeline-content">
                                <div class="sh-timeline-event">{{ $event->event }}</div>
                                <div class="sh-timeline-date">{{ $event->processed_at->format('d/m/Y H:i:s') }}</div>
                                @if($event->gateway_event_id)
                                    <div class="sh-timeline-id">{{ $event->gateway_event_id }}</div>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="sh-timeline-empty">
                        <i class="fas fa-satellite-dish" style="display:block;font-size:1.8rem;margin-bottom:10px;opacity:0.3;"></i>
                        Nenhum evento registrado ainda.<br>
                        <span style="font-size:0.72rem;color:rgba(255,255,255,0.2);">Aguardando confirmação via Webhook.</span>
                    </div>
                @endif
            </div>
        </div>
    </div>

</div>

<script>
function chCopy(id, btn) {
    const el = document.getElementById(id);
    el.select();
    el.setSelectionRange(0, 99999);
    try {
        navigator.clipboard.writeText(el.value).catch(() => document.execCommand('copy'));
    } catch(e) {
        document.execCommand('copy');
    }
    const orig = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-check"></i> Copiado!';
    btn.style.color = '#00D4AA';
    setTimeout(() => { btn.innerHTML = orig; btn.style.color = ''; }, 2000);
}
</script>

@endsection
