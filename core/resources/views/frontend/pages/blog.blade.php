@extends('frontend.layouts.landing')
@section('title', 'Blog Institucional — OriginPay')
@section('description', 'Engenharia, arquitetura e notas de lançamento da plataforma OriginPay.')

@section('content')

<x-frontend.editorial-hero 
    title="Blog." 
    subtitle="Estudos de caso, trade-offs de infraestrutura e relatos da nossa equipe de engenharia sobre escala e segurança."
    breadcrumb="Institucional / Blog" 
    align="left" />

<style>
    .op-blog-container { max-width: 1200px; margin: 0 auto; padding: 60px 20px 100px; }
    
    /* Featured Article */
    .op-featured-post { display: flex; flex-direction: column; gap: 40px; margin-bottom: 100px; }
    @media (min-width: 992px) {
        .op-featured-post { flex-direction: row; align-items: stretch; }
        .op-featured-image { width: 55%; flex-shrink: 0; }
    }
    .op-featured-image-wrapper { background: var(--bg-panel); border: 1px solid var(--border); border-radius: 12px; height: 100%; min-height: 400px; display: flex; align-items: center; justify-content: center; overflow: hidden; transition: border-color 0.2s; }
    .op-featured-post:hover .op-featured-image-wrapper { border-color: var(--primary); }
    .op-featured-content { padding: 24px 0; display: flex; flex-direction: column; justify-content: center; }
    
    /* Regular Articles */
    .op-blog-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 64px 40px; }
    .op-post-card { display: flex; flex-direction: column; transition: transform 0.2s; text-decoration: none !important; }
    .op-post-card:hover { transform: translateY(-4px); }
    .op-post-card:hover .op-post-title { color: var(--primary); }
    
    /* Shared Elements */
    .op-post-meta { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; font-size: 0.9rem; font-weight: 500; }
    .op-post-category { color: var(--primary); text-transform: uppercase; letter-spacing: 0.05em; }
    .op-post-date { color: var(--text-muted); }
    
    .op-post-title { font-size: 1.6rem; font-weight: 700; color: #fff; margin-bottom: 16px; line-height: 1.3; transition: color 0.2s; letter-spacing: -0.01em; }
    .op-featured-content .op-post-title { font-size: clamp(2rem, 3vw, 2.5rem); letter-spacing: -0.02em; }
    
    .op-post-excerpt { font-size: 1.1rem; color: var(--text-muted); line-height: 1.6; margin-bottom: 32px; }
    
    .op-post-author { display: flex; align-items: center; gap: 12px; margin-top: auto; }
    .op-author-avatar { width: 40px; height: 40px; background: var(--bg-deep); border: 1px solid var(--border); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.85rem; color: var(--text-muted); font-weight: 600; }
    .op-author-info { display: flex; flex-direction: column; }
    .op-author-name { font-size: 0.95rem; font-weight: 600; color: #fff; }
    .op-read-time { font-size: 0.85rem; color: var(--text-muted); }
</style>

<div style="background: var(--bg-deep);">
    <div class="op-blog-container">
        
        <!-- Featured Article -->
        <article class="op-featured-post">
            <a href="#" class="op-featured-image text-decoration-none">
                <div class="op-featured-image-wrapper">
                    <i class="fas fa-server text-muted" style="font-size: 5rem; opacity: 0.3;"></i>
                </div>
            </a>
            <div class="op-featured-content">
                <div class="op-post-meta">
                    <span class="op-post-category">Engenharia</span>
                    <span class="op-post-date">14 de Julho, 2026</span>
                </div>
                <a href="#" class="text-decoration-none">
                    <h2 class="op-post-title">O trade-off estrutural entre Consistência e Disponibilidade no processamento de transações</h2>
                </a>
                <p class="op-post-excerpt">Explorando o Teorema CAP através da ótica de gateways financeiros: por que abandonamos travas relacionais puras em favor de Event Sourcing garantido para transações síncronas de crédito.</p>
                <div class="op-post-author">
                    <div class="op-author-avatar">AL</div>
                    <div class="op-author-info">
                        <span class="op-author-name">Alexandre Lemos</span>
                        <span class="op-read-time">12 min de leitura</span>
                    </div>
                </div>
            </div>
        </article>

        <!-- Article Grid -->
        <div class="op-blog-grid">
            <a href="#" class="op-post-card">
                <div class="op-post-meta">
                    <span class="op-post-category">Produto</span>
                    <span class="op-post-date">02 de Julho, 2026</span>
                </div>
                <h3 class="op-post-title">Prevenção contra Enumeração de Cartões via Machine Learning</h3>
                <p class="op-post-excerpt">Como nossa arquitetura identifica e rejeita ataques de força bruta contra BINs em menos de 10 milissegundos operando diretamente no edge.</p>
                <div class="op-post-author">
                    <div class="op-author-avatar">CM</div>
                    <div class="op-author-info">
                        <span class="op-author-name">Carla Martins</span>
                        <span class="op-read-time">5 min de leitura</span>
                    </div>
                </div>
            </a>

            <a href="#" class="op-post-card">
                <div class="op-post-meta">
                    <span class="op-post-category">Atualizações</span>
                    <span class="op-post-date">28 de Junho, 2026</span>
                </div>
                <h3 class="op-post-title">Webhook Retries agora suportam Exponential Backoff</h3>
                <p class="op-post-excerpt">Nova política de conciliação assíncrona garante entregas aos servidores dos clientes mesmo em cenários de degradação massiva ou manutenção prolongada.</p>
                <div class="op-post-author">
                    <div class="op-author-avatar">RF</div>
                    <div class="op-author-info">
                        <span class="op-author-name">Rafael Fernandes</span>
                        <span class="op-read-time">8 min de leitura</span>
                    </div>
                </div>
            </a>

            <a href="#" class="op-post-card">
                <div class="op-post-meta">
                    <span class="op-post-category">Empresa</span>
                    <span class="op-post-date">15 de Junho, 2026</span>
                </div>
                <h3 class="op-post-title">OriginPay encerra fase de testes restritos</h3>
                <p class="op-post-excerpt">Disponibilização da API e painel de Sandbox globalmente, encerrando a exigência de convites para integração ao ambiente de homologação.</p>
                <div class="op-post-author">
                    <div class="op-author-avatar">DP</div>
                    <div class="op-author-info">
                        <span class="op-author-name">Diego Pereira</span>
                        <span class="op-read-time">4 min de leitura</span>
                    </div>
                </div>
            </a>
        </div>
        
    </div>
</div>

<x-frontend.highlight-section 
    title="Receba Atualizações Técnicas" 
    desc="Avisos sobre depreciações, otimizações na API e artigos da engenharia entregues pontualmente.">
    
    <div style="max-width: 400px; margin: 32px auto 0; display: flex; gap: 8px;">
        <input type="email" placeholder="email@sua-empresa.com" style="flex-grow: 1; background: var(--bg-deep); border: 1px solid var(--border); border-radius: 8px; padding: 12px 16px; color: #fff; outline: none;">
        <button class="btn btn-primary" style="padding: 12px 24px;">Inscrever-se</button>
    </div>
</x-frontend.highlight-section>

@endsection