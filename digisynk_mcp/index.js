const { Server } = require("@modelcontextprotocol/sdk/server/index.js");
const { StdioServerTransport } = require("@modelcontextprotocol/sdk/server/stdio.js");
const {
  CallToolRequestSchema,
  ListToolsRequestSchema,
} = require("@modelcontextprotocol/sdk/types.js");
const axios = require("axios");

const API_KEY = process.env.DIGISYNK_API_KEY;
const API_URL = process.env.DIGISYNK_API_URL || "http://localhost:8000/api/v1";

if (!API_KEY) {
  console.error("ERRO: DIGISYNK_API_KEY não configurada no ambiente.");
  process.exit(1);
}

const client = axios.create({
  baseURL: API_URL,
  headers: {
    Authorization: `Bearer ${API_KEY}`,
    "Content-Type": "application/json",
  },
});

const server = new Server(
  {
    name: "digisynk-mcp",
    version: "1.0.0",
  },
  {
    capabilities: {
      tools: {},
    },
  }
);

// Registrar as Ferramentas (Tools) da Digisynk baseadas na API V1 Oficial
server.setRequestHandler(ListToolsRequestSchema, async () => {
  return {
    tools: [
      {
        name: "create_payment",
        description: "Gera uma nova cobrança PIX ou Cartão usando a API V1 da Digisynk.",
        inputSchema: {
          type: "object",
          properties: {
            amount: { type: "number", description: "Valor em BRL" },
            method: { type: "string", description: "pix ou credit_card" },
            customer_name: { type: "string" },
            customer_email: { type: "string" },
            idempotency_key: { type: "string", description: "Chave única para evitar dupla cobrança" }
          },
          required: ["amount", "method"],
        },
      },
      {
        name: "check_balance",
        description: "Verifica o saldo disponível na conta Digisynk via API V1.",
        inputSchema: {
          type: "object",
          properties: {},
        },
      }
    ],
  };
});

server.setRequestHandler(CallToolRequestSchema, async (request) => {
  try {
    if (request.params.name === "create_payment") {
      const { amount, method, customer_name, customer_email, idempotency_key } = request.params.arguments;
      
      const payload = {
        amount,
        method,
        customer: { name: customer_name, email: customer_email }
      };

      const headers = {};
      if (idempotency_key) {
        headers["Idempotency-Key"] = idempotency_key;
      }

      const response = await client.post("/payments", payload, { headers });

      return {
        content: [{ type: "text", text: JSON.stringify(response.data, null, 2) }],
      };
    }

    if (request.params.name === "check_balance") {
      const response = await client.get("/balance");
      return {
        content: [{ type: "text", text: JSON.stringify(response.data, null, 2) }],
      };
    }

    throw new Error(`Tool unknown: ${request.params.name}`);

  } catch (error) {
    const errorData = error.response ? error.response.data : error.message;
    return {
      content: [{ type: "text", text: `Erro na API da Digisynk:\n${JSON.stringify(errorData, null, 2)}` }],
      isError: true,
    };
  }
});

// Inicialização do Servidor MCP
async function run() {
  const transport = new StdioServerTransport();
  await server.connect(transport);
  console.error("Digisynk MCP Server rodando na Stdio (Consumindo API V1 Oficial).");
}

run().catch((error) => {
  console.error("MCP Fatal Error:", error);
});
