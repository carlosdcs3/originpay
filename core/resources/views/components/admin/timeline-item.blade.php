@props(['title', 'subtitle', 'id' => null])
<li class="admin-timeline-item">
    <div class="fw-bold">{{ $title }}</div>
    <div class="text-muted small" {!! $id ? 'id="'.$id.'"' : '' !!}>{{ $subtitle ?? $slot }}</div>
</li>
