@php 
    use App\Enums\TrxStatus; 
    use App\Enums\TrxType; 
@endphp
@extends('frontend.layouts.user-v2')
@section('title', 'Transferências')

@section('styles')
<style>
    /* Reset & Base */
    html, body.v2-dashboard { overflow:hidden!important; scrollbar-width:none!important; -ms-overflow-style:none!important; }
    html::-webkit-scrollbar, body.v2-dashboard::-webkit-scrollbar, .cmf-shell::-webkit-scrollbar, .cmf-shell *::-webkit-scrollbar { width:0!important; height:0!important; display:none!important; }
    body.v2-dashboard .v2-main, body.v2-dashboard .v2-content { overflow:hidden!important; }

    .cmf-shell { 
        display: flex; 
        flex-direction: column; 
        flex: 1; 
        min-height: 0; 
        gap: 8px; 
        padding-bottom: 8px;
    }

    /* Header & KPIs */
    .cmf-header-area { flex-shrink: 0; }
    .cmf-header-title { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 12px; }
    .cmf-title h1 { margin: 0 0 4px; font-size: 1.25rem; font-weight: 700; color: var(--ds-text-main); }
    .cmf-title p { margin: 0; font-size: 0.85rem; color: var(--ds-text-muted); }

    .cmf-kpis { 
        display: grid; 
        grid-template-columns: repeat(5, minmax(0, 1fr)); 
        gap: 12px; 
    }
    .cmf-kpi { 
        background: var(--ds-bg-card); 
        border: 1px solid var(--ds-border-light); 
        border-radius: 12px; 
        padding: 10px 12px; 
        display: flex; 
        flex-direction: column; 
        gap: 6px;
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    .cmf-kpi:hover { border-color: var(--ds-border-medium); }
    .cmf-kpi-top { display: flex; justify-content: space-between; align-items: center; }
    .cmf-kpi-icon { 
        width: 32px; height: 32px; border-radius: 8px; 
        display: flex; align-items: center; justify-content: center; font-size: 0.85rem; 
    }
    .cmf-kpi-label { font-size: 0.72rem; font-weight: 700; color: var(--ds-text-muted); text-transform: uppercase; letter-spacing: 0.05em; }
    .cmf-kpi-value { font-size: 1.15rem; font-weight: 800; color: var(--ds-text-main); }
    .cmf-kpi-foot { font-size: 0.7rem; color: var(--ds-text-muted); display: flex; justify-content: space-between; }
    
    /* 2 Columns Grid */
    .cmf-grid {
        display: grid;
        grid-template-columns: 38% 1fr;
        gap: 16px;
        flex: 1;
        min-height: 0;
    }
    @media (max-width: 1200px) {
        .cmf-grid { grid-template-columns: 1fr; overflow-y: auto; display: flex; flex-direction: column; }
    }

    /* Left Column: Actions */
    .cmf-col-left { display: flex; flex-direction: column; gap: 12px; padding-right: 8px; }
    
    .cmf-step-title { font-size: 0.9rem; font-weight: 700; color: var(--ds-text-main); margin: 0 0 6px; display: flex; align-items: center; gap: 8px; }
    .cmf-step-title span { width: 22px; height: 22px; border-radius: 50%; background: var(--ds-bg-accent); display: flex; align-items: center; justify-content: center; font-size: 0.7rem; color: var(--ds-text-primary); }

    .cmf-type-cards { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    .cmf-type-card { 
        background: var(--ds-bg-card); border: 1px solid var(--ds-border-light); 
        border-radius: 12px; padding: 10px 12px; cursor: pointer; position: relative; 
        transition: all 0.2s ease; overflow: hidden;
    }
    .cmf-type-card:hover { border-color: var(--ds-border-medium); background: rgba(255,255,255,0.01); }
    .cmf-type-card.active { 
        border-color: var(--ds-primary); 
        background: rgba(124, 58, 237, 0.03); 
        box-shadow: 0 0 15px rgba(124, 58, 237, 0.1); 
    }
    .cmf-type-card-icon { 
        width: 34px; height: 34px; border-radius: 10px; background: rgba(255,255,255,0.03); 
        display: flex; align-items: center; justify-content: center; font-size: 0.95rem; color: var(--ds-text-secondary); margin-bottom: 8px; 
    }
    .cmf-type-card.active .cmf-type-card-icon { background: var(--ds-primary); color: #fff; }
    .cmf-type-card h5 { margin: 0 0 4px; font-size: 0.85rem; font-weight: 700; color: var(--ds-text-main); }
    .cmf-type-card p { margin: 0; font-size: 0.75rem; color: var(--ds-text-muted); line-height: 1.4; }
    
    .cmf-radio { position: absolute; top: 16px; right: 16px; width: 16px; height: 16px; border-radius: 50%; border: 1.5px solid var(--ds-border-medium); display: flex; align-items: center; justify-content: center; }
    .cmf-type-card.active .cmf-radio { border-color: var(--ds-primary); }
    .cmf-radio-inner { width: 8px; height: 8px; border-radius: 50%; background: var(--ds-primary); opacity: 0; transform: scale(0); transition: all 0.2s ease; }
    .cmf-type-card.active .cmf-radio-inner { opacity: 1; transform: scale(1); }

    /* Form Container */
    .cmf-form-container { position: relative; }
    .cmf-form { 
        position: absolute; top: 0; left: 0; width: 100%; 
        opacity: 0; visibility: hidden; pointer-events: none; 
        transition: opacity 0.2s ease, visibility 0.2s ease;
        background: var(--ds-bg-card); border: 1px solid var(--ds-border-light); border-radius: 12px; padding: 14px 16px;
    }
    .cmf-form.active { opacity: 1; visibility: visible; pointer-events: auto; position: relative; }

    /* Form Elements */
    .cmf-input-group { position: relative; margin-bottom: 10px; }
    .cmf-label { display: block; font-size: 0.8rem; font-weight: 700; color: var(--ds-text-secondary); margin-bottom: 6px; }
    .cmf-input { 
        width: 100%; height: 38px; background: rgba(255,255,255,0.02); border: 1px solid var(--ds-border-medium); 
        border-radius: 8px; padding: 0 14px; color: var(--ds-text-main); font-size: 0.9rem; outline: none; transition: border-color 0.2s; 
    }
    .cmf-input:focus { border-color: var(--ds-primary); }
    .cmf-input-icon { position: absolute; left: 14px; top: 33px; color: var(--ds-text-muted); font-size: 0.9rem; }
    .cmf-input.with-icon { padding-left: 38px; }
    
    .cmf-btn-submit { 
        width: 100%; height: 40px; background: var(--ds-primary); color: #fff; border: none; border-radius: 8px; 
        font-size: 0.9rem; font-weight: 700; cursor: pointer; transition: background 0.2s; 
        display: flex; align-items: center; justify-content: center; gap: 8px; margin-top: 12px;
    }
    .cmf-btn-submit:hover { background: var(--ds-primary-hover); }

    /* Security Card */
    .cmf-security { 
        background: rgba(255,255,255,0.015); border: 1px dashed var(--ds-border-medium); border-radius: 10px; 
        padding: 10px 14px; display: flex; gap: 10px; align-items: center; margin-top: 0;
    }
    .cmf-security i { color: var(--ds-text-muted); font-size: 1.2rem; }
    .cmf-security p { margin: 0; font-size: 0.72rem; color: var(--ds-text-muted); line-height: 1.4; }

    /* Right Column: History */
    .cmf-col-right { 
        display: flex; flex-direction: column; background: var(--ds-bg-card); 
        border: 1px solid var(--ds-border-light); border-radius: 12px; overflow: hidden; 
    }

    .cmf-history-header { padding: 16px 20px; border-bottom: 1px solid var(--ds-border-light); }
    .cmf-history-title { font-size: 1rem; font-weight: 700; color: var(--ds-text-main); margin: 0 0 8px; }
    
    .cmf-filters { display: grid; grid-template-columns: 1fr 140px; gap: 10px; }
    .cmf-filters select[name="direction"] { display: none !important; }
    .cmf-filter-input { height: 36px; background: rgba(255,255,255,0.02); border: 1px solid var(--ds-border-medium); border-radius: 8px; padding: 0 12px; color: var(--ds-text-secondary); font-size: 0.8rem; outline: none; }
    .cmf-filter-input:focus { border-color: var(--ds-primary); }
    .cmf-filter-input option { background: #11151e; color: #e2e8f0; }

    .cmf-tabs { display: flex; padding: 0 20px; border-bottom: 1px solid var(--ds-border-light); gap: 12px; flex-shrink: 0; }
    .cmf-tab { 
        padding: 16px 0; color: var(--ds-text-muted); font-size: 0.8rem; font-weight: 700; text-decoration: none; 
        border-bottom: 3px solid transparent; margin-bottom: -1px; transition: color 0.2s; display: block;
    }
    .cmf-tab:hover { color: var(--ds-text-secondary); }
    .cmf-tab.active { color: var(--ds-primary); border-color: var(--ds-primary); }

    .cmf-table-wrap { flex: 1; overflow-y: auto; padding: 0 20px; }
    .cmf-table { width: 100%; border-collapse: collapse; }
    .cmf-table th { color: var(--ds-text-muted); font-size: 0.7rem; font-weight: 700; text-transform: uppercase; padding: 14px 0; border-bottom: 1px solid var(--ds-border-light); text-align: left; position: sticky; top: 0; background: var(--ds-bg-card); z-index: 2; }
    .cmf-table td { padding: 14px 0; border-bottom: 1px solid var(--ds-border-light); vertical-align: middle; }
    
    .cmf-trx-info { display: flex; align-items: center; gap: 12px; }
    .cmf-trx-icon { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; flex-shrink: 0; }
    .cmf-trx-main { font-size: 0.85rem; font-weight: 700; color: var(--ds-text-main); margin-bottom: 2px; }
    .cmf-trx-sub { font-size: 0.72rem; color: var(--ds-text-muted); }
    
    .cmf-amt { font-size: 0.85rem; font-weight: 700; }
    .cmf-amt.out { color: var(--ds-error); }
    .cmf-amt.in { color: var(--ds-success); }
    
    .cmf-badge { display: inline-flex; align-items: center; justify-content: center; padding: 4px 10px; border-radius: 999px; font-size: 0.7rem; font-weight: 800; }
    .cmf-badge.completed { color: #22c55e; background: rgba(34,197,94,0.12); }
    .cmf-badge.pending { color: #fbbf24; background: rgba(245,158,11,0.12); }
    .cmf-badge.failed, .cmf-badge.canceled { color: #ef4444; background: rgba(239,68,68,0.12); }

    .cmf-empty { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 60px 0; text-align: center; }
    .cmf-empty i { font-size: 2.5rem; color: rgba(255,255,255,0.05); margin-bottom: 12px; }
    .cmf-empty h6 { font-size: 1rem; font-weight: 700; color: var(--ds-text-main); margin: 0 0 8px; }
    .cmf-empty p { font-size: 0.8rem; color: var(--ds-text-muted); margin: 0; }

    .cmf-history-footer { padding: 14px 20px; border-top: 1px solid var(--ds-border-light); text-align: center; }
    .cmf-link { font-size: 0.8rem; font-weight: 700; color: var(--ds-primary); text-decoration: none; }
    .cmf-link:hover { text-decoration: underline; }
</style>

<!-- Transaction Password Modal -->
<div id="trxPasswordModal" class="cmf-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); backdrop-filter:blur(4px); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:var(--ds-bg-card); border:1px solid var(--ds-border-light); width:90%; max-width:400px; border-radius:12px; padding:24px; position:relative;">
        <button type="button" onclick="closeTrxModal()" style="position:absolute; top:16px; right:16px; background:none; border:none; color:var(--ds-text-muted); cursor:pointer;"><i class="fas fa-times"></i></button>
        
        <h4 style="margin:0 0 8px; font-size:1.1rem; font-weight:700; color:var(--ds-text-main);">Confirmação de Segurança</h4>
        <p style="margin:0 0 20px; font-size:0.85rem; color:var(--ds-text-muted);">Por favor, insira sua senha transacional para autorizar esta operação.</p>
        
        <div class="cmf-input-group">
            <label class="cmf-label">Senha Transacional</label>
            <input type="password" id="modal_trx_password" class="cmf-input" placeholder="Digite sua senha" maxlength="4" pattern="\d{4}" inputmode="numeric" autocomplete="off" style="padding-left:14px;">
        </div>
        
        <div style="display:flex; gap:12px; margin-top:24px;">
            <button type="button" onclick="closeTrxModal()" class="v2-btn-secondary" style="flex:1; justify-content:center; height:40px;">Cancelar</button>
            <button type="button" onclick="confirmTrxModal()" class="cmf-btn-submit" style="margin-top:0; flex:1; justify-content:center; height:40px;">Confirmar</button>
        </div>
    </div>
</div>
@endsection

@section('content')
@php
    $money = fn ($value) => 'R$ ' . number_format((float) $value, 2, ',', '.');
    $activeDirection = request('direction', 'all');
    $activeStatus = request('status', 'all');
    
    // Simplificando o controle de abas
    $tabs = [
        'all' => 'Todas',
        'sent' => 'Enviadas',
        'received' => 'Recebidas',
        'withdraw' => 'Saques',
        'failed' => 'Falhas',
    ];

    $statusLabels = [
        TrxStatus::PENDING->value => 'Pendente',
        TrxStatus::COMPLETED->value => 'Concluída',
        TrxStatus::FAILED->value => 'Falhou',
        TrxStatus::CANCELED->value => 'Cancelada',
    ];
@endphp

<div class="cmf-shell">
    
    <div class="cmf-header-area">
        <div class="cmf-header-title">
            <div class="cmf-title">
                <h1>Transferências</h1>
                <p>Central de Movimentação Financeira</p>
            </div>
        </div>

        <div class="cmf-kpis">
            <div class="cmf-kpi">
                <div class="cmf-kpi-top">
                    <span class="cmf-kpi-label">Volume movimentado</span>
                    <div class="cmf-kpi-icon" style="background:rgba(124,58,237,.15);color:#a78bfa;"><i class="fas fa-exchange-alt"></i></div>
                </div>
                <div class="cmf-kpi-value">{{ $money($stats['total_volume'] ?? 0) }}</div>
                <div class="cmf-kpi-foot">Últimos 30 dias</div>
            </div>
            <div class="cmf-kpi">
                <div class="cmf-kpi-top">
                    <span class="cmf-kpi-label">Transf. Enviadas</span>
                    <div class="cmf-kpi-icon" style="background:rgba(239,68,68,.12);color:#f87171;"><i class="fas fa-arrow-up"></i></div>
                </div>
                <div class="cmf-kpi-value">{{ $money($stats['sent_volume'] ?? 0) }}</div>
                <div class="cmf-kpi-foot"><span>Qtd: {{ $directionCounts['sent'] ?? 0 }}</span></div>
            </div>
            <div class="cmf-kpi">
                <div class="cmf-kpi-top">
                    <span class="cmf-kpi-label">Recebidas</span>
                    <div class="cmf-kpi-icon" style="background:rgba(34,197,94,.12);color:#22c55e;"><i class="fas fa-arrow-down"></i></div>
                </div>
                <div class="cmf-kpi-value">{{ $money($stats['received_volume'] ?? 0) }}</div>
                <div class="cmf-kpi-foot"><span>Qtd: {{ $directionCounts['received'] ?? 0 }}</span></div>
            </div>
            <div class="cmf-kpi">
                <div class="cmf-kpi-top">
                    <span class="cmf-kpi-label">Saques (Pix)</span>
                    <div class="cmf-kpi-icon" style="background:rgba(59,130,246,.12);color:#60a5fa;"><i class="fas fa-university"></i></div>
                </div>
                <div class="cmf-kpi-value">{{ $money($stats['withdraw_volume'] ?? 0) }}</div>
                <div class="cmf-kpi-foot"><span>Qtd: {{ $directionCounts['withdraw'] ?? 0 }}</span></div>
            </div>
            <div class="cmf-kpi">
                <div class="cmf-kpi-top">
                    <span class="cmf-kpi-label">Taxa de sucesso</span>
                    <div class="cmf-kpi-icon" style="background:rgba(245,158,11,.12);color:#f59e0b;"><i class="fas fa-check-circle"></i></div>
                </div>
                <div class="cmf-kpi-value">{{ number_format((float)($stats['success_rate'] ?? 0), 1, ',', '.') }}%</div>
                <div class="cmf-kpi-foot">Histórico total</div>
            </div>
        </div>
    </div>

    <div class="cmf-grid">
        <!-- Coluna Esquerda: Ações -->
        <div class="cmf-col-left">
            
            <h4 class="cmf-step-title"><span>1</span> Escolha o tipo de movimentação</h4>
            <div class="cmf-type-cards">
                <div class="cmf-type-card active" id="card-internal" onclick="switchForm('internal')">
                    <div class="cmf-type-card-icon"><i class="fas fa-user"></i></div>
                    <h5>Usuário OriginPay</h5>
                    <p>Enviar saldo instantaneamente para outro usuário.</p>
                    <div class="cmf-radio"><div class="cmf-radio-inner"></div></div>
                </div>
                <div class="cmf-type-card" id="card-pix" onclick="switchForm('pix')">
                    <div class="cmf-type-card-icon"><i class="fas fa-university"></i></div>
                    <h5>Conta bancária (Pix)</h5>
                    <p>Transferir saldo via Pix para qualquer banco.</p>
                    <div class="cmf-radio"><div class="cmf-radio-inner"></div></div>
                </div>
            </div>

            <h4 class="cmf-step-title" style="margin-top: 4px;"><span>2</span> Dados da movimentação</h4>
            
            <div class="cmf-form-container">
                <!-- FORM: Internal Transfer -->
                <form id="form-internal" action="{{ route('user.send-money.store') }}" method="POST" class="cmf-form active" >
                    @csrf
                    
                    <div class="cmf-input-group">
                        <label class="cmf-label">Destinatário</label>
                        <i class="fas fa-at cmf-input-icon"></i>
                        <input type="text" name="recipient" class="cmf-input with-icon" placeholder="E-mail, username ou CPF" required>
                    </div>

                    <div class="cmf-input-group">
                        <label class="cmf-label">Valor</label>
                        <span class="cmf-input-icon" style="font-weight: 700; font-size: 0.85rem;">R$</span>
                        <input type="text" name="amount" class="cmf-input with-icon" placeholder="0,00" oninput="this.value = validateDouble(this.value)" required>
                    </div>

                    <div class="cmf-input-group">
                        <label class="cmf-label">Descrição (opcional)</label>
                        <input type="text" name="note" class="cmf-input" placeholder="Ex: Pagamento serviço" maxlength="120">
                    </div>

                    

                    <button type="submit" class="cmf-btn-submit">
                        Continuar
                    </button>
                </form>

                <!-- FORM: Pix Withdrawal -->
                <form id="form-pix" action="{{ route('user.withdraw.store') }}" method="POST" class="cmf-form" >
                    @csrf
                    
                    <div class="cmf-input-group">
                        <label class="cmf-label">Chave Pix Cadastrada</label>
                        @if(isset($pixKeys) && $pixKeys->count() > 0)
                            <select name="pix_key_id" class="cmf-input" required>
                                @foreach($pixKeys as $key)
                                    <option value="{{ $key->id }}">{{ $key->pix_key }} ({{ strtoupper($key->key_type) }})</option>
                                @endforeach
                            </select>
                        @else
                            <div style="background: rgba(255, 77, 106, 0.1); border-left: 3px solid var(--ds-error); padding: 12px; border-radius: 8px;">
                                <span style="color: var(--ds-error); font-size: 0.8rem; display: block; margin-bottom: 8px;">Nenhuma chave Pix cadastrada.</span>
                                <a href="{{ route('user.pix-keys.index') }}" class="v2-btn-secondary" style="font-size: 0.75rem; padding: 6px 12px; display: inline-flex; height: auto;">
                                    Cadastrar chave Pix
                                </a>
                            </div>
                        @endif
                    </div>

                    <div class="cmf-input-group">
                        <label class="cmf-label">Valor</label>
                        <span class="cmf-input-icon" style="font-weight: 700; font-size: 0.85rem;">R$</span>
                        <input type="text" name="amount" class="cmf-input with-icon" placeholder="0,00" oninput="this.value = validateDouble(this.value)" required @if(!isset($pixKeys) || $pixKeys->count() == 0) disabled @endif>
                    </div>

                    <div class="cmf-input-group">
                        <label class="cmf-label">Descrição (opcional)</label>
                        <input type="text" name="note" class="cmf-input" placeholder="Ex: Saque de rendimento" maxlength="120" @if(!isset($pixKeys) || $pixKeys->count() == 0) disabled @endif>
                    </div>

                    <button type="submit" class="cmf-btn-submit" @if(!isset($pixKeys) || $pixKeys->count() == 0) disabled style="opacity:0.5;cursor:not-allowed;" @endif>
                        Continuar
                    </button>
                </form>
            </div>

            <div class="cmf-security">
                <i class="fas fa-shield-alt"></i>
                <p>Todas as movimentações são protegidas por criptografia ponta a ponta e monitoradas automaticamente pela OriginPay.</p>
            </div>
            
        </div>

        <!-- Coluna Direita: Histórico -->
        <div class="cmf-col-right">
            
            <div class="cmf-history-header">
                <h4 class="cmf-history-title">Consulta de movimentações</h4>
                <form action="{{ route('user.transfer.index') }}" method="GET" class="cmf-filters">
                    <input type="text" name="search" class="cmf-filter-input" value="{{ request('search') }}" placeholder="Pesquisar ID ou ref...">
                    
                    <select name="direction" class="cmf-filter-input">
                        <option value="all" @selected($activeDirection === 'all')>Tipos (Todos)</option>
                        <option value="sent" @selected($activeDirection === 'sent')>Transferências</option>
                        <option value="withdraw" @selected($activeDirection === 'withdraw')>Saques</option>
                        <option value="received" @selected($activeDirection === 'received')>Recebimentos</option>
                    </select>

                    <select name="status" class="cmf-filter-input">
                        <option value="all" @selected($activeStatus === 'all')>Status (Todos)</option>
                        @foreach($statusLabels as $key => $label)
                            <option value="{{ $key }}" @selected($activeStatus === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </form>
            </div>

            <div class="cmf-tabs">
                @foreach($tabs as $key => $label)
                    @php 
                        $params = request()->except('direction', 'status', 'page');
                        if($key === 'failed') {
                            $params['status'] = TrxStatus::FAILED->value;
                        } elseif($key !== 'all') {
                            $params['direction'] = $key;
                        }
                        $isActive = ($key === 'failed' && $activeStatus === TrxStatus::FAILED->value) || ($key !== 'failed' && $activeDirection === $key && $activeStatus !== TrxStatus::FAILED->value);
                    @endphp
                    <a href="{{ route('user.transfer.index', $params) }}" class="cmf-tab {{ $isActive ? 'active' : '' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            <div class="cmf-table-wrap">
                <table class="cmf-table">
                    <thead>
                        <tr>
                            <th>Movimentação</th>
                            <th>Valor</th>
                            <th>Status</th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transfers->take(8) as $transfer)
                            @php
                                $type = $transfer->trx_type?->value ?? (string)$transfer->trx_type;
                                $status = $transfer->status?->value ?? (string)$transfer->status;
                                $isOut = in_array($type, [TrxType::SEND_MONEY->value, TrxType::WITHDRAW->value]);
                                $isPix = $type === TrxType::WITHDRAW->value;
                            @endphp
                            <tr>
                                <td>
                                    <div class="cmf-trx-info">
                                        <div class="cmf-trx-icon" style="background: {{ $isOut ? 'rgba(239,68,68,0.12)' : 'rgba(34,197,94,0.12)' }}; color: {{ $isOut ? 'var(--ds-error)' : 'var(--ds-success)' }};">
                                            <i class="fas {{ $isPix ? 'fa-university' : ($isOut ? 'fa-arrow-up' : 'fa-arrow-down') }}"></i>
                                        </div>
                                        <div>
                                            <div class="cmf-trx-main">{{ $isPix ? 'Saque via Pix' : ($isOut ? 'Transferência Enviada' : 'Transferência Recebida') }}</div>
                                            <div class="cmf-trx-sub">{{ $transfer->description ?: 'ID: ' . $transfer->trx_id }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="cmf-amt {{ $isOut ? 'out' : 'in' }}">{{ $isOut ? '-' : '+' }} {{ $money($transfer->amount) }}</div>
                                </td>
                                <td><span class="cmf-badge {{ $status }}">{{ $statusLabels[$status] ?? ucfirst($status) }}</span></td>
                                <td>
                                    <div class="cmf-trx-main" style="font-size:0.8rem;">{{ $transfer->created_at?->format('d/m/Y') }}</div>
                                    <div class="cmf-trx-sub">{{ $transfer->created_at?->format('H:i') }}</div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="border:none;">
                                    <div class="cmf-empty">
                                        <i class="fas fa-receipt"></i>
                                        <h6>Nenhuma movimentação encontrada</h6>
                                        <p>Quando você realizar uma transferência ou saque, ela aparecerá aqui.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($transfers->total() > 8)
            <div class="cmf-history-footer">
                @php $pagParams = request()->all(); $pagParams['page'] = 2; @endphp
                <a href="{{ route('user.transfer.index', $pagParams) }}" class="cmf-link">Ver histórico completo &rarr;</a>
            </div>
            @endif
        </div>

    </div>
</div>

<!-- Transaction Password Modal -->
<div id="trxPasswordModal" class="cmf-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); backdrop-filter:blur(4px); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:var(--ds-bg-card); border:1px solid var(--ds-border-light); width:90%; max-width:400px; border-radius:12px; padding:24px; position:relative;">
        <button type="button" onclick="closeTrxModal()" style="position:absolute; top:16px; right:16px; background:none; border:none; color:var(--ds-text-muted); cursor:pointer;"><i class="fas fa-times"></i></button>
        
        <h4 style="margin:0 0 8px; font-size:1.1rem; font-weight:700; color:var(--ds-text-main);">Confirmação de Segurança</h4>
        <p style="margin:0 0 20px; font-size:0.85rem; color:var(--ds-text-muted);">Por favor, insira sua senha transacional para autorizar esta operação.</p>
        
        <div class="cmf-input-group">
            <label class="cmf-label">Senha Transacional</label>
            <input type="password" id="modal_trx_password" class="cmf-input" placeholder="Digite sua senha" style="padding-left:14px;">
        </div>
        
        <div style="display:flex; gap:12px; margin-top:24px;">
            <button type="button" onclick="closeTrxModal()" class="v2-btn-secondary" style="flex:1; justify-content:center; height:40px;">Cancelar</button>
            <button type="button" onclick="confirmTrxModal()" class="cmf-btn-submit" style="margin-top:0; flex:1; justify-content:center; height:40px;">Confirmar</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function switchForm(type) {
        // Atualiza cards
        document.getElementById('card-internal').classList.remove('active');
        document.getElementById('card-pix').classList.remove('active');
        document.getElementById('card-' + type).classList.add('active');

        // Atualiza forms com fade
        const formInternal = document.getElementById('form-internal');
        const formPix = document.getElementById('form-pix');

        if(type === 'internal') {
            formPix.classList.remove('active');
            setTimeout(() => {
                formInternal.classList.add('active');
            }, 150);
        } else {
            formInternal.classList.remove('active');
            setTimeout(() => {
                formPix.classList.add('active');
            }, 150);
        }
    }

    function validateDouble(val) {
        return val.replace(/[^0-9.,]/g, '').replace(/(\..*?)\..*/g, '$1').replace(/(,.*?)\,.*/g, '$1');
    }

    document.addEventListener('DOMContentLoaded', () => {
        const colRight = document.querySelector('.cmf-col-right');

        function fetchHistory(url) {
            const tableWrap = colRight.querySelector('.cmf-table-wrap');
            if (tableWrap) {
                tableWrap.innerHTML = `
                    <x-spinner text="Carregando movimentações..." />
                `;
            }

            fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newColRight = doc.querySelector('.cmf-col-right');
                
                if (newColRight) {
                    colRight.innerHTML = newColRight.innerHTML;
                }
                
                window.history.pushState({}, '', url);
            })
            .catch(error => {
                console.error('Error fetching history:', error);
                window.location.href = url; // Fallback
            });
        }

        colRight.addEventListener('click', (e) => {
            const tab = e.target.closest('.cmf-tab');
            if (tab) {
                e.preventDefault();
                fetchHistory(tab.href);
            }
        });

        colRight.addEventListener('change', (e) => {
            if (e.target.closest('.cmf-filter-input')) {
                const form = e.target.closest('form.cmf-filters');
                if (form) {
                    const url = new URL(form.action);
                    const formData = new FormData(form);
                    for (const [key, value] of formData) {
                        if (value) url.searchParams.append(key, value);
                    }
                    fetchHistory(url.toString());
                }
            }
        });

        colRight.addEventListener('submit', (e) => {
            const form = e.target.closest('form.cmf-filters');
            if (form) {
                e.preventDefault();
                const url = new URL(form.action);
                const formData = new FormData(form);
                for (const [key, value] of formData) {
                    if (value) url.searchParams.append(key, value);
                }
                fetchHistory(url.toString());
            }
        });
    });
    let pendingForm = null;
    const trxPasswordInput = document.getElementById('modal_trx_password');

    if (trxPasswordInput) {
        trxPasswordInput.addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '').slice(0, 4);
        });
        trxPasswordInput.addEventListener('paste', function(event) {
            event.preventDefault();
        });
    }

    // Intercept form submissions
    const internalForm = document.getElementById('form-internal');
    const pixForm = document.getElementById('form-pix');

    [internalForm, pixForm].forEach((form) => {
        if (!form) return;

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            pendingForm = this;
            openTrxModal();
        });
    });

    function openTrxModal() {
        const modal = document.getElementById('trxPasswordModal');
        const passwordInput = document.getElementById('modal_trx_password');

        if (!modal || !passwordInput) return;

        modal.style.display = 'flex';
        setTimeout(() => passwordInput.focus(), 100);
    }

    function closeTrxModal() {
        const modal = document.getElementById('trxPasswordModal');
        const passwordInput = document.getElementById('modal_trx_password');

        if (modal) modal.style.display = 'none';
        if (passwordInput) passwordInput.value = '';
        pendingForm = null;
    }

    function confirmTrxModal() {
        const form = pendingForm;
        const passwordInput = document.getElementById('modal_trx_password');
        const pass = passwordInput ? passwordInput.value : '';

        if (!form) {
            closeTrxModal();
            return;
        }

        if(!pass) return;

        // Remove old hidden input if exists
        const oldInput = form.querySelector('input[name="transaction_password"]');
        if(oldInput) oldInput.remove();

        // Add hidden input with password
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'transaction_password';
        hiddenInput.value = pass;
        form.appendChild(hiddenInput);

        // Submit and show processing
        disableSubmitButton(form, 'Processando...');
        closeTrxModal();
        form.submit();
    }
</script>
@endpush
