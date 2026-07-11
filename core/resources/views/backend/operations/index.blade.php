@extends('backend.layouts.app')
@section('title')
    {{ __('Operações') }}
@endsection
@section('content')
    @php
        $adminMenus = config('admin_menus');
        $operationsMenu = collect($adminMenus)->firstWhere('label', 'Operações');
    @endphp
    
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div>
            <h1 class="fs-5 fw-semibold mb-0" style="color: var(--ds-text);">@yield('operations_title', 'Operações')</h1>
            <p style="color: var(--ds-text-muted); font-size: 0.75rem; margin: 0;">@yield('operations_desc', 'Monitoramento e Centro de Comando')</p>
        </div>
        <div class="btn-toolbar">
            @yield('operations_action')
        </div>
    </div>

    <div class="d-flex flex-column flex-md-row gap-2 align-items-start">
        {{-- Inner Navigation Sidebar --}}
        <div style="width: 100%; max-width: 280px; flex-shrink: 0;" class="operations-sidebar">
            <x-ds.card class="p-2 h-100">
                @if($operationsMenu && isset($operationsMenu['menus']))
                    <div class="d-flex flex-column gap-1">
                        @foreach($operationsMenu['menus'] as $menu)
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
        
        {{-- Main Content Area --}}
        <div class="operations-content" style="flex-grow: 1; min-width: 0;">
            @yield('operations_content')
        </div>
    </div>

    @push('styles')
    <style>
        /* Automatically enforce dark mode styles for operations */
        .operations-content .card,
        .operations-content .ds-card {
            background-color: var(--ds-surface) !important;
            --bs-card-bg: var(--ds-surface) !important;
        }
        .operations-content .card-body,
        .operations-content .card-header {
            background-color: transparent !important;
            color: var(--ds-text);
        }
        .operations-content .text-muted {
            color: var(--ds-text-secondary) !important;
        }
        .operations-content h1, .operations-content h2, .operations-content h3, 
        .operations-content h4, .operations-content h5, .operations-content h6,
        .operations-content .card-title,
        .operations-content td.fw-semibold {
            color: var(--ds-text) !important;
        }
        
        /* Table fixes */
        .operations-content .table-responsive {
            background-color: transparent !important;
        }
        .operations-content table, 
        .operations-content .table,
        .operations-content table tr,
        .operations-content table td,
        .operations-content table th {
            background-color: transparent !important;
            color: var(--ds-text) !important;
            --bs-table-bg: transparent !important;
            --bs-table-color: var(--ds-text) !important;
            --bs-table-hover-bg: rgba(255,255,255,0.04) !important;
        }
        .operations-content thead {
            background: var(--ds-surface-hover, rgba(0,0,0,0.02)) !important;
        }
        .operations-content thead th {
            color: var(--ds-text-muted) !important;
        }
        .operations-content .border-bottom {
            border-bottom-color: rgba(255,255,255,0.06) !important;
        }
        .operations-content .border-end {
            border-right-color: rgba(255,255,255,0.06) !important;
        }
    </style>
    @endpush
@endsection
