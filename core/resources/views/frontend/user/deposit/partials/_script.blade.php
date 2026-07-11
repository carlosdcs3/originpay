@php use App\Enums\MethodType; @endphp
<script>
    $(document).ready(function () {
        'use strict';
        // DOM Elements
        const elements = {
            depositMethodList: $('select.deposit-method-list'),
            depositMethodInfo: $('.deposit-method-info'),
            depositAmountInfo: $('.deposit-amount-info'),
            walletSelect: $('select.wallet-select'),
            depositInput: $('.deposit-amount'),
            summary: {
                methodName: $('.summary-method-name'),
                amount: $('.summary-amount'),
                payable: $('.summary-payable'),
            },
        };
        const templates = {
            defaultOption: `<option disabled selected>{{ __('Select Deposit Method') }}</option>`,
            noMethodOption: `<option disabled selected>{{ __('Select Another Wallet') }}</option>`,
        };
        let depositMethods = [];
        let selectedMethod = null;
        const siteCurrency = '{{ siteCurrency() }}';
        let walletCurrency = null;
        
        if (elements.walletSelect.val() !== null) {
            walletAjaxRequest(elements.walletSelect.val());
        }

        const updateDepositMethods = (methods) => {
            const {depositMethodList, depositMethodInfo} = elements;

            if (methods.length) {
                if(methods.length === 1) {
                    depositMethodList.html('');
                    depositMethodList.append(`<option value="${methods[0].id}" selected>${methods[0].name}</option>`);
                    depositMethodList.trigger('change');
                } else {
                    depositMethodList.html(templates.defaultOption);
                    methods.forEach(method =>
                        depositMethodList.append(`<option value="${method.id}">${method.name}</option>`)
                    );
                }
                depositMethodInfo.text('');
            } else {
                depositMethodList.html(templates.noMethodOption);
                depositMethodInfo.text('{{ __('No Payment Method Available For This Wallet') }}');
            }

            // Atualiza a interface gráfica do plugin nice-select
            if ($.fn.niceSelect) {
                depositMethodList.niceSelect('update');
            }
            
            // Hide loading skeleton
            $('#loading-method-display').hide();

            // Toggle visibility between beautiful single display and dropdown
            if (methods.length === 1) {
                $('#multi-method-display').hide();
                $('#single-method-name').text(methods[0].name);
                $('#single-method-display').css('display', 'flex');
            } else {
                $('#single-method-display').hide();
                $('#multi-method-display').show();
            }
        };

        const updateDepositAmountInfo = (amount) => {
            if (!selectedMethod) return;
            const {min_deposit, max_deposit} = selectedMethod;
            const {depositAmountInfo} = elements;
            if (amount < min_deposit || amount > max_deposit) {
                depositAmountInfo.text(`{{ __('Amount must be between') }} ${min_deposit} ${siteCurrency} {{ __('and') }} ${max_deposit} ${siteCurrency}`);
                resetSummary();
            } else {
                depositAmountInfo.text(`{{ __('Min Deposit') }}: ${min_deposit} ${siteCurrency}, {{ __('Max Deposit') }}: ${max_deposit} ${siteCurrency}`);
                updateSummary(amount);
            }
        };

        const updateSummary = (amount) => {
            if (!selectedMethod) {
                resetSummary();
                return;
            }
            // Sem taxas e sem conversão
            const payable = amount;

            const {summary} = elements;
            summary.methodName.text(selectedMethod.name);
            summary.amount.text(`${amount.toFixed(2)} ${siteCurrency}`);
            summary.payable.text(`${payable.toFixed(2)} ${siteCurrency}`);
        };

        const resetSummary = () => {
            const {summary} = elements;
            summary.methodName.text(selectedMethod ? selectedMethod.name : '-');
            summary.amount.text(`0.00 ${siteCurrency}`);
            summary.payable.text(`0.00 ${siteCurrency}`);
        };

        // Event Handlers
        elements.walletSelect.on('change', function () {
            const walletId = $(this).val();
            walletAjaxRequest(walletId);
        });
        
        elements.depositMethodList.on('change', function () {
            const paymentMethodId = $(this).val();
            selectedMethod = depositMethods.find(method => method.id == paymentMethodId);
            walletCurrency = selectedMethod?.currency;
            if (selectedMethod) {
                // Remove charge text rendering
                elements.depositMethodInfo.text('');
                updateDepositAmountInfo(parseFloat(elements.depositInput.val()) || 0);
                $('#manual-deposit-credentials').html('');
                
                if (selectedMethod.type === `{{ MethodType::MANUAL }}`) {
                    $.ajax({
                        url: `{{ route('user.deposit.credentials', ':method') }}`.replace(':method', paymentMethodId),
                        method: 'GET',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: (data) => {
                            $('#manual-deposit-credentials').html(data);
                        },
                    })
                }
            }
        });

        elements.depositInput.on('input change', function () {
            const amount = parseFloat($(this).val()) || 0;
            updateDepositAmountInfo(amount);
        });
        
        function walletAjaxRequest(walletId) {
            const url = `{{ route('user.wallet.supported-payment-methods', ':wallet') }}`.replace(':wallet', walletId);
            
            $.ajax({
                url,
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: (data) => {
                    // Filtro inteligente: agrupa qualquer variação de PIX (PIX EFI, PIX Manual) em um só método chamado "PIX"
                    // e adiciona os demais métodos normalmente (como Boleto futuramente).
                    let filteredData = [];
                    let hasPix = false;
                    
                    data.forEach(m => {
                        let isPix = m.name.toLowerCase().includes('pix');
                        if (isPix) {
                            if (!hasPix) {
                                m.name = 'PIX'; // Força o nome de exibição para "PIX"
                                filteredData.push(m);
                                hasPix = true;
                            }
                        } else {
                            filteredData.push(m); // Permite Boleto, Cartão, etc
                        }
                    });
                    
                    depositMethods = filteredData;
                    selectedMethod = null;
                    resetSummary();
                    updateDepositMethods(filteredData);
                },
                error: () => {
                    alert('{{ __('Failed to load payment methods. Please try again later.') }}');
                }
            });
        }
    });
</script>