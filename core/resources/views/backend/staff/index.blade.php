@extends('backend.settings.layout')
@section('setting_title', 'Equipe')

@section('setting_action')
    @can('staff-create')
        <a href="#new_staff_modal" data-coreui-toggle="modal" class="btn btn-primary shadow-sm">
            <i class="la la-plus me-1"></i> Adicionar usuário
        </a>
    @endcan
@endsection

@section('setting_content')

<div class="card border-0 shadow-sm rounded-3 overflow-hidden" style="background-color: var(--ds-surface) !important; --bs-card-bg: var(--ds-surface); border-color: rgba(255,255,255,0.06);">
    <div class="card-body p-0" style="background-color: transparent !important;">
        <div class="table-responsive" style="background-color: transparent !important;">
            <table class="table table-hover align-middle mb-0" style="background-color: transparent !important; color: var(--ds-text); --bs-table-bg: transparent; --bs-table-color: var(--ds-text); --bs-table-hover-bg: rgba(255,255,255,0.04);">
                <thead class="border-bottom" style="background-color: var(--ds-bg) !important;">
                <tr>
                    <th class="text-muted fw-semibold text-uppercase border-bottom-0" style="font-size: var(--ds-text-xs, 0.75rem); padding: 1rem; background-color: transparent !important;">Membro</th>
                    <th class="text-muted fw-semibold text-uppercase border-bottom-0" style="font-size: var(--ds-text-xs, 0.75rem); padding: 1rem; background-color: transparent !important;">Função</th>
                    <th class="text-muted fw-semibold text-uppercase border-bottom-0" style="font-size: var(--ds-text-xs, 0.75rem); padding: 1rem; background-color: transparent !important;">Permissões</th>
                    <th class="text-muted fw-semibold text-uppercase border-bottom-0" style="font-size: var(--ds-text-xs, 0.75rem); padding: 1rem; background-color: transparent !important;">Segurança (MFA)</th>
                    <th class="text-muted fw-semibold text-uppercase border-bottom-0" style="font-size: var(--ds-text-xs, 0.75rem); padding: 1rem; background-color: transparent !important;">Acesso & Criação</th>
                    <th class="text-muted fw-semibold text-uppercase border-bottom-0" style="font-size: var(--ds-text-xs, 0.75rem); padding: 1rem; background-color: transparent !important;">Status</th>
                    @can('staff-edit')
                        <th class="text-muted fw-semibold text-uppercase text-end border-bottom-0" style="font-size: var(--ds-text-xs, 0.75rem); padding: 1rem; background-color: transparent !important;">Ações</th>
                    @endcan
                </tr>
                </thead>
                <tbody style="border-top: 0; background-color: transparent !important;">
                @foreach($staffs as $staff)
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.06); background-color: transparent !important;">
                        <td class="px-4 py-3 border-0" style="background-color: transparent !important;">
                            <div class="d-flex align-items-center">
                                <div class="avatar avatar-md me-3" style="width: 40px; height: 40px; min-width: 40px;">
                                    <img class="avatar-img rounded-circle border border-secondary border-opacity-25" src="{{ asset($staff->avatar_alt) }}" alt="Avatar" style="object-fit: cover; width: 100%; height: 100%;">
                                </div>
                                <div>
                                    <div class="fw-bold" style="color: var(--ds-text);">{{ title($staff->name) }}</div>
                                    <div class="small text-muted">{{ $staff->email }}</div>
                                </div>
                            </div>
                        </td>
                        
                        <td class="border-0" style="background-color: transparent !important;">
                            <a class="badge text-decoration-none" style="background: rgba(255,255,255,0.1) !important; color: var(--ds-text-secondary) !important; border: 1px solid rgba(255,255,255,0.15) !important;" href="{{ route('admin.role.edit', $staff->roles->first()->id) }}" target="_blank">
                                <i class="la la-shield-alt me-1 text-muted"></i>
                                {{ title($staff->roles->first()->name) }}
                            </a>
                        </td>

                        <td class="border-0" style="background-color: transparent !important;">
                            <span class="badge rounded-pill" style="background: rgba(255,255,255,0.05) !important; color: var(--ds-text-secondary) !important; border: 1px solid rgba(255,255,255,0.1) !important;">
                                {{ $staff->roles->first()->permissions->count() ?? 0 }} concedidas
                            </span>
                        </td>

                        <td class="border-0" style="background-color: transparent !important;">
                            @if(isset($staff->two_fa) && $staff->two_fa)
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25"><i class="la la-check me-1"></i> Habilitado</span>
                            @else
                                <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25"><i class="la la-exclamation-triangle me-1"></i> Desabilitado</span>
                            @endif
                        </td>

                        <td class="text-muted small border-0" style="background-color: transparent !important;">
                            <div class="mb-1">
                                <i class="la la-sign-in-alt me-1"></i>
                                @if(isset($staff->last_login))
                                    {{ \Carbon\Carbon::parse($staff->last_login)->format('d/m/Y H:i') }}
                                @else
                                    —
                                @endif
                            </div>
                            <div>
                                <i class="la la-calendar-plus me-1"></i> {{ $staff->created_at->format('d/m/Y') }}
                            </div>
                        </td>
                        
                        <td class="border-0" style="background-color: transparent !important;">
                            @if($staff->status)
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">Ativo</span>
                            @else
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25">Inativo</span>
                            @endif
                        </td>
                        
                        @can('staff-edit')
                            <td class="text-end px-4 border-0" style="background-color: transparent !important;">
                                <button type="button" data-edit-url="{{ route('admin.staff.edit', $staff->id) }}" class="btn btn-sm btn-outline-primary edit-modal">
                                    Gerenciar
                                </button>
                            </td>
                        @endcan
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        
        @if($staffs->hasPages())
            <div class="px-4 py-3 border-top" style="background-color: var(--ds-bg) !important; border-color: rgba(255,255,255,0.06) !important;">
                {{ $staffs->links() }}
            </div>
        @endif
    </div>
</div>

@can('staff-create')
    @include('backend.staff.partials._new_staff')
@endcan

@can('staff-edit')
    @include('backend.staff.partials._edit_staff')
@endcan

@endsection

@push('scripts')
    @can('staff-edit')
        <script>
            $(document).ready(function () {
                editFormByModal('edit_staff_modal','edit_staff_data');
            });
        </script>
    @endcan
@endpush
