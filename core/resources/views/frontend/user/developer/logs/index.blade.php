@extends('frontend.user.developer.index')
@section('title', __('Logs da API'))

@section('user_developer_content')

<div class="v2-page-header" style="margin-bottom: 24px;">
    <h2 class="v2-page-title" style="font-size: 1.5rem; font-weight: 700; margin: 0 0 4px; color: var(--ds-text-main);">Logs da API</h2>
    <p class="v2-page-subtitle" style="font-size: 0.875rem; color: var(--ds-text-muted); margin: 0;">Visualize em tempo real todas as requisições feitas utilizando suas chaves de API.</p>
</div>

<!-- Toolbar -->
<div class="v2-settings-card mb-4" style="padding: 12px 16px; border-radius: 12px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
    <form action="{{ route('user.developer.logs.index') }}" method="GET" style="display: flex; gap: 12px; flex: 1; align-items: center; margin: 0;">
        
        <div style="flex: 1; min-width: 120px;">
            <select name="method" class="v2-input" style="padding: 0 12px; height: 36px; border-radius: 8px; border: 1px solid var(--ds-border-medium); background: rgba(255,255,255,0.03); color: white; font-size: 0.875rem;">
                <option value="" style="background: var(--ds-bg-card);">Método</option>
                <option value="GET" {{ request('method') == 'GET' ? 'selected' : '' }} style="background: var(--ds-bg-card);">GET</option>
                <option value="POST" {{ request('method') == 'POST' ? 'selected' : '' }} style="background: var(--ds-bg-card);">POST</option>
                <option value="PUT" {{ request('method') == 'PUT' ? 'selected' : '' }} style="background: var(--ds-bg-card);">PUT</option>
                <option value="DELETE" {{ request('method') == 'DELETE' ? 'selected' : '' }} style="background: var(--ds-bg-card);">DELETE</option>
            </select>
        </div>
        
        <div style="flex: 1; min-width: 120px;">
            <select name="status" class="v2-input" style="padding: 0 12px; height: 36px; border-radius: 8px; border: 1px solid var(--ds-border-medium); background: rgba(255,255,255,0.03); color: white; font-size: 0.875rem;">
                <option value="" style="background: var(--ds-bg-card);">Status</option>
                <option value="success" {{ request('status') == 'success' ? 'selected' : '' }} style="background: var(--ds-bg-card);">Sucesso (2xx)</option>
                <option value="error" {{ request('status') == 'error' ? 'selected' : '' }} style="background: var(--ds-bg-card);">Erro (4xx, 5xx)</option>
            </select>
        </div>
        
        <div style="flex: 1; min-width: 140px;">
            <input type="date" name="date" class="v2-input" value="{{ request('date') }}" style="padding: 0 12px; height: 36px; border-radius: 8px; border: 1px solid var(--ds-border-medium); background: rgba(255,255,255,0.03); color: white; color-scheme: dark; font-size: 0.875rem;" placeholder="Data">
        </div>
        
        <div style="display: flex; gap: 8px;">
            <button type="submit" class="v2-btn-secondary" style="height: 36px; padding: 0 16px; font-size: 0.875rem;">Filtrar</button>
            @if(request()->anyFilled(['method', 'status', 'date']))
                <a href="{{ route('user.developer.logs.index') }}" class="v2-btn-tertiary" style="height: 36px; padding: 0 16px; display: flex; align-items: center; text-decoration: none; font-size: 0.875rem;">Limpar</a>
            @endif
        </div>
    </form>
</div>

@if(count($logs) > 0)
<div class="v2-settings-card" style="padding: 0; overflow: hidden; border-radius: 12px; border: 1px solid var(--ds-border-light);">
    <div class="table-responsive" style="margin: 0;">
        <table class="table" style="margin: 0; color: var(--ds-text-main); font-size: 0.875rem;">
            <thead style="background: rgba(255,255,255,0.02); border-bottom: 1px solid var(--ds-border-light);">
                <tr>
                    <th style="padding: 12px 24px; font-weight: 600; color: var(--ds-text-muted); text-transform: uppercase; font-size: 0.75rem; border: none;">Status</th>
                    <th style="padding: 12px 24px; font-weight: 600; color: var(--ds-text-muted); text-transform: uppercase; font-size: 0.75rem; border: none;">Método</th>
                    <th style="padding: 12px 24px; font-weight: 600; color: var(--ds-text-muted); text-transform: uppercase; font-size: 0.75rem; border: none;">Endpoint</th>
                    <th style="padding: 12px 24px; font-weight: 600; color: var(--ds-text-muted); text-transform: uppercase; font-size: 0.75rem; border: none;">Data / Hora</th>
                    <th style="padding: 12px 24px; font-weight: 600; color: var(--ds-text-muted); text-transform: uppercase; font-size: 0.75rem; border: none;">Latência</th>
                    <th style="padding: 12px 24px; font-weight: 600; color: var(--ds-text-muted); text-transform: uppercase; font-size: 0.75rem; border: none;"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($logs as $index => $log)
                <tr style="cursor: pointer; transition: 200ms; background: {{ $index % 2 === 0 ? 'transparent' : 'rgba(255,255,255,0.01)' }}; border-bottom: 1px solid rgba(255,255,255,0.03);" onclick="openLogDetails({{ $log->id }})" onmouseover="this.style.background='rgba(255,255,255,0.03)'" onmouseout="this.style.background='{{ $index % 2 === 0 ? 'transparent' : 'rgba(255,255,255,0.01)' }}'">
                    <td style="padding: 16px 24px; vertical-align: middle; border: none;">
                        @if($log->status_code >= 200 && $log->status_code < 300)
                            <span class="v2-badge v2-badge-success" style="font-family: monospace;">{{ $log->status_code }} OK</span>
                        @else
                            <span class="v2-badge v2-badge-error" style="font-family: monospace;">{{ $log->status_code }} ERR</span>
                        @endif
                    </td>
                    <td style="padding: 16px 24px; vertical-align: middle; border: none;">
                        <span class="text-{{ $log->method == 'GET' ? 'primary' : ($log->method == 'POST' ? 'success' : ($log->method == 'DELETE' ? 'danger' : 'warning')) }}" style="font-family: monospace; font-weight: 700;">
                            {{ $log->method }}
                        </span>
                    </td>
                    <td style="padding: 16px 24px; vertical-align: middle; border: none; font-family: monospace; word-break: break-all;">
                        {{ Str::limit($log->endpoint, 45) }}
                    </td>
                    <td style="padding: 16px 24px; vertical-align: middle; border: none;">
                        {{ $log->created_at->format('d/m/Y H:i:s') }}
                    </td>
                    <td style="padding: 16px 24px; vertical-align: middle; border: none; font-family: monospace; color: var(--ds-text-muted);">
                        {{ $log->response_time_ms }} ms
                    </td>
                    <td style="padding: 16px 24px; vertical-align: middle; border: none; text-align: right;">
                        <button class="v2-btn-tertiary" type="button" onclick="openLogDetails({{ $log->id }}); event.stopPropagation();" style="padding: 4px 12px; height: 28px; font-size: 0.75rem;">
                            Detalhes
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@if($logs->hasPages())
<div style="margin-top: 24px;">
    {{ $logs->links() }}
</div>
@endif

@else
<div class="v2-empty-state" style="padding: 32px 24px; text-align: center; border: 1px dashed rgba(255,255,255,0.1); border-radius: 12px; margin-bottom: 24px;">
    <div style="width: 40px; height: 40px; background: rgba(255,255,255,.05); border-radius: 12px; color: var(--ds-text-muted); display: flex; align-items: center; justify-content: center; font-size: 1.125rem; margin: 0 auto 12px;">
        <i class="fas fa-search"></i>
    </div>
    <h3 style="margin: 0 0 8px; font-size: 1rem; font-weight: 600; color: var(--ds-text-main);">Nenhum log encontrado</h3>
    <p style="margin: 0; color: var(--ds-text-muted); font-size: 0.875rem;">Não há registros para os filtros selecionados.</p>
</div>
@endif

{{-- Log Details Modal --}}
<div class="modal fade" id="logDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px; border: 1px solid var(--ds-border-light); background: var(--ds-bg-card);">
            <div class="modal-header" style="border-bottom: 1px solid var(--ds-border-light); padding: 24px;">
                <h5 class="modal-title fw-bold" style="color: var(--ds-text-main); font-size: 1.125rem;">
                    Detalhes do Log
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="padding: 24px;" id="logDetailsContent">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function openLogDetails(logId) {
        var myModal = new bootstrap.Modal(document.getElementById('logDetailsModal'));
        myModal.show();
        
        document.getElementById('logDetailsContent').innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></div>';
        
        fetch(`/user/developer/logs/${logId}`)
            .then(response => response.json())
            .then(data => {
                let isSuccess = (data.status_code >= 200 && data.status_code < 300);
                let badgeClass = isSuccess ? 'v2-badge-success' : 'v2-badge-error';
                
                let html = `
                    <div class="v2-settings-card" style="padding: 20px; margin-bottom: 24px; display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 16px; background: rgba(255,255,255,0.02);">
                        <div>
                            <div style="font-size: 0.75rem; color: var(--ds-text-muted); text-transform: uppercase; margin-bottom: 4px; font-weight: 600;">Status</div>
                            <span class="v2-badge ${badgeClass}" style="font-family: monospace;">${data.status_code}</span>
                        </div>
                        <div style="grid-column: span 2;">
                            <div style="font-size: 0.75rem; color: var(--ds-text-muted); text-transform: uppercase; margin-bottom: 4px; font-weight: 600;">Endpoint</div>
                            <div style="font-family: monospace; font-weight: 700; color: var(--ds-text-main); word-break: break-all;">
                                <span class="text-primary" style="margin-right: 8px;">${data.method}</span> ${data.endpoint}
                            </div>
                        </div>
                        <div>
                            <div style="font-size: 0.75rem; color: var(--ds-text-muted); text-transform: uppercase; margin-bottom: 4px; font-weight: 600;">Latência</div>
                            <div style="font-weight: 600; color: var(--ds-text-main); font-family: monospace;">${data.response_time_ms} ms</div>
                        </div>
                        <div>
                            <div style="font-size: 0.75rem; color: var(--ds-text-muted); text-transform: uppercase; margin-bottom: 4px; font-weight: 600;">Ambiente</div>
                            <div style="font-weight: 600; color: var(--ds-text-main); text-transform: capitalize;">${data.environment || 'N/A'}</div>
                        </div>
                        <div>
                            <div style="font-size: 0.75rem; color: var(--ds-text-muted); text-transform: uppercase; margin-bottom: 4px; font-weight: 600;">IP de Origem</div>
                            <div style="font-weight: 600; color: var(--ds-text-main); font-family: monospace;">${data.ip_address || 'N/A'}</div>
                        </div>
                        <div style="grid-column: span 2;">
                            <div style="font-size: 0.75rem; color: var(--ds-text-muted); text-transform: uppercase; margin-bottom: 4px; font-weight: 600;">Correlation ID</div>
                            <div style="font-family: monospace; color: var(--ds-text-main); font-size: 0.875rem;">${data.request_id || 'N/A'}</div>
                        </div>
                    </div>
                    
                    <div class="v2-settings-card" style="padding: 20px; margin-bottom: 24px; background: rgba(255,255,255,0.02);">
                        <h6 style="color: var(--ds-text-main); font-size: 0.875rem; font-weight: 600; margin: 0 0 12px; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-heading text-muted"></i> Request Headers
                        </h6>
                        <pre style="margin: 0; background: rgba(0,0,0,0.2); border-radius: 8px; padding: 16px; color: var(--ds-text-muted); font-size: 0.8125rem; font-family: monospace; white-space: pre-wrap; word-break: break-all; max-height: 200px; overflow-y: auto;">${data.request_headers ? JSON.stringify(data.request_headers, null, 2) : 'N/A'}</pre>
                    </div>

                    <div class="v2-settings-card" style="padding: 20px; margin-bottom: 24px; background: rgba(255,255,255,0.02);">
                        <h6 style="color: var(--ds-text-main); font-size: 0.875rem; font-weight: 600; margin: 0 0 12px; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-code text-muted"></i> Request Payload (Body)
                        </h6>
                        <pre style="margin: 0; background: rgba(0,0,0,0.2); border-radius: 8px; padding: 16px; color: #a5b4fc; font-size: 0.8125rem; font-family: monospace; white-space: pre-wrap; word-break: break-all; max-height: 200px; overflow-y: auto;">${data.request_payload ? JSON.stringify(data.request_payload, null, 2) : 'Vazio'}</pre>
                    </div>

                    <div class="v2-settings-card" style="padding: 20px; background: rgba(255,255,255,0.02);">
                        <h6 style="color: var(--ds-text-main); font-size: 0.875rem; font-weight: 600; margin: 0 0 12px; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-reply text-muted"></i> Response Payload
                        </h6>
                        <pre style="margin: 0; background: rgba(0,0,0,0.2); border-radius: 8px; padding: 16px; color: ${isSuccess ? '#10b981' : '#ef4444'}; font-size: 0.8125rem; font-family: monospace; white-space: pre-wrap; word-break: break-all; max-height: 300px; overflow-y: auto;">${data.response_payload ? JSON.stringify(data.response_payload, null, 2) : 'Vazio'}</pre>
                    </div>
                `;
                
                document.getElementById('logDetailsContent').innerHTML = html;
            })
            .catch(err => {
                document.getElementById('logDetailsContent').innerHTML = '<div class="alert alert-danger" style="margin: 0;">Erro ao carregar detalhes do log.</div>';
            });
    }
</script>

@endsection
