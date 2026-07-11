<div class="card shadow-sm border-0 rounded-3">
	<div class="card-body p-4">
		<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
			<h5 class="card-title mb-0 fw-bold text-dark">
				{{ __('Transaction Summary') }}
			</h5>
            @if($user->transactions()->exists())
            <div class="btn-toolbar" role="toolbar">
                <div class="input-group">
                    <input type="hidden" id="wallet-hidden-daterange">
                    <div id="user-trx-reportrange"
                         class="report-range form-control d-flex align-items-center justify-content-between cursor-pointer border shadow-sm">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fa-solid fa-calendar-days text-primary"></i>
                            <span class="text-nowrap flex-grow-1" style="font-size: var(--ds-text-sm);">{{ __('Loading') }}...</span>
                        </div>
                        <x-icon name="angle-down" class="text-muted flex-shrink-0 ms-2"/>
                    </div>
                </div>
            </div>
            @endif
		</div>
        @if($user->transactions()->exists())
		    <div id="user-trx-chart"></div>
        @else
            <x-ds.empty-state 
                title="Histórico de transações vazio" 
                desc="Nenhuma atividade financeira registrada ainda. Gráficos de volume e atividade aparecerão após a primeira transação." 
                icon='<path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />' />
        @endif
	</div>
</div>

@push('scripts')
    @include('backend.user.manage.partials._user_transactions_chart_script')
@endpush

