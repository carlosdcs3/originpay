@extends('frontend.merchant.connect.layout')
@section('title', 'Segmentos - Origin Connect')

@section('connect_content')
<div style="display: flex; flex-direction: column; gap: 15px; height: 100%;">
    
    {{-- HEADER --}}
    <div class="v2-settings-card" style="margin-bottom: 0;">
        <div class="v2-settings-header" style="justify-content: space-between; border-bottom: none; padding-bottom: 10px; padding-top: 15px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div class="v2-settings-header-icon" style="background: rgba(59,130,246,0.1); color: var(--ds-primary); width: 36px; height: 36px;">
                    <i class="fas fa-filter" style="font-size: 1rem;"></i>
                </div>
                <div>
                    <h5 class="v2-settings-title" style="font-size: 1.05rem; margin-bottom: 2px;">Segmentos</h5>
                    <p class="v2-settings-desc" style="font-size: 0.85rem; margin: 0;">Organize automaticamente seus contatos através de regras inteligentes.</p>
                </div>
            </div>
            <div>
                <a href="{{ route('user.connect.segments.create') }}" class="v2-btn-primary" style="height: 32px; padding: 0 14px; gap: 6px; font-size: 0.85rem; text-decoration: none;">
                    <i class="fas fa-plus"></i> Novo Segmento
                </a>
            </div>
        </div>
    </div>

    {{-- KPI CARDS --}}
    <div class="v2-kpi-grid" style="margin: 0; grid-template-columns: repeat(4, 1fr); gap: 15px;">
        <div class="v2-kpi-card" style="padding: 12px 15px;">
            <div class="v2-kpi-header" style="margin-bottom: 5px;">
                <div class="v2-kpi-title" style="font-size: 0.8rem;">Total</div>
            </div>
            <div class="v2-kpi-value" style="font-size: 1.3rem;">{{ number_format($totalSegments, 0, ',', '.') }}</div>
        </div>
        <div class="v2-kpi-card" style="padding: 12px 15px;">
            <div class="v2-kpi-header" style="margin-bottom: 5px;">
                <div class="v2-kpi-title" style="font-size: 0.8rem;">Dinâmicos</div>
            </div>
            <div class="v2-kpi-value" style="font-size: 1.3rem; color: var(--ds-primary);">{{ number_format($dynamicSegments, 0, ',', '.') }}</div>
        </div>
        <div class="v2-kpi-card" style="padding: 12px 15px;">
            <div class="v2-kpi-header" style="margin-bottom: 5px;">
                <div class="v2-kpi-title" style="font-size: 0.8rem;">Contatos Segmentados</div>
            </div>
            <div class="v2-kpi-value" style="font-size: 1.3rem;">{{ number_format($segmentedContacts, 0, ',', '.') }}</div>
        </div>
        <div class="v2-kpi-card" style="padding: 12px 15px;">
            <div class="v2-kpi-header" style="margin-bottom: 5px;">
                <div class="v2-kpi-title" style="font-size: 0.8rem;">Última atualização</div>
            </div>
            <div class="v2-kpi-value" style="font-size: 1.1rem; color: var(--ds-text-muted);">
                @if($lastUpdate)
                    {{ \Carbon\Carbon::parse($lastUpdate)->diffForHumans() }}
                @else
                    -
                @endif
            </div>
        </div>
    </div>

    {{-- CONTENT AREA --}}
    <div class="v2-settings-card" style="flex: 1; display: flex; flex-direction: column;">
        @if($segments->total() > 0 || request()->anyFilled(['search', 'type']))
            <div class="v2-settings-header" style="padding: 12px 20px; border-bottom: 1px solid var(--ds-border-light);">
                <form action="{{ route('user.connect.segments.index') }}" method="GET" style="display: flex; gap: 8px; flex-wrap: wrap; width: 100%;">
                    
                    {{-- Search --}}
                    <div style="flex: 1; min-width: 200px; position: relative;">
                        <i class="fas fa-search" style="position: absolute; left: 10px; top: 10px; color: var(--ds-text-muted); font-size: 0.9rem;"></i>
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Buscar segmento..." style="padding-left: 30px; height: 34px; font-size: 0.85rem;">
                    </div>
                    
                    {{-- Filters --}}
                    <select name="type" class="form-control" style="width: auto; height: 34px; font-size: 0.85rem;">
                        <option value="">Tipo</option>
                        <option value="dynamic" {{ request('type') == 'dynamic' ? 'selected' : '' }}>Dinâmico</option>
                        <option value="static" {{ request('type') == 'static' ? 'selected' : '' }}>Estático</option>
                    </select>

                    <button type="submit" class="v2-btn-secondary" style="height: 34px; padding: 0 12px;"><i class="fas fa-filter" style="font-size: 0.85rem;"></i></button>
                    @if(request()->anyFilled(['search', 'type']))
                        <a href="{{ route('user.connect.segments.index') }}" class="v2-btn-secondary" style="height: 34px; padding: 0 12px; color: var(--ds-danger); font-size: 0.85rem; text-decoration: none;"><i class="fas fa-times"></i> Limpar</a>
                    @endif
                </form>
            </div>

            <div class="v2-settings-body" style="padding: 0; flex: 1; overflow-y: auto;">
                @if($segments->total() > 0)
                    <div class="table-responsive">
                        <table class="table v2-table" style="margin: 0; font-size: 0.85rem;">
                            <thead>
                                <tr>
                                    <th style="padding: 10px 15px;">Nome</th>
                                    <th style="padding: 10px 15px;">Regras</th>
                                    <th style="padding: 10px 15px;">Quantidade</th>
                                    <th style="padding: 10px 15px;">Atualizado</th>
                                    <th style="padding: 10px 15px;">Status</th>
                                    <th style="padding: 10px 15px;" class="text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($segments as $segment)
                                    <tr>
                                        <td style="padding: 8px 15px;">
                                            <div style="font-weight: 600; color: var(--ds-text-main);">{{ $segment->name }}</div>
                                            @if($segment->description)
                                                <div style="font-size: 0.75rem; color: var(--ds-text-muted); margin-top: 2px;">{{ Str::limit($segment->description, 40) }}</div>
                                            @endif
                                        </td>
                                        <td style="padding: 8px 15px;">
                                            <span class="badge badge-soft-primary" style="font-size: 0.7rem; padding: 3px 6px;">{{ $segment->rules_count }} regra(s)</span>
                                        </td>
                                        <td style="padding: 8px 15px; font-weight: 500;">
                                            {{ number_format($segment->total_contacts, 0, ',', '.') }}
                                        </td>
                                        <td style="padding: 8px 15px;">
                                            <span style="font-size: 0.8rem; color: var(--ds-text-muted);">
                                                {{ $segment->updated_at ? $segment->updated_at->diffForHumans() : '-' }}
                                            </span>
                                        </td>
                                        <td style="padding: 8px 15px;">
                                            @if($segment->is_dynamic)
                                                <span class="badge badge-soft-success" style="font-size: 0.7rem; padding: 3px 6px;"><i class="fas fa-bolt" style="margin-right:3px;"></i> Dinâmico</span>
                                            @else
                                                <span class="badge badge-soft-secondary" style="font-size: 0.7rem; padding: 3px 6px;">Estático</span>
                                            @endif
                                        </td>
                                        <td style="padding: 8px 15px;" class="text-right">
                                            <button class="btn btn-sm btn-light" style="padding: 2px 6px; font-size: 0.8rem;" title="Editar"><i class="fas fa-pen"></i></button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div style="padding: 10px 20px; border-top: 1px solid var(--ds-border-light);">
                        {{ $segments->links() }}
                    </div>
                @else
                    <div style="padding: 30px 20px; text-align: center;">
                        <div style="font-size: 2.5rem; color: var(--ds-text-muted); margin-bottom: 10px;"><i class="fas fa-search-minus"></i></div>
                        <h5 style="color: var(--ds-text-main); font-weight: 600; font-size: 1.1rem; margin-bottom: 5px;">Nenhum segmento encontrado</h5>
                        <p style="color: var(--ds-text-muted); font-size: 0.85rem; margin-bottom: 0;">Não há segmentos que correspondam aos filtros aplicados.</p>
                        <a href="{{ route('user.connect.segments.index') }}" class="v2-btn-secondary mt-3" style="height: 32px; padding: 0 14px; font-size: 0.85rem; text-decoration: none;">Limpar Filtros</a>
                    </div>
                @endif
            </div>
        @else
            {{-- EMPTY STATE (EDUCATIONAL) --}}
            <div class="v2-settings-body" style="padding: 30px 20px; display: flex; flex-direction: column; align-items: center; justify-content: center; flex: 1; height: 100%;">
                
                <div style="text-align: center; margin-bottom: 25px;">
                    <div style="font-size: 3rem; color: var(--ds-primary-light); opacity: 0.4; margin-bottom: 10px;"><i class="fas fa-users-cog"></i></div>
                    <h4 style="color: var(--ds-text-main); font-weight: 700; font-size: 1.2rem; margin-bottom: 5px;">Nenhum segmento criado.</h4>
                    <p style="color: var(--ds-text-muted); font-size: 0.9rem; max-width: 450px; margin: 0 auto;">
                        Segmentos permitem criar grupos automáticos de contatos com base em regras (ex: tags, origem, última compra). Eles são atualizados em <strong>tempo real</strong>.
                    </p>
                </div>

                {{-- EXAMPLE CARDS --}}
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; max-width: 500px; width: 100%; margin-bottom: 25px;">
                    <div style="background: rgba(59,130,246,0.05); border: 1px solid rgba(59,130,246,0.1); border-radius: 8px; padding: 12px; text-align: center;">
                        <h6 style="color: var(--ds-text-main); font-weight: 600; font-size: 0.85rem; margin-bottom: 5px;">Clientes VIP</h6>
                        <span class="badge" style="background: #fff; color: #333; font-size: 0.7rem; border: 1px solid var(--ds-border);">Tag = VIP</span>
                    </div>
                    <div style="background: rgba(16,185,129,0.05); border: 1px solid rgba(16,185,129,0.1); border-radius: 8px; padding: 12px; text-align: center;">
                        <h6 style="color: var(--ds-text-main); font-weight: 600; font-size: 0.85rem; margin-bottom: 5px;">Compraram nos últimos 30 dias</h6>
                        <span class="badge" style="background: #fff; color: #333; font-size: 0.7rem; border: 1px solid var(--ds-border);">Data &gt; 30d</span>
                    </div>
                    <div style="background: rgba(245,158,11,0.05); border: 1px solid rgba(245,158,11,0.1); border-radius: 8px; padding: 12px; text-align: center;">
                        <h6 style="color: var(--ds-text-main); font-weight: 600; font-size: 0.85rem; margin-bottom: 5px;">Leads Instagram</h6>
                        <span class="badge" style="background: #fff; color: #333; font-size: 0.7rem; border: 1px solid var(--ds-border);">Origem = Instagram</span>
                    </div>
                    <div style="background: rgba(239,68,68,0.05); border: 1px solid rgba(239,68,68,0.1); border-radius: 8px; padding: 12px; text-align: center;">
                        <h6 style="color: var(--ds-text-main); font-weight: 600; font-size: 0.85rem; margin-bottom: 5px;">Clientes Inativos</h6>
                        <span class="badge" style="background: #fff; color: #333; font-size: 0.7rem; border: 1px solid var(--ds-border);">Atividade &lt; 90d</span>
                    </div>
                </div>

                <a href="{{ route('user.connect.segments.create') }}" class="v2-btn-primary" style="height: 36px; padding: 0 20px; font-size: 0.9rem; text-decoration: none;">
                    Criar Segmento
                </a>
            </div>
        @endif
    </div>

</div>
@endsection
