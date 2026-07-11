@extends('frontend.layouts.api_reference')

@section('title', 'Migration Guide')

@section('content')
    <div class="doc-breadcrumb">
        <a href="{{ route('docs.v1.api_reference.index') }}">API Reference</a>
        <i data-lucide="chevron-right" style="width: 12px;"></i>
        <span>Migration Guide</span>
    </div>

    <h1>Migration Guide</h1>
    <p class="lead" style="margin-bottom: 48px;">
        Acompanhe aqui o guia oficial para migrar suas integrações entre as versões da API da OriginPay. Nossa arquitetura foi projetada para minimizar breaking changes.
    </p>

    <div class="doc-alert doc-alert-tip">
        <i data-lucide="info"></i>
        <div>
            <strong>Versão Atual: v1</strong>
            <p>Atualmente, a API encontra-se na versão inicial (v1). Não há versões anteriores para migrar. Todas as novas integrações devem utilizar a v1.</p>
        </div>
    </div>

    <h2 id="versioning-policy">Política de Versionamento</h2>
    <p>A OriginPay garante estabilidade. As seguintes alterações são consideradas "Non-Breaking" (retrocompatíveis) e podem ocorrer sem uma mudança de versão maior:</p>
    <ul style="color: var(--doc-muted); padding-left: 20px; line-height: 1.8; margin-bottom: 24px;">
        <li>Adição de novos endpoints.</li>
        <li>Adição de novos parâmetros opcionais aos requests.</li>
        <li>Adição de novas propriedades aos objetos de response.</li>
        <li>Alteração da ordem das propriedades no response JSON.</li>
        <li>Alteração do comprimento ou formato de strings de ID (sempre confie na chave, não na string do UUID).</li>
    </ul>

    <p>Mudanças maiores, como a remoção de campos, exigirão o lançamento da versão <code>v2</code>. Você terá pelo menos 24 meses para adaptar o seu código após o anúncio oficial de uma nova versão maior.</p>

@endsection
