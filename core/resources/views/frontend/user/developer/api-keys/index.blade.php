@extends('frontend.user.developer.index')
@section('title', __('API Keys'))

@section('user_developer_content')

<div class="v2-page-header" style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h2 class="v2-page-title" style="font-size: 1.5rem; font-weight: 700; margin: 0 0 4px; color: var(--ds-text-main);">API Keys</h2>
        <p class="v2-page-subtitle" style="font-size: 0.875rem; color: var(--ds-text-muted); margin: 0;">Gerencie credenciais de integração da sua aplicação.</p>
    </div>
    <button type="button" class="v2-btn-primary" data-bs-toggle="modal" data-bs-target="#createApiKeyModal" style="padding: 6px 16px; height: 36px; font-size: 0.875rem;">
        <i class="fas fa-plus" style="margin-right: 6px;"></i> Nova Chave
    </button>
</div>

@if(session('new_secret'))
<div class="v2-settings-card" style="padding: 24px; margin-bottom: 24px; border: 1px solid rgba(16, 185, 129, 0.3); background: rgba(16, 185, 129, 0.05); border-radius: 16px;">
    <div style="display: flex; align-items: flex-start; gap: 16px;">
        <i class="fas fa-check-circle text-success" style="font-size: 1.5rem; margin-top: 4px;"></i>
        <div style="flex: 1;">
            <h3 style="margin: 0 0 8px; font-size: 1.125rem; font-weight: 600; color: var(--ds-text-main);">Chave gerada com sucesso!</h3>
            <p style="margin: 0 0 16px; color: var(--ds-text-muted); font-size: 0.875rem;">Por questões de segurança, esta é a única vez que você verá a Secret Key. Copie-a e guarde em um local seguro.</p>
            
            <div style="font-size: 0.75rem; font-weight: 600; margin-bottom: 6px; color: var(--ds-text-muted); text-transform: uppercase;">Secret Key</div>
            <div style="display: flex; align-items: center; gap: 8px; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.05); border-radius: 8px; padding: 6px 12px; transition: 200ms;">
                <input type="password" id="newSecretKeyInput" value="{{ session('new_secret') }}" readonly style="flex: 1; background: transparent; border: none; color: var(--ds-text-main); font-family: monospace; font-size: 0.875rem; outline: none;">
                <button type="button" class="v2-btn-tertiary" onclick="toggleSecretNew()" id="toggleSecretNewBtn" style="padding: 4px; height: 28px; width: 28px; display: flex; align-items: center; justify-content: center; transition: 200ms;">
                    <i class="fas fa-eye"></i>
                </button>
                <button type="button" class="v2-btn-tertiary" onclick="copySecretKey()" id="copyBtn" style="padding: 4px; height: 28px; width: 28px; display: flex; align-items: center; justify-content: center; transition: 200ms;">
                    <i class="fas fa-copy"></i>
                </button>
            </div>
        </div>
    </div>
</div>
<script>
    function toggleSecretNew() {
        var input = document.getElementById("newSecretKeyInput");
        var icon = document.querySelector("#toggleSecretNewBtn i");
        if (input.type === "password") {
            input.type = "text";
            icon.className = "fas fa-eye-slash";
        } else {
            input.type = "password";
            icon.className = "fas fa-eye";
        }
    }
    function copySecretKey() {
        var copyText = document.getElementById("newSecretKeyInput");
        var oldType = copyText.type;
        copyText.type = "text";
        copyText.select();
        copyText.setSelectionRange(0, 99999);
        document.execCommand("copy");
        copyText.type = oldType;
        
        var btn = document.getElementById("copyBtn");
        btn.innerHTML = '<i class="fas fa-check text-success"></i>';
        
        setTimeout(function() {
            btn.innerHTML = '<i class="fas fa-copy"></i>';
        }, 2000);
    }
</script>
@endif

@forelse($keys as $key)
<div class="v2-settings-card" style="padding: 0; margin-bottom: 8px; border-radius: 12px; overflow: hidden; transition: border-color 200ms;">

    {{-- Linha principal --}}
    <div style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-bottom: 1px solid rgba(255,255,255,0.04);">

        {{-- Ícone --}}
        <div style="width: 32px; height: 32px; border-radius: 8px; background: rgba(124,58,237,0.1); color: #a78bfa; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 0.8rem;">
            <i class="fas fa-key"></i>
        </div>

        {{-- Nome + Badges --}}
        <div style="min-width: 0; flex: 1;">
            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                <span style="font-weight: 600; font-size: 0.9rem; color: var(--ds-text-main); white-space: nowrap;">{{ $key->name }}</span>
                @if($key->environment === 'live')
                    <span class="v2-badge" style="background: rgba(16,185,129,0.1); color: #10b981; border: 1px solid rgba(16,185,129,0.2); font-size: 0.65rem;">Live</span>
                @else
                    <span class="v2-badge" style="background: rgba(245,158,11,0.1); color: #f59e0b; border: 1px solid rgba(245,158,11,0.2); font-size: 0.65rem;">Test</span>
                @endif
                @if($key->status)
                    <span class="v2-badge v2-badge-success" style="font-size: 0.65rem;">Ativa</span>
                @else
                    <span class="v2-badge v2-badge-error" style="font-size: 0.65rem;">Revogada</span>
                @endif
            </div>
        </div>

        {{-- Meta info compacta --}}
        <div style="display: flex; align-items: center; gap: 20px; flex-shrink: 0;">
            <div style="text-align: right;">
                <div style="font-size: 0.65rem; color: var(--ds-text-muted); text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 1px;">Criado</div>
                <div style="font-size: 0.8rem; color: var(--ds-text-secondary);">{{ $key->created_at->format('d/m/Y') }}</div>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 0.65rem; color: var(--ds-text-muted); text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 1px;">Último uso</div>
                <div style="font-size: 0.8rem; color: var(--ds-text-secondary);">{{ $key->last_used_at ? $key->last_used_at->diffForHumans() : '—' }}</div>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 0.65rem; color: var(--ds-text-muted); text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 1px;">ID</div>
                <div style="font-size: 0.8rem; color: var(--ds-text-secondary); font-family: monospace;">{{ $key->id }}</div>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 0.65rem; color: var(--ds-text-muted); text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 1px;">Tipo</div>
                <div style="font-size: 0.8rem; color: var(--ds-text-secondary);">Padrão</div>
            </div>
        </div>

        {{-- Ações --}}
        <div style="display: flex; gap: 4px; align-items: center; flex-shrink: 0; margin-left: 8px;">
            <a href="{{ route('user.developer.logs.index') }}?key={{ $key->id }}"
               class="v2-icon-btn" style="width: 30px; height: 30px; border-radius: 6px; text-decoration: none; background: transparent;"
               title="Ver Logs">
                <i class="fas fa-list" style="font-size: 0.7rem;"></i>
            </a>
            @if($key->status)
            <form action="{{ route('user.developer.api-keys.revoke', $key->id) }}" method="POST" style="margin: 0;">
                @csrf
                <button type="submit"
                    class="v2-icon-btn"
                    data-tp-confirm="true"
                    style="width: 30px; height: 30px; border-radius: 6px; color: var(--ds-error); border-color: rgba(239,68,68,0.2); background: transparent;"
                    title="Revogar">
                    <i class="fas fa-trash-alt" style="font-size: 0.7rem;"></i>
                </button>
            </form>
            @endif
        </div>
    </div>

    {{-- Linha da chave --}}
    <div style="display: flex; align-items: center; gap: 10px; padding: 8px 16px; background: rgba(0,0,0,0.1);">
        <span style="font-size: 0.65rem; font-weight: 700; color: var(--ds-text-muted); text-transform: uppercase; letter-spacing: 0.06em; flex-shrink: 0;">Public Key</span>
        <span style="font-family: 'JetBrains Mono', 'Fira Code', monospace; font-size: 0.8rem; color: var(--ds-text-secondary); flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $key->key_prefix }}</span>
        <button type="button"
            onclick="navigator.clipboard.writeText('{{ $key->key_prefix }}'); this.innerHTML='<i class=\'fas fa-check\' style=\'color: var(--ds-success);\'></i>'; setTimeout(() => this.innerHTML='<i class=\'fas fa-copy\'></i>', 2000);"
            style="background: none; border: none; color: var(--ds-text-muted); cursor: pointer; padding: 2px 6px; border-radius: 4px; transition: color 150ms; flex-shrink: 0;"
            title="Copiar">
            <i class="fas fa-copy" style="font-size: 0.75rem;"></i>
        </button>
    </div>

</div>
@empty
<div class="v2-empty-state" style="padding: 32px 24px; text-align: center; border: 1px dashed rgba(255,255,255,0.1); border-radius: 16px; margin-bottom: 24px;">
    <div style="width: 40px; height: 40px; background: rgba(124,58,237,.12); border-radius: 12px; color: #7C3AED; display: flex; align-items: center; justify-content: center; font-size: 1.125rem; margin: 0 auto 12px;">
        <i class="fas fa-key"></i>
    </div>
    <h3 style="margin: 0 0 8px; font-size: 1rem; font-weight: 600; color: var(--ds-text-main);">Nenhuma chave gerada</h3>
    <p style="margin: 0 0 16px; color: var(--ds-text-muted); font-size: 0.875rem; max-width: 300px; margin-left: auto; margin-right: auto;">Você ainda não possui chaves de API.</p>
    <button type="button" class="v2-btn-primary" data-bs-toggle="modal" data-bs-target="#createApiKeyModal" style="padding: 6px 16px; height: 36px; font-size: 0.875rem;">
        Criar Minha Primeira Chave
    </button>
</div>
@endforelse

<style>
    #createApiKeyModal .modal-footer .v2-btn-tertiary {
        border: 1px solid var(--ds-border-medium) !important;
        background: rgba(255,255,255,.025) !important;
        color: var(--ds-text-secondary) !important;
        min-height: 40px;
        border-radius: 9px;
        padding: 0 16px;
    }

    #createApiKeyModal .modal-footer .v2-btn-tertiary:hover {
        background: rgba(255,255,255,.055) !important;
        color: var(--ds-text-main) !important;
    }

    @media (max-width: 768px) {
        .v2-settings-panel .v2-page-header {
            display: grid !important;
            grid-template-columns: 1fr !important;
            gap: 12px !important;
            align-items: stretch !important;
            margin-bottom: 16px !important;
        }

        .v2-settings-panel .v2-page-header .v2-btn-primary,
        .v2-settings-panel .v2-empty-state .v2-btn-primary {
            width: 100% !important;
            min-height: 42px !important;
            height: auto !important;
            justify-content: center !important;
            border-radius: 8px !important;
        }

        .v2-settings-panel .v2-empty-state {
            padding: 28px 18px !important;
            border-radius: 12px !important;
            margin-bottom: 16px !important;
        }

        #createApiKeyModal .modal-dialog {
            width: calc(100vw - 24px) !important;
            max-width: 420px !important;
            margin: 12px auto !important;
        }

        #createApiKeyModal .modal-content {
            border-radius: 14px !important;
            max-height: calc(100dvh - 24px) !important;
            overflow: hidden !important;
        }

        #createApiKeyModal .modal-header {
            padding: 16px 18px !important;
        }

        #createApiKeyModal .modal-title {
            font-size: 1rem !important;
            line-height: 1.25 !important;
        }

        #createApiKeyModal .modal-body {
            padding: 18px !important;
            overflow-y: auto !important;
        }

        #createApiKeyModal .modal-body > div {
            margin-bottom: 16px !important;
        }

        #createApiKeyModal .v2-label {
            font-size: .8rem !important;
            margin-bottom: 7px !important;
        }

        #createApiKeyModal .v2-input {
            width: 100% !important;
            height: 42px !important;
            border-radius: 10px !important;
            font-size: .84rem !important;
        }

        #createApiKeyModal label.d-flex {
            align-items: flex-start !important;
            gap: 10px !important;
            padding: 13px !important;
            border-radius: 10px !important;
            margin-bottom: 10px !important;
        }

        #createApiKeyModal label.d-flex .form-check-input {
            margin: 3px 0 0 !important;
            flex: 0 0 16px !important;
            width: 16px !important;
            height: 16px !important;
        }

        #createApiKeyModal label.d-flex > div {
            min-width: 0 !important;
            flex: 1 !important;
        }

        #createApiKeyModal .modal-footer {
            display: grid !important;
            grid-template-columns: 1fr !important;
            gap: 10px !important;
            padding: 16px 18px 18px !important;
        }

        #createApiKeyModal .modal-footer button {
            width: 100% !important;
            height: 42px !important;
            justify-content: center !important;
            border-radius: 9px !important;
        }
    }
</style>


{{-- Create API Key Modal --}}
<div class="modal fade" id="createApiKeyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('user.developer.api-keys.store') }}" method="POST" class="modal-content" style="border-radius: 16px; border: 1px solid var(--ds-border-light); background: var(--ds-bg-card);">
            @csrf
            <div class="modal-header" style="border-bottom: 1px solid var(--ds-border-light); padding: 24px;">
                <h5 class="modal-title fw-bold" style="color: var(--ds-text-main); font-size: 1.125rem;">Gerar Nova Chave de API</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="padding: 24px;">
                
                <div style="margin-bottom: 24px;">
                    <label for="name" class="v2-label" style="color: var(--ds-text-main); font-size: 0.875rem; margin-bottom: 8px;">Nome da Chave</label>
                    <input type="text" class="v2-input" id="name" name="name" placeholder="Ex: Sistema PDV Matriz, Loja Virtual" required style="padding: 0 16px; height: 44px; border-radius: 12px; border: 1px solid var(--ds-border-medium); background: rgba(255,255,255,0.03); color: white; transition: 200ms;">
                    <div class="form-text" style="color: var(--ds-text-muted); font-size: 0.75rem; margin-top: 8px;">Um nome amigável para identificar esta chave.</div>
                </div>

                <div style="margin-bottom: 24px;">
                    <label class="v2-label" style="color: var(--ds-text-main); font-size: 0.875rem; margin-bottom: 12px;">Ambiente</label>
                    
                    <label class="d-flex align-items-center" style="border: 1px solid var(--ds-border-medium); border-radius: 12px; padding: 16px; cursor: pointer; transition: all 200ms; background: rgba(255,255,255,0.02); margin-bottom: 12px;" onmouseover="this.style.background='rgba(255,255,255,0.04)'" onmouseout="this.style.background='rgba(255,255,255,0.02)'">
                        <input type="radio" name="environment" value="test" class="form-check-input me-3" checked style="width: 18px; height: 18px; margin-top: 0; cursor: pointer;">
                        <div>
                            <div style="font-weight: 600; color: var(--ds-text-main); font-size: 0.875rem;">Sandbox (Testes)</div>
                            <div style="font-size: 0.8125rem; color: var(--ds-text-muted); margin-top: 4px;">Gera chaves pk_test e sk_test. O saldo não é real.</div>
                        </div>
                    </label>

                    <label class="d-flex align-items-center" style="border: 1px solid var(--ds-border-medium); border-radius: 12px; padding: 16px; cursor: pointer; transition: all 200ms; background: rgba(255,255,255,0.02);" onmouseover="this.style.background='rgba(255,255,255,0.04)'" onmouseout="this.style.background='rgba(255,255,255,0.02)'">
                        <input type="radio" name="environment" value="live" class="form-check-input me-3" style="width: 18px; height: 18px; margin-top: 0; cursor: pointer;">
                        <div>
                            <div style="font-weight: 600; color: var(--ds-text-main); font-size: 0.875rem;">Produção (Live)</div>
                            <div style="font-size: 0.8125rem; color: var(--ds-text-muted); margin-top: 4px;">Gera chaves pk_live e sk_live. Movimenta dinheiro real.</div>
                        </div>
                    </label>
                </div>

            </div>
            <div class="modal-footer" style="border-top: 1px solid var(--ds-border-light); padding: 24px; gap: 12px;">
                <button type="button" class="v2-btn-tertiary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="v2-btn-primary" data-tp-confirm="true">Gerar Chaves</button>
            </div>
        </form>
    </div>
</div>

@endsection
