@extends('backend.layouts.app')
@section('title', 'Gestão de Whitelist')
@section('content')
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 fw-bold">Whitelist (Bypass de Risco)</h5>
    </div>
    <div class="card-body">
        <p class="text-muted">Lista de chaves isentas de bloqueios automáticos temporários.</p>
        <div class="alert alert-info border-0 mt-4">
            Nenhum motor de exclusão automática ou bypass de risco está configurado nesta tela no ambiente atual.
        </div>
    </div>
</div>
@endsection
