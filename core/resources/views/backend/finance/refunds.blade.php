@extends('backend.layouts.app')
@section('title', 'Estornos & Reembolsos')
@section('content')
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 fw-bold">Estornos (Refunds)</h5>
    </div>
    <div class="card-body">
        <p class="text-muted">Estornos voluntários realizados pelos lojistas ou pela plataforma.</p>
        <div class="table-responsive mt-4">
            <table class="table table-hover">
                <thead class="bg-body-tertiary">
                    <tr>
                        <th>Transação Original</th>
                        <th>Data Estorno</th>
                        <th>Gateway</th>
                        <th>Valor Reembolsado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($refunds as $ref)
                    <tr>
                        <td>{{ $ref->trx }}</td>
                        <td>{{ $ref->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $ref->gateway->name ?? 'N/A' }}</td>
                        <td>{{ siteCurrency('symbol') }} {{ $ref->amount }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center">Nenhum reembolso registrado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $refunds->links() }}
    </div>
</div>
@endsection
