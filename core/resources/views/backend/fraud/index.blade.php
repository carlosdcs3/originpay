@extends('backend.layouts.app')

@section('title', 'Inteligência Antifraude')

@section('content')
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h5 class="card-title">Risco LOW</h5>
                <h2>{{ $metrics['LOW'] }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-dark">
            <div class="card-body">
                <h5 class="card-title">Risco MEDIUM</h5>
                <h2>{{ $metrics['MEDIUM'] }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-danger text-white">
            <div class="card-body">
                <h5 class="card-title">Risco HIGH</h5>
                <h2>{{ $metrics['HIGH'] }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark text-white">
            <div class="card-body">
                <h5 class="card-title">Risco CRITICAL</h5>
                <h2>{{ $metrics['CRITICAL'] }}</h2>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Últimos Eventos de Fraude</h4>
            </div>
            <div class="card-body table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Usuário</th>
                            <th>Tipo (Gatilho)</th>
                            <th>Severidade</th>
                            <th>Metadados (Masc.)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($latestEvents as $event)
                        <tr>
                            <td>{{ $event->created_at->format('d/m/Y H:i:s') }}</td>
                            <td>UID #{{ $event->user_id }}</td>
                            <td>{{ $event->type }}</td>
                            <td><span class="badge badge-{{ $event->severity == 'CRITICAL' ? 'danger' : 'warning' }}">{{ $event->severity }}</span></td>
                            <td><pre>{{ json_encode($event->metadata, JSON_PRETTY_PRINT) }}</pre></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Atenção Especial</h4>
            </div>
            <div class="card-body">
                <p><strong>Device Farms (Dispositivos Compartilhados):</strong> {{ $sharedDevicesCount }} detectados.</p>
                <p class="text-muted small">Dispositivos com 2 ou mais usuários distintos registrados via Hash Hmac passivo.</p>
            </div>
        </div>
    </div>
</div>
@endsection
