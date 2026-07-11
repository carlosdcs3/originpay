<div class="card border-0 shadow-sm">
    <div class="card-header bg-white pt-4 pb-3 border-bottom-0">
        <h5 class="card-title mb-0"><i class="fa-solid fa-sliders me-2 text-muted"></i> {{ __('Taxas, Limites e Condições') }}</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.payment.gateway.update-taxes', $gateway->id) }}" method="POST" class="ajax-form">
            @csrf
            
            <h6 class="mb-3 text-muted text-uppercase small fw-bold">{{ __('Regras de Cash-in (Depósito)') }}</h6>
            <div class="row bg-light rounded border p-3 mx-0 mb-4">
                <div class="col-md-4 mb-3">
                    <label class="form-label text-muted small">{{ __('Depósito Mínimo (R$)') }}</label>
                    <input type="number" step="0.01" class="form-control" name="deposit_min" value="{{ $pixCharge->min_deposit ?? '10.00' }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label text-muted small">{{ __('Depósito Máximo (R$)') }}</label>
                    <input type="number" step="0.01" class="form-control" name="deposit_max" value="{{ $pixCharge->max_deposit ?? '10000.00' }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label text-muted small">{{ __('Taxa Cobrada') }}</label>
                    <div class="input-group">
                        <input type="number" step="0.01" class="form-control" name="deposit_charge" value="{{ $pixCharge->charge ?? '0.00' }}">
                        <select name="deposit_charge_type" class="form-select">
                            <option value="percent" {{ ($pixCharge->charge_type ?? '') == 'percent' ? 'selected' : '' }}>%</option>
                            <option value="fixed" {{ ($pixCharge->charge_type ?? '') == 'fixed' ? 'selected' : '' }}>Fixa</option>
                        </select>
                    </div>
                </div>
            </div>

            <h6 class="mb-3 text-muted text-uppercase small fw-bold">{{ __('Regras de Cash-out (Saque)') }}</h6>
            <div class="row bg-light rounded border p-3 mx-0 mb-4">
                <div class="col-md-4 mb-3">
                    <label class="form-label text-muted small">{{ __('Saque Mínimo (R$)') }}</label>
                    <input type="number" step="0.01" class="form-control" name="withdraw_min" value="{{ $pixWithdraw->min_limit ?? '20.00' }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label text-muted small">{{ __('Saque Máximo (R$)') }}</label>
                    <input type="number" step="0.01" class="form-control" name="withdraw_max" value="{{ $pixWithdraw->max_limit ?? '50000.00' }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label text-muted small">{{ __('Taxa Cobrada') }}</label>
                    <div class="input-group">
                        <input type="number" step="0.01" class="form-control" name="withdraw_charge" value="{{ $pixWithdraw->charge ?? '0.00' }}">
                        <select name="withdraw_charge_type" class="form-select">
                            <option value="percent" {{ ($pixWithdraw->charge_type ?? '') == 'percent' ? 'selected' : '' }}>%</option>
                            <option value="fixed" {{ ($pixWithdraw->charge_type ?? '') == 'fixed' ? 'selected' : '' }}>Fixa</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="mt-4 border-top pt-4 text-end">
                <button type="submit" class="btn btn-primary px-4 btn-save" {{ !$pixCharge && !$pixWithdraw ? 'disabled' : '' }}>
                    <span class="normal-state"><i class="fa-solid fa-save me-2"></i> {{ __('Salvar Taxas e Limites') }}</span>
                    <span class="loading-state d-none"><span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Salvando...</span>
                </button>
            </div>
        </form>
    </div>
</div>
