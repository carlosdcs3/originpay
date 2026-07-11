@extends('backend.layouts.app')
@section('title', 'Auditoria de Compliance')
@section('content')
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 fw-bold">Auditoria de Compliance</h5>
    </div>
    <div class="card-body">
        <p class="text-muted">Registro consolidado de anomalias, bloqueios e flags de risco levantadas contra clientes da infraestrutura.</p>
        <div class="alert alert-info border-0 mt-4">
            Nenhum pipeline automatizado de dossiês ou exportações regulatórias está configurado nesta tela no ambiente atual.
        </div>
    </div>
</div>
@endsection
