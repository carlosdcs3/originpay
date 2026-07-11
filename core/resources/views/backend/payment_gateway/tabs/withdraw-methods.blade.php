<div class="card border-0 shadow-sm">
    <div class="card-header bg-white pt-4 pb-3 border-bottom-0">
        <h5 class="card-title mb-0"><i class="fa-solid fa-money-bill-transfer me-2 text-muted"></i> {{ __('Configuração de Saque PIX') }}</h5>
    </div>
    <div class="card-body">
        @if($pixWithdraw)
            <form action="{{ route('admin.payment.gateway.update-pix-withdraw', $gateway->id) }}" method="POST" class="ajax-form">
                @csrf
                
                <div class="bg-light rounded p-3 mb-4 border d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1">{{ __('Status da Operação') }}</h6>
                        <small class="text-muted">{{ __('Habilita ou desabilita as saídas via PIX neste gateway.') }}</small>
                    </div>
                    <div class="form-check form-switch fs-4 mb-0">
                        <input class="form-check-input" type="checkbox" name="status" value="1" @checked($pixWithdraw->status)>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-4">
                        <label class="form-label text-muted small fw-bold">{{ __('Aprovação Manual Acima de (R$)') }}</label>
                        <input type="number" class="form-control" name="manual_approval_threshold" value="5000.00">
                        <small class="text-muted">{{ __('Saques acima deste valor exigirão revisão manual.') }}</small>
                    </div>
                    <div class="col-md-6 mb-4">
                        <label class="form-label text-muted small fw-bold">{{ __('Chave do Webhook de Saque') }}</label>
                        <input type="text" class="form-control bg-light" name="withdraw_webhook_key" value="wd_{{ Str::random(16) }}" readonly>
                    </div>
                </div>

                <div class="mt-4 border-top pt-4">
                    <h6 class="mb-3 text-muted text-uppercase small fw-bold">{{ __('Campos Exigidos para o Saque (Dynamic Field Builder)') }}</h6>
                    <div id="fields-builder-container">
                        @if($pixWithdraw->fields && is_array($pixWithdraw->fields))
                            @foreach($pixWithdraw->fields as $field)
                                <div class="row mb-3 align-items-end field-row">
                                    <div class="col-md-3">
                                        <label class="form-label text-muted small fw-bold">{{ __('Label') }}</label>
                                        <input type="text" class="form-control" name="field_labels[]" value="{{ $field['label'] }}" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label text-muted small fw-bold">{{ __('Chave (Key)') }}</label>
                                        <input type="text" class="form-control" name="field_keys[]" value="{{ $field['key'] }}" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label text-muted small fw-bold">{{ __('Tipo') }}</label>
                                        <select name="field_types[]" class="form-select">
                                            <option value="text" @selected(($field['type'] ?? '') == 'text')>Texto Livre</option>
                                            <option value="number" @selected(($field['type'] ?? '') == 'number')>Número</option>
                                            <option value="cpf" @selected(($field['type'] ?? '') == 'cpf')>CPF</option>
                                            <option value="cnpj" @selected(($field['type'] ?? '') == 'cnpj')>CNPJ</option>
                                            <option value="pix_key" @selected(($field['type'] ?? '') == 'pix_key')>Chave PIX</option>
                                            <option value="email" @selected(($field['type'] ?? '') == 'email')>E-mail</option>
                                            <option value="phone" @selected(($field['type'] ?? '') == 'phone')>Telefone</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-check form-switch fs-5 mb-2">
                                            <input class="form-check-input" type="checkbox" name="field_required[{{ $loop->index }}]" value="1" @checked($field['required'] ?? false)>
                                            <label class="form-check-label fs-6 mt-1 ms-1">{{ __('Obrigatório') }}</label>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-outline-danger w-100 remove-field"><i class="fa fa-times me-1"></i> Remover</button>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                    <button type="button" class="btn btn-outline-info btn-sm mt-2" id="add-field-btn">
                        <i class="fa fa-plus me-1"></i> {{ __('Adicionar Novo Campo') }}
                    </button>
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
                <div class="mb-3"><i class="fa-solid fa-money-bill-transfer fa-3x text-muted"></i></div>
                <h5>{{ __('PIX Saque não configurado') }}</h5>
                <p class="text-muted">{{ __('Este gateway ainda não possui o método de saque PIX criado.') }}</p>
                <button class="btn btn-primary mt-2" onclick="alert('Modal de criação abrirá.')">
                    <i class="fa fa-plus me-1"></i> {{ __('Criar Método Agora') }}
                </button>
            </div>
        @endif
    </div>
</div>

<template id="field-builder-template">
    <div class="row mb-3 align-items-end field-row">
        <div class="col-md-3">
            <label class="form-label text-muted small fw-bold">{{ __('Label') }}</label>
            <input type="text" class="form-control" name="field_labels[]" placeholder="Ex: Chave PIX" required>
        </div>
        <div class="col-md-3">
            <label class="form-label text-muted small fw-bold">{{ __('Chave (Key)') }}</label>
            <input type="text" class="form-control" name="field_keys[]" placeholder="Ex: pix_key" required>
        </div>
        <div class="col-md-2">
            <label class="form-label text-muted small fw-bold">{{ __('Tipo') }}</label>
            <select name="field_types[]" class="form-select">
                <option value="text">Texto Livre</option>
                <option value="number">Número</option>
                <option value="cpf">CPF</option>
                <option value="cnpj">CNPJ</option>
                <option value="pix_key" selected>Chave PIX</option>
                <option value="email">E-mail</option>
                <option value="phone">Telefone</option>
            </select>
        </div>
        <div class="col-md-2">
            <div class="form-check form-switch fs-5 mb-2">
                <input class="form-check-input" type="checkbox" name="field_required[999]" value="1" checked>
                <label class="form-check-label fs-6 mt-1 ms-1">{{ __('Obrigatório') }}</label>
            </div>
        </div>
        <div class="col-md-2">
            <button type="button" class="btn btn-outline-danger w-100 remove-field"><i class="fa fa-times me-1"></i> Remover</button>
        </div>
    </div>
</template>
