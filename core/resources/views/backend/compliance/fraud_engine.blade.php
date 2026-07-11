@extends('backend.layouts.app')
@section('title', 'Motor Antifraude')
@section('content')
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 fw-bold">Regras Antifraude Automatizadas</h5>
    </div>
    <div class="card-body">
        <p class="text-muted">Configure integrações de prevenção a fraude e regras locais de bloqueio de transação.</p>
        <div class="alert alert-info border-0 mt-4">
            Nenhum fluxo automático de cancelamento ou score externo está configurado nesta tela no ambiente atual.
        </div>
    </div>
</div>
@endsection
