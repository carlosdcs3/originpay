@props([
    'action' => url()->current(),
    'method' => 'GET',
])

<div class="mb-4">
    <form action="{{ $action }}" method="{{ $method }}" class="d-flex flex-wrap align-items-center gap-3">
        {{ $slot }}
    </form>
</div>
