@extends('backend.settings.index')
@section('setting_title', __('Empresa'))

@section('setting_content')
    @php($activeSection = session('section') ?? 'general_settings')
    
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-transparent border-bottom pt-2 pb-0">
            <ul class="nav nav-tabs card-header-tabs" id="siteSettingsTabs" role="tablist">
                @foreach($settingMenus as $name => $icon)
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold text-uppercase {{ $activeSection === $name ? 'active' : '' }}" 
                                style="letter-spacing: 0.05em; font-size: 0.75rem;"
                                id="tab-{{ $name }}"
                                data-coreui-toggle="tab" data-coreui-target="#content-{{ $name }}" 
                                type="button" role="tab"
                                aria-controls="content-{{ $name }}"
                                aria-selected="{{ $activeSection === $name ? 'true' : 'false' }}">
                            <x-icon name="{{ $icon }}" height="16" width="16" class="me-1 mb-1"/> {{ title($name) }}
                        </button>
                    </li>
                @endforeach
            </ul>
        </div>
        
        <div class="card-body p-2">
            <div class="tab-content" id="siteSettingsTabsContent">
                @foreach($settings as $section => $fields)
                    <div class="tab-pane fade {{ $activeSection === $section ? 'show active' : '' }}"
                         id="content-{{ $section }}" role="tabpanel" aria-labelledby="tab-{{ $section }}"
                         tabindex="0">
                        
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="mb-0 text-dark fw-bold">{{ title($section) }}</h5>
                            
                            @if (isset($fields['include_partials']))
                                <div>
                                    @include('backend.settings.site.partials.' . $fields['include_partials'],['section' => $section])
                                </div>
                            @endif
                        </div>
                        

                        @if (isset($fields['info']))
                            <div class="alert alert-info border-0 rounded-2 mb-2" style="background: var(--ds-accent-muted); color: var(--ds-accent); font-size: 14px;">
                                <div class="fw-bold mb-1">
                                    <i class="la la-info-circle me-1"></i> {{ __('Importante') }}
                                </div>
                                <div>
                                    {{ $fields['info'] }}
                                </div>
                            </div>
                        @endif
                        
                        <form method="POST" action="{{ route('admin.settings.site.update', $section) }}"
                              enctype="multipart/form-data">
                            @method('PUT')
                            @csrf
                            <div class="row g-2">
                                @foreach($fields['elements'] as $key => $field)
                                    @if($field['type'] !== 'hidden')
                                        <div class="{{ $field['class'] }}">
                                            @include('backend.settings.site.partials.fields.' . $field['type'], ['field' => $field])
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                            <div class="mt-2 text-end">
                                <x-form.submit-button icon="check">
                                    {{ __('Salvar alterações') }}
                                </x-form.submit-button>
                            </div>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
@push('styles')
<style>
    .nav-tabs .nav-link {
        color: var(--ds-text-muted);
        border: none !important;
        border-bottom: 2px solid transparent !important;
        padding-bottom: 0.75rem;
        padding-top: 0.75rem;
        margin-right: 1rem;
        font-weight: 500;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        transition: color 0.2s;
        background: transparent !important;
    }
    .nav-tabs .nav-link:hover {
        color: var(--ds-text);
        border-color: transparent !important;
    }
    .nav-tabs .nav-link.active,
    .nav-tabs .nav-item.show .nav-link {
        color: var(--ds-accent) !important;
        border-bottom: 2px solid var(--ds-accent) !important;
        background: transparent !important;
    }
    .nav-tabs {
        border-bottom: 1px solid rgba(255,255,255,0.06) !important;
    }
</style>
@endpush
@push('scripts')
   @include('backend.settings.site.partials._script')
@endpush
