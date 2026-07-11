/**
 * chat-components.js
 * Contains all reusable UI components for the chat renderer.
 * No business logic, just templates.
 */

window.dsChatComponents = {
    
    renderActionCards: function(items) {
        if (!items || items.length === 0) return '';
        let html = '<div class="ds-comp-action-cards">';
        items.forEach(item => {
            html += `<div class="ds-comp-action-item"><i class="fas fa-check text-success"></i> ${this.escapeHtml(item)}</div>`;
        });
        html += '</div>';
        return html;
    },

    renderEndpointCard: function(method, path) {
        const methodColor = method.toUpperCase() === 'POST' ? '#00D4AA' : '#7C6EFF';
        return `
            <div class="ds-comp-endpoint">
                <div class="endpoint-header">
                    <span class="endpoint-method" style="color: ${methodColor};">${method.toUpperCase()}</span>
                    <span class="endpoint-path">${this.escapeHtml(path)}</span>
                </div>
                <button class="endpoint-copy" onclick="dsChatActions.copyText('${this.escapeHtml(method)} ${this.escapeHtml(path)}')">
                    <i class="far fa-copy"></i> Copiar Endpoint
                </button>
            </div>
        `;
    },

    renderCodeBlock: function(language, code) {
        const escapedCode = this.escapeHtml(code);
        return `
            <div class="ds-comp-codeblock">
                <div class="codeblock-header">
                    <span class="codeblock-lang">${language.toUpperCase()}</span>
                    <button class="codeblock-copy" onclick="dsChatActions.copyText(\`${escapedCode}\`)"><i class="far fa-copy"></i> Copiar</button>
                </div>
                <pre><code>${escapedCode}</code></pre>
            </div>
        `;
    },

    renderButtons: function(actions) {
        if (!actions || actions.length === 0) return '';
        let html = '<div class="ds-comp-buttons">';
        actions.forEach(act => {
            if (act.type === 'navigate') {
                html += `<button class="ds-comp-btn" onclick="dsChatLinks.navigate('${act.url}')">${this.escapeHtml(act.label)}</button>`;
            } else if (act.type === 'action') {
                html += `<button class="ds-comp-btn" onclick="dsChatActions.execute('${act.actionId}')">${this.escapeHtml(act.label)}</button>`;
            }
        });
        html += '</div>';
        return html;
    },

    renderFeedback: function(messageId) {
        return `
            <div class="ds-comp-feedback" id="feedback-${messageId}">
                <button class="ds-comp-btn-outline" onclick="dsChatActions.sendFeedback('${messageId}', 'positive')"><i class="fas fa-thumbs-up"></i> Sim, resolveu</button>
                <button class="ds-comp-btn-outline" onclick="dsChatActions.sendFeedback('${messageId}', 'negative')"><i class="fas fa-thumbs-down"></i> Ainda preciso de ajuda</button>
            </div>
        `;
    },

    escapeHtml: function(text) {
        return text.toString().replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
};
