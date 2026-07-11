@extends('backend.finance.index')

@section('finance_title')<div class="d-none"></div>@endsection
@section('finance_desc')<div class="d-none"></div>@endsection
@section('finance_action')<div class="d-none"></div>@endsection

@section('finance_content')

{{-- FLASH MESSAGES --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show fs-sm py-2 px-3 mb-2" role="alert">
        <i class="la la-check-circle me-1"></i> {{ session('success') }}
        <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show fs-sm py-2 px-3 mb-2" role="alert">
        <i class="la la-times-circle me-1"></i> Houve um erro ao processar sua requisição.
        <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('locked_by'))
    <div class="alert alert-warning alert-dismissible fade show fs-sm py-2 px-3 mb-2" role="alert">
        <i class="la la-lock me-1"></i> <strong>Atenção:</strong> {{ session('locked_by') }}
        <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<style>
    @media (min-width: 1200px) {
        .workspace-wrapper {
            height: calc(100vh - 85px);
            overflow: hidden;
        }
    }
</style>
{{-- MAIN WORKSPACE WRAPPER - ZERO SCROLL (DESKTOP) --}}
<div class="d-flex flex-column workspace-wrapper" style="gap: 0.75rem;">

    {{-- 1. HEADER OPERACIONAL ULTRA-COMPACTO (DUAS LINHAS) --}}
    <x-ds.card class="p-3 flex-shrink-0">
        {{-- Linha 1 --}}
        <div class="d-flex flex-wrap align-items-center gap-3 mb-2">
            <span class="fs-5 fw-bold" style="color: var(--ds-text); font-family: monospace;">DSP-{{ date('Y') }}-{{ str_pad($dispute->id, 6, '0', STR_PAD_LEFT) }}</span>
            <x-ds.badge :status="$dispute->status->value" :label="$dispute->status->label()" />
            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 fs-xs">{{ $dispute->type->label() }}</span>
            
            <div class="border-start ms-2 ps-3 d-flex gap-3" style="border-color: var(--ds-border) !important;">
                <div class="d-flex gap-1 align-items-center"><span class="fs-xs text-muted text-uppercase fw-bold">Merc:</span><span class="fs-sm text-white fw-medium text-truncate" style="max-width:150px;">{{ $dispute->merchant->fullname ?? $dispute->merchant->name ?? '—' }}</span></div>
                <div class="d-flex gap-1 align-items-center"><span class="fs-xs text-muted text-uppercase fw-bold">Gtwy:</span><span class="fs-sm text-white fw-medium">{{ $dispute->gateway ?? '—' }}</span></div>
                <div class="d-flex gap-1 align-items-center"><span class="fs-xs text-muted text-uppercase fw-bold">Cli:</span><span class="fs-sm text-white fw-medium">João da Silva</span></div>
            </div>
            
            {{-- Ações isoladas na direita --}}
            <div class="ms-auto d-flex gap-2">
                <a href="{{ route('admin.finance.chargebacks.show', $dispute->uuid) }}" class="btn btn-sm btn-outline-secondary py-1 px-2 fs-xs"><i class="la la-sync"></i></a>
            </div>
        </div>

        {{-- Linha 2 --}}
        <div class="d-flex flex-wrap align-items-center gap-4">
            <div class="d-flex gap-2 align-items-center"><span class="fs-xs text-muted text-uppercase fw-bold">Valor:</span><span class="fs-sm fw-bold text-white">R$ {{ $dispute->formatted_amount }}</span></div>
            <div class="d-flex gap-2 align-items-center"><span class="fs-xs text-muted text-uppercase fw-bold">Retido:</span><span class="fs-sm fw-bold text-white">R$ {{ $dispute->formatted_retained_amount }}</span></div>
            <div class="d-flex gap-2 align-items-center"><span class="fs-xs text-muted text-uppercase fw-bold">SLA:</span><span class="fs-sm fw-bold text-warning">{{ $dispute->due_at ? str_replace('daqui a ', '', $dispute->due_at->diffForHumans()) : 'N/A' }}</span></div>
            <div class="d-flex gap-2 align-items-center"><span class="fs-xs text-muted text-uppercase fw-bold">Última Atualização:</span><span class="fs-sm text-white fw-medium">{{ $dispute->updated_at->format('d/m/Y H:i') }}</span></div>
            <div class="d-flex gap-2 align-items-center"><span class="fs-xs text-muted text-uppercase fw-bold">Responsável:</span><span class="fs-sm text-white fw-medium">Risk Operations</span></div>
        </div>
    </x-ds.card>

    {{-- MAIN GRID: LEFT 75% / RIGHT 25% --}}
    <div class="row g-3 flex-grow-1" style="min-height: 0;">
        
        {{-- LEFT COLUMN (75%) --}}
        <div class="col-12 col-xl-9 d-flex flex-column h-100">
            
            {{-- 5. WORKFLOW PROGRESS BAR --}}
            <div class="d-flex justify-content-between align-items-start px-4 mb-3 flex-shrink-0 position-relative w-100 mx-auto" style="max-width: 800px;">
                <div class="position-absolute" style="top: 10px; left: 10%; right: 10%; height: 2px; background: var(--ds-border); z-index: 1;"></div>
                
                @php
                    // Simplified progress logic based on status
                    $prog = 1;
                    if (in_array($dispute->status->value, ['waiting_merchant_docs', 'in_review'])) $prog = 2;
                    if (in_array($dispute->status->value, ['in_gateway_review'])) $prog = 4;
                    if (in_array($dispute->status->value, ['won', 'lost', 'canceled'])) $prog = 5;
                    $fillWidth = ($prog - 1) * 25;
                @endphp
                <div class="position-absolute" style="top: 10px; left: 10%; width: {{ $fillWidth }}%; height: 2px; background: var(--ds-primary); z-index: 1;"></div>
                
                <div class="d-flex flex-column align-items-center gap-1 position-relative" style="z-index: 2; background: var(--ds-bg); padding: 0 10px;">
                    <div class="rounded-circle {{ $prog >= 1 ? 'bg-primary' : 'border border-2' }}" style="width: 20px; height: 20px; {{ $prog < 1 ? 'border-color: var(--ds-border) !important; background: var(--ds-bg);' : '' }} box-shadow: 0 0 0 4px var(--ds-bg);"></div>
                    <span class="fs-xs {{ $prog >= 1 ? 'fw-bold text-primary' : 'fw-medium text-muted' }} mt-1">Recebido</span>
                </div>
                <div class="d-flex flex-column align-items-center gap-1 position-relative" style="z-index: 2; background: var(--ds-bg); padding: 0 10px;">
                    <div class="rounded-circle {{ $prog >= 2 ? 'bg-primary' : 'border border-2' }}" style="width: 20px; height: 20px; {{ $prog < 2 ? 'border-color: var(--ds-border) !important; background: var(--ds-bg);' : '' }} box-shadow: 0 0 0 4px var(--ds-bg);"></div>
                    <span class="fs-xs {{ $prog >= 2 ? 'fw-bold text-primary' : 'fw-medium text-muted' }} mt-1">Documentação</span>
                </div>
                <div class="d-flex flex-column align-items-center gap-1 position-relative" style="z-index: 2; background: var(--ds-bg); padding: 0 10px;">
                    <div class="rounded-circle {{ $prog >= 3 ? 'bg-primary' : 'border border-2' }}" style="width: 20px; height: 20px; {{ $prog < 3 ? 'border-color: var(--ds-border) !important; background: var(--ds-bg);' : '' }} box-shadow: 0 0 0 4px var(--ds-bg);"></div>
                    <span class="fs-xs {{ $prog >= 3 ? 'fw-bold text-primary' : 'fw-medium text-muted' }} mt-1">Defesa</span>
                </div>
                <div class="d-flex flex-column align-items-center gap-1 position-relative" style="z-index: 2; background: var(--ds-bg); padding: 0 10px;">
                    <div class="rounded-circle {{ $prog >= 4 ? 'bg-primary' : 'border border-2' }}" style="width: 20px; height: 20px; {{ $prog < 4 ? 'border-color: var(--ds-border) !important; background: var(--ds-bg);' : '' }} box-shadow: 0 0 0 4px var(--ds-bg);"></div>
                    <span class="fs-xs {{ $prog >= 4 ? 'fw-bold text-primary' : 'fw-medium text-muted' }} mt-1">Gateway</span>
                </div>
                <div class="d-flex flex-column align-items-center gap-1 position-relative" style="z-index: 2; background: var(--ds-bg); padding: 0 10px;">
                    <div class="rounded-circle {{ $prog >= 5 ? 'bg-primary' : 'border border-2' }}" style="width: 20px; height: 20px; {{ $prog < 5 ? 'border-color: var(--ds-border) !important; background: var(--ds-bg);' : '' }} box-shadow: 0 0 0 4px var(--ds-bg);"></div>
                    <span class="fs-xs {{ $prog >= 5 ? 'fw-bold text-primary' : 'fw-medium text-muted' }} mt-1">Resultado</span>
                </div>
            </div>

            {{-- 7. TABS (STRIPE STYLE) --}}
            <ul class="nav nav-tabs border-bottom mb-0 flex-shrink-0" id="workspaceTabs" role="tablist" style="border-color: var(--ds-border) !important;">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-medium fs-sm px-4 py-3 border-0" id="comunicacao-tab" data-bs-toggle="tab" data-bs-target="#comunicacao" type="button" role="tab" style="color: var(--ds-text); border-bottom: 2px solid var(--ds-primary) !important; background: transparent;">Comunicação</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-medium fs-sm px-4 py-3 border-0" id="timeline-tab" data-bs-toggle="tab" data-bs-target="#timeline" type="button" role="tab" style="color: var(--ds-text-muted); background: transparent;">Timeline</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-medium fs-sm px-4 py-3 border-0" id="financeiro-tab" data-bs-toggle="tab" data-bs-target="#financeiro" type="button" role="tab" style="color: var(--ds-text-muted); background: transparent;">Financeiro</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-medium fs-sm px-4 py-3 border-0" id="evidencias-tab" data-bs-toggle="tab" data-bs-target="#evidencias" type="button" role="tab" style="color: var(--ds-text-muted); background: transparent;">Evidências</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-medium fs-sm px-4 py-3 border-0" id="auditoria-tab" data-bs-toggle="tab" data-bs-target="#auditoria" type="button" role="tab" style="color: var(--ds-text-muted); background: transparent;">Auditoria</button>
                </li>
            </ul>

            {{-- TAB CONTENTS (SCROLLABLE AREA) --}}
            <div class="tab-content flex-grow-1" id="workspaceTabsContent" style="min-height: 0;">
                
                {{-- COMUNICAÇÃO --}}
                <div class="tab-pane fade show active h-100" id="comunicacao" role="tabpanel">
                    <x-ds.card class="p-0 h-100 d-flex flex-column border-top-0" style="border-top-left-radius: 0; border-top-right-radius: 0;">
                        {{-- MENSAGENS ROSTÁVEIS --}}
                        <div class="p-4" style="flex: 1; overflow-y: auto;">
                            @forelse($dispute->messages as $msg)
                                @php
                                    $bgColor = match($msg->sender_type) {
                                        'internal' => 'bg-warning text-dark',
                                        'system' => 'bg-secondary text-white',
                                        'admin' => 'bg-primary text-white',
                                        default => 'bg-secondary text-white'
                                    };
                                    $icon = match($msg->sender_type) {
                                        'internal' => 'la-sticky-note',
                                        'system' => 'la-robot',
                                        'admin' => 'la-shield-alt',
                                        default => 'la-store'
                                    };
                                    $labelName = match($msg->sender_type) {
                                        'internal' => 'Nota Interna (Risk)',
                                        'system' => 'Sistema OriginPay',
                                        'admin' => 'OriginPay Risk',
                                        default => ($dispute->merchant->fullname ?? $dispute->merchant->name ?? 'Lojista')
                                    };
                                    $labelRole = match($msg->sender_type) {
                                        'internal', 'admin' => 'Analista',
                                        'system' => 'Automático',
                                        default => 'Merchant'
                                    };
                                @endphp
                                <div class="d-flex gap-3 mb-4">
                                    <div class="rounded {{ $bgColor }} d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; flex-shrink: 0;">
                                        <i class="la {{ $icon }} fs-5"></i>
                                    </div>
                                    <div>
                                        <div class="d-flex align-items-baseline gap-2 mb-1">
                                            <span class="fw-bold fs-sm" style="color: var(--ds-text);">
                                                {{ $labelName }}
                                            </span>
                                            <span class="fs-xs text-muted">{{ $labelRole }}</span>
                                            <span class="fs-xs text-muted">{{ $msg->created_at->format('d/m/Y H:i') }}</span>
                                        </div>
                                        <div class="fs-sm mt-1 p-2 rounded {{ $msg->sender_type === 'internal' ? 'bg-warning bg-opacity-10 border border-warning' : '' }}" style="color: var(--ds-text); line-height: 1.5; white-space: pre-wrap;">{{ $msg->message }}</div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center my-5 py-5 text-muted">
                                    <i class="la la-comment-slash fs-2 mb-2 opacity-50"></i>
                                    <p class="fs-sm">Nenhuma comunicação registrada.</p>
                                </div>
                            @endforelse
                        </div>

                        {{-- INPUT FIXO --}}
                        <div class="p-3 border-top flex-shrink-0" style="border-color: var(--ds-border) !important; background: var(--ds-bg);">
                            <form action="{{ route('admin.finance.chargebacks.message', $dispute->uuid) }}" method="POST">
                                @csrf
                                <div class="border rounded p-2" style="background: var(--ds-surface); border-color: var(--ds-border) !important;">
                                    <textarea name="message" class="form-control border-0 fs-sm p-2 shadow-none" rows="2" placeholder="Responder lojista ou adicionar nota interna..." style="background: transparent; color: var(--ds-text); resize: none;" required></textarea>
                                    <div class="d-flex justify-content-between align-items-center mt-2 px-2">
                                        <div class="d-flex gap-3 align-items-center">
                                            <button type="button" class="btn btn-sm btn-link text-muted p-0 text-decoration-none"><i class="la la-paperclip fs-5"></i> Anexar</button>
                                            <div class="form-check m-0">
                                                <input class="form-check-input" type="checkbox" name="is_internal_note" value="1" id="internalNoteCheck">
                                                <label class="form-check-label fs-xs text-muted" for="internalNoteCheck">Nota Interna (Invisível ao Lojista)</label>
                                            </div>
                                        </div>
                                        <button type="submit" class="btn btn-sm btn-primary px-4 fw-medium">Enviar</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </x-ds.card>
                </div>

                {{-- TIMELINE --}}
                <div class="tab-pane fade h-100" id="timeline" role="tabpanel">
                    <x-ds.card class="p-4 h-100 overflow-auto border-top-0" style="border-top-left-radius: 0; border-top-right-radius: 0;">
                        <div class="d-flex flex-column gap-4 ms-2 border-start ps-3" style="border-color: var(--ds-border) !important;">
                            @forelse($dispute->events as $event)
                                <div class="position-relative">
                                    <div class="position-absolute rounded-circle bg-primary" style="width: 12px; height: 12px; left: -22px; top: 4px;"></div>
                                    <div class="fs-sm fw-bold text-white">{{ $event->title }}</div>
                                    <div class="fs-xs text-muted mb-1">{{ $event->created_at->format('d/m/Y H:i') }} • {{ $event->metadata['source'] ?? 'Sistema' }}</div>
                                    @if($event->description)
                                        <div class="fs-xs text-muted mt-1">{{ $event->description }}</div>
                                    @endif
                                </div>
                            @empty
                                <div class="text-center text-muted w-100 my-4">
                                    <p class="fs-sm">Nenhum evento na timeline.</p>
                                </div>
                            @endforelse
                        </div>
                    </x-ds.card>
                </div>

                {{-- FINANCEIRO --}}
                <div class="tab-pane fade h-100" id="financeiro" role="tabpanel">
                    <x-ds.card class="p-4 h-100 overflow-auto border-top-0 d-flex flex-column justify-content-center" style="border-top-left-radius: 0; border-top-right-radius: 0;">
                        <div class="d-flex justify-content-between text-center position-relative w-100 px-4">
                            <div class="position-absolute" style="top: 15px; left: 10%; right: 10%; height: 2px; background: var(--ds-border); z-index: 1;"></div>
                            
                            <div style="z-index: 2; background: var(--ds-surface); padding: 0 10px;">
                                <div class="rounded-circle bg-success mx-auto mb-2" style="width: 32px; height: 32px; line-height: 32px;"><i class="la la-check text-white"></i></div>
                                <div class="fs-xs fw-bold">Pagamento</div>
                                <div class="fs-xs text-success">+ R$ {{ $dispute->formatted_amount }}</div>
                            </div>
                            <div style="z-index: 2; background: var(--ds-surface); padding: 0 10px;">
                                <div class="rounded-circle bg-success mx-auto mb-2" style="width: 32px; height: 32px; line-height: 32px;"><i class="la la-check text-white"></i></div>
                                <div class="fs-xs fw-bold">Liquidação</div>
                                <div class="fs-xs text-success">Aprovado</div>
                            </div>
                            <div style="z-index: 2; background: var(--ds-surface); padding: 0 10px;">
                                <div class="rounded-circle border border-secondary mx-auto mb-2" style="width: 32px; height: 32px; line-height: 32px;"><i class="la la-minus text-muted"></i></div>
                                <div class="fs-xs fw-bold text-muted">Repasse</div>
                                <div class="fs-xs text-muted">Cancelado</div>
                            </div>
                            <div style="z-index: 2; background: var(--ds-surface); padding: 0 10px;">
                                <div class="rounded-circle bg-danger mx-auto mb-2" style="width: 32px; height: 32px; line-height: 32px;"><i class="la la-lock text-white"></i></div>
                                <div class="fs-xs fw-bold">Retenção</div>
                                <div class="fs-xs text-danger">- R$ {{ $dispute->formatted_retained_amount }}</div>
                            </div>
                            <div style="z-index: 2; background: var(--ds-surface); padding: 0 10px;">
                                <div class="rounded-circle border border-secondary mx-auto mb-2" style="width: 32px; height: 32px; line-height: 32px;"></div>
                                <div class="fs-xs fw-bold text-muted">Resultado</div>
                                <div class="fs-xs text-muted">{{ $dispute->status->label() }}</div>
                            </div>
                        </div>
                    </x-ds.card>
                </div>

                {{-- EVIDÊNCIAS --}}
                <div class="tab-pane fade h-100" id="evidencias" role="tabpanel">
                    <x-ds.card class="p-0 h-100 overflow-auto border-top-0" style="border-top-left-radius: 0; border-top-right-radius: 0;">
                        <table class="table table-borderless table-sm fs-xs m-0">
                            <tbody>
                                @forelse($dispute->evidenceItems as $item)
                                    <tr class="border-bottom" style="border-color: var(--ds-border) !important;">
                                        <td class="p-3" style="width: 40px;"><i class="la la-file{{ $item->file_path ? '-pdf text-danger' : ' text-muted' }} fs-5"></i></td>
                                        <td class="p-3 text-white fw-medium w-100">{{ $item->label }}</td>
                                        <td class="p-3"><x-ds.badge :status="$item->status === 'validated' ? 'paid' : ($item->status === 'pending' ? 'pending' : 'canceled')" :label="ucfirst($item->status)" /></td>
                                        <td class="p-3">
                                            @if($item->file_path)
                                                <button class="btn btn-sm btn-outline-secondary py-0 px-2"><i class="la la-download"></i></button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="p-4 text-center text-muted">Nenhuma evidência solicitada ou anexada.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </x-ds.card>
                </div>

                {{-- AUDITORIA --}}
                <div class="tab-pane fade h-100" id="auditoria" role="tabpanel">
                    <x-ds.card class="p-0 h-100 overflow-auto border-top-0" style="border-top-left-radius: 0; border-top-right-radius: 0;">
                        <table class="table table-borderless table-sm fs-xs m-0">
                            <thead style="background: var(--ds-bg);">
                                <tr>
                                    <th class="p-2 text-muted">Hora</th>
                                    <th class="p-2 text-muted">Usuário/Origem</th>
                                    <th class="p-2 text-muted">Evento</th>
                                    <th class="p-2 text-muted">IP</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($dispute->events as $event)
                                <tr class="border-bottom" style="border-color: var(--ds-border) !important;">
                                    <td class="p-2 text-muted">{{ $event->created_at->format('d/m H:i:s') }}</td>
                                    <td class="p-2 text-white">{{ $event->metadata['source'] ?? 'System' }} (ID: {{ $event->metadata['user_id'] ?? 'SYS' }})</td>
                                    <td class="p-2 text-white">{{ $event->event_type }}</td>
                                    <td class="p-2 text-muted">{{ $event->metadata['ip'] ?? '127.0.0.1' }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="p-4 text-center text-muted">Nenhum log de auditoria.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </x-ds.card>
                </div>
            </div>
        </div>

        {{-- RIGHT COLUMN (25% - STICKY METADATA) --}}
        <div class="col-12 col-xl-3 h-100 overflow-auto pe-1 pb-2">
            <div class="d-flex flex-column gap-3">
                
                {{-- 9. Próxima Ação Obrigatória (Exemplo dinâmico) --}}
                @if(in_array($dispute->status->value, ['open', 'waiting_merchant_docs']))
                <x-ds.card class="p-3" style="background: rgba(245, 158, 11, 0.05); border: 1px solid rgba(245, 158, 11, 0.3) !important;">
                    <div class="fs-xs fw-bold text-uppercase mb-2 text-warning" style="letter-spacing: 0.05em;">Próxima Ação</div>
                    <div class="fs-sm fw-bold text-white mb-1">Aguardando Lojista</div>
                    <div class="fs-xs text-muted mb-3">O caso pode ser perdido se a documentação não for enviada.</div>
                    
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <span class="fs-xs fw-bold text-warning"><i class="la la-clock"></i> {{ $dispute->due_at ? $dispute->due_at->diffInDays() : 5 }} dias restantes</span>
                        <button class="btn btn-sm btn-warning text-dark fw-bold px-3 py-1 fs-xs" data-bs-toggle="modal" data-bs-target="#requestDocumentModal">Solicitar</button>
                    </div>
                </x-ds.card>
                @endif

                {{-- 8. Checklist --}}
                <x-ds.card class="p-0">
                    <div class="px-3 py-2 border-bottom fs-xs fw-bold text-uppercase" style="border-color: var(--ds-border) !important; color: var(--ds-text-muted); background: var(--ds-bg); letter-spacing: 0.05em; border-radius: 8px 8px 0 0;">
                        Checklist Operacional
                    </div>
                    <div class="p-2 d-flex flex-column gap-1">
                        @forelse($dispute->evidenceItems as $item)
                        <div class="d-flex align-items-center justify-content-between p-1 rounded" style="background: rgba(255,255,255,0.02);">
                            <div class="d-flex align-items-center gap-2">
                                <i class="la {{ $item->status === 'validated' ? 'la-check-square text-primary' : 'la-square text-muted' }} fs-5"></i>
                                <span class="fs-sm text-white">{{ $item->label }}</span>
                            </div>
                            <x-ds.badge :status="$item->status === 'validated' ? 'paid' : ($item->status === 'pending' ? 'pending' : 'canceled')" :label="ucfirst($item->status)" />
                        </div>
                        @empty
                        <div class="text-center text-muted fs-xs py-2">Nenhum documento exigido.</div>
                        @endforelse
                    </div>
                </x-ds.card>

                {{-- 10. Ações Rápidas --}}
                <x-ds.card class="p-0">
                    <div class="px-3 py-2 border-bottom fs-xs fw-bold text-uppercase" style="border-color: var(--ds-border) !important; color: var(--ds-text-muted); background: var(--ds-bg); letter-spacing: 0.05em; border-radius: 8px 8px 0 0;">
                        Ações
                    </div>
                    <div class="p-3 d-flex flex-column gap-2">
                        <button class="btn btn-sm btn-outline-secondary w-100 text-start fw-medium" data-bs-toggle="modal" data-bs-target="#requestDocumentModal"><i class="la la-file-upload me-1 text-muted"></i> Solicitar Documento</button>
                        
                        <form action="{{ route('admin.finance.chargebacks.notify_merchant', $dispute->uuid) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-secondary w-100 text-start fw-medium"><i class="la la-envelope me-1 text-muted"></i> Notificar Lojista</button>
                        </form>
                        
                        <form action="{{ route('admin.finance.chargebacks.send_gateway', $dispute->uuid) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-secondary w-100 text-start fw-medium"><i class="la la-share me-1 text-muted"></i> Enviar Gateway</button>
                        </form>
                        
                        <div class="border-bottom my-2" style="border-color: var(--ds-border) !important;"></div>
                        
                        <form action="{{ route('admin.finance.chargebacks.release_retention', $dispute->uuid) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-success w-100 text-start fw-bold"><i class="la la-unlock me-1"></i> Liberar Retenção</button>
                        </form>
                        
                        <button class="btn btn-sm btn-danger w-100 text-start fw-bold" data-bs-toggle="modal" data-bs-target="#closeDisputeModal"><i class="la la-times-circle me-1"></i> Encerrar Caso</button>
                    </div>
                </x-ds.card>

            </div>
        </div>
    </div>
</div>

{{-- MODALS --}}

{{-- Solicitar Documento Modal --}}
<div class="modal fade" id="requestDocumentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background: var(--ds-surface); border: 1px solid var(--ds-border);">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fs-5 fw-bold text-white">Solicitar Documento</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.finance.chargebacks.request_document', $dispute->uuid) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fs-xs fw-bold text-muted text-uppercase">Tipo de Documento</label>
                        <select name="document_type" class="form-select bg-dark text-white border-secondary" required onchange="document.getElementById('docLabel').value = this.options[this.selectedIndex].text;">
                            <option value="">Selecione...</option>
                            <option value="invoice">Nota Fiscal</option>
                            <option value="pix_receipt">Comprovante Pix</option>
                            <option value="delivery_proof">Comprovante de entrega</option>
                            <option value="tracking">Rastreamento</option>
                            <option value="chat_log">Registro de Conversa</option>
                            <option value="contract">Contrato</option>
                            <option value="other">Outro</option>
                        </select>
                        <input type="hidden" name="label" id="docLabel">
                    </div>
                    <p class="fs-xs text-muted mb-0">Um item pendente será adicionado ao checklist e o lojista será notificado (MOCK).</p>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-primary">Solicitar</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Encerrar Caso Modal --}}
<div class="modal fade" id="closeDisputeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background: var(--ds-surface); border: 1px solid var(--ds-border);">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fs-5 fw-bold text-danger">Encerrar Disputa</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.finance.chargebacks.close', $dispute->uuid) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fs-xs fw-bold text-muted text-uppercase">Resultado Final</label>
                        <select name="close_result" class="form-select bg-dark text-white border-secondary" required>
                            <option value="">Selecione o resultado...</option>
                            <option value="won">Ganha (A favor do Lojista)</option>
                            <option value="lost">Perdida (A favor do Cliente)</option>
                            <option value="canceled">Cancelada / Invalida</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fs-xs fw-bold text-muted text-uppercase">Motivo (Interno)</label>
                        <textarea name="reason" class="form-control bg-dark text-white border-secondary" rows="3" placeholder="Ex: Falta de documentação, fraude comprovada..."></textarea>
                    </div>
                    <div class="alert alert-warning py-2 fs-xs mb-0">
                        Atenção: Esta ação atualizará o status da disputa e não pode ser desfeita. (MOCK)
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-danger">Confirmar Encerramento</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
