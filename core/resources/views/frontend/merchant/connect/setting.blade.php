@extends('frontend.merchant.connect.layout')
@section('title', 'Origin Connect - Configurações')

@section('connect_content')
<div class="v2-settings-card">
    <div class="v2-settings-header" style="justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div class="v2-settings-header-icon" style="background: rgba(124,58,237,0.1); color: var(--ds-primary-light);">
                <i class="fas fa-list"></i>
            </div>
            <div>
                <h5 class="v2-settings-title">Configurações</h5>
                <p class="v2-settings-desc">Gestão de Configurações do Origin Connect.</p>
            </div>
        </div>
        <div style="display: flex; gap: 10px; align-items: center;">
            <button class="v2-btn-primary" style="height: 36px; padding: 0 16px; font-size: 0.8125rem; gap: 7px;">
                <i class="fas fa-plus" style="font-size: 0.75rem;"></i> Novo
            </button>
        </div>
    </div>
    <div class="v2-settings-body">
        <p class="text-muted">Lista de Configurações aparecerá aqui.</p>
    </div>
</div>
@endsection

