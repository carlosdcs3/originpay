<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>OriginPay &mdash; @yield('title', 'Acesso')</title>
    <link rel="icon" href="{{ asset('frontend/images/originpay/originpay-app-icon.svg') }}" type="image/svg+xml">
    <link rel="shortcut icon" href="{{ asset('frontend/images/originpay/originpay-app-icon.png') }}" type="image/png">
    <link rel="apple-touch-icon" href="{{ asset('frontend/images/originpay/apple-touch-icon.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('general/css/fontawesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('general/css/simple-notify.min.css') }}">
    <link rel="stylesheet" href="{{ asset('general/css/originpay-notify.css') }}">
    <link rel="stylesheet" href="{{ asset('frontend/css/originpay.css') }}">

    @include('frontend.layouts.partials.custom.code-css')

    <style>
        :root {
            --bg-body: var(--bg-base);
            --text-main: var(--text-primary);
            --border-color: var(--border);
        }

        body {
            background: var(--bg-base);
            font-family: var(--font);
            color: var(--text-primary);
        }

        select option {
            background: var(--bg-card);
            color: var(--text-primary);
        }

        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: var(--primary) !important;
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.15);
        }

        .op-auth-input {
            width: 100%;
            height: 48px;
            padding: 0 16px;
            border-radius: 12px;
            border: 1px solid var(--border);
            background: rgba(255, 255, 255, 0.04);
            color: var(--text-primary);
            font-size: 0.95rem;
            font-family: var(--font);
            outline: none;
            transition: all 0.2s ease;
        }

        .op-auth-submit {
            width: 100%;
            padding: 14px;
            border-radius: 12px;
            border: none;
            background: linear-gradient(135deg, var(--primary), #5b21b6);
            color: #fff;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 6px 18px rgba(109, 40, 217, 0.28);
            position: relative;
            overflow: hidden;
        }

        .op-auth-submit::before {
            display: none;
        }

        @keyframes shimmer {
            0% { left: -100%; }
            100% { left: 160%; }
        }

        .op-auth-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(109, 40, 217, 0.34);
        }

        .op-auth-link {
            color: var(--primary-light);
            text-decoration: none;
            font-weight: 600;
        }

        .op-auth-link:hover {
            text-decoration: underline;
        }

        .op-auth-label {
            display: block;
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .op-auth-error {
            background: rgba(239, 68, 68, 0.08);
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 12px;
            padding: 14px 16px;
            margin-bottom: 24px;
            color: #f87171;
            font-size: 0.85rem;
        }

        .op-auth-card {
            width: 100%;
            max-width: 420px;
        }

        .op-auth-card-wide {
            width: 100%;
            max-width: 520px;
        }

        @media (max-width: 768px) {
            .auth-split-right {
                display: none !important;
            }
        }

        input:-webkit-autofill,
        input:-webkit-autofill:hover,
        input:-webkit-autofill:focus,
        input:-webkit-autofill:active {
            -webkit-box-shadow: 0 0 0 30px #1a1625 inset !important;
            -webkit-text-fill-color: white !important;
            transition: background-color 5000s ease-in-out 0s;
        }
    </style>

    @yield('styles')
    @stack('styles')
</head>
<body>

@yield('auth-content')

<script src="{{ asset('general/js/simple-notify.min.js') }}"></script>
<script src="{{ asset('general/js/helpers.js?v=' . config('app.version')) }}"></script>
<script src="{{ asset('frontend/js/auth.js') }}"></script>

@include('general._notify_evs')

@yield('scripts')
@stack('scripts')
</body>
</html>
