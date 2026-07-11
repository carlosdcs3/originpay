@extends('frontend.layouts.landing')
@section('title', 'Termos de Uso — OriginPay')
@section('description', 'Termos de Uso e condições gerais da OriginPay.')

@section('content')

<div class="docs-header" style="padding: 80px 20px 40px; border-bottom: 1px solid var(--border); background: var(--bg-deep); text-align: center;">
    <div class="container">
        <div class="docs-breadcrumb" style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 16px; font-weight: 500;">
            Institucional <span style="margin:0 8px;">/</span> Termos de Uso
        </div>
        <h1 class="docs-title" style="font-size: 2.8rem; font-weight: 700; color: #fff; margin-bottom: 16px; letter-spacing: -0.02em;">Termos de Uso</h1>
        <div class="docs-meta" style="display: flex; gap: 24px; justify-content: center; color: var(--text-muted); font-size: 0.95rem;">
            <div class="docs-meta-item"><i class="fas fa-clock"></i> 6 min de leitura</div>
            <div class="docs-meta-item"><i class="far fa-calendar-alt"></i> Atualizado: 05 de julho de 2026</div>
        </div>
    </div>
</div>

<div class="docs-container" style="max-width: 1200px; margin: 0 auto; display: flex; gap: 40px; padding: 40px 20px; align-items: flex-start;">
    
    <x-frontend.sticky-toc :sections="[
        'definicoes' => '1. Definições', 
        'aceitacao' => '2. Aceitação dos Termos', 
        'cadastro' => '3. Cadastro', 
        'elegibilidade' => '4. Elegibilidade',
        'utilizacao' => '5. Utilização da Plataforma',
        'obrigacoes' => '6. Obrigações do Usuário',
        'atividades-proibidas' => '7. Atividades Proibidas',
        'verificacao' => '8. Verificação de Identidade',
        'taxas' => '9. Taxas e Cobranças',
        'disponibilidade' => '10. Disponibilidade',
        'suspensao' => '11. Suspensão e Encerramento',
        'propriedade' => '12. Propriedade Intelectual',
        'limitacao' => '13. Limitação de Responsabilidade',
        'alteracoes-servicos' => '14. Alterações dos Serviços',
        'alteracoes-termos' => '15. Alterações destes Termos',
        'lei-aplicavel' => '16. Lei Aplicável',
        'foro' => '17. Foro',
        'contato' => '18. Contato'
    ]" />

    <main class="docs-main" style="max-width: 720px; flex-grow: 1;">
        <div class="docs-content">
            <p style="font-size: 1.1rem; color: var(--text-muted); margin-bottom: 30px; line-height: 1.6;">Bem-vindo à OriginPay. Estes Termos de Uso estabelecem as regras, direitos e obrigações aplicáveis à utilização da plataforma OriginPay, incluindo seu website, painel administrativo, APIs, integrações e demais serviços disponibilizados.</p>
            <p style="margin-bottom: 40px; font-weight: 500;">Ao criar uma conta ou utilizar qualquer funcionalidade da plataforma, o usuário declara que leu, compreendeu e concorda integralmente com estes Termos.</p>

            <h2 id="definicoes" class="docs-section-title">1. Definições</h2>
            <p>Para os fins destes Termos:</p>
            <ul class="docs-list">
                <li><strong>OriginPay</strong>: plataforma responsável pela disponibilização dos serviços.</li>
                <li><strong>Usuário</strong>: pessoa física ou jurídica cadastrada na plataforma.</li>
                <li><strong>Conta</strong>: ambiente individual criado para utilização dos serviços.</li>
                <li><strong>Serviços</strong>: soluções oferecidas pela OriginPay relacionadas ao processamento e gerenciamento de pagamentos e demais funcionalidades disponibilizadas.</li>
            </ul>

            <h2 id="aceitacao" class="docs-section-title">2. Aceitação dos Termos</h2>
            <p>O acesso e utilização da plataforma implicam na aceitação integral destes Termos.</p>
            <p>Caso o usuário não concorde com qualquer disposição aqui prevista, deverá interromper imediatamente a utilização dos serviços.</p>

            <h2 id="cadastro" class="docs-section-title">3. Cadastro</h2>
            <p>Para utilizar a plataforma é necessário realizar um cadastro fornecendo informações verdadeiras, completas e atualizadas.</p>
            <p>O usuário compromete-se a:</p>
            <ul class="docs-list">
                <li>manter seus dados sempre atualizados;</li>
                <li>fornecer apenas informações verídicas;</li>
                <li>responder pela veracidade das informações fornecidas;</li>
                <li>manter suas credenciais de acesso em sigilo.</li>
            </ul>
            <p>A OriginPay poderá solicitar documentos adicionais para confirmação das informações fornecidas.</p>

            <h2 id="elegibilidade" class="docs-section-title">4. Elegibilidade</h2>
            <p>Ao utilizar a plataforma, o usuário declara possuir capacidade legal para contratar os serviços ou estar devidamente representado, quando se tratar de pessoa jurídica.</p>

            <h2 id="utilizacao" class="docs-section-title">5. Utilização da Plataforma</h2>
            <p>O usuário poderá utilizar os serviços disponibilizados pela OriginPay exclusivamente para finalidades lícitas e em conformidade com a legislação vigente.</p>
            <p>É proibida qualquer utilização que possa comprometer a segurança, estabilidade ou funcionamento da plataforma.</p>

            <h2 id="obrigacoes" class="docs-section-title">6. Obrigações do Usuário</h2>
            <p>O usuário compromete-se a:</p>
            <ul class="docs-list">
                <li>utilizar a plataforma de forma responsável;</li>
                <li>proteger suas credenciais de acesso;</li>
                <li>comunicar imediatamente qualquer uso não autorizado da conta;</li>
                <li>cumprir a legislação aplicável;</li>
                <li>não utilizar terceiros para fraudar operações;</li>
                <li>cooperar com solicitações relacionadas à segurança e prevenção à fraude.</li>
            </ul>
            <p>Todas as atividades realizadas por meio da conta serão consideradas de responsabilidade do respectivo usuário.</p>

            <h2 id="atividades-proibidas" class="docs-section-title">7. Atividades Proibidas</h2>
            <p>É expressamente proibido utilizar a OriginPay para:</p>
            <ul class="docs-list">
                <li>lavagem de dinheiro;</li>
                <li>financiamento ao terrorismo;</li>
                <li>fraudes financeiras;</li>
                <li>envio de informações falsas;</li>
                <li>comercialização de produtos ou serviços ilícitos;</li>
                <li>violação de direitos de terceiros;</li>
                <li>invasão de sistemas;</li>
                <li>distribuição de malware;</li>
                <li>tentativa de engenharia reversa da plataforma;</li>
                <li>exploração de vulnerabilidades;</li>
                <li>qualquer atividade proibida pela legislação brasileira.</li>
            </ul>
            <p>A constatação de qualquer dessas práticas poderá resultar na suspensão imediata da conta.</p>

            <h2 id="verificacao" class="docs-section-title">8. Verificação de Identidade</h2>
            <p>Para garantir a segurança da plataforma, a OriginPay poderá solicitar informações e documentos adicionais para confirmação da identidade do usuário, prevenção à fraude ou atendimento de obrigações legais e regulatórias.</p>
            <p>O usuário concorda em fornecer as informações solicitadas sempre que necessário.</p>

            <h2 id="taxas" class="docs-section-title">9. Taxas e Cobranças</h2>
            <p>Os serviços disponibilizados pela OriginPay poderão estar sujeitos à cobrança de tarifas.</p>
            <p>As taxas aplicáveis serão informadas ao usuário por meio da plataforma ou de outros canais oficiais.</p>
            <p>A continuidade da utilização dos serviços após eventual alteração nas tarifas será interpretada como concordância com os novos valores.</p>

            <h2 id="disponibilidade" class="docs-section-title">10. Disponibilidade dos Serviços</h2>
            <p>A OriginPay busca manter seus serviços disponíveis continuamente.</p>
            <p>Entretanto, poderão ocorrer interrupções decorrentes de:</p>
            <ul class="docs-list">
                <li>manutenção preventiva;</li>
                <li>manutenção corretiva;</li>
                <li>atualizações da plataforma;</li>
                <li>falhas de infraestrutura;</li>
                <li>indisponibilidade de serviços de terceiros;</li>
                <li>eventos de força maior.</li>
            </ul>
            <p>A OriginPay não garante disponibilidade ininterrupta dos serviços.</p>

            <h2 id="suspensao" class="docs-section-title">11. Suspensão e Encerramento da Conta</h2>
            <p>A OriginPay poderá limitar, suspender ou encerrar contas sempre que verificar, entre outras hipóteses:</p>
            <ul class="docs-list">
                <li>descumprimento destes Termos;</li>
                <li>suspeita de fraude;</li>
                <li>utilização de documentos falsos;</li>
                <li>atividades ilícitas;</li>
                <li>determinação de autoridade competente;</li>
                <li>riscos à segurança da plataforma.</li>
            </ul>
            <p>A suspensão poderá ocorrer preventivamente durante procedimentos internos de análise.</p>

            <h2 id="propriedade" class="docs-section-title">12. Propriedade Intelectual</h2>
            <p>Todos os direitos relacionados à plataforma, incluindo:</p>
            <ul class="docs-list">
                <li>software;</li>
                <li>código-fonte;</li>
                <li>identidade visual;</li>
                <li>marca;</li>
                <li>logotipos;</li>
                <li>layout;</li>
                <li>APIs;</li>
                <li>documentação;</li>
                <li>banco de dados;</li>
                <li>conteúdos disponibilizados,</li>
            </ul>
            <p>pertencem exclusivamente à OriginPay ou aos respectivos licenciadores, sendo protegidos pela legislação aplicável.</p>
            <p>Nenhum direito de propriedade intelectual é transferido ao usuário.</p>

            <h2 id="limitacao" class="docs-section-title">13. Limitação de Responsabilidade</h2>
            <p>Na máxima extensão permitida pela legislação aplicável, a OriginPay não será responsável por prejuízos decorrentes de:</p>
            <ul class="docs-list">
                <li>utilização inadequada da plataforma;</li>
                <li>falhas de conexão à internet;</li>
                <li>indisponibilidade de serviços de terceiros;</li>
                <li>fornecimento de informações incorretas pelo usuário;</li>
                <li>ataques cibernéticos inevitáveis;</li>
                <li>casos fortuitos ou de força maior;</li>
                <li>atos praticados por terceiros fora do controle da OriginPay.</li>
            </ul>

            <h2 id="alteracoes-servicos" class="docs-section-title">14. Alterações dos Serviços</h2>
            <p>A OriginPay poderá modificar, atualizar, ampliar ou descontinuar funcionalidades da plataforma sempre que necessário para sua evolução, segurança ou adequação legal.</p>
            <p>Sempre que possível, alterações relevantes serão comunicadas previamente aos usuários.</p>

            <h2 id="alteracoes-termos" class="docs-section-title">15. Alterações destes Termos</h2>
            <p>Os presentes Termos poderão ser atualizados periodicamente.</p>
            <p>A versão vigente estará sempre disponível na plataforma.</p>
            <p>A continuidade da utilização dos serviços após a publicação de alterações será considerada como aceitação da nova versão.</p>

            <h2 id="lei-aplicavel" class="docs-section-title">16. Lei Aplicável</h2>
            <p>Estes Termos são regidos pelas leis da República Federativa do Brasil.</p>

            <h2 id="foro" class="docs-section-title">17. Foro</h2>
            <p>Fica eleito o foro competente previsto na legislação brasileira para solucionar quaisquer controvérsias decorrentes destes Termos, observadas as normas de proteção ao consumidor quando aplicáveis.</p>

            <h2 id="contato" class="docs-section-title">18. Contato</h2>
            <p>Em caso de dúvidas sobre estes Termos de Uso ou sobre os serviços oferecidos pela OriginPay, entre em contato por meio dos canais oficiais de atendimento disponibilizados na plataforma.</p>

        </div>
    </main>
</div>

@endsection