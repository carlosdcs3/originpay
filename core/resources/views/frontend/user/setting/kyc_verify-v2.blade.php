@php use App\Enums\KycStatus; @endphp
@extends('frontend.user.setting.index')
@section('title', __('Verificação KYC'))

@section('user_setting_content')

<style>
@media (max-width: 768px) {
    .kyc-status-alert {
        padding: 12px !important;
        gap: 10px !important;
        margin-bottom: 14px !important;
        border-radius: 10px !important;
    }

    .kyc-reasons {
        padding: 12px !important;
        margin-bottom: 14px !important;
        border-radius: 10px !important;
    }

    .kyc-reasons-title {
        margin-bottom: 10px !important;
        font-size: .68rem !important;
    }

    .kyc-reasons-list {
        gap: 8px !important;
    }

    .kyc-reason-item {
        gap: 10px !important;
        align-items: flex-start !important;
    }

    .kyc-reason-item i {
        margin-top: 2px !important;
        font-size: .78rem !important;
    }

    .kyc-reason-item span {
        font-size: .78rem !important;
        line-height: 1.35 !important;
    }

    .kyc-field {
        margin-bottom: 14px !important;
    }

    #template-select {
        height: 40px !important;
        border-radius: 8px !important;
        font-size: .86rem !important;
        padding-right: 34px !important;
    }

    #kyc-note {
        min-height: 88px !important;
        border-radius: 8px !important;
        font-size: .84rem !important;
        padding: 10px 12px !important;
    }

    .kyc-submit-footer {
        padding-top: 12px !important;
    }

    .kyc-submit-footer .v2-btn-primary {
        height: 40px !important;
        border-radius: 8px !important;
        font-size: .82rem !important;
    }
}
</style>

<div class="v2-page-header" style="margin-bottom: 28px;">
    <h2 class="v2-page-title" style="font-size: 1.5rem; font-weight: 700; margin: 0 0 6px; color: var(--ds-text-main);">Verificação KYC</h2>
    <p class="v2-page-subtitle" style="font-size: 0.9375rem; color: var(--ds-text-muted); margin: 0;">Comprove sua identidade para desbloquear todos os recursos da plataforma.</p>
</div>

@php
    $kycSubmission = auth()->user()->kycSubmission;
    $kycStatus = auth()->user()->kyc_status;
@endphp

{{-- ── STATUS CARD ─────────────────────────────────────────── --}}
@if($kycStatus == KycStatus::APPROVED || $kycStatus == KycStatus::PENDING || $kycStatus == KycStatus::REJECTED)
    @php
        $statusConfig = match($kycStatus) {
            KycStatus::APPROVED => [
                'color'   => '#10b981',
                'bg'      => 'rgba(16,185,129,0.08)',
                'border'  => 'rgba(16,185,129,0.2)',
                'icon'    => 'fa-check-circle',
                'label'   => 'Aprovado',
                'message' => 'Sua identidade foi verificada com sucesso. Você tem acesso a todos os recursos da plataforma.',
            ],
            KycStatus::PENDING => [
                'color'   => '#f59e0b',
                'bg'      => 'rgba(245,158,11,0.08)',
                'border'  => 'rgba(245,158,11,0.2)',
                'icon'    => 'fa-clock',
                'label'   => 'Em análise',
                'message' => 'Sua solicitação foi enviada e está sendo analisada pela equipe. Em breve você receberá uma resposta.',
            ],
            KycStatus::REJECTED => [
                'color'   => '#ef4444',
                'bg'      => 'rgba(239,68,68,0.08)',
                'border'  => 'rgba(239,68,68,0.2)',
                'icon'    => 'fa-times-circle',
                'label'   => 'Reprovado',
                'message' => $kycSubmission->notes ?? 'Sua verificação foi reprovada. Você pode reenviar os documentos abaixo.',
            ],
            default => null,
        };
    @endphp

    @if($statusConfig)
    <div class="kyc-status-alert" style="background:{{ $statusConfig['bg'] }};border:1px solid {{ $statusConfig['border'] }};border-radius:12px;padding:24px;display:flex;gap:16px;align-items:flex-start;margin-bottom:24px;">
        <i class="fas {{ $statusConfig['icon'] }}" style="color:{{ $statusConfig['color'] }};font-size:1.3rem;margin-top:2px;flex-shrink:0;"></i>
        <div>
            <p style="color:{{ $statusConfig['color'] }};font-weight:600;font-size:0.9rem;margin:0 0 4px;">
                Verificação KYC — {{ $statusConfig['label'] }}
            </p>
            <p style="color:var(--ds-text-muted);font-size:0.8rem;margin:0;line-height:1.5;">{{ $statusConfig['message'] }}</p>
        </div>
    </div>
    @endif
@endif

{{-- ── SUBMISSION FORM ──────────────────────────────────────── --}}
@if($kycStatus !== KycStatus::APPROVED && $kycStatus !== KycStatus::PENDING)
<form action="{{ route('user.kyc.submit') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <div class="v2-settings-card">
        <div class="v2-settings-header">
            <div class="v2-settings-header-icon" style="background:rgba(124, 58, 237, 0.1); color:var(--ds-primary-light);">
                <i class="fas fa-id-card"></i>
            </div>
            <div>
                <p class="v2-settings-title">Verificação de Identidade</p>
                <p class="v2-settings-desc">Envie seus documentos para liberar todos os recursos</p>
            </div>
        </div>
        <div class="v2-settings-body">

            {{-- Why KYC? --}}
            <div class="kyc-reasons" style="background:rgba(255,255,255,0.02);border:1px solid var(--ds-border-medium);border-radius:12px;padding:24px;margin-bottom:32px;">
                <p class="kyc-reasons-title" style="color:rgba(255,255,255,0.7);font-size:0.75rem;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;margin-bottom:16px;">Por que verificar?</p>
                <div class="kyc-reasons-list" style="display:flex;flex-direction:column;gap:12px;">
                    <div class="kyc-reason-item" style="display:flex;gap:12px;align-items:center;">
                        <i class="fas fa-unlock-alt" style="color:var(--ds-primary-light);width:16px;font-size:0.9rem;text-align:center;"></i>
                        <span style="color:rgba(255,255,255,0.6);font-size:0.85rem;">Desbloqueia saques e limites maiores</span>
                    </div>
                    <div class="kyc-reason-item" style="display:flex;gap:12px;align-items:center;">
                        <i class="fas fa-shield-alt" style="color:var(--ds-primary-light);width:16px;font-size:0.9rem;text-align:center;"></i>
                        <span style="color:rgba(255,255,255,0.6);font-size:0.85rem;">Protege sua conta contra fraudes</span>
                    </div>
                    <div class="kyc-reason-item" style="display:flex;gap:12px;align-items:center;">
                        <i class="fas fa-check-circle" style="color:var(--ds-primary-light);width:16px;font-size:0.9rem;text-align:center;"></i>
                        <span style="color:rgba(255,255,255,0.6);font-size:0.85rem;">Conformidade com regulamentações financeiras</span>
                    </div>
                </div>
            </div>

            <div class="kyc-field" style="margin-bottom:24px;">
                <label class="v2-label" for="template-select">Tipo de Documento</label>
                <select class="v2-input" name="template_id" id="template-select" style="border-radius:12px;">
                    <option disabled selected>Selecionar tipo de documento…</option>
                    @foreach($kycTemplates as $kycTemplate)
                        <option value="{{ $kycTemplate->id }}">{{ $kycTemplate->title }}</option>
                    @endforeach
                </select>
            </div>

            <div id="template-details" style="margin-bottom:24px;"></div>

            <div class="kyc-field" style="margin-bottom:32px;">
                <label class="v2-label" for="kyc-note">Observação <span style="color:var(--ds-text-muted);font-weight:400;">(opcional)</span></label>
                <textarea id="kyc-note" name="note" class="v2-input" rows="3" style="border-radius:12px; padding: 12px 16px; height: auto;"
                          placeholder="Informações adicionais para a equipe de análise…"></textarea>
            </div>

            <div class="v2-settings-footer kyc-submit-footer">
                <button type="submit" class="v2-btn-primary">
                    <i class="fas fa-paper-plane" style="margin-right:8px;"></i>
                    Enviar para Verificação
                </button>
            </div>
        </div>
    </div>
</form>

@elseif($kycStatus == KycStatus::APPROVED)
{{-- Approved state --}}
<div class="v2-settings-card" style="border-color:rgba(16,185,129,0.2);">
    <div class="v2-settings-header">
        <div class="v2-settings-header-icon" style="background:rgba(16,185,129,0.1); color:#10b981;">
            <i class="fas fa-id-card"></i>
        </div>
        <div>
            <p class="v2-settings-title">Identidade Verificada</p>
            <p class="v2-settings-desc">Status: Aprovado</p>
        </div>
    </div>
    <div class="v2-settings-body" style="text-align:center;padding:32px 22px;">
        <div style="width:56px;height:56px;border-radius:50%;background:rgba(16,185,129,0.1);border:2px solid rgba(16,185,129,0.3);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
            <i class="fas fa-check" style="color:#10b981;font-size:1.3rem;"></i>
        </div>
        <p style="color:rgba(255,255,255,0.9);font-weight:600;font-size:1rem;margin-bottom:6px;">Verificação concluída</p>
        <p style="color:var(--ds-text-muted);font-size:0.85rem;">Você tem acesso completo a todos os recursos e limites da plataforma.</p>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    'use strict';
    const $select  = $('#template-select');
    const $details = $('#template-details');

    $select.on('change', function () {
        const id = $(this).val();
        if (!id) { $details.empty(); return; }

        $details.html(`
            <div style="padding:16px 0;display:flex;align-items:center;gap:10px;color:var(--ds-text-muted);font-size:0.85rem;">
                <div class="spinner-border spinner-border-sm" role="status" style="color:var(--ds-primary-light);"></div>
                Carregando campos do documento…
            </div>
        `);

        const url = "{{ route('user.kyc.template.details', ':id') }}".replace(':id', id);
        $.get(url).done(response => { 
            // In case the response returns V1 elements, we might need to style them or the controller should return V2 styled elements. 
            // The user didn't mention this, so we assume they look ok or we inject classes.
            $details.html(response); 
            $details.find('input, select, textarea').addClass('form-control');
            $details.find('label').addClass('form-label');
            $details.find('input[type="file"]').each(function() {
                // simple styling for file inputs returned via ajax
                $(this).css({
                    'padding': '12px',
                    'background': 'rgba(255,255,255,0.02)',
                    'border': '1px solid var(--ds-border-medium)',
                    'border-radius': '12px',
                    'color': 'rgba(255,255,255,0.7)',
                    'width': '100%',
                    'margin-bottom': '16px'
                });
            });
        });
    });
});
</script>
@endpush
