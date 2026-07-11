@extends('backend.layouts.app')
@section('title')
    {{ __('Financeiro') }}
@endsection
@section('content')
    @php
        $adminMenus = config('admin_menus');
        $financeMenu = collect($adminMenus)->firstWhere('label', 'Financeiro');
    @endphp
    
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div>
            <h1 class="fs-5 fw-semibold mb-0" style="color: var(--ds-text);">@yield('finance_title', 'Financeiro')</h1>
            <p style="color: var(--ds-text-muted); font-size: 0.75rem; margin: 0;">@yield('finance_desc', 'Gestão Financeira e Transações')</p>
        </div>
        <div class="btn-toolbar">
            @yield('finance_action')
        </div>
    </div>

    <div class="d-flex flex-column flex-md-row gap-2 align-items-start">
        {{-- Inner Navigation Sidebar --}}
        <div style="width: 100%; max-width: 280px; flex-shrink: 0;" class="finance-sidebar">
            <x-ds.card class="p-2 h-100">
                @if($financeMenu && isset($financeMenu['menus']))
                    <div class="d-flex flex-column gap-1">
                        @foreach($financeMenu['menus'] as $menu)
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
        <div class="finance-content" style="flex-grow: 1; min-width: 0;">
            @yield('finance_content')
        </div>
    </div>

    @push('styles')
    <style>
        /* Automatically enforce dark mode styles for finance */
        .finance-content .card,
        .finance-content .ds-card {
            background-color: var(--ds-surface) !important;
            --bs-card-bg: var(--ds-surface) !important;
        }
        .finance-content .card-body,
        .finance-content .card-header {
            background-color: transparent !important;
            color: var(--ds-text);
        }
        .finance-content .text-muted {
            color: var(--ds-text-secondary) !important;
        }
        .finance-content h1, .finance-content h2, .finance-content h3, 
        .finance-content h4, .finance-content h5, .finance-content h6,
        .finance-content .card-title,
        .finance-content td.fw-semibold {
            color: var(--ds-text) !important;
        }
        
        /* Table fixes */
        .finance-content .table-responsive {
            background-color: transparent !important;
        }
        .finance-content table, 
        .finance-content .table,
        .finance-content table tr,
        .finance-content table td,
        .finance-content table th {
            background-color: transparent !important;
            color: var(--ds-text) !important;
            --bs-table-bg: transparent !important;
            --bs-table-color: var(--ds-text) !important;
            --bs-table-hover-bg: rgba(255,255,255,0.04) !important;
        }
        .finance-content thead {
            background: var(--ds-surface-hover, rgba(0,0,0,0.02)) !important;
        }
        .finance-content thead th {
            color: var(--ds-text-muted) !important;
        }
        .finance-content .border-bottom {
            border-bottom-color: rgba(255,255,255,0.06) !important;
        }
        .finance-content .border-end {
            border-right-color: rgba(255,255,255,0.06) !important;
        }
    </style>
    @endpush
@endsection
