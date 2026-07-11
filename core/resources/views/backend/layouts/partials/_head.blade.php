<head>
    {{-- Meta Tags --}}
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    {{-- Page Title --}}
    <title>{{ 'OriginPay' }} | @yield('title')</title>
    
    {{-- Favicon & Web Manifest --}}
    <link rel="icon" href="{{ asset('frontend/images/originpay/originpay-app-icon.svg') }}" type="image/svg+xml">
    <link rel="shortcut icon" href="{{ asset('frontend/images/originpay/originpay-app-icon.png') }}" type="image/png">
    <link rel="apple-touch-icon" href="{{ asset('frontend/images/originpay/apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <meta name="theme-color" content="#7C3AED">
    {{-- Enterprise Typography: Inter Font --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    {{-- Core Vendor CSS --}}
    <link rel="stylesheet" href="{{ asset('general/css/fontawesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('general/css/chartjs.css') }}">
    <link rel="stylesheet" href="{{ asset('general/css/simple-notify.min.css') }}">
    <link rel="stylesheet" href="{{ asset('general/css/originpay-notify.css') }}">
    <link rel="stylesheet" href="{{ asset('general/css/daterangepicker.css') }}">
    
    {{-- Plugin CSS --}}
    <link rel="stylesheet" href="{{ asset('backend/css/summernote-lite.min.css') }}">
    <link rel="stylesheet" href="{{ asset('backend/css/tagify.css') }}">
    
    {{-- Application CSS --}}
    <link rel="stylesheet" href="{{ asset('general/css/common.css') }}">
    <link rel="stylesheet" href="{{ asset('backend/css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('backend/css/custom.css?v=' . config('app.version')) }}"/>
    <link rel="stylesheet" href="{{ asset('general/css/admin-enterprise.css') }}?v={{ filemtime(public_path('general/css/admin-enterprise.css')) }}"/>
    
    {{-- Page Specific Styles --}}
    @yield('styles')
    @stack('styles')
</head>


