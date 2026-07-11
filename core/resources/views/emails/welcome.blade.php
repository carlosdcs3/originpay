@extends('emails.layout')

@section('content')
<h2>Olá, {{ $name }}!</h2>
<p>Seja muito bem-vindo à OriginPay. Sua conta foi criada com sucesso.</p>
<p>Nossa plataforma foi projetada para oferecer a você a melhor experiência em pagamentos digitais, com APIs modernas, alta conversão e estabilidade absoluta.</p>

<div style="text-align: center;">
    <a href="{{ $ctaUrl }}" class="btn">Acessar minha Dashboard</a>
</div>

<p style="margin-top: 30px;">
    Se precisar de ajuda para configurar sua integração, nossa documentação e equipe de suporte estão à disposição.
</p>
@endsection
