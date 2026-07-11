@extends('frontend.merchant.connect.layout')
@section('title', 'Contatos - Origin Connect')

@section('connect_content')

{{-- Define a layout with right sidebar if screen allows --}}
<div style="display: flex; gap: 15px; align-items: flex-start; height: 100%;">

    {{-- MAIN COLUMN --}}
    <div style="flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 15px; height: 100%;">
        
        {{-- HEADER --}}
        <div class="v2-settings-card" style="margin-bottom: 0;">
            <div class="v2-settings-header" style="justify-content: space-between; border-bottom: none; padding-bottom: 10px; padding-top: 15px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div class="v2-settings-header-icon" style="background: rgba(16,185,129,0.1); color: var(--ds-success); width: 36px; height: 36px;">
                        <i class="fas fa-users" style="font-size: 1rem;"></i>
                    </div>
                    <div>
                        <h5 class="v2-settings-title" style="font-size: 1.05rem; margin-bottom: 2px;">Contatos</h5>
                        <p class="v2-settings-desc" style="font-size: 0.85rem; margin: 0;">Gerencie toda sua base de clientes, leads e inscritos.</p>
                    </div>
                </div>
                <div style="display: flex; gap: 8px; align-items: center;">
                    <button class="v2-btn-secondary" style="height: 32px; padding: 0 14px; gap: 6px; font-size: 0.85rem;">
                        <i class="fas fa-file-import"></i> Importar CSV
                    </button>
                    <a href="{{ route('user.connect.contacts.create') }}" class="v2-btn-primary" style="height: 32px; padding: 0 14px; gap: 6px; font-size: 0.85rem; text-decoration: none;">
                        <i class="fas fa-plus"></i> Novo Contato
                    </a>
                </div>
            </div>
        </div>

        {{-- KPI CARDS --}}
        <div class="v2-kpi-grid" style="margin: 0; grid-template-columns: repeat(4, 1fr); gap: 15px;">
            <div class="v2-kpi-card" style="padding: 12px 15px;">
                <div class="v2-kpi-header" style="margin-bottom: 5px;">
                    <div class="v2-kpi-title" style="font-size: 0.8rem;">Total de contatos</div>
                </div>
                <div class="v2-kpi-value" style="font-size: 1.3rem;">{{ number_format($totalContacts, 0, ',', '.') }}</div>
            </div>
            <div class="v2-kpi-card" style="padding: 12px 15px;">
                <div class="v2-kpi-header" style="margin-bottom: 5px;">
                    <div class="v2-kpi-title" style="font-size: 0.8rem;">Contatos ativos</div>
                </div>
                <div class="v2-kpi-value" style="font-size: 1.3rem; color: var(--ds-success);">{{ number_format($activeContacts, 0, ',', '.') }}</div>
            </div>
            <div class="v2-kpi-card" style="padding: 12px 15px;">
                <div class="v2-kpi-header" style="margin-bottom: 5px;">
                    <div class="v2-kpi-title" style="font-size: 0.8rem;">Com WhatsApp</div>
                </div>
                <div class="v2-kpi-value" style="font-size: 1.3rem;">{{ number_format($whatsappContacts, 0, ',', '.') }}</div>
            </div>
            <div class="v2-kpi-card" style="padding: 12px 15px;">
                <div class="v2-kpi-header" style="margin-bottom: 5px;">
                    <div class="v2-kpi-title" style="font-size: 0.8rem;">Com Email</div>
                </div>
                <div class="v2-kpi-value" style="font-size: 1.3rem;">{{ number_format($emailContacts, 0, ',', '.') }}</div>
            </div>
        </div>

        {{-- FILTER BAR & TABLE --}}
        <div class="v2-settings-card" style="flex: 1; display: flex; flex-direction: column;">
            <div class="v2-settings-header" style="padding: 12px 20px; border-bottom: 1px solid var(--ds-border-light);">
                <form action="{{ route('user.connect.contacts.index') }}" method="GET" style="display: flex; gap: 8px; flex-wrap: wrap; width: 100%;">
                    
                    {{-- Search --}}
                    <div style="flex: 1; min-width: 200px; position: relative;">
                        <i class="fas fa-search" style="position: absolute; left: 10px; top: 10px; color: var(--ds-text-muted); font-size: 0.9rem;"></i>
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Buscar..." style="padding-left: 30px; height: 34px; font-size: 0.85rem;">
                    </div>
                    
                    {{-- Filters --}}
                    <select name="status" class="form-control" style="width: auto; height: 34px; font-size: 0.85rem;">
                        <option value="">Status</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Ativo</option>
                        <option value="unsubscribed" {{ request('status') == 'unsubscribed' ? 'selected' : '' }}>Inativo</option>
                        <option value="bounced" {{ request('status') == 'bounced' ? 'selected' : '' }}>Inválido</option>
                    </select>

                    <select name="tag" class="form-control" style="width: auto; height: 34px; font-size: 0.85rem;">
                        <option value="">Tag</option>
                        @foreach($merchantTags as $tag)
                            <option value="{{ $tag->id }}" {{ request('tag') == $tag->id ? 'selected' : '' }}>{{ $tag->name }}</option>
                        @endforeach
                    </select>

                    <select name="source" class="form-control" style="width: auto; height: 34px; font-size: 0.85rem;">
                        <option value="">Origem</option>
                        <option value="api" {{ request('source') == 'api' ? 'selected' : '' }}>API</option>
                        <option value="import" {{ request('source') == 'import' ? 'selected' : '' }}>Import.</option>
                        <option value="manual" {{ request('source') == 'manual' ? 'selected' : '' }}>Manual</option>
                    </select>
                    
                    <button type="submit" class="v2-btn-secondary" style="height: 34px; padding: 0 12px;"><i class="fas fa-filter" style="font-size: 0.85rem;"></i></button>
                    @if(request()->anyFilled(['search', 'status', 'tag', 'source', 'language']))
                        <a href="{{ route('user.connect.contacts.index') }}" class="v2-btn-secondary" style="height: 34px; padding: 0 12px; color: var(--ds-danger); font-size: 0.85rem; text-decoration: none;"><i class="fas fa-times"></i> Limpar</a>
                    @endif
                </form>
            </div>

            <div class="v2-settings-body" style="padding: 0; flex: 1; overflow-y: auto;">
                @if($contacts->total() > 0)
                    {{-- CONTACTS TABLE --}}
                    <div class="table-responsive">
                        <table class="table v2-table" style="margin: 0; font-size: 0.85rem;">
                            <thead>
                                <tr>
                                    <th style="padding: 10px 15px;">Nome</th>
                                    <th style="padding: 10px 15px;">Email</th>
                                    <th style="padding: 10px 15px;">WhatsApp</th>
                                    <th style="padding: 10px 15px;">Tags</th>
                                    <th style="padding: 10px 15px;">Origem</th>
                                    <th style="padding: 10px 15px;">Últ. Atividade</th>
                                    <th style="padding: 10px 15px;">Status</th>
                                    <th style="padding: 10px 15px;" class="text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($contacts as $contact)
                                    <tr>
                                        <td style="padding: 8px 15px;" class="font-weight-600">{{ $contact->name ?? 'Sem Nome' }}</td>
                                        <td style="padding: 8px 15px;">{{ $contact->email ?? '-' }}</td>
                                        <td style="padding: 8px 15px;">{{ $contact->whatsapp ?? '-' }}</td>
                                        <td style="padding: 8px 15px;">
                                            @if($contact->tags->count() > 0)
                                                <div style="display: flex; gap: 4px; flex-wrap: wrap;">
                                                    @foreach($contact->tags as $t)
                                                        <span class="badge" style="background: {{ $t->color ?? '#e2e8f0' }}; color: #333; font-weight: 500; font-size: 0.7rem; padding: 3px 6px;">{{ $t->name }}</span>
                                                    @endforeach
                                                </div>
                                            @else
                                                <span class="text-muted" style="font-size: 0.8rem;">-</span>
                                            @endif
                                        </td>
                                        <td style="padding: 8px 15px;">
                                            @if($contact->source)
                                                <span class="badge badge-soft-secondary" style="font-size: 0.7rem; padding: 3px 6px;">{{ ucfirst($contact->source) }}</span>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td style="padding: 8px 15px;">
                                            <span style="font-size: 0.8rem; color: var(--ds-text-muted);">
                                                {{ $contact->updated_at ? $contact->updated_at->diffForHumans() : '-' }}
                                            </span>
                                        </td>
                                        <td style="padding: 8px 15px;">
                                            @if($contact->status == 'active')
                                                <span class="badge badge-soft-success" style="font-size: 0.7rem; padding: 3px 6px;">Ativo</span>
                                            @elseif($contact->status == 'unsubscribed')
                                                <span class="badge badge-soft-warning" style="font-size: 0.7rem; padding: 3px 6px;">Inativo</span>
                                            @else
                                                <span class="badge badge-soft-danger" style="font-size: 0.7rem; padding: 3px 6px;">{{ ucfirst($contact->status) }}</span>
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
                        {{ $contacts->links() }}
                    </div>
                @else
                    {{-- EMPTY STATE --}}
                    @if(request()->anyFilled(['search', 'status', 'tag', 'source', 'language']))
                        <div style="padding: 30px 20px; text-align: center;">
                            <div style="font-size: 2.5rem; color: var(--ds-text-muted); margin-bottom: 10px;"><i class="fas fa-search-minus"></i></div>
                            <h5 style="color: var(--ds-text-main); font-weight: 600; font-size: 1.1rem; margin-bottom: 5px;">Nenhum contato encontrado</h5>
                            <p style="color: var(--ds-text-muted); font-size: 0.85rem; margin-bottom: 0;">Não há contatos que correspondam aos filtros aplicados.</p>
                            <a href="{{ route('user.connect.contacts.index') }}" class="v2-btn-secondary mt-3" style="height: 32px; padding: 0 14px; font-size: 0.85rem; text-decoration: none;">Limpar Filtros</a>
                        </div>
                    @else
                        <div style="padding: 30px 20px; text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%;">
                            <div style="font-size: 3rem; color: var(--ds-primary-light); opacity: 0.5; margin-bottom: 10px;"><i class="fas fa-user-plus"></i></div>
                            <h4 style="color: var(--ds-text-main); font-weight: 700; font-size: 1.2rem; margin-bottom: 5px;">Você ainda não possui contatos.</h4>
                            <p style="color: var(--ds-text-muted); font-size: 0.9rem; max-width: 400px; margin: 0 auto 20px;">
                                Os contatos são a base do seu CRM. Eles são utilizados em Segmentos, Campanhas, Jornadas e Automações.
                            </p>
                            <div style="display: flex; gap: 10px; justify-content: center; margin-bottom: 20px;">
                                <a href="{{ route('user.connect.contacts.create') }}" class="v2-btn-primary" style="height: 36px; padding: 0 20px; font-size: 0.9rem; text-decoration: none;">
                                    Criar primeiro contato
                                </a>
                                <button class="v2-btn-secondary" style="height: 36px; padding: 0 20px; font-size: 0.9rem;">
                                    Importar CSV
                                </button>
                            </div>
                        </div>

                        {{-- EDUCATIONAL BLOCK --}}
                        <div style="background: rgba(59,130,246,0.05); padding: 15px; border-top: 1px solid var(--ds-border-light); display: flex; flex-direction: column; align-items: center; margin-top: auto;">
                            <h6 style="color: var(--ds-primary); font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; font-size: 0.75rem; margin-bottom: 10px;">Com contatos você poderá:</h6>
                            <div style="display: flex; gap: 20px; flex-wrap: wrap; justify-content: center;">
                                <div style="display: flex; align-items: center; gap: 6px; color: var(--ds-text-main); font-weight: 500; font-size: 0.85rem;">
                                    <i class="fas fa-check-circle" style="color: var(--ds-success);"></i> Criar segmentos
                                </div>
                                <div style="display: flex; align-items: center; gap: 6px; color: var(--ds-text-main); font-weight: 500; font-size: 0.85rem;">
                                    <i class="fas fa-check-circle" style="color: var(--ds-success);"></i> Enviar campanhas
                                </div>
                                <div style="display: flex; align-items: center; gap: 6px; color: var(--ds-text-main); font-weight: 500; font-size: 0.85rem;">
                                    <i class="fas fa-check-circle" style="color: var(--ds-success);"></i> Automatizar mensagens
                                </div>
                            </div>
                        </div>
                    @endif
                @endif
            </div>
        </div>

    </div>

    {{-- RIGHT SIDEBAR (TIPS) --}}
    <div style="width: 250px; flex-shrink: 0; display: flex; flex-direction: column; gap: 15px;" class="d-none d-xl-flex">
        <div class="v2-settings-card" style="background: linear-gradient(135deg, rgba(124,58,237,0.03) 0%, rgba(124,58,237,0.08) 100%); border-color: rgba(124,58,237,0.15);">
            <div class="v2-settings-body" style="padding: 15px;">
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                    <i class="fas fa-lightbulb" style="color: #f59e0b; font-size: 1rem;"></i>
                    <h6 style="margin: 0; font-weight: 700; color: var(--ds-text-main); font-size: 0.9rem;">Dica Rápida</h6>
                </div>
                <p style="color: var(--ds-text-muted); font-size: 0.8rem; margin-bottom: 10px; line-height: 1.4;">
                    Use <strong>Tags</strong> para organizar seus contatos de forma eficiente.
                </p>
                <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                    <span class="badge" style="background: #e2e8f0; color: #333; font-size: 0.7rem; padding: 3px 6px;">VIP</span>
                    <span class="badge" style="background: #e2e8f0; color: #333; font-size: 0.7rem; padding: 3px 6px;">Lead</span>
                    <span class="badge" style="background: #e2e8f0; color: #333; font-size: 0.7rem; padding: 3px 6px;">Cliente</span>
                </div>
            </div>
        </div>
        
        <div class="v2-settings-card">
            <div class="v2-settings-body" style="padding: 15px;">
                <h6 style="margin: 0 0 8px; font-weight: 700; color: var(--ds-text-main); font-size: 0.9rem;">Exportação</h6>
                <p style="color: var(--ds-text-muted); font-size: 0.8rem; margin-bottom: 12px; line-height: 1.4;">
                    Baixe sua base filtrada ou completa em CSV.
                </p>
                <button class="v2-btn-secondary" style="width: 100%; justify-content: center; height: 32px; font-size: 0.85rem;">
                    <i class="fas fa-file-export"></i> Exportar Contatos
                </button>
            </div>
        </div>
    </div>

</div>

@endsection
