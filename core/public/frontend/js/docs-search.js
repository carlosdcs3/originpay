// docs-search.js

const searchIndex = [
    {
        title: "Visão Geral",
        description: "Introdução à OriginPay, como funciona e garantias da API.",
        slug: "/docs",
        category: "Introdução",
        keywords: "introdução, api, funcionamento, garantias",
        endpoint: null
    },
    {
        title: "Comece em 5 minutos",
        description: "Guia rápido para criar sua primeira cobrança.",
        slug: "/docs/quickstart",
        category: "Introdução",
        keywords: "quickstart, início rápido, tutorial",
        endpoint: null
    },
    {
        title: "Sandbox e Produção",
        description: "Entenda a diferença entre os ambientes de teste e produção.",
        slug: "/docs/sandbox",
        category: "Introdução",
        keywords: "sandbox, teste, produção, live, chaves",
        endpoint: null
    },
    {
        title: "Criar Pagamento (Referência)",
        description: "Cria uma nova intenção de pagamento (PIX ou Cartão).",
        slug: "/docs/v1/api-reference/create-payment",
        category: "API Reference",
        keywords: "pagamento, payment, criar, post",
        endpoint: "POST /v1/payments"
    },
    {
        title: "Recuperar Pagamento (Referência)",
        description: "Recupera os detalhes de um pagamento existente.",
        slug: "/docs/v1/api-reference/get-payment",
        category: "API Reference",
        keywords: "pagamento, payment, get, buscar, recuperar",
        endpoint: "GET /v1/payments/{id}"
    },
    {
        title: "Criar Payout (Referência)",
        description: "Realiza transferência do seu saldo via PIX ou TED.",
        slug: "/docs/v1/api-reference/create-payout",
        category: "API Reference",
        keywords: "payout, saque, retirada, post",
        endpoint: "POST /v1/payouts"
    },
    {
        title: "Webhook Simulator",
        description: "Gere payloads e assinaturas reais para testar localmente.",
        slug: "/docs/v1/webhooks/simulator",
        category: "Tools",
        keywords: "webhook, simulador, teste, payload, assinatura, hmac",
        endpoint: null
    },
    {
        title: "Developer Resources",
        description: "Baixe SDKs (Node.js, PHP) e coleções do Postman.",
        slug: "/docs/v1/resources",
        category: "Tools",
        keywords: "sdk, nodejs, php, postman, insomnia, download",
        endpoint: null
    },
    {
        title: "API Explorer",
        description: "Monte e visualize requisições HTTP dinamicamente.",
        slug: "/docs/v1/explorer",
        category: "Tools",
        keywords: "explorer, request builder, curl",
        endpoint: null
    },
    {
        title: "Assinatura HMAC",
        description: "Como validar webhooks utilizando HMAC-SHA256.",
        slug: "/docs/hmac",
        category: "Webhooks",
        keywords: "hmac, assinatura, segurança, webhook, validação",
        endpoint: null
    },
    {
        title: "Códigos de Erro",
        description: "Tabela completa de códigos DGK e HTTP status.",
        slug: "/docs/errors",
        category: "Referência",
        keywords: "erro, 400, 422, dgk, status",
        endpoint: null
    }
];

// Elements
const triggers = document.querySelectorAll('#docSearchTrigger');
const modal = document.getElementById('docSearchModal');
const searchInput = document.getElementById('docSearchInputNative');
const resultsContainer = document.getElementById('docSearchResults');

function openSearch() {
    if(modal) {
        modal.style.display = 'flex';
        setTimeout(() => searchInput.focus(), 100);
        renderResults('');
    }
}

function closeSearch() {
    if(modal) {
        modal.style.display = 'none';
        searchInput.value = '';
    }
}

// Attach clicks to all triggers
triggers.forEach(t => t.addEventListener('click', openSearch));

// Keyboard shortcut (Cmd/Ctrl + K)
document.addEventListener('keydown', (e) => {
    if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
        e.preventDefault();
        openSearch();
    }
    if (e.key === 'Escape' && modal && modal.style.display === 'flex') {
        closeSearch();
    }
});

// Close when clicking outside
if(modal) {
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeSearch();
        }
    });
}

function renderResults(query) {
    if(!resultsContainer) return;
    
    query = query.toLowerCase().trim();
    
    if (query === '') {
        resultsContainer.innerHTML = '<div style="padding: 24px; text-align: center; color: var(--doc-muted); font-size: 0.9rem;">Type to search endpoints, guides, and SDKs...</div>';
        return;
    }

    const filtered = searchIndex.filter(item => {
        return item.title.toLowerCase().includes(query) || 
               item.description.toLowerCase().includes(query) ||
               item.keywords.toLowerCase().includes(query) ||
               (item.endpoint && item.endpoint.toLowerCase().includes(query));
    });

    if (filtered.length === 0) {
        resultsContainer.innerHTML = '<div style="padding: 24px; text-align: center; color: var(--doc-muted); font-size: 0.9rem;">No results found for "'+query+'"</div>';
        return;
    }

    let html = '';
    filtered.forEach(item => {
        let endpointBadge = '';
        if(item.endpoint) {
            let method = item.endpoint.split(' ')[0];
            let badgeColor = method === 'GET' ? '#38bdf8' : '#10b981';
            let badgeBg = method === 'GET' ? 'rgba(56, 189, 248, 0.1)' : 'rgba(16, 185, 129, 0.1)';
            endpointBadge = `<div style="font-family: monospace; font-size: 0.75rem; margin-top: 6px;">
                <span style="background: ${badgeBg}; color: ${badgeColor}; padding: 2px 6px; border-radius: 4px; margin-right: 6px;">${method}</span>
                <span style="color: var(--doc-muted);">${item.endpoint.replace(method+' ', '')}</span>
            </div>`;
        }

        html += `
        <a href="${item.slug}" style="display: block; padding: 16px; border-radius: 8px; text-decoration: none; border-bottom: 1px solid rgba(255,255,255,0.05); transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.05)'" onmouseout="this.style.background='transparent'">
            <div style="font-size: 0.75rem; color: var(--doc-primary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px; font-weight: 600;">${item.category}</div>
            <div style="color: #fff; font-weight: 500; font-size: 1.05rem; margin-bottom: 4px;">${item.title}</div>
            <div style="color: var(--doc-muted); font-size: 0.85rem;">${item.description}</div>
            ${endpointBadge}
        </a>
        `;
    });

    resultsContainer.innerHTML = html;
}

if(searchInput) {
    searchInput.addEventListener('input', (e) => {
        renderResults(e.target.value);
    });
}
