@extends('frontend.user.developer.index')
@section('title', __('Detalhes do Webhook'))

@section('user_developer_content')

<div class="mb-4">
    <a href="{{ route('user.developer.webhooks.index') }}" class="v2-btn-tertiary" style="margin-bottom: 16px; display: inline-flex; text-decoration: none;">
        <i class="fas fa-arrow-left" style="margin-right: 8px;"></i> Voltar para Webhooks
    </a>
    
    <div class="v2-page-header" style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: flex-start;">
        <div>
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                <h2 class="v2-page-title" style="font-size: 1.5rem; font-weight: 700; margin: 0; color: var(--ds-text-main); font-family: monospace; word-break: break-all;">
                    {{ $endpoint->url }}
                </h2>
                @if($endpoint->environment === 'live')
                    <span class="v2-badge" style="background: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.2);">Live</span>
                @else
                    <span class="v2-badge" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.2);">Test</span>
                @endif
            </div>
            <p class="v2-page-subtitle" style="font-size: 0.875rem; color: var(--ds-text-muted); margin: 0;">Detalhes e histórico de entrega deste endpoint.</p>
        </div>
        
        <form action="{{ route('user.developer.webhooks.test', $endpoint->id) }}" method="POST" class="m-0">
            @csrf
            <button class="v2-btn-secondary" type="submit">
                <i class="fas fa-paper-plane" style="margin-right: 8px;"></i> Enviar Evento Teste
            </button>
        </form>
    </div>
</div>

@if(session('success'))
<div class="v2-settings-card" style="padding: 16px 24px; margin-bottom: 24px; border: 1px solid rgba(16, 185, 129, 0.3); background: rgba(16, 185, 129, 0.05); display: flex; align-items: center; justify-content: space-between;">
    <div style="display: flex; align-items: center; gap: 12px;">
        <i class="fas fa-check-circle text-success" style="font-size: 1.25rem;"></i>
        <span style="font-weight: 500; color: var(--ds-text-main);">{{ session('success') }}</span>
    </div>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close" style="filter: invert(1) grayscale(100%) brightness(200%);"></button>
</div>
@endif

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px; margin-bottom: 24px;">
    
    <!-- Card Informações Gerais -->
    <div class="v2-settings-card" style="padding: 24px; display: flex; flex-direction: column;">
        <h3 style="font-size: 1rem; font-weight: 600; color: var(--ds-text-main); margin: 0 0 16px;">Informações Gerais</h3>
        <div style="flex: 1;">
            <div style="font-size: 0.75rem; color: var(--ds-text-muted); margin-bottom: 4px; text-transform: uppercase; font-weight: 600;">Status de Entrega</div>
            <div style="display: flex; align-items: flex-end; margin-bottom: 12px;">
                <h2 style="margin: 0; font-weight: 700; color: var(--ds-text-main); font-size: 2rem; line-height: 1;">
                    {{ $successCount > 0 || $failureCount > 0 ? round(($successCount / ($successCount + $failureCount)) * 100) : 100 }}%
                </h2>
                <span style="color: var(--ds-text-muted); margin-left: 8px; padding-bottom: 2px;">sucesso</span>
            </div>
            <div style="display: flex; gap: 16px; font-size: 0.875rem;">
                <div><span class="text-success"><i class="fas fa-check"></i> {{ $successCount }}</span> sucesso</div>
                <div><span class="text-danger"><i class="fas fa-times"></i> {{ $failureCount }}</span> falhas</div>
            </div>
        </div>
    </div>
    
    <!-- Card Endpoint & Secret -->
    <div class="v2-settings-card" style="padding: 24px; display: flex; flex-direction: column;">
        <h3 style="font-size: 1rem; font-weight: 600; color: var(--ds-text-main); margin: 0 0 16px;">Endpoint & Segurança</h3>
        <div style="margin-bottom: 16px;">
            <div style="font-size: 0.75rem; color: var(--ds-text-muted); margin-bottom: 4px; text-transform: uppercase; font-weight: 600;">Tempo Médio de Resposta</div>
            <div style="display: flex; align-items: flex-end; margin-bottom: 4px;">
                <h2 style="margin: 0; font-weight: 700; color: var(--ds-text-main); font-size: 1.5rem; line-height: 1;">
                    {{ round($avgLatency) }} <span style="font-size: 0.875rem; color: var(--ds-text-muted); font-weight: 400;">ms</span>
                </h2>
            </div>
            <div style="font-size: 0.75rem; color: var(--ds-text-muted);">Recomendado: < 3000ms</div>
        </div>
        
        <div style="margin-top: auto;">
            <div style="font-size: 0.75rem; color: var(--ds-text-muted); margin-bottom: 8px; text-transform: uppercase; font-weight: 600;">Segredo do Webhook (HMAC)</div>
            <div style="display: flex; align-items: center; gap: 8px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; padding: 6px 12px;">
                <input type="password" id="webhookSecret" value="{{ $endpoint->secret }}" readonly style="flex: 1; background: transparent; border: none; color: var(--ds-text-main); font-family: monospace; font-size: 0.875rem; outline: none; width: 100%;">
                <button type="button" class="v2-btn-tertiary" onclick="toggleSecret()" id="toggleBtn" style="padding: 4px; height: 28px; width: 28px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-eye"></i>
                </button>
                <button type="button" class="v2-btn-secondary" onclick="copySecret()" id="copyBtn" style="padding: 4px; height: 28px; width: 28px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-copy"></i>
                </button>
            </div>
        </div>
    </div>
    
</div>

<script>
    function toggleSecret() {
        var input = document.getElementById("webhookSecret");
        var icon = document.querySelector("#toggleBtn i");
        if (input.type === "password") {
            input.type = "text";
            icon.className = "fas fa-eye-slash";
        } else {
            input.type = "password";
            icon.className = "fas fa-eye";
        }
    }
    function copySecret() {
        var copyText = document.getElementById("webhookSecret");
        var oldType = copyText.type;
        copyText.type = "text";
        copyText.select();
        copyText.setSelectionRange(0, 99999);
        document.execCommand("copy");
        copyText.type = oldType;
        
        var btn = document.getElementById("copyBtn");
        btn.innerHTML = '<i class="fas fa-check"></i>';
        setTimeout(function() { btn.innerHTML = '<i class="fas fa-copy"></i>'; }, 2000);
    }
</script>

<!-- Histórico -->
<h3 style="font-size: 1.125rem; font-weight: 600; color: var(--ds-text-main); margin: 32px 0 16px;">Histórico de Entregas (Logs)</h3>

@forelse($endpoint->deliveries as $delivery)
<div class="v2-settings-card" style="padding: 20px 24px; margin-bottom: 16px;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div style="display: flex; align-items: center; gap: 16px;">
            @if($delivery->success)
                <span class="v2-badge v2-badge-success" style="font-family: monospace;">{{ $delivery->http_status ?? 200 }} OK</span>
            @else
                <span class="v2-badge v2-badge-error" style="font-family: monospace;">{{ $delivery->http_status ?? 'ERR' }} FAILED</span>
            @endif
            
            <div>
                <div style="font-weight: 600; color: var(--ds-text-main); font-size: 0.95rem; font-family: monospace;">
                    POST <span style="color: var(--ds-primary-light);">{{ $delivery->event_type }}</span>
                </div>
                <div style="font-size: 0.75rem; color: var(--ds-text-muted); margin-top: 4px;">
                    {{ $delivery->created_at->format('d/m/Y H:i:s') }} &bull; {{ $delivery->latency_ms }} ms &bull; {{ $delivery->event_id }}
                </div>
            </div>
        </div>
        
        <div style="display: flex; gap: 8px;">
            <button class="v2-btn-secondary" type="button" data-bs-toggle="modal" data-bs-target="#deliveryModal{{ $delivery->id }}" style="padding: 6px 16px; height: 32px; font-size: 0.8125rem;">
                Detalhes do Payload
            </button>
            @if(!$delivery->success)
                <form action="{{ route('user.developer.webhooks.delivery.retry', $delivery->id) }}" method="POST" style="margin: 0;">
                    @csrf
                    <button type="submit" class="v2-btn-tertiary" style="padding: 6px 16px; height: 32px; font-size: 0.8125rem; color: #f59e0b;">
                        <i class="fas fa-redo-alt" style="margin-right: 6px;"></i> Reenviar
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>

{{-- Delivery Details Modal --}}
<div class="modal fade" id="deliveryModal{{ $delivery->id }}" tabindex="-1" inert>
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius: 16px; border: 1px solid var(--ds-border-light); background: var(--ds-bg-card);">
            <div class="modal-header" style="border-bottom: 1px solid var(--ds-border-light); padding: 24px;">
                <div>
                    <h5 class="modal-title fw-bold" style="color: var(--ds-text-main); font-size: 1.125rem; margin-bottom: 4px;">Detalhes da Entrega</h5>
                    <div style="color: var(--ds-text-muted); font-size: 0.8125rem; font-family: monospace;">{{ $delivery->event_id }}</div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="padding: 24px; max-height: 70vh; overflow-y: auto;">
                
                <div class="v2-settings-card" style="padding: 20px; margin-bottom: 24px; background: rgba(255,255,255,0.02);">
                    <h6 style="color: var(--ds-text-main); font-size: 0.875rem; font-weight: 600; margin: 0 0 12px; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-heading text-muted"></i> Request Headers
                    </h6>
                    <pre style="margin: 0; background: rgba(0,0,0,0.2); border-radius: 8px; padding: 16px; color: var(--ds-text-muted); font-size: 0.8125rem; font-family: monospace; white-space: pre-wrap; word-break: break-all;">{{ is_array($delivery->request_headers) ? json_encode($delivery->request_headers, JSON_PRETTY_PRINT) : $delivery->request_headers }}</pre>
                </div>

                <div class="v2-settings-card" style="padding: 20px; margin-bottom: 24px; background: rgba(255,255,255,0.02);">
                    <h6 style="color: var(--ds-text-main); font-size: 0.875rem; font-weight: 600; margin: 0 0 12px; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-code text-muted"></i> Payload (Request Body)
                    </h6>
                    <pre style="margin: 0; background: rgba(0,0,0,0.2); border-radius: 8px; padding: 16px; color: #a5b4fc; font-size: 0.8125rem; font-family: monospace; white-space: pre-wrap; word-break: break-all;">{{ is_array($delivery->payload) ? json_encode($delivery->payload, JSON_PRETTY_PRINT) : $delivery->payload }}</pre>
                </div>

                <div class="v2-settings-card" style="padding: 20px; background: rgba(255,255,255,0.02);">
                    <h6 style="color: var(--ds-text-main); font-size: 0.875rem; font-weight: 600; margin: 0 0 12px; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-reply text-muted"></i> Resposta do Servidor
                    </h6>
                    <pre style="margin: 0; background: rgba(0,0,0,0.2); border-radius: 8px; padding: 16px; color: {{ $delivery->success ? '#10b981' : '#ef4444' }}; font-size: 0.8125rem; font-family: monospace; white-space: pre-wrap; word-break: break-all;">{{ is_array($delivery->response_body) ? json_encode($delivery->response_body, JSON_PRETTY_PRINT) : $delivery->response_body }}</pre>
                </div>

            </div>
        </div>
    </div>
</div>

@empty
<div class="v2-empty-state" style="padding: 48px 24px; text-align: center; border: 1px dashed rgba(255,255,255,0.1); border-radius: 16px; margin-bottom: 24px;">
    <div style="width: 48px; height: 48px; background: rgba(255,255,255,.05); border-radius: 14px; color: var(--ds-text-muted); display: flex; align-items: center; justify-content: center; font-size: 1.25rem; margin: 0 auto 16px;">
        <i class="fas fa-inbox"></i>
    </div>
    <h3 style="margin: 0 0 8px; font-size: 1.125rem; font-weight: 600; color: var(--ds-text-main);">Nenhuma entrega registrada</h3>
    <p style="margin: 0; color: var(--ds-text-muted); font-size: 0.875rem;">Ainda não enviamos eventos para este endpoint.</p>
</div>
@endforelse

@endsection
