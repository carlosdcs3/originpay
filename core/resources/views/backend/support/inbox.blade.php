@extends('backend.layouts.app')
@section('title', 'Inbox (Conversas)')
@section('content')
<div class="row h-100">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold">Caixa de Entrada</h5>
            </div>
            <div class="list-group list-group-flush">
                <div class="list-group-item text-center py-4 text-muted">
                    Nenhuma conversa ativa no momento.
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card border-0 shadow-sm h-100 d-flex align-items-center justify-content-center bg-light">
            <div class="text-center text-muted">
                <i class="cil-chat-bubble fs-1 mb-3"></i>
                <p>Selecione uma conversa para iniciar o atendimento.</p>
                <small>As antigas interfaces de 'Tickets' foram depreciadas a favor da nova Central de Atendimento Real-Time.</small>
            </div>
        </div>
    </div>
</div>
@endsection
