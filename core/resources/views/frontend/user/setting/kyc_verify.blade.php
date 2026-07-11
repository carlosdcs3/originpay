@php use App\Enums\KycStatus; @endphp
@extends('frontend.layouts.user-v2')
@section('title', __('Verificação KYC'))

@section('user_setting_content')
@php
    $kycSubmission = auth()->user()->kycSubmission;
    $kycStatus = auth()->user()->kyc_status;
@endphp

{{-- ── STATUS CARD ─────────────────────────────────────────── --}}
@if($kycStatus == KycStatus::APPROVED || $kycStatus == KycStatus::PENDING || $kycStatus == KycStatus::REJECTED)
    @php
        $statusConfig = match($kycStatus) {
            KycStatus::APPROVED => [
                'color'   => '#00D4AA',
                'bg'      => 'rgba(0,212,170,0.08)',
                'border'  => 'rgba(0,212,170,0.2)',
                'icon'    => 'fa-check-circle',
                'label'   => 'Aprovado',
                'message' => 'Sua identidade foi verificada com sucesso. Você tem acesso a todos os recursos da plataforma.',
            ],
            KycStatus::PENDING => [
                'color'   => '#ffc107',
                'bg'      => 'rgba(255,193,7,0.08)',
                'border'  => 'rgba(255,193,7,0.2)',
                'icon'    => 'fa-clock',
                'label'   => 'Em análise',
                'message' => 'Sua solicitação foi enviada e está sendo analisada pela equipe. Em breve você receberá uma resposta.',
            ],
            KycStatus::REJECTED => [
                'color'   => '#FF4D6A',
                'bg'      => 'rgba(255,77,106,0.08)',
                'border'  => 'rgba(255,77,106,0.2)',
                'icon'    => 'fa-times-circle',
                'label'   => 'Reprovado',
                'message' => $kycSubmission->notes ?? 'Sua verificação foi reprovada. Você pode reenviar os documentos abaixo.',
            ],
            default => null,
        };
    @endphp

    @if($statusConfig)
    <div style="background:{{ $statusConfig['bg'] }};border:1px solid {{ $statusConfig['border'] }};border-radius:14px;padding:18px 20px;display:flex;gap:14px;align-items:flex-start;margin-bottom:4px;">
        <i class="fas {{ $statusConfig['icon'] }}" style="color:{{ $statusConfig['color'] }};font-size:1.3rem;margin-top:1px;flex-shrink:0;"></i>
        <div>
            <p style="color:{{ $statusConfig['color'] }};font-weight:700;font-size:0.88rem;margin:0 0 4px;letter-spacing:0.02em;">
                Verificação KYC — {{ $statusConfig['label'] }}
            </p>
            <p style="color:#94a3b8;font-size:0.82rem;margin:0;line-height:1.5;">{{ $statusConfig['message'] }}</p>
        </div>
    </div>
    @endif
@endif

{{-- ── SUBMISSION FORM ──────────────────────────────────────── --}}
@if($kycStatus !== KycStatus::APPROVED && $kycStatus !== KycStatus::PENDING)
<form action="{{ route('user.kyc.submit') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <div class="cfg-section">
        <div class="cfg-section-header">
            <div class="cfg-section-icon" style="background:rgba(124,110,255,0.1);">
                <i class="fas fa-id-card" style="color:var(--ds-purple);"></i>
            </div>
            <div>
                <p class="cfg-section-title">Verificação de Identidade</p>
                <p class="cfg-section-sub">Envie seus documentos para liberar todos os recursos</p>
            </div>
        </div>
        <div class="cfg-section-body">

            {{-- Why KYC? --}}
            <div style="background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.055);border-radius:10px;padding:14px 16px;margin-bottom:20px;">
                <p style="color:#475569;font-size:0.72rem;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;margin-bottom:10px;">Por que verificar?</p>
                <div style="display:flex;flex-direction:column;gap:7px;">
                    <div style="display:flex;gap:10px;align-items:center;">
                        <i class="fas fa-unlock-alt" style="color:var(--ds-primary-light);width:16px;font-size:0.8rem;"></i>
                        <span style="color:#64748b;font-size:0.8rem;">Desbloqueia saques e limites maiores</span>
                    </div>
                    <div style="display:flex;gap:10px;align-items:center;">
                        <i class="fas fa-shield-alt" style="color:var(--ds-primary-light);width:16px;font-size:0.8rem;"></i>
                        <span style="color:#64748b;font-size:0.8rem;">Protege sua conta contra fraudes</span>
                    </div>
                    <div style="display:flex;gap:10px;align-items:center;">
                        <i class="fas fa-check-circle" style="color:var(--ds-primary-light);width:16px;font-size:0.8rem;"></i>
                        <span style="color:#64748b;font-size:0.8rem;">Conformidade com regulamentações financeiras</span>
                    </div>
                </div>
            </div>

            <div class="cfg-field-row full" style="max-width:520px;">
                <div>
                    <label class="cfg-label" for="template-select">Tipo de Documento</label>
                    <select class="cfg-input" name="template_id" id="template-select">
                        <option disabled selected>Selecionar tipo de documento…</option>
                        @foreach($kycTemplates as $kycTemplate)
                            <option value="{{ $kycTemplate->id }}">{{ $kycTemplate->title }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div id="template-details" class="cfg-field-row full" style="max-width:520px;"></div>

            <div class="cfg-field-row full" style="max-width:520px;">
                <div>
                    <label class="cfg-label" for="kyc-note">Observação <span style="color:#475569;font-weight:400;">(opcional)</span></label>
                    <textarea id="kyc-note" name="note" class="cfg-input" rows="3"
                              placeholder="Informações adicionais para a equipe de análise…"></textarea>
                </div>
            </div>

            <div class="cfg-save-row">
                <button type="submit" class="ds-btn-submit"
                        style="background:var(--ds-purple);color:#fff;width:auto;padding:10px 28px;">
                    <i class="fas fa-paper-plane" style="margin-right:8px;"></i>
                    Enviar para Verificação
                </button>
            </div>
        </div>
    </div>
</form>

@elseif($kycStatus == KycStatus::APPROVED)
{{-- Approved state — show a completion summary --}}
<div class="cfg-section" style="border-color:rgba(124,58,237,0.15);">
    <div class="cfg-section-header">
        <div class="cfg-section-icon" style="background:rgba(124,58,237,0.1);">
            <i class="fas fa-id-card" style="color:var(--ds-primary-light);"></i>
        </div>
        <div>
            <p class="cfg-section-title">Identidade Verificada</p>
            <p class="cfg-section-sub">Status: Aprovado</p>
        </div>
    </div>
    <div class="cfg-section-body" style="text-align:center;padding:32px 22px;">
        <div style="width:56px;height:56px;border-radius:50%;background:rgba(124,58,237,0.1);border:2px solid rgba(124,58,237,0.3);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
            <i class="fas fa-check" style="color:var(--ds-primary-light);font-size:1.3rem;"></i>
        </div>
        <p style="color:#e2e8f0;font-weight:700;font-size:1rem;margin-bottom:6px;">Verificação concluída</p>
        <p style="color:#64748b;font-size:0.83rem;">Você tem acesso completo a todos os recursos e limites da plataforma.</p>
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
            <div style="padding:16px 0;display:flex;align-items:center;gap:10px;color:#64748b;font-size:0.85rem;">
                <div class="spinner-border spinner-border-sm" role="status" style="color:var(--ds-primary-light);"></div>
                Carregando campos do documento…
            </div>
        `);

        const url = "{{ route('user.kyc.template.details', ':id') }}".replace(':id', id);
        $.get(url).done(response => { $details.html(response); });
    });
});
</script>
@endpush
