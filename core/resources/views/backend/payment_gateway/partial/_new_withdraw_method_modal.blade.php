<div class="modal fade" id="new-withdraw-method-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Adicionar Novo Método de Saque') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.payment.gateway.store-withdraw-method', $gateway->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Nome do Método') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" required placeholder="Ex: Saque PIX, Saque USDT">
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
                            <label class="form-label">{{ __('Saque Mínimo') }}</label>
                            <input type="number" step="0.01" class="form-control" name="min_limit" value="0.00">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Saque Máximo') }}</label>
                            <input type="number" step="0.01" class="form-control" name="max_limit" value="0.00">
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
                    
                    <hr>
                    <h6 class="mb-3">{{ __('Campos Exigidos do Usuário (Dynamic Field Builder)') }}</h6>
                    
                    <div id="fields-builder-container">
                        <!-- Builder Area -->
                    </div>
                    <button type="button" class="btn btn-outline-secondary btn-sm mt-2" id="add-field-btn">
                        <i class="fa fa-plus me-1"></i> {{ __('Adicionar Campo') }}
                    </button>
                    
                    <div class="form-check form-switch mt-4">
                        <input class="form-check-input" type="checkbox" name="status" id="new_w_method_status" value="1" checked>
                        <label class="form-check-label" for="new_w_method_status">{{ __('Ativo') }}</label>
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

<template id="field-builder-template">
    <div class="row mb-3 field-row border rounded p-3 bg-light position-relative mx-1">
        <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 mt-2 me-2 w-auto remove-field"><i class="fa fa-times"></i></button>
        <div class="col-md-3 mb-2">
            <label class="form-label">{{ __('Label (Ex: Chave PIX)') }}</label>
            <input type="text" class="form-control form-control-sm" name="field_labels[]" required>
        </div>
        <div class="col-md-3 mb-2">
            <label class="form-label">{{ __('Key (Ex: pix_key)') }}</label>
            <input type="text" class="form-control form-control-sm" name="field_keys[]" required>
        </div>
        <div class="col-md-2 mb-2">
            <label class="form-label">{{ __('Tipo') }}</label>
            <select name="field_types[]" class="form-select form-select-sm">
                <option value="text">Text</option>
                <option value="number">Number</option>
                <option value="cpf">CPF</option>
                <option value="cnpj">CNPJ</option>
                <option value="pix_key">Pix Key</option>
                <option value="email">Email</option>
                <option value="phone">Phone</option>
                <option value="select">Select</option>
            </select>
        </div>
        <div class="col-md-2 mb-2">
            <label class="form-label">{{ __('Placeholder') }}</label>
            <input type="text" class="form-control form-control-sm" name="field_placeholders[]">
        </div>
        <div class="col-md-2 mb-2">
            <label class="form-label d-block">{{ __('Obrigatório?') }}</label>
            <div class="form-check form-switch mt-2">
                <!-- Using a hidden input for unchecked state since checkboxes don't POST if unchecked -->
                <input type="hidden" name="field_required[]" value="0" class="field-required-hidden">
                <input class="form-check-input field-required-checkbox" type="checkbox" checked onchange="this.previousElementSibling.value = this.checked ? '1' : '0'">
            </div>
        </div>
    </div>
</template>
