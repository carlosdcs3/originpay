<div class="card border-0 shadow-sm">
    <div class="card-header bg-white pt-4 pb-3 border-bottom-0">
        <h5 class="card-title mb-0"><i class="fa-solid fa-route me-2 text-muted"></i> {{ __('Roteamento de Operações') }}</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.payment.gateway.update-routing', $gateway->id) }}" method="POST" class="ajax-form">
            @csrf
            
            <p class="text-muted mb-4">{{ __('Configure como este Gateway será priorizado em cada tipo de operação. Gateways com maior número (peso) na operação específica recebem a transação primeiro. Se o gateway não puder processar (ex: sem saldo ou fora do ar), o sistema passa para o próximo (Fallback).') }}</p>

            @php
                // Em um cenário real, as operations vêm do Provider (ex: $gateway->operations)
                $operations = $gateway->operations ?? [];
                // Se a tabela payment_method_routes estiver sendo preenchida, deve vir de um model relacionado.
                // Simulando para a view
                $routes = $gateway->routes ?? collect();
            @endphp

            @if(empty($operations))
                <div class="alert alert-warning">
                    {{ __('Este gateway não declarou nenhuma operação (operation) em seu Provider. Verifique a integração.') }}
                </div>
            @else
                <div class="row">
                    @foreach($operations as $operation)
                        @php
                            $route = $routes->where('operation', $operation)->first();
                            $priority = $route ? $route->priority : 0;
                            $isActive = $route ? $route->is_active : true;
                        @endphp
                        <div class="col-md-6 mb-3">
                            <div class="card border">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="mb-0 fw-bold">{{ str_replace('_', ' ', $operation) }}</h6>
                                        <div class="form-check form-switch fs-5 mb-0">
                                            <input class="form-check-input" type="checkbox" name="routing[{{ $operation }}][is_active]" value="1" @checked($isActive)>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="form-label small text-muted mb-1">{{ __('Prioridade / Peso') }}</label>
                                        <input type="number" name="routing[{{ $operation }}][priority]" class="form-control form-control-sm" value="{{ $priority }}" min="0">
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="mt-4 border-top pt-4 text-end">
                <button type="submit" class="btn btn-primary px-4 btn-save">
                    <span class="normal-state"><i class="fa-solid fa-save me-2"></i> {{ __('Salvar Roteamento') }}</span>
                    <span class="loading-state d-none"><span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Salvando...</span>
                </button>
            </div>
        </form>
    </div>
</div>

