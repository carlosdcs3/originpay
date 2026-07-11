<style>
.manual-deposit-card {
    background: rgba(0, 212, 170, 0.02);
    border: 1px dashed rgba(0, 212, 170, 0.2);
    border-radius: 12px;
    padding: 24px;
    margin-top: 15px;
}
.manual-deposit-card h6 {
    color: #00D4AA;
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 12px;
    margin-top: 0;
}
.manual-deposit-card .payment-details {
    color: #94a3b8;
    font-size: 0.95rem;
    margin-bottom: 24px;
    background: rgba(255, 255, 255, 0.02);
    padding: 16px;
    border-radius: 8px;
    border: 1px solid rgba(255,255,255,0.05);
}
.manual-deposit-card .form-label {
    color: #e2e8f0;
    font-weight: 500;
    font-size: 0.9rem;
    margin-bottom: 8px;
}
.manual-deposit-card .form-control {
    background: #181b26;
    border: 1px solid rgba(255, 255, 255, 0.1);
    color: #fff;
    padding: 12px 16px;
    border-radius: 8px;
}
.manual-deposit-card .form-control:focus {
    border-color: #00D4AA;
    box-shadow: none;
}
.manual-deposit-card .form-control::placeholder {
    color: #475569;
}
</style>

@if(!empty($method->receive_payment_details) || (!empty($method->fields) && is_array($method->fields) && count($method->fields) > 0))
<div class="manual-deposit-card">
    @if(!empty($method->receive_payment_details))
        <h6><i class="fas fa-info-circle"></i> {{ __('Instruções de Pagamento') }}</h6>
        <div class="payment-details">
            {!! $method->receive_payment_details !!}
        </div>
    @endif

    @if(!empty($method->fields) && is_array($method->fields) && count($method->fields) > 0)
        <h6><i class="fas fa-file-invoice"></i> {{ __('Comprovante e Dados do Depósito') }}</h6>
        <div class="row g-4 mt-1">
            @foreach($method->fields as $field)
                <div class="col-12">
                    <label for="{{ 'credentials['.$field['name'].']' }}" class="v2-label">
                        {{ ucfirst(str_replace('_', ' ', $field['name'])) }}
                    </label>
                    @if($field['type'] === 'file')
                        <input type="file"
                               id="{{ 'credentials['.$field['name'].']' }}"
                               name="{{ 'credentials['.$field['name'].']' }}"
                               class="v2-input"
                               {{ $field['validation'] ? 'required' : '' }}
                        />
                    @elseif($field['type'] === 'textarea')
                        <textarea id="{{ 'credentials['.$field['name'].']' }}"
                               name="{{ 'credentials['.$field['name'].']' }}"
                               class="v2-input"
                               rows="3"
                               placeholder="{{ ucfirst(str_replace('_', ' ', $field['name'])) }}"
                               {{ $field['validation'] ? 'required' : '' }}></textarea>
                    @else
                        <input type="{{ $field['type'] }}"
                               id="{{ 'credentials['.$field['name'].']' }}"
                               name="{{ 'credentials['.$field['name'].']' }}"
                               class="v2-input"
                               placeholder="{{ ucfirst(str_replace('_', ' ', $field['name'])) }}"
                               {{ $field['validation'] ? 'required' : '' }}
                        />
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
@endif