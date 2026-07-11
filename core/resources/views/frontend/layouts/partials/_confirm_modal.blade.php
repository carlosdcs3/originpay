<style>
/* OriginPay Premium Confirm Modal */
#op-confirm-modal {
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
}

#op-confirm-modal .modal-dialog {
    max-width: 500px;
    margin: 1.75rem auto;
    transform: scale(0.96);
    transition: transform 180ms cubic-bezier(0.16, 1, 0.3, 1), opacity 180ms ease-out;
    opacity: 0;
}

#op-confirm-modal.show .modal-dialog {
    transform: scale(1);
    opacity: 1;
}

#op-confirm-modal .modal-content {
    background-color: #151823;
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 16px;
    box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.5), 0 10px 20px -5px rgba(0, 0, 0, 0.3);
    padding: 32px 24px;
}

#op-confirm-modal .op-modal-icon-wrapper {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: rgba(220, 38, 38, 0.08); /* #DC2626 com baixa opacidade */
    color: #DC2626;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px auto;
}

#op-confirm-title {
    color: #ffffff;
    font-size: 24px;
    font-weight: 700;
    margin-bottom: 12px;
}

#op-confirm-message {
    color: rgba(255, 255, 255, 0.72);
    font-size: 15px;
    line-height: 1.5;
    margin-bottom: 32px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Botões */
.op-modal-actions {
    display: flex;
    justify-content: center;
    gap: 12px;
}

.op-btn-cancel, .op-btn-confirm {
    height: 42px;
    padding: 0 20px;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 150ms ease;
    cursor: pointer;
}

.op-btn-cancel {
    background: rgba(255, 255, 255, 0.05);
    color: #E5E7EB;
    border: 1px solid rgba(255, 255, 255, 0.08);
}
.op-btn-cancel:hover {
    background: rgba(255, 255, 255, 0.10);
    color: #ffffff;
}

.op-btn-confirm {
    background: transparent;
    color: #DC2626;
    border: 1px solid #DC2626;
}
.op-btn-confirm:hover {
    background: #DC2626;
    color: #ffffff;
}

/* Responsividade */
@media (max-width: 768px) {
    #op-confirm-modal .modal-dialog {
        max-width: 440px;
    }
}
@media (max-width: 576px) {
    #op-confirm-modal .modal-dialog {
        max-width: 92vw;
        margin: 1rem auto;
    }
}
</style>

<!-- Generic Confirm Modal for OriginPay V2 -->
<div class="modal fade" id="op-confirm-modal" tabindex="-1" inert data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body p-0 text-center">
                <!-- Icon container -->
                <div class="op-modal-icon-wrapper">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
                        <path d="M12 9v4"/>
                        <path d="M12 17h.01"/>
                    </svg>
                </div>

                <h4 id="op-confirm-title">Confirmação</h4>
                <p id="op-confirm-message">Tem certeza que deseja executar esta ação?</p>
                
                <form id="op-confirm-form" method="POST" action="">
                    @csrf
                    <input type="hidden" name="_method" id="op-confirm-method" value="POST">
                    
                    <div class="op-modal-actions">
                        <button type="button" class="op-btn-cancel" data-bs-dismiss="modal" aria-label="Cancelar confirmação">
                            Cancelar
                        </button>
                        <button type="submit" class="op-btn-confirm" aria-label="Confirmar ação">
                            Confirmar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
