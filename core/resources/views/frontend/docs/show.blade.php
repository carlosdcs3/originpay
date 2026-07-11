@extends('frontend.layouts.docs')

@section('title', $pageTitle)

@section('content')
    <div class="doc-breadcrumb">
        <a href="{{ route('docs.index') }}">Documentação</a>
        <i class="fas fa-chevron-right"></i>
        <span>{{ $pageTitle }}</span>
    </div>

    <h1>{{ $pageTitle }}</h1>
    <p class="lead">Esta seção está sendo elaborada pela equipe de engenharia da OriginPay.</p>

    <div style="
        margin-top: 48px;
        background: rgba(255,255,255,0.015);
        border: 1px dashed rgba(255,255,255,0.1);
        padding: 56px 48px;
        text-align: center;
        border-radius: 12px;
    ">
        <div style="
            width: 48px; height: 48px;
            border-radius: 50%;
            background: rgba(0, 212, 170, 0.07);
            border: 1px solid rgba(0, 212, 170, 0.15);
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 20px;
        ">
            <i class="fas fa-file-code" style="color: var(--doc-primary); font-size: 1.1rem;"></i>
        </div>
        <h3 style="font-size: 1rem; font-weight: 600; color: #fff; margin-bottom: 8px;">Conteúdo em preparação</h3>
        <p style="font-size: 0.875rem; color: var(--doc-muted); max-width: 380px; margin: 0 auto 28px; line-height: 1.6;">
            Esta página técnica está sendo redigida pelos nossos engenheiros e será publicada em breve.
        </p>
        <a href="{{ route('docs.index') }}" class="btn-doc btn-doc-secondary">
            <i class="fas fa-arrow-left" style="margin-right: 8px;"></i>
            Voltar à documentação
        </a>
    </div>
@endsection
