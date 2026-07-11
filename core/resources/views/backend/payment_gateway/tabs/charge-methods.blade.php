<div class="card border-0 shadow-sm">
    <div class="card-header bg-white pt-4 pb-3 border-bottom-0">
        <h5 class="card-title mb-0"><i class="fa-solid fa-qrcode me-2 text-muted"></i> {{ __('Configuração de Cobrança PIX') }}</h5>
    </div>
    <div class="card-body">
        @if($pixCharge)
            <form action="{{ route('admin.payment.gateway.update-pix-charge', $gateway->id) }}" method="POST" class="ajax-form">
                @csrf
                
                <div class="bg-light rounded p-3 mb-4 border d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1">{{ __('Status da Operação') }}</h6>
                        <small class="text-muted">{{ __('Habilita ou desabilita o recebimento via PIX neste gateway.') }}</small>
                    </div>
                    <div class="form-check form-switch fs-4 mb-0">
                        <input class="form-check-input" type="checkbox" name="status" value="1" @checked($pixCharge->status)>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-4">
                        <label class="form-label text-muted small fw-bold">{{ __('Expiração do QR Code (minutos)') }}</label>
                        <input type="number" class="form-control" name="qr_expiration" value="30" placeholder="Ex: 30">
                        <small class="text-muted">{{ __('Tempo em que o QR Code gerado será válido.') }}</small>
                    </div>
                    <div class="col-md-6 mb-4">
                        <label class="form-label text-muted small fw-bold">{{ __('Descrição Automática') }}</label>
                        <input type="text" class="form-control" name="auto_description" value="{{ __('Pedido #{id}') }}">
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="dynamic_qr" id="dynamic_qr" value="1" checked>
                            <label class="form-check-label" for="dynamic_qr">{{ __('Usar QR Code Dinâmico (Recomendado)') }}</label>
                        </div>
                    </div>
                </div>

                <div class="mt-4 border-top pt-4 text-end">
                    <button type="submit" class="btn btn-primary px-4 btn-save">
                        <span class="normal-state"><i class="fa-solid fa-save me-2"></i> {{ __('Salvar Configurações') }}</span>
                        <span class="loading-state d-none"><span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Salvando...</span>
                    </button>
                </div>
            </form>
        @else
            <div class="text-center py-5">
                <div class="mb-3"><i class="fa-solid fa-qrcode fa-3x text-muted"></i></div>
                <h5>{{ __('PIX Cobrança não configurado') }}</h5>
                <p class="text-muted">{{ __('Este gateway ainda não possui o método PIX criado.') }}</p>
                <button class="btn btn-primary mt-2" onclick="alert('Modal de criação abrirá.')">
                    <i class="fa fa-plus me-1"></i> {{ __('Criar Método Agora') }}
                </button>
            </div>
        @endif
    </div>
</div>
