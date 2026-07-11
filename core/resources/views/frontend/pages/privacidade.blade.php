@extends('frontend.layouts.landing')
@section('title', 'Política de Privacidade — OriginPay')
@section('description', 'Diretrizes oficiais da OriginPay sobre Privacidade.')

@section('content')

<div class="docs-header" style="padding: 80px 20px 40px; border-bottom: 1px solid var(--border); background: var(--bg-deep); text-align: center;">
    <div class="container">
        <div class="docs-breadcrumb" style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 16px; font-weight: 500;">
            Institucional <span style="margin:0 8px;">/</span> Política de Privacidade
        </div>
        <h1 class="docs-title" style="font-size: 2.8rem; font-weight: 700; color: #fff; margin-bottom: 16px; letter-spacing: -0.02em;">Política de Privacidade</h1>
        <div class="docs-meta" style="display: flex; gap: 24px; justify-content: center; color: var(--text-muted); font-size: 0.95rem;">
            <div class="docs-meta-item"><i class="fas fa-clock"></i> 5 min de leitura</div>
            <div class="docs-meta-item"><i class="far fa-calendar-alt"></i> Atualizada: 05 de julho de 2026</div>
        </div>
    </div>
</div>

<div class="docs-container" style="max-width: 1200px; margin: 0 auto; display: flex; gap: 40px; padding: 40px 20px; align-items: flex-start;">
    
    <x-frontend.sticky-toc :sections="[
        'quem-somos' => '1. Quem somos', 
        'dados-coletados' => '2. Quais dados coletamos', 
        'utilizacao' => '3. Como utilizamos seus dados', 
        'bases-legais' => '4. Bases legais',
        'compartilhamento' => '5. Compartilhamento',
        'cookies' => '6. Cookies',
        'seguranca' => '7. Segurança da informação',
        'retencao' => '8. Retenção dos dados',
        'direitos' => '9. Direitos do titular',
        'transferencia' => '10. Transferência internacional',
        'alteracoes' => '11. Alterações desta Política',
        'contato' => '12. Contato'
    ]" />

    <main class="docs-main" style="max-width: 720px; flex-grow: 1;">
        <div class="docs-content">
            <p style="font-size: 1.1rem; color: var(--text-muted); margin-bottom: 30px; line-height: 1.6;">A OriginPay respeita a sua privacidade e está comprometida com a proteção dos seus dados pessoais. Esta Política de Privacidade descreve como coletamos, utilizamos, armazenamos, compartilhamos e protegemos as informações de nossos usuários ao utilizar nossa plataforma, website, APIs e demais serviços relacionados.</p>
            <p style="margin-bottom: 40px; font-weight: 500;">Ao utilizar a OriginPay, você declara que leu e compreendeu esta Política de Privacidade.</p>

            <h2 id="quem-somos" class="docs-section-title">1. Quem somos</h2>
            <p>A OriginPay é uma plataforma de soluções financeiras e processamento de pagamentos que oferece serviços destinados a pessoas físicas e jurídicas, sempre observando a legislação aplicável, especialmente a Lei nº 13.709/2018 (Lei Geral de Proteção de Dados – LGPD).</p>

            <h2 id="dados-coletados" class="docs-section-title">2. Quais dados coletamos</h2>
            <p>Podemos coletar diferentes categorias de informações para possibilitar o funcionamento da plataforma.</p>
            
            <h3 class="docs-subsection-title">Dados de cadastro</h3>
            <ul class="docs-list">
                <li>Nome completo;</li>
                <li>CPF ou CNPJ;</li>
                <li>Razão social (quando aplicável);</li>
                <li>Data de nascimento;</li>
                <li>Endereço;</li>
                <li>E-mail;</li>
                <li>Número de telefone;</li>
                <li>Informações de autenticação da conta.</li>
            </ul>

            <h3 class="docs-subsection-title">Dados financeiros</h3>
            <p>Quando necessários para a prestação dos serviços, poderão ser coletados:</p>
            <ul class="docs-list">
                <li>Chaves Pix;</li>
                <li>Dados bancários;</li>
                <li>Histórico de transações;</li>
                <li>Informações de recebimentos;</li>
                <li>Informações de transferências;</li>
                <li>Dados relacionados à prevenção de fraudes.</li>
            </ul>

            <h3 class="docs-subsection-title">Dados técnicos</h3>
            <p>Durante a utilização da plataforma, poderemos coletar automaticamente:</p>
            <ul class="docs-list">
                <li>Endereço IP;</li>
                <li>Data e horário dos acessos;</li>
                <li>Tipo de navegador;</li>
                <li>Sistema operacional;</li>
                <li>Modelo do dispositivo;</li>
                <li>Identificadores técnicos;</li>
                <li>Logs de utilização;</li>
                <li>Cookies e tecnologias semelhantes.</li>
            </ul>

            <h2 id="utilizacao" class="docs-section-title">3. Como utilizamos seus dados</h2>
            <p>Os dados pessoais poderão ser utilizados para:</p>
            <ul class="docs-list">
                <li>criar e administrar sua conta;</li>
                <li>autenticar sua identidade;</li>
                <li>processar pagamentos e transferências;</li>
                <li>prevenir fraudes e atividades ilícitas;</li>
                <li>cumprir obrigações legais e regulatórias;</li>
                <li>melhorar nossos produtos e serviços;</li>
                <li>prestar suporte ao usuário;</li>
                <li>responder solicitações e comunicações;</li>
                <li>enviar notificações relacionadas à conta;</li>
                <li>garantir a segurança da plataforma.</li>
            </ul>
            <p>Jamais utilizaremos seus dados para finalidades incompatíveis com esta Política ou com a legislação vigente.</p>

            <h2 id="bases-legais" class="docs-section-title">4. Bases legais para o tratamento</h2>
            <p>O tratamento dos dados pessoais poderá ocorrer com fundamento em uma ou mais das seguintes bases legais previstas na LGPD:</p>
            <ul class="docs-list">
                <li>execução de contrato;</li>
                <li>cumprimento de obrigação legal ou regulatória;</li>
                <li>exercício regular de direitos;</li>
                <li>legítimo interesse;</li>
                <li>proteção do crédito, quando aplicável;</li>
                <li>consentimento do titular, quando necessário.</li>
            </ul>

            <h2 id="compartilhamento" class="docs-section-title">5. Compartilhamento de informações</h2>
            <p>A OriginPay poderá compartilhar informações apenas quando necessário para a prestação dos serviços ou para cumprimento de obrigações legais.</p>
            <p>Os dados poderão ser compartilhados com:</p>
            <ul class="docs-list">
                <li>instituições financeiras;</li>
                <li>parceiros de processamento de pagamentos;</li>
                <li>prestadores de serviços de hospedagem e infraestrutura;</li>
                <li>fornecedores de tecnologia;</li>
                <li>empresas de prevenção à fraude;</li>
                <li>autoridades públicas, mediante obrigação legal ou determinação competente.</li>
            </ul>
            <p>A OriginPay não vende nem comercializa dados pessoais.</p>

            <h2 id="cookies" class="docs-section-title">6. Cookies</h2>
            <p>Utilizamos cookies e tecnologias semelhantes para:</p>
            <ul class="docs-list">
                <li>manter sua sessão autenticada;</li>
                <li>lembrar preferências;</li>
                <li>melhorar a experiência de navegação;</li>
                <li>aumentar a segurança;</li>
                <li>gerar estatísticas de utilização da plataforma.</li>
            </ul>
            <p>O usuário poderá configurar seu navegador para bloquear cookies, embora determinadas funcionalidades possam deixar de operar corretamente.</p>

            <h2 id="seguranca" class="docs-section-title">7. Segurança da informação</h2>
            <p>Adotamos medidas técnicas e administrativas compatíveis com os padrões do mercado para proteger seus dados contra acesso não autorizado, perda, destruição, alteração ou divulgação indevida.</p>
            <p>Entre essas medidas podem estar:</p>
            <ul class="docs-list">
                <li>criptografia de dados;</li>
                <li>conexões seguras (HTTPS/TLS);</li>
                <li>controle de acesso;</li>
                <li>autenticação de usuários;</li>
                <li>monitoramento de segurança;</li>
                <li>registros de auditoria;</li>
                <li>backups periódicos.</li>
            </ul>
            <p>Apesar de nossos esforços, nenhum sistema é completamente imune a riscos de segurança.</p>

            <h2 id="retencao" class="docs-section-title">8. Retenção dos dados</h2>
            <p>Os dados pessoais serão armazenados apenas pelo tempo necessário para:</p>
            <ul class="docs-list">
                <li>execução dos serviços;</li>
                <li>cumprimento de obrigações legais;</li>
                <li>atendimento de exigências regulatórias;</li>
                <li>prevenção à fraude;</li>
                <li>exercício regular de direitos em processos administrativos ou judiciais.</li>
            </ul>
            <p>Após o término do período necessário, os dados poderão ser eliminados ou anonimizados, observadas as hipóteses legais de conservação.</p>

            <h2 id="direitos" class="docs-section-title">9. Direitos do titular</h2>
            <p>Nos termos da LGPD, o titular dos dados poderá solicitar, quando aplicável:</p>
            <ul class="docs-list">
                <li>confirmação da existência de tratamento;</li>
                <li>acesso aos dados pessoais;</li>
                <li>correção de dados incompletos, inexatos ou desatualizados;</li>
                <li>anonimização, bloqueio ou eliminação de dados tratados em desconformidade com a legislação;</li>
                <li>portabilidade dos dados;</li>
                <li>eliminação dos dados tratados com base no consentimento, quando cabível;</li>
                <li>informação sobre compartilhamento de dados;</li>
                <li>revogação do consentimento, quando aplicável.</li>
            </ul>
            <p>As solicitações poderão ser realizadas pelos canais oficiais disponibilizados pela OriginPay.</p>

            <h2 id="transferencia" class="docs-section-title">10. Transferência internacional de dados</h2>
            <p>Quando necessário para a prestação dos serviços, alguns dados poderão ser processados ou armazenados em servidores localizados fora do Brasil, sempre observando as garantias previstas na legislação aplicável e adotando medidas adequadas para proteção das informações.</p>

            <h2 id="alteracoes" class="docs-section-title">11. Alterações desta Política</h2>
            <p>Esta Política de Privacidade poderá ser modificada periodicamente para refletir alterações legais, regulatórias, tecnológicas ou operacionais.</p>
            <p>A versão mais recente estará sempre disponível na plataforma da OriginPay.</p>

            <h2 id="contato" class="docs-section-title">12. Contato</h2>
            <p>Em caso de dúvidas, solicitações relacionadas aos seus dados pessoais ou exercício de direitos previstos na LGPD, entre em contato com a OriginPay por meio dos canais oficiais de atendimento disponibilizados em nossa plataforma.</p>

        </div>
    </main>
</div>

@endsection