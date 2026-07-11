@extends('backend.marketing.index')
@section('marketing_title', 'Campanhas')
@section('marketing_desc', 'Acompanhe campanhas e comunicações outbound disponíveis no ambiente atual.')

@section('marketing_content')
<div class="row">
    <div class="col-12">
        <x-ds.card class="border-0 shadow-sm rounded-3 overflow-hidden" style="background-color: var(--ds-surface) !important; --bs-card-bg: var(--ds-surface); border-color: rgba(255,255,255,0.06);">
            <div class="card-body p-0 text-center" style="background-color: transparent !important;">
                <div class="py-5 px-3" style="min-height: 280px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                    <div class="rounded-circle d-flex justify-content-center align-items-center mb-3 border shadow-sm" style="width: 60px; height: 60px; background: var(--ds-surface-hover, rgba(0,0,0,0.02));">
                        <i class="la la-bullhorn fs-2" style="color: var(--ds-text-muted);"></i>
                    </div>
                    <h4 class="fw-bold mb-2">Nenhuma campanha registrada</h4>
                    <p class="text-muted max-w-sm mb-0">O cadastro e disparo de campanhas não estão conectados neste ambiente. Quando houver backend ativo para esse módulo, as campanhas reais aparecerão aqui.</p>
                </div>
            </div>
        </x-ds.card>
    </div>
</div>
@endsection
