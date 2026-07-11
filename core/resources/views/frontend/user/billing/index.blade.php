@extends('frontend.layouts.user-v2')
@section('content')
<div class="row">
    <div class="col-xl-8 col-lg-12">
        <div class="card mb-4">
            <div class="card-header bg-white pb-0">
                <h6 class="mb-0">Seu Plano Atual</h6>
            </div>
            <div class="card-body">
                @if($subscription && $subscription->planVersion)
                    @php $plan = $subscription->planVersion->plan; $price = $subscription->price; @endphp
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h4 class="mb-1">{{ $plan->name }}</h4>
                            <span class="badge {{ $subscription->status == 'active' ? 'bg-success' : 'bg-warning' }}">
                                {{ ucfirst($subscription->status) }}
                            </span>
                        </div>
                        <div>
                            @if(!$price || $price->amount == 0)
                                <h3 class="mb-0 text-primary">Grátis</h3>
                            @else
                                <h3 class="mb-0 text-primary">R$ {{ number_format($price->amount / 100, 2, ',', '.') }}<small class="text-sm text-muted">/{{ $price->billing_period == 'annual' ? 'ano' : 'mês' }}</small></h3>
                            @endif
                        </div>
                    </div>
                    
                    <p class="text-sm text-muted">{{ $plan->description }}</p>
                    <hr>
                    
                    <h6>Limites de Uso (Ciclo Atual)</h6>
                    
                    <!-- API Requests -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between text-sm mb-1">
                            <span>API Requests</span>
                            <span>{{ number_format($apiRequests) }} / {{ $apiLimit ? number_format($apiLimit) : 'Ilimitado' }}</span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            @php
                                $apiPct = $apiLimit ? min(100, ($apiRequests / $apiLimit) * 100) : 0;
                            @endphp
                            <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $apiPct }}%" aria-valuenow="{{ $apiPct }}" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                    
                    <!-- Webhooks -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between text-sm mb-1">
                            <span>Webhooks Disparados</span>
                            <span>{{ number_format($webhooks) }} / {{ $webhookLimit ? number_format($webhookLimit) : 'Ilimitado' }}</span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            @php
                                $whPct = $webhookLimit ? min(100, ($webhooks / $webhookLimit) * 100) : 0;
                            @endphp
                            <div class="progress-bar bg-info" role="progressbar" style="width: {{ $whPct }}%" aria-valuenow="{{ $whPct }}" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>

                @else
                    <p class="text-muted">Nenhuma assinatura ativa encontrada.</p>
                @endif
            </div>
            @if($subscription && $subscription->price && $subscription->price->amount > 0)
            <div class="card-footer bg-white text-end">
                <button class="btn btn-outline-danger btn-sm">Cancelar Assinatura</button>
            </div>
            @endif
        </div>
    </div>
    
    <div class="col-xl-4 col-lg-12">
        <div class="card">
            <div class="card-header bg-white pb-0">
                <h6 class="mb-0">Fazer Upgrade</h6>
            </div>
            <div class="card-body">
                <p class="text-sm text-muted">Aumente seus limites e tenha acesso a taxas reduzidas fazendo o upgrade da sua conta.</p>
                
                <div class="d-grid gap-2">
                    <button class="v2-btn-primary" data-bs-toggle="modal" data-bs-target="#upgradeModal">Ver Planos Disponíveis</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Upgrade -->
<div class="modal fade" id="upgradeModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Comparativo de Planos</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <div class="row row-cols-1 row-cols-md-3 g-4">
            @foreach($catalog as $item)
                @if($item['product']->slug === 'payment-gateway')
                    @foreach($item['plans'] as $pv)
                    @php
                        $plan = $pv->plan;
                        $monthlyPrice = $pv->prices->where('billing_period', 'monthly')->first();
                    @endphp
                    <div class="col">
                        <div class="card h-100 border {{ $plan->is_recommended ? 'border-primary shadow' : '' }}">
                            @if($plan->is_recommended)
                            <div class="position-absolute top-0 start-50 translate-middle badge rounded-pill bg-primary">
                                Recomendado
                            </div>
                            @endif
                            <div class="card-body text-center">
                                <h4 class="v2-card-header">{{ $plan->name }}</h4>
                                @if(!$monthlyPrice || $monthlyPrice->amount == 0)
                                    <h2 class="my-4">Grátis</h2>
                                @else
                                    <h2 class="my-4">R$ {{ number_format($monthlyPrice->amount / 100, 2, ',', '.') }}<span class="text-sm text-muted">/mês</span></h2>
                                @endif
                                <p class="text-muted text-sm">{{ $plan->description }}</p>
                                
                                <ul class="list-unstyled text-start mt-4 mb-4">
                                    @foreach($pv->features as $feature)
                                        @if($feature->is_enabled && $feature->feature->type === 'boolean')
                                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> {{ $feature->feature->name }}</li>
                                        @endif
                                    @endforeach
                                    @php
                                        $pixFee = $pv->features->firstWhere('feature.slug', 'pix_fee_percent');
                                    @endphp
                                    <li class="mb-2"><i class="fas fa-bolt text-warning me-2"></i> {{ $pixFee ? $pixFee->value : '0' }}% Taxa PIX</li>
                                </ul>
                            </div>
                            <div class="card-footer bg-white border-top-0 p-3">
                                @if($subscription && $subscription->plan_version_id == $pv->id)
                                    <button class="btn btn-outline-secondary w-100" disabled>Plano Atual</button>
                                @else
                                    <button class="btn {{ $plan->is_recommended ? 'btn-primary' : 'btn-outline-primary' }} w-100">Assinar {{ $plan->name }}</button>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                @endif
            @endforeach
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
