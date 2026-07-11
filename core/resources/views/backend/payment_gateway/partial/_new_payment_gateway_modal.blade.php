<div class="modal fade" id="new-payment-gateway-modal" tabindex="-1" aria-labelledby="newPaymentGatewayModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h4 class="modal-title fw-bold" id="newPaymentGatewayModalLabel">{{ __('Catálogo de Gateways') }}</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2">
                <p class="text-muted mb-4">{{ __('Selecione um provedor da lista abaixo. Toda a infraestrutura do gateway será provisionada automaticamente e você será redirecionado para preencher as credenciais.') }}</p>
                
                <form action="{{ route('admin.payment.gateway.store') }}" method="POST" id="catalog-form">
                    @csrf
                    <input type="hidden" name="provider" id="selected-provider" value="">

                    <div class="row row-cols-1 row-cols-md-3 g-4">
                        @php
                            $providers = \App\Gateway\Providers\Registry\ProviderRegistry::getProviders();
                        @endphp
                        
                        @foreach($providers as $code => $providerObj)
                            @php $def = $providerObj->definition(); @endphp
                            <div class="col">
                                <div class="card h-100 border provider-card cursor-pointer" data-code="{{ $code }}" onclick="selectProvider('{{ $code }}')">
                                    <div class="card-body text-center position-relative">
                                        <div class="provider-check position-absolute top-0 end-0 m-3 d-none text-success">
                                            <i class="fa-solid fa-circle-check fs-4"></i>
                                        </div>
                                        <div class="mb-3 mt-2" style="height: 60px; display: flex; align-items: center; justify-content: center;">
                                            @if($def->logo)
                                                <img src="{{ asset($def->logo) }}" alt="{{ $def->name }}" style="max-height: 50px; max-width: 100%;" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                <div class="gateway-logo-fallback" style="display:none; width: 50px; height: 50px; background: rgba(148,163,184,.10); border-radius: 8px; align-items: center; justify-content: center; color: #cbd5e1;"><i class="fa-solid fa-plug fa-2x"></i></div>
                                            @else
                                                <div class="gateway-logo-fallback" style="width: 50px; height: 50px; background: rgba(148,163,184,.10); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #cbd5e1;"><i class="fa-solid fa-plug fa-2x"></i></div>
                                            @endif
                                        </div>
                                        <h5 class="card-title fw-bold">{{ $def->name }}</h5>
                                        <p class="card-text text-muted small" style="min-height: 40px;">{{ $def->description }}</p>
                                        
                                        <div class="d-flex flex-wrap justify-content-center gap-1 mt-3">
                                            @if($def->supports_pix) <span class="badge op-badge"><i class="fa-brands fa-pix text-success"></i> PIX</span> @endif
                                            @if($def->supports_boleto) <span class="badge op-badge"><i class="fa-solid fa-barcode"></i> Boleto</span> @endif
                                            @if($def->supports_card) <span class="badge op-badge"><i class="fa-regular fa-credit-card text-primary"></i> Cartão</span> @endif
                                            @if($def->supports_crypto) <span class="badge op-badge"><i class="fa-brands fa-bitcoin text-warning"></i> Crypto</span> @endif
                                            @if($def->is_withdraw) <span class="badge op-badge"><i class="fa-solid fa-money-bill-transfer text-info"></i> Saque</span> @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancelar') }}</button>
                <button type="button" class="btn btn-primary px-4" id="btn-create-gateway" disabled onclick="document.getElementById('catalog-form').submit();">
                    <i class="fa-solid fa-magic me-2"></i>{{ __('Provisionar Gateway') }}
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.provider-card {
    transition: all 0.2s ease;
    border: 2px solid transparent !important;
}
.provider-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.05);
    border-color: #e2e8f0 !important;
}
.provider-card.selected {
    border-color: var(--bs-primary) !important;
    background-color: rgba(var(--bs-primary-rgb), 0.03);
}
.provider-card.selected .provider-check {
    display: block !important;
}
.cursor-pointer {
    cursor: pointer;
}
</style>

<script>
function selectProvider(code) {
    document.getElementById('selected-provider').value = code;
    
    // Remove selected class from all
    document.querySelectorAll('.provider-card').forEach(card => {
        card.classList.remove('selected');
        card.classList.add('border'); // restore standard border
    });
    
    // Add selected class to the clicked one
    const selectedCard = document.querySelector(`.provider-card[data-code="${code}"]`);
    if(selectedCard) {
        selectedCard.classList.remove('border'); // remove standard border
        selectedCard.classList.add('selected');
    }
    
    // Enable submit button
    document.getElementById('btn-create-gateway').disabled = false;
}
</script>
