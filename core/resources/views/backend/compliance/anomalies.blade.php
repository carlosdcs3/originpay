@extends('backend.layouts.app')
@section('title', 'Anomalias Operacionais')
@section('content')
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 fw-bold">Gestão de Anomalias (Chaves Pix Suspeitas & Picos)</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="bg-body-tertiary">
                    <tr>
                        <th>Identificador</th>
                        <th>Tipo</th>
                        <th>Severidade</th>
                        <th>Data</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($anomalies as $anomaly)
                    <tr>
                        <td colspan="5"></td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center py-4">Nenhuma anomalia detectada recentemente.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
