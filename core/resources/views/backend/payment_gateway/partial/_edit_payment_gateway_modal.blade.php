<div class="modal fade" id="edit-gateway-modal-{{ $gateway->id ?? '0' }}" tabindex="-1" aria-labelledby="editGatewayModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editGatewayModalLabel">{{ __('Editar Gateway: ') }} {{ $gateway->name ?? '' }}</h5>
                <button type="button" class="btn-close" data-coreui-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ isset($gateway) ? route('admin.payment.gateway.update', $gateway->id) : '#' }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Nome do Gateway') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" value="{{ $gateway->name ?? '' }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Código Interno') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control bg-light" name="code" value="{{ $gateway->code ?? '' }}" readonly>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-4 mb-3">
                            <div class="form-check form-switch fs-5">
                                <input class="form-check-input" type="checkbox" name="status" id="edit_status" value="1" @checked($gateway->status ?? false)>
                                <label class="form-check-label fs-6 mt-1 ms-2" for="edit_status">{{ __('Ativo') }}</label>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="form-check form-switch fs-5">
                                <input class="form-check-input" type="checkbox" name="is_sandbox" id="edit_sandbox" value="1" @checked($gateway->is_sandbox ?? false)>
                                <label class="form-check-label fs-6 mt-1 ms-2 text-warning" for="edit_sandbox">{{ __('Modo Sandbox') }}</label>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    <h6 class="mb-3">{{ __('Operações Suportadas') }}</h6>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="supports_pix" id="edit_sp_pix" value="1" @checked($gateway->supports_pix ?? false)>
                                <label class="form-check-label" for="edit_sp_pix">{{ __('PIX') }}</label>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="supports_boleto" id="edit_sp_boleto" value="1" @checked($gateway->supports_boleto ?? false)>
                                <label class="form-check-label" for="edit_sp_boleto">{{ __('Boleto') }}</label>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="supports_card" id="edit_sp_card" value="1" @checked($gateway->supports_card ?? false)>
                                <label class="form-check-label" for="edit_sp_card">{{ __('Cartão') }}</label>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="supports_crypto" id="edit_sp_crypto" value="1" @checked($gateway->supports_crypto ?? false)>
                                <label class="form-check-label" for="edit_sp_crypto">{{ __('Crypto') }}</label>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_withdraw" id="edit_sp_withdraw" value="1" @checked($gateway->is_withdraw ?? false)>
                                <label class="form-check-label" for="edit_sp_withdraw">{{ __('Saque (Cash-out)') }}</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">{{ __('Cancelar') }}</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save me-2"></i>{{ __('Salvar Alterações') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
