@extends('frontend.merchant.connect.layout')
@section('title', 'Origin Connect - Em Breve')

@section('connect_content')
<div class="v2-settings-card" style="height: 100%; display: flex; align-items: center; justify-content: center;">
    <div class="v2-settings-body" style="text-align: center; padding: 50px 20px;">
        <div style="font-size: 3rem; color: var(--ds-primary); opacity: 0.5; margin-bottom: 20px;">
            <i class="fas fa-tools"></i>
        </div>
        <h4 style="font-weight: 700; color: var(--ds-text-main); margin-bottom: 10px;">Página em Construção</h4>
        <p style="color: var(--ds-text-muted); font-size: 0.95rem; max-width: 400px; margin: 0 auto 25px;">
            Esta funcionalidade está sendo desenvolvida e estará disponível na próxima atualização do Origin Connect.
        </p>
        <button onclick="history.back()" class="v2-btn-secondary" style="height: 38px; padding: 0 20px;">
            Voltar
        </button>
    </div>
</div>
@endsection
