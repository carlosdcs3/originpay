@extends('backend.finance.index')
@section('finance_title', 'Cobranças')
@section('finance_desc', 'Monitoramento e acompanhamento das cobranças processadas pela plataforma.')

@section('finance_content')
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-2">
            <x-ds.stat-card title="Todas" :value="$stats['total'] ?? 0" trend="neutral" trendValue="Cobranças" />
        </div>
        <div class="col-6 col-md-2">
            <x-ds.stat-card title="Pagas" :value="$stats['paid'] ?? 0" trend="up" trendValue="Status finalizado" />
        </div>
        <div class="col-6 col-md-2">
            <x-ds.stat-card title="Pendentes" :value="$stats['pending'] ?? 0" trend="neutral" trendValue="Aguardando" />
        </div>
        <div class="col-6 col-md-3">
            <x-ds.stat-card title="Receita Bruta" :value="'R$ ' . number_format($stats['gross'] ?? 0, 2, ',', '.')" trend="up" trendValue="Cobranças pagas" />
        </div>
        <div class="col-6 col-md-3">
            <x-ds.stat-card title="Receita Líquida" :value="'R$ ' . number_format($stats['net'] ?? 0, 2, ',', '.')" trend="up" trendValue="Valor líquido" />
        </div>
    </div>

    <x-ds.table
        title="Relatório de cobranças"
        :count="$charges->total() ?? $charges->count()"
        :isEmpty="$charges->isEmpty()"
        :action="route('admin.gateway.charges.index')">

        <x-slot name="search">
            <div class="position-relative">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--ds-text-muted);pointer-events:none;"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                <input type="text" name="search" class="ds-filter-input form-control" placeholder="ID, UUID, TXID, lojista ou cliente" value="{{ request('search') }}" style="padding-left:2.2rem!important;width:100%; min-width:300px;">
            </div>
        </x-slot>

        <x-slot name="filters">
            <select name="status" class="ds-filter-select form-select" style="width:auto; font-size:var(--ds-text-sm);">
                <option value="">Todos os status</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Aguardando pagamento</option>
                <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }}>Processando</option>
                <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Pago</option>
                <option value="refunded" {{ request('status') == 'refunded' ? 'selected' : '' }}>Reembolsado</option>
                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelado</option>
                <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>Expirado</option>
                <option value="chargeback" {{ request('status') == 'chargeback' ? 'selected' : '' }}>Chargeback</option>
            </select>
            <button type="submit" class="btn btn-primary btn-sm px-3">Filtrar</button>
            @if(request('search') || request('status'))
                <a href="{{ route('admin.gateway.charges.index') }}" class="btn btn-ghost btn-sm" style="color:var(--ds-text-muted);">Limpar</a>
            @endif
        </x-slot>

        <x-slot name="thead">
            <th>Cobrança</th>
            <th>Lojista</th>
            <th>Cliente</th>
            <th>Valor</th>
            <th>Método</th>
            <th>Gateway</th>
            <th>Status</th>
            <th class="text-end">Ações</th>
        </x-slot>

        @forelse($charges as $charge)
            @php
                $visualId = 'CHG-' . str_pad($charge->id ?? 0, 6, '0', STR_PAD_LEFT);
                $platformFee = $charge->platform_fee ?? 0;
                $gatewayFee = $charge->gateway_fee ?? 0;
                $totalFee = $platformFee + $gatewayFee;
                $statusValue = $charge->status->value ?? (is_string($charge->status) ? $charge->status : 'pending');
                $statusConfig = match($statusValue) {
                    'paid' => ['label' => 'Pago', 'color' => 'success'],
                    'waiting_payment', 'pending' => ['label' => 'Aguardando pagamento', 'color' => 'warning'],
                    'processing' => ['label' => 'Processando', 'color' => 'info'],
                    'refunded' => ['label' => 'Reembolsado', 'color' => 'purple'],
                    'cancelled' => ['label' => 'Cancelado', 'color' => 'danger'],
                    'expired' => ['label' => 'Expirado', 'color' => 'dark'],
                    'chargeback' => ['label' => 'Chargeback', 'color' => 'secondary'],
                    default => ['label' => ucfirst($statusValue), 'color' => 'light']
                };
                $methodLabel = match($charge->payment_method->value ?? '') {
                    'pix' => 'PIX',
                    'credit_card' => 'Cartão',
                    'boleto' => 'Boleto',
                    'crypto' => 'Cripto',
                    default => 'Outro'
                };
            @endphp
            <tr>
                <td>
                    <div style="font-weight:600; font-family:var(--ds-font-mono); font-size:var(--ds-text-sm); color:var(--ds-heading);">
                        {{ $visualId }}
                    </div>
                    <div style="font-size:10px; color:var(--ds-text-muted); margin-top:2px;">
                        {{ $charge->created_at->format('d/m/Y H:i') }}
                    </div>
                </td>
                <td>
                    <div style="font-weight:600; font-size:var(--ds-text-sm);">
                        {{ $charge->user->fullname ?? ($charge->user->name ?? '—') }}
                    </div>
                    <div style="font-size:11px; color:var(--ds-text-muted);">
                        {{ '@' . ($charge->user->username ?? 'system') }}
                    </div>
                </td>
                <td>
                    <div style="font-weight:500; font-size:var(--ds-text-sm);">{{ $charge->customer_name ?: 'Cliente não informado' }}</div>
                    @if($charge->customer_email)
                        <div style="font-size:11px; color:var(--ds-text-muted);">{{ $charge->customer_email }}</div>
                    @endif
                </td>
                <td>
                    <div style="font-weight:600; font-size:var(--ds-text-sm); color:var(--ds-heading);">
                        R$ {{ number_format($charge->amount, 2, ',', '.') }}
                    </div>
                    <div style="font-size:11px; color:var(--ds-text-muted);">
                        Líquido: R$ {{ number_format($charge->net_amount, 2, ',', '.') }} | Taxas: R$ {{ number_format($totalFee, 2, ',', '.') }}
                    </div>
                </td>
                <td><span class="badge ds-badge-info" style="font-weight:600;">{{ $methodLabel }}</span></td>
                <td>{{ $charge->gateway->name ?? 'N/A' }}</td>
                <td><span class="badge ds-badge-{{ $statusConfig['color'] }}">{{ $statusConfig['label'] }}</span></td>
                <td class="text-end">
                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.gateway.charges.show', $charge->id) }}" class="btn btn-sm btn-outline-primary">Detalhes</a>
                        <button type="button" class="btn btn-sm btn-ghost" onclick="navigator.clipboard.writeText('{{ $charge->uuid }}')">Copiar UUID</button>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="8">
                    <x-ds.empty-state title="Nenhuma cobrança encontrada" desc="Sem resultados para os filtros aplicados." />
                </td>
            </tr>
        @endforelse

        <x-slot name="pagination">
            <x-ds.pagination :paginator="$charges" />
        </x-slot>

    </x-ds.table>
@endsection
