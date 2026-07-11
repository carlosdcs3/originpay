<div class="ds-card-body no-pad">
    <div class="ds-tx-list">
        @forelse($transactions as $transaction)
            @php
                $transactionTypeClass = $transaction->trx_type->kebabCase();
                $amountColor = $transaction->amount_flow->color($transaction->status);
                $amountSign = $transaction->amount_flow->sign($transaction->status);
                
                // Map to new icon classes
                $iconClass = 'default';
                $iconHtml = '<i class="fas fa-coins"></i>';
                
                if (str_contains($transactionTypeClass, 'deposit')) {
                    $iconClass = 'deposit';
                    $iconHtml = '<i class="fas fa-arrow-down"></i>';
                } elseif (str_contains($transactionTypeClass, 'withdraw')) {
                    $iconClass = 'withdraw';
                    $iconHtml = '<i class="fas fa-arrow-up"></i>';
                } elseif (str_contains($transactionTypeClass, 'send')) {
                    $iconClass = 'send-money';
                    $iconHtml = '<i class="fas fa-paper-plane"></i>';
                } elseif (str_contains($transactionTypeClass, 'receive')) {
                    $iconClass = 'receive';
                    $iconHtml = '<i class="fas fa-hand-holding-usd"></i>';
                } elseif (str_contains($transactionTypeClass, 'payment')) {
                    $iconClass = 'payment';
                    $iconHtml = '<i class="fas fa-exchange-alt"></i>';
                }
                
                // Status mapping
                $statusClass = strtolower($transaction->status->value);
            @endphp
            <div class="ds-tx-item" role="button" data-bs-toggle="modal" data-bs-target="#transactionModal{{ $transaction->id }}">
                <div class="ds-tx-icon {{ $iconClass }}">
                    {!! $iconHtml !!}
                </div>
                
                <div class="ds-tx-info">
                    <div class="ds-tx-desc" title="{{ $transaction->description }}">{{ $transaction->description }}</div>
                    <div class="ds-tx-meta">
                        <span class="ds-tx-type {{ $iconClass }}">{{ title($transaction->trx_type->value) }}</span>
                        <span class="ds-tx-date">{{ $transaction->created_at->format('d/m/Y H:i') }} • {{ strtoupper($transaction->trx_id) }}</span>
                    </div>
                </div>
                
                <div class="ds-tx-right">
                    @php
                        $amtClass = 'neutral';
                        if ($amountSign === '+') $amtClass = 'positive';
                        if ($amountSign === '-') $amtClass = 'negative';
                    @endphp
                    <div class="ds-tx-amount {{ $amtClass }}">
                        {{ $amountSign }} {{ number_format($transaction->amount, 2) }} {{ $transaction->currency }}
                    </div>
                    <div class="ds-tx-status {{ $statusClass }}">
                        {{ strtoupper($transaction->status->value) }}
                    </div>
                </div>
            </div>
            
            {{-- Transaction Modal --}}
            @include('frontend.user.transaction.partials._details_modal', ['transaction' => $transaction, 'transactionTypeClass' => $transactionTypeClass])
            
        @empty
            <div class="ds-empty-state">
                <div class="ds-empty-icon"><i class="fas fa-receipt" style="color: var(--ds-text-muted);"></i></div>
                <div class="ds-empty-title">Nenhuma transação ainda</div>
                <div class="ds-empty-desc">Suas movimentações recentes aparecerão aqui.</div>
            </div>
        @endforelse
    </div>
</div>