
<div id="ds-hc-fab" role="button" tabindex="0" onclick="dsChatUI.toggleWidget()" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();dsChatUI.toggleWidget();}" title="Central de Ajuda" aria-label="Abrir central de ajuda">
    <i class="fas fa-headset" id="ds-hc-fab-icon" aria-hidden="true"></i>
    <span class="ds-hc-badge" id="ds-hc-badge" style="display:none;">0</span>
</div>

<div id="ds-hc-widget" style="display:none;">
    
        <div class="ds-hc-header">
        <div class="ds-hc-header-top">
            <div class="ds-hc-header-info">
                <div class="ds-hc-avatar">
                    <img src="{{ asset('frontend/images/originpay/originpay-icon-transparent.svg') }}" alt="Logo">
                </div>
                <div>
                    <div class="ds-hc-title">Assistente OriginPay</div>
                    <div class="ds-hc-status">
                        <span class="ds-hc-status-dot online"></span>
                        Geralmente responde em minutos
                    </div>
                </div>
            </div>
            <button type="button" onclick="dsChatUI.toggleWidget()" class="ds-hc-close-btn" title="Fechar" aria-label="Fechar suporte">
                <i class="fas fa-chevron-down" aria-hidden="true"></i>
            </button>
        </div>
    </div>

        <div class="ds-hc-main">
                <div class="ds-hc-views">
            
                        <div id="ds-view-home" class="ds-hc-view active">
                <div class="ds-hc-hero">
                    <h2>Olá, {{ strtok(auth()->user()->name ?? auth()->user()->first_name ?? 'Usuário', ' ') }}</h2>
                    <p>Como podemos ajudar hoje?</p>
                    <button type="button" class="ds-hc-btn-primary" onclick="dsChatUI.navigate('chat')" aria-label="Falar com o assistente OriginPay">
                        <i class="fas fa-paper-plane" aria-hidden="true"></i> Falar com Assistente
                    </button>
                </div>
                
                <div class="ds-hc-section">
                    <div class="ds-hc-search-box">
                        <i class="fas fa-search" aria-hidden="true"></i>
                        <label for="ds-hc-search-input" class="visually-hidden">Pesquisar documentação e APIs</label>
                        <input type="text" id="ds-hc-search-input" name="support_search" placeholder="Pesquisar documentação, APIs..." autocomplete="off" oninput="dsChatUI.mockSearch(this.value)">
                    </div>
                    <div id="ds-hc-search-results" style="display:none;" class="ds-hc-search-results"></div>
                </div>

                <div class="ds-hc-section" id="ds-hc-conversations-section" style="display: none;">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="ds-hc-section-title mb-0">Minhas conversas</h3>
                    </div>
                    <div id="ds-hc-conversations-list" class="ds-hc-conversations-list">
                        <!-- Rendered by JS -->
                    </div>
                </div>

                <div class="ds-hc-section">
                    <h3 class="ds-hc-section-title">Atalhos Inteligentes</h3>
                    <div class="ds-hc-grid">
                        <div class="ds-hc-card-action" onclick="dsChatUI.triggerShortcut('Como configurar Webhook?', true)">
                            <i class="fas fa-satellite-dish text-primary"></i>
                            <div class="ds-hc-card-title">Configurar Webhook</div>
                            <div class="ds-hc-card-desc">Receba eventos em tempo real da API.</div>
                        </div>
                        <div class="ds-hc-card-action" onclick="dsChatUI.triggerShortcut('Como criar uma cobrança PIX?', true)">
                            <i class="fas fa-qrcode" style="color:#00D4AA;"></i>
                            <div class="ds-hc-card-title">Criar Cobrança PIX</div>
                            <div class="ds-hc-card-desc">Aprenda a gerar sua primeira cobrança.</div>
                        </div>
                        <div class="ds-hc-card-action" onclick="dsChatUI.triggerShortcut('Como gerar uma API Key?', true)">
                            <i class="fas fa-key text-info"></i>
                            <div class="ds-hc-card-title">Gerar API Key</div>
                            <div class="ds-hc-card-desc">Crie uma chave para sua aplicação.</div>
                        </div>
                        <div class="ds-hc-card-action" onclick="dsChatUI.triggerShortcut('Quero falar com suporte humano', true)">
                            <i class="fas fa-user-tie text-warning"></i>
                            <div class="ds-hc-card-title">Suporte Humano</div>
                            <div class="ds-hc-card-desc">Transfira para um de nossos especialistas.</div>
                        </div>
                    </div>
                </div>
            </div>

                        <div id="ds-view-chat" class="ds-hc-view ds-hc-chat-layout">
                <div class="ds-hc-view-header">
                    <button type="button" class="ds-hc-back-btn" onclick="dsChatUI.navigate('home')" title="Voltar" aria-label="Voltar para o início"><i class="fas fa-arrow-left" aria-hidden="true"></i></button>
                    <span>Conversa com Assistente</span>
                </div>
                
                <div id="ds-chat-messages" class="ds-hc-messages" onclick="dsChatUI.handleMessageMenuClick(event)">
                    <!-- Messages rendered via JS chat-renderer.js -->
                </div>

                <div class="ds-hc-input-area">
                    <div id="ds-chat-file-preview" style="display:none;" class="ds-hc-file-preview">
                        <div class="preview-content">
                            <i class="fas fa-file-alt"></i>
                            <span id="ds-chat-file-name">arquivo.png</span>
                        </div>
                        <button type="button" class="preview-close" onclick="dsChatUI.removeAttachment()" title="Remover anexo" aria-label="Remover anexo"><i class="fas fa-times" aria-hidden="true"></i></button>
                    </div>

                    <div id="ds-chat-emoji-picker" style="display:none;" class="ds-hc-emoji-picker">
                        <span onclick="dsChatUI.insertEmoji('😀')">😀</span>
                        <span onclick="dsChatUI.insertEmoji('😂')">😂</span>
                        <span onclick="dsChatUI.insertEmoji('👍')">👍</span>
                        <span onclick="dsChatUI.insertEmoji('🙏')">🙏</span>
                        <span onclick="dsChatUI.insertEmoji('🚀')">🚀</span>
                        <span onclick="dsChatUI.insertEmoji('🔥')">🔥</span>
                        <span onclick="dsChatUI.insertEmoji('👀')">👀</span>
                        <span onclick="dsChatUI.insertEmoji('✅')">✅</span>
                    </div>

                    <form id="ds-chat-form" onsubmit="dsChatUI.handleSend(event)" enctype="multipart/form-data">
                        @csrf
                        <div class="ds-hc-input-tools">
                            <label for="ds-chat-file-input" class="visually-hidden">Anexar arquivo ao chat</label>
                            <input type="file" id="ds-chat-file-input" name="attachment" style="display:none;" accept=".jpg,.jpeg,.png,.webp,.gif,.pdf,.txt,.log,.zip,.rar" onchange="dsChatUI.handleAttachmentSelect(this)">
                            <button type="button" title="Anexar Arquivo" aria-label="Anexar arquivo" onclick="dsChatUI.triggerAttachmentSelection()"><i class="fas fa-paperclip" aria-hidden="true"></i></button>
                            <button type="button" title="Inserir Emoji" aria-label="Inserir emoji" onclick="dsChatUI.toggleEmojiPicker()"><i class="far fa-smile" aria-hidden="true"></i></button>
                        </div>
                        <div class="ds-hc-input-row">
                            <label for="ds-chat-input" class="visually-hidden">Mensagem para o suporte</label>
                            <textarea id="ds-chat-input" name="message" class="ds-hc-input" placeholder="Escreva sua mensagem..." rows="1" onkeydown="if(event.key==='Enter' && !event.shiftKey){event.preventDefault(); dsChatUI.handleSend(event);}"></textarea>
                            <button type="submit" class="ds-hc-send-btn" title="Enviar" aria-label="Enviar mensagem"><i class="fas fa-paper-plane" aria-hidden="true"></i></button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
/* Design System Premium (Intercom / Stripe inspired) */
:root {
    --hc-bg-card: #12141c;
    --hc-bg-elevated: rgba(255, 255, 255, 0.03);
    --hc-border: rgba(255, 255, 255, 0.06);
    --hc-primary: #7C6EFF;
    --hc-primary-hover: #6b5ce6;
    --hc-text-main: #e2e8f0;
    --hc-text-muted: #94a3b8;
    --hc-shadow: 0 12px 40px rgba(0,0,0,0.4);
}

/* Floating Action Button */
#ds-hc-fab {
    position: fixed; bottom: 24px; right: 24px; width: 60px; height: 60px;
    background: var(--hc-primary); border-radius: 20px; display: flex; align-items: center; justify-content: center;
    cursor: pointer; z-index: 9998; box-shadow: 0 8px 24px rgba(124, 110, 255, 0.4); transition: all 0.3s;
    color: #fff; font-size: 1.5rem;
}
#ds-hc-fab:hover { transform: translateY(-4px) scale(1.02); box-shadow: 0 12px 28px rgba(124, 110, 255, 0.5); }
.ds-hc-badge { position: absolute; top: -6px; right: -6px; background: #FF4D6A; color: #fff; border-radius: 50%; width: 22px; height: 22px; font-size: 0.7rem; font-weight: 700; display: flex; align-items: center; justify-content: center; border: 2px solid var(--hc-bg-card); }

/* Widget Panel */
#ds-hc-widget {
    position: fixed; bottom: 96px; right: 24px; width: 340px; height: 540px; max-height: calc(100vh - 120px);
    background: var(--hc-bg-card); border: 1px solid var(--hc-border); border-radius: 20px; z-index: 9999;
    display: flex; flex-direction: column; overflow: hidden; box-shadow: var(--hc-shadow);
    animation: dsHcIn 0.3s cubic-bezier(0.16, 1, 0.3, 1); font-family: 'Inter', sans-serif;
}
@media (max-width: 500px) {
    #ds-hc-widget { bottom: 0; right: 0; width: 100vw; height: 100vh; max-height: 100vh; border-radius: 0; }
}
@keyframes dsHcIn { from { opacity: 0; transform: translateY(20px) scale(0.96); } to { opacity: 1; transform: translateY(0) scale(1); } }

/* Header */
.ds-hc-header { background: rgba(18, 20, 28, 0.95); -webkit-backdrop-filter: blur(10px); backdrop-filter: blur(10px); border-bottom: 1px solid var(--hc-border); flex-shrink: 0; }
.ds-hc-header-top { padding: 14px 20px; display: flex; align-items: center; justify-content: space-between; }
.ds-hc-header-info { display: flex; align-items: center; gap: 12px; }
.ds-hc-avatar { width: 38px; height: 38px; background: rgba(124, 110, 255, 0.1); border-radius: 10px; display: flex; align-items: center; justify-content: center; padding: 6px; }
.ds-hc-avatar img { width: 100%; height: 100%; object-fit: contain; }
.ds-hc-title { font-weight: 700; font-size: 0.85rem; color: var(--hc-text-main); margin-bottom: 2px;}
.ds-hc-status { font-size: 0.7rem; color: var(--hc-text-muted); display: flex; align-items: center; gap: 6px; }
.ds-hc-status-dot { width: 8px; height: 8px; border-radius: 50%; }
.ds-hc-status-dot.online { background: #00D4AA; box-shadow: 0 0 6px rgba(0,212,170,0.5); }
.ds-hc-close-btn { background: var(--hc-bg-elevated); border: 1px solid var(--hc-border); cursor: pointer; color: var(--hc-text-muted); width: 32px; height: 32px; border-radius: 10px; display: flex; align-items: center; justify-content: center; transition: all 0.2s; }
.ds-hc-close-btn:hover { background: rgba(255,255,255,0.08); color: var(--hc-text-main); }

/* Main Area & Views */
.ds-hc-main { flex: 1; position: relative; overflow: hidden; }
.ds-hc-views { width: 100%; height: 100%; position: relative; }
.ds-hc-view { position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: flex; flex-direction: column; overflow-y: auto; overflow-x: hidden; opacity: 0; visibility: hidden; transition: opacity 0.2s ease; }
.ds-hc-view.active { opacity: 1; visibility: visible; z-index: 1; }
.ds-hc-view::-webkit-scrollbar { width: 4px; }
.ds-hc-view::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 4px; }

/* View Headers */
.ds-hc-view-header { display: flex; align-items: center; gap: 12px; padding: 16px 20px; border-bottom: 1px solid var(--hc-border); position: sticky; top: 0; background: var(--hc-bg-card); z-index: 2; font-weight: 600; color: var(--hc-text-main); font-size: 0.9rem; }
.ds-hc-back-btn { background: none; border: none; color: var(--hc-text-muted); font-size: 1rem; cursor: pointer; padding: 4px 8px; margin-left: -8px; transition: color 0.2s; }
.ds-hc-back-btn:hover { color: var(--hc-text-main); }

/* HOME VIEW */
.ds-hc-hero { padding: 24px 20px; text-align: center; }
.ds-hc-hero h2 { font-size: 1.15rem; color: var(--hc-text-main); margin: 0 0 6px 0; font-weight: 700; letter-spacing: -0.02em;}
.ds-hc-hero p { font-size: 0.8rem; color: var(--hc-text-muted); margin: 0 0 16px 0; }
.ds-hc-btn-primary { background: var(--hc-primary); color: #fff; width: 100%; border: none; border-radius: 10px; padding: 10px 14px; font-weight: 600; font-size: 0.85rem; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.2s; box-shadow: 0 4px 12px rgba(124, 110, 255, 0.2); }
.ds-hc-btn-primary:hover { background: var(--hc-primary-hover); transform: translateY(-2px); box-shadow: 0 6px 16px rgba(124, 110, 255, 0.3); }

.ds-hc-section { padding: 0 20px 16px; }
.ds-hc-section-title { font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--hc-text-muted); margin: 0 0 12px 0; }

.ds-hc-search-box { background: var(--hc-bg-elevated); border: 1px solid var(--hc-border); border-radius: 12px; display: flex; align-items: center; padding: 0 14px; height: 44px; transition: border-color 0.2s; }
.ds-hc-search-box:focus-within { border-color: var(--hc-primary); }
.ds-hc-search-box i { color: var(--hc-text-muted); font-size: 0.9rem; }
.ds-hc-search-box input { background: none; border: none; color: var(--hc-text-main); font-size: 0.85rem; width: 100%; padding: 0 10px; outline: none; font-family: inherit; }
.ds-hc-search-results { background: var(--hc-bg-elevated); border: 1px solid var(--hc-border); border-radius: 12px; margin-top: 8px; overflow: hidden; }
.ds-hc-search-result-item { padding: 12px 14px; border-bottom: 1px solid var(--hc-border); cursor: pointer; font-size: 0.85rem; color: var(--hc-text-main); transition: background 0.2s; }
.ds-hc-search-result-item:hover { background: rgba(255,255,255,0.05); }
.ds-hc-search-result-item:last-child { border-bottom: none; }

/* Enhanced Action Cards (Home View) */
.ds-hc-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.ds-hc-card-action {
    background: var(--hc-bg-elevated); border: 1px solid var(--hc-border); border-radius: 12px; padding: 12px; text-align: left; cursor: pointer; transition: all 0.2s cubic-bezier(0.25, 0.8, 0.25, 1);
}
.ds-hc-card-action:hover { background: rgba(255,255,255,0.06); transform: translateY(-3px); border-color: rgba(255,255,255,0.15); box-shadow: 0 4px 12px rgba(0,0,0,0.2); }
.ds-hc-card-action i { font-size: 1.2rem; margin-bottom: 8px; display: block; }
.ds-hc-card-title { font-size: 0.8rem; font-weight: 700; color: var(--hc-text-main); margin-bottom: 4px; }
.ds-hc-card-desc { font-size: 0.7rem; color: var(--hc-text-muted); line-height: 1.3; }

/* Conversations List */
.ds-hc-conversations-list { display: flex; flex-direction: column; gap: 8px; }
.ds-hc-conv-card { background: var(--hc-bg-elevated); border: 1px solid var(--hc-border); border-radius: 12px; padding: 14px; cursor: pointer; transition: all 0.2s; position: relative; }
.ds-hc-conv-card:hover { background: rgba(255,255,255,0.06); border-color: rgba(255,255,255,0.15); transform: translateX(2px); }
.ds-hc-conv-card-title { font-size: 0.85rem; font-weight: 600; color: var(--hc-text-main); margin-bottom: 4px; display: flex; align-items: center; justify-content: space-between; }
.ds-hc-conv-card-desc { font-size: 0.75rem; color: var(--hc-text-muted); line-height: 1.4; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.ds-hc-conv-card-meta { display: flex; justify-content: space-between; margin-top: 8px; font-size: 0.7rem; color: var(--hc-text-muted); align-items: center; }
.ds-hc-conv-status { display: flex; align-items: center; gap: 4px; font-weight: 500; }
.ds-hc-conv-status.open { color: #00D4AA; }
.ds-hc-conv-status.pending { color: #f59e0b; }
.ds-hc-conv-status.answered { color: #3b82f6; }
.ds-hc-conv-status.closed { color: #94a3b8; }
.ds-hc-conv-unread { background: #FF4D6A; color: #fff; font-size: 0.65rem; font-weight: 700; padding: 2px 6px; border-radius: 10px; margin-left: 6px; }

/* CHAT VIEW */
.ds-hc-chat-layout { display: flex; flex-direction: column; height: 100%; }
.ds-hc-messages { flex: 1; overflow-y: auto; padding: 20px; display: flex; flex-direction: column; gap: 20px; position: relative; }
.ds-msg { display: flex; gap: 12px; max-width: 90%; position: relative; animation: dsMsgFade 0.3s ease; }
@keyframes dsMsgFade { from{ opacity:0; transform:translateY(10px); } to{ opacity:1; transform:translateY(0); } }
.ds-msg-user { align-self: flex-end; flex-direction: row-reverse; }
.ds-msg-admin { align-self: flex-start; }
.ds-msg-avatar { width: 28px; height: 28px; border-radius: 50%; background: rgba(124, 110, 255, 0.1); color: var(--hc-primary); display: flex; align-items: center; justify-content: center; font-size: 0.85rem; flex-shrink: 0; margin-top: 4px; }
.ds-msg-user .ds-msg-avatar { background: rgba(0, 212, 170, 0.1); color: #00D4AA; }
.ds-msg-content { position: relative; display: flex; flex-direction: column; gap: 4px; }
.ds-msg-header { font-size: 0.7rem; font-weight: 600; color: var(--hc-text-muted); display: flex; align-items: center; gap: 6px; margin-bottom: 2px; }
.ds-msg-bubble { padding: 12px 16px; border-radius: 16px; font-size: 0.85rem; line-height: 1.6; word-break: break-word; position: relative; display: flex; flex-direction: column; gap: 8px;}
.ds-msg-user .ds-msg-bubble { background: var(--hc-primary); color: #fff; border-top-right-radius: 4px; }
.ds-msg-admin .ds-msg-bubble { background: var(--hc-bg-elevated); color: var(--hc-text-main); border-top-left-radius: 4px; border: 1px solid var(--hc-border); }
.ds-msg-footer-time { font-size: 0.65rem; opacity: 0.6; align-self: flex-end; margin-top: 4px; }
.ds-smart-link { color: #00D4AA; text-decoration: underline; text-decoration-color: rgba(0,212,170,0.3); text-underline-offset: 3px; font-weight: 500; transition: color 0.2s; }
.ds-smart-link:hover { color: #fff; text-decoration-color: #fff; }

/* 3 Dots Menu */
.ds-msg-menu-btn { position: absolute; top: 50%; transform: translateY(-50%); background: var(--hc-bg-card); border: 1px solid var(--hc-border); border-radius: 50%; width: 26px; height: 26px; display: flex; align-items: center; justify-content: center; color: var(--hc-text-muted); cursor: pointer; opacity: 0; transition: opacity 0.2s; font-size: 0.75rem; box-shadow: 0 2px 5px rgba(0,0,0,0.2); }
.ds-msg-user .ds-msg-menu-btn { left: -34px; }
.ds-msg-admin .ds-msg-menu-btn { right: -34px; }
.ds-msg:hover .ds-msg-menu-btn { opacity: 1; }
.ds-msg-menu-btn:hover { color: var(--hc-text-main); background: var(--hc-bg-elevated); }

.ds-msg-actions-popup { position: absolute; z-index: 10; background: var(--hc-bg-card); border: 1px solid var(--hc-border); border-radius: 8px; box-shadow: var(--hc-shadow); padding: 4px; min-width: 140px; font-size: 0.8rem; display: none; }
.ds-msg-actions-popup.show { display: block; }
.ds-msg-action-item { padding: 8px 12px; border-radius: 6px; cursor: pointer; color: var(--hc-text-main); display: flex; align-items: center; gap: 8px; transition: background 0.2s; }
.ds-msg-action-item:hover { background: rgba(255,255,255,0.06); }

/* Chat Input Area */
.ds-hc-input-area { border-top: 1px solid var(--hc-border); padding: 12px 16px; background: var(--hc-bg-card); flex-shrink: 0; position: relative; }
.ds-hc-file-preview { display: flex; align-items: center; justify-content: space-between; background: var(--hc-bg-elevated); border: 1px solid var(--hc-border); border-radius: 8px; padding: 8px 12px; margin-bottom: 8px; font-size: 0.8rem; color: var(--hc-text-main); }
.ds-hc-file-preview .preview-content { display: flex; align-items: center; gap: 8px; }
.ds-hc-file-preview .preview-close { background: none; border: none; color: #FF4D6A; cursor: pointer; }
.ds-hc-emoji-picker { position: absolute; bottom: 100%; left: 16px; background: var(--hc-bg-card); border: 1px solid var(--hc-border); border-radius: 8px; padding: 10px; display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; box-shadow: var(--hc-shadow); margin-bottom: 8px; z-index: 10; }
.ds-hc-emoji-picker span { font-size: 1.2rem; cursor: pointer; text-align: center; padding: 4px; border-radius: 6px; transition: background 0.2s; }
.ds-hc-emoji-picker span:hover { background: rgba(255,255,255,0.1); }

.ds-hc-input-tools { display: flex; gap: 12px; margin-bottom: 8px; padding: 0 4px; }
.ds-hc-input-tools button { background: none; border: none; color: var(--hc-text-muted); font-size: 1.1rem; cursor: pointer; transition: color 0.2s; }
.ds-hc-input-tools button:hover { color: var(--hc-text-main); }
.ds-hc-input-row { display: flex; gap: 10px; align-items: flex-end; }
.ds-hc-input { flex: 1; background: var(--hc-bg-elevated); border: 1px solid var(--hc-border); border-radius: 14px; color: var(--hc-text-main); font-family: inherit; font-size: 0.85rem; padding: 12px 16px; resize: none; max-height: 120px; overflow-y: auto; transition: border-color 0.2s; }
.ds-hc-input:focus { outline: none; border-color: var(--hc-primary); }
.ds-hc-send-btn { width: 44px; height: 44px; border-radius: 14px; background: var(--hc-primary); border: none; cursor: pointer; color: #fff; font-size: 1rem; display: flex; align-items: center; justify-content: center; transition: all 0.2s; flex-shrink: 0; }
.ds-hc-send-btn:hover { background: var(--hc-primary-hover); transform: scale(1.05); }

/* TYPING INDICATOR */
.ds-typing-bubble { display: flex; gap: 4px; align-items: center; padding: 14px 18px; flex-direction: row !important; }
.ds-typing-bubble span { width: 6px; height: 6px; background: var(--hc-text-muted); border-radius: 50%; animation: dsTypingBounce 1.4s infinite ease-in-out; }
.ds-typing-bubble span:nth-child(2) { animation-delay: 0.2s; }
.ds-typing-bubble span:nth-child(3) { animation-delay: 0.4s; }
@keyframes dsTypingBounce { 0%, 80%, 100% { opacity: 0.3; transform: translateY(0); } 40% { opacity: 1; transform: translateY(-4px); } }

/* RICH COMPONENTS CSS */
.ds-comp-action-cards { border-top: 1px solid var(--hc-border); margin-top: 8px; padding-top: 8px; display: grid; grid-template-columns: 1fr 1fr; gap: 6px; }
.ds-comp-action-item { font-size: 0.75rem; color: var(--hc-text-main); display: flex; align-items: center; gap: 6px; }
.ds-comp-endpoint { background: rgba(0,0,0,0.3); border: 1px solid var(--hc-border); border-radius: 8px; padding: 10px 14px; display: flex; justify-content: space-between; align-items: center; margin-top: 8px; font-family: monospace; font-size: 0.8rem; flex-wrap: wrap; gap: 10px; }
.ds-comp-endpoint .endpoint-header { display: flex; gap: 8px; flex: 1; word-break: break-all; align-items: center; }
.ds-comp-endpoint .endpoint-method { font-weight: 700; white-space: nowrap; }
.ds-comp-endpoint .endpoint-copy { background: none; border: none; color: var(--hc-text-muted); cursor: pointer; font-size: 0.8rem; transition: color 0.2s; white-space: nowrap; flex-shrink: 0; }
.ds-comp-endpoint .endpoint-copy:hover { color: #fff; }
.ds-comp-codeblock { background: #0c0d12; border: 1px solid var(--hc-border); border-radius: 8px; overflow: hidden; margin-top: 8px; }
.ds-comp-codeblock .codeblock-header { background: rgba(255,255,255,0.05); padding: 4px 12px; display: flex; justify-content: space-between; align-items: center; font-size: 0.7rem; color: var(--hc-text-muted); }
.ds-comp-codeblock pre { margin: 0; padding: 12px; font-size: 0.75rem; overflow-x: auto; color: #a5b4fc; }
.ds-comp-codeblock .codeblock-copy { background: none; border: none; color: var(--hc-text-muted); cursor: pointer; }
.ds-comp-codeblock .codeblock-copy:hover { color: #fff; }
.ds-comp-buttons { display: flex; flex-direction: column; gap: 8px; margin-top: 8px; }
.ds-comp-btn { background: var(--hc-bg-elevated); border: 1px solid var(--hc-border); color: var(--hc-text-main); padding: 8px 14px; border-radius: 10px; font-size: 0.8rem; font-weight: 500; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; justify-content: center; gap: 8px; width: 100%; }
.ds-comp-btn:hover { background: rgba(255,255,255,0.08); border-color: rgba(255,255,255,0.15); transform: translateY(-1px); }
.ds-comp-btn-outline { background: transparent; border: 1px solid var(--hc-border); color: var(--hc-text-main); padding: 8px 14px; border-radius: 10px; font-size: 0.8rem; font-weight: 500; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; justify-content: center; gap: 8px; flex: 1; }
.ds-comp-btn-outline:hover { background: rgba(255,255,255,0.06); border-color: rgba(255,255,255,0.15); }
.ds-comp-feedback { border-top: 1px solid var(--hc-border); margin-top: 12px; padding-top: 12px; display: flex; gap: 8px; width: 100%; }
.ds-feedback-success { font-size: 0.8rem; color: #00D4AA; font-weight: 500; }
.ds-feedback-overflow { font-size: 0.8rem; color: var(--hc-text-main); }
.ds-feedback-overflow p { margin: 0 0 8px 0; }

/* OriginPay support polish */
#ds-hc-widget {
    width: min(390px, calc(100vw - 32px));
    height: min(640px, calc(100dvh - 112px));
    right: max(20px, env(safe-area-inset-right));
    bottom: calc(88px + env(safe-area-inset-bottom));
    border-radius: 16px;
    box-shadow: 0 22px 70px rgba(0,0,0,0.48), 0 0 0 1px rgba(255,255,255,0.08) inset;
}

.ds-hc-header-top {
    min-height: 64px;
}

.ds-hc-header-info {
    min-width: 0;
}

.ds-hc-header-info > div:last-child {
    min-width: 0;
}

.ds-hc-title,
.ds-hc-status {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.ds-hc-close-btn,
.ds-hc-back-btn,
.ds-hc-input-tools button,
.ds-hc-send-btn {
    min-width: 36px;
}

.ds-hc-main,
.ds-hc-views,
.ds-hc-chat-layout {
    min-height: 0;
}

.ds-hc-view {
}

.ds-hc-messages {
    min-height: 0;
    overscroll-behavior: contain;
}

.ds-hc-input-area {
    padding-bottom: max(12px, env(safe-area-inset-bottom));
}

.ds-hc-input-row {
    width: 100%;
    min-width: 0;
}

.ds-hc-input {
    min-height: 44px;
    min-width: 0;
}

.ds-msg {
    min-width: 0;
}

.ds-msg-content,
.ds-msg-bubble {
    min-width: 0;
    max-width: 100%;
}

.ds-hc-card-action,
.ds-hc-conv-card,
.ds-comp-codeblock,
.ds-comp-endpoint {
    min-width: 0;
}

@media (max-width: 768px) {
    #ds-hc-widget {
        left: max(10px, env(safe-area-inset-left));
        right: max(10px, env(safe-area-inset-right));
        bottom: calc(76px + env(safe-area-inset-bottom));
        width: auto;
        height: min(74dvh, 620px);
        max-height: calc(100dvh - 92px - env(safe-area-inset-bottom));
        border-radius: 16px;
    }

    .ds-hc-header-top {
        padding: 12px 14px;
    }

    .ds-hc-avatar {
        width: 34px;
        height: 34px;
        border-radius: 10px;
    }

    .ds-hc-title {
        font-size: 0.82rem;
    }

    .ds-hc-status {
        font-size: 0.68rem;
    }

    .ds-hc-hero {
        padding: 18px 14px;
    }

    .ds-hc-section {
        padding: 0 14px 14px;
    }

    .ds-hc-grid {
        grid-template-columns: 1fr;
    }

    .ds-hc-card-action {
        padding: 12px;
    }

    .ds-hc-view-header {
        padding: 12px 14px;
    }

    .ds-hc-messages {
        padding: 14px;
        gap: 14px;
        min-height: 0;
    }

    .ds-msg {
        max-width: 96%;
        gap: 8px;
    }

    .ds-msg-avatar {
        width: 26px;
        height: 26px;
    }

    .ds-msg-bubble {
        padding: 10px 12px;
        border-radius: 12px;
        font-size: 0.82rem;
        line-height: 1.5;
    }

    .ds-hc-input-area {
        padding: 10px 12px max(12px, env(safe-area-inset-bottom));
    }

    #ds-hc-widget .ds-hc-input-row {
        display: flex !important;
        width: 100% !important;
        min-width: 0 !important;
        gap: 10px !important;
        align-items: flex-end !important;
    }

    .ds-hc-input-tools {
        gap: 8px;
        margin-bottom: 6px;
    }

    .ds-hc-input-tools button {
        width: 36px;
        height: 32px;
        border-radius: 8px;
        background: rgba(255,255,255,0.04);
    }

    #ds-hc-widget .ds-hc-input {
        flex: 1 1 auto !important;
        width: auto !important;
        min-width: 0 !important;
        min-height: 42px !important;
        font-size: 16px !important;
        padding: 10px 12px !important;
        border-radius: 12px !important;
    }

    #ds-hc-widget .ds-hc-send-btn {
        flex: 0 0 42px !important;
        width: 42px !important;
        min-width: 42px !important;
        max-width: 42px !important;
        height: 42px !important;
        padding: 0 !important;
        border-radius: 12px !important;
    }
}

@media (max-width: 420px) {
    #ds-hc-widget {
        left: 0 !important;
        right: 0 !important;
        bottom: 0 !important;
        width: 100vw !important;
        height: min(82dvh, 680px) !important;
        max-height: calc(100dvh - env(safe-area-inset-top)) !important;
        border-radius: 16px 16px 0 0;
        border-left: 0;
        border-right: 0;
        border-bottom: 0;
    }
}
</style>

<script src="{{ asset('frontend/js/support-chat/support-config.js') }}"></script>
<script src="{{ asset('frontend/js/support-chat/knowledge-base.js') }}"></script>
<script src="{{ asset('frontend/js/support-chat/chat-components.js') }}"></script>
<script src="{{ asset('frontend/js/support-chat/chat-links.js') }}"></script>
<script src="{{ asset('frontend/js/support-chat/chat-actions.js') }}"></script>
<script src="{{ asset('frontend/js/support-chat/chat-renderer.js') }}"></script>
<script src="{{ asset('frontend/js/support-chat/support-ai.js?v=1.1') }}"></script>
<script src="{{ asset('frontend/js/support-chat/chat-ui.js?v=1.1') }}"></script>






