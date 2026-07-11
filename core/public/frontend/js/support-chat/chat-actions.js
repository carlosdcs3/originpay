/**
 * chat-actions.js
 * Handles user actions originating from chat buttons and menus.
 */

window.dsChatActions = {
    
    copyText: function(text) {
        navigator.clipboard.writeText(text);
        // Em um sistema real, poderia disparar um Toast "Copiado com sucesso!"
        alert("Copiado para a área de transferência!");
    },

    execute: function(actionId) {
        switch (actionId) {
            case 'open_ticket':
                // Transition to Human Ticket flow
                window.dsChatUI.addAdminMessage("Entendido. Criando seu ticket de suporte...");
                setTimeout(() => {
                    window.dsChatUI.addAdminMessage("Ticket #8943 criado com sucesso. Um especialista assumirá o atendimento em breve.");
                }, 1500);
                break;
            default:
                console.log("Ação não definida:", actionId);
        }
    },

    sendFeedback: function(messageId, type) {
        const feedbackBox = document.getElementById('feedback-' + messageId);
        if (!feedbackBox) return;

        if (type === 'positive') {
            feedbackBox.innerHTML = '<div class="ds-feedback-success"><i class="fas fa-check-circle"></i> Obrigado pelo feedback!</div>';
        } else {
            // Negative feedback opens human overflow
            feedbackBox.innerHTML = `
                <div class="ds-feedback-overflow">
                    <p>Sinto muito por não ajudar. Deseja falar com um humano?</p>
                    <button class="ds-comp-btn" onclick="dsChatActions.execute('open_ticket')">Abrir Ticket</button>
                    <button class="ds-comp-btn-outline" onclick="document.getElementById('feedback-${messageId}').style.display='none'">Continuar conversando</button>
                </div>
            `;
        }
    }
};
