@extends('backend.settings.layout')
@section('setting_title', 'Plataforma')

@section('setting_content')
@php
    $platformGeneral = [
        'Nome da plataforma' => config('app.name', 'OriginPay'),
        'URL principal' => config('app.url'),
        'Prefixo admin' => 'admin',
        'Timezone' => config('app.timezone'),
        'Idioma' => config('app.locale'),
        'Moeda' => config('app.currency', 'BRL'),
    ];

    $appearance = [
        'Logo' => asset('assets/global/images/logo.png'),
        'Logo escura' => asset('assets/global/images/logo_white.png'),
        'Favicon' => asset('assets/global/images/favicon.png'),
        'Banner do login' => asset('assets/global/images/login_banner.png'),
    ];

    $mailConfig = [
        'Driver' => config('mail.default'),
        'Host' => config('mail.mailers.smtp.host'),
        'Porta' => config('mail.mailers.smtp.port'),
        'Encryption' => config('mail.mailers.smtp.encryption'),
        'Nome remetente' => config('mail.from.name'),
        'E-mail remetente' => config('mail.from.address'),
    ];

    $security = [
        'Sessão em minutos' => config('session.lifetime'),
        'Driver da sessão' => config('session.driver'),
        'ReCAPTCHA' => config('services.recaptcha.key') ? 'Configurado' : 'Não configurado',
        'Sessão única' => 'Não mapeado neste ambiente',
        'IPs confiáveis' => 'Não configurado neste ambiente',
    ];

    $maintenance = [
        'Modo manutenção' => app()->isDownForMaintenance() ? 'Ativo' : 'Inativo',
        'Mensagem pública' => 'Controlada pelo backend de manutenção',
        'IPs liberados' => 'Não configurado neste ambiente',
        'Acesso admin durante manutenção' => 'Controlado pela política operacional',
    ];
@endphp

<div class="row g-4">
    <div class="col-xl-6">
        <x-ds.card class="border-0 shadow-sm rounded-3 h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Geral</h6>
                @foreach($platformGeneral as $label => $value)
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="text-muted small">{{ $label }}</span>
                        <span class="fw-semibold text-end">{{ $value ?: 'Não configurado' }}</span>
                    </div>
                @endforeach
            </div>
        </x-ds.card>
    </div>

    <div class="col-xl-6">
        <x-ds.card class="border-0 shadow-sm rounded-3 h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-3">E-mail</h6>
                @foreach($mailConfig as $label => $value)
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="text-muted small">{{ $label }}</span>
                        <span class="fw-semibold text-end">{{ $value ?: 'Não configurado' }}</span>
                    </div>
                @endforeach
            </div>
        </x-ds.card>
    </div>

    <div class="col-xl-6">
        <x-ds.card class="border-0 shadow-sm rounded-3 h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Segurança</h6>
                @foreach($security as $label => $value)
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="text-muted small">{{ $label }}</span>
                        <span class="fw-semibold text-end">{{ $value ?: 'Não configurado' }}</span>
                    </div>
                @endforeach
            </div>
        </x-ds.card>
    </div>

    <div class="col-xl-6">
        <x-ds.card class="border-0 shadow-sm rounded-3 h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Manutenção</h6>
                @foreach($maintenance as $label => $value)
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="text-muted small">{{ $label }}</span>
                        <span class="fw-semibold text-end">{{ $value }}</span>
                    </div>
                @endforeach
            </div>
        </x-ds.card>
    </div>

    <div class="col-12">
        <x-ds.card class="border-0 shadow-sm rounded-3">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Aparência</h6>
                <div class="row g-3">
                    @foreach($appearance as $label => $assetPath)
                        <div class="col-md-3">
                            <div class="border rounded-3 p-3 h-100 text-center">
                                <div class="text-muted small mb-2">{{ $label }}</div>
                                <img src="{{ $assetPath }}" alt="{{ $label }}" class="img-fluid" style="max-height: 48px;">
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </x-ds.card>
    </div>

    <div class="col-12">
        <div class="alert alert-info border-0">
            Esta tela mostra somente configurações detectadas no ambiente atual. Alterações centralizadas ainda não estão conectadas a um backend dedicado nesta rota.
        </div>
    </div>
</div>
@endsection
