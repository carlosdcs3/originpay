@props([
    'title' => 'Nenhum registro encontrado',
    'desc' => 'Não há dados disponíveis no momento.',
    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>',
    'actionText' => null,
    'actionUrl' => null,
])

<div class="ds-empty" style="padding:4rem 1rem;">
    <div class="ds-empty-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            {!! $icon !!}
        </svg>
    </div>
    <div class="ds-empty-title">{{ $title }}</div>
    <div class="ds-empty-desc">{{ $desc }}</div>
    
    @if($actionText && $actionUrl)
        <a href="{{ $actionUrl }}" class="btn btn-primary mt-3">
            {{ $actionText }}
        </a>
    @endif
    
    {{ $slot }}
</div>
