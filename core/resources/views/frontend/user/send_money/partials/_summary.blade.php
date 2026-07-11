<div class="ds-card ds-tx-summary-card" style="border-color:rgba(124,110,255,0.2);">
    {{-- Header Banner --}}
    <div class="tx-summary-banner" style="background:linear-gradient(135deg,rgba(124,58,237,0.12) 0%,rgba(124,58,237,0.03) 100%);border-bottom:1px solid rgba(124,58,237,0.1);">
        <i class="fas fa-paper-plane" style="color:var(--ds-primary-light);font-size:1.1rem;"></i>
        <div>
            <div class="tx-summary-banner-title">Resumo da Transferência</div>
            <div class="tx-summary-banner-sub">Envio entre contas</div>
        </div>
    </div>

    <div class="ds-card-body padded">
        <ul class="tx-summary-list">
            <li>
                <span class="tx-summary-label">Valor Enviado</span>
                <span class="tx-summary-value summary-amount">0.00</span>
            </li>
            <li>
                <span class="tx-summary-label">Taxa</span>
                <span class="tx-summary-value summary-charge">0.00</span>
            </li>
            <li class="tx-summary-total" style="background:rgba(124,58,237,0.07);border:1px solid rgba(124,58,237,0.2) !important;">
                <span class="tx-summary-label" style="color:var(--ds-primary-light);">Total a Pagar</span>
                <span class="tx-summary-value summary-total" style="color:var(--ds-primary-light);">0.00</span>
            </li>
            <li class="d-none" style="display:none !important;">
                <span class="tx-summary-label">Taxa de Câmbio</span>
                <span class="tx-summary-value summary-rate"></span>
            </li>
            <li class="d-none" style="display:none !important;">
                <span class="tx-summary-label">Destinatário Recebe</span>
                <span class="tx-summary-value recipient-wallet-added"></span>
            </li>
            <li class="d-none" style="display:none !important;">
                <span class="tx-summary-label">Debitado da Carteira</span>
                <span class="tx-summary-value my-wallet-decreased"></span>
            </li>
        </ul>
    </div>
</div>