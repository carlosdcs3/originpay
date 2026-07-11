@extends('backend.layouts.app')
@section('title', 'Resumo Financeiro')
@section('content')
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 fw-bold">Resumo Financeiro (Overview)</h5>
    </div>
    <div class="card-body">
        <p class="text-muted">Consolidação das operações, receitas da plataforma e volume transacionado.</p>
        <div class="table-responsive mt-4">
            <table class="table table-hover">
                <thead class="bg-body-tertiary">
                    <tr>
                        <th>Transação</th>
                        <th>Data</th>
                        <th>Valor</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $trx)
                    <tr>
                        <td>{{ $trx->trx }}</td>
                        <td>{{ $trx->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ siteCurrency('symbol') }} {{ $trx->amount }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="text-center">Sem dados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
