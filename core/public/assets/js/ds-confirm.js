/**
 * OriginPay Standard Confirmation Utility
 * Replaces window.confirm(), window.alert(), window.prompt()
 */

(function (window) {
    'use strict';

    let currentConfirmCallback = null;

    /**
     * Shows a customized confirm modal.
     * 
     * @param {Object} options
     * @param {string} options.title - Modal title
     * @param {string} options.text - Main description text (can include HTML)
     * @param {string} [options.confirmBtnText='Confirmar'] - Text for the confirm button
     * @param {string} [options.cancelBtnText='Cancelar'] - Text for the cancel button
     * @param {string} [options.confirmBtnClass='btn-primary'] - Additional classes for the confirm button (e.g. 'btn-danger')
     * @param {Function} options.onConfirm - Callback fired when confirm button is clicked
     * @param {Function} [options.onCancel] - Callback fired when canceled (optional)
     * @param {boolean} [options.ajax=false] - If true, keeps modal open and shows spinner. The callback must return a Promise.
     */
    window.dsConfirm = function (options) {
        const modalEl = document.getElementById('dsConfirmModal');
        if (!modalEl) {
            console.error('dsConfirmModal element not found in DOM.');
            // Fallback
            if (window.confirm(options.text.replace(/<[^>]+>/g, ''))) {
                if (options.onConfirm) options.onConfirm();
            } else {
                if (options.onCancel) options.onCancel();
            }
            return false;
        }

        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);

        const titleEl = document.getElementById('dsConfirmTitle');
        const textEl = document.getElementById('dsConfirmText');
        const btnCancel = document.getElementById('dsConfirmBtnCancel');
        const btnConfirm = document.getElementById('dsConfirmBtnConfirm');
        const btnText = btnConfirm.querySelector('.ds-confirm-btn-text');
        const spinner = document.getElementById('dsConfirmSpinner');
        const btnCloseIcon = document.getElementById('dsConfirmBtnCloseIcon');

        // Set content
        titleEl.innerHTML = options.title || 'Confirmação';
        textEl.innerHTML = options.text || 'Tem certeza que deseja continuar?';
        btnCancel.innerText = options.cancelBtnText || 'Cancelar';
        btnText.innerText = options.confirmBtnText || 'Confirmar';

        // Reset button classes
        btnConfirm.className = 'btn rounded-pill px-4 d-flex align-items-center justify-content-center ' + (options.confirmBtnClass || 'btn-primary');
        
        // Reset state
        btnConfirm.disabled = false;
        btnCancel.disabled = false;
        btnCloseIcon.disabled = false;
        spinner.classList.add('d-none');

        // Remove old listeners to avoid multiple fires
        const newBtnConfirm = btnConfirm.cloneNode(true);
        btnConfirm.parentNode.replaceChild(newBtnConfirm, btnConfirm);

        // Bind new listener
        newBtnConfirm.addEventListener('click', function () {
            if (options.ajax) {
                // Loading state
                newBtnConfirm.disabled = true;
                btnCancel.disabled = true;
                btnCloseIcon.disabled = true;
                newBtnConfirm.querySelector('.spinner-border').classList.remove('d-none');

                // Execute callback, expect a promise
                const result = options.onConfirm();
                if (result && typeof result.then === 'function') {
                    result.finally(() => {
                        modal.hide();
                    });
                }
            } else {
                modal.hide();
                if (options.onConfirm) options.onConfirm();
            }
        });

        // Handle cancel callback if provided (using jQuery or vanilla events on the modal)
        if (options.onCancel) {
            const hiddenListener = function () {
                // Determine if it was closed via confirm or cancel
                // Since modal.hide() is called in both, we need to check state.
                // A simpler way: we always call onCancel unless confirm was clicked.
                modalEl.removeEventListener('hidden.bs.modal', hiddenListener);
            };
            modalEl.addEventListener('hidden.bs.modal', hiddenListener);
        }

        modal.show();
        return false; // Prevent default form submission if called from onclick="return dsConfirm(...)"
    };

    /**
     * Helper to wrap a form submission with dsConfirm.
     * Usage in blade: onclick="return dsConfirmForm(event, this.closest('form'), { title: '...', text: '...' })"
     */
    window.dsConfirmForm = function(event, form, options) {
        event.preventDefault();
        window.dsConfirm({
            title: options.title,
            text: options.text,
            confirmBtnText: options.confirmBtnText,
            confirmBtnClass: options.confirmBtnClass,
            onConfirm: function() {
                form.submit();
            }
        });
        return false;
    };

})(window);
