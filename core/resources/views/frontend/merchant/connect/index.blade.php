@extends('frontend.merchant.connect.layout')
@section('title', 'Origin Connect - Visão Geral')

@section('connect_content')

@if($hasCampaigns)
    {{-- ========================================== --}}
    {{-- FACE 2: ANALYTICS DASHBOARD                --}}
    {{-- ========================================== --}}
    <div style="display: flex; flex-direction: column; gap: 15px; height: 100%;">
        
        {{-- HEADER --}}
        <div class="v2-settings-card" style="margin-bottom: 0;">
            <div class="v2-settings-header" style="justify-content: space-between; border-bottom: none; padding-bottom: 10px; padding-top: 15px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div class="v2-settings-header-icon" style="background: rgba(124,58,237,0.1); color: var(--ds-primary-light); width: 36px; height: 36px;">
                        <i class="fas fa-chart-line" style="font-size: 1rem;"></i>
                    </div>
                    <div>
                        <h5 class="v2-settings-title" style="font-size: 1.05rem; margin-bottom: 2px;">Visão Geral</h5>
                        <p class="v2-settings-desc" style="font-size: 0.85rem; margin: 0;">Métricas e performance da sua operação multicanal.</p>
                    </div>
                </div>
                <div style="display: flex; gap: 8px; align-items: center;">
                    <button class="v2-btn-secondary" style="height: 32px; padding: 0 14px; gap: 6px; font-size: 0.85rem;">
                        <i class="fas fa-project-diagram"></i> Nova Jornada
                    </button>
                    <a href="{{ route('user.connect.campaigns.create') }}" class="v2-btn-primary" style="height: 32px; padding: 0 14px; gap: 6px; font-size: 0.85rem; text-decoration: none;">
                        <i class="fas fa-bullhorn"></i> Nova Campanha
                    </a>
                </div>
            </div>
        </div>

        {{-- KPI CARDS (6) --}}
        <div class="v2-kpi-grid" style="margin: 0; grid-template-columns: repeat(6, 1fr); gap: 10px;">
            <div class="v2-kpi-card" style="padding: 12px 10px; border-color: rgba(59,130,246,0.15);">
                <div class="v2-kpi-title" style="font-size: 0.75rem; margin-bottom: 5px;">Mensagens enviadas</div>
                <div class="v2-kpi-value" style="font-size: 1.2rem; color: #3b82f6;">1.2M</div>
            </div>
            <div class="v2-kpi-card" style="padding: 12px 10px; border-color: rgba(16,185,129,0.15);">
                <div class="v2-kpi-title" style="font-size: 0.75rem; margin-bottom: 5px;">Taxa de entrega</div>
                <div class="v2-kpi-value" style="font-size: 1.2rem; color: #10b981;">99.8%</div>
            </div>
            <div class="v2-kpi-card" style="padding: 12px 10px;">
                <div class="v2-kpi-title" style="font-size: 0.75rem; margin-bottom: 5px;">Campanhas</div>
                <div class="v2-kpi-value" style="font-size: 1.2rem;">{{ number_format($campaignsCount) }}</div>
            </div>
            <div class="v2-kpi-card" style="padding: 12px 10px;">
                <div class="v2-kpi-title" style="font-size: 0.75rem; margin-bottom: 5px;">Templates</div>
                <div class="v2-kpi-value" style="font-size: 1.2rem;">{{ number_format($templatesCount) }}</div>
            </div>
            <div class="v2-kpi-card" style="padding: 12px 10px;">
                <div class="v2-kpi-title" style="font-size: 0.75rem; margin-bottom: 5px;">Contatos</div>
                <div class="v2-kpi-value" style="font-size: 1.2rem;">{{ number_format($contactsCount) }}</div>
            </div>
            <div class="v2-kpi-card" style="padding: 12px 10px;">
                <div class="v2-kpi-title" style="font-size: 0.75rem; margin-bottom: 5px;">Segmentos</div>
                <div class="v2-kpi-value" style="font-size: 1.2rem;">{{ number_format($segmentsCount) }}</div>
            </div>
        </div>

        {{-- GRAPHS & LISTS --}}
        <div style="display: flex; gap: 15px; flex: 1;">
            
            {{-- MAIN CHART --}}
            <div class="v2-settings-card" style="flex: 2; display: flex; flex-direction: column;">
                <div class="v2-settings-header" style="padding: 12px 15px; border-bottom: 1px solid var(--ds-border-light);">
                    <h6 style="margin: 0; font-size: 0.9rem; font-weight: 600;">Desempenho de Disparos</h6>
                </div>
                <div class="v2-settings-body" style="padding: 20px; flex: 1; display: flex; align-items: center; justify-content: center;">
                    <div style="text-align: center; color: var(--ds-text-muted);">
                        <i class="fas fa-chart-area" style="font-size: 2rem; margin-bottom: 10px; opacity: 0.5;"></i>
                        <div style="font-size: 0.85rem;">Gráfico de performance (Simulação)</div>
                    </div>
                </div>
            </div>

            {{-- SIDE PANELS --}}
            <div style="flex: 1; display: flex; flex-direction: column; gap: 15px;">
                
                <div class="v2-settings-card" style="flex: 1; display: flex; flex-direction: column;">
                    <div class="v2-settings-header" style="padding: 12px 15px; border-bottom: 1px solid var(--ds-border-light);">
                        <h6 style="margin: 0; font-size: 0.85rem; font-weight: 600;">Últimas Campanhas</h6>
                    </div>
                    <div class="v2-settings-body" style="padding: 0; overflow-y: auto;">
                        <div style="padding: 10px 15px; border-bottom: 1px solid var(--ds-border-light); display: flex; justify-content: space-between; font-size: 0.8rem;">
                            <span style="font-weight: 500;">Promoção Inverno</span>
                            <span style="color: var(--ds-success);">Concluída</span>
                        </div>
                        <div style="padding: 10px 15px; border-bottom: 1px solid var(--ds-border-light); display: flex; justify-content: space-between; font-size: 0.8rem;">
                            <span style="font-weight: 500;">Boas-vindas Batch</span>
                            <span style="color: var(--ds-primary);">Rodando</span>
                        </div>
                    </div>
                </div>

                <div class="v2-settings-card" style="flex: 1; display: flex; flex-direction: column;">
                    <div class="v2-settings-header" style="padding: 12px 15px; border-bottom: 1px solid var(--ds-border-light);">
                        <h6 style="margin: 0; font-size: 0.85rem; font-weight: 600; color: var(--ds-danger);">Fila & Alertas</h6>
                    </div>
                    <div class="v2-settings-body" style="padding: 0; overflow-y: auto;">
                        <div style="padding: 10px 15px; display: flex; align-items: center; gap: 8px; font-size: 0.8rem;">
                            <i class="fas fa-check-circle" style="color: var(--ds-success);"></i> Fila saudável (0 pendentes)
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@else
    {{-- ========================================== --}}
    {{-- FACE 1: ONBOARDING INTELIGENTE             --}}
    {{-- ========================================== --}}
    <div style="display: flex; flex-direction: column; gap: 20px; max-width: 1200px; margin: 0 auto; height: 100%;">
        
        {{-- HERO SECTION --}}
        <div class="v2-settings-card" style="background: linear-gradient(135deg, rgba(124,58,237,0.1) 0%, rgba(124,58,237,0.2) 100%); border-color: rgba(124,58,237,0.2); padding: 30px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 0;">
            <div>
                <div style="font-size: 0.85rem; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; color: var(--ds-primary);">Origin Connect • Plataforma de Engajamento Multicanal</div>
                <h2 style="margin: 0 0 10px; font-weight: 700; font-size: 1.8rem; letter-spacing: -0.5px; color: var(--ds-text-main);">Bem-vindo ao Origin Connect</h2>
                <p style="margin: 0; font-size: 1rem; color: var(--ds-text-muted); max-width: 500px; line-height: 1.5;">Configure sua operação em poucos passos e publique sua primeira campanha automátizada.</p>
            </div>
            <div>
                <a href="{{ route('user.connect.contacts.create') }}" class="v2-btn-primary" style="font-weight: 700; border-radius: 6px; padding: 10px 24px; font-size: 0.95rem; text-decoration: none;">
                    Começar
                </a>
            </div>
        </div>

        <div style="display: flex; gap: 20px; align-items: stretch; flex: 1;">
            
            {{-- LEFT: CHECKLIST & NEXT STEP --}}
            <div style="flex: 1; display: flex; flex-direction: column; gap: 20px;">
                
                {{-- NEXT STEP CARD --}}
                <div class="v2-settings-card" style="border: 2px solid var(--ds-primary); box-shadow: 0 4px 15px rgba(124,58,237,0.1);">
                    <div class="v2-settings-body" style="padding: 25px;">
                        <span class="badge" style="background: rgba(124,58,237,0.1); color: var(--ds-primary); font-size: 0.75rem; margin-bottom: 15px; padding: 4px 8px;">Próxima etapa</span>
                        
                        @if($nextStep == 'contacts')
                            <h4 style="font-weight: 700; color: var(--ds-text-main); margin-bottom: 10px;">Crie seu primeiro contato.</h4>
                            <p style="color: var(--ds-text-muted); font-size: 0.95rem; margin-bottom: 20px;">Os contatos são a base do seu CRM. Eles serão utilizados por toda a plataforma em segmentos e jornadas.</p>
                            <a href="{{ route('user.connect.contacts.create') }}" class="v2-btn-primary" style="height: 38px; padding: 0 20px; text-decoration: none;">Criar contato</a>
                        @elseif($nextStep == 'segments')
                            <h4 style="font-weight: 700; color: var(--ds-text-main); margin-bottom: 10px;">Crie um segmento dinâmico.</h4>
                            <p style="color: var(--ds-text-muted); font-size: 0.95rem; margin-bottom: 20px;">Agrupe seus contatos automaticamente através de regras para enviar campanhas direcionadas.</p>
                            <a href="{{ route('user.connect.segments.create') }}" class="v2-btn-primary" style="height: 38px; padding: 0 20px; text-decoration: none;">Criar segmento</a>
                        @elseif($nextStep == 'templates')
                            <h4 style="font-weight: 700; color: var(--ds-text-main); margin-bottom: 10px;">Desenhe um Template.</h4>
                            <p style="color: var(--ds-text-muted); font-size: 0.95rem; margin-bottom: 20px;">Crie a mensagem que será enviada. Pode ser um Email bonito ou uma notificação de WhatsApp.</p>
                            <a href="{{ route('user.connect.templates.create') }}" class="v2-btn-primary" style="height: 38px; padding: 0 20px; text-decoration: none;">Criar template</a>
                        @elseif($nextStep == 'campaigns')
                            <h4 style="font-weight: 700; color: var(--ds-text-main); margin-bottom: 10px;">Envie sua primeira Campanha.</h4>
                            <p style="color: var(--ds-text-muted); font-size: 0.95rem; margin-bottom: 20px;">Junte seu Segmento e seu Template e dispare para sua audiência.</p>
                            <a href="{{ route('user.connect.campaigns.create') }}" class="v2-btn-primary" style="height: 38px; padding: 0 20px; text-decoration: none;">Criar campanha</a>
                        @else
                            <h4 style="font-weight: 700; color: var(--ds-text-main); margin-bottom: 10px;">Tudo pronto!</h4>
                            <p style="color: var(--ds-text-muted); font-size: 0.95rem; margin-bottom: 20px;">Sua operação já está configurada. Lance sua campanha para ativar o Analytics.</p>
                        @endif
                    </div>
                </div>

                {{-- PROGRESS CARDS --}}
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
                    <div class="v2-settings-card" style="margin: 0;">
                        <div class="v2-settings-body" style="padding: 15px;">
                            <div style="color: var(--ds-text-muted); font-size: 0.75rem; font-weight: 600; text-transform: uppercase; margin-bottom: 5px;">Contatos</div>
                            <div style="font-size: 1.5rem; font-weight: 700; color: var(--ds-text-main);">{{ number_format($contactsCount) }}</div>
                        </div>
                    </div>
                    <div class="v2-settings-card" style="margin: 0;">
                        <div class="v2-settings-body" style="padding: 15px;">
                            <div style="color: var(--ds-text-muted); font-size: 0.75rem; font-weight: 600; text-transform: uppercase; margin-bottom: 5px;">Segmentos</div>
                            <div style="font-size: 1.5rem; font-weight: 700; color: var(--ds-text-main);">{{ number_format($segmentsCount) }}</div>
                        </div>
                    </div>
                    <div class="v2-settings-card" style="margin: 0;">
                        <div class="v2-settings-body" style="padding: 15px;">
                            <div style="color: var(--ds-text-muted); font-size: 0.75rem; font-weight: 600; text-transform: uppercase; margin-bottom: 5px;">Templates</div>
                            <div style="font-size: 1.5rem; font-weight: 700; color: var(--ds-text-main);">{{ number_format($templatesCount) }}</div>
                        </div>
                    </div>
                    <div class="v2-settings-card" style="margin: 0;">
                        <div class="v2-settings-body" style="padding: 15px;">
                            <div style="color: var(--ds-text-muted); font-size: 0.75rem; font-weight: 600; text-transform: uppercase; margin-bottom: 5px;">Campanhas</div>
                            <div style="font-size: 1.5rem; font-weight: 700; color: var(--ds-text-main);">{{ number_format($campaignsCount) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- RIGHT: CHECKLIST & TIMELINE --}}
            <div style="flex: 1; display: flex; flex-direction: column; gap: 20px;">
                <div class="v2-settings-card" style="flex: 1;">
                    <div class="v2-settings-header" style="padding: 15px 20px; border-bottom: 1px solid var(--ds-border-light);">
                        <h6 style="margin: 0; font-weight: 700; font-size: 0.95rem;">Progresso de Configuração</h6>
                    </div>
                    <div class="v2-settings-body" style="padding: 20px;">
                        
                        {{-- CHECKLIST ITEMS --}}
                        <div style="display: flex; flex-direction: column; gap: 15px; margin-bottom: 40px;">
                            <a href="{{ route('user.connect.contacts.index') }}" style="display: flex; align-items: center; gap: 10px; color: var(--ds-text-main); text-decoration: none; font-size: 0.95rem;">
                                @if($contactsCount > 0)
                                    <i class="fas fa-check-circle" style="color: var(--ds-success); font-size: 1.1rem;"></i> 
                                    <span style="text-decoration: line-through; opacity: 0.7;">Criar contatos</span>
                                @else
                                    <i class="far fa-circle" style="color: var(--ds-text-muted); font-size: 1.1rem;"></i> 
                                    <span style="font-weight: 500;">Criar contatos</span>
                                @endif
                            </a>

                            <a href="{{ route('user.connect.segments.index') }}" style="display: flex; align-items: center; gap: 10px; color: var(--ds-text-main); text-decoration: none; font-size: 0.95rem;">
                                @if($segmentsCount > 0)
                                    <i class="fas fa-check-circle" style="color: var(--ds-success); font-size: 1.1rem;"></i> 
                                    <span style="text-decoration: line-through; opacity: 0.7;">Criar segmentos</span>
                                @else
                                    <i class="far fa-circle" style="color: var(--ds-text-muted); font-size: 1.1rem;"></i> 
                                    <span style="font-weight: 500;">Criar segmentos</span>
                                @endif
                            </a>

                            <a href="{{ route('user.connect.templates.index') }}" style="display: flex; align-items: center; gap: 10px; color: var(--ds-text-main); text-decoration: none; font-size: 0.95rem;">
                                @if($templatesCount > 0)
                                    <i class="fas fa-check-circle" style="color: var(--ds-success); font-size: 1.1rem;"></i> 
                                    <span style="text-decoration: line-through; opacity: 0.7;">Criar templates</span>
                                @else
                                    <i class="far fa-circle" style="color: var(--ds-text-muted); font-size: 1.1rem;"></i> 
                                    <span style="font-weight: 500;">Criar templates</span>
                                @endif
                            </a>

                            <a href="{{ route('user.connect.campaigns.index') }}" style="display: flex; align-items: center; gap: 10px; color: var(--ds-text-main); text-decoration: none; font-size: 0.95rem;">
                                @if($campaignsCount > 0)
                                    <i class="fas fa-check-circle" style="color: var(--ds-success); font-size: 1.1rem;"></i> 
                                    <span style="text-decoration: line-through; opacity: 0.7;">Criar campanha</span>
                                @else
                                    <i class="far fa-circle" style="color: var(--ds-text-muted); font-size: 1.1rem;"></i> 
                                    <span style="font-weight: 500;">Criar campanha</span>
                                @endif
                            </a>

                            <div style="display: flex; align-items: center; gap: 10px; color: var(--ds-text-muted); font-size: 0.95rem;">
                                <i class="far fa-circle" style="font-size: 1.1rem;"></i> 
                                <span>Executar Dry Run (Teste)</span>
                            </div>
                        </div>

                        {{-- TIMELINE --}}
                        <div style="background: rgba(0,0,0,0.15); border-radius: 8px; padding: 25px 20px; display: flex; align-items: center; justify-content: space-between; font-size: 0.8rem; font-weight: 600; color: var(--ds-text-muted); border: 1px solid var(--ds-border-light);">
                            
                            <div style="text-align: center; color: {{ $contactsCount > 0 ? 'var(--ds-primary)' : 'var(--ds-text-main)' }};">
                                <i class="fas fa-users" style="font-size: 1.2rem; margin-bottom: 8px; display: block; opacity: {{ $contactsCount > 0 ? '1' : '0.5' }};"></i>
                                <span>Contatos</span>
                            </div>
                            
                            <i class="fas fa-chevron-right" style="opacity: 0.3; font-size: 0.8rem;"></i>
                            
                            <div style="text-align: center; color: {{ $segmentsCount > 0 ? 'var(--ds-primary)' : 'var(--ds-text-main)' }};">
                                <i class="fas fa-filter" style="font-size: 1.2rem; margin-bottom: 8px; display: block; opacity: {{ $segmentsCount > 0 ? '1' : '0.5' }};"></i>
                                <span>Segmentos</span>
                            </div>
                            
                            <i class="fas fa-chevron-right" style="opacity: 0.3; font-size: 0.8rem;"></i>
                            
                            <div style="text-align: center; color: {{ $templatesCount > 0 ? 'var(--ds-primary)' : 'var(--ds-text-main)' }};">
                                <i class="fas fa-layer-group" style="font-size: 1.2rem; margin-bottom: 8px; display: block; opacity: {{ $templatesCount > 0 ? '1' : '0.5' }};"></i>
                                <span>Templates</span>
                            </div>
                            
                            <i class="fas fa-chevron-right" style="opacity: 0.3; font-size: 0.8rem;"></i>
                            
                            <div style="text-align: center; color: {{ $campaignsCount > 0 ? 'var(--ds-primary)' : 'var(--ds-text-main)' }};">
                                <i class="fas fa-bullhorn" style="font-size: 1.2rem; margin-bottom: 8px; display: block; opacity: {{ $campaignsCount > 0 ? '1' : '0.5' }};"></i>
                                <span>Campanhas</span>
                            </div>

                            <i class="fas fa-chevron-right" style="opacity: 0.3; font-size: 0.8rem;"></i>

                            <div style="text-align: center; opacity: 0.4;">
                                <i class="fas fa-chart-pie" style="font-size: 1.2rem; margin-bottom: 8px; display: block;"></i>
                                <span>Analytics</span>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </div>
@endif

@endsection
