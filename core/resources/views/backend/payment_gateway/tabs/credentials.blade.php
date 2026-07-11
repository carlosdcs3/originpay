<div class="card border-0 shadow-sm">
    <div class="card-header bg-white pt-4 pb-3 border-bottom-0 d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0"><i class="fa-solid fa-key me-2 text-muted"></i> {{ __('Configuração de Credenciais') }}</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.payment.gateway.update-credentials', $gateway->id) }}" method="POST" enctype="multipart/form-data" class="ajax-form">
            @csrf
            
            <div class="bg-light rounded p-3 mb-4 border">
                <div class="row align-items-center">
                    <div class="col-md-6 border-end">
                        <div class="d-flex align-items-center">
                            <div class="form-check form-switch fs-5 mb-0">
                                <input class="form-check-input" type="checkbox" name="status" id="gateway_status" value="1" @checked($gateway->status)>
                                <label class="form-check-label fs-6 mt-1 ms-2" for="gateway_status">{{ __('Gateway Ativo') }}</label>
                            </div>
                        </div>
                        <small class="text-muted ms-5">{{ __('Permite transações reais.') }}</small>
                    </div>
                    <div class="col-md-6 ps-4">
                        <div class="d-flex align-items-center">
                            <div class="form-check form-switch fs-5 mb-0">
                                <input class="form-check-input sandbox-toggle" type="checkbox" name="is_sandbox" id="is_sandbox" value="1" @checked($gateway->is_sandbox)>
                                <label class="form-check-label fs-6 mt-1 ms-2 text-warning" for="is_sandbox">{{ __('Modo Sandbox') }}</label>
                            </div>
                        </div>
                        <small class="text-muted ms-5">{{ __('Redireciona para ambiente de teste.') }}</small>
                    </div>
                </div>
            </div>

            @php
                $isCustomOrLegacy = (!$definition || empty($definition->credentials) || !is_array(reset($definition->credentials)));
            @endphp

            @if($isCustomOrLegacy)
                <!-- LEGACY / CUSTOM PROVIDER BUILDER -->
                <h6 class="mb-3 text-muted text-uppercase small fw-bold">{{ __('Parâmetros de Integração (Modo Legado/Manual)') }}</h6>
                <div id="credentials-container">
                    @if(is_array($gateway->credentials))
                        @foreach($gateway->credentials as $key => $value)
                            <div class="row mb-3 credential-row align-items-start">
                                <div class="col-md-4">
                                    <label class="form-label text-muted small fw-bold mb-1">{{ strtoupper(str_replace('_', ' ', $key)) }}</label>
                                    <input type="text" class="form-control bg-light" name="credential_keys[]" value="{{ $key }}" readonly>
                                </div>
                                <div class="col-md-7">
                                    <label class="form-label text-muted small fw-bold mb-1">{{ __('VALOR (SECRET)') }}</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control secret-input" name="credential_values[]" placeholder="{{ $value ? '••••••••••••••••••••' : 'Insira a chave secreta' }}">
                                        <button class="btn btn-outline-secondary toggle-secret" type="button" tabindex="-1"><i class="fa-regular fa-eye"></i></button>
                                        <button class="btn btn-outline-secondary copy-secret" type="button" data-val="{{ $value }}" tabindex="-1"><i class="fa-regular fa-copy"></i></button>
                                    </div>
                                    <small class="text-muted">{{ __('Deixe em branco para manter o valor atual no banco.') }}</small>
                                </div>
                                <div class="col-md-1 pt-4 mt-1 text-end">
                                    <button type="button" class="btn btn-outline-danger remove-credential" tabindex="-1"><i class="fa fa-times"></i></button>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
                
                <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="add-credential-btn">
                    <i class="fa fa-plus me-1"></i> {{ __('Adicionar Parâmetro Customizado') }}
                </button>
                
                <!-- Template para nova credencial (invisível) -->
                <template id="credential-template">
                    <div class="row mb-3 credential-row align-items-start">
                        <div class="col-md-4">
                            <label class="form-label text-muted small fw-bold mb-1">CHAVE</label>
                            <input type="text" class="form-control" name="credential_keys[]" placeholder="Ex: api_key, webhook_secret" required>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label text-muted small fw-bold mb-1">VALOR</label>
                            <div class="input-group">
                                <input type="password" class="form-control secret-input" name="credential_values[]" placeholder="Insira o valor" required>
                                <button class="btn btn-outline-secondary toggle-secret" type="button" tabindex="-1"><i class="fa-regular fa-eye"></i></button>
                            </div>
                        </div>
                        <div class="col-md-1 pt-4 mt-1 text-end">
                            <button type="button" class="btn btn-outline-danger remove-credential" tabindex="-1"><i class="fa fa-times"></i></button>
                        </div>
                    </div>
                </template>
            @else
                <!-- GATEWAY SCHEMA ENGINE -->
                @php
                    // Agrupar os metadados pelas chaves 'group' e ordenar
                    $groups = [];
                    foreach($definition->credentials as $key => $schema) {
                        $groupName = $schema['group'] ?? 'Geral';
                        $groups[$groupName][$key] = $schema;
                    }
                    
                    // Ordenar dentro de cada grupo
                    foreach($groups as $groupName => &$fields) {
                        uasort($fields, function($a, $b) {
                            return ($a['order'] ?? 99) <=> ($b['order'] ?? 99);
                        });
                    }
                @endphp

                @foreach($groups as $groupName => $fields)
                    <div class="schema-group mb-4">
                        <h6 class="mb-3 text-muted text-uppercase small fw-bold border-bottom pb-2">{{ $groupName }}</h6>
                        
                        <div class="row">
                            @foreach($fields as $key => $schema)
                                @php
                                    $value = is_array($gateway->credentials) ? ($gateway->credentials[$key] ?? '') : '';
                                    $dependsAttr = '';
                                    if (!empty($schema['depends_on'])) {
                                        $dependsAttr = 'data-depends-on="' . $schema['depends_on'] . '"';
                                    }
                                    
                                    // Helper class to handle conditional visibility in JS
                                    $wrapperClass = !empty($schema['depends_on']) ? 'schema-conditional-field' : '';
                                @endphp
                                
                                <div class="col-md-12 mb-3 {{ $wrapperClass }}" {!! $dependsAttr !!}>
                                    <label class="form-label fw-bold">
                                        @if(!empty($schema['icon']))
                                            <i class="fa-solid {{ $schema['icon'] }} me-1 text-muted"></i>
                                        @endif
                                        {{ $schema['label'] }}
                                        @if(!empty($schema['required']))
                                            <span class="text-danger">*</span>
                                        @endif
                                    </label>
                                    
                                    @if($schema['input'] === 'file')
                                        <div class="p-3 border rounded bg-light">
                                            @if(!empty($value))
                                                <div class="d-flex align-items-center mb-2">
                                                    <i class="fa-solid fa-file-shield text-success fs-4 me-2"></i>
                                                    <div>
                                                        <span class="d-block fw-bold text-success"><i class="fa-solid fa-check"></i> Arquivo enviado com segurança</span>
                                                        <small class="text-muted text-break">{{ basename($value) }}</small>
                                                    </div>
                                                </div>
                                                <div class="form-text mb-2">Para substituir, selecione um novo arquivo abaixo:</div>
                                            @endif
                                            <input type="file" class="form-control" name="credentials[{{ $key }}]" accept="{{ $schema['accept'] ?? '*/*' }}">
                                        </div>
                                    @elseif($schema['input'] === 'select')
                                        <select class="form-select" name="credentials[{{ $key }}]" @if(!empty($schema['readonly'])) readonly @endif>
                                            <option value="">Selecione...</option>
                                            @if(!empty($schema['options']))
                                                @foreach($schema['options'] as $optValue => $optLabel)
                                                    <option value="{{ $optValue }}" @selected($value == $optValue)>{{ $optLabel }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    @elseif($schema['input'] === 'textarea')
                                        <textarea class="form-control" name="credentials[{{ $key }}]" rows="3" placeholder="{{ $schema['placeholder'] ?? '' }}" @if(!empty($schema['readonly'])) readonly @endif>{{ $value }}</textarea>
                                    @else
                                        <div class="input-group">
                                            <input type="{{ $schema['input'] }}" class="form-control @if(!empty($schema['masked'])) secret-input @endif" name="credentials[{{ $key }}]" placeholder="{{ !empty($schema['masked']) && !empty($value) ? '••••••••••••••••••••' : ($schema['placeholder'] ?? '') }}" @if(!empty($schema['readonly'])) value="{{ $value }}" readonly @elseif(empty($schema['masked'])) value="{{ $value }}" @endif>
                                            
                                            @if(!empty($schema['masked']))
                                                <button class="btn btn-outline-secondary toggle-secret" type="button" tabindex="-1"><i class="fa-regular fa-eye"></i></button>
                                            @endif
                                            
                                            @if(!empty($schema['copyable']))
                                                <button class="btn btn-outline-secondary copy-secret" type="button" data-val="{{ $value }}" tabindex="-1"><i class="fa-regular fa-copy"></i></button>
                                            @endif
                                        </div>
                                        @if(!empty($schema['masked']))
                                            <small class="text-muted d-block mt-1">{{ __('Deixe em branco para manter o valor atual seguro no banco.') }}</small>
                                        @endif
                                    @endif

                                    @if(!empty($schema['description']))
                                        <div class="form-text mt-1 text-secondary">
                                            <i class="fa-solid fa-circle-info me-1"></i> {!! $schema['description'] !!}
                                        </div>
                                    @endif
                                    
                                    @if(!empty($schema['documentation_url']))
                                        <a href="{{ $schema['documentation_url'] }}" target="_blank" class="small text-primary mt-1 d-inline-block">
                                            <i class="fa-solid fa-book-open me-1"></i> {{ $schema['documentation_title'] ?? 'Ver documentação' }}
                                        </a>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            @endif
            
            <div class="mt-4 border-top pt-4 text-end">
                <button type="submit" class="btn btn-primary px-4 btn-save">
                    <span class="normal-state"><i class="fa-solid fa-save me-2"></i> {{ __('Salvar Credenciais') }}</span>
                    <span class="loading-state d-none"><span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Salvando...</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle depends_on logic (Schema Engine)
        const sandboxToggle = document.querySelector('.sandbox-toggle');
        const conditionalFields = document.querySelectorAll('.schema-conditional-field');
        
        function evaluateConditions() {
            if (!sandboxToggle) return;
            
            const isSandbox = sandboxToggle.checked;
            
            conditionalFields.forEach(field => {
                const condition = field.getAttribute('data-depends-on');
                if (!condition) return;
                
                // Extremely simple condition evaluator based on instructions.
                // If depends_on == 'is_sandbox == true', we parse it.
                // To keep it light, we just do string match.
                let show = true;
                if (condition.includes('is_sandbox == true') || condition === 'is_sandbox') {
                    show = isSandbox;
                } else if (condition.includes('is_sandbox == false') || condition.includes('!is_sandbox')) {
                    show = !isSandbox;
                }
                
                if (show) {
                    field.style.display = 'block';
                } else {
                    field.style.display = 'none';
                }
            });
        }
        
        if (sandboxToggle) {
            sandboxToggle.addEventListener('change', evaluateConditions);
            evaluateConditions(); // Run on load
        }
    });
</script>
