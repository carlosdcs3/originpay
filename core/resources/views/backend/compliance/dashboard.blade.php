@extends('backend.layouts.app')
@section('title', 'Compliance e Fraude')

@section('content')
<x-ds.page
    title="Centro de Compliance"
    desc="Monitore análises KYC, incidentes e sinais operacionais de risco disponíveis neste ambiente."
    :breadcrumb="[
        ['title' => 'Clientes'],
        ['title' => 'Compliance']
    ]">

    <div class="row g-4">
        <div class="col-lg-12">
            <x-ds.card title="Fila de KYC recente">
                @if($pendingKyc->count() > 0)
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Lojista</th>
                                <th>Data de submissão</th>
                                <th>Status</th>
                                <th>Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pendingKyc as $kyc)
                                <tr>
                                    <td>Lojista #{{ $kyc->merchant_id ?? 'N/A' }}</td>
                                    <td>{{ $kyc->created_at }}</td>
                                    <td><x-ds.badge status="pending" label="Pendente" /></td>
                                    <td>
                                        <a href="{{ route('admin.kyc.index') }}" class="btn btn-sm btn-outline-primary">Abrir fila KYC</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <x-ds.empty-state title="Fila vazia" desc="Nenhum KYC pendente de análise neste momento." />
                @endif
            </x-ds.card>
        </div>

        <div class="col-lg-6">
            <x-ds.card title="Incidentes de segurança">
                @if($securityIncidents->count() > 0)
                    <div class="timeline">
                        @foreach($securityIncidents as $incident)
                            <div class="timeline-item mt-3 border-start ps-3 border-danger">
                                <div class="text-muted text-sm">{{ $incident->started_at }}</div>
                                <h6>{{ $incident->title }}</h6>
                                <p class="text-sm mb-0">Causa: {{ $incident->root_cause ?? 'Automático' }}</p>
                                <span class="badge bg-{{ $incident->status == 'active' ? 'danger' : 'success' }}">
                                    {{ strtoupper($incident->status) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <x-ds.empty-state title="Ambiente estável" desc="Nenhum incidente crítico registrado nos últimos dias." />
                @endif
            </x-ds.card>
        </div>

        <div class="col-lg-6">
            <x-ds.card title="Bloqueios de fraude">
                @if($recentFraudBlocks->count() > 0)
                    <table class="table">
                    </table>
                @else
                    <x-ds.empty-state title="Sem bloqueios recentes" desc="O mecanismo de fraude não registrou bloqueios diretos recentemente." />
                @endif
                <p class="text-muted text-xs text-center mt-3">Visualização operacional somente leitura.</p>
            </x-ds.card>
        </div>
    </div>
</x-ds.page>
@endsection
