# Plugin `webkul/projects` ("Gestão de Processos")

> Convenções e decisões específicas deste plugin. Para o que vale para
> o projeto inteiro (convenção de Model de cadastro de negócio, regras
> de nomenclatura do sistema, comandos DDEV, etc.), veja o `CLAUDE.md`
> da raiz. Para o histórico narrado (o "porquê" de uma decisão antiga),
> veja a seção "Ver também" no final deste arquivo.

Plugin CORE do AureusERP (acompanhamento de tarefas/etapas/marcos),
rotulado **"Gestão de Processos"** no menu principal — renomeado
internamente de `Project` para `Processo` (02/09/2026) para liberar a
palavra "Projeto" para a entidade de negócio de `perseu/comercial`.
NÃO confundir os dois — namespaces e tabelas totalmente independentes,
sem FK entre si (ver "Nomenclatura atual do sistema" no `CLAUDE.md` da
raiz).

## Nomenclatura: "Processo" (não "Project", não "Projeto")

`Webkul\Project\Models\Processo` representa **todo o ciclo operacional
de ações sobre um Projeto** (`perseu/comercial`): início, negociação,
alterações, revisões, fechamento, compras, produção, até a
finalização — via Tarefas/Etapas/Marcos deste plugin. A relação técnica
entre `Projeto` (Comercial) e `Processo` (aqui) ainda não foi
desenhada — ver "Pendências" abaixo e `CONCEITO-OBRA-PROPOSTA-PROJETO.md`
(raiz do projeto).

### O que foi renomeado (Model/banco/Filament/permissões)

| Antes | Depois |
|---|---|
| `Webkul\Project\Models\Project` | `Webkul\Project\Models\Processo` |
| `Webkul\Project\Models\ProjectStage` | `Webkul\Project\Models\ProcessoStage` |
| tabela `projects_projects` | `projects_processos` |
| tabela `projects_project_stages` | `projects_processo_stages` |
| tabela `projects_project_tag` | `projects_processo_tag` |
| tabela `projects_user_project_favorites` | `projects_user_processo_favorites` |
| coluna `projects_tasks.project_id` | `processo_id` |
| coluna `projects_milestones.project_id` | `processo_id` |
| coluna `projects_task_stages.project_id` | `processo_id` |
| coluna `projects_processo_tag.project_id` | `processo_id` |
| coluna `projects_user_processo_favorites.project_id` | `processo_id` |
| coluna `analytic_records.project_id` | `processo_id` |
| `Filament\Resources\ProjectResource` | `ProcessoResource` (+ Pages `CreateProject`→`CreateProcesso`, `EditProject`→`EditProcesso`, `ListProjects`→`ListProcessos`, `ViewProject`→`ViewProcesso`) |
| `Clusters\Configurations\Resources\ProjectStageResource` | `ProcessoStageResource` (+ Page `ManageProjectStages`→`ManageProcessoStages`) |
| `Filament\Widgets\TopProjectsWidget` | `TopProcessosWidget` |
| `ProjectPolicy`/`ProjectStagePolicy` | `ProcessoPolicy`/`ProcessoStagePolicy` |
| `ProjectFactory`/`ProjectStageFactory` | `ProcessoFactory`/`ProcessoStageFactory` |
| Permissões `*_project_project`/`*_project_project::stage` | `*_project_processo`/`*_project_processo::stage` |
| Permissão `widget_project_top_projects_widget` | `widget_project_top_processos_widget` |

`TaskResource`, `MilestoneResource`, `TaskStageResource`,
`Models\Task`/`Milestone`/`TaskStage`/`Timesheet`, e os widgets
`TopAssigneesWidget`/`StatsOverviewWidget`/`TaskByStageChart`/
`TaskByStateChart`/`Dashboard` não foram renomeados (seus nomes não
continham "Project"), mas todos tiveram suas referências internas ao
relacionamento atualizadas (`project_id`→`processo_id`, `->project`→
`->processo`, lang keys `project`→`processo`).

### Exceções conscientes — deixadas de propósito com o nome antigo

- **`Webkul\Project\Enums\ProjectVisibility`** — CLASSE, ARQUIVO e
  chaves de tradução mantidos 100% intactos. Usado tanto pelo Filament
  quanto por `Http\Requests\ProjectRequest.php` (API preservada fora
  do escopo do rename); o texto traduzido de cada valor nunca conteve
  "Project"/"Projeto".
- **`Webkul\Project\Settings\TaskSettings::$enable_project_stages`**
  (Spatie `laravel-settings`) — a PROPRIEDADE PHP e a CHAVE de
  tradução foram mantidas sem mudança (renomear exigiria migration do
  blob JSON armazenado, desproporcional). O VALOR exibido ao usuário
  foi atualizado pra dizer "processo" nas traduções.
- **`protected static string $routePath = 'project';`** (`Dashboard`)
  e slugs com prefixo `project/` (`ProcessoResource::$slug =
  'project/processos'`, mesmo padrão em `ProcessoStageResource`) —
  mantidos assim de propósito: esse segmento é o namespace de rota
  COMPARTILHADO do plugin inteiro, renomear quebraria TODAS as URLs.
- **`ProjectPlugin`** (`Webkul\Project\ProjectPlugin`) — nome do
  PLUGIN INTEIRO, não da entidade Processo — não renomeado.

### Camada de API REST e testes — deliberadamente FORA do escopo

A API REST própria do plugin (`Http/Controllers/API/V1/*`,
`Http/Resources/V1/*`, `Http/Requests/*`, ~18 arquivos — ex.:
`ProjectController`, rota `admin/api/v1/projects/projects`) e a suíte
`tests/` continuam usando nomenclatura "Project"/"project" — decisão
consciente tomada com o usuário, não omissão, já que nada no Perseu
consome essa API hoje. Como essa camada consome DIRETAMENTE o Model/
colunas renomeados, as referências INTERNAS desses ~18 arquivos
(chamadas ao Model, nomes de coluna, `whenLoaded()`) foram corrigidas
o suficiente para não quebrar em runtime, preservando nomes de classe/
rota/campo do contrato JSON. Única exceção aceita: o campo
`processo_id` no payload (era `project_id`), inevitável porque a
COLUNA em si mudou de nome. Ver "Pendências" abaixo.

## Pendências

- **Vínculo Projeto ↔ Processo**: decidir só depois deste plugin
  estar estável em uso real — Opção A (Processo espelho por Projeto,
  exige sincronização) vs. Opção B (Processo único "guarda-chuva",
  referência só em texto). Definir no momento de implementar a
  automação de criação de tarefas (ex.: disparo por mudança de
  Situação do Projeto, em `perseu/comercial`).
- **API REST/testes com nomenclatura antiga**: se a API algum dia for
  consumida por um sistema externo real, revisitar o rename como
  tarefa própria (o `processo_id` que já mudou no payload precisará
  ser comunicado como breaking change aos consumidores).
- **Visão de motor de workflow** (futuro, não desenhado): usar a base
  de registros deste plugin (Processo, Tarefa, Etapa, Marco) como
  fundação para automatizar hand-offs entre departamentos disparados
  por eventos de negócio (ex.: Projeto mudando de Situação) — ver
  `CONCEITO-OBRA-PROPOSTA-PROJETO.md` (raiz), seção "Renomeação do
  plugin de acompanhamento + visão de motor de workflow".

## Ver também (histórico narrado, `HISTORICO-DESENVOLVIMENTO.md`)

- "Rename interno Project → Processo no plugin webkul/projects" (02/09/2026)
