@extends('frontend.merchant.connect.layout')
@section('title', 'Provedores - Origin Connect')
@section('connect_content')
<div class="v2-settings-card">
    <div class="v2-settings-header" style="justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div class="v2-settings-header-icon" style="background: rgba(124,58,237,0.1); color: var(--ds-primary-light);">
                <i class="fas fa-list"></i>
            </div>
            <div>
                <h5 class="v2-settings-title">Provedores de Envio</h5>
                <p class="v2-settings-desc">Gerencie integrações e provedores externos.</p>
            </div>
        </div>
        <div style="display: flex; gap: 10px; align-items: center;">
            <button class="v2-btn-primary">Novo Provedor</button>
        </div>
    </div>
    
    <div class="v2-settings-body">
        <!-- Table listing -->
        <table class="table">
        <tr>
            <th>Provedor</th>
            <th>Canal</th>
            <th>Token / Chave</th>
            <th>Health Score</th>
            <th>Ações</th>
        </tr>
        <tr>
            <td>AWS SES</td>
            <td>Email</td>
            <td><code style="background: #eee; padding: 2px 6px;">••••••••••••••••</code></td>
            <td><span style="color: green;">99.9%</span></td>
            <td>
                <button class="v2-btn-secondary">Testar</button>
                <button class="v2-btn-secondary">Substituir credencial</button>
            </td>
        </tr>
    </table>
    </div>
</div>
@endsection

