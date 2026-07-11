@extends('merchant.layouts.app')
@section('connect_content')
<div class="container">
    <h2>Origin Connect - Seu Plano</h2>
    <div class="card">
        <div class="card-body">
            <h4>Plano Atual: {{ $subscription->plan_name ?? 'Nenhum' }}</h4>
            <p>Status: {{ $subscription->status ?? 'Inativo' }}</p>
            <p>Preço: R$ 39,90 / mês</p>
            
            <h5>Limites (Emails)</h5>
            <div class="progress">
                <div class="progress-bar" style="width: {{ ($usage / $limit) * 100 }}%"></div>
            </div>
            <p>{{ $usage }} / {{ $limit }}</p>

            <button class="btn btn-danger">Cancelar Assinatura</button>
        </div>
    </div>
</div>
@endsection

