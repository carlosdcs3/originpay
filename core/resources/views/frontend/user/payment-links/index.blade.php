@extends('frontend.layouts.user-v2')
@section('title', 'Links de Pagamento')

@section('styles')
<style>
    /* New styles using DSv2 logic */
    .pl-shell { display: flex; flex-direction: column; gap: 20px; }
    
    /* KPIs */
    .op-stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 8px; }
    .op-kpi-card { background: var(--ds-bg-card); border: 1px solid var(--ds-border-light); border-radius: 12px; padding: 16px; display: flex; flex-direction: column; position: relative; overflow: hidden; }
    .op-kpi-icon { width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; margin-bottom: 12px; }
    .op-kpi-icon.bg-active { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
    .op-kpi-icon.bg-paid { background: rgba(34, 197, 94, 0.1); color: #22c55e; }
    .op-kpi-icon.bg-expired { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
    .op-kpi-icon.bg-conversion { background: rgba(168, 85, 247, 0.1); color: #a855f7; }
    .op-kpi-icon.bg-revenue { background: rgba(16, 185, 129, 0.1); color: #10b981; }
    .op-kpi-title { font-size: 0.85rem; color: var(--ds-text-muted); font-weight: 500; margin-bottom: 4px; }
    .op-kpi-val { font-size: 1.4rem; font-weight: 700; color: var(--ds-text-main); }
    .op-kpi-trend { font-size: 0.75rem; margin-top: 8px; font-weight: 500; }
    
    /* Filters */
    .pl-filters { display: flex; gap: 12px; margin-bottom: 8px; flex-wrap: wrap; }
    .pl-filters input, .pl-filters select { background: var(--ds-bg-light); border: 1px solid var(--ds-border-light); border-radius: 8px; padding: 8px 12px; color: var(--ds-text-main); font-size: 0.85rem; outline: none; }
    .pl-filters input:focus, .pl-filters select:focus { border-color: var(--ds-primary); }
    .pl-filters select option { background-color: var(--ds-bg-card); color: var(--ds-text-main); }
    
    /* Table */
    .pl-card { background: var(--ds-bg-card); border: 1px solid var(--ds-border-light); border-radius: 12px; overflow: hidden; }
    .pl-table { width: 100%; border-collapse: collapse; text-align: left; }
    .pl-table th { padding: 14px 16px; font-size: 0.75rem; color: var(--ds-text-muted); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1px solid var(--ds-border-light); }
    .pl-table td { padding: 16px; border-bottom: 1px solid var(--ds-border-light); vertical-align: middle; }
    .pl-table tr:last-child td { border-bottom: none; }
    
    .pl-main { color: var(--ds-text-main); font-weight: 600; font-size: 0.9rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 250px; }
    .pl-sub { color: var(--ds-text-muted); font-size: 0.75rem; margin-top: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 250px; }
    
    .pl-badge { display: inline-flex; align-items: center; justify-content: center; padding: 4px 10px; border-radius: 999px; font-size: 0.7rem; font-weight: 600; }
    .pl-badge.pending, .pl-badge.awaiting_payment, .pl-badge.active { color: #f59e0b; background: rgba(245, 158, 11, 0.1); }
    .pl-badge.paid { color: #22c55e; background: rgba(34, 197, 94, 0.1); }
    .pl-badge.expired, .pl-badge.canceled, .pl-badge.failed { color: #94a3b8; background: rgba(148, 163, 184, 0.1); }
    
    .pl-row-actions { display: flex; gap: 8px; justify-content: flex-end; }
    .pl-icon { width: 32px; height: 32px; border-radius: 6px; border: 1px solid var(--ds-border-light); background: transparent; color: var(--ds-text-muted); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; cursor: pointer; transition: 0.2s; }
    .pl-icon:hover { background: var(--ds-border-light); color: var(--ds-text-main); }
    
    /* Empty */
    .pl-empty-state { flex-grow: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; min-height: 400px; background: var(--ds-bg-card); border-radius: 12px; border: 1px dashed var(--ds-border-medium); padding: 40px; margin-top: 10px; }
    .pl-empty-icon { width: 64px; height: 64px; background: rgba(124, 58, 237, 0.1); color: var(--ds-primary); border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; margin-bottom: 20px; }
    .pl-empty-title { font-size: 1.25rem; font-weight: 700; color: var(--ds-text-main); margin-bottom: 8px; }
    .pl-empty-desc { color: var(--ds-text-muted); font-size: 0.9rem; margin-bottom: 24px; max-width: 400px; line-height: 1.5; }

    @media (max-width: 768px) {
        .pl-shell { gap: 12px !important; }
        .pl-actions,
        .pl-actions .v2-btn-primary { width: 100% !important; }
        .pl-actions .v2-btn-primary {
            height: 40px !important;
            border-radius: 8px !important;
            justify-content: center !important;
        }
        .pl-filters {
            display: grid !important;
            grid-template-columns: 1fr !important;
            gap: 8px !important;
            margin-bottom: 0 !important;
        }
        .pl-filters input,
        .pl-filters select {
            width: 100% !important;
            height: 40px !important;
            min-height: 40px !important;
            padding: 0 12px !important;
            font-size: 0.82rem !important;
            border-radius: 8px !important;
            margin-left: 0 !important;
        }
        .pl-card { border-radius: 10px !important; }
        .pl-table.v2-mobile-card-table tr {
            padding: 10px 12px !important;
            margin-bottom: 10px !important;
            border-radius: 10px !important;
        }
        .pl-table.v2-mobile-card-table td {
            padding: 6px 0 !important;
            min-height: 0 !important;
            align-items: flex-start !important;
        }
        .pl-table.v2-mobile-card-table td::before {
            font-size: 0.64rem !important;
            letter-spacing: 0.04em !important;
            text-transform: uppercase !important;
            min-width: 66px !important;
        }
        .pl-table.v2-mobile-card-table td:nth-child(3),
        .pl-table.v2-mobile-card-table td:nth-child(7) {
            display: none !important;
        }
        .pl-main {
            max-width: 190px !important;
            font-size: 0.82rem !important;
            line-height: 1.25 !important;
        }
        .pl-sub {
            max-width: 190px !important;
            font-size: 0.69rem !important;
            margin-top: 2px !important;
        }
        .pl-badge {
            padding: 3px 8px !important;
            font-size: 0.66rem !important;
        }
        .pl-row-actions {
            justify-content: flex-end !important;
            gap: 6px !important;
            flex-wrap: nowrap !important;
        }
        .pl-icon {
            width: 30px !important;
            height: 30px !important;
            border-radius: 7px !important;
        }
        .pl-empty-state {
            min-height: 220px !important;
            padding: 26px 16px !important;
        }
    }
</style>
@endsection

@section('content')
@php
    $money = fn ($value) => 'R$ ' . number_format((float) $value, 2, ',', '.');
    
    // Get all links for accurate KPIs
    $allLinks = \App\Models\PaymentLink::where('user_id', auth()->id())->get();
    
    $activeLinks = $allLinks->whereIn('status', ['pending', 'awaiting_payment', 'active'])->count();
    $paidLinks = $allLinks->where('status', 'paid')->count();
    $expiredLinks = $allLinks->where('status', 'expired')->count();
    
    $totalReceived = $allLinks->where('status', 'paid')->sum('amount');
    
    // Total received is now sum of converted visits' parent link amount, or just rely on $totalReceived.
    // Conversion rate and total views are calculated in Controller.
@endphp

<div class="pl-shell">
    <div class="v2-page-header" style="margin:0;justify-content:space-between;align-items:center;">
        <div>
            <h1 class="v2-page-title" style="margin-bottom:4px;">Links de pagamento</h1>
            <p class="v2-page-subtitle" style="margin:0;">Gerencie links compartilháveis onde o cliente preenche os próprios dados no checkout público.</p>
        </div>
        <div class="pl-actions">
            <button type="button" class="v2-btn-primary" style="height:38px;padding:0 16px;" data-bs-toggle="modal" data-bs-target="#pl-type-modal"><i class="fas fa-plus" style="margin-right: 6px;"></i> Novo link</button>
        </div>
    </div>

    <!-- KPIs -->
    <div class="v2-kpi-grid" style="flex-shrink: 0; margin: 0; margin-bottom: 8px;">
        <div class="v2-kpi-card" style="border-color: rgba(59,130,246,0.12);">
            <div style="flex: 1;">
                <div class="v2-kpi-header">
                    <div class="v2-kpi-icon" style="background: rgba(59,130,246,0.1); color: #60a5fa;">
                        <i class="fas fa-link"></i>
                    </div>
                    <div class="v2-kpi-title" style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
                        <span>Links ativos</span>
                    </div>
                </div>
                <div class="v2-kpi-value" style="display: flex; align-items: center; height: 32px;">
                    <span style="font-size: 1.5rem;">{{ $activeLinks }}</span>
                </div>
            </div>
            <div class="v2-kpi-trend" style="color: var(--ds-text-muted);">
                <span style="font-weight: 400;">Links aguardando pagamento</span>
            </div>
        </div>

        <div class="v2-kpi-card" style="border-color: rgba(16,185,129,0.12);">
            <div style="flex: 1;">
                <div class="v2-kpi-header">
                    <div class="v2-kpi-icon" style="background: rgba(16,185,129,0.1); color: var(--ds-success);">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="v2-kpi-title" style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
                        <span>Links pagos</span>
                    </div>
                </div>
                <div class="v2-kpi-value" style="display: flex; align-items: center; height: 32px;">
                    <span style="font-size: 1.5rem;">{{ $paidLinks }}</span>
                </div>
            </div>
            <div class="v2-kpi-trend" style="color: var(--ds-text-muted);">
                <span style="font-weight: 400;">Pagamentos recebidos</span>
            </div>
        </div>

        <div class="v2-kpi-card" style="border-color: rgba(245,158,11,0.12);">
            <div style="flex: 1;">
                <div class="v2-kpi-header">
                    <div class="v2-kpi-icon" style="background: rgba(245,158,11,0.1); color: #f59e0b;">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="v2-kpi-title" style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
                        <span>Links expirados</span>
                    </div>
                </div>
                <div class="v2-kpi-value" style="display: flex; align-items: center; height: 32px;">
                    <span style="font-size: 1.5rem;">{{ $expiredLinks }}</span>
                </div>
            </div>
            <div class="v2-kpi-trend" style="color: var(--ds-text-muted);">
                <span style="font-weight: 400;">Perderam a validade</span>
            </div>
        </div>

        <div class="v2-kpi-card" style="border-color: rgba(168,85,247,0.12);">
            <div style="flex: 1;">
                <div class="v2-kpi-header">
                    <div class="v2-kpi-icon" style="background: rgba(168,85,247,0.1); color: #a855f7;">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <div class="v2-kpi-title" style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
                        <span>Conversão</span>
                    </div>
                </div>
                <div class="v2-kpi-value" style="display: flex; align-items: center; height: 32px;">
                    <span style="font-size: 1.5rem;">{{ $globalConversion }}%</span>
                </div>
            </div>
            <div class="v2-kpi-trend" style="color: var(--ds-success);">
                <span style="font-weight: 400;">De todos os links</span>
            </div>
        </div>

        <div class="v2-kpi-card" style="border-color: rgba(124,58,237,0.12);">
            <div style="flex: 1;">
                <div class="v2-kpi-header">
                    <div class="v2-kpi-icon" style="background: rgba(124,58,237,0.1); color: #a78bfa;">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="v2-kpi-title" style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
                        <span>Valor recebido</span>
                    </div>
                </div>
                <div class="v2-kpi-value" style="display: flex; align-items: center; height: 32px;">
                    <span style="font-size: 1.5rem;">{{ $money($totalReceived) }}</span>
                </div>
            </div>
            <div class="v2-kpi-trend" style="color: var(--ds-text-muted);">
                <span style="font-weight: 400;">Total processado</span>
            </div>
        </div>
    </div>

    @if(session('payment_link_url'))
        <div class="pl-card" style="padding:16px;display:flex;justify-content:space-between;gap:12px;align-items:center;margin-bottom:16px;border-color:var(--ds-success);">
            <div><div class="pl-main" style="color:var(--ds-success);">Link criado com sucesso!</div><div class="pl-sub">{{ session('payment_link_url') }}</div></div>
            <button type="button" class="v2-btn-secondary" style="height:36px;padding:0 16px;" onclick="navigator.clipboard.writeText('{{ session('payment_link_url') }}')"><i class="far fa-copy" style="margin-right: 6px;"></i> Copiar link</button>
        </div>
    @endif

    <!-- Filtros -->
    <div class="pl-filters">
        <input type="text" placeholder="Buscar..." style="width: 250px;">
        <select>
            <option value="">Status: Todos</option>
            <option value="active">Ativo</option>
            <option value="paid">Pago</option>
            <option value="expired">Expirado</option>
            <option value="canceled">Cancelado</option>
        </select>
        <select>
            <option value="">Tipo: Todos</option>
            <option value="cobranca">Cobrança</option>
            <option value="assinatura">Assinatura</option>
        </select>
        <select style="margin-left: auto;">
            <option value="recent">Mais recentes</option>
            <option value="oldest">Mais antigos</option>
        </select>
    </div>

    <div class="pl-card">
        <table class="pl-table">
            <thead>
                <tr>
                    <th style="width:20%;">Título</th>
                    <th style="width:9%;">Tipo</th>
                    <th style="width:13%;">Métodos</th>
                    <th style="width:13%;">Valor</th>
                    <th style="width:10%;">Status</th>
                    <th style="width:9%;">Visitas</th>
                    <th style="width:12%;">Expira em</th>
                    <th style="width:14%;text-align:right;">Ações</th>
                </tr>
            </thead>
            <tbody>
            @forelse($links as $link)
                @php $url = $link->publicUrl(); @endphp
                <tr>
                    <td>
                        <div class="pl-main" title="{{ $link->title }}">{{ $link->title }}</div>
                        <div class="pl-sub" title="{{ $url }}">{{ str()->limit($url, 30) }}</div>
                    </td>
                    <td><span class="pl-main" style="font-size:0.8rem;">{{ $link->type === 'subscription' ? 'Assinatura' : 'Cobrança' }}</span></td>
                    <td><span class="pl-sub">{{ collect($link->allowed_payment_methods ?: [$link->payment_method])->map(fn($method) => ucfirst($method))->join(', ') }}</span></td>
                    <td>
                        <div class="pl-main">
                            {{ $money($link->amount) }}
                            @if($link->type === 'subscription' && isset($link->metadata['interval']))
                                <span style="font-size: 0.75rem; color: var(--ds-text-muted);">
                                    / {{ $link->metadata['interval'] === 'month' ? 'mês' : ($link->metadata['interval'] === 'year' ? 'ano' : ($link->metadata['interval'] === 'week' ? 'sem' : 'dia')) }}
                                </span>
                            @endif
                        </div>
                        <div class="pl-sub">{{ $link->currency }}</div>
                    </td>
                    <td><span class="pl-badge {{ $link->status }}">{{ ucfirst($link->status) }}</span></td>
                    <td>
                        <div class="pl-main" title="Total de acessos">{{ $link->visits_count }}</div>
                        <div class="pl-sub" title="Convertidos (Pagos)">{{ $link->converted_count }} pagos</div>
                    </td>
                    <td>
                        <div class="pl-main" style="font-weight: 500; font-size:0.8rem;">{{ $link->expires_at?->format('d/m/Y') ?: '-' }}</div>
                        <div class="pl-sub">{{ $link->expires_at?->format('H:i') ?: '' }}</div>
                    </td>
                    <td>
                        <div class="pl-row-actions">
                            <a class="pl-icon" href="{{ route('user.payment-links.show', $link->id) }}" title="Analytics"><i class="fas fa-chart-line"></i></a>
                            <a class="pl-icon" href="{{ $url }}" target="_blank" title="Abrir Link"><i class="fas fa-external-link-alt"></i></a>
                            <button type="button" class="pl-icon" title="Copiar Link" onclick="navigator.clipboard.writeText('{{ $url }}')"><i class="far fa-copy"></i></button>
                            @if($link->charge && Route::has('user.charge.show'))
                                <a class="pl-icon" href="{{ route('user.charge.show', $link->charge->id) }}" title="Ver Cobrança"><i class="fas fa-receipt"></i></a>
                            @endif
                            @if(in_array($link->status, ['pending', 'awaiting_payment', 'active'], true))
                                <button type="button" class="pl-icon op-confirm-action" 
                                        data-confirm-title="Cancelar Link" 
                                        data-confirm-message="Tem certeza que deseja cancelar este link de pagamento?" 
                                        data-confirm-url="{{ route('user.payment-links.cancel', $link) }}" 
                                        data-confirm-method="POST" 
                                        title="Cancelar Link">
                                    <i class="fas fa-ban"></i>
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="padding: 0;">
                        <div class="pl-empty-state" style="border: none; background: transparent;">
                            <div class="pl-empty-icon"><i class="fas fa-link"></i></div>
                            <h2 class="pl-empty-title">Nenhum link criado</h2>
                            <p class="pl-empty-desc">Crie seu primeiro link para compartilhar com clientes e receber pagamentos através do checkout público.</p>
                            <button type="button" class="v2-btn-primary" data-bs-toggle="modal" data-bs-target="#pl-type-modal"><i class="fas fa-plus" style="margin-right: 6px;"></i> Criar primeiro link</button>
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    
    @if($links->hasPages())
    <div style="margin-top: 16px;">
        {{ $links->links() }}
    </div>
    @endif
</div>

<!-- Modal Escolha de Link -->
<div class="modal fade" id="pl-type-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content" style="background: var(--ds-bg-card); border: 1px solid var(--ds-border-light); border-radius: 16px;">
            <div class="modal-header" style="border-bottom: 1px solid var(--ds-border-light); padding: 20px 24px;">
                <h5 class="modal-title" style="font-weight: 700; color: var(--ds-text-main);">O que você deseja criar?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: invert(1) grayscale(100%) brightness(200%); opacity: 0.5;"></button>
            </div>
            <div class="modal-body" style="padding: 24px;">
                <div style="display: flex; flex-direction: column; gap: 16px;">
                    <!-- Option 1: Charge -->
                    <a href="{{ route('user.payment-links.create', ['type' => 'charge']) }}" style="display: flex; align-items: flex-start; gap: 16px; padding: 16px; background: var(--ds-bg-light); border: 1px solid var(--ds-border-light); border-radius: 12px; text-decoration: none; transition: 0.2s;">
                        <div style="width: 48px; height: 48px; background: rgba(124, 58, 237, 0.1); color: var(--ds-primary); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0;">
                            <i class="fas fa-receipt"></i>
                        </div>
                        <div>
                            <div style="font-size: 1.05rem; font-weight: 700; color: var(--ds-text-main); margin-bottom: 4px;">Pagamento único</div>
                            <div style="font-size: 0.85rem; color: var(--ds-text-muted); line-height: 1.4; margin-bottom: 12px;">Receba uma cobrança pontual por Pix, cartão ou boleto.</div>
                            <div class="v2-btn-outline" style="font-size: 0.8rem; padding: 6px 12px; display: inline-flex; height: auto;">Criar pagamento</div>
                        </div>
                    </a>
                    
                    <!-- Option 2: Subscription -->
                    <a href="{{ route('user.payment-links.create', ['type' => 'subscription']) }}" style="display: flex; align-items: flex-start; gap: 16px; padding: 16px; background: var(--ds-bg-light); border: 1px solid var(--ds-border-light); border-radius: 12px; text-decoration: none; transition: 0.2s;">
                        <div style="width: 48px; height: 48px; background: rgba(16, 185, 129, 0.1); color: var(--ds-success); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0;">
                            <i class="fas fa-sync-alt"></i>
                        </div>
                        <div>
                            <div style="font-size: 1.05rem; font-weight: 700; color: var(--ds-text-main); margin-bottom: 4px;">Assinatura</div>
                            <div style="font-size: 0.85rem; color: var(--ds-text-muted); line-height: 1.4; margin-bottom: 12px;">Venda planos recorrentes com cobrança automática ou recorrente.</div>
                            <div class="v2-btn-outline" style="font-size: 0.8rem; padding: 6px 12px; display: inline-flex; height: auto;">Criar assinatura</div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    #pl-type-modal a:hover {
        border-color: var(--ds-primary) !important;
        background: var(--ds-bg-card) !important;
    }
</style>
@endsection
