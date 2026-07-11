@extends('backend.layouts.app')
@section('title', __('Withdraw Methods'))
@section('content')
    <div class="py-4">
        <h1 class="h3 fw-bold mb-1">
            <x-icon name="withdraw-1" height="28" width="28" class="me-1 text-primary align-middle"/>
            {{ __('Withdraw Methods') }}
        </h1>
    </div>
    
    <div class="card border-0 mb-4 shadow-sm">
        <div class="card-body text-center py-5">
            <i class="fa-solid fa-code-branch text-muted mb-3" style="font-size: 4rem;"></i>
            <h3 class="mb-3">{{ __('Arquitetura Atualizada') }}</h3>
            <p class="text-muted fs-5 mb-4">
                {{ __('A configuração de métodos de saque (Saque PIX e outros) agora é gerenciada diretamente dentro de cada Gateway.') }}<br>
                {{ __('Isto faz parte da arquitetura multiadquirência da OriginPay.') }}
            </p>
            <a href="{{ route('admin.payment.gateway.index') }}" class="btn btn-primary btn-lg px-5">
                <i class="fa-solid fa-arrow-right me-2"></i> {{ __('Ir para Gateways') }}
            </a>
        </div>
    </div>
@endsection
