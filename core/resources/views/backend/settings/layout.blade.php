@extends('backend.layouts.hub')

@section('hub_title', 'Configurações')
@section('hub_desc', 'Gerencie as preferências da plataforma, empresa e equipe.')

@section('hub_action')
    @yield('setting_action')
@endsection

@section('hub_nav')
    <style>
        .setting-nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 16px;
            margin-bottom: 6px;
            border-radius: 8px;
            text-decoration: none;
            color: var(--ds-text-secondary);
            transition: all 0.2s ease-in-out;
            background: transparent;
            border: 1px solid transparent;
        }
        .setting-nav-link:hover {
            background: rgba(255, 255, 255, 0.03);
            color: var(--ds-text);
        }
        .setting-nav-link.active {
            background: rgba(255, 255, 255, 0.06);
            color: #fff;
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.05);
            border-color: rgba(255,255,255,0.02);
        }
        .setting-nav-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            color: var(--ds-text-muted);
            transition: all 0.2s;
        }
        .setting-nav-link:hover .setting-nav-icon {
            color: var(--ds-text);
        }
        .setting-nav-link.active .setting-nav-icon {
            color: var(--ds-accent);
        }
        .setting-nav-label {
            font-weight: 500;
            font-size: 0.875rem;
            letter-spacing: 0.02em;
        }
        .setting-nav-link.active .setting-nav-label {
            font-weight: 600;
        }
    </style>

    @php
        $adminMenus = config('admin_menus');
        $settingsMenu = collect($adminMenus)->firstWhere('label', 'Configurações');
    @endphp
    @if($settingsMenu && isset($settingsMenu['menus']))
        @foreach($settingsMenu['menus'] as $menu)
            @php $isActive = isActive($menu['route'] , $menu['params'] ?? []); @endphp
            <a href="{{ route($menu['route'], $menu['params'] ?? []) }}" 
               class="setting-nav-link {{ $isActive ? 'active' : '' }}">
                
                <div class="setting-nav-icon">
                    <x-icon name="{{ $menu['icon'] }}" height="18" width="18"/>
                </div>
                <span class="setting-nav-label">{{ title($menu['label']) }}</span>
            </a>
        @endforeach
    @endif
@endsection

@section('hub_content')
    @yield('setting_content')
@endsection
