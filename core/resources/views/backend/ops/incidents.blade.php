@extends('backend.operations.index')
@section('operations_title', 'Incidentes')
@section('operations_desc', 'Acompanhamento e histórico de falhas operacionais e intervenções do sistema.')

@section('operations_action')
    <a href="{{ route('admin.operations.command') }}" class="btn btn-outline-primary shadow-sm">
        <i class="la la-arrow-left me-1"></i> Voltar ao Centro
    </a>
@endsection

@section('operations_content')

<div class="row">
    <div class="col-12">
        <x-ds.card class="border-0 shadow-sm rounded-3">
            <div class="card-body p-0 text-center" style="background-color: transparent !important;">
                <div class="py-5 px-3" style="min-height: 400px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                    <div class="rounded-circle d-flex justify-content-center align-items-center mb-3 border shadow-sm" style="width: 80px; height: 80px; background: var(--ds-surface-hover, rgba(0,0,0,0.02));">
                        <i class="la la-shield fs-1" style="color: var(--ds-text-muted);"></i>
                    </div>
                    <h4 class="fw-bold mb-2">Nenhum incidente registrado.</h4>
                    <p class="text-muted max-w-sm mb-0">Quando ocorrer uma falha operacional ou intervenção manual, ela aparecerá aqui para acompanhamento.</p>
                </div>
            </div>
        </x-ds.card>
    </div>
</div>
@endsection
