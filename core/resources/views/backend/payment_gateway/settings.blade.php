@extends('backend.layouts.app')
@section('title', __('Configurações do gateway: ') . $gateway->name)
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="{{ route('admin.payment.gateway.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fa-solid fa-arrow-left me-1"></i> {{ __('Todos os Gateways') }}
        </a>
    </div>

    <!-- Enterprise Gateway Header Dashboard -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div class="d-flex align-items-center gap-4">
                    <div class="bg-light rounded p-2 border">
                        <img src="{{ asset($gateway->logo) }}" alt="{{ $gateway->name }}" width="64" height="64" class="object-fit-contain">
                    </div>
                    <div>
                        <h3 class="mb-1 fw-bold">{{ $gateway->name }}</h3>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            @if($gateway->status)
                                <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="fa-solid fa-circle-check me-1"></i> {{ __('Ativo') }}</span>
                            @else
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="fa-solid fa-circle-xmark me-1"></i> {{ __('Inativo') }}</span>
                            @endif

                            @if($gateway->is_sandbox)
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle"><i class="fa-solid fa-flask me-1"></i> {{ __('Sandbox') }}</span>
                            @else
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><i class="fa-solid fa-rocket me-1"></i> {{ __('Produção') }}</span>
                            @endif
                        </div>
                        <div class="d-flex gap-2">
                            @if($gateway->supports_pix)<span class="badge bg-light text-dark border"><i class="fa-solid fa-qrcode text-success me-1"></i> PIX</span>@endif
                            @if($gateway->is_withdraw)<span class="badge bg-light text-dark border"><i class="fa-solid fa-money-bill-transfer text-primary me-1"></i> Saque PIX</span>@endif
                            @if($gateway->supports_boleto)<span class="badge bg-light text-dark border"><i class="fa-solid fa-barcode me-1"></i> Boleto</span>@endif
                            @if($gateway->supports_card)<span class="badge bg-light text-dark border"><i class="fa-regular fa-credit-card text-info me-1"></i> Cartão</span>@endif
                            @if($gateway->supports_crypto)<span class="badge bg-light text-dark border"><i class="fa-brands fa-bitcoin text-warning me-1"></i> Crypto</span>@endif
                        </div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted small">{{ __('O teste manual de conexão não está disponível neste painel.') }}</span>
                    <button class="btn btn-outline-secondary" data-coreui-toggle="modal" data-coreui-target="#edit-gateway-modal-{{ $gateway->id }}">
                        <i class="fa-solid fa-pen me-1"></i> {{ __('Editar Gateway') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Sidebar Navigation -->
        <div class="col-md-3 mb-4">
            <div class="card border-0 shadow-sm sticky-top" style="top: 1rem; z-index: 10;">
                <div class="card-body p-0">
                    <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">

                        <!-- GERAL -->
                        <div class="px-4 py-2 mt-2 text-uppercase text-muted small fw-bold">{{ __('Geral') }}</div>
                        <a href="{{ route('admin.payment.gateway.overview', $gateway->id) }}" @class(['nav-link text-start px-4 py-2 rounded-0 border-start border-3', 'active bg-primary text-white fw-bold border-primary' => $activeTab == 'overview', 'text-muted' => $activeTab != 'overview'])>
                            <i class="fa-solid fa-chart-pie me-2 text-center" style="width: 20px;"></i> {{ __('Visão Geral') }}
                        </a>

                        <!-- CONFIGURAÇÃO -->
                        <div class="px-4 py-2 mt-3 text-uppercase text-muted small fw-bold">{{ __('Configuração') }}</div>
                        <a href="{{ route('admin.payment.gateway.credentials', $gateway->id) }}" @class(['nav-link text-start px-4 py-2 rounded-0 border-start border-3', 'active bg-primary text-white fw-bold border-primary' => $activeTab == 'credentials', 'text-muted' => $activeTab != 'credentials'])>
                            <i class="fa-solid fa-key me-2 text-center" style="width: 20px;"></i> {{ __('Credenciais') }}
                        </a>
                        <a href="{{ route('admin.payment.gateway.charge-methods', $gateway->id) }}" @class(['nav-link text-start px-4 py-2 rounded-0 border-start border-3', 'active bg-primary text-white fw-bold border-primary' => $activeTab == 'charge-methods', 'text-muted' => $activeTab != 'charge-methods'])>
                            <i class="fa-solid fa-qrcode me-2 text-center" style="width: 20px;"></i> {{ __('Cobrança PIX') }}
                        </a>
                        <a href="{{ route('admin.payment.gateway.withdraw-methods', $gateway->id) }}" @class(['nav-link text-start px-4 py-2 rounded-0 border-start border-3', 'active bg-primary text-white fw-bold border-primary' => $activeTab == 'withdraw-methods', 'text-muted' => $activeTab != 'withdraw-methods'])>
                            <i class="fa-solid fa-money-bill-transfer me-2 text-center" style="width: 20px;"></i> {{ __('Saque PIX') }}
                        </a>
                        <a href="{{ route('admin.payment.gateway.fees-limits', $gateway->id) }}" @class(['nav-link text-start px-4 py-2 rounded-0 border-start border-3', 'active bg-primary text-white fw-bold border-primary' => $activeTab == 'fees-limits', 'text-muted' => $activeTab != 'fees-limits'])>
                            <i class="fa-solid fa-sliders me-2 text-center" style="width: 20px;"></i> {{ __('Taxas e Limites') }}
                        </a>

                        <!-- INFRAESTRUTURA -->
                        <div class="px-4 py-2 mt-3 text-uppercase text-muted small fw-bold">{{ __('Infraestrutura') }}</div>
                        <a href="{{ route('admin.payment.gateway.webhooks', $gateway->id) }}" @class(['nav-link text-start px-4 py-2 rounded-0 border-start border-3', 'active bg-primary text-white fw-bold border-primary' => $activeTab == 'webhooks', 'text-muted' => $activeTab != 'webhooks'])>
                            <i class="fa-solid fa-network-wired me-2 text-center" style="width: 20px;"></i> {{ __('Webhooks') }}
                        </a>
                        <a href="{{ route('admin.payment.gateway.health', $gateway->id) }}" @class(['nav-link text-start px-4 py-2 rounded-0 border-start border-3', 'active bg-primary text-white fw-bold border-primary' => $activeTab == 'health', 'text-muted' => $activeTab != 'health'])>
                            <i class="fa-solid fa-heart-pulse me-2 text-center" style="width: 20px;"></i> {{ __('Health') }}
                        </a>
                        <a href="{{ route('admin.payment.gateway.routing', $gateway->id) }}" @class(['nav-link text-start px-4 py-2 rounded-0 border-start border-3', 'active bg-primary text-white fw-bold border-primary' => $activeTab == 'routing', 'text-muted' => $activeTab != 'routing'])>
                            <i class="fa-solid fa-route me-2 text-center" style="width: 20px;"></i> {{ __('Prioridade/Fallback') }}
                        </a>
                        <a href="{{ route('admin.payment.gateway.logs', $gateway->id) }}" @class(['nav-link text-start px-4 py-2 mb-2 rounded-0 border-start border-3', 'active bg-primary text-white fw-bold border-primary' => $activeTab == 'logs', 'text-muted' => $activeTab != 'logs'])>
                            <i class="fa-solid fa-list-ol me-2 text-center" style="width: 20px;"></i> {{ __('Logs') }}
                        </a>

                    </div>
                </div>
            </div>
        </div>

        <style>
            .nav-pills .nav-link {
                color: var(--ds-text-muted, #9e9e9e);
                transition: all 0.2s;
            }
            .nav-pills .nav-link:hover {
                color: var(--ds-text, #ffffff);
                background-color: rgba(255, 255, 255, 0.05);
            }
            .nav-pills .nav-link.active.gateway-tab-active {
                color: #ffffff !important;
                background-color: var(--ds-primary, #6366f1) !important;
                border-left-color: #ffffff !important;
                font-weight: 600;
            }
        </style>

        <!-- Content Area -->
        <div class="col-md-9">
            <div class="tab-content">
                @includeIf("backend.payment_gateway.tabs.{$activeTab}")
            </div>
        </div>
    </div>
    @include('backend.payment_gateway.partial._new_deposit_method_modal')
    @include('backend.payment_gateway.partial._new_withdraw_method_modal')
@endsection

@push('scripts')
    <!-- Edit Gateway Modal -->
    @include('backend.payment_gateway.partial._edit_payment_gateway_modal', ['gateway' => $gateway])
<script>
    $(document).ready(function() {
        // Dynamic Credentials
        $('#add-credential-btn').on('click', function() {
            let template = $('#credential-template').html();
            $('#credentials-container').append(template);
        });

        $(document).on('click', '.remove-credential', function() {
            $(this).closest('.credential-row').remove();
        });

        // Dynamic Field Builder for Withdraw Methods
        $('#add-field-btn').on('click', function() {
            let template = $('#field-builder-template').html();
            $('#fields-builder-container').append(template);
        });

        $(document).on('click', '.remove-field', function() {
            $(this).closest('.field-row').remove();
        });
        
        // Toggle Secret Visibility
        $(document).on('click', '.toggle-secret', function() {
            let input = $(this).siblings('.secret-input');
            let icon = $(this).find('i');
            
            if (input.attr('type') === 'password') {
                input.attr('type', 'text');
                icon.removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                input.attr('type', 'password');
                icon.removeClass('fa-eye-slash').addClass('fa-eye');
            }
        });
        
        // Copy Secret Value
        $(document).on('click', '.copy-secret', function() {
            let val = $(this).data('val');
            if(val) {
                navigator.clipboard.writeText(val);
                notify('success', '{{ __("Credencial copiada para a área de transferência!") }}');
            } else {
                notify('warning', '{{ __("A credencial está mascarada e não pode ser copiada. Insira um novo valor.") }}');
            }
        });

        // AJAX Form Submission
        $('.ajax-form').on('submit', function(e) {
            e.preventDefault();
            
            let form = $(this);
            let url = form.attr('action');
            let method = form.attr('method');
            let data = new FormData(this);
            
            let btn = form.find('.btn-save');
            let normalState = btn.find('.normal-state');
            let loadingState = btn.find('.loading-state');
            
            btn.prop('disabled', true);
            normalState.addClass('d-none');
            loadingState.removeClass('d-none');
            
            fetch(url, {
                method: method,
                body: data,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if(!response.ok) {
                    throw new Error('Network error');
                }
                return response.json();
            })
            .then(data => {
                if(data.success) {
                    notify('success', data.message || '{{ __("Configurações salvas com sucesso!") }}');
                } else {
                    notify('error', data.message || '{{ __("Ocorreu um erro ao salvar.") }}');
                }
            })
            .catch(error => {
                notify('error', '{{ __("Ocorreu um erro de comunicação com o servidor.") }}');
            })
            .finally(() => {
                btn.prop('disabled', false);
                loadingState.addClass('d-none');
                normalState.removeClass('d-none');
            });
        });
    });
</script>
@endpush

