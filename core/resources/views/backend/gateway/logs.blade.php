@extends('backend.layouts.app')
@section('title', 'Gateway Logs')

@section('content')
@include('backend.operations.partials._logs_tabs')

<x-ds.page 
    title="Gateway Logs" 
    :breadcrumb="[
        ['title' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['title' => 'Gateways'],
        ['title' => 'Logs']
    ]">
    
    <x-ds.table 
        title="Logs de Integração" 
        :count="$logs->total() ?? $logs->count()"
        :isEmpty="$logs->isEmpty()"
        :action="route('admin.gateway.logs')">
        
        <x-slot name="filters">
            <select name="status" class="ds-filter-select form-select" style="width:auto;">
                <option value="">Todos os Status</option>
                <option value="success" @if(request()->status == 'success') selected @endif>Sucesso</option>
                <option value="error" @if(request()->status == 'error') selected @endif>Erro</option>
            </select>
            <div class="position-relative">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--ds-text-muted);pointer-events:none;"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                <input type="text" name="gateway" class="ds-filter-input form-control" placeholder="Código Gateway" value="{{ request()->gateway }}" style="padding-left:2rem!important;">
            </div>
            <button type="submit" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                Filtrar
            </button>
            @if(request('status') || request('gateway'))
                <a href="{{ route('admin.gateway.logs') }}" class="btn btn-ghost" style="color:var(--ds-text-muted);">Limpar</a>
            @endif
        </x-slot>
        
        <x-slot name="thead">
            <th>Data</th>
            <th>Gateway</th>
            <th>Ação</th>
            <th>Cobrança UUID</th>
            <th>Status HTTP</th>
            <th>Status</th>
            <th>Duração (ms)</th>
            <th class="ds-col-action">Ações</th>
        </x-slot>

        @forelse($logs as $log)
        <tr>
            <td>
                <div class="ds-col-date">{{ \Carbon\Carbon::parse($log->created_at)->format('d/m/y H:i:s') }}</div>
            </td>
            <td>
                <span class="ds-col-name">{{ $log->gateway_code }}</span>
            </td>
            <td>
                <span style="font-size:var(--ds-text-sm);">{{ $log->action }}</span>
            </td>
            <td>
                @if($log->charge_uuid)
                    <div class="ds-col-id">{{ substr($log->charge_uuid, 0, 8) }}...</div>
                @else
                    <span style="color:var(--ds-text-muted);font-size:var(--ds-text-xs);">N/A</span>
                @endif
            </td>
            <td>
                <div style="font-family:var(--ds-font-mono);font-size:var(--ds-text-sm);">{{ $log->http_status }}</div>
            </td>
            <td>
                @if($log->status == 'success')
                    <x-ds.badge status="success" label="Sucesso" />
                @else
                    <x-ds.badge status="failed" label="Erro" />
                @endif
            </td>
            <td>
                <div style="font-family:var(--ds-font-mono);font-size:var(--ds-text-sm);">{{ $log->duration_ms }}ms</div>
            </td>
            <td class="ds-col-action">
                <button type="button" class="btn btn-secondary btn-sm view-payload" 
                        data-payload="{{ $log->payload }}" 
                        data-response="{{ $log->response }}" 
                        style="font-size:var(--ds-text-xs);padding:.25rem .5rem;"
                        data-coreui-toggle="modal"
                        data-coreui-target="#payloadModal">
                    Detalhes
                </button>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="8">
                <x-ds.empty-state 
                    title="Nenhum log encontrado" 
                    desc="Os logs de comunicação com os gateways aparecerão aqui." 
                    icon='<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>'
                />
            </td>
        </tr>
        @endforelse

        <x-slot name="pagination">
            <x-ds.pagination :paginator="$logs" />
        </x-slot>
    </x-ds.table>
</x-ds.page>

<!-- Modal Detalhes -->
<div class="modal modal-blur fade" id="payloadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalhes do Log</h5>
                <button type="button" class="btn-close" data-coreui-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6 style="font-size:var(--ds-text-sm);font-weight:600;margin-bottom:.5rem;">Payload Enviado:</h6>
                <div class="bg-dark text-white p-3 rounded mb-4" style="font-family:var(--ds-font-mono);font-size:12px;overflow-x:auto;">
                    <pre style="margin:0;"><code id="modal-payload"></code></pre>
                </div>
                
                <h6 style="font-size:var(--ds-text-sm);font-weight:600;margin-bottom:.5rem;">Resposta:</h6>
                <div class="bg-dark text-white p-3 rounded" style="font-family:var(--ds-font-mono);font-size:12px;overflow-x:auto;">
                    <pre style="margin:0;"><code id="modal-response"></code></pre>
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid var(--ds-border);">
                <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('script')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const buttons = document.querySelectorAll('.view-payload');
        buttons.forEach(btn => {
            btn.addEventListener('click', function() {
                const payloadStr = this.getAttribute('data-payload');
                const responseStr = this.getAttribute('data-response');
                
                let pElem = document.getElementById('modal-payload');
                let rElem = document.getElementById('modal-response');
                
                try {
                    pElem.textContent = JSON.stringify(JSON.parse(payloadStr), null, 2);
                } catch(e) {
                    pElem.textContent = payloadStr || 'Vazio';
                }

                try {
                    rElem.textContent = JSON.stringify(JSON.parse(responseStr), null, 2);
                } catch(e) {
                    rElem.textContent = responseStr || 'Vazio';
                }
            });
        });
    });
</script>
@endpush
