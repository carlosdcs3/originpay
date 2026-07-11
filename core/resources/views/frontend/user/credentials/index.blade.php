@extends('frontend.layouts.user-v2')
@section('title', 'Credenciais de Integração')

@push('styles')
<style>
.credentials-card {
    background: #181b26;
    border: 1px solid rgba(0, 212, 170, 0.2);
    border-radius: 20px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.4);
    overflow: hidden;
}
.credentials-header {
    background: linear-gradient(135deg, rgba(0, 212, 170, 0.1) 0%, rgba(24, 27, 38, 1) 100%);
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    padding: 30px;
}
.credentials-header h5 {
    color: #fff;
    font-weight: 700;
    margin-bottom: 8px;
    font-size: 1.25rem;
}
.credentials-header p {
    color: #94a3b8;
    margin-bottom: 0;
}
.credentials-body {
    padding: 30px;
}
.key-group {
    background: #12141c;
    border: 1px solid rgba(255, 255, 255, 0.05);
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 24px;
    transition: all 0.3s ease;
}
.key-group:hover {
    border-color: rgba(0, 212, 170, 0.4);
    box-shadow: 0 0 15px rgba(0, 212, 170, 0.1);
}
.key-label {
    color: #e2e8f0;
    font-weight: 600;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.key-input-container {
    position: relative;
    display: flex;
}
.key-input {
    background: #0a0b10 !important;
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
    border-right: none !important;
    color: #00D4AA !important;
    font-family: 'Courier New', Courier, monospace !important;
    font-weight: 600;
    letter-spacing: 2px;
    padding: 16px 20px !important;
    border-radius: 12px 0 0 12px !important;
}
.key-input:focus {
    box-shadow: none !important;
    border-color: rgba(0, 212, 170, 0.5) !important;
}
.key-btn {
    background: #0a0b10 !important;
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
    color: #94a3b8 !important;
    padding: 0 20px !important;
    transition: all 0.3s ease !important;
    display: flex;
    align-items: center;
    justify-content: center;
}
.key-btn:hover {
    color: #00D4AA !important;
    background: rgba(0, 212, 170, 0.1) !important;
    border-color: rgba(0, 212, 170, 0.5) !important;
}
.btn-eye {
    border-left: none !important;
    border-right: 1px solid rgba(255, 255, 255, 0.05) !important;
}
.btn-copy {
    border-radius: 0 12px 12px 0 !important;
    border-left: none !important;
}
.security-notice {
    background: rgba(245, 158, 11, 0.1);
    border-left: 4px solid #f59e0b;
    padding: 18px 24px;
    border-radius: 12px;
    display: flex;
    gap: 16px;
    align-items: flex-start;
}
.security-notice i {
    color: #f59e0b;
    font-size: 1.4rem;
    margin-top: 2px;
}
.security-notice p {
    color: #cbd5e1;
    margin: 0;
    font-size: 0.95rem;
    line-height: 1.6;
}
.security-notice strong {
    color: #fff;
    display: block;
    margin-bottom: 6px;
    font-size: 1.05rem;
}
</style>
@endpush

@section('content')
    <div class="row justify-content-center mt-2">
        <div class="col-xl-8 col-lg-10">
            
            <div class="security-notice mb-4 d-flex align-items-start">
                <i class="fas fa-shield-alt mt-1 me-3" style="font-size: 1.4rem; color: #f59e0b; flex-shrink: 0;"></i>
                <div>
                    <strong>Aviso de Segurança</strong>
                    <p>Suas chaves de API garantem acesso à sua carteira para criação de cobranças. Nunca as compartilhe publicamente, não envie em chats e não as coloque em repositórios de código aberto (como o GitHub).</p>
                </div>
            </div>

            <div class="credentials-card">
                <div class="credentials-header">
                    <h5 class="d-flex align-items-center"><i class="fas fa-code me-2" style="color: #00D4AA; font-size: 1.2rem;"></i> Credenciais de Integração</h5>
                    <p>Utilize as chaves abaixo para conectar o seu sistema, loja ou bot à nossa API.</p>
                </div>

                <div class="credentials-body">
                    {{-- API Key --}}
                    <div class="key-group">
                        <div class="key-label">
                            <i class="fas fa-key text-muted me-2"></i> Chave de API Pública (API Key)
                        </div>
                        <div class="input-group key-input-container">
                            <input type="text" class="v2-input key-input" id="apiKey" data-key="{{ $merchant->getCurrentApiKey() }}" data-hidden="true" value="••••••••••••••••••••••••••••••••" readonly>
                            <button class="btn key-btn btn-eye" type="button" onclick="toggleKeyVisibility('apiKey', this)" title="Revelar Chave">
                                <i class="fas fa-eye-slash"></i>
                            </button>
                            <button class="btn key-btn btn-copy copy-btn" type="button" data-clipboard-target="apiKey" title="Copiar">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                        <small class="text-muted mt-2 d-block"><i class="fas fa-info-circle me-2"></i> Usada como token de autorização nas requisições HTTP.</small>
                    </div>

                    {{-- Merchant Key --}}
                    <div class="key-group mb-0">
                        <div class="key-label">
                            <i class="fas fa-id-badge text-muted me-2"></i> Identificador da Conta (Merchant Key)
                        </div>
                        <div class="input-group key-input-container">
                            <input type="text" class="v2-input key-input" id="merchantKey" data-key="{{ $merchant->getCurrentMerchantKey() }}" data-hidden="true" value="••••••••••••••••••••••••••••••••" readonly>
                            <button class="btn key-btn btn-eye" type="button" onclick="toggleKeyVisibility('merchantKey', this)" title="Revelar Chave">
                                <i class="fas fa-eye-slash"></i>
                            </button>
                            <button class="btn key-btn btn-copy copy-btn" type="button" data-clipboard-target="merchantKey" title="Copiar">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                        <small class="text-muted mt-2 d-block"><i class="fas fa-info-circle me-2"></i> Identifica a sua carteira unicamente de forma global.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Função global para alternar visibilidade (100% imune a conflitos)
        function toggleKeyVisibility(targetId, btn) {
            var input = document.getElementById(targetId);
            var icon = btn.querySelector('i');
            
            if (input && icon) {
                var isHidden = input.getAttribute('data-hidden') === 'true';
                var realKey = input.getAttribute('data-key');
                
                if (isHidden) {
                    input.value = realKey;
                    input.setAttribute('data-hidden', 'false');
                    icon.className = 'fas fa-eye';
                } else {
                    input.value = '••••••••••••••••••••••••••••••••';
                    input.setAttribute('data-hidden', 'true');
                    icon.className = 'fas fa-eye-slash';
                }
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Function to copy text
            document.querySelectorAll('.copy-btn').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    var targetId = this.getAttribute('data-clipboard-target');
                    var input = document.getElementById(targetId);
                    
                    if(input) {
                        var realKey = input.getAttribute('data-key');
                        
                        navigator.clipboard.writeText(realKey).then(function() {
                            var icon = btn.querySelector('i');
                            var originalClass = icon.className;
                            icon.className = 'fas fa-check text-success';
                            setTimeout(function() {
                                icon.className = originalClass;
                            }, 2000);
                        }).catch(function(err) {
                            var tempInput = document.createElement('input');
                            tempInput.value = realKey;
                            document.body.appendChild(tempInput);
                            tempInput.select();
                            document.execCommand('copy');
                            document.body.removeChild(tempInput);
                            
                            var icon = btn.querySelector('i');
                            var originalClass = icon.className;
                            icon.className = 'fas fa-check text-success';
                            setTimeout(function() {
                                icon.className = originalClass;
                            }, 2000);
                        });
                    }
                });
            });
        });
    </script>
@endpush
