# OriginPay Admin Design System

Bem-vindo ao OriginPay Admin Design System. Este documento dita as regras, os componentes base e as boas práticas de desenvolvimento para a evolução contínua da arquitetura de UX do painel administrativo. O objetivo é garantir **100% de consistência visual e operacional** sem introduzir novas dependências ou sobrecarga de assets.

## Princípios de UI/UX
1. **Zero N+1 Cliques**: O uso de abas, modais e Drawers deve substituir a navegação excessiva entre páginas. Toda a contextualização de um registro deve ocorrer de forma fluida.
2. **Componentização Estrita**: Nenhuma tabela ou KPI card pode ser construído via HTML livre. O uso dos componentes Blade prefixados com x-admin é mandatório.
3. **Modo Noturno Nativizado (Dark/Light)**: Utilizar impreterivelmente as classes semânticas do CoreUI/Bootstrap 5 (ex: g-body, 	ext-body-emphasis) em substituição a cores absolutas (g-white, 	ext-black).

---

## Catálogo de Componentes

### 1. Page Hero (<x-admin.page-hero>)
Cabeçalho padronizado da página. Suporta status globais e indicadores rápidos em linha.

**Props:**
- 	itle (obrigatório)
- description
- readcrumbs (Array: ['Label' => 'URL'])
- status (ex: "Ativo")
- statusColor (ex: "success")
- environment (ex: "Produção")
- quickStats (Array: [['label' => '', 'value' => '']])

**Exemplo de uso:**
``blade
<x-admin.page-hero 
    title="Ledger Financeiro" 
    description="Registro imutável..."
    status="Online"
    statusColor="success"
    :quickStats="[['label' => 'Total', 'value' => 'R$ 1M']]"
>
    <!-- Slot para botões principais -->
    <button class="btn btn-primary">Nova Ação</button>
</x-admin.page-hero>
``

### 2. KPI Grid e Card (<x-admin.kpi-grid> e <x-admin.kpi-card>)
Para exibição de indicadores operacionais. Suporta estados de loading.

**Props do Card:**
- 	itle
- alue
- subtitle
- 	rend ('up', 'down', 'neutral')
- delta (ex: '+5.4%')
- icon (ex: 'fa-solid fa-wallet')
- color (padrão 'primary')
- href (transforma o card clicável via stretched-link)
- loading (boolean, ativa o skeleton nativo)
- 	ooltip

**Exemplo de uso:**
``blade
<x-admin.kpi-grid>
    <x-admin.kpi-card 
        title="Volume (TPV)" 
        value=",500.00" 
        trend="up" 
        delta="+12%" 
        icon="fa-solid fa-chart-line" 
        loading="false"
    />
</x-admin.kpi-grid>
``

### 3. Smart Filter (<x-admin.smart-filter>)
Padronização da área de filtros.

**Props:**
- ction (Route GET)
- searchPlaceholder
- ctiveFilters (Array com keys da Request e labels visuais)

### 4. Data Table (<x-admin.data-table>)
Tabela 100% responsiva, com paginação e ações em massa.

**Props:**
- headers (Array)
- paginator (Instância do Laravel Paginator)
- ulkActions (Slot para botões quando linhas forem selecionadas)
- loading (boolean)
- emptyStateTitle & emptyStateDesc

**Nota sobre Row Click:** Os botões de ação na linha devem acionar primariamente um Drawer para inspeção de dados contextuais.

### 5. Drawer (<x-admin.drawer>)
Painel lateral expansível que atua como centro de visualização do fluxo de vida da entidade.

**Props:**
- id
- 	itle
- size ('md', 'lg', 'xl')
- position ('end', 'start')
- 	abs (Array de abas, ex: ['Resumo', 'Auditoria'])
- ooterActions (Slot para botões de confirmação/estorno)

### 6. Timeline (<x-admin.timeline>)
Exibição do histórico e ciclo de vida de registros.

**Composição:** Utiliza <x-admin.timeline> envolta de vários <x-admin.timeline-item>.
- **Props do Item:** 	itle, 	ime, status ('active', 'success', 'danger'), icon.

### 7. JSON Viewer (<x-admin.json-viewer>)
Exibe Payloads e Headers sem recorrer a bibliotecas de sintaxe externas. Usa nativo <pre><code> formatado.

**Props:**
- data (String JSON ou Array)

**Exemplo de uso:**
``blade
<x-admin.json-viewer :data="$webhookPayload" />
``

---

*Gerado automaticamente na Fase 1.5 da refatoração OriginPay.*
