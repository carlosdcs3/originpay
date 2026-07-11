@extends('backend.layouts.app')
@section('title', 'Motor de Risco')
@section('content')
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 fw-bold">Score de Risco & Device Fingerprint</h5>
    </div>
    <div class="card-body">
        <p class="text-muted">Acompanhe as pontuações de risco em tempo real para prevenção à lavagem de dinheiro (PLD) e Chargebacks.</p>
        <div class="alert alert-info border-0 mt-4">
            Módulo de Scoring Dinâmico em preparação. Ele cruzará Histórico Comportamental, CPF/CNPJ Score e Velocity Checks.
        </div>
    </div>
</div>
@endsection
