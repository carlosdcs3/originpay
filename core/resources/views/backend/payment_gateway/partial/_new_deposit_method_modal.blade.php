<div class="modal fade" id="new-deposit-method-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Adicionar Novo Método de Cobrança') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.payment.gateway.store-deposit-method', $gateway->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Nome do Método') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" required placeholder="Ex: PIX, Boleto">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Moeda') }} <span class="text-danger">*</span></label>
                            <select class="form-select" name="currency" required>
                                @foreach($gateway->currencies ?? [] as $curr)
                                    <option value="{{ $curr }}">{{ $curr }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Depósito Mínimo') }}</label>
                            <input type="number" step="0.01" class="form-control" name="min_deposit" value="0.00">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Depósito Máximo') }}</label>
                            <input type="number" step="0.01" class="form-control" name="max_deposit" value="0.00">
                        </div>
                    </div>

                    <h6 class="mb-3">{{ __('Taxas') }}</h6>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-primary">{{ __('Taxa Usuário Padrão') }}</label>
                            <div class="input-group">
                                <input type="number" step="0.01" class="form-control" name="charge" value="0">
                                <select name="charge_type" class="form-select">
                                    <option value="fixed">{{ __('Fixa') }}</option>
                                    <option value="percent">{{ __('Percentual') }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-success">{{ __('Taxa Lojista') }}</label>
                            <div class="input-group">
                                <input type="number" step="0.01" class="form-control" name="merchant_charge" value="0">
                                <select name="merchant_charge_type" class="form-select">
                                    <option value="fixed">{{ __('Fixa') }}</option>
                                    <option value="percent">{{ __('Percentual') }}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-check form-switch mt-3">
                        <input class="form-check-input" type="checkbox" name="status" id="new_d_method_status" value="1" checked>
                        <label class="form-check-label" for="new_d_method_status">{{ __('Ativo') }}</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancelar') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Criar Método') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
