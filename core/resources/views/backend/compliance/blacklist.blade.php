@extends('backend.layouts.app')
@section('title', 'Gestão de Blacklist')
@section('content')
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 fw-bold">Blacklist (Restrições)</h5>
    </div>
    <div class="card-body">
        <p class="text-muted">Lista global de CPFs, CNPJs, e-mails e IPs explicitamente bloqueados de transacionar na infraestrutura.</p>
        <div class="alert alert-info border-0 mt-4">
            Nenhuma rotina de importação em lote ou integração externa de listas restritivas está conectada nesta tela.
        </div>
    </div>
</div>
@endsection
