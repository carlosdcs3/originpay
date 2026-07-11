@if($walletBalances->isNotEmpty())
	<div class="row g-3 mb-4">
		<div class="col-12">
			<div class="card border-0 shadow-sm">
				<div class="card-body px-4">
					{{-- Improved Header Section --}}
					<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
						<h5 class="fw-semibold mb-0 text-dark text-capitalize">
							 {{ __('Wallet Summary') }}
						</h5>
						<span class="badge bg-light text-muted px-3 py-2 small shadow-sm rounded-pill">
							<i class="fa-solid fa-circle-info me-1 text-primary"></i> {{ $walletBalances->count() }} Currency Type{{ $walletBalances->count() > 1 ? 's' : '' }}
						</span>
					</div>
					
					{{-- Wallet Grid --}}
					<div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-3">
						@foreach($walletBalances as $wallet)
							<div class="col-sm-12 col-md-6 col-xxl-4">
								<div class="card wallet-summary-card h-100 border-0 shadow-sm p-3 {{ $wallet['bg_class'] }}">
									<div class="d-flex justify-content-between align-items-center h-100 mb-2">
										{{-- Symbol --}}
										<div class="currency-icon fs-4 fw-bold text-dark">
											<img src="{{ asset($wallet['flag']) }}" alt="">
										</div>
										
										{{-- Info --}}
										<div class="flex-grow-1 px-3">
											<h6 class="mb-1 fw-semibold text-dark">{{ $wallet['code'] }} Wallet</h6>
											<small class="text-muted">{{ $wallet['count'] }} Wallet(s)</small>
										</div>
										
										{{-- Amount --}}
										<div class="text-end">
											<div class="wallet-amount fw-bold fs-6 text-dark">
												{{ $wallet['symbol'] }}{{ number_format($wallet['total'], 2) }}
											</div>
										</div>
									</div>
									<div class="d-flex justify-content-between border-top pt-2 mt-2" style="font-size: 0.85rem;">
										<span class="text-success"><i class="fa fa-check-circle"></i> {{ $wallet['symbol'] }}{{ number_format($wallet['total_available'], 2) }} disp</span>
										<span class="text-warning"><i class="fa fa-lock"></i> {{ $wallet['symbol'] }}{{ number_format($wallet['total_reserved'], 2) }} resv</span>
									</div>
									<div class="d-flex justify-content-between pt-1" style="font-size: 0.85rem;">
										<span class="text-info"><i class="fa fa-clock"></i> {{ $wallet['symbol'] }}{{ number_format($wallet['total_pending'], 2) }} pend</span>
										<span class="text-danger"><i class="fa fa-arrow-down"></i> {{ $wallet['symbol'] }}{{ number_format($wallet['total_withdrawn'], 2) }} ret</span>
									</div>
								</div>
							</div>
						@endforeach
					</div>

					@if(isset($withdrawalStats))
					<div class="row mt-4">
						<div class="col-12">
							<div class="alert alert-warning d-flex align-items-center mb-0">
								<i class="fa fa-exclamation-triangle fa-2x me-3"></i>
								<div>
									<h6 class="mb-1">{{ __('Withdrawal Reserves Insight') }}</h6>
									<span class="small">
										<strong>{{ $withdrawalStats['count_with_reserve'] }}</strong> saques em andamento.
										<strong>BRL {{ number_format($withdrawalStats['total_reserved'], 2) }}</strong> de liquidez reservada.
										@if($withdrawalStats['stuck_count'] > 0)
											<strong class="text-danger">({{ $withdrawalStats['stuck_count'] }} travados > 24h)</strong>
										@endif
									</span>
								</div>
							</div>
						</div>
					</div>
					@endif
				</div>
			</div>
		</div>
	</div>
@endif
