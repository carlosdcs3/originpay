# OriginPay Design System (Enterprise Dashboard)

O Design System oficial para a área administrativa da OriginPay foi estabelecido visando padronização total, reusabilidade de UI e consistência de navegação. É terminantemente **proibido** criar HTML estrutural bruto ou escrever lógicas de negócio e formatações pesadas nos *Controllers*.

## 1. Regras de Arquitetura de Backend (Obrigatório)

Todos os módulos devem, impreterivelmente, seguir a cascata:
**Controller** (Fino) → **DashboardService** (Pesado) → **DTO** (Tipado) → **Blade** (UI Pura)

- **Controllers**: Não devem conter NENHUMA lógica financeira ou de UI. Eles recebem o Request, passam os filtros para o Service e enviam o DTO para a Blade.
- **DashboardServices**: Nomeados como `[Modulo]DashboardService.php`. Devem isolar as queries pesadas, resolver N+1, clonar a query original para gerar KPIs exatos baseados nos filtros da UI, e popular o DTO.
- **DTOs**: Todas as informações transitadas para a View devem estar encapsuladas em classes (ex: `TransactionDashboardData.php`), com tipagem forte e collections bem definidas.

## 2. Padrões de Interface e Regras Visuais

### CSS e Javascript Centralizados
- **`public/assets/css/admin-enterprise.css`**: Único arquivo responsável pelos componentes do painel. **É proibido** criar CSS específico (ex: `.wallet-hero`, `.chargeback-table`).
- **`public/assets/js/admin-drawer.js`**: Implementação oficial do Drawer. Todo ciclo de vida, preenchimento e eventos do Offcanvas acontecem aqui. Nenhum módulo terá um JS próprio de Offcanvas.

### Componentes Blade Obrigatórios (`resources/views/components/admin/`)

Se uma página possuir uma interface estrutural, ela deve ser montada em formato de *Lego* usando os componentes abaixo. Caso precise de algo novo, construa um componente flexível, nunca um HTML "inline".

- `<x-admin.page-hero>`: Topo da página explicando a finalidade ("O que aconteceu?").
- `<x-admin.kpi-grid>`: (Opcional) Container para agrupar múltiplos cards numéricos.
- `<x-admin.kpi-card>`: Blocos que **devem recalcular** quando filtros de data ou status forem aplicados.
- `<x-admin.alerts-area>`: Conecta com `FinanceAlertService` para avisos de risco de forma proativa.
- `<x-admin.smart-filter>`: Agrupador inline de formulários que direciona via GET.
- `<x-admin.data-table>`: Recebe paginação `$paginator` e trata automaticamente interatividade e tabelas responsivas.
- `<x-admin.empty-state>`: Acionado dentro das tabelas, explicando porque um filtro não trouxe dados.
- `<x-admin.error-state>`: Acionado em caso de falhas conhecidas de comunicação ou acesso negado.
- `<x-admin.drawer>`: Reagente ao `admin-drawer.js`, renderiza o painel lateral no clique da linha da `data-table`.
- `<x-admin.timeline>`: Cria uma trilha linear visual de auditoria ou rastreabilidade.
- `<x-admin.json-viewer>`: Para exibição elegante de metadados complexos.

### Padrões de Quick Actions
- Devem estar em formato de `button` ou `a` (links) dentro do `<x-admin.page-hero>` (topo) ou injetados dinamicamente no `<x-admin.drawer>` ao clicar em uma linha.
- Exemplo no Drawer: "Ver Saque", "Forçar Conciliação", etc. Sempre apontando para instâncias exatas ligadas aos metadados.

## 3. Critérios de Homologação de Módulos (Definição de Pronto)
Nenhum módulo é considerado entregue apenas porque "aparece na tela". Ele deve obedecer aos requisitos abaixo para receber o selo de Gold Standard:
1. **Arquitetura**: O Controller tem < 25 linhas? O Service e o DTO estão abstraídos?
2. **Performance**: Consultas N+1 tratadas? Múltiplos count() de KPI partem de uma *query clonada* para evitar idas ao DB?
3. **Segurança e Auditoria**: O Drawer fornece a trilha inegável das operações e ID dos envolvidos?
4. **Reutilização**: A página é composta 100% de tags `<x-admin.*>` sem divs isoladas?
5. **Aderência ao Design System**: A interface responde instantaneamente a filtros sem quebrar layout? Respeita o CSS agnóstico?
