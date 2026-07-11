<!-- Transaction Password Confirm Modal -->
<div class="modal fade" id="tp-confirm-modal" tabindex="-1" inert data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background-color: #151823; border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 16px;">
            <div class="modal-body p-4 text-center">
                <div style="width: 48px; height: 48px; border-radius: 50%; background: rgba(124, 58, 237, 0.1); color: #7C3AED; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px auto;">
                    <i class="fas fa-lock" style="font-size: 1.25rem;"></i>
                </div>

                <h4 style="color: #ffffff; font-size: 20px; font-weight: 700; margin-bottom: 12px;">Confirmar Operação</h4>
                <p style="color: rgba(255, 255, 255, 0.72); font-size: 14px; margin-bottom: 24px;">
                    Digite sua senha transacional para continuar.
                </p>
                
                <div style="margin-bottom: 24px;">
                    <label for="tp-confirm-input" class="visually-hidden">Senha transacional</label><input type="password" id="tp-confirm-input" name="transaction_password_confirmation" class="v2-input" style="text-align: center; letter-spacing: 4px; font-size: 18px;" maxlength="4" pattern="\d{4}" inputmode="numeric" placeholder="••••" autocomplete="off">
                </div>

                <div style="display: flex; justify-content: center; gap: 12px;">
                    <button type="button" class="op-btn-cancel" data-bs-dismiss="modal" aria-label="Cancelar confirmação de senha transacional">
                        Cancelar
                    </button>
                    <button type="button" class="op-btn-confirm" id="tp-confirm-submit-btn" aria-label="Confirmar senha transacional" style="background: #7C3AED; color: #ffffff; border: 1px solid #7C3AED;">
                        Confirmar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentForm = null;
    let confirmModal = new bootstrap.Modal(document.getElementById('tp-confirm-modal'));
    let inputField = document.getElementById('tp-confirm-input');
    let modalElement = document.getElementById('tp-confirm-modal');

    inputField.addEventListener('input', function() {
        this.value = this.value.replace(/\D/g, '').slice(0, 4);
    });

    inputField.addEventListener('paste', function(event) {
        event.preventDefault();
    });

    modalElement.addEventListener('hidden.bs.modal', function() {
        inputField.value = '';
        currentForm = null;
    });

    // Attach click handler to any button with data-tp-confirm
    document.querySelectorAll('[data-tp-confirm]').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            currentForm = this.closest('form');
            if (currentForm) {
                // If the form has native validation, run it first
                if (currentForm.reportValidity && !currentForm.reportValidity()) {
                    return;
                }
                
                inputField.value = '';
                confirmModal.show();
                setTimeout(() => inputField.focus(), 500);
            }
        });
    });

    document.getElementById('tp-confirm-submit-btn').addEventListener('click', function() {
        if (!inputField.value || inputField.value.length !== 4) {
            alert('A senha transacional deve ter 4 dígitos.');
            return;
        }

        if (currentForm) {
            const formToSubmit = currentForm;

            // Append the password to the form before submitting
            let existingInput = formToSubmit.querySelector('input[type="hidden"][name="transaction_password"]');
            if (!existingInput) {
                existingInput = document.createElement('input');
                existingInput.type = 'hidden';
                existingInput.name = 'transaction_password';
                formToSubmit.appendChild(existingInput);
            }
            existingInput.value = inputField.value;
            
            confirmModal.hide();
            inputField.value = '';
            
            // Allow time for modal to hide before submitting, to avoid UI freezing issues
            setTimeout(() => {
                if (typeof disableSubmitButton === 'function') {
                    // Try to find the submit button to disable it
                    let btn = formToSubmit.querySelector('[data-tp-confirm]');
                    if(btn) disableSubmitButton(btn, 'Processando...');
                }
                formToSubmit.submit();
            }, 300);
        }
    });

    // Enter key support
    inputField.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            document.getElementById('tp-confirm-submit-btn').click();
        }
    });
});
</script>
