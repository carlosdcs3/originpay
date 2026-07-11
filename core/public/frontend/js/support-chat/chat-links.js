/**
 * chat-links.js
 * Responsible for smart linking, routing, and navigating.
 */

window.dsChatLinks = {
    
    navigate: function(url) {
        // In a real app this might use Turbolinks, Livewire navigate, or just window.location
        window.location.href = url;
    },

    linkifyKeywords: function(text) {
        // Smart Links mapping (Keyword -> URL)
        const linksMap = {
            "API & Integrações": "/user/integrations",
            "Webhooks": "/user/webhooks",
            "Sandbox": "/user/sandbox",
            "Billing": "/user/billing",
            "SDK": "/docs/sdk",
            "OpenAPI": "/docs/openapi",
            "MCP": "/docs/mcp"
        };

        let result = text;
        for (const [keyword, url] of Object.entries(linksMap)) {
            // Use regex to replace exact matches (case-sensitive for exact terms, or insensitive depending on need)
            const regex = new RegExp(`\\b(${keyword})\\b`, 'gi');
            result = result.replace(regex, `<a href="${url}" class="ds-smart-link">$1</a>`);
        }

        // Also parse basic markdown bold and newlines
        result = result.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        result = result.replace(/\n/g, '<br>');

        return result;
    }
};
