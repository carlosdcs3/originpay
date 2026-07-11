/**
 * knowledge-base.js
 * Isolates the mocked knowledge base.
 * In the future, this will be replaced by API calls to an LLM or RAG pipeline.
 */

window.DS_KNOWLEDGE_BASE = [
    {
        id: "kb-webhook-01",
        title: "Configurar Webhook",
        category: "developers",
        keywords: ["webhook", "webhooks", "eventos", "notificação", "post"],
        aliases: ["Como configurar webhook?", "Receber eventos na minha API"],
        answer: "Para configurar um webhook:\n1. Acesse **API & Integrações**.\n2. Abra a seção **Webhooks**.\n3. Cadastre a URL que receberá os eventos.\n4. Salve a configuração.\n\nA OriginPay envia requisições HTTP POST em JSON e assina todos os eventos utilizando HMAC SHA-256.",
        components: [
            { type: "action_cards", items: ["POST JSON", "Retry Automático", "HMAC SHA-256", "Sandbox"] },
            { 
                type: "code_block", 
                language: "json", 
                code: "{\n  \"event\": \"payment.success\",\n  \"data\": {\n    \"id\": \"evt_1234\",\n    \"amount\": 100.00\n  }\n}" 
            }
        ],
        actions: [
            { label: "Abrir API & Integrações", type: "navigate", url: "/user/integrations" },
            { label: "Ver Documentação", type: "navigate", url: "/docs/webhooks" }
        ],
        related: ["kb-api-key-01", "kb-webhook-02"],
        confidence: 0.95
    },
    {
        id: "kb-pix-01",
        title: "Criar Cobrança PIX",
        category: "payments",
        keywords: ["pix", "cobrança", "qrcode", "receber pix"],
        aliases: ["Como criar uma cobrança PIX?", "Gerar QRCode PIX"],
        answer: "Você pode gerar uma cobrança PIX via Painel ou via API.\nPara gerar via painel, acesse **Cobranças > Nova Cobrança** e selecione o método PIX. Se desejar usar a API, envie um POST para o nosso endpoint.",
        components: [
            { type: "endpoint_card", method: "POST", path: "/v1/charges/pix" }
        ],
        actions: [
            { label: "Nova Cobrança", type: "navigate", url: "/user/charges/create" },
            { label: "Documentação PIX API", type: "navigate", url: "/docs/pix" }
        ],
        related: ["kb-webhook-01"],
        confidence: 0.92
    },
    {
        id: "kb-api-key-01",
        title: "Gerar API Key",
        category: "developers",
        keywords: ["api", "key", "token", "autenticação", "credenciais"],
        aliases: ["Como gerar uma API Key?", "Onde pego meu token?"],
        answer: "As chaves de API (Tokens) ficam na seção de Desenvolvedores. Nunca compartilhe sua *Secret Key*.",
        components: [],
        actions: [
            { label: "Gerenciar API Keys", type: "navigate", url: "/user/api-keys" }
        ],
        related: ["kb-webhook-01"],
        confidence: 0.88
    },
    {
        id: "kb-human-01",
        title: "Suporte Humano",
        category: "support",
        keywords: ["humano", "atendente", "falar com pessoa", "suporte"],
        aliases: ["Quero falar com suporte humano", "Alguém pode me ajudar?"],
        answer: "Vou transferir seu atendimento para um de nossos especialistas. Por favor, detalhe seu problema para que ele já inicie o atendimento com o contexto necessário.",
        components: [],
        actions: [
            { label: "Abrir Ticket", type: "action", actionId: "open_ticket" }
        ],
        related: [],
        confidence: 1.0
    }
];
