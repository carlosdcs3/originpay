@php use App\Enums\MethodType; @endphp
@extends('general.merchant.index')
@section('favicon', asset($data['business_logo']))
@section('title', __('Payment Checkout'))
@section('merchant_content')
    <div class="gateway-section">
        {{-- Compact & Modern Checkout Header --}}
        <div class="checkout-header d-flex align-items-center justify-content-between mb-4 px-4 py-3">
            {{-- Merchant Info --}}
            <div class="d-flex align-items-center gap-3">
                <div class="d-flex align-items-center">
                    <img src="{{ asset($data['business_logo'] ?? 'default-logo.png') }}"
                         alt="{{ $data['business_name'] ?? 'Business' }} Logo"
                         class="merchant-logo">
                </div>
                <div>
                    <h6 class="mb-0">
                        {{ $data['business_name'] }}
                        @if($data['is_sandbox'] ?? false)
                            <small class="badge bg-warning ms-2">@lang('TEST MODE')</small>
                        @endif
                    </h6>
                    <small class="text-muted merchant-description">{{ $data['description'] }}</small>
                </div>
            </div>
            {{-- Payment Amount --}}
            <div class="text-end">
                <small class="text-muted d-block">@lang('Total Amount')</small>
                <h4 class="mb-0 fw-bold">{{ $data['payment_amount'] }}</h4>
            </div>
        </div>

        {{-- Test Payment Information (Sandbox Only) --}}
        @if($data['is_sandbox'] ?? false)
            <div class="alert border-0 mb-4 test-payment-info py-2 px-3">
                <details>
                    <summary class="text-primary fw-bold" style="cursor:pointer; outline: none;">
                        <i class="fas fa-info-circle me-2"></i>@lang('Test Payment Info')
                    </summary>
                    <div class="row g-2 mt-2">
                        <div class="col-md-4">
                            <div class="bg-white rounded p-2 border">
                                <strong class="text-primary small">@lang('Wallet ID:')</strong>
                                <code class="d-block bg-light px-2 py-1 rounded mt-1">123456789</code>
                                <small class="text-muted">@lang('Password:'): <code>demo123</code></small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="bg-white rounded p-2 border">
                                <strong class="text-success small">@lang('Voucher Code:')</strong>
                                <code class="d-block bg-light px-2 py-1 rounded mt-1">TESTVOUCHER</code>
                                <small class="text-muted">@lang('For testing only')</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="bg-white rounded p-2 border">
                                <strong class="text-info small">@lang('Gateway:')</strong>
                                <code class="d-block bg-light px-2 py-1 rounded mt-1">@lang('Auto Success')</code>
                                <small class="text-muted">@lang('Instant completion')</small>
                            </div>
                        </div>
                    </div>
                </details>
            </div>
        @endif

        {{-- Payment Methods Grid --}}
        <section aria-label="@lang('Payment Methods')">
            <h6 class="text-uppercase text-muted mb-3">
                @lang('Payment Methods')
            </h6>

            <form id="paymentForm" action="{{ route('payment.process') }}" method="post">
                @csrf
                <input type="hidden" name="selected_method" id="selectedMethod">
                <input type="hidden" name="trx_id" value="{{ $trxId }}">
                <div class="row g-3">
                    <div class="col-6 col-md-3">
                        <div class="payment-logo-card"
                             role="button"
                             tabindex="0"
                             aria-pressed="false"
                             data-method="{{ MethodType::SYSTEM->value }}">
                            <img src="{{ asset(setting('logo')) }}" class="payment-logo"
                                 alt="{{ 'OriginPay' }} Logo">
                            <p class="payment-name">{{ 'OriginPay' }}</p>
                        </div>
                    </div>

                    @foreach($paymentMethods as $method)
                        <div class="col-6 col-md-3">
                            <div class="payment-logo-card"
                                 role="button"
                                 tabindex="0"
                                 aria-pressed="false"
                                 data-method="{{ $method->method_code }}">
                                <img src="{{ asset($method->logo_alt) }}" class="payment-logo"
                                     alt="{{ $method->name }} Logo" {{ $loop->iteration > 4 ? 'loading="lazy"' : '' }}>
                                <p class="payment-name">{{ $method->name }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Payment Action --}}
                <div class="d-grid mt-5">
                    <button id="payButton" type="submit" class="btn btn-primary btn-lg fw-bold" disabled>
                        <i class="fas fa-arrow-right me-2"></i>
                        @lang('Complete Payment :amount', ['amount' => $data['payment_amount']])
                    </button>
                </div>
            </form>
        </section>

        {{-- Security Footer --}}
        <div class="text-center mt-4 small text-muted">
            <i class="fas fa-lock me-2"></i>
            @lang('256-bit SSL Secured Payment • PCI DSS Compliant')
        </div>
    </div>
@endsection
@push('scripts')
    <script>
        "use strict";
        
        document.addEventListener('DOMContentLoaded', function() {
            const isSandbox = {{ ($data['is_sandbox'] ?? false) ? 'true' : 'false' }};
            
            // Add sandbox logging
            if (isSandbox) {
                console.log('%c🧪 SANDBOX MODE ACTIVE', 'color: #007bff; font-weight: bold; font-size: 14px;');
                console.log('Transaction ID:', '{{ $data['sandbox_transaction_id'] ?? $trxId }}');
                console.log('Environment:', '{{ $data['environment'] ?? 'sandbox' }}');
            }
            
            // Payment method selection logic
            const paymentCards = document.querySelectorAll('.payment-logo-card');
            const payButton = document.getElementById('payButton');
            const selectedMethodInput = document.getElementById('selectedMethod');
            const paymentForm = document.getElementById('paymentForm');
            
            paymentCards.forEach(card => {
                card.addEventListener('click', function() {
                    // Remove active class from all cards
                    paymentCards.forEach(c => {
                        c.classList.remove('selected');
                        c.setAttribute('aria-pressed', 'false');
                    });
                    
                    // Add active class to clicked card
                    this.classList.add('selected');
                    this.setAttribute('aria-pressed', 'true');
                    
                    // Set selected method and enable button
                    const selectedMethod = this.dataset.method;
                    selectedMethodInput.value = selectedMethod;
                    payButton.disabled = false;
                    
                    // Sandbox auto-payment for gateway methods
                    if (isSandbox && selectedMethod !== '{{ MethodType::SYSTEM->value }}') {
                        console.log('🧪 Auto-completing sandbox gateway payment:', selectedMethod);
                        
                        // Show processing state
                        payButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>@lang("Auto Processing...")';
                        payButton.disabled = true;
                        
                        // Auto-submit after short delay for better UX
                        setTimeout(() => {
                            paymentForm.submit();
                        }, 1000);
                    }
                    
                    // Sandbox feedback
                    if (isSandbox) {
                        console.log('🧪 Payment method selected:', selectedMethod);
                    }
                });
                
                // Keyboard accessibility
                card.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        this.click();
                    }
                });
            });
            
            // Form submission handling
            paymentForm.addEventListener('submit', function(e) {
                if (isSandbox) {
                    console.log('🧪 Submitting sandbox payment form');
                }
                
                // Disable button to prevent double submission
                if (!payButton.innerHTML.includes('Auto Processing')) {
                    payButton.disabled = true;
                    payButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>@lang("Processing...")';
                }
            });
        });
    </script>
@endpush
