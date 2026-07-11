@extends('frontend.layouts.api_reference')

@section('title', 'Developer Resources')

@section('content')
    <div class="doc-breadcrumb">
        <a href="{{ route('docs.v1.api_reference.index') }}">API Reference</a>
        <i data-lucide="chevron-right" style="width: 12px;"></i>
        <span>Developer Resources</span>
    </div>

    <h1>Developer Resources</h1>
    <p class="lead" style="margin-bottom: 48px;">
        Baixe nossos SDKs oficiais e coleções de clientes de API para acelerar a sua integração.
    </p>

    <h2 id="sdks">SDKs Oficiais</h2>
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 48px;">
        
        <div style="background: var(--doc-surface); border: 1px solid var(--doc-border); padding: 24px; border-radius: 12px;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <i class="fab fa-node-js" style="font-size: 24px; color: #10b981;"></i>
                    <h3 style="margin: 0; font-size: 1.1rem;">Node.js</h3>
                </div>
                <span style="background: rgba(255,255,255,0.1); padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; color: var(--doc-muted);">v1.2.0</span>
            </div>
            <p style="color: var(--doc-muted); font-size: 0.9rem; margin-bottom: 16px;">
                Compatível com API v1. Atualizado em 15/06/2026.
            </p>
            <div style="background: #000; border: 1px solid rgba(255,255,255,0.1); padding: 8px 12px; border-radius: 6px; font-family: monospace; font-size: 0.85rem; color: #a1a1aa;">
                npm install originpay-node
            </div>
        </div>

        <div style="background: var(--doc-surface); border: 1px solid var(--doc-border); padding: 24px; border-radius: 12px;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <i class="fab fa-php" style="font-size: 24px; color: #8b5cf6;"></i>
                    <h3 style="margin: 0; font-size: 1.1rem;">PHP</h3>
                </div>
                <span style="background: rgba(255,255,255,0.1); padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; color: var(--doc-muted);">v1.0.4</span>
            </div>
            <p style="color: var(--doc-muted); font-size: 0.9rem; margin-bottom: 16px;">
                Compatível com API v1. Atualizado em 10/06/2026.
            </p>
            <div style="background: #000; border: 1px solid rgba(255,255,255,0.1); padding: 8px 12px; border-radius: 6px; font-family: monospace; font-size: 0.85rem; color: #a1a1aa;">
                composer require originpay/php
            </div>
        </div>

    </div>

    <h2 id="api-clients">API Clients (Postman, Insomnia)</h2>
    <div class="doc-table-wrap">
        <table class="doc-table">
            <thead>
                <tr>
                    <th>Ferramenta</th>
                    <th>Versão API</th>
                    <th>Checksum (SHA256)</th>
                    <th>Download</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>OpenAPI Spec (Swagger)</strong></td>
                    <td>v1</td>
                    <td style="font-family: monospace; font-size: 0.8rem; color: var(--doc-muted);">a1b2c3d4...</td>
                    <td><a href="#" style="color: var(--doc-primary);"><i data-lucide="download" style="width: 14px; margin-right: 4px; vertical-align: middle;"></i> JSON</a></td>
                </tr>
                <tr>
                    <td><strong>Postman Collection</strong></td>
                    <td>v1</td>
                    <td style="font-family: monospace; font-size: 0.8rem; color: var(--doc-muted);">9f8e7d6c...</td>
                    <td><a href="#" style="color: var(--doc-primary);"><i data-lucide="download" style="width: 14px; margin-right: 4px; vertical-align: middle;"></i> JSON</a></td>
                </tr>
                <tr>
                    <td><strong>Insomnia Collection</strong></td>
                    <td>v1</td>
                    <td style="font-family: monospace; font-size: 0.8rem; color: var(--doc-muted);">1a2b3c4d...</td>
                    <td><a href="#" style="color: var(--doc-primary);"><i data-lucide="download" style="width: 14px; margin-right: 4px; vertical-align: middle;"></i> JSON</a></td>
                </tr>
            </tbody>
        </table>
    </div>

@endsection
