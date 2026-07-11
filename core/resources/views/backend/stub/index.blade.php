@extends('backend.layouts.app')
@section('title', $title ?? 'Módulo não configurado')
@section('content')
<div class="ds-fade-in">
    <div class="ds-page-header mb-5">
        <div>
            <div class="ds-breadcrumb mb-1">
                <a href="{{ route('admin.dashboard') }}">Dashboard</a>
                <span class="ds-breadcrumb-sep">/</span>
                <span style="color:var(--ds-text);">{{ $title ?? 'Módulo' }}</span>
            </div>
            <h1 class="ds-heading-lg mb-1">{{ $title ?? 'Módulo não configurado' }}</h1>
            @if(!empty($desc))<p class="ds-body-sm mb-0">{{ $desc }}</p>@endif
        </div>
        <a href="{{ url()->previous() != url()->current() ? url()->previous() : route('admin.dashboard') }}" class="btn btn-secondary">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Voltar
        </a>
    </div>

    <div class="card" style="max-width:660px;margin:0 auto;">
        <div class="card-body" style="padding:3.5rem 2.5rem !important;text-align:center;">
            <div style="width:72px;height:72px;background:var(--ds-accent-muted);border:1px solid rgba(79,70,229,.15);border-radius:var(--ds-radius-xl);display:flex;align-items:center;justify-content:center;margin:0 auto 1.75rem;">
                <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="none" viewBox="0 0 24 24" stroke="var(--ds-accent)" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
            </div>
            <span class="badge ds-badge-accent mb-3" style="font-size:.65rem;letter-spacing:.06em;">SEM BACKEND ATIVO</span>
            <h2 class="ds-heading-md mb-3">{{ $title ?? 'Módulo não configurado' }}</h2>
            <p style="color:var(--ds-text-secondary);max-width:420px;margin:0 auto 2rem;font-size:var(--ds-text-sm);line-height:1.7;">
                @if(!empty($desc))
                    {{ $desc }}
                @else
                    Esta rota existe no projeto, mas não há uma funcionalidade operacional conectada para este módulo no ambiente atual.
                @endif
            </p>
            <div style="background:var(--ds-bg);border:1px solid var(--ds-border);border-radius:var(--ds-radius);padding:1.25rem 1.5rem;text-align:left;margin-bottom:2rem;">
                <div class="ds-caption mb-2">Próximos passos</div>
                <div style="font-size:var(--ds-text-sm);color:var(--ds-text-secondary);line-height:1.7;">
                    Revise se esta rota ainda deve existir, se deve redirecionar para um módulo consolidado ou se precisa de backend dedicado antes de ser exibida no admin.
                </div>
            </div>
            <div class="d-flex justify-content-center gap-3">
                <a href="{{ route('admin.dashboard') }}" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                    Ir ao Dashboard
                </a>
                <a href="{{ url()->previous() != url()->current() ? url()->previous() : route('admin.dashboard') }}" class="btn btn-secondary">Voltar</a>
            </div>
        </div>
    </div>
</div>
@endsection
