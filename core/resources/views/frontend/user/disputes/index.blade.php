@extends('frontend.layouts.user-v2')

@section('title', $pageTitle)

@section('content')

<style>
    /* Enterprise Workspace Grid Optimization */
    .disputes-workspace {
        display: flex;
        flex-direction: column;
        height: calc(100vh - 100px);
        overflow: hidden;
    }
    .disputes-header, .disputes-kpis {
        flex-shrink: 0;
    }
    .disputes-main-row {
        flex: 1;
        min-height: 0; 
        display: flex;
        gap: 20px;
        margin-top: 20px;
    }
    .disputes-col-left {
        flex: 0 0 72%;
        max-width: 72%;
        display: flex;
        flex-direction: column;
        min-height: 0;
    }
    .disputes-col-right {
        flex: 0 0 calc(28% - 20px);
        max-width: calc(28% - 20px);
        display: flex;
        flex-direction: column;
        min-height: 0;
        overflow: hidden; /* we want to try to fit it entirely */
    }
    .sidebar-scroll-area {
        flex: 1;
        overflow-y: auto;
        padding-right: 4px;
        display: flex;
        flex-direction: column;
        gap: 16px;
    }
    
    /* Table layout */
    .disputes-table-card {
        flex: 1;
        display: flex;
        flex-direction: column;
        min-height: 0;
        margin-bottom: 0 !important;
    }
    .disputes-table-wrapper {
        flex: 1;
        overflow-y: auto;
        min-height: 0;
    }
    
    /* Densify KPIs */
    .kpi-dense {
        padding: 12px 16px 16px 16px !important;
        display: flex;
        flex-direction: column;
        justify-content: center;
        height: 90px;
    }
    .kpi-title {
        font-size: 0.65rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--ds-text-muted);
    }
    
    /* Microinteractions & Dense Sidebar */
    .sb-card {
        margin-bottom: 0 !important;
    }
    .sb-header {
        padding: 12px 16px !important;
        border-bottom: 1px solid var(--ds-border);
    }
    .sb-title {
        font-size: 0.8rem !important;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--ds-text-muted);
    }
    .sb-body {
        padding: 14px 16px !important;
    }
    
    /* Accordion FAQ */
    .faq-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 16px;
        border-bottom: 1px solid var(--ds-border);
        cursor: pointer;
        transition: background-color 0.2s ease, padding-left 0.2s ease;
        font-size: 0.75rem;
        color: var(--ds-text-main);
    }
    .faq-item:last-child { border-bottom: none; }
    .faq-item:hover {
        background-color: var(--ds-surface-hover);
        padding-left: 20px;
        color: var(--ds-primary);
    }
    .faq-item .faq-icon {
        font-size: 0.65rem;
        color: var(--ds-text-muted);
        transition: color 0.2s;
    }
    .faq-item:hover .faq-icon { color: var(--ds-primary); }

    /* Action Links */
    .action-link {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 16px;
        border-radius: 6px;
        cursor: pointer;
        text-decoration: none !important;
        transition: all 0.2s ease;
        margin-bottom: 4px;
        font-size: 0.75rem;
        color: var(--ds-text-main);
    }
    .action-link:last-child { margin-bottom: 0; }
    .action-link:hover {
        background-color: var(--ds-surface-hover);
        transform: translateX(2px);
    }
    .action-link-left {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .action-link-icon {
        width: 24px;
        height: 24px;
        border-radius: 6px;
        background: rgba(124, 58, 237, 0.08);
        color: var(--ds-primary);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.7rem;
    }
    .action-link:hover .action-link-icon {
        background: var(--ds-primary);
        color: #fff;
    }
    .action-chevron {
        font-size: 0.65rem;
        color: var(--ds-text-muted);
    }
    
    /* Timeline */
    .tl-item {
        display: flex;
        position: relative;
        padding-bottom: 12px;
    }
    .tl-item:last-child { padding-bottom: 0; }
    .tl-item::before {
        content: '';
        position: absolute;
        left: 3px;
        top: 10px;
        bottom: -2px;
        width: 2px;
        background: var(--ds-border);
    }
    .tl-item:last-child::before { display: none; }
    .tl-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: var(--ds-primary);
        margin-top: 4px;
        margin-right: 12px;
        flex-shrink: 0;
        z-index: 1;
        box-shadow: 0 0 0 3px var(--ds-surface);
    }
    .tl-text {
        font-size: 0.75rem;
        font-weight: 500;
        color: var(--ds-text-main);
        line-height: 1.2;
    }
    
    /* Button Secondary */
    .btn-true-secondary {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        background: transparent;
        border: 1px solid var(--ds-border-medium, rgba(255,255,255,.08));
        color: var(--ds-text-main);
        min-height: 36px;
        padding: 0 12px;
        border-radius: 8px;
        font-size: 0.78rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .btn-true-secondary:hover {
        background: var(--ds-surface-hover);
        border-color: var(--ds-text-muted);
    }
    .btn-true-secondary:active {
        transform: scale(0.98);
    }

    @media (max-width: 1399px) {
        .disputes-workspace { height: auto; overflow: visible; }
        .disputes-main-row { flex-direction: column; }
        .disputes-col-left { flex: 0 0 100%; max-width: 100%; height: 600px; }
        .disputes-col-right { flex: 0 0 100%; max-width: 100%; overflow: visible; }
        .sidebar-scroll-area { overflow: visible; }
    }

    @media (max-width: 768px) {
        .disputes-workspace {
            height: auto !important;
            overflow: visible !important;
            gap: 12px !important;
        }

        .disputes-header {
            display: grid !important;
            grid-template-columns: 1fr !important;
            gap: 12px !important;
            align-items: stretch !important;
            margin-bottom: 12px !important;
        }

        .disputes-header .v2-btn-outline {
            width: 100% !important;
            min-height: 42px !important;
            justify-content: center !important;
        }

        .disputes-kpis {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: 9px !important;
        }

        .kpi-dense {
            height: auto !important;
            min-height: 82px !important;
            padding: 10px !important;
            border-radius: 10px !important;
            justify-content: flex-start !important;
        }

        .kpi-dense .v2-kpi-header {
            margin-bottom: 6px !important;
        }

        .kpi-dense .v2-kpi-icon {
            width: 22px !important;
            height: 22px !important;
            border-radius: 7px !important;
            font-size: .62rem !important;
        }

        .kpi-dense .v2-kpi-value {
            font-size: 1.1rem !important;
        }

        .kpi-dense > div:last-child {
            font-size: .66rem !important;
            line-height: 1.25 !important;
        }

        .kpi-title {
            font-size: .62rem !important;
            line-height: 1.25 !important;
            white-space: normal !important;
        }

        .disputes-main-row {
            margin-top: 12px !important;
            gap: 12px !important;
        }

        .disputes-col-left {
            height: auto !important;
            max-width: 100% !important;
        }

        .disputes-col-right {
            max-width: 100% !important;
            overflow: visible !important;
        }

        .disputes-table-card {
            min-height: 0 !important;
        }

        .disputes-table-wrapper {
            min-height: 0 !important;
            overflow-x: auto !important;
            overflow-y: visible !important;
        }

        .disputes-table-wrapper table {
            min-width: 0 !important;
        }

        .disputes-table-wrapper tbody tr td[colspan] > div {
            min-height: 220px !important;
            padding: 24px 14px !important;
        }

        .sidebar-scroll-area {
            gap: 12px !important;
            padding-right: 0 !important;
        }

        .disputes-col-right .v2-panel {
            background: var(--ds-bg-card) !important;
            border: 1px solid var(--ds-border-light) !important;
            border-radius: 10px !important;
            overflow: hidden !important;
        }

        .sb-header {
            padding: 12px 14px !important;
        }

        .sb-body {
            padding: 12px 14px !important;
        }

        .sb-card {
            margin: 0 !important;
        }

        .tl-item {
            padding-bottom: 9px !important;
        }

        .faq-item,
        .action-link {
            padding: 11px 14px !important;
            border-bottom-color: var(--ds-border-light) !important;
        }

        .action-link {
            border: 1px solid transparent !important;
            margin-bottom: 6px !important;
        }

        .action-link:hover,
        .faq-item:hover {
            transform: none !important;
            padding-left: 14px !important;
        }

        .btn-true-secondary {
            min-height: 36px !important;
            border-radius: 8px !important;
            background: rgba(255,255,255,.025) !important;
        }
    }
</style>

<div class="disputes-workspace">

    {{-- Header --}}
    <div class="disputes-header" style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 20px;">
        <div>
            <h2 style="font-size: 1.2rem; font-weight: 700; color: var(--ds-text-main); margin-bottom: 4px; line-height: 1;">{{ $pageTitle }}</h2>
            <p style="font-size: 0.8rem; color: var(--ds-text-muted); margin: 0;">Acompanhe contestações e envie documentos em um único lugar.</p>
        </div>
        <div>
            <button type="button" class="v2-btn-outline" style="font-size: 0.75rem; padding: 6px 16px;" data-bs-toggle="modal" data-bs-target="#modal-entender-disputas">
                <i class="fas fa-book-open" style="margin-right: 6px;"></i> Central de Ajuda
            </button>
        </div>
    </div>

    {{-- KPIs Grid --}}
    <div class="disputes-kpis v2-kpi-grid" style="gap: 16px;">
        <div class="v2-kpi-card kpi-dense">
            <div class="v2-kpi-header" style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                <div class="v2-kpi-icon" style="background: rgba(124, 58, 237, 0.1); color: var(--ds-primary); width: 24px; height: 24px; font-size: 0.7rem;">
                    <i class="fas fa-folder-open"></i>
                </div>
                <span class="kpi-title">CASOS ABERTOS</span>
            </div>
            <div class="v2-kpi-value" style="font-size: 1.5rem; font-weight: 700; line-height: 1;">
                <span>{{ $kpiOpen }}</span>
            </div>
            <div style="font-size: 0.7rem; color: var(--ds-text-muted); margin-top: 4px;">Casos em andamento</div>
        </div>

        <div class="v2-kpi-card kpi-dense">
            <div class="v2-kpi-header" style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                <div class="v2-kpi-icon" style="background: rgba(245, 158, 11, 0.1); color: var(--ds-warning); width: 24px; height: 24px; font-size: 0.7rem;">
                    <i class="fas fa-clock"></i>
                </div>
                <span class="kpi-title">AGUARDANDO AÇÃO</span>
            </div>
            <div class="v2-kpi-value" style="font-size: 1.5rem; font-weight: 700; line-height: 1;">
                <span>{{ $kpiWaitingMe }}</span>
            </div>
            <div style="font-size: 0.7rem; color: var(--ds-text-muted); margin-top: 4px;">Aguardando documentos</div>
        </div>

        <div class="v2-kpi-card kpi-dense">
            <div class="v2-kpi-header" style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                <div class="v2-kpi-icon" style="background: rgba(239, 68, 68, 0.1); color: var(--ds-danger); width: 24px; height: 24px; font-size: 0.7rem;">
                    <i class="fas fa-lock"></i>
                </div>
                <span class="kpi-title">RETIDO EM GARANTIA</span>
                <div style="margin-left: auto;">
                    <i class="fas fa-eye-slash toggle-dash-balance" style="cursor: pointer; opacity: 0.6; color: var(--ds-text-muted); font-size: 0.75rem;" title="Alternar visibilidade"></i>
                </div>
            </div>
            <div class="v2-kpi-value" style="display: flex; align-items: center; font-size: 1.5rem; font-weight: 700; line-height: 1;">
                <span class="dash-balance-visible" style="display: none;">R$ {{ number_format($kpiRetained / 100, 2, ',', '.') }}</span>
                <span class="dash-balance-hidden" style="letter-spacing: 0.1em; font-size: 1.5rem;">••••••</span>
            </div>
            <div style="font-size: 0.7rem; color: var(--ds-text-muted); margin-top: 4px;">Saldo temporariamente retido</div>
        </div>

        <div class="v2-kpi-card kpi-dense">
            <div class="v2-kpi-header" style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                <div class="v2-kpi-icon" style="background: rgba(16, 185, 129, 0.1); color: var(--ds-success); width: 24px; height: 24px; font-size: 0.7rem;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <span class="kpi-title">ENCERRADOS</span>
            </div>
            <div class="v2-kpi-value" style="font-size: 1.5rem; font-weight: 700; line-height: 1;">
                <span>{{ $kpiClosed }}</span>
            </div>
            <div style="font-size: 0.7rem; color: var(--ds-text-muted); margin-top: 4px;">Histórico concluído</div>
        </div>
    </div>

    {{-- Main Workspace Area --}}
    <div class="disputes-main-row">
        
        {{-- Left Column (72%) --}}
        <div class="disputes-col-left">
            <div class="v2-panel disputes-table-card">
                <div class="v2-panel-header sb-header" style="flex-shrink: 0;">
                    <h3 class="v2-panel-title" style="font-size: 0.9rem; font-weight: 600;">Histórico de Disputas</h3>
                </div>
                
                <div class="disputes-table-wrapper">
                    <table class="table v2-table mb-0" style="width: 100%;">
                        <thead style="position: sticky; top: 0; background: var(--ds-surface); z-index: 10; font-size: 0.7rem;">
                            <tr>
                                <th style="padding-left: 20px;">ID / Data</th>
                                <th>Tipo</th>
                                <th>Status</th>
                                <th>Valor / Retido</th>
                                @if($disputes->isNotEmpty())
                                <th>Ação Necessária</th>
                                @endif
                                <th class="text-end" style="padding-right: 20px;">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($disputes as $dispute)
                            <tr style="cursor: pointer; transition: background 0.2s;" onmouseover="this.style.background='var(--ds-surface-hover)'" onmouseout="this.style.background='transparent'" onclick="window.location='{{ route('user.disputes.show', $dispute->uuid) }}'">
                                <td style="padding-left: 20px; vertical-align: middle;">
                                    <div style="font-weight: 600; color: var(--ds-text-main); font-size: 0.75rem; letter-spacing: 0.3px;">DSP-{{ str_pad($dispute->id, 6, '0', STR_PAD_LEFT) }}</div>
                                    <div style="font-size: 0.7rem; color: var(--ds-text-muted); margin-top: 2px;">{{ $dispute->created_at->format('d/m/Y H:i') }}</div>
                                </td>
                                <td style="vertical-align: middle;">
                                    <div style="display: flex; align-items: center; gap: 6px;">
                                        <div style="width: 20px; height: 20px; border-radius: 50%; background: rgba(0,229,200,0.1); color: var(--ds-pix); display: flex; align-items: center; justify-content: center; font-size: 0.6rem;">
                                            <i class="fas fa-qrcode"></i>
                                        </div>
                                        <span style="font-size: 0.75rem; font-weight: 500; color: var(--ds-text-main);">MED Pix</span>
                                    </div>
                                </td>
                                <td style="vertical-align: middle;">
                                    <span class="v2-badge" style="background: var(--ds-surface-hover); color: var(--ds-text-main); border: 1px solid var(--ds-border); font-size: 0.7rem; padding: 2px 6px;">{{ $dispute->status->label() }}</span>
                                </td>
                                <td style="vertical-align: middle;">
                                    <div style="font-weight: 600; color: var(--ds-text-main); font-size: 0.75rem;">R$ {{ number_format($dispute->amount_cents / 100, 2, ',', '.') }}</div>
                                    @if($dispute->retained_amount_cents > 0)
                                        <div style="font-size: 0.7rem; color: var(--ds-danger); margin-top: 2px;"><i class="fas fa-lock" style="font-size: 0.6rem; margin-right: 2px;"></i> Retido: R$ {{ number_format($dispute->retained_amount_cents / 100, 2, ',', '.') }}</div>
                                    @else
                                        <div style="font-size: 0.7rem; color: var(--ds-text-muted); margin-top: 2px;">Sem retenção</div>
                                    @endif
                                </td>
                                <td style="vertical-align: middle;">
                                    @if($dispute->status->value === 'waiting_merchant_docs')
                                        <span style="font-size: 0.7rem; color: var(--ds-warning); font-weight: 600; background: rgba(245,158,11,0.1); padding: 2px 6px; border-radius: 4px;"><i class="fas fa-exclamation-circle me-1"></i> Enviar documentos</span>
                                    @elseif($dispute->status->value === 'won')
                                        <span style="font-size: 0.7rem; color: var(--ds-success); font-weight: 500;"><i class="fas fa-check me-1"></i> Resolvido</span>
                                    @else
                                        <span style="font-size: 0.7rem; color: var(--ds-text-muted);">Aguardando análise</span>
                                    @endif
                                </td>
                                <td class="text-end" style="padding-right: 20px; vertical-align: middle;">
                                    <div style="width: 24px; height: 24px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; background: var(--ds-surface-hover); color: var(--ds-text-main); font-size: 0.7rem;">
                                        <i class="fas fa-chevron-right"></i>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" style="border: none; padding: 0;">
                                    <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 400px; height: 100%;">
                                        <div style="width: 48px; height: 48px; background: var(--ds-surface-hover); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 16px;">
                                            <i class="fas fa-shield-alt" style="font-size: 1.25rem; color: var(--ds-text-muted);"></i>
                                        </div>
                                        <div style="font-weight: 600; color: var(--ds-text-main); font-size: 0.95rem;">Nenhuma disputa encontrada</div>
                                        <div style="font-size: 0.8rem; color: var(--ds-text-muted); text-align: center; max-width: 300px; margin: 4px 0 20px; line-height: 1.5;">Quando surgir uma disputa ou solicitação de reembolso, ela aparecerá automaticamente aqui.</div>
                                        <button type="button" class="btn-true-secondary" style="width: auto; padding: 6px 20px;" onclick="window.location.reload();">
                                            <i class="fas fa-sync-alt" style="margin-right: 6px;"></i> Atualizar
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($disputes->hasPages())
                    <div class="v2-panel-footer" style="border-top: 1px solid var(--ds-border); padding: 12px 20px; flex-shrink: 0;">
                        {{ $disputes->links() }}
                    </div>
                @endif
            </div>
        </div>

        {{-- Right Column (28%) --}}
        <div class="disputes-col-right">
            <div class="sidebar-scroll-area">
                
                {{-- Card 1: Resumo da Conta --}}
                <div class="v2-panel sb-card">
                    <div class="v2-panel-header sb-header">
                        <h3 class="v2-panel-title sb-title">Resumo da Conta</h3>
                    </div>
                    <div class="v2-panel-body sb-body">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span style="font-size: 0.75rem; color: var(--ds-text-muted);">Status</span>
                            @if($kpiOpen == 0)
                                <span style="font-size: 0.75rem; font-weight: 600; color: var(--ds-success);"><i class="fas fa-shield-check me-1"></i> Protegido</span>
                            @else
                                <span style="font-size: 0.75rem; font-weight: 600; color: var(--ds-warning);"><i class="fas fa-exclamation-circle me-1"></i> Disputas ativas</span>
                            @endif
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span style="font-size: 0.75rem; color: var(--ds-text-muted);">Saldo Retido</span>
                            <span style="font-size: 0.75rem; font-weight: 600; color: var(--ds-text-main);">R$ {{ number_format($kpiRetained / 100, 2, ',', '.') }}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span style="font-size: 0.75rem; color: var(--ds-text-muted);">Doc. Pendentes</span>
                            <span style="font-size: 0.75rem; font-weight: 600; color: var(--ds-text-main);">{{ $kpiWaitingMe > 0 ? $kpiWaitingMe : 'Nenhum' }}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="font-size: 0.75rem; color: var(--ds-text-muted);">Última Disputa</span>
                            <span style="font-size: 0.75rem; font-weight: 600; color: var(--ds-text-main);">{{ $disputes->first() ? $disputes->first()->created_at->format('d/m/Y') : 'Nunca' }}</span>
                        </div>
                    </div>
                </div>

                {{-- Card 2: Como funciona --}}
                <div class="v2-panel sb-card">
                    <div class="v2-panel-header sb-header">
                        <h3 class="v2-panel-title sb-title">Como funciona</h3>
                    </div>
                    <div class="v2-panel-body sb-body" style="padding-bottom: 16px !important;">
                        <div class="tl-item">
                            <div class="tl-dot"></div>
                            <div class="tl-text">Contestação</div>
                        </div>
                        <div class="tl-item">
                            <div class="tl-dot"></div>
                            <div class="tl-text">Documentação</div>
                        </div>
                        <div class="tl-item">
                            <div class="tl-dot"></div>
                            <div class="tl-text">Análise</div>
                        </div>
                        <div class="tl-item">
                            <div class="tl-dot"></div>
                            <div class="tl-text">Resultado</div>
                        </div>
                        
                        <div style="margin-top: 16px;">
                            <button type="button" class="btn-true-secondary" data-bs-toggle="modal" data-bs-target="#modal-entender-disputas">
                                Saiba mais
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Card 3: Perguntas Rápidas (Accordion) --}}
                <div class="v2-panel sb-card">
                    <div class="v2-panel-header sb-header">
                        <h3 class="v2-panel-title sb-title">Perguntas Rápidas</h3>
                    </div>
                    <div class="v2-panel-body" style="padding: 0 !important;">
                        <div class="faq-item" data-bs-toggle="modal" data-bs-target="#modal-entender-disputas">
                            <span>Quanto tempo dura uma disputa?</span>
                            <i class="fas fa-chevron-right faq-icon"></i>
                        </div>
                        <div class="faq-item" data-bs-toggle="modal" data-bs-target="#modal-entender-disputas">
                            <span>Meu saldo foi perdido?</span>
                            <i class="fas fa-chevron-right faq-icon"></i>
                        </div>
                        <div class="faq-item" data-bs-toggle="modal" data-bs-target="#modal-entender-disputas">
                            <span>Como envio documentos?</span>
                            <i class="fas fa-chevron-right faq-icon"></i>
                        </div>
                        <div class="faq-item" data-bs-toggle="modal" data-bs-target="#modal-entender-disputas">
                            <span>Posso acompanhar pelo celular?</span>
                            <i class="fas fa-chevron-right faq-icon"></i>
                        </div>
                    </div>
                </div>

                {{-- Card 4: Links Úteis --}}
                <div class="v2-panel sb-card">
                    <div class="v2-panel-header sb-header">
                        <h3 class="v2-panel-title sb-title">Links Úteis</h3>
                    </div>
                    <div class="v2-panel-body sb-body" style="padding: 10px 12px !important;">
                        <a href="#" class="action-link">
                            <div class="action-link-left">
                                <div class="action-link-icon"><i class="fas fa-book-open"></i></div>
                                <span>Central de Ajuda</span>
                            </div>
                            <i class="fas fa-chevron-right action-chevron"></i>
                        </a>
                        <a href="javascript:void(0)" onclick="if(typeof dsChatUI !== 'undefined') dsChatUI.toggleWidget();" class="action-link">
                            <div class="action-link-left">
                                <div class="action-link-icon"><i class="fas fa-headset"></i></div>
                                <span>Suporte OriginPay</span>
                            </div>
                            <i class="fas fa-chevron-right action-chevron"></i>
                        </a>
                        <a href="{{ route('api-docs.index') }}" target="_blank" class="action-link">
                            <div class="action-link-left">
                                <div class="action-link-icon"><i class="fas fa-file-code"></i></div>
                                <span>Documentação da API</span>
                            </div>
                            <i class="fas fa-chevron-right action-chevron"></i>
                        </a>
                    </div>
                </div>
                
            </div>
        </div>

    </div>
</div>

{{-- Modal Entender Disputas --}}
<div class="modal fade" id="modal-entender-disputas" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content v2-modal-content" style="background-color: var(--ds-bg-card, #11151E) !important; border: 1px solid var(--ds-border);">
            <div class="modal-header v2-modal-header" style="border-bottom: 1px solid var(--ds-border);">
                <h5 class="modal-title" style="font-size: 0.95rem; font-weight: 600; color: var(--ds-text-main);">Entendendo Disputas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: invert(1) grayscale(100%) brightness(200%);"></button>
            </div>
            <div class="modal-body" style="padding: 20px;">
                <p style="font-size: 0.8rem; color: var(--ds-text-muted); margin-bottom: 12px; line-height: 1.5;">Uma disputa (ou chargeback / MED) ocorre quando um portador de cartão ou remetente Pix contata o próprio banco relatando que não reconhece a compra ou não recebeu o produto.</p>
                <p style="font-size: 0.8rem; color: var(--ds-text-muted); margin-bottom: 12px; line-height: 1.5;">Como intermediadora de pagamentos, a OriginPay é obrigada pelas bandeiras e pelo Banco Central a reter preventivamente o valor da transação e iniciar um processo de investigação.</p>
                <p style="font-size: 0.8rem; color: var(--ds-text-muted); margin-bottom: 0; line-height: 1.5;"><strong>Não é uma perda garantida.</strong> Forneça as provas de entrega rapidamente e nossa equipe montará uma defesa para reverter o estorno e devolver o valor retido.</p>
            </div>
            <div class="modal-footer" style="border-top: none; padding: 12px 20px;">
                <button type="button" class="btn-true-secondary" data-bs-dismiss="modal" style="width: 100%;">Entendi</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggleBtns = document.querySelectorAll('.toggle-dash-balance');
        const visibleEls = document.querySelectorAll('.dash-balance-visible');
        const hiddenEls = document.querySelectorAll('.dash-balance-hidden');
        
        let isHidden = localStorage.getItem('hideBalance') !== 'false';

        function applyBalanceVisibility() {
            toggleBtns.forEach(btn => btn.className = isHidden ? 'fas fa-eye-slash toggle-dash-balance' : 'fas fa-eye toggle-dash-balance');
            visibleEls.forEach(el => el.style.display = isHidden ? 'none' : 'block');
            hiddenEls.forEach(el => el.style.display = isHidden ? 'block' : 'none');
        }

        applyBalanceVisibility();

        toggleBtns.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation(); 
                isHidden = !isHidden;
                localStorage.setItem('hideBalance', isHidden);
                applyBalanceVisibility();
                
                // Keep sidebar toggle in sync
                const sidebarBtn = document.getElementById('toggle-balance-btn-v2');
                if(sidebarBtn) {
                    sidebarBtn.click();
                }
            });
        });
    });
</script>
@endsection
