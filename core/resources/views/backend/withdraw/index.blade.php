@extends('backend.layouts.app')
@section('content')
    {{--Withdraw Header Dynamic Content Show Here--}}
    @yield('withdraw_header')
    <div class="card border-0 px-3 py-4 shadow-sm">
        <div class="py-3">
            {{-- Withdraw Dynamic Content Show Here --}}
            @yield('withdraw_content')
        </div>
    </div>
@endsection
