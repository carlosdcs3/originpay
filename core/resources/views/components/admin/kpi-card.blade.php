@props(['title', 'value', 'colorClass' => 'text-dark'])

<div class="card admin-kpi-card h-100">
    <div class="card-body">
        <h6 class="text-muted mb-2">{{ $title }}</h6>
        <h3 class="{{ $colorClass }} mb-0">{{ $value }}</h3>
    </div>
</div>
