@extends('backend.marketing.index')
@section('marketing_title', 'Landing Pages')
@section('marketing_desc', 'Gerencie páginas públicas da OriginPay.')

@section('marketing_action')
    @can('page-create')
        <a href="{{ route('admin.page.site.create') }}" class="btn btn-primary shadow-sm">
            <i class="la la-plus me-1"></i> Nova Landing
        </a>
    @endcan
@endsection

@section('marketing_content')

<x-ds.card class="border-0 shadow-sm rounded-3 overflow-hidden" style="background-color: var(--ds-surface) !important; --bs-card-bg: var(--ds-surface); border-color: rgba(255,255,255,0.06);">
    <div class="card-body p-0" style="background-color: transparent !important;">
        <div class="table-responsive" style="background-color: transparent !important;">
                    <table class="table table-hover align-middle mb-0" style="background-color: transparent !important; color: var(--ds-text); --bs-table-bg: transparent; --bs-table-color: var(--ds-text); --bs-table-hover-bg: rgba(255,255,255,0.04);">
                <thead class="border-bottom" style="background: var(--ds-surface-hover, rgba(0,0,0,0.02));">
                <tr>
                    <th class="text-muted fw-semibold text-uppercase" style="font-size: var(--ds-text-xs, 0.75rem); padding: 1rem;">Nome</th>
                    <th class="text-muted fw-semibold text-uppercase" style="font-size: var(--ds-text-xs, 0.75rem); padding: 1rem;">URL</th>
                    <th class="text-muted fw-semibold text-uppercase" style="font-size: var(--ds-text-xs, 0.75rem); padding: 1rem;">Status</th>
                    <th class="text-muted fw-semibold text-uppercase" style="font-size: var(--ds-text-xs, 0.75rem); padding: 1rem;">Última atualização</th>
                    <th class="text-muted fw-semibold text-uppercase text-end" style="font-size: var(--ds-text-xs, 0.75rem); padding: 1rem;">Ações</th>
                </tr>
                </thead>
                <tbody style="background-color: transparent !important;">
                @forelse($pages as $page)
                    <tr style="background-color: transparent !important;">
                        <td class="fw-semibold text-start px-4" style="background-color: transparent !important;">
                            <div class="text-nowrap fw-bold">{{ $page->label }}</div>
                        </td>
                        
                        <td class="text-nowrap" style="background-color: transparent !important;">
                            <a href="{{ url($page->slug === '/' ? '/' : '/' . ltrim($page->slug, '/')) }}" target="_blank"
                               class="fw-semibold text-primary text-decoration-none">
                                {{ $page->slug === '/' ? '/' : '/' . ltrim($page->slug, '/') }}
                            </a>
                        </td>
                        
                        <td style="background-color: transparent !important;">
                            @if($page->is_active)
                                <span class="badge bg-success bg-opacity-10 text-success border border-success">Ativa</span>
                            @else
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger">Inativa</span>
                            @endif
                        </td>
                        
                        <td class="text-muted small" style="background-color: transparent !important;">
                            {{ $page->updated_at ? $page->updated_at->format('d/m/Y H:i') : '--' }}
                        </td>
                        
                        <td class="text-end px-4" style="background-color: transparent !important;">
                            <div class="d-flex justify-content-end gap-2">
                                @can('page-edit')
                                    <a href="{{ route('admin.page.site.edit', $page->id) }}" class="btn btn-sm btn-outline-primary">
                                        Editar
                                    </a>
                                @endcan
                                
                                @if($page->type === \App\Enums\PageType::Dynamic)
                                    @can('page-delete')
                                        <a href="javascript:void(0)" data-url="{{ route('admin.page.site.destroy', $page->id) }}" class="btn btn-sm btn-outline-danger delete">
                                            Excluir
                                        </a>
                                    @endcan
                                @else
                                    <button disabled class="btn btn-sm btn-outline-secondary">
                                        <i class="la la-lock me-1"></i> Protegida
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr style="background-color: transparent !important;">
                        <td colspan="5" class="text-center p-0" style="background-color: transparent !important;">
                            <div class="py-4 px-3" style="min-height: 200px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                                <div class="rounded-circle d-flex justify-content-center align-items-center mb-3 border shadow-sm" style="width: 60px; height: 60px; background: var(--ds-surface-hover, rgba(0,0,0,0.02));">
                                    <i class="la la-file-alt fs-2" style="color: var(--ds-text-muted);"></i>
                                </div>
                                <h4 class="fw-bold mb-2">Nenhuma landing page criada.</h4>
                                @can('page-create')
                                    <a href="{{ route('admin.page.site.create') }}" class="btn btn-primary mt-3 shadow-sm">
                                        <i class="la la-plus me-1"></i> Nova Landing
                                    </a>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-ds.card>
@endsection

@push('modal')
    @include('backend.partials._delete_modal')
@endpush
