@extends('backend.layouts.app')

@section('title', $pageTitle)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="mb-1">{{ $pageTitle }}</h3>
        <p class="text-muted mb-0">Gerencie taxas globais, overrides por merchant e simule cobranças.</p>
    </div>
</div>

@php
    $methodLabels = [
        'pix' => 'Pix',
        'card' => 'Cartão',
        'boleto' => 'Boleto',
        'crypto' => 'Crypto',
    ];
    $formatRuleFee = function ($rule) {
        if (($rule->metadata['pricing_model'] ?? 'flat') === 'tiered') {
            return 'Por faixa de valor';
        }

        return number_format($rule->percentage_fee, 4, ',', '.').'% + R$ '.number_format($rule->fixed_fee, 2, ',', '.');
    };
@endphp

<div class="alert alert-primary border-0 mb-4">
    <div class="fw-semibold mb-1">Guia rapido de cadastro</div>
    <div class="row g-2 small">
        <div class="col-lg-4"><strong>R$ 0,30 fixo:</strong> taxa simples, fixa 0,30 e percentual 0.</div>
        <div class="col-lg-4"><strong>1,5% + R$ 0,30:</strong> taxa simples, fixa 0,30 e percentual 1,5.</div>
        <div class="col-lg-4"><strong>R$ 0,30 ate X:</strong> por faixa de valor, primeira faixa fixa e segunda faixa percentual + fixa.</div>
    </div>
</div>

<div class="row g-3 mb-4">
    @foreach($methods as $method)
        @php
            $activeRule = ($globalRules[$method] ?? collect())->firstWhere('status', \App\Models\PlatformFeeRule::STATUS_ACTIVE);
        @endphp
        <div class="col-xl-3 col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <div class="text-muted small text-uppercase">{{ $methodLabels[$method] }}</div>
                            <h5 class="mb-0">Taxa global</h5>
                        </div>
                        <span class="badge bg-{{ $activeRule ? 'success' : 'warning' }}">{{ $activeRule ? 'Ativa' : 'Fallback' }}</span>
                    </div>
                    @if($activeRule)
                        <div class="fw-bold">{{ $formatRuleFee($activeRule) }}</div>
                        @if(($activeRule->metadata['pricing_model'] ?? 'flat') === 'tiered')
                            <div class="text-muted small mt-2">
                                @foreach(($activeRule->metadata['tiers'] ?? []) as $tier)
                                    <div>
                                        R$ {{ number_format($tier['from_amount'] ?? 0, 2, ',', '.') }}
                                        ate
                                        {{ ($tier['to_amount'] ?? null) === null ? 'sem limite' : 'R$ '.number_format($tier['to_amount'], 2, ',', '.') }}:
                                        {{ number_format($tier['percentage_fee'] ?? 0, 4, ',', '.') }}% + R$ {{ number_format($tier['fixed_fee'] ?? 0, 2, ',', '.') }}
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        <div class="text-muted small mt-2">Liquidação: {{ $activeRule->settlement_delay_days }} dia(s)</div>
                        <div class="text-muted small">Reserva: {{ number_format($activeRule->reserve_percentage, 2, ',', '.') }}%</div>
                    @else
                        <div class="text-muted">Sem regra ativa cadastrada.</div>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row g-4">
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">Nova taxa global</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.platform-fees.global.store') }}">
                    @csrf
                    @include('backend.platform_fees.partials.rule-form', ['includeMerchant' => false, 'merchants' => $merchants])
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">Novo override individual</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.platform-fees.merchant.store') }}">
                    @csrf
                    @include('backend.platform_fees.partials.rule-form', ['includeMerchant' => true, 'merchants' => $merchants])
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Overrides por merchant</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-striped mb-0">
            <thead>
                <tr>
                    <th>Merchant</th>
                    <th>Método</th>
                    <th>Taxa</th>
                    <th>Min / Max</th>
                    <th>Janela</th>
                    <th>Status</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($merchantRules as $rule)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $rule->user->name ?? $rule->user->username ?? 'Merchant removido' }}</div>
                            <div class="text-muted small">{{ $rule->user->email ?? '-' }}</div>
                        </td>
                        <td>{{ $methodLabels[$rule->payment_method] ?? strtoupper($rule->payment_method) }}</td>
                        <td>
                            <div>{{ $formatRuleFee($rule) }}</div>
                            @if(($rule->metadata['pricing_model'] ?? 'flat') === 'tiered')
                                <div class="text-muted small">
                                    @foreach(($rule->metadata['tiers'] ?? []) as $tier)
                                        <div>
                                            R$ {{ number_format($tier['from_amount'] ?? 0, 2, ',', '.') }}
                                            ate
                                            {{ ($tier['to_amount'] ?? null) === null ? 'sem limite' : 'R$ '.number_format($tier['to_amount'], 2, ',', '.') }}:
                                            {{ number_format($tier['percentage_fee'] ?? 0, 4, ',', '.') }}% + R$ {{ number_format($tier['fixed_fee'] ?? 0, 2, ',', '.') }}
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td>
                            R$ {{ number_format($rule->minimum_fee ?? 0, 2, ',', '.') }}
                            /
                            {{ $rule->maximum_fee === null ? 'sem teto' : 'R$ '.number_format($rule->maximum_fee, 2, ',', '.') }}
                        </td>
                        <td>
                            <div>{{ optional($rule->starts_at)->format('d/m/Y H:i') ?? 'imediato' }}</div>
                            <div class="text-muted small">{{ optional($rule->ends_at)->format('d/m/Y H:i') ?? 'sem fim' }}</div>
                        </td>
                        <td><span class="badge bg-{{ $rule->status === 'active' ? 'success' : 'secondary' }}">{{ $rule->status }}</span></td>
                        <td class="text-end">
                            @if($rule->status === 'active')
                                <form method="POST" action="{{ route('admin.platform-fees.deactivate', $rule) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="reason" value="Desativação manual no painel">
                                    <button class="btn btn-sm btn-outline-danger">Desativar</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">Nenhum override individual cadastrado.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        {{ $merchantRules->links() }}
    </div>
</div>

<div class="row g-4 mt-1">
    <div class="col-xl-5">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">Simulador</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.platform-fees.simulate') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Valor</label>
                            <input type="number" step="0.01" min="0" name="amount" class="form-control" value="{{ old('amount', '100.00') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Método</label>
                            <select name="payment_method" class="form-select" required>
                                @foreach($methods as $method)
                                    <option value="{{ $method }}" @selected(old('payment_method') === $method)>{{ $methodLabels[$method] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Merchant opcional</label>
                            <select name="user_id" class="form-select">
                                <option value="">Usar regra global</option>
                                @foreach($merchants as $merchant)
                                    <option value="{{ $merchant->id }}" @selected((string) old('user_id') === (string) $merchant->id)>
                                        {{ $merchant->name ?? $merchant->username ?? $merchant->email }} - {{ $merchant->email }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <button class="btn btn-primary">Simular</button>
                        </div>
                    </div>
                </form>

                @if(session('simulation'))
                    @php($simulation = session('simulation'))
                    <div class="alert alert-info mt-4 mb-0">
                        <div><strong>Origem:</strong> {{ $simulation['source'] }}</div>
                        <div><strong>Taxa:</strong> R$ {{ number_format($simulation['platform_fee_amount'], 2, ',', '.') }}</div>
                        @if(($simulation['snapshot']['pricing_model'] ?? 'flat') === 'tiered')
                            <div><strong>Modelo:</strong> Por faixa de valor</div>
                            @if(!empty($simulation['snapshot']['selected_tier']))
                                <div class="small">
                                    Faixa aplicada:
                                    R$ {{ number_format($simulation['snapshot']['selected_tier']['from_amount'], 2, ',', '.') }}
                                    ate
                                    {{ $simulation['snapshot']['selected_tier']['to_amount'] === null ? 'sem limite' : 'R$ '.number_format($simulation['snapshot']['selected_tier']['to_amount'], 2, ',', '.') }}
                                </div>
                            @endif
                        @endif
                        <div><strong>Líquido:</strong> R$ {{ number_format($simulation['net_amount'], 2, ',', '.') }}</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-xl-7">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">Histórico de auditoria</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Admin</th>
                            <th>Ação</th>
                            <th>Regra</th>
                            <th>Motivo</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($audits as $audit)
                            <tr>
                                <td>{{ $audit->created_at->format('d/m/Y H:i') }}</td>
                                <td>{{ $audit->admin->name ?? 'Sistema' }}</td>
                                <td>{{ $audit->action }}</td>
                                <td>
                                    @if($audit->rule)
                                        {{ $audit->rule->scope }} / {{ $audit->rule->payment_method }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $audit->reason }}</td>
                                <td><code>{{ $audit->ip_address }}</code></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">Nenhum log de auditoria encontrado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                {{ $audits->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
