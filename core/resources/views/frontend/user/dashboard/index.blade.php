@extends('frontend.layouts.user-v2')
@section('title', __('Dashboard'))

@section('content')

@php
    $brlWallet  = isset($userWallets) ? $userWallets->where('currency.code', 'BRL')->first() : null;
    $primaryWallet = $brlWallet ?? (isset($userWallets) ? $userWallets->first() : null);
    
    // Fallbacks for KPIs
    $volPix = 0;
    $volCartoes = 0;
    $volBoletos = 0;
    $volCripto = 0;
    
    // Try to extract from statistics if available
    if(isset($statistics)) {
        foreach($statistics as $stat) {
            $t = strtolower($stat['title']);
            if(str_contains($t, 'pix')) $volPix = (float) preg_replace('/[^0-9.]/', '', str_replace(',', '.', str_replace('.', '', $stat['value'])));
            elseif(str_contains($t, 'cart')) $volCartoes = (float) preg_replace('/[^0-9.]/', '', str_replace(',', '.', str_replace('.', '', $stat['value'])));
            elseif(str_contains($t, 'boleto')) $volBoletos = (float) preg_replace('/[^0-9.]/', '', str_replace(',', '.', str_replace('.', '', $stat['value'])));
            elseif(str_contains($t, 'cripto')) $volCripto = (float) preg_replace('/[^0-9.]/', '', str_replace(',', '.', str_replace('.', '', $stat['value'])));
        }
    }
    
    $volTotal = $volPix + $volCartoes + $volBoletos + $volCripto;
    if ($volTotal == 0 && isset($totalSuccessDeposit)) {
        // If absolutely no specific gateway data, we just show total deposit as total volume
        $volTotal = (float) preg_replace('/[^0-9.]/', '', str_replace(',', '.', str_replace('.', '', $totalSuccessDeposit)));
    }
@endphp

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 style="font-weight: 700; color: var(--ds-text-main); margin-bottom: 4px;">Olá, {{ strtok(auth()->user()->name ?? auth()->user()->first_name ?? 'Usuário', ' ') }}! 👋</h4>
        <div style="color: var(--ds-text-muted); font-size: 0.9rem;">Aqui está o resumo da sua operação hoje.</div>
    </div>
    <div class="d-flex gap-3">
        @if(isset($onboarding) && !$onboarding['kyc_approved'])
            <div class="badge bg-warning text-dark d-flex align-items-center px-3" style="border-radius: 20px; font-weight: 600;">
                <i class="fas fa-exclamation-triangle me-2"></i> KYC Pendente
            </div>
        @endif
        <div class="ds-card px-3 py-2 d-flex align-items-center gap-2" style="border-radius: 8px;">
            <i class="far fa-calendar-alt text-muted"></i>
            <span style="font-size: 0.85rem; color: var(--ds-text-muted);">{{ now()->startOfMonth()->format('d/m/Y') }} - {{ now()->endOfMonth()->format('d/m/Y') }}</span>
        </div>
    </div>
</div>

{{-- ── HERO KPIs ──────────────────────────────────────────────────────────── --}}
<div class="op-stats-grid">
    <div class="op-kpi-card">
        <div class="op-kpi-icon bg-pix"><i class="fas fa-qrcode"></i></div>
        <div class="op-kpi-title">Volume Pix</div>
        <div class="op-kpi-val">R$ {{ number_format($volPix, 2, ',', '.') }}</div>
        @if($volPix > 0)
        <div class="op-kpi-trend text-success"><i class="fas fa-arrow-up"></i> 0,0% <span class="text-muted">vs período anterior</span></div>
        @else
        <div class="op-kpi-trend text-muted">Sem dados ainda</div>
        @endif
    </div>
    <div class="op-kpi-card">
        <div class="op-kpi-icon bg-card"><i class="fas fa-credit-card"></i></div>
        <div class="op-kpi-title">Volume Cartões</div>
        <div class="op-kpi-val">R$ {{ number_format($volCartoes, 2, ',', '.') }}</div>
        @if($volCartoes > 0)
        <div class="op-kpi-trend text-success"><i class="fas fa-arrow-up"></i> 0,0% <span class="text-muted">vs período anterior</span></div>
        @else
        <div class="op-kpi-trend text-muted">Sem dados ainda</div>
        @endif
    </div>
    <div class="op-kpi-card">
        <div class="op-kpi-icon bg-boleto"><i class="fas fa-barcode"></i></div>
        <div class="op-kpi-title">Volume Boletos</div>
        <div class="op-kpi-val">R$ {{ number_format($volBoletos, 2, ',', '.') }}</div>
        @if($volBoletos > 0)
        <div class="op-kpi-trend text-success"><i class="fas fa-arrow-up"></i> 0,0% <span class="text-muted">vs período anterior</span></div>
        @else
        <div class="op-kpi-trend text-muted">Sem dados ainda</div>
        @endif
    </div>
    <div class="op-kpi-card">
        <div class="op-kpi-icon bg-crypto"><i class="fab fa-bitcoin"></i></div>
        <div class="op-kpi-title">Volume Cripto</div>
        <div class="op-kpi-val">R$ {{ number_format($volCripto, 2, ',', '.') }}</div>
        @if($volCripto > 0)
        <div class="op-kpi-trend text-success"><i class="fas fa-arrow-up"></i> 0,0% <span class="text-muted">vs período anterior</span></div>
        @else
        <div class="op-kpi-trend text-muted">Sem dados ainda</div>
        @endif
    </div>
    <div class="op-kpi-card" style="background: linear-gradient(135deg, rgba(124,58,237,0.15) 0%, rgba(19,23,34,1) 100%); border-color: var(--ds-primary);">
        <div class="op-kpi-icon bg-total"><i class="fas fa-dollar-sign"></i></div>
        <div class="op-kpi-title" style="color: var(--ds-text-main);">Volume Total</div>
        <div class="op-kpi-val text-primary">R$ {{ number_format($volTotal, 2, ',', '.') }}</div>
        @if($volTotal > 0)
        <div class="op-kpi-trend text-success"><i class="fas fa-arrow-up"></i> 0,0% <span class="text-muted">vs período anterior</span></div>
        @else
        <div class="op-kpi-trend text-muted">Sem dados ainda</div>
        @endif
    </div>
</div>

{{-- ── MAIN DASHBOARD GRID ──────────────────────────────────────────────────────────── --}}
<div class="op-dashboard-grid mb-4">
    
    <div class="d-flex flex-column gap-4">
        {{-- Charts Area --}}
        <div class="ds-card p-4 h-100">
            <div class="d-flex justify-content-between mb-4">
                <h6 style="color: var(--ds-text-main); font-weight: 600; margin: 0;">Volume de Transações <i class="fas fa-info-circle text-muted ms-1"></i></h6>
                <select class="v2-input form-select-sm" style="width: auto; background-color: rgba(255,255,255,0.05); border-color: var(--ds-border-medium); color: var(--ds-text-main);">
                    <option>Diário</option>
                </select>
            </div>
            
            @if(isset($transactions) && $transactions->count() > 0)
                <div id="deposit-chart" style="height: 250px;"></div>
            @else
                <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 250px; text-align: center;">
                    <div style="width: 48px; height: 48px; background: rgba(124, 58, 237, 0.05); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 12px;">
                        <i class="fas fa-chart-area" style="color: var(--ds-text-muted); font-size: 1.2rem;"></i>
                    </div>
                    <div style="font-weight: 600; color: var(--ds-text-main); font-size: 0.95rem; margin-bottom: 4px;">Sem dados suficientes</div>
                    <div style="font-size: 0.85rem; color: var(--ds-text-muted);">Os gráficos de volume aparecerão quando houver movimentação.</div>
                </div>
            @endif
        </div>

        {{-- Recent Transactions --}}
        <div class="ds-card p-4">
            <div class="d-flex justify-content-between mb-3">
                <h6 style="color: var(--ds-text-main); font-weight: 600; margin: 0;">Transações Recentes</h6>
                <a href="{{ route('user.transaction.index') }}" style="color: var(--ds-primary); text-decoration: none; font-size: 0.85rem; font-weight: 600;">Ver todas &rarr;</a>
            </div>
            <div class="table-responsive">
                <table class="table ds-dark-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Data</th>
                            <th>Cliente</th>
                            <th>Valor</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse((isset($transactions) ? $transactions->take(5) : []) as $trx)
                        <tr>
                            <td class="text-muted">#{{ substr($trx->trx ?? $trx->id, 0, 10) }}</td>
                            <td>{{ \Carbon\Carbon::parse($trx->created_at)->format('d/m/Y H:i') }}</td>
                            <td>{{ $trx->user->fullname ?? 'N/A' }}</td>
                            <td style="font-weight: 600;">R$ {{ number_format($trx->amount ?? 0, 2, ',', '.') }}</td>
                            <td>
                                @if(isset($trx->status) && $trx->status == 1)
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">Aprovada &rsaquo;</span>
                                @elseif(isset($trx->status) && (string)$trx->status === '2')
                                    <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25">Pendente &rsaquo;</span>
                                @else
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25">Recusada &rsaquo;</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">Nenhuma transação recente encontrada.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="d-flex flex-column gap-4">
        {{-- Approval Rate --}}
        <div class="ds-card p-4">
            <h6 style="color: var(--ds-text-main); font-weight: 600; margin-bottom: 24px;">Taxa de Aprovação</h6>
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div style="position: relative; width: 120px; height: 120px; border-radius: 50%; border: 12px solid rgba(16, 185, 129, 0.2); border-left-color: var(--ds-success); border-top-color: var(--ds-success); display: flex; flex-direction: column; align-items: center; justify-content: center;">
                    <span style="font-size: 1.5rem; font-weight: 700; color: var(--ds-text-main);">0,0%</span>
                    <span style="font-size: 0.65rem; color: var(--ds-text-muted); text-transform: uppercase;">Aprovação</span>
                </div>
                <div>
                    <div class="mb-2"><span class="d-inline-block rounded-circle bg-success me-2" style="width:8px;height:8px;"></span> <span class="text-muted" style="font-size:0.85rem;">Aprovadas</span></div>
                    <div class="mb-2"><span class="d-inline-block rounded-circle bg-danger me-2" style="width:8px;height:8px;"></span> <span class="text-muted" style="font-size:0.85rem;">Recusadas</span></div>
                    <div><span class="d-inline-block rounded-circle bg-warning me-2" style="width:8px;height:8px;"></span> <span class="text-muted" style="font-size:0.85rem;">Pendentes</span></div>
                </div>
            </div>
            <a href="javascript:void(0)" class="text-center d-block text-muted" style="font-size: 0.85rem; text-decoration: none;">Ver mais detalhes</a>
        </div>

        {{-- Payment Methods --}}
        <div class="ds-card p-4 flex-grow-1">
            <h6 style="color: var(--ds-text-main); font-weight: 600; margin-bottom: 24px;">Recebimentos por Método</h6>
            
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div style="position: relative; width: 100px; height: 100px; border-radius: 50%; border: 12px solid rgba(124, 58, 237, 0.2); border-left-color: var(--ds-primary); border-bottom-color: var(--ds-accent); display: flex; flex-direction: column; align-items: center; justify-content: center;">
                </div>
                <div>
                    <div class="mb-2"><span class="d-inline-block rounded-circle me-2 bg-pix" style="width:8px;height:8px;background:#00E5C8;"></span> <span class="text-muted" style="font-size:0.85rem;">Pix</span> <span class="float-end ms-3">0,0%</span></div>
                    <div class="mb-2"><span class="d-inline-block rounded-circle me-2 bg-card" style="width:8px;height:8px;background:#7C3AED;"></span> <span class="text-muted" style="font-size:0.85rem;">Cartões</span> <span class="float-end ms-3">0,0%</span></div>
                    <div class="mb-2"><span class="d-inline-block rounded-circle me-2 bg-boleto" style="width:8px;height:8px;background:#F59E0B;"></span> <span class="text-muted" style="font-size:0.85rem;">Boletos</span> <span class="float-end ms-3">0,0%</span></div>
                    <div><span class="d-inline-block rounded-circle me-2 bg-crypto" style="width:8px;height:8px;background:#3B82F6;"></span> <span class="text-muted" style="font-size:0.85rem;">Cripto</span> <span class="float-end ms-3">0,0%</span></div>
                </div>
            </div>

            <div class="mt-auto p-3" style="background: rgba(124,58,237,0.1); border-radius: 12px; border: 1px solid rgba(124,58,237,0.2);">
                <h6 style="color: var(--ds-text-main); font-size: 0.9rem; font-weight: 600; margin-bottom: 4px;">Aumente suas conversões</h6>
                <p style="color: var(--ds-text-muted); font-size: 0.75rem; margin-bottom: 12px;">Ofereça mais métodos de pagamento e alcance mais clientes.</p>
                <a href="{{ route('user.settings.profile') }}" class="btn btn-sm w-100" style="background: var(--ds-primary); color: white; border: none;">Ativar métodos</a>
            </div>
        </div>
    </div>
</div>

{{-- ── FOOTER STATUS BAR ──────────────────────────────────────────────────────────── --}}
<div class="op-status-bar">
    <div class="d-flex gap-4">
        <div class="op-status-item">
            <i class="fas fa-server"></i> API Status 
            <span class="op-status-dot"></span> <span class="text-success">Operacional</span>
        </div>
        <div class="op-status-item">
            <i class="fas fa-link"></i> Webhooks 
            @if(\App\Models\WebhookEndpoint::where('user_id', auth()->id())->exists())
                <span class="op-status-dot"></span> <span class="text-success">Ativo</span>
            @else
                <span class="op-status-dot bg-secondary"></span> <span>Inativo</span>
            @endif
        </div>
    </div>
    <div class="d-flex gap-4 align-items-center">
        <div class="op-status-item">
            <i class="far fa-clock"></i> Última atualização <br/> {{ now()->format('d/m/Y H:i:s') }}
        </div>
    </div>
</div>

@endsection

@push('scripts')
@include('frontend.user.dashboard.partials._script')
@endpush
