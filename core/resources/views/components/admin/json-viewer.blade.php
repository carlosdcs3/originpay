@props(['id'])
<pre id="{{ $id }}" class="bg-light p-3 rounded text-muted small" style="max-height: 250px; overflow-y: auto;">
{{ $slot }}
</pre>
