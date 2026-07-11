@extends('backend.layouts.app')
@section('title', 'Sessões Ativas')
@section('content')
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 fw-bold">Sessões Ativas Globais</h5>
    </div>
    <div class="card-body">
        <p class="text-muted">Acompanhe sessões de usuários (internos e clientes) conectadas à infraestrutura no momento.</p>
        <div class="table-responsive mt-4">
            <table class="table table-hover">
                <thead class="bg-body-tertiary">
                    <tr>
                        <th>Usuário</th>
                        <th>IP</th>
                        <th>Dispositivo / Browser</th>
                        <th>Última Atividade</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sessions as $session)
                    <tr>
                        <td>{{ $session->user->full_name ?? 'Desconhecido' }}</td>
                        <td>{{ $session->user_ip }}</td>
                        <td>{{ $session->browser }} - {{ $session->os }}</td>
                        <td>{{ $session->created_at->diffForHumans() }}</td>
                        <td><button class="btn btn-sm btn-outline-danger">Derrubar</button></td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center py-4">Sem dados ou integração de sessões pendente.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
