@extends('frontend.layouts.user-v2')
@section('title', __('Edit Cardholder'))
@section('content')
    <div class="v2-card">
        <div class="v2-card-header d-flex flex-column flex-md-row justify-content-between">
            <h2 class="v2-card-title mb-2 mb-md-0">{{ __('Edit Cardholder') }}</h6>
            <a class="v2-btn-secondary btn-sm" href="{{ route('user.virtual-card.cardholders.index') }}">
                <i class="fa-solid fa-list"></i> {{ __('All Cardholders') }}
            </a>
        </div>
        <div class="v2-card-body">
            <form method="POST" action="{{ route('user.virtual-card.cardholders.update', $cardholder) }}" autocomplete="off" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="card_type" class="v2-label">@lang('Cardholder Type')</label>
                        <input type="text" class="v2-input" value="{{ $cardholder->card_type->label() }}" disabled>
                        <input type="hidden" name="card_type" value="{{ $cardholder->card_type->value }}">
                    </div>
                    @if($cardholder->card_type->value === \App\Enums\VirtualCard\CardholderType::PERSONAL->value)
                        <div class="col-md-6 kyc-fields-wrap">
                            <label for="kyc_template_id" class="form-label fw-semibold text-primary-emphasis">
                                @lang('KYC Type')
                            </label>
                            @if($cardholder->kyc_status && $cardholder->kyc_status === \App\Enums\KycStatus::REJECTED)
                                <select class="v2-input" id="kyc_template_id" name="kyc_template_id">
                                    <option value="">@lang('Select KYC Type')</option>
                                    @if(isset($kycTemplates))
                                        @foreach($kycTemplates as $tpl)
                                            <option value="{{ $tpl->id }}" {{ old('kyc_template_id', $cardholder->kyc_template_id) == $tpl->id ? 'selected' : '' }}>{{ $tpl->title }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            @else
                                <input type="text" class="v2-input" value="{{ optional($cardholder->kycTemplate)->title ?? '-' }}" disabled>
                                <input type="hidden" name="kyc_template_id" value="{{ $cardholder->kyc_template_id }}">
                            @endif
                        </div>
                    @endif
                </div>
                <div id="kyc-fields-dynamic"></div>
                
                @if($cardholder->card_type === \App\Enums\VirtualCard\CardholderType::PERSONAL)
                    @include('frontend.user.virtual_card.cardholders.partials._personal_details', ['cardholder' => $cardholder])
                @elseif($cardholder->card_type === \App\Enums\VirtualCard\CardholderType::BUSINESS)
                    @include('frontend.user.virtual_card.cardholders.partials._business_details', ['business' => $cardholder->business])
                @endif
                
                <div class="d-flex justify-content-end mt-4">
                    <a href="{{ route('user.virtual-card.cardholders.index') }}" class="btn btn-secondary me-2">
                        <x-icon name="x" class="me-1" height="20" width="20"/>
                        @lang('Cancel')
                    </a>
                    <button type="submit" class="v2-btn-primary">
                        <x-icon name="check" class="me-1" height="20" width="20"/>
                        @lang('Update Cardholder')
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
@push('scripts')
    @include('frontend.user.virtual_card.cardholders.partials._script')
@endpush
