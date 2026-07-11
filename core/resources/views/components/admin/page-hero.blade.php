@props(['title', 'description'])

<div class="admin-page-hero d-flex justify-content-between align-items-center">
    <div>
        <h2 class="mb-1 text-white">{{ $title }}</h2>
        <p class="mb-0 text-white-50">{{ $description }}</p>
    </div>
    <div>
        {{ $slot }}
    </div>
</div>
