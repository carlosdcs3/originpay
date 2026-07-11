@props([
    'type' => 'text', // 'text', 'title', 'avatar', 'table', 'card'
    'rows' => 3,      // For table
    'cols' => 4,      // For table
])

@if($type === 'table')
    @for($i = 0; $i < $rows; $i++)
        <div class="ds-skeleton-table-row">
            @for($j = 0; $j < $cols; $j++)
                <div class="ds-skeleton ds-skeleton-table-cell" style="width: {{ rand(60, 100) }}%;"></div>
            @endfor
        </div>
    @endfor
@elseif($type === 'card')
    <div class="ds-skeleton" style="height: 120px; padding: 1.5rem;">
        <div class="ds-skeleton ds-skeleton-title"></div>
        <div class="ds-skeleton ds-skeleton-text" style="width: 80%;"></div>
        <div class="ds-skeleton ds-skeleton-text" style="width: 50%;"></div>
    </div>
@elseif($type === 'avatar')
    <div class="ds-skeleton ds-skeleton-avatar"></div>
@elseif($type === 'title')
    <div class="ds-skeleton ds-skeleton-title"></div>
@else
    <div class="ds-skeleton ds-skeleton-text" {{ $attributes }}></div>
@endif
