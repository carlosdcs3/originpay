@extends('admin.layouts.app')

@section('panel')
<div class="row">
    <div class="col-xl-3 col-sm-6 mb-30">
        <div class="dashboard-w1 bg--primary b-radius--10 box-shadow">
            <div class="icon">
                <i class="fa fa-users"></i>
            </div>
            <div class="details">
                <div class="numbers">
                    <span class="amount">{{ $activeSubscriptions }}</span>
                </div>
                <div class="desciption">
                    <span class="text--small">Assinaturas Ativas</span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-sm-6 mb-30">
        <div class="dashboard-w1 bg--success b-radius--10 box-shadow">
            <div class="icon">
                <i class="fa fa-money-bill"></i>
            </div>
            <div class="details">
                <div class="numbers">
                    <span class="amount">R$ {{ number_format($mrr, 2, ',', '.') }}</span>
                </div>
                <div class="desciption">
                    <span class="text--small">MRR (Mensal)</span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-sm-6 mb-30">
        <div class="dashboard-w1 bg--info b-radius--10 box-shadow">
            <div class="icon">
                <i class="fa fa-chart-line"></i>
            </div>
            <div class="details">
                <div class="numbers">
                    <span class="amount">R$ {{ number_format($arr, 2, ',', '.') }}</span>
                </div>
                <div class="desciption">
                    <span class="text--small">ARR (Anual)</span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-sm-6 mb-30">
        <div class="dashboard-w1 bg--warning b-radius--10 box-shadow">
            <div class="icon">
                <i class="fa fa-list"></i>
            </div>
            <div class="details">
                <div class="numbers">
                    <span class="amount">{{ $totalPlans }}</span>
                </div>
                <div class="desciption">
                    <span class="text--small">Planos Criados</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-lg-12">
        <div class="card b-radius--10 ">
            <div class="card-header">
                <h4 class="card-title">Gestão de Billing Enterprise</h4>
            </div>
            <div class="card-body">
                <p>O Billing Engine Enterprise está ativo e desacoplado. Todos os acessos e limitações da API e plataforma agora consultam o serviço de provisionamento.</p>
                <div class="mt-4">
                    <a href="{{ route('admin.billing.plans.index') }}" class="btn btn--primary">Gerenciar Planos</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
