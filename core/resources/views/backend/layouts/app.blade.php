<!doctype html>
<html lang="en">
@include('backend.layouts.partials._head')
<body class="originpay-admin">

@include('backend.layouts.partials._sidebar')

<div class="wrapper d-flex flex-column min-vh-100">
    @include('backend.layouts.partials._header')
    
    <div class="body flex-grow-1 px-0">
        <div class="container-fluid px-4 pt-3">
            @if(\Illuminate\Support\Facades\Cache::get('system_withdrawals_paused'))
                <div class="alert alert-danger text-center" style="font-weight:bold; font-size: 16px;">
                    <i class="fas fa-exclamation-triangle"></i> ATENÇÃO: KILL SWITCH DE SAQUES ATIVADO. TODO PROCESSAMENTO DE SAQUES ESTÁ SUSPENSO.
                </div>
            @endif

            {{-- Any Warning Messages Here --}}
            @include('backend.partials._messages')
            
             {{--  Main Content --}}
             @yield('content')
        </div>
    </div>

    {{-- delete modal --}}
    @include('backend.partials._delete_modal')

</div>

@include('backend.layouts.partials._scripts')
@include('partials.confirm_modal')
<script src="{{ asset('assets/js/ds-confirm.js') }}"></script>
</body>
</html>

