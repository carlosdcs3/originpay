<div class="row">
    <!-- Card: Operações -->
    <div class="col-md-6 mb-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom-0 pt-4 pb-2">
                <h6 class="card-title text-muted text-uppercase mb-0"><i class="fa-solid fa-server me-2"></i> {{ __('Operações Disponíveis') }}</h6>
            </div>
            <div class="card-body">
                <div class="d-flex flex-column gap-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <span><i class="fa-solid fa-qrcode text-secondary w-20px text-center me-2"></i> PIX Cobrança</span>
                        @if($gateway->supports_pix)
                            <span class="badge bg-success"><i class="fa-solid fa-check"></i> Ativo</span>
                        @else
                            <span class="badge bg-light text-muted">Desabilitado</span>
                        @endif
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span><i class="fa-solid fa-money-bill-transfer text-secondary w-20px text-center me-2"></i> PIX Saque</span>
                        @if($gateway->is_withdraw)
                            <span class="badge bg-success"><i class="fa-solid fa-check"></i> Ativo</span>
                        @else
                            <span class="badge bg-light text-muted">Desabilitado</span>
                        @endif
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span><i class="fa-solid fa-barcode text-secondary w-20px text-center me-2"></i> Boleto</span>
                        @if($gateway->supports_boleto)
                            <span class="badge bg-success"><i class="fa-solid fa-check"></i> Ativo</span>
                        @else
                            <span class="badge bg-light text-muted">Desabilitado</span>
                        @endif
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span><i class="fa-regular fa-credit-card text-secondary w-20px text-center me-2"></i> Cartão</span>
                        @if($gateway->supports_card)
                            <span class="badge bg-success"><i class="fa-solid fa-check"></i> Ativo</span>
                        @else
                            <span class="badge bg-light text-muted">Desabilitado</span>
                        @endif
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span><i class="fa-brands fa-bitcoin text-secondary w-20px text-center me-2"></i> Crypto</span>
                        @if($gateway->supports_crypto)
                            <span class="badge bg-success"><i class="fa-solid fa-check"></i> Ativo</span>
                        @else
                            <span class="badge bg-light text-muted">Desabilitado</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card: Health & Webhooks -->
    <div class="col-md-6 mb-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom-0 pt-4 pb-2">
                <h6 class="card-title text-muted text-uppercase mb-0"><i class="fa-solid fa-heart-pulse me-2"></i> {{ __('Integração & Health') }}</h6>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-secondary">Credenciais</span>
                    @if(is_array($gateway->credentials) && count($gateway->credentials) > 0)
                        <span class="badge bg-success-subtle text-success border border-success-subtle">Configuradas</span>
                    @else
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Incompletas</span>
                    @endif
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-secondary">Webhooks</span>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Configurado</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-secondary">Status</span>
                    <span class="badge bg-success"><i class="fa-solid fa-wifi me-1"></i> ONLINE</span>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-secondary">Latência Média</span>
                    <span class="fw-bold">124ms</span>
                </div>
            </div>
        </div>
        
        <!-- Card: Roteamento -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom-0 pt-4 pb-2">
                <h6 class="card-title text-muted text-uppercase mb-0"><i class="fa-solid fa-route me-2"></i> {{ __('Roteamento') }}</h6>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-secondary">Prioridade</span>
                    <span class="badge bg-dark">Nível {{ $gateway->priority ?? 0 }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-secondary">Fallback Gateway</span>
                    <span class="text-muted fst-italic">Nenhum configurado</span>
                </div>
            </div>
        </div>
    </div>
</div>
