{{-- 
  OriginPay Standard Confirmation Modal 
  This modal is controlled via ds-confirm.js 
--}}
<div class="modal fade ds-confirm-modal" id="dsConfirmModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border: none; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.1);">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <h5 class="modal-title fw-bold" id="dsConfirmTitle">Confirmação</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="dsConfirmBtnCloseIcon"></button>
            </div>
            <div class="modal-body py-4 px-4">
                <p id="dsConfirmText" class="mb-0 ds-text-main" style="font-size: 0.95rem; line-height: 1.5;"></p>
            </div>
            <div class="modal-footer border-0 pb-4 px-4 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4 ds-btn-cancel" data-bs-dismiss="modal" id="dsConfirmBtnCancel">Cancelar</button>
                <button type="button" class="btn btn-primary rounded-pill px-4 d-flex align-items-center justify-content-center" id="dsConfirmBtnConfirm" style="min-width: 120px;">
                    <span class="ds-confirm-btn-text">Confirmar</span>
                    <span class="spinner-border spinner-border-sm d-none ms-2" role="status" aria-hidden="true" id="dsConfirmSpinner"></span>
                </button>
            </div>
        </div>
    </div>
</div>
