@extends('emails.layout')

@section('content')
<h2>Olá, {{ $name }}!</h2>
<p>Boas notícias! Você acaba de receber um novo pagamento.</p>

<div style="background-color: #f8fafc; border: 1px solid #e2e8f0; padding: 20px; border-radius: 6px; margin: 20px 0; text-align: center;">
    <span style="display: block; font-size: 14px; color: #64748b; margin-bottom: 5px;">Valor Recebido</span>
    <span style="display: block; font-size: 32px; font-weight: bold; color: #10b981;">R$ {{ $amount }}</span>
    <span style="display: block; font-size: 12px; color: #94a3b8; margin-top: 10px;">ID da Cobrança: {{ $chargeId }}</span>
</div>

<p>O valor já foi creditado no seu saldo e está disponível conforme as regras do seu plano de liquidação.</p>

<p style="margin-top: 30px;">
    Atenciosamente,<br>Equipe OriginPay
</p>
@endsection
