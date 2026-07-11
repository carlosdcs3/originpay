@extends('frontend.layouts.docs')

@section('title', 'OpenAPI Spec')

@section('toc')
    <ul class="doc-toc-list">
        <li><a href="#o-que-e">O que é OpenAPI</a></li>
        <li><a href="#download">Baixar Especificação</a></li>
        <li><a href="#ferramentas">Ferramentas Compatíveis</a></li>
    </ul>
@endsection

@section('content')
    <div class="doc-breadcrumb">
        <a href="{{ route('docs.index') }}">Documentação</a>
        <i class="fas fa-chevron-right"></i>
        <a href="#">Desenvolvedores</a>
        <i class="fas fa-chevron-right"></i>
        <span>OpenAPI Spec</span>
    </div>

    <h1>Especificação OpenAPI</h1>
    <div class="doc-meta">
        <span><i class="far fa-clock"></i> 2 min de leitura</span>
        <span><i class="far fa-calendar-alt"></i> Atualizado hoje</span>
    </div>

    <p class="lead">A OriginPay fornece uma especificação formal de todas as suas rotas, parâmetros, headers e respostas utilizando o padrão OpenAPI 3.1.0.</p>

    <h2 id="o-que-e">O que é OpenAPI</h2>
    <p>O OpenAPI (antigo Swagger) é uma linguagem agnóstica para descrever APIs REST. Com a nossa especificação, você pode gerar código cliente (SDKs) para a sua linguagem preferida, mockar servidores de teste e configurar clientes HTTP como Postman e Insomnia com apenas um clique.</p>

    <h2 id="download">Baixar Especificação</h2>
    <p>Baixe o arquivo JSON mais atualizado com a nossa documentação estruturada:</p>

    <div style="margin: 32px 0;">
        <a href="#" class="btn-doc btn-doc-primary" style="display:inline-flex; align-items:center; gap: 8px;">
            <i class="fas fa-download"></i> Baixar openapi.json
        </a>
        <span style="color: var(--doc-muted); margin-left: 16px; font-size: 0.9rem;">v1.0.5 — Atualizado há 2 dias</span>
    </div>

    <h2 id="ferramentas">Ferramentas Compatíveis</h2>
    <p>Com o arquivo <code>openapi.json</code> baixado, você pode importá-lo diretamente nas seguintes ferramentas:</p>
    <ul style="color: var(--doc-muted); padding-left: 20px; line-height: 1.8; margin-bottom: 24px;">
        <li><strong>Postman:</strong> File -> Import -> Selecione o JSON. Ele criará uma Collection pronta para uso.</li>
        <li><strong>Insomnia:</strong> Create -> Import From -> File.</li>
        <li><strong>Swagger UI:</strong> Renderize sua própria documentação local ou interaja visualmente com nossa API.</li>
        <li><strong>OpenAPI Generator:</strong> Gere wrappers TypeScript, Java, Go, Ruby e C# para o seu projeto com <code>openapi-generator-cli generate -i openapi.json -g typescript-axios</code></li>
    </ul>

    <div class="doc-pagination">
        <a href="{{ route('docs.show', 'hmac') }}" class="doc-pagination-link">
            <span class="dir"><i class="fas fa-arrow-left"></i> Anterior</span>
            <span class="title">Assinatura HMAC</span>
        </a>
        <a href="{{ route('docs.show', 'sdks') }}" class="doc-pagination-link next">
            <span class="dir">Próximo</span>
            <span class="title">SDKs Oficiais <i class="fas fa-arrow-right"></i></span>
        </a>
    </div>
@endsection
