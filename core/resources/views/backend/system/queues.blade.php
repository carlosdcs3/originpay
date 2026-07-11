@extends('backend.operations.index')
@section('operations_title', 'Agendador')
@section('operations_desc', 'Central de tarefas agendadas da plataforma.')

@section('operations_content')
<div class="row">
    <div class="col-12">
        <x-ds.card class="border-0 shadow-sm rounded-3">
            <div class="card-body p-0 text-center">
                <div class="py-5 px-3" style="min-height: 400px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                    <div class="rounded-circle d-flex justify-content-center align-items-center mb-3 border shadow-sm" style="width: 80px; height: 80px; background: var(--ds-surface-hover, rgba(0,0,0,0.02));">
                        <i class="la la-clock fs-1" style="color: var(--ds-text-muted);"></i>
                    </div>
                    <h4 class="fw-bold mb-2">Nenhuma tarefa monitorada</h4>
                    <p class="text-muted max-w-sm mb-0">O monitoramento do Agendador poderá ser habilitado futuramente.</p>
                </div>
            </div>
        </x-ds.card>
    </div>
</div>
@endsection
