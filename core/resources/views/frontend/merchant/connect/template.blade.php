@extends('frontend.merchant.connect.layout')
@section('title', 'Templates - Origin Connect')

@section('connect_content')
<div style="display: flex; gap: 15px; align-items: flex-start; height: 100%;">

    {{-- MAIN COLUMN --}}
    <div style="flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 15px; height: 100%;">
        
        {{-- HEADER --}}
        <div class="v2-settings-card" style="margin-bottom: 0;">
            <div class="v2-settings-header" style="justify-content: space-between; border-bottom: none; padding-bottom: 10px; padding-top: 15px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div class="v2-settings-header-icon" style="background: rgba(139,92,246,0.1); color: #8b5cf6; width: 36px; height: 36px;">
                        <i class="fas fa-layer-group" style="font-size: 1rem;"></i>
                    </div>
                    <div>
                        <h5 class="v2-settings-title" style="font-size: 1.05rem; margin-bottom: 2px;">Templates</h5>
                        <p class="v2-settings-desc" style="font-size: 0.85rem; margin: 0;">Crie modelos reutilizáveis para Email, WhatsApp, SMS e Push.</p>
                    </div>
                </div>
                <div style="display: flex; gap: 8px; align-items: center;">
                    <button class="v2-btn-secondary" style="height: 32px; padding: 0 14px; gap: 6px; font-size: 0.85rem;">
                        <i class="fas fa-file-import"></i> Importar
                    </button>
                    <a href="{{ route('user.connect.templates.create') }}" class="v2-btn-primary" style="height: 32px; padding: 0 14px; gap: 6px; font-size: 0.85rem; text-decoration: none;">
                        <i class="fas fa-plus"></i> Novo Template
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
                <div class="v2-kpi-value" style="font-size: 1.3rem;">{{ number_format($totalTemplates, 0, ',', '.') }}</div>
            </div>
            <div class="v2-kpi-card" style="padding: 12px 15px;">
                <div class="v2-kpi-header" style="margin-bottom: 5px;">
                    <div class="v2-kpi-title" style="font-size: 0.8rem;">Email</div>
                </div>
                <div class="v2-kpi-value" style="font-size: 1.3rem;">{{ number_format($emailTemplates, 0, ',', '.') }}</div>
            </div>
            <div class="v2-kpi-card" style="padding: 12px 15px;">
                <div class="v2-kpi-header" style="margin-bottom: 5px;">
                    <div class="v2-kpi-title" style="font-size: 0.8rem;">WhatsApp</div>
                </div>
                <div class="v2-kpi-value" style="font-size: 1.3rem; color: #25D366;">{{ number_format($whatsappTemplates, 0, ',', '.') }}</div>
            </div>
            <div class="v2-kpi-card" style="padding: 12px 15px;">
                <div class="v2-kpi-header" style="margin-bottom: 5px;">
                    <div class="v2-kpi-title" style="font-size: 0.8rem;">Publicados</div>
                </div>
                <div class="v2-kpi-value" style="font-size: 1.3rem; color: var(--ds-success);">{{ number_format($publishedTemplates, 0, ',', '.') }}</div>
            </div>
        </div>

        {{-- CONTENT AREA --}}
        <div class="v2-settings-card" style="flex: 1; display: flex; flex-direction: column;">
            @if($templates->total() > 0 || request()->anyFilled(['search', 'channel']))
                <div class="v2-settings-header" style="padding: 12px 20px; border-bottom: 1px solid var(--ds-border-light);">
                    <form action="{{ route('user.connect.templates.index') }}" method="GET" style="display: flex; gap: 8px; flex-wrap: wrap; width: 100%;">
                        
                        {{-- Search --}}
                        <div style="flex: 1; min-width: 200px; position: relative;">
                            <i class="fas fa-search" style="position: absolute; left: 10px; top: 10px; color: var(--ds-text-muted); font-size: 0.9rem;"></i>
                            <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Buscar template..." style="padding-left: 30px; height: 34px; font-size: 0.85rem;">
                        </div>
                        
                        {{-- Filters --}}
                        <select name="channel" class="form-control" style="width: auto; height: 34px; font-size: 0.85rem;">
                            <option value="">Canal</option>
                            <option value="email" {{ request('channel') == 'email' ? 'selected' : '' }}>Email</option>
                            <option value="whatsapp" {{ request('channel') == 'whatsapp' ? 'selected' : '' }}>WhatsApp</option>
                            <option value="sms" {{ request('channel') == 'sms' ? 'selected' : '' }}>SMS</option>
                            <option value="push" {{ request('channel') == 'push' ? 'selected' : '' }}>Push</option>
                        </select>

                        <button type="submit" class="v2-btn-secondary" style="height: 34px; padding: 0 12px;"><i class="fas fa-filter" style="font-size: 0.85rem;"></i></button>
                        @if(request()->anyFilled(['search', 'channel']))
                            <a href="{{ route('user.connect.templates.index') }}" class="v2-btn-secondary" style="height: 34px; padding: 0 12px; color: var(--ds-danger); font-size: 0.85rem; text-decoration: none;"><i class="fas fa-times"></i> Limpar</a>
                        @endif
                    </form>
                </div>

                <div class="v2-settings-body" style="padding: 0; flex: 1; overflow-y: auto;">
                    @if($templates->total() > 0)
                        <div class="table-responsive">
                            <table class="table v2-table" style="margin: 0; font-size: 0.85rem;">
                                <thead>
                                    <tr>
                                        <th style="padding: 10px 15px;">Nome</th>
                                        <th style="padding: 10px 15px;">Canal</th>
                                        <th style="padding: 10px 15px;">Versão</th>
                                        <th style="padding: 10px 15px;">Status</th>
                                        <th style="padding: 10px 15px;">Atualizado</th>
                                        <th style="padding: 10px 15px;" class="text-right">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($templates as $template)
                                        <tr>
                                            <td style="padding: 8px 15px;">
                                                <div style="font-weight: 600; color: var(--ds-text-main);">{{ $template->name }}</div>
                                                @if($template->subject)
                                                    <div style="font-size: 0.75rem; color: var(--ds-text-muted); margin-top: 2px;">Assunto: {{ Str::limit($template->subject, 35) }}</div>
                                                @endif
                                            </td>
                                            <td style="padding: 8px 15px;">
                                                @if($template->channel == 'email')
                                                    <span class="badge" style="background: rgba(59,130,246,0.1); color: var(--ds-primary); font-size: 0.7rem; padding: 4px 8px;"><i class="fas fa-envelope" style="margin-right: 4px;"></i> Email</span>
                                                @elseif($template->channel == 'whatsapp')
                                                    <span class="badge" style="background: rgba(37,211,102,0.1); color: #25D366; font-size: 0.7rem; padding: 4px 8px;"><i class="fab fa-whatsapp" style="margin-right: 4px;"></i> WhatsApp</span>
                                                @else
                                                    <span class="badge badge-soft-secondary" style="font-size: 0.7rem; padding: 4px 8px;">{{ ucfirst($template->channel) }}</span>
                                                @endif
                                            </td>
                                            <td style="padding: 8px 15px;">
                                                <span class="badge badge-soft-secondary" style="font-size: 0.7rem; padding: 3px 6px;">v{{ $template->version ?? '1' }}.0</span>
                                            </td>
                                            <td style="padding: 8px 15px;">
                                                @if($template->status == 'published' || $template->published_at)
                                                    <span class="badge badge-soft-success" style="font-size: 0.7rem; padding: 3px 6px;">Publicado</span>
                                                @else
                                                    <span class="badge badge-soft-warning" style="font-size: 0.7rem; padding: 3px 6px;">Rascunho</span>
                                                @endif
                                            </td>
                                            <td style="padding: 8px 15px;">
                                                <span style="font-size: 0.8rem; color: var(--ds-text-muted);">
                                                    {{ $template->updated_at ? $template->updated_at->diffForHumans() : '-' }}
                                                </span>
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
                            {{ $templates->links() }}
                        </div>
                    @else
                        <div style="padding: 30px 20px; text-align: center;">
                            <div style="font-size: 2.5rem; color: var(--ds-text-muted); margin-bottom: 10px;"><i class="fas fa-search-minus"></i></div>
                            <h5 style="color: var(--ds-text-main); font-weight: 600; font-size: 1.1rem; margin-bottom: 5px;">Nenhum template encontrado</h5>
                            <p style="color: var(--ds-text-muted); font-size: 0.85rem; margin-bottom: 0;">Tente ajustar os filtros de busca ou canal.</p>
                            <a href="{{ route('user.connect.templates.index') }}" class="v2-btn-secondary mt-3" style="height: 32px; padding: 0 14px; font-size: 0.85rem; text-decoration: none;">Limpar Filtros</a>
                        </div>
                    @endif
                </div>
            @else
                {{-- EMPTY STATE (EDUCATIONAL) --}}
                <div class="v2-settings-body" style="padding: 30px 20px; display: flex; flex-direction: column; align-items: center; justify-content: center; flex: 1; height: 100%;">
                    
                    <div style="text-align: center; margin-bottom: 25px;">
                        <div style="font-size: 3rem; color: #8b5cf6; opacity: 0.4; margin-bottom: 10px;"><i class="fas fa-file-code"></i></div>
                        <h4 style="color: var(--ds-text-main); font-weight: 700; font-size: 1.2rem; margin-bottom: 5px;">Nenhum Template criado.</h4>
                        <p style="color: var(--ds-text-muted); font-size: 0.9rem; max-width: 450px; margin: 0 auto;">
                            Templates permitem reutilizar conteúdos ricos em campanhas e automações. Crie uma vez, envie milhares de vezes.
                        </p>
                    </div>

                    {{-- EXAMPLE CARDS --}}
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; max-width: 500px; width: 100%; margin-bottom: 25px;">
                        <div style="background: rgba(139,92,246,0.05); border: 1px solid rgba(139,92,246,0.1); border-radius: 8px; padding: 12px; text-align: center; position: relative;">
                            <span class="badge" style="position: absolute; top: -8px; right: -8px; background: #8b5cf6; color: #fff; font-size: 0.65rem;">Exemplo</span>
                            <h6 style="color: var(--ds-text-main); font-weight: 600; font-size: 0.85rem; margin-bottom: 8px;">Boas-vindas</h6>
                            <span class="badge" style="background: #fff; color: var(--ds-primary); font-size: 0.7rem; border: 1px solid rgba(59,130,246,0.2);"><i class="fas fa-envelope"></i> Email</span>
                        </div>
                        <div style="background: rgba(139,92,246,0.05); border: 1px solid rgba(139,92,246,0.1); border-radius: 8px; padding: 12px; text-align: center; position: relative;">
                            <span class="badge" style="position: absolute; top: -8px; right: -8px; background: #8b5cf6; color: #fff; font-size: 0.65rem;">Exemplo</span>
                            <h6 style="color: var(--ds-text-main); font-weight: 600; font-size: 0.85rem; margin-bottom: 8px;">Promoção</h6>
                            <span class="badge" style="background: #fff; color: #25D366; font-size: 0.7rem; border: 1px solid rgba(37,211,102,0.2);"><i class="fab fa-whatsapp"></i> WhatsApp</span>
                        </div>
                        <div style="background: rgba(139,92,246,0.05); border: 1px solid rgba(139,92,246,0.1); border-radius: 8px; padding: 12px; text-align: center; position: relative;">
                            <span class="badge" style="position: absolute; top: -8px; right: -8px; background: #8b5cf6; color: #fff; font-size: 0.65rem;">Exemplo</span>
                            <h6 style="color: var(--ds-text-main); font-weight: 600; font-size: 0.85rem; margin-bottom: 8px;">Carrinho abandonado</h6>
                            <span class="badge" style="background: #fff; color: var(--ds-primary); font-size: 0.7rem; border: 1px solid rgba(59,130,246,0.2);"><i class="fas fa-envelope"></i> Email</span>
                        </div>
                        <div style="background: rgba(139,92,246,0.05); border: 1px solid rgba(139,92,246,0.1); border-radius: 8px; padding: 12px; text-align: center; position: relative;">
                            <span class="badge" style="position: absolute; top: -8px; right: -8px; background: #8b5cf6; color: #fff; font-size: 0.65rem;">Exemplo</span>
                            <h6 style="color: var(--ds-text-main); font-weight: 600; font-size: 0.85rem; margin-bottom: 8px;">Lançamento</h6>
                            <span class="badge" style="background: #fff; color: #25D366; font-size: 0.7rem; border: 1px solid rgba(37,211,102,0.2);"><i class="fab fa-whatsapp"></i> WhatsApp</span>
                        </div>
                    </div>

                    <a href="{{ route('user.connect.templates.create') }}" class="v2-btn-primary" style="height: 36px; padding: 0 20px; font-size: 0.9rem; text-decoration: none;">
                        Criar Novo Template
                    </a>
                </div>
            @endif
        </div>
    </div>

    {{-- RIGHT SIDEBAR (VARIABLES) --}}
    <div style="width: 250px; flex-shrink: 0; display: flex; flex-direction: column; gap: 15px;" class="d-none d-xl-flex">
        <div class="v2-settings-card" style="height: 100%;">
            <div class="v2-settings-body" style="padding: 15px;">
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px; padding-bottom: 10px; border-bottom: 1px solid var(--ds-border-light);">
                    <i class="fas fa-code" style="color: var(--ds-primary); font-size: 1rem;"></i>
                    <h6 style="margin: 0; font-weight: 700; color: var(--ds-text-main); font-size: 0.9rem;">Variáveis Disponíveis</h6>
                </div>
                <p style="color: var(--ds-text-muted); font-size: 0.8rem; margin-bottom: 15px; line-height: 1.4;">
                    Você pode utilizar estas variáveis dinâmicas em qualquer template para personalizar suas mensagens.
                </p>
                
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <div>
                        <div style="font-size: 0.75rem; font-weight: 700; color: var(--ds-text-main); margin-bottom: 4px;">Dados do Contato</div>
                        <div style="display: flex; flex-direction: column; gap: 6px;">
                            <code style="display: block; background: #f8fafc; border: 1px solid #e2e8f0; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; color: #d926a9;">@{{contact.name}}</code>
                            <code style="display: block; background: #f8fafc; border: 1px solid #e2e8f0; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; color: #d926a9;">@{{contact.email}}</code>
                        </div>
                    </div>
                    
                    <div>
                        <div style="font-size: 0.75rem; font-weight: 700; color: var(--ds-text-main); margin-bottom: 4px;">Dados da Empresa</div>
                        <div style="display: flex; flex-direction: column; gap: 6px;">
                            <code style="display: block; background: #f8fafc; border: 1px solid #e2e8f0; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; color: #0284c7;">@{{merchant.name}}</code>
                        </div>
                    </div>
                    
                    <div>
                        <div style="font-size: 0.75rem; font-weight: 700; color: var(--ds-text-main); margin-bottom: 4px;">Sistema</div>
                        <div style="display: flex; flex-direction: column; gap: 6px;">
                            <code style="display: block; background: #f8fafc; border: 1px solid #e2e8f0; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; color: #16a34a;">@{{current_date}}</code>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
