@php use App\Enums\MethodType; @endphp
@php use App\Constants\FixPctType; @endphp

@extends('frontend.layouts.user-v2')
@section('title', 'Contas de saque')

@section('content')
    <div class="v2-card">
        <div class="v2-card-header d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <h2 class="v2-card-title mb-0">Contas de saque</h2>
                <p class="mb-0 text-muted small">Cadastre e mantenha atualizadas as contas usadas para receber liquidações.</p>
            </div>
            <a class="v2-btn-secondary btn-sm" href="{{ route('user.withdraw.account.create') }}">
                <i class="fa-solid fa-plus-circle"></i> Nova conta
            </a>
        </div>
        <div class="v2-card-body bg-main">
            @forelse($withdrawAccounts as $account)
                <div class="withdraw-account-item d-flex align-items-center justify-content-between p-2 rounded mb-2 bg-white border-default">
                    <div class="circle-icon d-flex align-items-center justify-content-center me-2">
                        <img class="img-fluid" src="{{ asset($account->withdrawMethod->logo) }}"
                             alt="Logo do metodo de saque">
                    </div>

                    <div class="withdraw-details flex-grow-1">
                        <div class="d-flex flex-column">
                            <span class="fw-bold text-dark">
                                {{ title($account->name) }}
                                @if($account->withdrawMethod->type == MethodType::AUTOMATIC)
                                    <span class="badge bg-success ms-1">Automatico</span>
                                @else
                                    <span class="badge bg-warning ms-1">Manual</span>
                                @endif
                            </span>
                            <div class="text-muted small mt-1">
                                Tarifa:
                                <span class="text-primary">
                                    {{ $account->withdrawMethod->charge }}{{ $account->withdrawMethod->charge_type === FixPctType::PERCENT ? '%' : ' '.$account->withdrawMethod->currency }}
                                </span>
                                |
                                Limites:
                                {{ $account->withdrawMethod->min_withdraw }} - {{ $account->withdrawMethod->max_withdraw }} {{ $account->withdrawMethod->currency }}
                            </div>
                        </div>
                    </div>

                    <div>
                        <a href="{{ route('user.withdraw.account.edit', $account->id) }}" class="btn btn-sm btn-primary"
                           aria-label="Editar conta de saque">
                            <i class="fa-solid fa-pen-to-square"></i>
                            <span class="d-none d-sm-inline">Editar</span>
                        </a>
                    </div>
                </div>
            @empty
                <div class="text-center py-5">
                    <div class="mb-3">
                        <i class="fas fa-university fa-3x text-muted"></i>
                    </div>
                    <h6 class="text-muted mb-2">Nenhuma conta de saque cadastrada</h6>
                    <p class="small text-muted mb-3">Quando voce cadastrar uma conta valida, ela aparecera aqui para ser usada nas solicitacoes de saque.</p>
                    <a href="{{ route('user.withdraw.account.create') }}" class="v2-btn-primary btn-sm">
                        <i class="fas fa-plus me-1"></i> Cadastrar conta
                    </a>
                </div>
            @endforelse
        </div>
    </div>
@endsection
