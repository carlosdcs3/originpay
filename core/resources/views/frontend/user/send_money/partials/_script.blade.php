@php use App\Constants\CurrencyRole; @endphp
<script>
$(document).ready(function () {
    'use strict';

    let sendMoneyInfo = {};
    let recipientValidated = false;
    let inputValues = { recipient: null, walletId: null, amount: 0 };
    let recipientDebounceTimer = null;

    const selectors = {
        walletSelect:         '.wallet-select',
        walletInfo:           '.wallet-info',
        recipientInfo:        '.recipient-info',
        sendAmountInfo:       '.send-amount-info',
        summaryAmount:        '.summary-amount',
        summaryCharge:        '.summary-charge',
        summaryTotal:         '.summary-total',
        summaryRate:          '.summary-rate',
        recipientWalletAdded: '.recipient-wallet-added',
        myWalletDecreased:    '.my-wallet-decreased',
    };

    // ── Helpers ────────────────────────────────────────────────────────────
    const hideCurrencyRows = () => {
        [selectors.summaryRate, selectors.recipientWalletAdded, selectors.myWalletDecreased]
            .forEach(s => $(s).closest('li').addClass('d-none').attr('style', 'display:none !important'));
    };

    const showSummaryPlaceholder = () => {
        $(selectors.summaryAmount).text('—');
        $(selectors.summaryCharge).text('—');
        $(selectors.summaryTotal).text('—');
        hideCurrencyRows();
    };

    // ── Summary update ─────────────────────────────────────────────────────
    const updateSummary = () => {
        const amount = inputValues.amount;

        // Not validated yet — show placeholder state
        if (!recipientValidated || !sendMoneyInfo.currency_rate || !sendMoneyInfo.wallet_currency) {
            showSummaryPlaceholder();
            return;
        }

        const { fee, fee_type, currency_rate, wallet_currency, min_limit, max_limit } = sendMoneyInfo;

        if (!amount) {
            showSummaryPlaceholder();
            return;
        }

        if (amount < min_limit || amount > max_limit) {
            $(selectors.sendAmountInfo)
                .text(`{{ __('Amount must be between') }} ${min_limit} {{ siteCurrency() }} {{ __('and') }} ${max_limit} {{ siteCurrency() }}`)
                .addClass('text-danger').removeClass('text-success');
            showSummaryPlaceholder();
            return;
        } else {
            $(selectors.sendAmountInfo)
                .text(`Mín: ${min_limit} {{ siteCurrency() }} | Máx: ${max_limit} {{ siteCurrency() }}`)
                .removeClass('text-danger').addClass('text-success');
        }

        const feeAmount = fee_type === 'fixed' ? parseFloat(fee) : (parseFloat(fee) / 100) * amount;
        const total = parseFloat(amount) + feeAmount;
        const convertedAmount = (total * currency_rate).toFixed(2);
        const recipientAmount = (amount * currency_rate).toFixed(2);
        const currency = '{{ siteCurrency() }}';

        $(selectors.summaryAmount).text(`${amount.toFixed(2)} ${currency}`);
        $(selectors.summaryCharge).text(`${feeAmount.toFixed(2)} ${currency}`);
        $(selectors.summaryTotal).text(`${total.toFixed(2)} ${currency}`);

        if (wallet_currency !== currency) {
            $(selectors.summaryRate).closest('li').removeClass('d-none').attr('style', '');
            $(selectors.summaryRate).text(`1 ${currency} = ${currency_rate} ${wallet_currency}`);
            $(selectors.recipientWalletAdded).closest('li').removeClass('d-none').attr('style', '');
            $(selectors.recipientWalletAdded).text(`${recipientAmount} ${wallet_currency}`);
            $(selectors.myWalletDecreased).closest('li').removeClass('d-none').attr('style', '');
            $(selectors.myWalletDecreased).text(`${convertedAmount} ${wallet_currency}`);
        } else {
            hideCurrencyRows();
        }
    };

    // ── Reset on recipient change ───────────────────────────────────────────
    const resetOnRecipientChange = () => {
        sendMoneyInfo = {};
        recipientValidated = false;
        inputValues.walletId = null;
        $(selectors.walletSelect).html('<option disabled selected>{{ __("Select Wallet") }}</option>');
        $(selectors.walletInfo).text('');
        $(selectors.recipientInfo).text('').removeClass('text-danger text-success');
        $(selectors.sendAmountInfo).text('');
        showSummaryPlaceholder();
    };

    // ── Recipient validation AJAX ──────────────────────────────────────────
    const fetchRecipientInfo = () => {
        const val = inputValues.recipient;
        if (!val || val.length < 3) return;

        $(selectors.recipientInfo).text('Verificando...').removeClass('text-danger text-success');

        const url = "{{ route('user.wallet.validate.recipient', [CurrencyRole::SENDER, ':value']) }}"
            .replace(':value', val);

        $.get(url, (data) => {
            if (data.status === 'success') {
                const { currency_role, wallet_currency, currency_rate, type } = data;
                recipientValidated = true;

                $(selectors.recipientInfo).text(data.message).removeClass('text-danger').addClass('text-success');
                $(selectors.walletSelect).html(data.available_wallets);

                if (type === 'wallet_uuid') {
                    sendMoneyInfo = { ...currency_role, wallet_currency, currency_rate };
                    updateWalletFee(currency_role);
                }

                // Auto-select first wallet → triggers fetchWalletInfo
                const firstWallet = $(selectors.walletSelect).find('option:not([disabled])').first().val();
                if (firstWallet) {
                    $(selectors.walletSelect).val(firstWallet).trigger('change');
                } else {
                    updateSummary();
                }
            } else {
                recipientValidated = false;
                resetOnRecipientChange();
                $(selectors.recipientInfo).text(data.message).addClass('text-danger').removeClass('text-success');
            }
        }).fail(() => {
            recipientValidated = false;
            $(selectors.recipientInfo).text('{{ __("Unable to validate recipient. Please try again.") }}').addClass('text-danger');
        });
    };

    // ── Wallet info AJAX ───────────────────────────────────────────────────
    const fetchWalletInfo = () => {
        if (!inputValues.walletId) return;
        const url = "{{ route('user.wallet.info', [CurrencyRole::SENDER, ':walletId']) }}"
            .replace(':walletId', inputValues.walletId);
        $.get(url, (data) => {
            sendMoneyInfo = { ...data.data };
            updateWalletFee(data.data);
            updateSummary();
        });
    };

    const updateWalletFee = (walletData) => {
        const feeText = walletData.fee_type === 'fixed'
            ? `${walletData.fee} {{ siteCurrency() }}`
            : `${walletData.fee}%`;
        $(selectors.walletInfo).text(`{{ __('Fee') }}: ${feeText}`);
        $(selectors.sendAmountInfo).text(`Mín: ${walletData.min_limit} {{ siteCurrency() }} | Máx: ${walletData.max_limit} {{ siteCurrency() }}`);
    };

    // ── Event listeners ────────────────────────────────────────────────────

    // Recipient: debounce 600ms on input so user doesn't need to blur
    $(document).on('input change', '.recipient-input', function () {
        const val = $(this).val().trim();
        inputValues.recipient = val;
        resetOnRecipientChange();
        clearTimeout(recipientDebounceTimer);
        if (val.length >= 3) {
            recipientDebounceTimer = setTimeout(fetchRecipientInfo, 600);
        }
    });

    // Wallet select (hidden)
    $(document).on('change', selectors.walletSelect, function () {
        inputValues.walletId = $(this).val();
        fetchWalletInfo();
    });

    // Amount — update summary in real time
    $(document).on('input change', '.amount-input', function () {
        inputValues.amount = parseFloat($(this).val()) || 0;
        updateSummary();
    });
});
</script>