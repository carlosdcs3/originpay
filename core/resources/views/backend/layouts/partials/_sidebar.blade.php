<div class="sidebar sidebar-dark sidebar-fixed" id="sidebar">

    <div class="sidebar-brand d-flex align-items-center justify-content-center p-4 mb-2" style="border-bottom: 1px solid rgba(255,255,255,0.06);">
        <img width="140" src="{{ asset('frontend/images/originpay/originpay-logo-horizontal-dark.svg') }}" alt="OriginPay" style="filter: brightness(0) invert(1);">
    </div>

    <ul class="sidebar-nav overflow-auto" data-coreui="navigation" data-simplebar style="padding: 12px 16px 24px; gap: 8px;">
        @php
            $adminMenus = config('admin_menus');

            if (!function_exists('checkRouteIsActive')) {
                function checkRouteIsActive($route) {
                    $patterns = is_array($route) ? $route : [$route];
                    $allPatterns = [];
                    foreach($patterns as $p) {
                        $allPatterns[] = $p;
                        $allPatterns[] = str_ends_with($p, '.index') ? str_replace('.index', '.*', $p) : $p . '.*';
                    }
                    return request()->routeIs($allPatterns) ? 'active' : '';
                }
            }
            
            if (!function_exists('checkGroupIsShow')) {
                function checkGroupIsShow($routes) {
                    return checkRouteIsActive($routes) ? 'show' : '';
                }
            }
        @endphp

        @foreach($adminMenus as $section)
            @php
                $hasPermissionForSection = false;
                foreach($section['menus'] as $menu) {
                    $permission = $menu['permission'] ?? null;
                    if(is_null($permission) || auth()->guard('admin')->user()->can($permission)) {
                        $hasPermissionForSection = true;
                        break;
                    }
                }
            @endphp
            
            @if($hasPermissionForSection)
                @php
                    $sectionRoutes = collect($section['menus'])
                        ->flatMap(function ($menu) {
                            if (($menu['type'] ?? 'single') === 'groups') {
                                return collect($menu['sub_menus'] ?? [])->pluck('route');
                            }

                            return [$menu['route'] ?? null];
                        })
                        ->filter()
                        ->push($section['route'])
                        ->all();
                @endphp
                <li class="nav-item mb-1">
                    <a class="nav-link {{ checkRouteIsActive($sectionRoutes) }}"
                       href="{{ route($section['route']) }}"
                       style="border-radius: 10px; min-height: 44px;">
                        <x-icon name="{{ $section['icon'] }}" class="nav-icon"/>
                        <span>{{ __($section['label']) }}</span>
                    </a>
                </li>
            @endif
        @endforeach
    </ul>
</div>
