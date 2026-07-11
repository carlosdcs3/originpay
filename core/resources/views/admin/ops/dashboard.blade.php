@extends('admin.layouts.app')

@section('panel')
<div class="row">
    <div class="col-lg-12">
        <h4 class="mb-4">Monitoramento de Resiliência (Circuit Breakers)</h4>
        <div class="row">
            @foreach($gateways as $gw)
            <div class="col-md-4 mb-4">
                <div class="card bg--@if($gw['circuit_status'] == 'CLOSED') success @elseif($gw['circuit_status'] == 'HALF_OPEN') warning @else danger @endif text-white">
                    <div class="card-body">
                        <h5>{{ $gw['name'] }} ({{ $gw['code'] }})</h5>
                        <h2>Score: {{ $gw['score'] }}</h2>
                        <p>Status do circuito: <strong>{{ $gw['circuit_status'] }}</strong></p>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-lg-12">
        <h4 class="mb-4">Métricas de Fila</h4>
        <div class="card">
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-4">
                        <h3>{{ $horizonStats['queue_size'] }}</h3>
                        <span>Tamanho da fila</span>
                    </div>
                    <div class="col-md-4">
                        <h3>{{ $horizonStats['recent_jobs'] }}</h3>
                        <span>Jobs recentes</span>
                    </div>
                    <div class="col-md-4">
                        <h3 class="text-danger">{{ $horizonStats['failed_jobs'] }}</h3>
                        <span>Jobs com falha</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header bg--dark text-white d-flex justify-content-between align-items-center">
                <h5 class="card-title text-white mb-0">Ações rápidas e recuperação operacional</h5>
                @if($withdrawalsPaused)
                    <span class="badge bg--danger">SAQUES PAUSADOS (KILL SWITCH ATIVO)</span>
                @else
                    <span class="badge bg--success">Fluxo financeiro normal</span>
                @endif
            </div>
            <div class="card-body">
                <p>Em caso de falha sistêmica, consulte os <strong>runbooks</strong>.</p>
                <div class="d-flex flex-wrap gap-3 mt-3">
                    <form action="{{ route('admin.ops.toggle_withdrawals') }}" method="POST" onsubmit="return confirm('Tem certeza? Isso irá ' + ({{ $withdrawalsPaused ? 'true' : 'false' }} ? 'RETOMAR' : 'PAUSAR') + ' o processamento de saques da plataforma.');">
                        @csrf
                        <input type="hidden" name="reason" value="Emergência acionada pelo painel operacional">
                        <button class="btn btn--{{ $withdrawalsPaused ? 'success' : 'danger' }}">
                            {{ $withdrawalsPaused ? 'Retomar saques' : 'Pausar saques (kill switch)' }}
                        </button>
                    </form>

                    <form action="{{ route('admin.ops.run_reconciliation') }}" method="POST">
                        @csrf
                        <button class="btn btn--primary">
                            Enfileirar reconciliação
                        </button>
                    </form>

                    <form action="{{ route('admin.ops.verify_ledger') }}" method="POST">
                        @csrf
                        <button class="btn btn--warning text-white">
                            Verificar integridade do ledger
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
