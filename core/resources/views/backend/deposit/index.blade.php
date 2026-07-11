@extends('backend.layouts.app')
@section('content')
    {{--Deposit Header Dynamic Content Show Here--}}
    @yield('deposit_header')
    <div class="card border-0 px-3 py-4 shadow-sm">
        <div class="py-3">
            {{-- Deposit Dynamic Content Show Here --}}
            @yield('deposit_content')
        </div>
    </div>
@endsection
