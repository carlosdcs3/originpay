@extends('backend.layouts.app')
@section('title')
    @yield('hub_title')
@endsection
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div>
            <h1 class="fs-5 fw-semibold mb-0" style="color: var(--ds-text);">@yield('hub_title')</h1>
            <p style="color: var(--ds-text-muted); font-size: 0.85rem; margin: 0;">@yield('hub_desc')</p>
        </div>
        <div class="btn-toolbar">
            @yield('hub_action')
        </div>
    </div>

    <div class="row g-3">
        {{-- Inner Navigation Sidebar --}}
        <div class="col-12 col-md-4 col-lg-3">
            <x-ds.card class="p-2 h-100">
                <div class="d-flex flex-column gap-1">
                    @yield('hub_nav')
                </div>
            </x-ds.card>
        </div>

        {{-- Main Content --}}
        <div class="col-12 col-md-8 col-lg-9">
            @yield('hub_content')
        </div>
    </div>
@endsection
