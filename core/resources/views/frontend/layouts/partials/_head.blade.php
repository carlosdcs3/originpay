<head>
    {{-- Basic Meta Tags --}}
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="author" content="{{ 'OriginPay' }}">
	
	{{-- Dynamic Page Title --}}
	<title>{{ $meta['meta']['title'] ?? 'OriginPay' }}</title>
    
    {{-- SEO Meta Tags --}}
	<meta name="description" content="{{ $meta['meta']['description'] ?? '' }}">
	<meta name="keywords" content="{{ $meta['meta']['keywords'] ?? '' }}">
	<meta name="author" content="{{ 'OriginPay' }}">
	<meta name="csrf-token" content="{{ csrf_token() }}">
	
	{{-- Canonical URL --}}
	<link rel="canonical" href="{{ $meta['meta']['canonical_url'] ?? url()->current() }}">
	
	{{-- Robots Meta --}}
	<meta name="robots" content="{{ $meta['meta']['robots'] ?? 'index,follow' }}">
	
	{{-- Open Graph Tags --}}
	<meta property="og:site_name" content="{{ $meta['meta']['og']['site_name'] ?? 'OriginPay' }}">
	<meta property="og:title" content="{{ $meta['meta']['og']['title'] ?? 'OriginPay' }}">
	<meta property="og:description" content="{{ $meta['meta']['og']['description'] ?? '' }}">
	<meta property="og:url" content="{{ $meta['meta']['og']['url'] ?? url()->current() }}">
	<meta property="og:type" content="{{ $meta['meta']['og']['type'] ?? 'website' }}">
	<meta property="og:image" content="{{ $meta['meta']['og']['image'] ?? '' }}">
	<meta property="og:locale" content="{{ $meta['meta']['og']['locale'] ?? 'en_US' }}">
	
	{{-- Twitter Card Meta --}}
	<meta name="twitter:card" content="{{ $meta['meta']['twitter']['card'] ?? 'summary_large_image' }}">
	@if(!empty($meta['meta']['twitter']['site']))
		<meta name="twitter:site" content="{{ $meta['meta']['twitter']['site'] }}">
	@endif
	<meta name="twitter:title" content="{{ $meta['meta']['twitter']['title'] ?? 'OriginPay' }}">
	<meta name="twitter:description" content="{{ $meta['meta']['twitter']['description'] ?? '' }}">
	<meta name="twitter:image" content="{{ $meta['meta']['twitter']['image'] ?? ''
 }}">
	
	
	{{-- Favicon & Web Manifest --}}
    <link rel="icon" href="{{ asset('frontend/images/originpay/originpay-app-icon.svg') }}" type="image/svg+xml">
    <link rel="shortcut icon" href="{{ asset('frontend/images/originpay/originpay-app-icon.png') }}" type="image/png">
    <link rel="apple-touch-icon" href="{{ asset('frontend/images/originpay/apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <meta name="theme-color" content="#7C3AED">
    
    {{-- Core CSS --}}
    <link rel="stylesheet" href="{{ asset('general/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('general/css/fontawesome.min.css') }}">
	<link rel="stylesheet" href="{{ asset('general/css/simple-notify.min.css') }}">
    <link rel="stylesheet" href="{{ asset('general/css/originpay-notify.css') }}">
	
	
	{{-- Frontend Plugins CSS --}}
    <link rel="stylesheet" href="{{ asset('frontend/css/animate.css') }}">
    <link rel="stylesheet" href="{{ asset('frontend/css/magnific-popup.css') }}">
    <link rel="stylesheet" href="{{ asset('frontend/css/swiper-bundle.min.css') }}">
    <link rel="stylesheet" href="{{ asset('frontend/css/nice-select.css') }}">
    <link rel="stylesheet" href="{{ asset('frontend/css/icomoon.css') }}">
    <link rel="stylesheet" href="{{ asset('frontend/css/main.css') }}">
	
	{{-- Custom CSS --}}
	@include('frontend.layouts.partials.custom.code-css')
    
    {{-- Extra Styles --}}
    @yield('styles')
    @stack('styles')
</head>
