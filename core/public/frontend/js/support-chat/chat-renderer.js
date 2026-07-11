/**
 * chat-renderer.js
 * Renders messages based on the unified payload structure.
 * No business logic, just rendering to DOM.
 */

window.dsChatRenderer = {

    renderMessage: function(payload) {
        const container = document.getElementById('ds-chat-messages');
        const time = new Date(payload.timestamp).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
        
        const div = document.createElement('div');
        
        if (payload.is_system) {
            div.className = 'ds-msg ds-msg-system my-2';
            div.innerHTML = `
                <div class="w-100 text-center" style="font-size: 0.8rem; color: #adb5bd; font-style: italic;">
                    <i class="fas fa-info-circle me-1"></i> ${window.dsChatComponents.escapeHtml(payload.markdown)}
                    <span class="ms-2" style="font-size: 0.7rem; opacity: 0.7;">${time}</span>
                </div>
            `;
            container.appendChild(div);
            this.scrollToBottom();
            return;
        }

        div.className = 'ds-msg ds-msg-' + payload.sender;
        
        // Avatar
        let avatarHTML = '';
        if (payload.sender === 'admin') {
            avatarHTML = `<div class="ds-msg-avatar"><img src="${window.DS_CHAT_CONFIG.avatarIA}" alt="AI" style="width:20px;height:20px;object-fit:contain;"></div>`;
        } else {
            avatarHTML = `<div class="ds-msg-avatar"><i class="${window.DS_CHAT_CONFIG.avatarUser}"></i></div>`;
        }

        // Header (Authority Header)
        let headerHTML = '';
        if (payload.title && payload.sender === 'admin') {
            headerHTML = `<div class="ds-msg-header"><i class="fas fa-shield-alt"></i> ${window.DS_CHAT_CONFIG.botName} • ${window.dsChatComponents.escapeHtml(payload.title)}</div>`;
        }

        // Markdown text processing (Smart Links)
        let textHTML = window.dsChatLinks.linkifyKeywords(payload.markdown);

        // Components (Action Cards, Endpoint Cards, Code Blocks, Buttons)
        let componentsHTML = '';
        if (payload.components && payload.components.length > 0) {
            componentsHTML += '<div class="ds-msg-components">';
            payload.components.forEach(comp => {
                if (comp.type === 'action_cards') componentsHTML += window.dsChatComponents.renderActionCards(comp.items);
                if (comp.type === 'endpoint_card') componentsHTML += window.dsChatComponents.renderEndpointCard(comp.method, comp.path);
                if (comp.type === 'code_block') componentsHTML += window.dsChatComponents.renderCodeBlock(comp.language, comp.code);
            });
            componentsHTML += '</div>';
        }

        // Buttons
        let buttonsHTML = '';
        if (payload.actions && payload.actions.length > 0) {
            buttonsHTML += window.dsChatComponents.renderButtons(payload.actions);
        }

        // Feedback
        let feedbackHTML = '';
        if (payload.feedback_required) {
            feedbackHTML += window.dsChatComponents.renderFeedback(payload.id);
        }

        // 3 Dots Menu
        let actionItems = '';
        if (payload.sender === 'user') {
            actionItems = `
                <div class="ds-msg-action-item" data-action="copy"><i class="far fa-copy"></i> Copiar</div>
                <div class="ds-msg-action-item" data-action="edit"><i class="far fa-edit"></i> Editar</div>
                <div class="ds-msg-action-item" data-action="resend"><i class="fas fa-redo"></i> Reenviar</div>
            `;
        } else {
            actionItems = `
                <div class="ds-msg-action-item" data-action="copy"><i class="far fa-copy"></i> Copiar</div>
                <div class="ds-msg-action-item" data-action="resend"><i class="fas fa-redo"></i> Reenviar</div>
                <div class="ds-msg-action-item" data-action="useful"><i class="far fa-thumbs-up"></i> Útil</div>
                <div class="ds-msg-action-item" data-action="not-useful"><i class="far fa-thumbs-down"></i> Não Útil</div>
            `;
        }

        let statusIcon = '';
        if (payload.sender === 'user') {
            if (payload.read_at) {
                statusIcon = ' <i class="fas fa-check-double ds-status-icon read" style="color: #0d6efd; font-size:10px; margin-left:4px;"></i>';
            } else {
                statusIcon = ' <i class="fas fa-check-double ds-status-icon sent" style="color: #adb5bd; font-size:10px; margin-left:4px;"></i>';
            }
        }
        
        let attachmentHtml = '';
        if (payload.attachments && payload.attachments.length > 0) {
            payload.attachments.forEach(att => {
                const downloadUrl = att.url;
                if (att.mime_type.startsWith('image/')) {
                    const fallbackHtml = `<a href='${downloadUrl}' class='btn btn-sm btn-outline-light mt-2' target='_blank' style='display:inline-block; border-color: rgba(255,255,255,0.2);'><i class='fas fa-download'></i> Baixar Imagem</a>`;
                    attachmentHtml += `
                        <div class="mt-2 text-center" style="background: rgba(255,255,255,0.1); border-radius: 8px; padding: 4px;">
                            <a href="${downloadUrl}" target="_blank">
                                <img src="${downloadUrl}" style="max-width: 100%; max-height: 200px; border-radius: 6px; cursor: pointer;" alt="${att.original_name}" onerror="this.onerror=null; this.outerHTML=\`${fallbackHtml}\`">
                            </a>
                        </div>
                    `;
                } else {
                    let icon = 'fa-file-alt';
                    if (att.mime_type === 'application/pdf') icon = 'fa-file-pdf';
                    else if (att.mime_type.includes('zip') || att.mime_type.includes('rar')) icon = 'fa-file-archive';
                    
                    attachmentHtml += `
                        <div class="mt-2 p-2 rounded d-flex align-items-center" style="background: rgba(0,0,0,0.1); border: 1px solid rgba(255,255,255,0.1);">
                            <i class="fas ${icon} fs-4 me-2 text-primary"></i>
                            <div class="flex-grow-1 overflow-hidden">
                                <div class="text-truncate" style="font-size: 0.85rem; max-width: 150px; color: #fff;" title="${att.original_name}">${att.original_name}</div>
                                <div style="font-size: 0.7rem; opacity: 0.8;">${(att.size / 1024).toFixed(1)} KB</div>
                            </div>
                            <a href="${downloadUrl}" class="btn btn-sm btn-outline-light ms-2" target="_blank" download style="padding: 2px 8px;">
                                <i class="fas fa-download"></i>
                            </a>
                        </div>
                    `;
                }
            });
        }

        const rawText = window.dsChatComponents.escapeHtml(payload.markdown) || '';

        div.innerHTML = `
            ${avatarHTML}
            <div class="ds-msg-content">
                ${headerHTML}
                <div class="ds-msg-bubble" data-raw="${rawText}">
                    ${textHTML}
                    ${attachmentHtml}
                    ${componentsHTML}
                    ${buttonsHTML}
                    <div class="ds-msg-footer-time">${time}${statusIcon}</div>
                </div>
                ${feedbackHTML}
                <div class="ds-msg-menu-btn"><i class="fas fa-ellipsis-v"></i></div>
                <div class="ds-msg-actions-popup">
                    ${actionItems}
                </div>
            </div>
        `;

        container.appendChild(div);
        this.scrollToBottom();
    },

    showTypingIndicator: function() {
        const container = document.getElementById('ds-chat-messages');
        const div = document.createElement('div');
        div.className = 'ds-msg ds-msg-admin ds-typing-container';
        div.id = 'ds-typing-indicator';
        div.innerHTML = `
            <div class="ds-msg-avatar"><img src="${window.DS_CHAT_CONFIG.avatarIA}" alt="AI" style="width:20px;height:20px;object-fit:contain;"></div>
            <div class="ds-msg-content">
                <div class="ds-msg-bubble ds-typing-bubble">
                    <span></span><span></span><span></span>
                </div>
            </div>
        `;
        container.appendChild(div);
        this.scrollToBottom();
    },

    removeTypingIndicator: function() {
        const el = document.getElementById('ds-typing-indicator');
        if (el) el.remove();
    },

    scrollToBottom: function() {
        const container = document.getElementById('ds-chat-messages');
        if(container) {
            container.scrollTop = container.scrollHeight;
        }
    }
};
