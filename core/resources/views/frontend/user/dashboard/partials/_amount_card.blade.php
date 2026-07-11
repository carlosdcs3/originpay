<div class="single-amount-card-area mb-3">
    @if(isset($userWallets))
    <div class="row mb-4">
        @foreach($userWallets as $wallet)
            <div class="col-12 mb-3">
                <div class="card bg-light">
                    <div class="card-body">
                        <h5>{{ __('Wallet:') }} {{ $wallet->currency->code }}</h5>
                        <div class="row">
                            <div class="col-md-4">
                                <strong>{{ __('Total Balance:') }}</strong> {{ getSymbol($wallet->currency->code) }}{{ number_format($wallet->balance, 2) }}
                            </div>
                            <div class="col-md-4 text-success">
                                <strong>{{ __('Available:') }}</strong> {{ getSymbol($wallet->currency->code) }}{{ number_format($wallet->available_balance, 2) }}
                            </div>
                            <div class="col-md-4 text-warning">
                                <strong>{{ __('Reserved:') }}</strong> {{ getSymbol($wallet->currency->code) }}{{ number_format($wallet->reserved_balance, 2) }}
                            </div>
                        </div>
                        @if($wallet->reserved_balance > 0)
                            <div class="alert alert-warning mt-2 mb-0">
                                <i class="fa fa-info-circle"></i> {{ __('Parte do seu saldo está reservada para saques em andamento.') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    @endif
    <div class="row">        @foreach($statistics as $statistic)            <div class="col-xl-4 col-lg-6 col-4">                <div class="single-amount-card">                    <div class="media">                        <div class="media-left icon-container {{ $statistic['color_class'] }}">                            <x-icon name="{{ $statistic['icon'] }}" class="icon"/>                        </div>                        <div class="media-body align-self-center">                            <h6>{{ $statistic['value'] }}</h6>                            <span>{{ $statistic['title'] }}</span>                        </div>                    </div>                    @if(isset($statistic['link']))                        <a href="{{ $statistic['link'] }}">                            <x-icon name="arrow-icon" class="icon"/>                        </a>                    @endif                </div>            </div>        @endforeach    </div></div>