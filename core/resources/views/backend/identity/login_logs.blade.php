@extends('backend.layouts.app')
@section('title', 'Logs de Login')
@section('content')
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 fw-bold">Auditoria e Logs de Login</h5>
    </div>
    <div class="card-body">
        <p class="text-muted">Consolidação histórica de acessos à plataforma.</p>
        <div class="table-responsive mt-4">
            <table class="table table-hover">
                <thead class="bg-body-tertiary">
                    <tr>
                        <th>Data/Hora</th>
                        <th>Usuário</th>
                        <th>IP</th>
                        <th>Localização</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td>{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                        <td>{{ $log->user->full_name ?? 'Sistema' }}</td>
                        <td>{{ $log->user_ip }}</td>
                        <td>{{ $log->city }}, {{ $log->country }}</td>
                        <td><span class="badge bg-success">Sucesso</span></td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center py-4">Sem logs.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $logs->links() ?? '' }}
    </div>
</div>
@endsection
