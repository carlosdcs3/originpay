@extends('frontend.layouts.user-v2')

@section('title', 'Detalhes da Disputa DSP-' . str_pad($dispute->id, 6, '0', STR_PAD_LEFT))

@section('content')

{{-- Banner de Retenção --}}
@if(!in_array($dispute->status->value, ['won', 'lost', 'canceled', 'closed']))
<div style="background: rgba(245,158,11,0.05); border: 1px solid rgba(245,158,11,0.2); padding: 16px; border-radius: 8px; display: flex; align-items: flex-start; gap: 16px; margin-bottom: 24px;">
    <i class="fas fa-info-circle" style="color: var(--ds-warning); font-size: 1.5rem; margin-top: 2px;"></i>
    <div>
        <div style="font-weight: 600; color: var(--ds-warning); font-size: 0.95rem; margin-bottom: 4px;">Este valor está temporariamente retido devido a uma disputa externa.</div>
        <div style="font-size: 0.85rem; color: var(--ds-text-muted); line-height: 1.5;">Isso não significa perda definitiva. A OriginPay está acompanhando o caso e precisa das suas informações para defender a transação perante o banco ou bandeira.</div>
    </div>
</div>
@endif

{{-- Header Resumo (KPI Style) --}}
<div class="v2-kpi-grid" style="margin-bottom: 24px;">
    <div class="v2-kpi-card">
        <div style="flex: 1;">
            <div class="v2-kpi-header">
                <div class="v2-kpi-title" style="font-size: 0.75rem;">Status Atual</div>
            </div>
            <div class="v2-kpi-value" style="font-size: 1.1rem; margin-top: 4px;">
                <span class="v2-badge" style="background: var(--ds-surface-hover); color: var(--ds-text-main);">{{ $dispute->status->label() }}</span>
            </div>
        </div>
    </div>
    
    <div class="v2-kpi-card">
        <div style="flex: 1;">
            <div class="v2-kpi-header">
                <div class="v2-kpi-title" style="font-size: 0.75rem;">Valor Total</div>
            </div>
            <div class="v2-kpi-value" style="font-size: 1.25rem; margin-top: 4px;">
                <span style="color: var(--ds-text-main);">R$ {{ number_format($dispute->amount_cents / 100, 2, ',', '.') }}</span>
            </div>
        </div>
    </div>

    <div class="v2-kpi-card">
        <div style="flex: 1;">
            <div class="v2-kpi-header">
                <div class="v2-kpi-title" style="font-size: 0.75rem;">Valor Retido</div>
            </div>
            <div class="v2-kpi-value" style="font-size: 1.25rem; margin-top: 4px;">
                <span style="color: var(--ds-danger);">R$ {{ number_format($dispute->retained_amount_cents / 100, 2, ',', '.') }}</span>
            </div>
        </div>
    </div>

    <div class="v2-kpi-card">
        <div style="flex: 1;">
            <div class="v2-kpi-header">
                <div class="v2-kpi-title" style="font-size: 0.75rem;">Prazo de Resposta</div>
            </div>
            <div class="v2-kpi-value" style="font-size: 1.1rem; margin-top: 4px;">
                @if($dispute->due_at)
                    <span style="color: var(--ds-text-main);">{{ $dispute->due_at->format('d/m/Y') }}</span>
                    <div style="font-size: 0.75rem; color: var(--ds-warning); font-weight: 500; margin-top: 2px;">({{ floor(now()->diffInHours($dispute->due_at)) }}h restantes)</div>
                @else
                    <span style="color: var(--ds-text-muted);">Indeterminado</span>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Workflow Progress --}}
<div class="v2-panel" style="margin-bottom: 24px; padding: 24px;">
    @php
        $w_status = $dispute->status->value;
        $step = 1;
        if(in_array($w_status, ['waiting_merchant_docs', 'docs_received'])) $step = 2;
        if(in_array($w_status, ['under_review', 'evidence_sent', 'gateway_review', 'bank_review', 'pending_decision'])) $step = 3;
        if(in_array($w_status, ['won', 'lost', 'canceled', 'closed'])) $step = 4;
    @endphp
    
    <div style="display: flex; justify-content: space-between; position: relative;">
        <div style="position: absolute; top: 12px; left: 0; right: 0; height: 2px; background: var(--ds-border); z-index: 0;"></div>
        <div style="position: absolute; top: 12px; left: 0; height: 2px; background: var(--ds-primary); z-index: 1; width: {{ ($step - 1) * 33.33 }}%; transition: width 0.5s ease;"></div>

        @foreach(['Notificação Recebida', 'Envio de Documentação', 'Análise de Risco', 'Resultado Final'] as $idx => $label)
            <div style="text-center; position: relative; z-index: 2; width: 120px; text-align: center;">
                <div style="width: 24px; height: 24px; border-radius: 50%; margin: 0 auto 8px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: bold; background: {{ $step >= ($idx+1) ? 'var(--ds-primary)' : 'var(--ds-surface)' }}; color: {{ $step >= ($idx+1) ? '#fff' : 'var(--ds-text-muted)' }}; border: 2px solid {{ $step >= ($idx+1) ? 'var(--ds-primary)' : 'var(--ds-border)' }};">
                    {{ $step > ($idx+1) ? '✓' : ($idx+1) }}
                </div>
                <div style="font-size: 0.7rem; font-weight: 600; color: {{ $step >= ($idx+1) ? 'var(--ds-text-main)' : 'var(--ds-text-muted)' }};">{{ $label }}</div>
            </div>
        @endforeach
    </div>
</div>

<div class="row">
    {{-- Left Column: Chat & Timeline --}}
    <div class="col-lg-8">
        
        {{-- Chat/Messages Panel --}}
        <div class="v2-panel" style="margin-bottom: 24px;">
            <div class="v2-panel-header">
                <h3 class="v2-panel-title">Comunicação do Caso</h3>
            </div>
            <div class="v2-panel-body" style="padding: 0;">
                <div style="height: 400px; overflow-y: auto; padding: 24px; background: rgba(0,0,0,0.01);">
                    @forelse($dispute->messages as $msg)
                        @php
                            $isMe = $msg->sender_type === 'merchant';
                            $align = $isMe ? 'flex-end' : 'flex-start';
                            $bg = $isMe ? 'var(--ds-primary)' : 'var(--ds-surface-hover)';
                            $color = $isMe ? '#fff' : 'var(--ds-text-main)';
                            $name = $isMe ? 'Você' : ($msg->sender_type === 'system' ? 'Sistema' : 'Time OriginPay');
                        @endphp
                        <div style="display: flex; justify-content: {{ $align }}; margin-bottom: 16px;">
                            <div style="max-width: 80%;">
                                <div style="font-size: 0.7rem; color: var(--ds-text-muted); margin-bottom: 4px; text-align: {{ $isMe ? 'right' : 'left' }};">
                                    {{ $name }} • {{ $msg->created_at->format('d/m/Y H:i') }}
                                </div>
                                <div style="background: {{ $bg }}; color: {{ $color }}; padding: 12px 16px; border-radius: 8px; font-size: 0.85rem; line-height: 1.5; white-space: pre-wrap; {{ $isMe ? 'border-bottom-right-radius: 0;' : 'border-bottom-left-radius: 0;' }}">
                                    {{ $msg->message }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center" style="color: var(--ds-text-muted); margin-top: 100px;">
                            <i class="fas fa-comments" style="font-size: 2rem; opacity: 0.5; margin-bottom: 8px;"></i>
                            <p style="font-size: 0.85rem;">Nenhuma mensagem registrada. <br>Utilize o campo abaixo para enviar informações ou dúvidas.</p>
                        </div>
                    @endforelse
                </div>
                
                {{-- Formulario de Envio --}}
                <div style="padding: 16px; border-top: 1px solid var(--ds-border);">
                    <form action="{{ route('user.disputes.message.send', $dispute->uuid) }}" method="POST">
                        @csrf
                        <div style="display: flex; gap: 12px;">
                            <textarea name="message" class="v2-input" rows="1" placeholder="Digite sua mensagem para a nossa equipe..." required style="flex: 1; resize: none; border-radius: 20px; padding-top: 10px;"></textarea>
                            <button type="submit" class="v2-btn-primary" style="border-radius: 20px; padding: 0 24px;">
                                Enviar <i class="fas fa-paper-plane ms-2"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Timeline Simplificada --}}
        <div class="v2-panel">
            <div class="v2-panel-header">
                <h3 class="v2-panel-title">Histórico Simplificado</h3>
            </div>
            <div class="v2-panel-body">
                <div style="border-left: 2px solid var(--ds-border); margin-left: 8px; padding-left: 20px; position: relative;">
                    @foreach($events as $event)
                    <div style="position: relative; margin-bottom: 24px;">
                        <div style="position: absolute; left: -27px; top: 0; width: 12px; height: 12px; background: var(--ds-surface); border: 2px solid var(--ds-primary); border-radius: 50%;"></div>
                        <div style="font-size: 0.75rem; font-weight: 600; color: var(--ds-primary); margin-bottom: 2px;">{{ $event->created_at->format('d/m/Y H:i') }}</div>
                        <div style="font-size: 0.85rem; font-weight: 600; color: var(--ds-text-main);">{{ $event->title }}</div>
                        @if($event->description)
                            <div style="font-size: 0.8rem; color: var(--ds-text-muted); margin-top: 4px;">{{ $event->description }}</div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

    </div>

    {{-- Right Column: Uploads & FAQ --}}
    <div class="col-lg-4">
        
        <div class="v2-panel" style="margin-bottom: 24px;">
            <div class="v2-panel-header">
                <h3 class="v2-panel-title">Ação Necessária</h3>
            </div>
            <div class="v2-panel-body">
                <p style="font-size: 0.8rem; color: var(--ds-text-muted); margin-bottom: 16px;">
                    Envie as evidências abaixo para fortalecermos a contestação perante o banco.
                </p>

                @if(session('error'))
                    <div style="background: rgba(239,68,68,0.1); color: var(--ds-danger); padding: 8px 12px; border-radius: 6px; font-size: 0.75rem; margin-bottom: 12px;">{{ session('error') }}</div>
                @endif
                @if($errors->any())
                    <div style="background: rgba(239,68,68,0.1); color: var(--ds-danger); padding: 8px 12px; border-radius: 6px; font-size: 0.75rem; margin-bottom: 12px;">Falha no upload do arquivo.</div>
                @endif

                @forelse($dispute->evidenceItems as $item)
                    <div style="background: var(--ds-surface-hover); border: 1px solid var(--ds-border); border-radius: 8px; padding: 12px; margin-bottom: 12px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <span style="font-size: 0.85rem; font-weight: 600; color: var(--ds-text-main);">{{ $item->label }}</span>
                            @if($item->status === 'pending')
                                <span class="v2-badge" style="background: rgba(245,158,11,0.1); color: var(--ds-warning);">Pendente</span>
                            @else
                                <span class="v2-badge" style="background: rgba(16,185,129,0.1); color: var(--ds-success);">Recebido</span>
                            @endif
                        </div>

                        @if($item->status === 'pending')
                            <form action="{{ route('user.disputes.evidence.upload', [$dispute->uuid, $item->id]) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div style="display: flex; gap: 8px; margin-top: 12px;">
                                    <input type="file" name="file" class="v2-input" style="font-size: 0.75rem; padding: 6px;" required accept=".pdf,.png,.jpg,.jpeg">
                                    <button type="submit" class="v2-btn-outline" style="padding: 4px 12px; font-size: 0.75rem;">Enviar</button>
                                </div>
                            </form>
                        @else
                            <div style="font-size: 0.75rem; color: var(--ds-text-muted); margin-top: 8px;">
                                <i class="fas fa-check-circle" style="color: var(--ds-success); margin-right: 4px;"></i> Documento anexado com sucesso.
                            </div>
                        @endif
                    </div>
                @empty
                    <div style="text-align: center; color: var(--ds-text-muted); padding: 24px 0;">
                        <i class="fas fa-check-circle" style="font-size: 1.5rem; color: var(--ds-success); margin-bottom: 8px; opacity: 0.7;"></i>
                        <p style="font-size: 0.8rem; margin: 0;">Nenhuma documentação pendente no momento.</p>
                    </div>
                @endforelse
            </div>
        </div>

    </div>
</div>

@endsection
