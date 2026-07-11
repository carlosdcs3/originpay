<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Erro Interno do Servidor</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --ds-bg: #000000;
            --ds-text: #ededed;
            --ds-text-muted: #a1a1aa;
            --ds-border: #27272a;
            --ds-danger: #ef4444;
            --ds-font-sans: 'Inter', sans-serif;
            --ds-font-mono: 'JetBrains Mono', monospace;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background-color: var(--ds-bg);
            color: var(--ds-text);
            font-family: var(--ds-font-sans);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            line-height: 1.5;
        }
        .error-container {
            max-width: 480px;
            width: 100%;
            padding: 2rem;
            text-align: center;
        }
        .error-code {
            font-size: 6rem;
            font-weight: 700;
            line-height: 1;
            margin-bottom: 1rem;
            color: var(--ds-danger);
            letter-spacing: -0.05em;
        }
        .error-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .error-desc {
            color: var(--ds-text-muted);
            margin-bottom: 2rem;
        }
        .error-meta {
            font-family: var(--ds-font-mono);
            font-size: 0.75rem;
            color: var(--ds-text-muted);
            background: rgba(255,255,255,0.05);
            padding: 1rem;
            border-radius: 8px;
            border: 1px solid var(--ds-border);
            margin-bottom: 2rem;
            text-align: left;
        }
        .error-meta div {
            margin-bottom: 0.25rem;
        }
        .error-meta div:last-child {
            margin-bottom: 0;
        }
        .actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            font-weight: 500;
            border-radius: 6px;
            text-decoration: none;
            transition: all 0.2s;
            cursor: pointer;
            border: 1px solid transparent;
        }
        .btn-primary {
            background: #ffffff;
            color: #000000;
        }
        .btn-primary:hover {
            background: #f4f4f5;
        }
        .btn-secondary {
            background: transparent;
            color: var(--ds-text);
            border-color: var(--ds-border);
        }
        .btn-secondary:hover {
            background: rgba(255,255,255,0.05);
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code">500</div>
        <div class="error-title">Erro Interno do Servidor</div>
        <div class="error-desc">Ocorreu um problema inesperado do nosso lado. Nossa equipe já foi notificada.</div>

        <div class="error-meta">
            <div><span style="opacity:0.5;">Timestamp:</span> {{ now()->toIso8601String() }}</div>
            @if(request()->header('X-OriginPay-Request-Id'))
                <div><span style="opacity:0.5;">Request ID:</span> {{ request()->header('X-OriginPay-Request-Id') }}</div>
            @endif
        </div>

        <div class="actions">
            <button onclick="window.history.back()" class="btn btn-secondary">Voltar</button>
            <button onclick="window.location.reload()" class="btn btn-primary">Tentar novamente</button>
        </div>
    </div>
</body>
</html>
