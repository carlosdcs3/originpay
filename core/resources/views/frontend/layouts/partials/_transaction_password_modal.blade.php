<style>
#transaction-password-setup-modal {
    -webkit-backdrop-filter: blur(8px);
    backdrop-filter: blur(8px);
    background: rgba(9, 11, 16, 0.78);
}

#transaction-password-setup-modal .modal-dialog {
    max-width: 410px;
    margin: 1rem auto;
}

#transaction-password-setup-modal .modal-content {
    background-color: #151823;
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 16px;
    box-shadow: 0 18px 38px -18px rgba(0, 0, 0, 0.75);
    padding: 24px;
}

.tp-icon-wrapper {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    background: rgba(124, 58, 237, 0.1);
    color: #7C3AED;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 18px auto;
}

.tp-title {
    color: #ffffff;
    font-size: 20px;
    font-weight: 700;
    margin-bottom: 8px;
    text-align: center;
}

.tp-desc {
    color: rgba(226, 232, 240, 0.82);
    font-size: 13px;
    line-height: 1.5;
    margin: 0 auto 20px;
    max-width: 31ch;
    text-align: center;
}

.tp-form-group {
    margin-bottom: 16px;
    text-align: left;
}

.tp-form-group label {
    display: block;
    color: rgba(255, 255, 255, 0.9);
    font-size: 13px;
    font-weight: 500;
    margin-bottom: 8px;
}

.tp-input-wrapper {
    position: relative;
}

.tp-input {
    width: 100%;
    height: 44px;
    background: #090B10;
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 10px;
    color: #fff;
    padding: 0 16px;
    font-size: 16px;
    letter-spacing: 4px;
    transition: all 0.2s;
    text-align: center;
}

.tp-input:focus {
    border-color: #7C3AED;
    outline: none;
    box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.15);
}

.tp-btn-submit {
    width: 100%;
    height: 44px;
    border-radius: 10px;
    background: #7C3AED;
    color: #fff;
    font-weight: 600;
    border: none;
    transition: background 0.2s;
    margin-top: 8px;
    cursor: pointer;
}

.tp-btn-submit:hover {
    background: #6D28D9;
}

@media (max-width: 480px) {
    #transaction-password-setup-modal .modal-dialog {
        max-width: calc(100vw - 32px);
        margin: 0.75rem auto;
    }

    #transaction-password-setup-modal .modal-content {
        padding: 22px;
    }
}
</style>

@if(auth()->check() && !auth()->user()->transactionPassword()->exists())
<div class="modal fade" id="transaction-password-setup-modal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-labelledby="transaction-password-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="tp-icon-wrapper" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
            </div>

            <h3 class="tp-title" id="transaction-password-title">Criar senha transacional</h3>
            <p class="tp-desc">
                Use 4 dígitos para confirmar operações sensíveis, como transferências e geração de API Keys.
            </p>

            <form action="{{ route('user.transaction-password.store') }}" method="POST">
                @csrf
                <div class="tp-form-group">
                    <label for="transaction-password-input">Nova senha transacional</label>
                    <div class="tp-input-wrapper">
                        <input type="password" id="transaction-password-input" name="transaction_password" class="tp-input js-transaction-pin" maxlength="4" pattern="\d{4}" inputmode="numeric" autocomplete="new-password" required placeholder="••••">
                    </div>
                </div>

                <div class="tp-form-group">
                    <label for="transaction-password-confirmation-input">Confirmar senha transacional</label>
                    <div class="tp-input-wrapper">
                        <input type="password" id="transaction-password-confirmation-input" name="transaction_password_confirmation" class="tp-input js-transaction-pin" maxlength="4" pattern="\d{4}" inputmode="numeric" autocomplete="new-password" required placeholder="••••">
                    </div>
                </div>

                <button type="submit" class="tp-btn-submit" aria-label="Criar senha transacional">Criar senha transacional</button>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var modalElement = document.getElementById('transaction-password-setup-modal');
    if (!modalElement || typeof bootstrap === 'undefined' || !bootstrap.Modal) {
        return;
    }

    var tpModal = new bootstrap.Modal(modalElement, {
        backdrop: 'static',
        keyboard: false
    });

    document.querySelectorAll('#transaction-password-setup-modal .js-transaction-pin').forEach(function(input) {
        input.addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '').slice(0, 4);
        });
        input.addEventListener('paste', function(event) {
            event.preventDefault();
        });
    });

    modalElement.addEventListener('shown.bs.modal', function() {
        var firstInput = document.getElementById('transaction-password-input');
        if (firstInput) {
            firstInput.focus();
        }
    }, { once: true });

    tpModal.show();
});
</script>
@endif
