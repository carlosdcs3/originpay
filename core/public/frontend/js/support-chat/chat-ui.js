/**
 * chat-ui.js
 * Handles core UI interactions, view switching, and form submission.
 */

window.dsChatUI = {

    init: function() {
        this.bindEvents();
        // Auto-resize textarea
        const input = document.getElementById('ds-chat-input');
        if (input) {
            input.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = Math.min(this.scrollHeight, 120) + 'px';
            });
        }
        this.fetchState();
        this.startPolling();
    },

    bindEvents: function() {
        document.addEventListener('click', (e) => {
            if (window.dsChatState.activePopup && !e.target.closest('.ds-msg-content')) {
                window.dsChatState.activePopup.classList.remove('show');
                window.dsChatState.activePopup = null;
            }
            
            const emojiPicker = document.getElementById('ds-chat-emoji-picker');
            if (emojiPicker && emojiPicker.style.display === 'grid' && !e.target.closest('.ds-hc-input-tools') && !e.target.closest('.ds-hc-emoji-picker')) {
                emojiPicker.style.display = 'none';
            }
        });
    },

    toggleWidget: function() {
        window.dsChatState.isOpen = !window.dsChatState.isOpen;
        const widget = document.getElementById('ds-hc-widget');
        const fabIcon = document.getElementById('ds-hc-fab-icon');
        
        if (window.dsChatState.isOpen) {
            widget.style.display = 'flex';
            document.body.classList.add('support-chat-open');
            fabIcon.className = 'fas fa-times';
            document.getElementById('ds-hc-badge').style.display = 'none';
            this.fetchState(); // refresh immediately
        } else {
            widget.style.display = 'none';
            document.body.classList.remove('support-chat-open');
            fabIcon.className = 'fas fa-headset';
            this.navigate('home'); 
        }
    },

    navigate: function(viewId) {
        document.querySelectorAll('.ds-hc-view').forEach(v => v.classList.remove('active'));
        const viewEl = document.getElementById('ds-view-' + viewId);
        if(viewEl) viewEl.classList.add('active');

        if (viewId === 'chat') {
            document.getElementById('ds-chat-input').focus();
        } else if (viewId === 'home') {
            window.dsChatState.activeConversationId = null;
            this.fetchState();
        }
    },

    fetchState: async function() {
        const url = window.DS_CHAT_CONFIG?.endpoints?.state;
        if (!url) return;

        try {
            const res = await fetch(url);
            const contentType = res.headers.get('content-type') || '';
            
            if (!res.ok) {
                const text = await res.text();
                console.error('Support chat request failed:', res.status, text);
                throw new Error(`HTTP ${res.status}`);
            }
            
            if (!contentType.includes('application/json')) {
                const text = await res.text();
                console.error('Expected JSON, got:', contentType, text);
                throw new Error('Invalid JSON response');
            }
            
            const data = await res.json();
            
            // Update Badge
            const badge = document.getElementById('ds-hc-badge');
            if (badge && !window.dsChatState.isOpen) {
                if (data.unread_count > 0) {
                    badge.textContent = data.unread_count;
                    badge.style.display = 'flex';
                } else {
                    badge.style.display = 'none';
                }
            }

            // Render Conversations List
            const section = document.getElementById('ds-hc-conversations-section');
            const list = document.getElementById('ds-hc-conversations-list');
            if (section && list) {
                if (data.conversations && data.conversations.length > 0) {
                    section.style.display = 'block';
                    let html = '';
                    data.conversations.forEach(conv => {
                        let statusText = 'Aberta';
                        let statusClass = 'open';
                        
                        if(conv.status === 'pending') { statusText = 'Aguardando Sup.'; statusClass = 'pending'; }
                        else if(conv.status === 'answered') { statusText = 'Respondida'; statusClass = 'answered'; }
                        else if(conv.status === 'closed') { statusText = 'Encerrada'; statusClass = 'closed'; }

                        let unreadBadge = conv.unread_count > 0 ? `<span class="ds-hc-conv-unread">${conv.unread_count} nova(s)</span>` : '';
                        
                        let date = new Date(conv.last_message_at);
                        let dateStr = date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});

                        html += `
                            <div class="ds-hc-conv-card" onclick="dsChatUI.openConversation(${conv.id})">
                                <div class="ds-hc-conv-card-title">
                                    ${conv.subject} ${unreadBadge}
                                </div>
                                <div class="ds-hc-conv-card-desc">
                                    ${conv.last_message || '...'}
                                </div>
                                <div class="ds-hc-conv-card-meta">
                                    <div class="ds-hc-conv-status ${statusClass}">
                                        <i class="fas fa-circle" style="font-size:6px;"></i> ${statusText}
                                    </div>
                                    <div>${dateStr}</div>
                                </div>
                            </div>
                        `;
                    });
                    list.innerHTML = html;
                } else {
                    section.style.display = 'none';
                }
            }
        } catch (e) {
            console.error('Failed to fetch state', e);
        }
    },

    openConversation: async function(id) {
        window.dsChatState.activeConversationId = id;
        window.dsChatState.lastMessageId = 0;
        this.navigate('chat');
        document.getElementById('ds-chat-messages').innerHTML = '<div class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin"></i> Carregando...</div>';
        await this.fetchHistory(true);
    },

    startNewConversation: function() {
        window.dsChatState.activeConversationId = null;
        window.dsChatState.lastMessageId = 0;
        document.getElementById('ds-chat-messages').innerHTML = '';
        this.addAdminMessage("Olá! Sou o Assistente OriginPay. Como posso te ajudar hoje?");
        this.navigate('chat');
    },

    fetchHistory: async function(initial = false) {
        if (!window.dsChatState.activeConversationId) return;

        const url = window.DS_CHAT_CONFIG?.endpoints?.conversation + window.dsChatState.activeConversationId;
        if (!url) return;

        try {
            const res = await fetch(url);
            const contentType = res.headers.get('content-type') || '';
            
            if (!res.ok) {
                const text = await res.text();
                console.error('Support chat fetchHistory failed:', res.status, text);
                throw new Error(`HTTP ${res.status}`);
            }
            
            if (!contentType.includes('application/json')) {
                const text = await res.text();
                console.error('Expected JSON, got:', contentType, text);
                throw new Error('Invalid JSON response');
            }
            
            const data = await res.json();
            const messages = data.messages || [];
            
            if (initial) {
                const chatBox = document.getElementById('ds-chat-messages');
                if (chatBox) chatBox.innerHTML = '';
            }

            if (messages.length > 0) {
                let lastMessageId = window.dsChatState.lastMessageId || 0;
                let added = false;
                
                messages.forEach(msg => {
                    if (msg.id > lastMessageId) {
                        window.dsChatRenderer.renderMessage({
                            id: 'msg-' + msg.id,
                            type: 'message',
                            sender: msg.sender,
                            timestamp: msg.created_at,
                            markdown: msg.message,
                            read_at: msg.read_at,
                            attachments: msg.attachments || []
                        });
                        lastMessageId = msg.id;
                        added = true;
                    } else if (!initial && msg.sender === 'user' && msg.read_at) {
                        const msgEl = document.getElementById('msg-' + msg.id);
                        if (msgEl) {
                            const icon = msgEl.querySelector('.ds-status-icon');
                            if (icon && icon.classList.contains('sent')) {
                                icon.classList.remove('sent');
                                icon.classList.add('read');
                                icon.style.color = '#0d6efd';
                            }
                        }
                    }
                });
                
                window.dsChatState.lastMessageId = lastMessageId;
                if (added || initial) {
                    const chatBox = document.getElementById('ds-chat-messages');
                    if (chatBox) chatBox.scrollTop = chatBox.scrollHeight;
                }
            } else if (initial) {
                this.addAdminMessage("Olá! Sou o Assistente OriginPay. Como posso te ajudar hoje?");
            }
        } catch (e) {
            console.error('Failed to fetch chat history', e);
        }
    },

    startPolling: function() {
        if (window.dsChatState.pollingInterval) clearInterval(window.dsChatState.pollingInterval);
        
        window.dsChatState.pollingInterval = setInterval(() => {
            if (window.dsChatState.isOpen) {
                if (window.dsChatState.activeConversationId) {
                    this.fetchHistory(false);
                } else {
                    this.fetchState();
                }
            } else {
                // Fechado: poll a cada 30s? Como set interval é fixo, podemos 
                // otimizar usando contador ou mudar o tempo. Para simplificar,
                // vamos buscar state se isOpen == false.
                this.fetchState();
            }
        }, window.dsChatState.isOpen ? 10000 : 30000);
        
        // Listen for open state change to adjust polling dynamically if needed, 
        // but 10s open / 30s closed is acceptable by checking inside the loop, though we need varying interval.
        // Let's rewrite startPolling to be recursive timeout for better dynamic interval:
        clearInterval(window.dsChatState.pollingInterval);
        const poll = () => {
            const interval = window.dsChatState.isOpen ? 10000 : 30000;
            
            if (window.dsChatState.isOpen && window.dsChatState.activeConversationId) {
                this.fetchHistory(false);
            } else {
                this.fetchState();
            }

            window.dsChatState.pollingInterval = setTimeout(poll, interval);
        };
        window.dsChatState.pollingInterval = setTimeout(poll, 30000);
    },

    mockSearch: function(query) {
        const resultsEl = document.getElementById('ds-hc-search-results');
        if (!query || query.trim() === '') {
            resultsEl.style.display = 'none';
            return;
        }

        const q = query.toLowerCase();
        let matches = [];

        if (window.DS_KNOWLEDGE_BASE) {
            matches = window.DS_KNOWLEDGE_BASE.filter(item => {
                if (item.title.toLowerCase().includes(q)) return true;
                if (item.keywords.some(k => k.toLowerCase().includes(q))) return true;
                if (item.aliases.some(a => a.toLowerCase().includes(q))) return true;
                return false;
            });
        }

        if (matches.length > 0) {
            let html = '';
            matches.forEach(m => {
                html += `<div class="ds-hc-search-result-item" onclick="dsChatUI.triggerShortcut('${m.title}', true)">
                            <i class="fas fa-search" style="margin-right: 8px; opacity: 0.5;"></i> ${m.title}
                         </div>`;
            });
            resultsEl.innerHTML = html;
            resultsEl.style.display = 'block';
        } else {
            resultsEl.innerHTML = `<div class="ds-hc-search-result-item" style="color:var(--hc-text-muted);cursor:default;">Nenhum resultado encontrado.</div>`;
            resultsEl.style.display = 'block';
        }
    },

    triggerShortcut: function(promptText, autoSubmit = false) {
        this.startNewConversation();
        const input = document.getElementById('ds-chat-input');
        input.value = promptText;
        input.focus();
        
        if (autoSubmit) {
            this.handleSend(new Event('submit', { cancelable: true }));
        }
    },

    handleSend: async function(e) {
        e.preventDefault();
        const input = document.getElementById('ds-chat-input');
        const attachmentInput = document.getElementById('ds-chat-file-input');
        let msg = input.value.trim();
        const hasFile = window.dsChatState.pendingAttachment != null;
        
        if ((!msg && !hasFile) || window.dsChatState.isTyping) return;

        const userContext = {
            url: window.location.href,
            userAgent: navigator.userAgent,
            language: navigator.language,
            time: new Date().toISOString()
        };

        input.value = '';
        input.style.height = 'auto';
        
        // Render locally (message only, attachment won't render preview easily)
        if (msg) {
            window.dsChatRenderer.renderMessage({
                id: 'msg-' + Date.now(),
                type: 'message',
                sender: 'user',
                timestamp: new Date().toISOString(),
                markdown: msg
            });
        }
        
        const sendBtn = document.querySelector('.ds-hc-send-btn');
        if (sendBtn) sendBtn.disabled = true;

        const formData = new FormData();
        if (msg) formData.append('message', msg);
        formData.append('sender', 'user');
        formData.append('context', JSON.stringify(userContext));
        if (window.dsChatState.activeConversationId) {
            formData.append('conversation_id', window.dsChatState.activeConversationId);
        }
        if (hasFile) {
            formData.append('attachment', window.dsChatState.pendingAttachment);
        }

        this.removeAttachment();

        // Send to Backend
        const sendEndpoint = window.DS_CHAT_CONFIG?.endpoints?.send || '/support-chat/send';
        try {
            const res = await fetch(sendEndpoint, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: formData
            });
            const contentType = res.headers.get('content-type') || '';
            
            if (!res.ok) {
                const text = await res.text();
                let errMsg = `HTTP ${res.status}`;
                try {
                    const errorData = JSON.parse(text);
                    if (errorData.error) errMsg = errorData.error;
                    if (res.status === 403 && errorData.conversation_id) {
                        window.dsChatState.activeConversationId = errorData.conversation_id;
                        this.fetchHistory(false);
                    }
                } catch(e) {}
                if (typeof notify === 'function') {
                    notify('error', errMsg);
                } else {
                    alert(errMsg);
                }
                if (sendBtn) sendBtn.disabled = false;
                throw new Error(errMsg);
            }
            
            if (!contentType.includes('application/json')) {
                throw new Error('Invalid JSON response');
            }
            
            const data = await res.json();
            
            if (data.conversation_id) {
                window.dsChatState.activeConversationId = data.conversation_id;
            }
            this.fetchHistory(false);
            
            // Trigger AI response
            this.triggerAI(msg, userContext);
        } catch (err) {
            console.error('Failed to save user msg', err);
        } finally {
            if (sendBtn) sendBtn.disabled = false;
        }
    },

    triggerAI: async function(msg, context) {
        window.dsChatState.isTyping = true;
        this.showTypingIndicator();
        
        try {
            const aiPayload = await window.dsSupportAI.processMessage(msg, context);
            this.hideTypingIndicator();
            
            if (aiPayload) {
                const formData = new FormData();
                formData.append('message', aiPayload.markdown);
                formData.append('sender', 'admin');
                formData.append('conversation_id', window.dsChatState.activeConversationId);
                
                const sendEndpoint = window.DS_CHAT_CONFIG?.endpoints?.send || '/support-chat/send';
                await fetch(sendEndpoint, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                    body: formData
                });
                
                this.fetchHistory(false);
            }
        } catch (e) {
            console.error('AI Error:', e);
            this.hideTypingIndicator();
        } finally {
            window.dsChatState.isTyping = false;
        }
    },
    
    showTypingIndicator: function() {
        const chatBox = document.getElementById('ds-chat-messages');
        if (!chatBox) return;
        const id = 'ds-typing-indicator';
        if (!document.getElementById(id)) {
            const el = document.createElement('div');
            el.id = id;
            el.className = 'ds-msg ds-msg-admin';
            el.innerHTML = `
                <div class="ds-msg-avatar"><i class="fas fa-robot"></i></div>
                <div class="ds-msg-content">
                    <div class="ds-msg-bubble ds-typing-bubble">
                        <span></span><span></span><span></span>
                    </div>
                </div>
            `;
            chatBox.appendChild(el);
            chatBox.scrollTop = chatBox.scrollHeight;
        }
    },
    
    hideTypingIndicator: function() {
        const el = document.getElementById('ds-typing-indicator');
        if (el) el.remove();
    },

    addAdminMessage: function(text) {
        window.dsChatRenderer.renderMessage({
            id: 'msg-' + Date.now(),
            type: 'message',
            sender: 'admin',
            timestamp: new Date().toISOString(),
            markdown: text
        });
    },

    handleMessageMenuClick: function(e) {
        // ... previous logic remains
    },

    triggerAttachmentSelection: function() {
        document.getElementById('ds-chat-file-input').click();
    },

    handleAttachmentSelect: function(input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            if (file.size > 5 * 1024 * 1024) {
                if (typeof notify === 'function') {
                    notify('warning', 'Tamanho máximo permitido: 5MB');
                } else {
                    alert('Tamanho máximo permitido: 5MB');
                }
                this.removeAttachment();
                return;
            }
            window.dsChatState.pendingAttachment = file;
            document.getElementById('ds-chat-file-name').textContent = file.name;
            document.getElementById('ds-chat-file-preview').style.display = 'flex';
        }
    },

    removeAttachment: function() {
        window.dsChatState.pendingAttachment = null;
        const fileInput = document.getElementById('ds-chat-file-input');
        if (fileInput) fileInput.value = '';
        document.getElementById('ds-chat-file-preview').style.display = 'none';
    },

    toggleEmojiPicker: function() {
        const picker = document.getElementById('ds-chat-emoji-picker');
        picker.style.display = picker.style.display === 'none' ? 'grid' : 'none';
    },

    insertEmoji: function(emoji) {
        const input = document.getElementById('ds-chat-input');
        input.value += emoji;
        document.getElementById('ds-chat-emoji-picker').style.display = 'none';
        input.focus();
    }
};

document.addEventListener('DOMContentLoaded', () => window.dsChatUI.init());
