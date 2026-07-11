@extends('backend.layouts.app')
@section('title')
    {{ __('Marketing') }}
@endsection
@section('content')
    @php
        $adminMenus = config('admin_menus');
        $marketingMenu = collect($adminMenus)->firstWhere('label', 'Marketing');
    @endphp
    
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div>
            <h1 class="fs-5 fw-semibold mb-0" style="color: var(--ds-text);">@yield('marketing_title', 'Marketing')</h1>
            <p style="color: var(--ds-text-muted); font-size: 0.75rem; margin: 0;">@yield('marketing_desc', 'Gerencie as campanhas, páginas e modelos de notificação.')</p>
        </div>
        <div class="btn-toolbar">
            @yield('marketing_action')
        </div>
    </div>

    <div class="row g-2">
        {{-- Inner Navigation Sidebar --}}
        <div class="col-12 col-md-4 col-lg-3">
            <x-ds.card class="p-2 h-100">
                @if($marketingMenu && isset($marketingMenu['menus']))
                    <div class="d-flex flex-column gap-1">
                        @foreach($marketingMenu['menus'] as $menu)
                            <a href="{{ route($menu['route'], $menu['params'] ?? []) }}" 
                               class="d-flex align-items-center gap-2 py-2 px-3 mb-1 rounded text-decoration-none"
                               style="
                                  color: {{ isActive($menu['route'] , $menu['params'] ?? []) ? 'var(--ds-accent)' : 'var(--ds-text-secondary)' }}; 
                                  background: {{ isActive($menu['route'] , $menu['params'] ?? []) ? 'var(--ds-accent-muted)' : 'transparent' }}; 
                                  transition: background 0.2s, color 0.2s;
                               "
                               onmouseover="this.style.background='var(--ds-bg)'; this.style.color='var(--ds-text)';"
                               onmouseout="this.style.background='{{ isActive($menu['route'] , $menu['params'] ?? []) ? 'var(--ds-accent-muted)' : 'transparent' }}'; this.style.color='{{ isActive($menu['route'] , $menu['params'] ?? []) ? 'var(--ds-accent)' : 'var(--ds-text-secondary)' }}';">
                                
                                <div style="
                                    width: 28px; 
                                    height: 28px; 
                                    border-radius: 6px; 
                                    background: {{ isActive($menu['route'] , $menu['params'] ?? []) ? 'var(--ds-accent)' : 'var(--ds-surface)' }};
                                    color: {{ isActive($menu['route'] , $menu['params'] ?? []) ? '#fff' : 'var(--ds-text-muted)' }};
                                    display: flex; 
                                    align-items: center; 
                                    justify-content: center;
                                ">
                                    <x-icon name="{{ $menu['icon'] }}" height="14" width="14"/>
                                </div>
                                <span style="font-weight: 500; font-size: 0.85rem;">{{ title($menu['label']) }}</span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </x-ds.card>
        </div>

        {{-- Main Content --}}
        <div class="col-12 col-md-8 col-lg-9">
            @yield('marketing_content')
        </div>
    </div>
@endsection
