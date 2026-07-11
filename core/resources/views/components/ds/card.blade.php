@props([
    'title' => null,
    'padding' => '1.5rem',
])

<div class="card ds-table-card" {{ $attributes }}>
    @if($title || isset($headerActions))
        <div class="ds-table-header">
            @if($title)
                <span class="ds-table-title">{{ $title }}</span>
            @endif
            
            @if(isset($headerActions))
                <div class="ds-table-header-actions d-flex gap-2">
                    {{ $headerActions }}
                </div>
            @endif
        </div>
    @endif
    
    <div style="padding: {{ $padding }};">
        {{ $slot }}
    </div>
</div>
