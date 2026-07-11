@php($formUid = 'platform_fee_rule_'.uniqid())

<div id="{{ $formUid }}" class="platform-fee-rule-form">
    <div class="alert alert-info border-0 mb-4">
        <div class="fw-semibold mb-1">Como a OriginPay vai cobrar?</div>
        <div class="small mb-0">
            Use <strong>taxa simples</strong> para "R$ 0,30 fixo", "1,5% + R$ 0,30" ou "2% com minimo de R$ 0,30".
            Use <strong>por faixa de valor</strong> para "R$ 0,30 ate X" e "1,5% + R$ 0,30 acima de X".
        </div>
    </div>

    <div class="row g-3">
        @if($includeMerchant)
            <div class="col-12">
                <label class="form-label">Merchant</label>
                <select name="user_id" class="form-select" required>
                    <option value="">Selecione</option>
                    @foreach($merchants as $merchant)
                        <option value="{{ $merchant->id }}">{{ $merchant->name ?? $merchant->username ?? $merchant->email }} - {{ $merchant->email }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        <div class="col-md-6">
            <label class="form-label">Metodo</label>
            <select name="payment_method" class="form-select" required>
                <option value="pix">Pix</option>
                <option value="card">Cartao</option>
                <option value="boleto">Boleto</option>
                <option value="crypto">Crypto</option>
            </select>
        </div>

        <div class="col-md-6">
            <label class="form-label">Moeda</label>
            <input type="text" name="currency" class="form-control" value="BRL" maxlength="3" required>
        </div>

        <div class="col-12">
            <label class="form-label">Modelo de cobranca</label>
            <div class="row g-2">
                <div class="col-md-6">
                    <label class="border rounded p-3 w-100 h-100 fee-model-option">
                        <div class="form-check mb-1">
                            <input class="form-check-input js-pricing-model" type="radio" name="pricing_model" value="flat" checked>
                            <span class="form-check-label fw-semibold">Taxa simples</span>
                        </div>
                        <div class="small text-muted">Uma formula unica para qualquer valor: fixa + percentual.</div>
                    </label>
                </div>
                <div class="col-md-6">
                    <label class="border rounded p-3 w-100 h-100 fee-model-option">
                        <div class="form-check mb-1">
                            <input class="form-check-input js-pricing-model" type="radio" name="pricing_model" value="tiered">
                            <span class="form-check-label fw-semibold">Por faixa de valor</span>
                        </div>
                        <div class="small text-muted">Uma regra para valores pequenos e outra para valores maiores.</div>
                    </label>
                </div>
            </div>
        </div>

        <div class="col-12 js-flat-fields">
            <div class="border rounded p-3">
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <button type="button" class="btn btn-sm btn-outline-secondary js-preset-fixed">Exemplo: R$ 0,30 fixo</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary js-preset-percentage">Exemplo: 1,5% + R$ 0,30</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary js-preset-crypto">Exemplo: Crypto 2% minimo R$ 0,30</button>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Taxa fixa (R$)</label>
                        <input type="number" step="0.00000001" min="0" name="fixed_fee" class="form-control js-fixed-fee" value="0.30" required>
                        <div class="form-text">Valor cobrado em toda transacao.</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Taxa percentual (%)</label>
                        <input type="number" step="0.0001" min="0" max="100" name="percentage_fee" class="form-control js-percentage-fee" value="2.0000" required>
                        <div class="form-text">Percentual calculado sobre o valor bruto.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 js-tiered-fields d-none">
            <div class="border rounded p-3">
                <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                    <div>
                        <div class="fw-semibold">Faixas por valor da transacao</div>
                        <div class="small text-muted">Exemplo comum: R$ 0,30 ate R$ 20,00; acima disso, 1,5% + R$ 0,30.</div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary js-preset-tiered">Usar exemplo</button>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>De (R$)</th>
                                <th>Ate (R$)</th>
                                <th>Fixa (R$)</th>
                                <th>Percentual (%)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><input type="number" step="0.01" min="0" name="tiers[0][from_amount]" class="form-control form-control-sm js-tier-from-0" value="0"></td>
                                <td><input type="number" step="0.01" min="0" name="tiers[0][to_amount]" class="form-control form-control-sm js-tier-to-0" value="20.00"></td>
                                <td><input type="number" step="0.00000001" min="0" name="tiers[0][fixed_fee]" class="form-control form-control-sm js-tier-fixed-0" value="0.30"></td>
                                <td><input type="number" step="0.0001" min="0" max="100" name="tiers[0][percentage_fee]" class="form-control form-control-sm js-tier-percentage-0" value="0"></td>
                            </tr>
                            <tr>
                                <td><input type="number" step="0.01" min="0" name="tiers[1][from_amount]" class="form-control form-control-sm js-tier-from-1" value="20.01"></td>
                                <td><input type="number" step="0.01" min="0" name="tiers[1][to_amount]" class="form-control form-control-sm js-tier-to-1" placeholder="Sem limite"></td>
                                <td><input type="number" step="0.00000001" min="0" name="tiers[1][fixed_fee]" class="form-control form-control-sm js-tier-fixed-1" value="0.30"></td>
                                <td><input type="number" step="0.0001" min="0" max="100" name="tiers[1][percentage_fee]" class="form-control form-control-sm js-tier-percentage-1" value="1.5000"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <label class="form-label">Taxa minima (piso opcional)</label>
            <input type="number" step="0.00000001" min="0" name="minimum_fee" class="form-control" value="0">
            <div class="form-text">Ex: Crypto 2% com minimo de R$ 0,30 = percentual 2, fixa 0, minima 0,30.</div>
        </div>

        <div class="col-md-6">
            <label class="form-label">Taxa maxima (teto opcional)</label>
            <input type="number" step="0.00000001" min="0" name="maximum_fee" class="form-control">
            <div class="form-text">Deixe vazio para nao limitar.</div>
        </div>

        <div class="col-md-6">
            <label class="form-label">Prazo de liquidacao (dias)</label>
            <input type="number" min="0" max="365" name="settlement_delay_days" class="form-control" value="1" required>
        </div>

        <div class="col-md-6">
            <label class="form-label">Reserva (%)</label>
            <input type="number" step="0.0001" min="0" max="100" name="reserve_percentage" class="form-control" value="0" required>
        </div>

        <div class="col-md-6">
            <label class="form-label">Status</label>
            <select name="status" class="form-select" required>
                <option value="active">Ativa</option>
                <option value="inactive">Inativa</option>
            </select>
        </div>

        <div class="col-md-6">
            <label class="form-label">Inicio</label>
            <input type="datetime-local" name="starts_at" class="form-control">
        </div>

        <div class="col-md-6">
            <label class="form-label">Fim</label>
            <input type="datetime-local" name="ends_at" class="form-control">
        </div>

        <div class="col-md-6">
            <label class="form-label">Motivo</label>
            <input type="text" name="reason" class="form-control" maxlength="255" placeholder="Ex: politica comercial Pix lancamento" required>
            <div class="form-text">O motivo fica salvo no historico de auditoria.</div>
        </div>

        <div class="col-12">
            <button class="btn btn-primary">Salvar regra</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
    (function () {
        const root = document.getElementById(@json($formUid));

        if (!root) {
            return;
        }

        const flatFields = root.querySelector('.js-flat-fields');
        const tieredFields = root.querySelector('.js-tiered-fields');
        const fixedFee = root.querySelector('.js-fixed-fee');
        const percentageFee = root.querySelector('.js-percentage-fee');

        function setValue(selector, value) {
            const input = root.querySelector(selector);

            if (input) {
                input.value = value;
            }
        }

        function syncModel() {
            const selected = root.querySelector('.js-pricing-model:checked').value;
            flatFields.classList.toggle('d-none', selected !== 'flat');
            tieredFields.classList.toggle('d-none', selected !== 'tiered');
        }

        root.querySelectorAll('.js-pricing-model').forEach((input) => {
            input.addEventListener('change', syncModel);
        });

        root.querySelector('.js-preset-fixed')?.addEventListener('click', function () {
            fixedFee.value = '0.30';
            percentageFee.value = '0';
        });

        root.querySelector('.js-preset-percentage')?.addEventListener('click', function () {
            fixedFee.value = '0.30';
            percentageFee.value = '1.5000';
        });

        root.querySelector('.js-preset-crypto')?.addEventListener('click', function () {
            fixedFee.value = '0';
            percentageFee.value = '2.0000';
            const minimumFee = root.querySelector('[name="minimum_fee"]');

            if (minimumFee) {
                minimumFee.value = '0.30';
            }
        });

        root.querySelector('.js-preset-tiered')?.addEventListener('click', function () {
            setValue('.js-tier-from-0', '0');
            setValue('.js-tier-to-0', '20.00');
            setValue('.js-tier-fixed-0', '0.30');
            setValue('.js-tier-percentage-0', '0');
            setValue('.js-tier-from-1', '20.01');
            setValue('.js-tier-to-1', '');
            setValue('.js-tier-fixed-1', '0.30');
            setValue('.js-tier-percentage-1', '1.5000');
        });

        syncModel();
    })();
</script>
@endpush
