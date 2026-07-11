@extends('frontend.layouts.docs')

@section('title', 'Sandbox e Produção')

@section('toc')
    <ul class="doc-toc-list">
        <li><a href="#ambiente-sandbox">Ambiente Sandbox</a></li>
        <li><a href="#simulacao-pagamentos">Simulação de Pagamentos</a></li>
        <li><a href="#ambiente-producao">Ambiente Produção</a></li>
        <li><a href="#migracao">Migrando para Produção</a></li>
    </ul>
@endsection

@section('content')
    <div class="doc-breadcrumb">
        <a href="{{ route('docs.index') }}">Documentação</a>
        <i class="fas fa-chevron-right"></i>
        <a href="#">Introdução</a>
        <i class="fas fa-chevron-right"></i>
        <span>Sandbox e Produção</span>
    </div>

    <h1>Sandbox e Produção</h1>
    <div class="doc-meta">
        <span><i class="far fa-clock"></i> 3 min de leitura</span>
        <span><i class="far fa-calendar-alt"></i> Atualizado hoje</span>
    </div>

    <p class="lead">A OriginPay oferece dois ambientes completamente isolados: Sandbox e Produção. Seus dados, configurações, transações e chaves de API nunca se misturam entre os dois ambientes.</p>

    <h2 id="ambiente-sandbox">Ambiente Sandbox</h2>
    <p>O Sandbox é um ambiente de testes seguro. Nenhum dinheiro real transita por ele e as conexões com as instituições bancárias parceiras são mockadas internamente.</p>
    <ul style="color: var(--doc-muted); padding-left: 20px; line-height: 1.8; margin-bottom: 24px;">
        <li>As chaves começam com <code>sk_test_...</code></li>
        <li>Não há movimentação real de fundos.</li>
        <li>Cartões reais <strong>não serão cobrados</strong>, mesmo se você inserir os dados válidos. Recomendamos usar os Cartões de Teste da documentação.</li>
        <li>Códigos PIX gerados não podem ser pagos em aplicativos bancários reais.</li>
        <li>Você pode forçar estados específicos (sucesso, falha, chargeback) pelo Dashboard de Sandbox.</li>
    </ul>

    <h2 id="simulacao-pagamentos">Simulação de Pagamentos</h2>
    <p>Como os códigos PIX ou boletos de Sandbox não são reais, o pagamento deles deve ser simulado no seu Dashboard de Desenvolvedor ou via API.</p>
    <p>Ao simular o pagamento, a OriginPay vai disparar automaticamente o evento <code>payment.paid</code> para as URLs de Webhook cadastradas no ambiente Sandbox, permitindo que você valide sua lógica de recebimento.</p>

    <h2 id="ambiente-producao">Ambiente Produção</h2>
    <p>O ambiente de Produção lida com operações financeiras reais. Requer aprovação de Compliance (KYC/KYB) antes de ser liberado para a sua conta.</p>
    <ul style="color: var(--doc-muted); padding-left: 20px; line-height: 1.8; margin-bottom: 24px;">
        <li>As chaves começam com <code>sk_live_...</code></li>
        <li>Os fundos serão debitados dos cartões dos seus clientes.</li>
        <li>O QR Code PIX gerado deve ser lido pelo app bancário do cliente e o saldo será compensado de verdade.</li>
        <li>Sujeito às taxas operacionais acordadas.</li>
    </ul>

    <div class="doc-alert doc-alert-important">
        <i class="fas fa-shield-alt"></i>
        <div>
            <strong>Aviso Legal</strong>
            <p>Testes automatizados da sua plataforma (CI/CD) nunca devem apontar para chaves de produção. O uso excessivo de chamadas inválidas em produção pode resultar em suspensão de conta devido à proteção anti-fraude do Banco Central.</p>
        </div>
    </div>

    <h2 id="migracao">Migrando para Produção</h2>
    <p>O design da API é idêntico entre os ambientes. Para migrar o seu sistema pronto e homologado do Sandbox para a Produção, você precisa executar apenas 2 passos no seu código:</p>
    
    <ol style="color: var(--doc-muted); padding-left: 20px; line-height: 1.8; margin-bottom: 24px;">
        <li>Substituir a sua chave <code>sk_test_...</code> pela chave <code>sk_live_...</code> fornecida após a aprovação de KYC.</li>
        <li>Cadastrar as URLs dos Webhooks novamente no Dashboard de Produção, utilizando chaves secretas de assinatura (<code>whsec_...</code>) próprias daquele ambiente.</li>
    </ol>

    <div class="doc-pagination">
        <a href="{{ route('docs.show', 'authentication') }}" class="doc-pagination-link">
            <span class="dir"><i class="fas fa-arrow-left"></i> Anterior</span>
            <span class="title">Autenticação</span>
        </a>
        <a href="{{ route('docs.show', 'payments') }}" class="doc-pagination-link next">
            <span class="dir">Próximo</span>
            <span class="title">Criar Pagamento <i class="fas fa-arrow-right"></i></span>
        </a>
    </div>
@endsection
