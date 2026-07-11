@props(['title', 'description' => null, 'icon' => 'fa-inbox', 'action' => null, 'actionLabel' => 'Adicionar'])
<div class="card border border-secondary-subtle shadow-sm rounded-4 mb-4 text-center py-5 bg-body">
    <div class="card-body py-4">
        <div class="mb-4 text-body-secondary mx-auto d-flex align-items-center justify-content-center bg-body-tertiary rounded-circle" style="width: 80px; height: 80px;">
            <i class="fa-solid {{ $icon }} fs-1"></i>
        </div>
        <h4 class="fw-bold text-body-emphasis mb-2">{{ $title }}</h4>
        @if($description)
            <p class="text-body-secondary mb-4 mx-auto" style="max-width: 400px; font-size: 0.95rem;">{{ $description }}</p>
        @endif
        @if($action)
            <a href="{{ $action }}" class="btn btn-primary fw-semibold px-4 py-2 rounded-3 shadow-sm">{{ $actionLabel }}</a>
        @endif
        {{ $slot }}
    </div>
</div>
