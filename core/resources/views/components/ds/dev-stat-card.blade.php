@props(['title', 'value', 'trend' => null, 'trendType' => 'neutral', 'icon' => 'activity'])

<div class="card border-0 shadow-sm rounded-3 h-100">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="text-muted fw-semibold" style="font-size: var(--ds-text-sm);">{{ $title }}</span>
            <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; background: rgba(var(--ds-primary-rgb), 0.1);">
                <i class="la la-{{ $icon }} text-primary"></i>
            </div>
        </div>
        <h3 class="fw-bold mb-1" style="font-family: var(--ds-font-mono);">{{ $value }}</h3>
        @if($trend)
            <div class="d-flex align-items-center mt-2" style="font-size: 0.75rem;">
                @if($trendType === 'positive')
                    <span class="text-success fw-bold d-flex align-items-center"><i class="la la-arrow-up me-1"></i> {{ $trend }}</span>
                @elseif($trendType === 'negative')
                    <span class="text-danger fw-bold d-flex align-items-center"><i class="la la-arrow-down me-1"></i> {{ $trend }}</span>
                @else
                    <span class="text-muted fw-bold">{{ $trend }}</span>
                @endif
                <span class="text-muted ms-2">vs último período</span>
            </div>
        @endif
    </div>
</div>
