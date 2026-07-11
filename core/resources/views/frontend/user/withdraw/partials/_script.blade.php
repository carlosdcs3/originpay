<script>
    $(document).ready(function () {
        'use strict';
        
        const siteCurrency = "{{ siteCurrency() }}";
        
        // Define PIX config from backend
        const accountInfo = {
            processing_time: @json($pixMethod ? $pixMethod->processing_time : 'Instantâneo'),
            charge: @json($pixMethod ? (float)$pixMethod->charge : 0),
            charge_type: @json($pixMethod ? $pixMethod->charge_type : 'fixed'),
            conversion_rate: @json($pixMethod ? (float)$pixMethod->conversion_rate : 1),
            currency: @json($pixMethod ? $pixMethod->currency : siteCurrency()),
            min_limit: @json($pixMethod ? (float)$pixMethod->min_withdraw : 1),
            max_limit: @json($pixMethod ? (float)$pixMethod->max_withdraw : 100000)
        };

        // Update withdrawal summary
        const updateSummary = () => {
            const amount = parseFloat($('.amount-input').val()) || 0;
            const pixKeySelect = $('select[name="pix_key_id"]');
            const pixKeyText = pixKeySelect.length > 0 && pixKeySelect.val() ? pixKeySelect.find('option:selected').text().trim() : 'Nenhuma chave';

            const {
                processing_time,
                charge,
                charge_type,
                min_limit,
                max_limit
            } = accountInfo;

            // Validate amount
            if (amount < min_limit || amount > max_limit) {
                showError('.withdraw-amount-info', `O valor deve estar entre ${min_limit} ${siteCurrency} e ${max_limit} ${siteCurrency}`);
            } else {
                showSuccess('.withdraw-amount-info', `Limites: Mín ${min_limit} ${siteCurrency} | Máx ${max_limit} ${siteCurrency}`);
            }

            // Calculate and display values
            const fee = charge_type === 'fixed' || charge_type == 1 ? charge : (amount * charge) / 100;
            
            // For PIX withdrawals:
            // The fee is deducted from the requested amount, so the user receives:
            const receivedAmount = amount > fee ? amount - fee : 0;

            updateSummaryUI({
                pixKeyText,
                amount,
                fee,
                receivedAmount
            });
        };

        // Update summary UI
        const updateSummaryUI = ({
                                     pixKeyText,
                                     amount,
                                     fee,
                                     receivedAmount
                                 }) => {
            $('.summary-pix-key').text(pixKeyText);
            $('.summary-amount').text(`${amount.toFixed(2)} ${siteCurrency}`);
            $('.summary-charge').text(`${fee.toFixed(2)} ${siteCurrency}`);
            $('.summary-received').text(`${receivedAmount.toFixed(2)} ${siteCurrency}`);
        };

        // Show error message
        const showError = (selector, message) => {
            $(selector).text(message).addClass('text-danger').removeClass('text-success');
        };

        // Show success message
        const showSuccess = (selector, message) => {
            $(selector).text(message).addClass('text-success').removeClass('text-danger');
        };

        // Event bindings
        $('.amount-input').on('input change', updateSummary);
        $('select[name="pix_key_id"]').on('change', updateSummary);
        
        // Initial call
        updateSummary();
    });
</script>