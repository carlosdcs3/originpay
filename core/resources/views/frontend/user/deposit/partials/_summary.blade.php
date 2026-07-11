<div class="ds-card ds-tx-summary-card" style="border-color:rgba(0,212,170,0.2);">
    {{-- Header Banner --}}
    <div class="tx-summary-banner" style="background:linear-gradient(135deg,rgba(124,58,237,0.12) 0%,rgba(124,58,237,0.03) 100%);border-bottom:1px solid rgba(124,58,237,0.1);">
        <i class="fas fa-wallet" style="color:var(--ds-primary-light);font-size:1.1rem;"></i>
        <div>
            <div class="tx-summary-banner-title">Resumo da Operação</div>
            <div class="tx-summary-banner-sub">Depósito via PIX</div>
        </div>
    </div>

    <div class="ds-card-body padded">
        <ul class="tx-summary-list">
            <li>
                <span class="tx-summary-label">Método</span>
                <span class="tx-summary-value summary-method-name">-</span>
            </li>
            <li>
                <span class="tx-summary-label">Valor a Depositar</span>
                <span class="tx-summary-value summary-amount">0.00 {{ siteCurrency() }}</span>
            </li>
            <li class="tx-summary-total" style="background:rgba(124,58,237,0.07);border:1px solid rgba(124,58,237,0.2) !important;">
                <span class="tx-summary-label" style="color:var(--ds-primary-light);">Total a Pagar</span>
                <span class="tx-summary-value summary-payable" style="color:var(--ds-primary-light);">0.00 {{ siteCurrency() }}</span>
            </li>
        </ul>
    </div>
</div>