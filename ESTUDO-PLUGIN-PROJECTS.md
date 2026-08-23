# Estudo do plugin `projects` (AureusERP original)

Investigação feita em `/home/projeto_studio/testes/aureuserp/plugins/webkul/projects`
(cópia de referência intocada do AureusERP original — nada foi alterado
lá nem no projeto atual). Namespace PHP do plugin: `Webkul\Project`
(singular), embora o pacote/diretório seja `webkul/projects` (plural).

## 1. Models e tabelas

| Model | Tabela | Papel |
|---|---|---|
| `Project` | `projects_projects` | O projeto em si |
| `ProjectStage` | `projects_project_stages` | Estágios do Kanban de **Projetos** (lista global, não por projeto) |
| `Task` | `projects_tasks` | Tarefa, pode ter subtarefas (auto-relacionamento) |
| `TaskStage` | `projects_task_stages` | Estágios do Kanban de **Tarefas** — um conjunto próprio **por projeto** (`project_id` na tabela) |
| `Milestone` | `projects_milestones` | Marco/entrega dentro de um projeto |
| `Tag` | `projects_tags` | Etiqueta livre, colorida, reaproveitável em Project e Task |
| `Timesheet` | *(não tem tabela própria — ver seção 4)* | Apontamento de horas |

Tabelas pivot: `projects_project_tag`, `projects_task_tag`,
`projects_task_users` (tarefa ↔ usuário, N:N), `projects_user_project_favorites`
(projeto favoritado por usuário).

### `Project` — colunas/relacionamentos principais
- Colunas: `name`, `tasks_label`, `description`, `visibility` (enum
  `ProjectVisibility`), `color`, `sort`, `start_date`, `end_date`,
  `allocated_hours`, `allow_timesheets`, `allow_milestones`,
  `allow_task_dependencies` (flags de feature por projeto),
  `is_active`, `stage_id`, `partner_id`, `company_id`, `user_id`
  (gerente do projeto), `creator_id`.
- Relacionamentos: `stage()` (ProjectStage), `taskStages()` (1:N,
  estágios de tarefa deste projeto), `tasks()`, `milestones()`,
  `tags()` (N:N), `partner()` → **`Webkul\Partner\Models\Partner`**
  (o model único de parceiro do AureusERP original — não as tabelas
  `pessoas_fisicas`/`pessoas_juridicas` do Perseu), `favoriteUsers()`
  (N:N com User).
- `getRemainingHoursAttribute()`: `allocated_hours - sum(tasks.remaining_hours)`.
- Usa `HasChatter`, `HasLogActivity` (comentários/log — ver seção 5),
  `HasCustomFields` (campos dinâmicos, plugin `webkul/fields`),
  `HasOwnershipScope` (visibilidade por dono/seguidor, plugin
  `webkul/security`), `BelongsToCompany` (multi-empresa),
  `SortableTrait` (drag-reorder via coluna `sort`).

### `Task` — colunas/relacionamentos principais
- Colunas: `title`, `description`, `color`, `priority` (**boolean**,
  não um enum de níveis — é só um "destacar/starred"), `state` (enum
  `TaskState`, string), `sort`, `is_active`, `is_recurring`,
  `deadline` (datetime), `working_hours_open/close`,
  `allocated_hours`, `remaining_hours`, `effective_hours`,
  `total_hours_spent`, `subtask_effective_hours`, `overtime`,
  `progress`, `stage_id`, `project_id`, `partner_id`, `parent_id`
  (subtarefa), `company_id`, `creator_id`.
- **Responsável ("assignee") é N:N**, não uma FK única:
  `users(): belongsToMany(User::class, 'projects_task_users')` — uma
  tarefa pode ter vários responsáveis simultaneamente.
- `milestone()` existe como relacionamento e a migration tem a coluna
  `milestone_id`, mas **ela não está no `$fillable` do model** — parece
  um descuido do código original (não é atribuída em massa por padrão).
- `parent()`/`subTasks()`: subtarefas via auto-relacionamento
  (`parent_id` na própria tabela `projects_tasks`).
- `timesheets()`: 1:N com `Timesheet` (ver seção 4).
- Os campos `allocated_hours/remaining_hours/effective_hours/
  total_hours_spent/overtime/progress` são **recalculados
  automaticamente** sempre que um Timesheet é criado/atualizado/
  removido (`Timesheet::updateTaskTimes()`, ver seção 4) — inclusive
  propagando para a tarefa-pai quando é uma subtarefa.

### `TaskState` (enum) vs. `stage_id` (tabela) — dois níveis de "status"
O plugin usa **dois conceitos de status em paralelo**, não um só:
- `TaskState` (`src/Enums/TaskState.php`): enum PHP fixo de 5 valores —
  `IN_PROGRESS`, `CHANGE_REQUESTED`, `APPROVED`, `CANCELLED`, `DONE` —
  cada um com cor/ícone/label. É um campo simples (`ToggleButtons`) no
  formulário da tarefa, sem relação com "colunas" visuais.
- `stage_id` → `TaskStage`: uma lista de estágios **configurável por
  projeto** (cada projeto tem seu próprio conjunto de `TaskStage`,
  criável/reordenável em Configurações), pensada para representar um
  fluxo visual tipo Kanban (tem `sort`, `is_collapsed`).

Ou seja: `state` = "qual é a situação/aprovação da tarefa" (fixo,
igual em todo o sistema); `stage_id` = "em que coluna do fluxo do
projeto ela está" (livre, por projeto). São ortogonais.

## 2. Estrutura da Task — resumo direto

| Aspecto | Como é modelado |
|---|---|
| Status/estágio | `stage_id` (FK, configurável por projeto) + `state` (enum fixo, 5 valores) — dois campos independentes, ver acima |
| Responsável | `users()` — **N:N** via `projects_task_users`, não uma coluna `assignee_id` |
| Prazo | `deadline` (datetime) |
| Prioridade | `priority` (**boolean** — "starred/destacada", não um enum de níveis tipo baixa/média/alta) |
| Descrição | `description` (texto rico, `RichEditor` no form) |
| Vínculo com Projeto | `project_id` (obrigatório em termos de fluxo, embora `nullable` na migration) |
| Vínculo com Milestone | `milestone_id` (opcional) |
| Subtarefas | `parent_id` (auto-relacionamento, uma subtarefa é só uma Task com `parent_id` preenchido) |
| Tags | N:N via `projects_task_tag` |
| Horas | `allocated_hours` (planejado) vs. `remaining_hours`/`effective_hours`/`total_hours_spent`/`overtime`/`progress` (calculados a partir dos Timesheets) |

## 3. Kanban — achado importante: **não existe um board visual de arrastar-e-soltar**

Apesar de todo o modelo de dados estar desenhado para suportar um
Kanban (estágios ordenáveis por projeto, `SortableTrait`, colunas
colapsáveis via `is_collapsed`), **a tela de Tarefas do AureusERP
nesta versão não implementa uma visualização de quadro/colunas
arrastáveis**. Não há nenhum pacote de Kanban no `composer.json`
(ex: `mokhosh/filament-kanban`, `guava/filament-kanban`) nem
referência a "kanban"/"board" em nenhuma Blade view do plugin
(não existe nenhuma Blade view custom no plugin — só arquivos de
tradução).

O que existe de fato:
1. **Lista de Tarefas em tabela** (`ProjectResource\Pages\ManageTasks`,
   uma `ManageRelatedRecords` do Filament padrão), com abas de filtro
   pré-definidas via `webkul/table-views` (`HasTableViews`):
   "Abertas", "Minhas Tarefas" (`whereHas('users', ... Auth::id())`),
   "Sem responsável", "Fechadas", "Favoritas" (`priority = true`),
   "Arquivadas". A tabela tem `->reorderable('sort', direction: 'desc')`
   — dá pra arrastar linhas pra reordenar, mas dentro de uma lista
   linear, não entre colunas.
2. **Um "Progress Stepper" dentro do formulário da própria tarefa**
   (`Webkul\Field\Filament\Forms\Components\ProgressStepper`, plugin
   `webkul/fields`) — um componente horizontal tipo "wizard" com um
   botão por `TaskStage` do projeto; clicar em um estágio muda
   `stage_id` da tarefa que está sendo editada. É a forma de "mudar de
   coluna", mas exige abrir a tarefa — não é drag-and-drop numa tela
   de quadro.

**Conclusão**: o material de marketing do AureusERP sugere Kanban,
mas o código vendido/vendorizado aqui só entrega a base de dados
pronta pra um Kanban (estágios + ordenação) — a UI de quadro em si
teria que ser construída (ou vem de outro pacote/versão não presente
nesta cópia).

## 4. Timesheets

**Não existe uma tabela `projects_timesheets` própria.** O model
`Webkul\Project\Models\Timesheet` **estende**
`Webkul\Analytic\Models\Record` (plugin `webkul/analytics`), cuja
tabela real é `analytic_records` — uma tabela **genérica de
"lançamentos analíticos"**, compartilhada por outros módulos do ERP
(padrão claramente inspirado no conceito `account.analytic.line` do
Odoo). As colunas relevantes de `analytic_records`: `type` (discrimina
o "dono" do lançamento — a Relation Manager do Projects grava sempre
`type = 'projects'` num campo `Hidden`), `name` (descrição), `date`,
`amount`, `unit_amount` (= horas), `partner_id`, `company_id`,
`user_id`, `creator_id`. `Timesheet` adiciona só `project()`/`task()`
(BelongsTo) e a lógica de recálculo.

- **Vínculo com "Employee": não existe.** O campo que a tela rotula
  como "Employee" é literalmente `Select::make('user_id')
  ->relationship('user', 'name')` — aponta pro model
  `Webkul\Security\Models\User` (o mesmo usado em login/permissões),
  não pro plugin `webkul/employees`. Ou seja, registrar horas não
  depende de ninguém ser um "Funcionário" cadastrado — só de ser um
  usuário do sistema.
- **Vínculo com Task**: `TimesheetsRelationManager` dentro da tela da
  Task (`->relationship('timesheets')`), com campos `date`, `user_id`,
  `name` (descrição), `unit_amount` (horas). Só aparece se
  `TimeSettings::enable_timesheets` estiver ligado E o projeto tiver
  `allow_timesheets = true`.
- **Efeito colateral automático**: toda vez que um Timesheet é criado/
  atualizado/removido, `Timesheet::updateTaskTimes()` recalcula
  `total_hours_spent`, `effective_hours`, `overtime`, `remaining_hours`
  e `progress` da Task (somando os timesheets dela + das subtarefas),
  e propaga o mesmo recálculo pra tarefa-pai se for uma subtarefa.

## 5. Chatter

`plugins/webkul/chatter` é um **plugin totalmente separado e
genérico** (não é específico de Projects) — um sistema de
comentários/log de atividade **polimórfico**, anexável a qualquer
model via a trait `Webkul\Chatter\Traits\HasChatter` (que `Project` e
`Task` usam).

**Tecnicamente NÃO é "tempo real" via WebSocket** — não há nenhuma
referência a Laravel Echo/Reverb/Pusher/`ShouldBroadcast` em todo o
plugin. É "tempo real" no sentido de: comentários aparecem na hora via
recarregamento normal do componente Livewire (`ChatterPanel`) depois
de uma ação (enviar comentário), e o restante (menções, notificação
por e-mail) roda via fila/eventos do Laravel, não push ao vivo pro
navegador de outro usuário. O termo de marketing é mais forte que a
implementação real.

Estrutura:
- **`Message`** (`chatter_messages`): tabela única pra comentários E
  log de atividade E "activity" agendada — discriminada por `type`.
  `messageable_type`/`messageable_id` (MorphTo) — o registro ao qual a
  mensagem pertence. Campos: `subject`, `body`, `summary`,
  `is_internal`, `date_deadline`, `pinned_at`, `log_name`, `event`,
  `assigned_to`, `causer_type`/`causer_id` (quem originou, via morph —
  nomenclatura idêntica ao pacote `spatie/laravel-activitylog`, que o
  Perseu provavelmente já usa/conhece de outro contexto), `properties`
  (JSON livre).
- **`Follower`**: quem está "seguindo" um registro (recebe
  notificação quando algo muda nele). Adicionado automaticamente:
  `HasChatter::bootHasChatter()` chama `addDefaultChatterFollowers()`
  na criação e `syncResponsibleChatterFollower()` na atualização — daí
  o método `chatterResponsibles()` que `Task`/`Project` implementam
  (`return ['users']` — quando a coluna que guarda os responsáveis
  muda, os novos responsáveis viram followers automaticamente).
  **Acoplamento a observar**: `syncResponsibleChatterFollower()` faz
  `User::whereKey(...)->value('partner_id')` e depois
  `Partner::find($partnerId)` — ou seja, o Follower é indexado pelo
  `Webkul\Partner\Models\Partner`, não diretamente pelo User. Ver mais
  sobre isso na seção de avaliação.
- **`Attachment`**: arquivos anexados a uma mensagem.
- **`ChatterNotificationService`**: dispara notificação (banco +
  e-mail, via `ChatterDatabaseNotification`/`FollowerMail`/
  `MessageMail`) pros Followers quando uma nova Message é criada
  (hook `static::created()` no Message, dentro de
  `app()->terminating(...)` — roda no fim do request, não bloqueia a
  resposta).
- **`ChatterMentions`**: suporte a `@menção` dentro do texto do
  comentário.
- **`ChatterPanel`** (Livewire): o componente visual embutido na tela
  de Edit/View de um Resource (é isso que aparece como aba/painel de
  "Log" ou comentários na interface).
- `HasLogActivity` (trait separada, também usada por Task/Project) é o
  que gera as entradas de **log automático de mudança de campo** (ex.:
  "fulano mudou o estágio de X pra Y") — usa `getLogAttributeLabels()`
  (visto em Task/Project) pra saber quais campos rotular; tecnicamente
  também grava na mesma tabela `chatter_messages`, só que com
  `type`/`log_name` diferentes dos comentários manuais.

## 6. Widgets / Dashboard — o ponto de maior interesse

Existe uma página de Dashboard **dedicada ao Projects**
(`Webkul\Project\Filament\Pages\Dashboard`, nav própria "Project"), **separada
do dashboard principal do painel** — não é o `/admin` padrão, é uma
página adicional. Ela é uma classe que estende
`Filament\Pages\Dashboard` (a própria página de dashboard nativa do
Filament) e usa `HasFiltersForm` (recurso nativo do Filament pra
dashboards com filtro compartilhado entre todos os widgets da página).

### Formulário de filtro (`filtersForm()`)
Um `Section` com 4 campos `Select` multi-seleção + um range de datas:
- `selectedProjects` (Project::pluck('name','id'))
- `selectedAssignees` (**User::pluck('name','id')** — de novo, não é
  Employee)
- `selectedTags` (Tag)
- `selectedPartners` (**`Webkul\Partner\Models\Partner`**)
- Range de data via `Webkul\Support\Filament\Forms\Components\DashboardDateRange`
  (um componente reutilizável do plugin `webkul/support`)

Todo widget da página lê esses filtros via
`Filament\Widgets\Concerns\InteractsWithPageFilters`
(`$this->pageFilters['selectedProjects']` etc.) — é assim que o filtro
compartilhado do topo afeta todos os widgets ao mesmo tempo.

### Os 5 widgets (`getWidgets()`)
1. **`StatsOverviewWidget`** (`StatsOverviewWidget` nativo do Filament,
   3 `Stat::make()`): total de tarefas, total de horas gastas, total
   de horas restantes — cada um com % de variação vs. o período
   anterior (calculado manualmente comparando dois intervalos de
   datas) e um mini-gráfico de tendência via pacote
   `flowframe/laravel-trend` (`Trend::query(...)->perDay()->aggregate(...)`).
2. **`TaskByStageChart`** (`ChartWidget` nativo, tipo `bar`): conta
   tarefas por `TaskStage` (todos os estágios de todos os projetos,
   sem filtrar por projeto — pode misturar estágios de projetos
   diferentes com o mesmo nome, o código só desambigua o *label* no
   eixo X se o nome se repetir).
3. **`TaskByStateChart`** (`ChartWidget`, tipo `pie`): conta tarefas
   por `TaskState` (os 5 valores fixos do enum), cores vindas do
   próprio enum.
4. **`TopAssigneesWidget`** (`TableWidget` nativo): ranking dos 10
   usuários que mais lançaram horas (`Timesheet`/`analytic_records`
   agrupado por `user_id`, `SUM(unit_amount)` + `COUNT(DISTINCT
   task_id)`), ordenado por horas desc.
5. **`TopProjectsWidget`** (`TableWidget`): mesma lógica, agrupado por
   projeto em vez de usuário.

**Achado importante para o objetivo do Perseu**: **nenhum desses 5
widgets é "minhas tarefas pendentes" do usuário logado.** Todos são
visões **gerenciais/agregadas** (totais, ranking, gráficos), filtráveis
manualmente por qualquer usuário via o formulário do topo — não há
nenhum widget que, sozinho, mostre "as tarefas atribuídas a mim que
ainda não terminei". O mais próximo disso no plugin inteiro é a aba
de tabela "Minhas Tarefas" (`my_tasks`, `whereHas('users', ...
Auth::id())`) dentro da lista de Tarefas de um projeto específico —
que é uma visão de TABELA all filtrada, não um widget de dashboard, e
está por projeto (não teria como ver "minhas tarefas de todos os
projetos" numa tela só sem entrar projeto por projeto).

Ou seja: a peça que o Perseu quer construir — um dashboard
centrado no usuário — **não existe pronta neste plugin**. O padrão
arquitetural (widget `TableWidget` com `InteractsWithPageFilters`,
`getTableRecordKey()`, query customizada) é reaproveitável como
referência técnica, mas o RECORTE (minhas tarefas, por usuário) teria
que ser desenhado do zero.

## 7. Dependências de outros plugins

Confirmado via `use Webkul\...` no código-fonte de `projects`
(não há uma declaração formal de dependências — o AureusERP não versiona
isso via composer entre plugins do mesmo monorepo, é tudo resolvido
via autoload compartilhado):

| Plugin | Para quê |
|---|---|
| `webkul/security` | `User` (assignee de Task, gerente de Project, "employee" do Timesheet), `HasOwnershipScope`, `OwnerSource` |
| `webkul/support` | `Company`/`BelongsToCompany` (multi-empresa), `DashboardDateRange` (componente de filtro), enums/traits genéricos |
| `webkul/partner` | `Partner` — usado em `Project.partner_id` (cliente do projeto), no filtro `selectedPartners` do Dashboard, e internamente pelo Chatter (`Follower` é resolvido via `Partner`, não via `User` direto) |
| `webkul/chatter` | Comentários/log de atividade (`HasChatter`, `HasLogActivity`) — ver seção 5 |
| `webkul/analytics` | Base do `Timesheet` (`analytic_records`, model `Record`) — ver seção 4 |
| `webkul/fields` | `HasCustomFields` (campos dinâmicos configuráveis) e o componente `ProgressStepper` (form/infolist) usado pro `stage_id` da Task |
| `webkul/table-views` | `HasTableViews`/`PresetView` — as abas de filtro pré-definidas nas listagens (Minhas Tarefas, Abertas, etc.) |
| `webkul/plugin-manager` | Infraestrutura de registro/instalação do próprio plugin (`PackageServiceProvider`) — não é uma dependência de domínio |

**Não depende de `webkul/employees`** — contrário ao que a descrição
da tarefa cogitava, o "responsável" e o "empregado que lançou hora"
são sempre o model `User` de `webkul/security`, nunca um Employee.

---

## Avaliação de integração ao Perseu

### O que vale como referência de arquitetura (reconstruir, não copiar)

1. **Separar "estado fixo" de "estágio configurável"** (`TaskState`
   enum vs. `stage_id`/`TaskStage` por projeto) é uma ideia sólida e
   fácil de adaptar: um enum PHP pequeno e estável (ex.: Pendente/Em
   andamento/Concluída/Cancelada) pro filtro rápido e pros widgets, e
   opcionalmente (se algum dia fizer sentido) uma tabela de estágios
   configuráveis por Projeto — sem precisar da segunda parte
   imediatamente.
2. **`InteractsWithPageFilters` + uma página de Dashboard própria com
   `filtersForm()` compartilhado entre widgets** é exatamente o
   mecanismo nativo do Filament que o Perseu deveria usar pro
   "Dashboard de usuários" pedido — não precisa reinventar nada aqui,
   é a forma idiomática e já documentada do próprio Filament v4.
3. **`TableWidget` customizado com query própria** (como
   `TopAssigneesWidget`/`TopProjectsWidget`) é o padrão certo pra um
   widget "Minhas tarefas pendentes" — uma tabela dentro de um widget,
   com `getTableRecordKey()` e uma query Eloquent normal filtrada por
   `Auth::id()`.
4. **Recalcular campos agregados via observer no model
   filho** (`Timesheet::updateTaskTimes()` recalculando a Task ao
   salvar/apagar um lançamento) é um padrão limpo, direto — se o Perseu
   um dia quiser "horas gastas" numa Tarefa, essa é a forma de fazer.
5. **`ownershipSources()`/`HasOwnershipScope`** (do `webkul/security`,
   plugin que o Perseu já usa) já dá visibilidade por
   "dono"/"responsável" pronta — vale olhar se services do Perseu já
   usam isso em algum lugar antes de reinventar controle de "só vejo
   minhas tarefas".

### O que seria arriscado/trabalhoso demais integrar direto

1. **`webkul/partner`**: `Project.partner_id`, o filtro
   `selectedPartners` do Dashboard, e a lógica de Follower do Chatter
   dependem todos do model único `Partner` do AureusERP original — que
   o Perseu **deliberadamente não usa como entidade de primeira
   classe** (CLAUDE.md: cadastro próprio de Pessoa Física/Jurídica em
   tabelas separadas; o `Partner` "técnico" continua existindo só por
   baixo do `User::boot()`, sem interface). Trazer qualquer coisa do
   `projects` que toque `Partner` diretamente reintroduziria esse
   acoplamento pela porta dos fundos.
2. **`webkul/analytics`** (`analytic_records`/`Record`): útil, mas é
   uma tabela genérica pensada pra encaixar timesheets, custos e
   provavelmente outros lançamentos financeiros/contábeis do ERP
   inteiro (o `type` discrimina o dono). Adotar isso significaria
   trazer um plugin inteiro novo (e seu modelo mental de "analytic
   line" ao estilo Odoo) só pra registrar horas — desproporcional se o
   Perseu só quer "Fulano trabalhou 2h na Tarefa X".
3. **`webkul/chatter`** como um todo: é o pedaço mais tentador (dá
   comentários + log de atividade + notificação de graça em qualquer
   model via uma trait), mas arrasta consigo `webkul/support`
   (`ActivityType`, `Company`), a dependência de `Partner` já citada
   no fluxo de Follower, e uma tabela `chatter_messages` desenhada pro
   ERP inteiro (com conceitos como `activity_type_id`,
   `date_deadline`, que não têm equivalente hoje no Perseu). Reaproveitar
   a **ideia** (mensagens polimórficas com Follower) num plugin próprio
   e enxuto é razoável; importar o plugin `chatter` inteiro para só
   comentar em Tarefas é mais peso do que o benefício justifica agora.
4. **`webkul/fields`** (`HasCustomFields`, `ProgressStepper`): campos
   dinâmicos configuráveis é um recurso poderoso mas é uma
   infraestrutura própria (EAV) que o Perseu não usa em nenhum plugin
   `perseu/*` hoje — não vale adotar só pelo componente `ProgressStepper`.
5. **`webkul/table-views`** (`HasTableViews`/`PresetView`): as abas de
   filtro pré-definidas são um recurso bacana, mas é outro
   plugin/dependência nova. Dá pra conseguir o mesmo resultado (abas
   "Minhas Tarefas"/"Pendentes"/"Concluídas") só com `->filters()`
   nativo do Filament Table ou até um `Tabs` simples no topo da
   listagem, sem precisar do pacote.

### Sugestão de estrutura — plugin `perseu/followup` (ou `perseu/tarefas`)

Nome sugerido: **`perseu/followup`** — evita colidir semanticamente
com "Projeto" (que já é o nome do model em `perseu/comercial`) e
descreve bem o propósito (acompanhamento de pendências ligadas a um
Projeto/obra).

**Escopo mínimo, sem nenhuma dependência de `webkul/projects`,
`webkul/partner`, `webkul/analytics`, `webkul/chatter` ou
`webkul/fields`** — só do que o Perseu já usa (`webkul/security` pro
`User`, `perseu/comercial` pro `Projeto`):

- **Model `Tarefa`** (`plugins/perseu/followup`), tabela própria
  `followup_tarefas`:
  - `titulo`, `descricao` (nullable), `prazo` (date, nullable),
    `prioridade` (enum PHP simples: Baixa/Normal/Alta — mais simples
    que o `priority` boolean do AureusERP, que não deu pra diferenciar
    "urgente" de "importante"),
  - `status` (enum PHP: Pendente/Em Andamento/Concluída/Cancelada —
    igual ao padrão `TaskState` que se mostrou útil; **sem** um
    segundo conceito de "estágio configurável" — não há indício de que
    o Perseu precise de um Kanban por projeto agora, e isso é fácil de
    adicionar depois se a necessidade aparecer),
  - `projeto_id` — FK **direta pra `Perseu\Comercial\Models\Projeto`**
    (não um model "Project" próprio do plugin — reaproveita o que já
    existe, é literalmente o pedido da tarefa),
  - `responsavel_id` — FK pra `Webkul\Security\Models\User` (um único
    responsável por tarefa é suficiente pro caso de uso descrito;
    N:N como no AureusERP só se a necessidade real aparecer depois —
    não antecipar),
  - `criador_id`, `concluida_em` (nullable, preenchido quando o status
    vira Concluída), timestamps + SoftDeletes.
  - Sem tabela de "horas gastas" nem Timesheet nesta primeira versão —
    não foi pedido, e traria a decisão difícil de "de onde vem o
    tempo" sem um caso de uso definido ainda.
- **Resource Filament `TarefaResource`**: campos padrão do plugin
  (`Select` de Projeto filtrado pelos projetos do usuário se fizer
  sentido, `Select` de Responsável, `DatePicker` de prazo,
  `Select`/`ToggleButtons` de status e prioridade). Segue as mesmas
  convenções já estabelecidas no Perseu (`HasCompactFieldWidth` pros
  campos compactos, tradução pt_BR completa, etc. — ver seções
  correspondentes no CLAUDE.md).
- **Dashboard**: uma página própria (`Filament\Pages\Dashboard`
  customizada, exatamente como `Webkul\Project\Filament\Pages\Dashboard`
  faz — sem herdar nada do plugin deles, só seguindo o mesmo padrão
  nativo do Filament) com **um widget principal**: `TableWidget`
  "Minhas Tarefas Pendentes" —
  `Tarefa::where('responsavel_id', Auth::id())->whereNotIn('status', ['concluida','cancelada'])`,
  ordenado por prazo, com colunas Título / Projeto (nome da
  obra) / Prazo / Prioridade / Status, e uma ação rápida "Marcar como
  concluída" direto na linha da tabela (`Action::make()->action(fn
  ($record) => $record->update(['status' => 'concluida', 'concluida_em'
  => now()]))`) — resolve o pedido central ("cada usuário vê suas
  tarefas pendentes vinculadas aos projetos/obras já cadastrados") sem
  precisar de filtro manual nem tela extra.
  - Widgets adicionais (opcionais, fase 2, se quiserem visão
    gerencial): um `StatsOverviewWidget` simples (total pendente/
    atrasado/concluído no mês) e um `ChartWidget` por status — ambos
    fáceis de acrescentar depois, seguindo o mesmo padrão dos widgets
    do AureusERP (arquitetura reaproveitável, só os dados são
    diferentes).
- Notificação: se algum dia quiserem avisar o responsável quando uma
  tarefa é atribuída a ele ou o prazo está próximo, dá pra usar as
  Notifications nativas do Laravel (`Illuminate\Notifications`, já
  usadas em outros pontos do painel — ex.: `->databaseNotifications()`
  no `AdminPanelProvider`) sem precisar de nada do `webkul/chatter`.

Essa estrutura entrega exatamente o que a tarefa original pediu — um
dashboard de tarefas pendentes por usuário, vinculado aos projetos já
cadastrados — com o menor acoplamento possível a plugins do AureusERP
que o Perseu não usa hoje, e reaproveitando só as **ideias**
arquiteturais (separação status/prioridade, `InteractsWithPageFilters`,
`TableWidget` customizado) que se mostraram sólidas na leitura do
código original.
