@props(['action'])

<div class="admin-smart-filter-bar">
    <form action="{{ $action }}" method="GET" class="row g-3">
        {{ $slot }}
        <div class="col-md-1 d-flex align-items-end">
            <button type="submit" class="btn btn-secondary w-100"><i class="fas fa-filter"></i> Filtrar</button>
        </div>
    </form>
</div>
