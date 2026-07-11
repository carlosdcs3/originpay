@extends('backend.layouts.app')
@section('title')
    {{ __('Clientes') }}
@endsection
@section('content')
    @php
        $adminMenus = config('admin_menus');
        $customersMenu = collect($adminMenus)->firstWhere('label', 'Clientes');
    @endphp
    
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div>
            <h1 class="fs-5 fw-semibold mb-0" style="color: var(--ds-text);">@yield('customers_title', 'Clientes')</h1>
            <p style="color: var(--ds-text-muted); font-size: 0.75rem; margin: 0;">@yield('customers_desc', 'Gestão de Usuários e KYC')</p>
        </div>
        <div class="btn-toolbar">
            @yield('customers_action')
        </div>
    </div>

    <div class="row g-2">
        {{-- Inner Navigation Sidebar --}}
        <div class="col-12 col-md-4 col-lg-3">
            <x-ds.card class="p-2 h-100">
                @if($customersMenu && isset($customersMenu['menus']))
                    <div class="d-flex flex-column gap-1">
                        @foreach($customersMenu['menus'] as $menu)
                            <a href="{{ route($menu['route'] ?? (isset($menu['sub_menus']) ? $menu['sub_menus'][0]['route'] : '#'), $menu['params'] ?? []) }}" 
                               class="d-flex align-items-center gap-2 py-2 px-3 mb-1 rounded text-decoration-none"
                               style="
                                  color: {{ (isset($menu['route']) && isActive($menu['route'] , $menu['params'] ?? [])) || (isset($menu['sub_menus']) && collect($menu['sub_menus'])->contains(fn($sub) => isActive($sub['route']))) ? 'var(--ds-accent)' : 'var(--ds-text-secondary)' }}; 
                                  background: {{ (isset($menu['route']) && isActive($menu['route'] , $menu['params'] ?? [])) || (isset($menu['sub_menus']) && collect($menu['sub_menus'])->contains(fn($sub) => isActive($sub['route']))) ? 'var(--ds-accent-muted)' : 'transparent' }}; 
                                  transition: background 0.2s, color 0.2s;
                               "
                               onmouseover="this.style.background='var(--ds-bg)'; this.style.color='var(--ds-text)';"
                               onmouseout="this.style.background='{{ (isset($menu['route']) && isActive($menu['route'] , $menu['params'] ?? [])) || (isset($menu['sub_menus']) && collect($menu['sub_menus'])->contains(fn($sub) => isActive($sub['route']))) ? 'var(--ds-accent-muted)' : 'transparent' }}'; this.style.color='{{ (isset($menu['route']) && isActive($menu['route'] , $menu['params'] ?? [])) || (isset($menu['sub_menus']) && collect($menu['sub_menus'])->contains(fn($sub) => isActive($sub['route']))) ? 'var(--ds-accent)' : 'var(--ds-text-secondary)' }}';">
                                
                                <div style="
                                    width: 28px; 
                                    height: 28px; 
                                    border-radius: 6px; 
                                    background: {{ (isset($menu['route']) && isActive($menu['route'] , $menu['params'] ?? [])) || (isset($menu['sub_menus']) && collect($menu['sub_menus'])->contains(fn($sub) => isActive($sub['route']))) ? 'var(--ds-accent)' : 'var(--ds-surface)' }};
                                    color: {{ (isset($menu['route']) && isActive($menu['route'] , $menu['params'] ?? [])) || (isset($menu['sub_menus']) && collect($menu['sub_menus'])->contains(fn($sub) => isActive($sub['route']))) ? '#fff' : 'var(--ds-text-muted)' }};
                                    display: flex; 
                                    align-items: center; 
                                    justify-content: center;
                                ">
                                    <x-icon name="{{ $menu['icon'] ?? 'users-1' }}" height="14" width="14"/>
                                </div>
                                <span style="font-weight: 500; font-size: 0.85rem;">{{ title($menu['label']) }}</span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </x-ds.card>
        </div>
        
        {{-- Main Content Area --}}
        <div class="col-12 col-md-8 col-lg-9 customers-content">
            @yield('customers_content')
        </div>
    </div>

    @push('styles')
    <style>
        /* Automatically enforce dark mode styles for customers */
        .customers-content .card,
        .customers-content .ds-card {
            background-color: var(--ds-surface) !important;
            --bs-card-bg: var(--ds-surface) !important;
        }
        .customers-content .card-body,
        .customers-content .card-header {
            background-color: transparent !important;
            color: var(--ds-text);
        }
        .customers-content .text-muted {
            color: var(--ds-text-secondary) !important;
        }
        .customers-content h1, .customers-content h2, .customers-content h3, 
        .customers-content h4, .customers-content h5, .customers-content h6,
        .customers-content .card-title,
        .customers-content td.fw-semibold {
            color: var(--ds-text) !important;
        }
        
        /* Table fixes */
        .customers-content .table-responsive {
            background-color: transparent !important;
        }
        .customers-content table, 
        .customers-content .table,
        .customers-content table tr,
        .customers-content table td,
        .customers-content table th {
            background-color: transparent !important;
            color: var(--ds-text) !important;
            --bs-table-bg: transparent !important;
            --bs-table-color: var(--ds-text) !important;
            --bs-table-hover-bg: rgba(255,255,255,0.04) !important;
        }
        .customers-content thead {
            background: var(--ds-surface-hover, rgba(0,0,0,0.02)) !important;
        }
        .customers-content thead th {
            color: var(--ds-text-muted) !important;
        }
        .customers-content .border-bottom {
            border-bottom-color: rgba(255,255,255,0.06) !important;
        }
        .customers-content .border-end {
            border-right-color: rgba(255,255,255,0.06) !important;
        }
    </style>
    @endpush
@endsection
