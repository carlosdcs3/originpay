/**
 * support-ai.js
 * The intelligence engine. Responsible for:
 * - Finding intent
 * - Building the standardized payload
 * - Returning payload to the UI
 */

window.dsSupportAI = {
    
    /**
     * Simulates sending a message to the AI and getting a payload back.
     * In the future, this calls an LLM or backend RAG system.
     */
    processMessage: function(messageText, context) {
        return new Promise((resolve) => {
            const delay = Math.floor(Math.random() * (window.DS_CHAT_CONFIG.maxTypingDelay - window.DS_CHAT_CONFIG.minTypingDelay + 1)) + window.DS_CHAT_CONFIG.minTypingDelay;
            
            setTimeout(() => {
                const intent = this.findIntent(messageText);
                const payload = this.buildPayload(intent, messageText, context);
                resolve(payload);
            }, delay);
        });
    },

    findIntent: function(text) {
        const lowerText = text.toLowerCase();
        
        let bestMatch = null;
        let highestConfidence = 0;

        // Extremely simple keyword/alias matcher for the mock
        window.DS_KNOWLEDGE_BASE.forEach(kb => {
            let score = 0;
            
            // Check aliases
            kb.aliases.forEach(alias => {
                if (lowerText.includes(alias.toLowerCase())) score += 0.8;
            });

            // Check keywords
            kb.keywords.forEach(kw => {
                if (lowerText.includes(kw.toLowerCase())) score += 0.2;
            });

            if (score > highestConfidence) {
                highestConfidence = score;
                bestMatch = kb;
            }
        });

        if (highestConfidence > 0.3 && bestMatch) {
            return bestMatch;
        }
        
        return null;
    },

    buildPayload: function(intent, userMessage, context) {
        const lowerMsg = userMessage.toLowerCase();
        
        // Define human support keywords
        const supportKeywords = ['atendente', 'suporte', 'ajuda', 'humano', 'especialista', 'pessoa', 'falar com'];
        const needsHuman = supportKeywords.some(kw => lowerMsg.includes(kw));

        let payload = null;

        if (intent && !needsHuman) {
            payload = {
                id: 'msg-' + Date.now(),
                type: 'message',
                sender: 'admin',
                timestamp: new Date().toISOString(),
                confidence: intent.confidence,
                source: 'knowledge_base',
                title: 'Base de conhecimento',
                markdown: intent.answer,
                components: intent.components || [],
                actions: intent.actions || [],
                related_articles: intent.related || [],
                feedback_required: true
            };
        } else if (needsHuman || (intent && intent.id === 'kb-human-01')) {
            if (window.dsChatState.humanRequested) {
                // Do not send the same message again.
                return null;
            } else {
                window.dsChatState.humanRequested = true;
                payload = {
                    id: 'msg-' + Date.now(),
                    type: 'message',
                    sender: 'admin',
                    timestamp: new Date().toISOString(),
                    confidence: 1,
                    source: 'ai_fallback',
                    title: null,
                    markdown: "Vou transferir seu atendimento para um de nossos especialistas. Por favor, detalhe seu problema para que ele já inicie o atendimento com o contexto necessário.",
                    components: [],
                    actions: [],
                    related_articles: [],
                    feedback_required: false
                };
            }
        } else {
            payload = {
                id: 'msg-' + Date.now(),
                type: 'message',
                sender: 'admin',
                timestamp: new Date().toISOString(),
                confidence: 0,
                source: 'ai_fallback',
                title: null,
                markdown: "Desculpe, não consegui encontrar uma resposta exata para sua dúvida. Você pode tentar perguntar de outra forma ou, se preferir, pedir para falar com um atendente.",
                components: [],
                actions: [],
                related_articles: [],
                feedback_required: false
            };
            
            if (lowerMsg.includes('pix')) {
                payload.components.push({ type: 'action_cards', items: ["Ver limites PIX", "Cobrança PIX"] });
            }
        }

        return payload;
    }
};
