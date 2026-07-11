@extends('frontend.layouts.user-v2')

@section('title', 'Dashboard')

@section('content')

<style>
    @media (max-width: 768px) {
        .dashboard-summary-card {
            padding: 14px !important;
        }

        .dashboard-summary-inner,
        .dashboard-summary-copy,
        .dashboard-summary-title,
        .dashboard-summary-updated,
        .dashboard-summary-empty,
        .dashboard-summary-empty-copy {
            width: 100% !important;
            min-width: 0 !important;
        }

        .dashboard-summary-inner {
            display: grid !important;
            grid-template-columns: 1fr !important;
            gap: 14px !important;
        }

        .dashboard-summary-copy {
            display: grid !important;
            grid-template-columns: 1fr !important;
            gap: 6px !important;
        }

        .dashboard-summary-title,
        .dashboard-summary-updated,
        .dashboard-summary-empty {
            display: flex !important;
            align-items: flex-start !important;
            flex-wrap: nowrap !important;
            gap: 7px !important;
        }

        .dashboard-summary-title i,
        .dashboard-summary-updated i,
        .dashboard-summary-empty i {
            flex: 0 0 auto !important;
            margin-top: 2px !important;
        }

        .dashboard-summary-updated {
            line-height: 1.35 !important;
        }

        .dashboard-summary-empty-copy {
            display: grid !important;
            grid-template-columns: 1fr !important;
            gap: 3px !important;
        }
    }
</style>

@php
    // Fallbacks for KPIs
    $pixVolume = 0;
    $cardVolume = 0;
    $boletoVolume = 0;
    $cryptoVolume = 0;
    
    // Try to extract from statistics if available
    if(isset($statistics)) {
        foreach($statistics as $stat) {
            $t = strtolower($stat['title']);
            if(str_contains($t, 'pix')) $pixVolume = (float) preg_replace('/[^0-9.]/', '', str_replace(',', '.', str_replace('.', '', $stat['value'])));
            elseif(str_contains($t, 'cart')) $cardVolume = (float) preg_replace('/[^0-9.]/', '', str_replace(',', '.', str_replace('.', '', $stat['value'])));
            elseif(str_contains($t, 'boleto')) $boletoVolume = (float) preg_replace('/[^0-9.]/', '', str_replace(',', '.', str_replace('.', '', $stat['value'])));
            elseif(str_contains($t, 'cripto')) $cryptoVolume = (float) preg_replace('/[^0-9.]/', '', str_replace(',', '.', str_replace('.', '', $stat['value'])));
        }
    }
    
    $totalAmount = $pixVolume + $cardVolume + $boletoVolume + $cryptoVolume;
    if ($totalAmount == 0 && isset($totalSuccessDeposit)) {
        // If absolutely no specific gateway data, we just show total deposit as total volume
        $totalAmount = (float) preg_replace('/[^0-9.]/', '', str_replace(',', '.', str_replace('.', '', $totalSuccessDeposit)));
    }

    $hasStats = ($totalAmount > 0 || $pixVolume > 0 || $cardVolume > 0 || $boletoVolume > 0 || $cryptoVolume > 0);
    $periodLabel = isset($startDate, $endDate) && $startDate->isSameDay($endDate) ? 'Hoje' : 'Periodo selecionado';
@endphp

<div class="v2-kpi-grid">
    <div class="v2-kpi-card pix">
        <div style="flex: 1;">
            <div class="v2-kpi-header">
                <div class="v2-kpi-icon" style="background: rgba(0,229,200,0.1); color: var(--ds-pix);">
                    <i class="fas fa-qrcode"></i>
                </div>
                <div class="v2-kpi-title" style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
                    <span>Volume Pix</span>
                    <i class="fas fa-eye-slash toggle-dash-balance" style="cursor: pointer; opacity: 0.6; transition: opacity 0.2s;" title="Alternar visibilidade" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.6'"></i>
                </div>
            </div>
            <div class="v2-kpi-value" style="display: flex; align-items: center; height: 32px;">
                <span class="dash-balance-visible" style="display: none;">R$ {{ number_format($pixVolume, 2, ',', '.') }}</span>
                <span class="dash-balance-hidden" style="letter-spacing: 0.1em; font-size: 1.5rem; padding-top: 4px;">••••••</span>
            </div>
        </div>
        <div class="v2-kpi-trend" style="color: var(--ds-text-muted);">
            <i class="far fa-calendar"></i> {{ $periodLabel }}
        </div>
    </div>
    
    <div class="v2-kpi-card cards">
        <div style="flex: 1;">
            <div class="v2-kpi-header">
                <div class="v2-kpi-icon" style="background: rgba(124,58,237,0.1); color: var(--ds-card);">
                    <i class="far fa-credit-card"></i>
                </div>
                <div class="v2-kpi-title" style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
                    <span>Volume Cartões</span>
                    <i class="fas fa-eye-slash toggle-dash-balance" style="cursor: pointer; opacity: 0.6; transition: opacity 0.2s;" title="Alternar visibilidade" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.6'"></i>
                </div>
            </div>
            <div class="v2-kpi-value" style="display: flex; align-items: center; height: 32px;">
                <span class="dash-balance-visible" style="display: none;">R$ {{ number_format($cardVolume, 2, ',', '.') }}</span>
                <span class="dash-balance-hidden" style="letter-spacing: 0.1em; font-size: 1.5rem; padding-top: 4px;">••••••</span>
            </div>
        </div>
        <div class="v2-kpi-trend" style="color: var(--ds-text-muted);">
            <i class="far fa-calendar"></i> {{ $periodLabel }}
        </div>
    </div>
    
    <div class="v2-kpi-card boleto">
        <div style="flex: 1;">
            <div class="v2-kpi-header">
                <div class="v2-kpi-icon" style="background: rgba(245,158,11,0.1); color: var(--ds-boleto);">
                    <i class="fas fa-barcode"></i>
                </div>
                <div class="v2-kpi-title" style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
                    <span>Volume Boletos</span>
                    <i class="fas fa-eye-slash toggle-dash-balance" style="cursor: pointer; opacity: 0.6; transition: opacity 0.2s;" title="Alternar visibilidade" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.6'"></i>
                </div>
            </div>
            <div class="v2-kpi-value" style="display: flex; align-items: center; height: 32px;">
                <span class="dash-balance-visible" style="display: none;">R$ {{ number_format($boletoVolume, 2, ',', '.') }}</span>
                <span class="dash-balance-hidden" style="letter-spacing: 0.1em; font-size: 1.5rem; padding-top: 4px;">••••••</span>
            </div>
        </div>
        <div class="v2-kpi-trend" style="color: var(--ds-text-muted);">
            <i class="far fa-calendar"></i> {{ $periodLabel }}
        </div>
    </div>
    
    <div class="v2-kpi-card crypto">
        <div style="flex: 1;">
            <div class="v2-kpi-header">
                <div class="v2-kpi-icon" style="background: rgba(124,58,237,0.1); color: var(--ds-primary);">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div class="v2-kpi-title" style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
                    <span>Outros recebimentos</span>
                    <i class="fas fa-eye-slash toggle-dash-balance" style="cursor: pointer; opacity: 0.6; transition: opacity 0.2s;" title="Alternar visibilidade" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.6'"></i>
                </div>
            </div>
            <div class="v2-kpi-value" style="display: flex; align-items: center; height: 32px;">
                <span class="dash-balance-visible" style="display: none;">R$ {{ number_format($cryptoVolume, 2, ',', '.') }}</span>
                <span class="dash-balance-hidden" style="letter-spacing: 0.1em; font-size: 1.5rem; padding-top: 4px;">••••••</span>
            </div>
        </div>
        <div class="v2-kpi-trend" style="color: var(--ds-text-muted);">
            <i class="far fa-calendar"></i> {{ $periodLabel }}
        </div>
    </div>
    
    <div class="v2-kpi-card total">
        <div style="flex: 1;">
            <div class="v2-kpi-header">
                <div class="v2-kpi-icon" style="background: rgba(255,255,255,0.15); color: #fff; border: 1px solid rgba(255,255,255,0.1);">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="v2-kpi-title" style="color: rgba(255,255,255,0.8); display: flex; align-items: center; justify-content: space-between; width: 100%;">
                    <span>Volume Total</span>
                    <i class="fas fa-eye-slash toggle-dash-balance" style="cursor: pointer; opacity: 0.6; transition: opacity 0.2s;" title="Alternar visibilidade" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.6'"></i>
                </div>
            </div>
            <div class="v2-kpi-value" style="color: #fff; display: flex; align-items: center; height: 32px;">
                <span class="dash-balance-visible" style="display: none;">R$ {{ number_format($totalAmount, 2, ',', '.') }}</span>
                <span class="dash-balance-hidden" style="letter-spacing: 0.1em; font-size: 1.5rem; padding-top: 4px;">••••••</span>
            </div>
        </div>
        <div class="v2-kpi-trend" style="color: rgba(255,255,255,0.72);">
            <i class="far fa-calendar"></i> {{ $periodLabel }}
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const dashBtns = document.querySelectorAll('.toggle-dash-balance');
        const dashVisibles = document.querySelectorAll('.dash-balance-visible');
        const dashHiddens = document.querySelectorAll('.dash-balance-hidden');
        
        function syncDashBalances() {
            let isHidden = localStorage.getItem('hideBalance') !== 'false';
            
            dashBtns.forEach(btn => {
                btn.className = isHidden ? 'fas fa-eye-slash toggle-dash-balance' : 'fas fa-eye toggle-dash-balance';
            });
            
            dashVisibles.forEach(el => {
                el.style.display = isHidden ? 'none' : 'block';
            });
            
            dashHiddens.forEach(el => {
                el.style.display = isHidden ? 'block' : 'none';
            });
            
            // Sync with sidebar if exists in this layout
            const sbBtn = document.getElementById('toggle-balance-btn') || document.getElementById('toggle-balance-btn-v2');
            const sbVis = document.getElementById('balance-visible') || document.getElementById('balance-visible-v2');
            const sbHid = document.getElementById('balance-hidden') || document.getElementById('balance-hidden-v2');
            
            if (sbBtn && sbVis && sbHid) {
                const sbIcon = sbBtn.querySelector('i');
                if (sbIcon) {
                    sbIcon.className = isHidden ? 'fas fa-eye-slash' : 'fas fa-eye';
                }
                sbBtn.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
                sbVis.style.display = isHidden ? 'none' : 'block';
                sbHid.style.display = isHidden ? 'block' : 'none';
            }
        }
        
        syncDashBalances();
        
        dashBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                let isHidden = localStorage.getItem('hideBalance') !== 'false';
                localStorage.setItem('hideBalance', !isHidden);
                syncDashBalances();
            });
        });
        
        // Also listen to sidebar clicks if it exists
        const sbBtn = document.getElementById('toggle-balance-btn') || document.getElementById('toggle-balance-btn-v2');
        if (sbBtn) {
            sbBtn.addEventListener('click', function() {
                // Short delay to let the sidebar script update localStorage first
                setTimeout(syncDashBalances, 50);
            });
        }
    });
</script>

<div class="v2-dashboard-grid" style="flex-grow: 1; min-height: 0; align-items: stretch;">
    
    <div class="v2-left-col" style="display: flex; flex-direction: column; gap: 24px; height: 100%;">
        <div class="v2-card" style="flex-grow: 1; height: auto;">
            <div class="v2-card-header" style="border-bottom: none; padding-bottom: 0;">
                <div class="v2-card-title" style="font-size: 1.125rem;">
                    Volume de Transações 
                    <i class="fas fa-info-circle ms-1" style="color: var(--ds-text-muted); font-size: 0.875rem;" title="Volume consolidado de transações aprovadas."></i>
                </div>
                <span class="v2-badge v2-badge-info" style="font-weight: 500;">{{ $periodLabel }}</span>
            </div>
            
            <div style="display: flex; justify-content: center; gap: 24px; margin-bottom: 24px; font-size: 0.8125rem; font-weight: 500; margin-top: -10px;">
                <div style="display: flex; align-items: center; gap: 6px;"><span style="width: 14px; height: 8px; border-radius: 4px; background: var(--ds-primary);"></span> <span style="color: var(--ds-text-secondary);">Volume aprovado</span></div>
            </div>

            <div style="flex-grow: 1; min-height: 160px; display: flex; align-items: center; justify-content: center; flex-direction: column; position: relative;">
                @if($hasStats)
                    <canvas id="deposit-chart" style="width: 100%; height: 100%;"></canvas>
                @else
                    <div class="v2-chart-empty" style="width: 100%;">
                        <i class="fas fa-chart-area"></i>
                        <span style="font-weight: 500; color: var(--ds-text-secondary); margin-bottom: 2px;">Nenhuma movimentação no período</span>
                        <span style="font-size: 0.7rem;">Assim que você começar a transacionar, seus gráficos aparecerão aqui.</span>
                    </div>
                @endif
            </div>
        </div>

        <div class="v2-card" style="flex-grow: 1; height: auto;">
            <div class="v2-card-header">
                <span class="v2-card-title">Transações Recentes</span>
                <a href="{{ route('user.transaction.index') }}" class="v2-btn-outline" style="padding: 6px 12px; font-size: 0.75rem; text-decoration: none;">Ver todas <i class="fas fa-arrow-right ms-1"></i></a>
            </div>
            
            <div class="v2-table-wrapper" style="flex-grow: 1; display: flex; flex-direction: column;">
                <table class="v2-table" style="flex-grow: 1;">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Data</th>
                            <th>Método</th>
                            <th style="text-align: right;">Valor</th>
                            <th style="text-align: right;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(isset($transactions) ? $transactions->take(4) : [] as $trx)
                        <tr>
                            <td style="color: var(--ds-text-muted); font-family: monospace;">#{{ $trx->trx }}</td>
                            <td>{{ $trx->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                @if($trx->type == \App\Enums\TrxType::DEPOSIT)
                                    <span class="v2-badge v2-badge-pix"><i class="fas fa-qrcode"></i> Pix</span>
                                @else
                                    <span class="v2-badge v2-badge-card"><i class="fas fa-exchange-alt"></i> Padrão</span>
                                @endif
                            </td>
                            <td style="text-align: right; font-weight: 500; color: var(--ds-text-main);">R$ {{ number_format(abs($trx->amount), 2, ',', '.') }}</td>
                            <td style="text-align: right;">
                                @if($trx->status == \App\Enums\TrxStatus::COMPLETED)
                                    <span class="v2-badge v2-badge-success">Aprovada</span>
                                @else
                                    <span class="v2-badge v2-badge-warning">Pendente</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 48px 0;">
                                <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; color: var(--ds-text-muted);">
                                    <i class="fas fa-receipt" style="font-size: 2rem; opacity: 0.5; margin-bottom: 12px;"></i>
                                    <span style="font-weight: 500;">Nenhuma transação recente.</span>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="v2-right-col" style="display: flex; flex-direction: column; gap: 24px; height: 100%;">
        
        <div class="v2-card" style="height: auto;">
            <div class="v2-card-header">
                <span class="v2-card-title">Taxa de Aprovação</span>
            </div>
            
            <div style="display: flex; align-items: center; justify-content: center; padding: 0 0 16px;">
                <div style="width: 80px; height: 80px; border-radius: 50%; border: 6px solid var(--ds-bg-active); border-top-color: var(--ds-success); border-right-color: var(--ds-success); display: flex; align-items: center; justify-content: center; flex-direction: column; transform: rotate(-45deg);">
                    <div style="transform: rotate(45deg); display: flex; flex-direction: column; align-items: center;">
                        <span style="font-size: 1rem; font-weight: 700; color: var(--ds-text-main); line-height: 1;">{{ $dashboardSummary['approval_rate'] !== null ? number_format($dashboardSummary['approval_rate'], 1, ',', '.') . '%' : '--' }}</span>
                        <span style="font-size: 0.55rem; color: var(--ds-text-muted); text-transform: uppercase; margin-top: 2px; font-weight: 500; letter-spacing: 0.05em;">Geral</span>
                    </div>
                </div>
            </div>
            
            <div>
                <div class="v2-stat-row">
                    <div class="v2-stat-label">
                        <div class="v2-stat-dot" style="background: var(--ds-success);"></div> Aprovadas
                    </div>
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <span class="v2-stat-value">{{ $dashboardSummary['approved'] }}</span>
                        <span class="v2-stat-percent" style="color: var(--ds-success);">{{ $dashboardSummary['total'] > 0 ? number_format(($dashboardSummary['approved'] / $dashboardSummary['total']) * 100, 1, ',', '.') . '%' : '--' }}</span>
                    </div>
                </div>
                <div class="v2-stat-row">
                    <div class="v2-stat-label">
                        <div class="v2-stat-dot" style="background: var(--ds-danger);"></div> Recusadas
                    </div>
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <span class="v2-stat-value">{{ $dashboardSummary['rejected'] }}</span>
                        <span class="v2-stat-percent" style="color: var(--ds-danger);">{{ $dashboardSummary['total'] > 0 ? number_format(($dashboardSummary['rejected'] / $dashboardSummary['total']) * 100, 1, ',', '.') . '%' : '--' }}</span>
                    </div>
                </div>
                <div class="v2-stat-row">
                    <div class="v2-stat-label">
                        <div class="v2-stat-dot" style="background: var(--ds-boleto);"></div> Pendentes
                    </div>
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <span class="v2-stat-value">{{ $dashboardSummary['pending'] }}</span>
                        <span class="v2-stat-percent" style="color: var(--ds-boleto);">{{ $dashboardSummary['total'] > 0 ? number_format(($dashboardSummary['pending'] / $dashboardSummary['total']) * 100, 1, ',', '.') . '%' : '--' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="v2-card" style="flex-grow: 1; height: auto;">
            <div class="v2-card-header">
                <span class="v2-card-title">Recebimentos por Metodo</span>
            </div>
            
            @if(!$hasStats)
                <div style="flex-grow: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 12px 0; text-align: center; color: var(--ds-text-muted);">
                    <div style="width: 60px; height: 60px; border-radius: 50%; border: 4px dashed var(--ds-border-medium); margin: 0 auto 12px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-chart-pie" style="font-size: 1.2rem; opacity: 0.5;"></i>
                    </div>
                    <div style="font-size: 0.8rem;">Sem recebimentos no periodo.</div>
                </div>
            @else
                @php
                    $pixPct = $totalAmount > 0 ? round(($pixVolume / $totalAmount) * 100) : 0;
                    $cardPct = $totalAmount > 0 ? round(($cardVolume / $totalAmount) * 100) : 0;
                    $boletoPct = $totalAmount > 0 ? round(($boletoVolume / $totalAmount) * 100) : 0;
                    $cryptoPct = $totalAmount > 0 ? round(($cryptoVolume / $totalAmount) * 100) : 0;
                    
                    $totalPct = $pixPct + $cardPct + $boletoPct + $cryptoPct;
                    if($totalPct > 0 && $totalPct != 100) {
                        $max = max($pixPct, $cardPct, $boletoPct, $cryptoPct);
                        $diff = 100 - $totalPct;
                        if($max == $pixPct) $pixPct += $diff;
                        elseif($max == $cardPct) $cardPct += $diff;
                        elseif($max == $boletoPct) $boletoPct += $diff;
                        elseif($max == $cryptoPct) $cryptoPct += $diff;
                    }
                    
                    // Donut segments
                    $degPix = ($pixPct / 100) * 360;
                    $degCard = ($cardPct / 100) * 360;
                    $degBoleto = ($boletoPct / 100) * 360;
                    $degCrypto = ($cryptoPct / 100) * 360;
                @endphp
                
                <div style="flex-grow: 1; display: flex; flex-direction: column; justify-content: center;">
                    <div style="display: flex; align-items: center; gap: 12px; padding: 0;">
                        <div style="width: 50px; height: 50px; border-radius: 50%; background: conic-gradient(var(--ds-pix) 0deg {{ $degPix }}deg, var(--ds-card) {{ $degPix }}deg {{ $degPix + $degCard }}deg, var(--ds-boleto) {{ $degPix + $degCard }}deg {{ $degPix + $degCard + $degBoleto }}deg, var(--ds-primary) {{ $degPix + $degCard + $degBoleto }}deg 360deg); display: flex; align-items: center; justify-content: center;">
                            <div style="width: 38px; height: 38px; border-radius: 50%; background: var(--ds-bg-card);"></div>
                        </div>
                        <div style="flex-grow: 1;">
                            <div class="v2-stat-row" style="padding: 6px 0; border: none;">
                                <div class="v2-stat-label"><div class="v2-stat-dot" style="background: var(--ds-pix);"></div> Pix</div>
                                <span class="v2-stat-percent" style="color: var(--ds-text-muted);">{{ $pixPct }}%</span>
                            </div>
                            <div class="v2-stat-row" style="padding: 6px 0; border: none;">
                                <div class="v2-stat-label"><div class="v2-stat-dot" style="background: var(--ds-card);"></div> Cartões</div>
                                <span class="v2-stat-percent" style="color: var(--ds-text-muted);">{{ $cardPct }}%</span>
                            </div>
                            <div class="v2-stat-row" style="padding: 6px 0; border: none;">
                                <div class="v2-stat-label"><div class="v2-stat-dot" style="background: var(--ds-boleto);"></div> Boletos</div>
                                <span class="v2-stat-percent" style="color: var(--ds-text-muted);">{{ $boletoPct }}%</span>
                            </div>
                            <div class="v2-stat-row" style="padding: 6px 0; border: none;">
                                <div class="v2-stat-label"><div class="v2-stat-dot" style="background: var(--ds-primary);"></div> Outros</div>
                                <span class="v2-stat-percent" style="color: var(--ds-text-muted);">{{ $cryptoPct }}%</span>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- ── Status Bar ─── --}}
        <div class="v2-card dashboard-summary-card" style="padding: 16px 20px; height: auto;">
            <div class="dashboard-summary-inner" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px;">
                <div class="dashboard-summary-copy" style="display: flex; flex-direction: column; gap: 6px;">
                    <div class="dashboard-summary-title" style="display: flex; align-items: center; gap: 8px; color: var(--ds-text-main); font-size: 0.85rem; font-weight: 600;">
                        <i class="fas fa-database" style="color: var(--ds-text-muted);"></i>
                        <span>Resumo do período</span>
                    </div>
                    <div class="dashboard-summary-updated" style="font-size: 0.75rem; color: var(--ds-text-muted); display: flex; align-items: center; gap: 4px;">
                        <i class="far fa-clock"></i> Última atualização da consulta: {{ $dashboardSummary['query_time']->format('d/m/Y H:i:s') }}
                    </div>
                </div>

                @if($dashboardSummary['total'] > 0)
                    <div style="display: flex; align-items: center; gap: 24px;">
                        <div style="display: flex; flex-direction: column;">
                            <span style="font-size: 0.75rem; color: var(--ds-text-muted); font-weight: 500;">Total Recebido</span>
                            <span style="font-size: 1rem; color: var(--ds-success); font-weight: 700;">{{ getSymbol('BRL') }} {{ number_format($dashboardSummary['approved_amount'], 2, ',', '.') }}</span>
                        </div>
                        
                        <div style="width: 1px; height: 24px; background: var(--ds-border-light);"></div>

                        <div style="display: flex; flex-direction: column;">
                            <span style="font-size: 0.75rem; color: var(--ds-text-muted); font-weight: 500;">Movimentações</span>
                            <span style="font-size: 1rem; color: var(--ds-text-main); font-weight: 700;">{{ $dashboardSummary['total'] }}</span>
                        </div>

                        <div style="width: 1px; height: 24px; background: var(--ds-border-light);"></div>

                        <div style="display: flex; flex-direction: column;">
                            <span style="font-size: 0.75rem; color: var(--ds-text-muted); font-weight: 500;">Última movimentação</span>
                            <span style="font-size: 0.85rem; color: var(--ds-text-main); font-weight: 600;">{{ $dashboardSummary['last_transaction'] ? $dashboardSummary['last_transaction']->created_at->diffForHumans() : '-' }}</span>
                        </div>
                    </div>
                @else
                    <div class="dashboard-summary-empty" style="display: flex; align-items: center; gap: 15px; color: var(--ds-text-muted); font-size: 0.85rem;">
                        <i class="fas fa-info-circle" style="color: var(--ds-warning); font-size: 1rem;"></i>
                        <div class="dashboard-summary-empty-copy" style="display: flex; flex-direction: column;">
                            <span style="font-weight: 600; color: var(--ds-text-main);">Nenhuma movimentação neste período.</span>
                            <span>Tente alterar o filtro de data ou criar uma cobrança.</span>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <script>
            if(typeof window.dashboardChart !== 'undefined' && window.dashboardChart !== null) {
                window.dashboardChart.destroy();
            }
            if(document.getElementById('deposit-chart')) {
                var ctx = document.getElementById('deposit-chart').getContext('2d');
                var gradient = ctx.createLinearGradient(0, 0, 0, 400);
                gradient.addColorStop(0, 'rgba(124, 58, 237, 0.4)');   
                gradient.addColorStop(1, 'rgba(124, 58, 237, 0)');
                var chartLabels = {!! json_encode($chartData['labels'] ?? []) !!};
                var chartValues = {!! json_encode($chartData['data'] ?? []) !!};
                window.dashboardChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: chartLabels,
                        datasets: [{
                            label: 'Volume Aprovado',
                            data: chartValues,
                            borderColor: '#7C3AED',
                            backgroundColor: gradient,
                            borderWidth: 2,
                            pointBackgroundColor: '#0B0E14',
                            pointBorderColor: '#7C3AED',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false }, tooltip: { backgroundColor: '#11151E', titleColor: '#94A3B8', bodyColor: '#F8FAFC', borderColor: 'rgba(255,255,255,0.1)', borderWidth: 1, padding: 12, displayColors: false, callbacks: { label: function(context) { return 'R$ ' + context.parsed.y.toLocaleString('pt-BR', {minimumFractionDigits: 2}); } } } },
                        scales: { y: { display: true, grid: { color: 'rgba(255,255,255,0.03)', drawBorder: false }, ticks: { color: '#94A3B8', font: { size: 11 } } }, x: { display: true, grid: { display: false, drawBorder: false }, ticks: { color: '#94A3B8', font: { size: 11 } } } },
                        interaction: { mode: 'index', intersect: false }
                    }
                });
            }
        </script>

    </div>
</div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('general/css/daterangepicker.css') }}">
    <style>
        /* Dark Theme overrides for Daterangepicker */
        .daterangepicker {
            background-color: #111218;
            border: 1px solid rgba(255,255,255,0.1);
            color: #F0F0F5;
            box-shadow: 0 10px 40px rgba(0,0,0,0.8);
            font-family: inherit;
        }
        .daterangepicker .calendar-table {
            background-color: #111218;
            border-color: rgba(255,255,255,0.1);
        }
        .daterangepicker .calendar-table th, .daterangepicker .calendar-table td {
            color: #A1A1AA;
        }
        .daterangepicker td.off, .daterangepicker td.off.in-range, .daterangepicker td.off.start-date, .daterangepicker td.off.end-date {
            background-color: #0B0B0F;
            border-color: transparent;
            color: #3F3F46;
        }
        .daterangepicker td.available:hover, .daterangepicker th.available:hover {
            background-color: #1F202B;
            border-color: transparent;
            color: #F0F0F5;
        }
        .daterangepicker td.in-range {
            background-color: rgba(124,58,237,0.15);
            border-color: transparent;
            color: #F0F0F5;
        }
        .daterangepicker td.active, .daterangepicker td.active:hover {
            background-color: #7C3AED;
            border-color: transparent;
            color: #fff;
        }
        .daterangepicker .ranges li {
            background-color: #111218;
            color: #A1A1AA;
            transition: 0.2s ease;
            border-radius: 6px;
            margin: 4px;
        }
        .daterangepicker .ranges li:hover {
            background-color: #1F202B;
            color: #F0F0F5;
        }
        .daterangepicker .ranges li.active {
            background-color: rgba(124,58,237,0.2);
            color: #7C3AED;
            border: 1px solid rgba(124,58,237,0.3);
        }
        .daterangepicker .drp-buttons {
            border-top: 1px solid rgba(255,255,255,0.1);
            background-color: #0B0B0F;
        }
        .daterangepicker .cancelBtn {
            background: transparent;
            border: 1px solid rgba(255,255,255,0.1);
            color: #A1A1AA;
        }
        .daterangepicker .cancelBtn:hover {
            background: #1F202B;
            color: #F0F0F5;
        }
        .daterangepicker .applyBtn {
            background: #7C3AED;
            border: none;
            color: #fff;
        }
        .daterangepicker .applyBtn:hover {
            background: #6D28D9;
        }
        .daterangepicker::before, .daterangepicker::after {
            display: none; /* Hide the arrow pointers to keep it clean */
        }
        .daterangepicker select.monthselect, .daterangepicker select.yearselect {
            background: #1F202B;
            border: 1px solid rgba(255,255,255,0.1);
            color: #F0F0F5;
            padding: 2px 4px;
            border-radius: 4px;
        }
    </style>
@endpush

@push('scripts')
<script src="{{ asset('general/js/moment.js') }}"></script>
<script src="{{ asset('general/js/daterangepicker.min.js') }}"></script>
<script src="{{ asset('general/js/chartjs.js') }}"></script>
<script>
    $(function() {
        if ($('#dashboard-daterange').length) {
            $('#dashboard-daterange').daterangepicker({
                opens: 'left',
                startDate: '{{ isset($startDate) ? $startDate->format("d/m/Y") : date("d/m/Y") }}',
                endDate: '{{ isset($endDate) ? $endDate->format("d/m/Y") : date("d/m/Y") }}',
                ranges: {
                    'Hoje': [moment(), moment()],
                    'Ontem': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'Últimos 7 dias': [moment().subtract(6, 'days'), moment()],
                    'Últimos 30 dias': [moment().subtract(29, 'days'), moment()],
                    'Este mês': [moment().startOf('month'), moment().endOf('month')],
                    'Mês anterior': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
                },
                locale: {
                    format: 'DD/MM/YYYY',
                    applyLabel: "Aplicar",
                    cancelLabel: "Cancelar",
                    customRangeLabel: "Personalizado",
                    daysOfWeek: ["Dom","Seg","Ter","Qua","Qui","Sex","Sáb"],
                    monthNames: ["Janeiro","Fevereiro","Março","Abril","Maio","Junho","Julho","Agosto","Setembro","Outubro","Novembro","Dezembro"],
                }
            }, function(start, end, label) {
                var isCustom = (label === 'Personalizado' || label === 'Custom Range');
                if (isCustom) {
                    $('#dashboard-daterange span').html(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));
                } else {
                    $('#dashboard-daterange span').html(label);
                }
                
                $('#start_date').val(start.format('YYYY-MM-DD'));
                $('#end_date').val(end.format('YYYY-MM-DD'));
                
                // Fetch new dashboard via AJAX to avoid full reload
                var btnIcon = $('#dashboard-daterange i.fa-calendar');
                btnIcon.removeClass('fa-calendar far').addClass('fa-spinner fa-spin fas');
                
                $.ajax({
                    url: '{{ route("user.dashboard") }}',
                    data: {
                        start_date: start.format('YYYY-MM-DD'),
                        end_date: end.format('YYYY-MM-DD')
                    },
                    success: function(response) {
                        var newGrid = $(response).find('.v2-dashboard-grid').html();
                        $('.v2-dashboard-grid').html(newGrid);
                        btnIcon.removeClass('fa-spinner fa-spin fas').addClass('fa-calendar far');
                    },
                    error: function() {
                        window.location.href = '{{ route("user.dashboard") }}?start_date=' + start.format('YYYY-MM-DD') + '&end_date=' + end.format('YYYY-MM-DD');
                    }
                });
            });
            
            // On load initialization label update
            var startParam = '{{ request("start_date") }}';
            var endParam = '{{ request("end_date") }}';
            if(!startParam && !endParam) {
                $('#dashboard-daterange span').html('Últimos 7 dias');
            } else if (startParam == moment().format('YYYY-MM-DD') && endParam == moment().format('YYYY-MM-DD')) {
                $('#dashboard-daterange span').html('Hoje');
            } else if (startParam == moment().subtract(1, 'days').format('YYYY-MM-DD') && endParam == moment().subtract(1, 'days').format('YYYY-MM-DD')) {
                $('#dashboard-daterange span').html('Ontem');
            } else if (startParam == moment().startOf('month').format('YYYY-MM-DD') && endParam == moment().endOf('month').format('YYYY-MM-DD')) {
                $('#dashboard-daterange span').html('Este mês');
            }
        }
    });
</script>

@endpush
