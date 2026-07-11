@include('backend.settings.site.partials.fields._level')
<textarea  name="{{ $field['key'] }}" class="form-control form-control-sm" id="{{ $field['key'] }}"
           placeholder="{{ title($field['label']) }}">{{ setting($field['key'],$field['value']) }}</textarea>
