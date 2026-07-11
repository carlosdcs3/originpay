@props([
    'label' => 'Ações',
    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>'
])

<div class="dropdown">
    <button class="btn btn-sm btn-outline-secondary" type="button" data-coreui-toggle="dropdown" aria-expanded="false" style="padding:.25rem .5rem;">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            {!! $icon !!}
        </svg>
    </button>
    <ul class="dropdown-menu dropdown-menu-end">
        {{ $slot }}
    </ul>
</div>
