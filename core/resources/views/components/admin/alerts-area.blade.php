@props(['alerts' => []])
@if(count($alerts) > 0)
<div class="alerts-area mb-4">
    @foreach($alerts as $alert)
        <div class="alert alert-{{ $alert['type'] ?? 'info' }} alert-dismissible fade show d-flex align-items-center gap-3 border-0 shadow-sm rounded-3 py-3" role="alert">
            @if(isset($alert['icon']))
                <div class="text-{{ $alert['type'] ?? 'info' }} fs-4 d-flex align-items-center">
                    <i class="{{ $alert['icon'] }}"></i>
                </div>
            @endif
            <div class="flex-grow-1">
                @if(isset($alert['title']))
                    <h6 class="alert-heading fw-bold mb-1">{{ $alert['title'] }}</h6>
                @endif
                <p class="mb-0" style="font-size: 0.9rem;">{!! $alert['message'] !!}</p>
            </div>
            @if(isset($alert['action']))
                <div class="ms-auto ms-md-4 me-4 me-md-0 mt-2 mt-md-0">
                    <a href="{{ $alert['action']['url'] }}" class="btn btn-sm btn-{{ $alert['type'] ?? 'info' }} fw-semibold rounded-pill px-3 shadow-sm">{{ $alert['action']['label'] }}</a>
                </div>
            @endif
            <button type="button" class="btn-close" data-coreui-dismiss="alert" aria-label="Close"></button>
        </div>
    @endforeach
</div>
@endif
