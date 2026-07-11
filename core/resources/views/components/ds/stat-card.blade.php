@props([
    'title' => null,
    'label' => null,
    'value',
    'subtitle' => null,
    'trend' => null,
    'accent' => null,
    'deltaText' => null,
    'deltaIcon' => null,
])

@php
    $cardClass = 'ds-kpi-card ds-hover-lift';
    if ($accent) {
        $cardClass .= " ds-accent-{$accent}";
    }
    $displayLabel = $title ?? $label;
    $displayDeltaText = $subtitle ?? $deltaText;
@endphp

<div class="{{ $cardClass }}" {{ $attributes }}>
    <div class="ds-kpi-label">{{ $displayLabel }}</div>
    
    <div class="ds-kpi-value" @if($accent) style="color:var(--ds-{{ $accent }});" @endif>
        {{ $value }}
    </div>
    
    @if($displayDeltaText || $deltaIcon)
        <div class="ds-kpi-delta neu">
            @if($deltaIcon)
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    {!! $deltaIcon !!}
                </svg>
            @endif
            {{ $displayDeltaText }}
        </div>
    @endif
</div>
