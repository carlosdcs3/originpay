@extends('backend.marketing.index')
@section('marketing_title', 'Modelos de E-mail')
@section('marketing_desc', 'Gerencie os modelos transacionais reais cadastrados na plataforma.')

@section('marketing_content')
<x-ds.card class="border-0 shadow-sm rounded-3 overflow-hidden" style="background-color: var(--ds-surface) !important; --bs-card-bg: var(--ds-surface); border-color: rgba(255,255,255,0.06);">
    <div class="card-body p-0" style="background-color: transparent !important;">
        <div class="table-responsive" style="background-color: transparent !important;">
            <table class="table table-hover align-middle mb-0" style="background-color: transparent !important; color: var(--ds-text); --bs-table-bg: transparent; --bs-table-color: var(--ds-text); --bs-table-hover-bg: rgba(255,255,255,0.04);">
                <thead class="border-bottom" style="background: var(--ds-surface-hover, rgba(0,0,0,0.02));">
                    <tr>
                        <th class="text-muted fw-semibold text-uppercase" style="font-size: var(--ds-text-xs, 0.75rem); padding: 1rem;">Nome</th>
                        <th class="text-muted fw-semibold text-uppercase" style="font-size: var(--ds-text-xs, 0.75rem); padding: 1rem;">Categoria</th>
                        <th class="text-muted fw-semibold text-uppercase" style="font-size: var(--ds-text-xs, 0.75rem); padding: 1rem;">Idioma</th>
                        <th class="text-muted fw-semibold text-uppercase" style="font-size: var(--ds-text-xs, 0.75rem); padding: 1rem;">Última edição</th>
                        <th class="text-muted fw-semibold text-uppercase" style="font-size: var(--ds-text-xs, 0.75rem); padding: 1rem;">Status</th>
                        <th class="text-muted fw-semibold text-uppercase text-end" style="font-size: var(--ds-text-xs, 0.75rem); padding: 1rem;">Ações</th>
                    </tr>
                </thead>
                <tbody style="background-color: transparent !important;">
                    @forelse($notifyTemplates as $template)
                        <tr>
                            <td class="fw-semibold text-start px-4">
                                <div class="fw-bold">{{ $template->name }}</div>
                                <div class="small text-muted">{{ $template->info }}</div>
                            </td>
                            <td><span class="badge bg-light text-dark border">Transacional</span></td>
                            <td class="text-muted">PT-BR</td>
                            <td class="text-muted small">{{ $template->updated_at ? $template->updated_at->format('d/m/Y H:i') : '—' }}</td>
                            <td><span class="badge bg-success bg-opacity-10 text-success border border-success">Ativo</span></td>
                            <td class="text-end px-4">
                                @can('notification-template-manage')
                                    <a href="{{ route('admin.notifications.template.edit', $template->id) }}" class="btn btn-sm btn-outline-primary">
                                        Editar
                                    </a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center p-0">
                                <div class="py-4 px-3" style="min-height: 220px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                                    <div class="rounded-circle d-flex justify-content-center align-items-center mb-3 border shadow-sm" style="width: 60px; height: 60px; background: var(--ds-surface-hover, rgba(0,0,0,0.02));">
                                        <i class="la la-envelope-open fs-2" style="color: var(--ds-text-muted);"></i>
                                    </div>
                                    <h4 class="fw-bold mb-2">Nenhum modelo cadastrado</h4>
                                    <p class="text-muted max-w-sm mb-0">Os modelos transacionais aparecerão aqui quando forem cadastrados pela plataforma.</p>
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
