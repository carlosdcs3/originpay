@extends('backend.operations.index')
@section('operations_title', 'Status Público')
@section('operations_desc', 'Acompanhe o estado público dos serviços monitorados pela plataforma.')

@section('operations_content')
<div class="row">
    <div class="col-12">
        <x-ds.card class="border-0 shadow-sm rounded-3">
            <div class="card-body p-0 text-center" style="background-color: transparent !important;">
                <div class="py-5 px-3" style="min-height: 250px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                    <div class="rounded-circle d-flex justify-content-center align-items-center mb-3 border shadow-sm" style="width: 60px; height: 60px; background: var(--ds-surface-hover, rgba(0,0,0,0.02)); border-color: var(--ds-border) !important;">
                        <i class="la la-server fs-2" style="color: var(--ds-text-muted);"></i>
                    </div>
                    <h5 class="fw-bold mb-2" style="color: var(--ds-heading);">Monitoramento indisponível</h5>
                    <p class="text-muted max-w-sm mb-4" style="color: var(--ds-text-muted);">As integrações de infraestrutura ainda não foram configuradas no ambiente atual.</p>
                    <a href="{{ route('admin.payment.gateway.index') }}" class="btn btn-outline-secondary btn-sm" style="border-color: var(--ds-border); color: var(--ds-text-secondary); background: transparent;">
                        <i class="la la-cog me-1"></i> Ver integrações
                    </a>
                </div>
            </div>
        </x-ds.card>
    </div>
</div>
@endsection
