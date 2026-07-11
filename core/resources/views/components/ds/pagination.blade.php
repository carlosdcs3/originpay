@props([
    'paginator'
])

@if($paginator->hasPages())
<div class="ds-pagination px-4 py-3">
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-3">
        <div style="font-size:var(--ds-text-sm);color:var(--ds-text-muted);">
            Mostrando
            <span style="font-weight:600;color:var(--ds-text);">{{ $paginator->firstItem() ?? 0 }}</span>
            a
            <span style="font-weight:600;color:var(--ds-text);">{{ $paginator->lastItem() ?? 0 }}</span>
            de
            <span style="font-weight:600;color:var(--ds-text);">{{ $paginator->total() }}</span>
            resultados
        </div>
        <div class="ds-pagination-links">
            {{ $paginator->withQueryString()->links('backend.pagination.default') }}
        </div>
    </div>
</div>
@endif
