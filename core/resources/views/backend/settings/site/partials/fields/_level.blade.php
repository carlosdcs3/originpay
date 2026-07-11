<label for="{{ $field['key'] }}" class="form-label text-muted small fw-semibold mb-1">{{ title($field['label']) }}</label>
@isset($field['message'])
    <span data-coreui-toggle="tooltip" data-coreui-placement="top" title="{{ $field['message'] }}" class="text-muted">
        <x-icon name="info" height="18"/>
    </span>
@endisset
