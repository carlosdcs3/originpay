<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OriginPay - Status do Sistema</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #0f172a;
            color: #f8fafc;
            margin: 0;
            padding: 50px 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            margin-bottom: 50px;
        }
        .status-badge {
            display: inline-block;
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
            padding: 10px 20px;
            border-radius: 50px;
            font-weight: 600;
            margin-top: 20px;
            border: 1px solid rgba(16, 185, 129, 0.5);
        }
        .card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .service-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #334155;
        }
        .service-row:last-child {
            border-bottom: none;
        }
        .service-name {
            font-weight: 500;
            font-size: 1.1rem;
        }
        .service-status {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .status-dot {
            width: 10px;
            height: 10px;
            background: #10b981;
            border-radius: 50%;
            box-shadow: 0 0 10px #10b981;
        }
        .footer {
            text-align: center;
            margin-top: 50px;
            color: #64748b;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>OriginPay Status</h1>
        <div class="status-badge">
            <i class="fas fa-check-circle mr-2"></i> Todos os sistemas operacionais
        </div>
    </div>

    <div class="card">
        <h3 style="margin-top: 0; color: #94a3b8; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px;">Status dos Serviços</h3>
        
        @foreach($services as $service)
        <div class="service-row">
            <div class="service-name">{{ $service['name'] }}</div>
            <div class="service-status">
                <span style="color: #94a3b8; font-size: 0.9rem; margin-right: 15px;">Uptime: {{ $service['uptime'] }}</span>
                <span class="status-dot"></span>
                <span style="color: #10b981;">Operacional</span>
            </div>
        </div>
        @endforeach
    </div>

    <div class="footer">
        &copy; {{ date('Y') }} OriginPay Gateway. Atualizado automaticamente a cada minuto.
    </div>
</div>

</body>
</html>
