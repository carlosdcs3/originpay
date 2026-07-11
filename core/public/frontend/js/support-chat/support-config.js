/**
 * support-config.js
 * Configuration variables and global constants for the Support Chat.
 */

window.DS_CHAT_CONFIG = {
    csrfToken: document.querySelector('meta[name="csrf-token"]')?.content || '',
    avatarIA: '/frontend/images/digikash-logo.png',
    avatarUser: 'fas fa-user', // Class fallback if no image
    botName: 'Assistente OriginPay AI',
    minTypingDelay: 900,
    maxTypingDelay: 1600,
    endpoints: {
        state: '/user/support-chat/state',
        conversation: '/user/support-chat/conversations/',
        send: '/user/support-chat/send', // Backend endpoint
        messages: '/user/support-chat/messages', // History endpoint fallback
        docs: '/docs',
        dashboard: '/user/dashboard'
    }
};

window.dsChatState = {
    isOpen: false,
    isTyping: false,
    hasInitialized: false,
    activePopup: null,
    pendingAttachment: null,
    activeConversationId: null,
    pollingInterval: null
};
