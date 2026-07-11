@extends('frontend.layouts.landing')
@section('title', 'Carreiras — OriginPay')
@section('description', 'Construa infraestrutura financeira de escala global.')

@section('content')

<x-frontend.editorial-hero 
    title="Construa sistemas críticos." 
    subtitle="Engenheiros da OriginPay têm impacto imediato. Nós resolvemos problemas difíceis de escalabilidade, criptografia distribuída e conciliação atômica para garantir o fluxo contínuo de pagamentos na internet."
    breadcrumb="Institucional / Carreiras" 
    align="center" />

<x-frontend.editorial-quote 
    quote="Contratamos executores técnicos excepcionais e removemos as barreiras do caminho. Sem microgerenciamento. Sem tecnologias legadas impostas."
    author="Culture Code"
    role="OriginPay" />

<x-frontend.content-section maxWidth="800px" padding="80px 20px">
    <h2>Nossa Filosofia</h2>
    <p>A cultura é focada no produto. Nós acreditamos na comunicação assíncrona, onde problemas são resolvidos através de design docs e pull requests muito bem revisados, e não em reuniões intermináveis. O ritmo de trabalho é sustentável porque a arquitetura é sólida.</p>
    <p>Somos um time pequeno com a responsabilidade de movimentar o capital operacional de diversas plataformas. Se você é rigoroso com o seu código e se incomoda com sistemas instáveis, existe um espaço para você.</p>
</x-frontend.content-section>

<h3 style="font-size: 1.8rem; font-weight: 600; color: #fff; text-align: center; margin: 40px 0 0; background: var(--bg-deep); padding-top: 40px;">Processo Seletivo (Engenharia)</h3>
<x-frontend.process-flow 
    :steps="[
        [
            'title' => 'Aplicação Técnica',
            'desc' => 'Avaliação inicial do seu repositório ou de contribuições open-source. Procuramos indícios de qualidade de código e decisões arquiteturais.'
        ],
        [
            'title' => 'Pair Programming',
            'desc' => 'Uma sessão objetiva construindo um pequeno serviço em tempo real. Avaliamos a familiaridade com as ferramentas, testes e clean code.'
        ],
        [
            'title' => 'Desenho de Sistema',
            'desc' => 'Uma discussão sobre escalabilidade, concorrência e tradeoffs. Como você desenharia o fluxo de estornos de cartões de crédito em uma arquitetura distribuída?'
        ],
        [
            'title' => 'Oferta',
            'desc' => 'Decisão rápida. Apresentação formal de proposta, alinhamento de equity e onboarding remoto.'
        ]
    ]" 
/>

<x-frontend.highlight-section 
    title="Banco de Talentos" 
    desc="No momento atual, a equipe de engenharia se encontra alocada e com as posições completas focadas nas entregas deste ciclo. No entanto, procuramos ativamente seniores em Golang, Laravel e AWS." />

<x-frontend.cta-section 
    title="Junte-se à Origem" 
    subtitle="Envie seu perfil no LinkedIn, GitHub ou um descritivo de projetos de impacto." 
    buttonText="Enviar e-mail para Talentos" 
    buttonUrl="mailto:talents@originpay.com" />

@endsection