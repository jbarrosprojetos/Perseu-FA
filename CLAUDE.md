# Contexto do projeto Perseu

> Para o histórico narrado de tarefas, bugs corrigidos e investigações
> já feitas (o "porquê" de uma decisão antiga), consulte
> `HISTORICO-DESENVOLVIMENTO.md`. Este arquivo mantém só o que vale
> para o projeto inteiro — convenções específicas de cada plugin
> vivem no `CLAUDE.md` daquele plugin (ver índice abaixo).

Este projeto é baseado no AureusERP, customizado para uma marcenaria
industrial (Perseu).

## Idioma de comunicação

Todas as respostas, resumos, explicações e perguntas do Claude Code
neste projeto devem ser sempre em Português do Brasil, independente do
idioma em que a tarefa foi escrita. Isso vale para comunicação com o
usuário — não afeta nomes de variáveis, funções, classes ou
comentários de código, que seguem a convenção de nomenclatura já usada
no projeto (majoritariamente português nos domínios de negócio,
conforme já é o padrão). Não confundir com a seção "Idioma" abaixo,
que trata dos idiomas DO SISTEMA/interface para usuários finais
(`pt_BR`/`en`) — assunto diferente.

## Antes de qualquer tarefa de código, consulte:
- Este arquivo (convenções universais, válidas pra qualquer plugin).
- O `CLAUDE.md` do plugin específico em que a tarefa mexe (ver índice
  abaixo) — convenções, decisões de design e armadilhas conhecidas
  daquele módulo. O Claude Code carrega automaticamente o `CLAUDE.md`
  de uma subpasta ao trabalhar com arquivos dela (confirmado
  empiricamente nesta sessão), então normalmente não é preciso abrir
  esse arquivo manualmente — mas vale conferir se a tarefa cruza mais
  de um plugin.
- AUDITORIA-ESTRUTURA.md — como funciona controle de acesso, usuários,
  empresas e branding
- GUIA-CRIACAO-PLUGIN.md — passo a passo para criar novos plugins seguindo
  as convenções deste projeto
- HISTORICO-DESENVOLVIMENTO.md — contexto histórico, se precisar entender
  o porquê de uma decisão

## Índice de plugins com `CLAUDE.md` próprio

| Plugin | O que cobre |
|---|---|
| [`plugins/perseu/comercial/CLAUDE.md`](plugins/perseu/comercial/CLAUDE.md) | Cadastro de Projeto (obras da F.A. Marcenaria), Referência de Preços |
| [`plugins/perseu/pessoas/CLAUDE.md`](plugins/perseu/pessoas/CLAUDE.md) | Pessoa Física/Jurídica, Endereços, Contatos, Categorias, largura de campos/layout de formulário |
| [`plugins/perseu/auditoria/CLAUDE.md`](plugins/perseu/auditoria/CLAUDE.md) | Central de Auditoria, Lixeira Central |
| [`plugins/webkul/support/CLAUDE.md`](plugins/webkul/support/CLAUDE.md) | Company/Branch (localização brasileira), Branding, `NavigationGroup`, idioma/RTL (mecanismo) |
| [`plugins/webkul/projects/CLAUDE.md`](plugins/webkul/projects/CLAUDE.md) | Gestão de Processos (rename interno Project→Processo) |

Plugins `webkul/*` sem customização registrada (a maioria) não têm
`CLAUDE.md` próprio ainda — só criar um quando esse plugin ganhar
alguma decisão/convenção real específica, não preventivamente.

## Idioma

Todo o sistema deve ser traduzido/adaptado para português do Brasil,
incluindo campos específicos do Brasil (CPF, CNPJ, RG, Inscrição
Estadual) quando aplicável.

**Locales suportados: só `pt_BR` e `en`** (2026-09-01, removido
suporte a Espanhol `es` e Árabe `ar`, herdados do AureusERP original
— sem uso real num sistema 100% voltado à F.A. Marcenaria, no Brasil).
`pt_BR` é o locale principal (`config/app.php` → `locale`, refletido
em `.env` → `APP_LOCALE`); `en` é mantido só como `fallback_locale`
técnico padrão do Laravel e para legibilidade de código por qualquer
desenvolvedor. `config('app.supported_locales')` (`config/app.php`) é
a fonte única que alimenta o seletor de idioma da topbar, o middleware
`App\Http\Middleware\SetLocale` e o Select de idioma em Perfil/Usuário — **todo Model/Resource novo, em qualquer
plugin, só precisa de arquivo de tradução em `lang/pt_BR/` e
`lang/en/` (ou `resources/lang/pt_BR|en/` de plugin), não mais 4
idiomas.** Não recriar diretórios `lang/es`/`lang/ar` nem adicionar
`es`/`ar` de volta a `supported_locales` sem decisão consciente nova.
O MECANISMO de implementação (trait de RTL, language-switcher da
topbar) vive em `webkul/support` — ver `CLAUDE.md` daquele plugin.

## Atenção: Resources duplicados entre plugins

Alguns Resources existem em mais de um plugin (o caso conhecido hoje:
`CompanyResource`, em `webkul/security` E `webkul/support` — ver
`plugins/webkul/support/CLAUDE.md` para qual versão está ativa).
**Antes de editar qualquer Resource, confirme com `route:list` ou
busca por nome de classe qual versão está realmente ativa**, para não
editar a versão errada — vale para qualquer Resource, não só o caso já
conhecido.

## Tema Bonsai — removido, não reinstalar

`qalainau/bonsai-theme` foi removido definitivamente do projeto
(2026-08-24) por conflitos recorrentes de espaçamento/tipografia com
`perseu/pessoas`/`perseu/comercial` (que já têm compactação própria —
ver `plugins/perseu/pessoas/CLAUDE.md`). Não está mais ativo. Se algo
parecido for cogitado no futuro, veja o histórico da investigação em
`HISTORICO-DESENVOLVIMENTO.md` e o gotcha de `!important` documentado
em `plugins/perseu/pessoas/CLAUDE.md` antes de reinstalar qualquer
tema de densidade.

## Debugbar via Role com Guard Sanctum (não `APP_DEBUG`)

A Debugbar (`barryvdh/laravel-debugbar`) não é controlada por
`APP_DEBUG` — fica condicionada a o usuário autenticado ter uma Role
com `guard_name = 'sanctum'`.

- Middleware `App\Http\Middleware\ControlDebugbarVisibility`
  (registrado em `->authMiddleware([Authenticate::class,
  ControlDebugbarVisibility::class])` de `AdminPanelProvider`) chama
  `Debugbar::enable()`/`disable()` por requisição conforme
  `$user->roles()->where('guard_name', 'sanctum')->exists()`.
- `config/debugbar.php` precisa de `'force_allow_enable' =>
  env('DEBUGBAR_FORCE_ALLOW_ENABLE', true)` — sem isso,
  `canBeEnabled()` retorna `false` com `APP_DEBUG=false`, e o
  `ServiceProvider::boot()` do pacote nem registra os listeners que
  injetam a barra na resposta.
- **Para habilitar em um usuário**: Configurações → Funções → Nova
  Função → Guard = **Sanctum** (não Web) → salvar → atribuir ao
  usuário (Segurança → Usuários → Roles). Efeito imediato na próxima
  requisição, sem logout/login. Uma role "Sistema" (guard `sanctum`) já
  existe no ambiente para isso.
- Motivo: `APP_DEBUG` é global (expõe SQL/stack traces pra todo mundo
  com acesso ao painel); por Role permite ligar só para quem precisa
  investigar, mesmo em produção.

## Convenção para Model novo de cadastro de negócio

Sempre que criar um novo Model de cadastro de negócio (Projeto, Pessoa
Física/Jurídica, Referência de Preços, etc., em QUALQUER plugin), siga
desde o primeiro commit:

1. Usar `Illuminate\Database\Eloquent\SoftDeletes`.
2. Usar `Perseu\Auditoria\Traits\LogsBusinessActivity` (não escrever
   `getActivitylogOptions()` à mão, a não ser que o Model precise de
   algo diferente do padrão — aí sobrescrever o método depois do
   `use`). O trait audita os mesmos campos do `$fillable`
   (`logFillable()` + `logOnlyDirty()` + `dontSubmitEmptyLogs()`) e
   registra automaticamente um log de `forceDeleted` (evento que o
   Spatie Activitylog NÃO grava nativamente — só
   `created/updated/deleted/restored`; o listener próprio do trait
   fecha essa lacuna).
3. Se o Model participa de uma relação `BelongsToMany` com `Endereco`
   e/ou `HasMany` com `Contato` (ou dado análogo sem `SoftDeletes`
   próprio): usar `Perseu\Pessoas\Traits\CascadesRelatedDataOnForceDelete`
   (ou padrão equivalente) — **sempre em `forceDeleting`, NUNCA em
   `deleting`**, para não apagar dados relacionados num soft-delete
   comum e quebrar a Lixeira/Restaurar.
4. O Resource correspondente precisa de página de Edit/View dedicada
   (não `ManageRecords`) para ganhar `TrashedFilter` +
   `RestoreAction`/`ForceDeleteAction`. Se o cadastro for simples o
   bastante pra usar `ManageRecords` (Categoria/Setor/Situação/Tipo),
   aceite que ele fica sem Lixeira de UI própria (mas continua
   auditado pela Central) até uma decisão consciente de reestruturar —
   não implemente isso por padrão em todo cadastro novo sem
   necessidade real.
5. Adicionar `RestoreAction`/`ForceDeleteAction`/`RestoreBulkAction`/
   `ForceDeleteBulkAction` + `TrashedFilter` no `table()` do Resource.
   **Não** adicionar `ActivitylogRelationManager` — a aba de
   "Atividades" por registro foi removida do sistema (ver
   `plugins/perseu/auditoria/CLAUDE.md`, "Auditoria e Lixeira —
   arquitetura atual").
6. Declarar `restore`/`restore_any`/`force_delete`/`force_delete_any`
   no `config/filament-shield.php` do plugin, rodar `shield:generate`
   e sincronizar manualmente com a role Admin (`shield:generate`
   sozinho NÃO sincroniza — precisa de `$admin->givePermissionTo(...)`
   manual).
7. Registrar o Model em `Perseu\Auditoria\Support\SubjectTypeCatalog`
   (rótulo, módulo, `referenceFor()`, `applyBusca()`) e, se ganhar
   Lixeira de UI, em `Perseu\Auditoria\Support\TrashCatalog::models()`
   — sem isso o Model continua funcionando, só fica sem filtro
   dedicado/referência amigável na Central de Auditoria/Lixeira
   Central até ser adicionado. Ver `plugins/perseu/auditoria/CLAUDE.md`
   para o funcionamento completo dessas duas classes — **se o Model
   for renomeado depois de já ter logs gravados, o
   `activity_log.subject_type` (FQCN cru) precisa de uma atualização
   de dados na própria migration de rename, ou o histórico de
   auditoria anterior ao rename fica órfão** (achado real, ver
   `plugins/perseu/auditoria/CLAUDE.md`).

## Auditoria e Lixeira

Arquitetura completa (Central de Auditoria, Lixeira Central,
`SubjectTypeCatalog`, `TrashCatalog`, filtros, busca) documentada em
`plugins/perseu/auditoria/CLAUDE.md` — qualquer Model novo em qualquer
plugin que siga a convenção acima passa a aparecer lá automaticamente.

## Navegação: Cluster vs. grupo achatado (regra)

Para um conjunto de Resources/Pages aparecerem juntos como itens do
MESMO dropdown na topbar (padrão "Pessoas"/"Comercial", vários itens
irmãos, sem sidebar): **NÃO os agrupe sob um Cluster comum** — declare
`getNavigationGroup()` apontando para o MESMO caso de
`Webkul\Support\Enums\NavigationGroup` diretamente em cada um, SEM
`$cluster`. Um Resource clusterizado NUNCA se registra sozinho na
navegação principal (`HasNavigation::registerNavigationItems()` retorna
cedo se `getCluster()` estiver preenchido) — só o Cluster aparece,
como item único.

Um Cluster é a ferramenta certa quando o objetivo é o OPOSTO: UM item
no dropdown que abre uma sidebar própria com sub-hierarquia (padrão
"Configurações", "Projetos", "Referências" — ver
`Perseu\Comercial\Filament\Clusters\{Projetos,Referencias}`, detalhado
em `plugins/perseu/comercial/CLAUDE.md`). Nesse caso:
- `getNavigationGroup()` só no Cluster, nunca nos Resources filhos —
  se o Resource também declarar (herança do padrão achatado), a
  sidebar do Cluster lança `\Exception` quando o grupo tem ícone E os
  itens também têm `$navigationIcon` próprio.
- Sobrescrever `getClusterBreadcrumb()` explicitamente retornando
  `static::getNavigationLabel()` — o padrão do Filament deriva o
  breadcrumb do nome da CLASSE, sem tradução (bug conhecido do Cluster
  `Configurations` de `webkul/projects`, "Configurations >" em inglês,
  não corrigido por ser de outro plugin).
- Declarar o slug completo manualmente em cada Resource filho
  (`{slug do Cluster}/{slug do Resource}` é a rota final).
- Adicionar `'pages' => ['exclude' => [MeuCluster::class]]` em
  `config/filament-shield.php` — um Cluster "puro" (sem Page própria
  além dos Resources filhos) vira um toggle de permissão morto sem
  isso.

Sempre declarar `getNavigationGroup()` em pelo menos um item de
qualquer `NavigationGroup` novo (criando o caso + label/ícone +
traduções em pt_BR/en se ainda não existir um adequado) — nunca
deixar um Cluster/Resource de topo sem isso, ou cai num grupo anônimo
compartilhado, escondido do dropdown da topbar.

## Nomenclatura atual do sistema (estado vigente — pode confundir se olhar código antigo)

| Conceito | Nome ATUAL | Namespace/plugin |
|---|---|---|
| Entidade interna de gestão de processos (Kanban/tarefas internas) | **Processo** (antes "Project"/"Projeto") | `Webkul\Project\Models\Processo` — plugin core `webkul/projects` |
| Cadastro de negócio de marcenaria (obras da F.A. Marcenaria) | **Projeto** (antes "Obra", que por sua vez tinha sido renomeado de "Projeto" originalmente — ver histórico) | `Perseu\Comercial\Models\Projeto` — plugin `perseu/comercial` |

Não confundir os dois: são namespaces/tabelas totalmente independentes,
sem FK entre si. A relação entre eles ainda não foi desenhada
tecnicamente (ver `CONCEITO-OBRA-PROPOSTA-PROJETO.md`, e "Pendências"
abaixo). Detalhamento técnico de cada rename está no `CLAUDE.md` do
plugin correspondente (`plugins/perseu/comercial/CLAUDE.md`,
`plugins/webkul/projects/CLAUDE.md`).

## Roadmap / Pendências globais (cross-plugin)

Pendências específicas de um único plugin vivem no `CLAUDE.md` daquele
plugin. Aqui só ficam as que dependem de mais de um módulo ou de uma
decisão do usuário que afeta vários de uma vez:

- **Vínculo Projeto (`perseu/comercial`) ↔ Processo (`webkul/projects`)**:
  decidir só depois do plugin de Gestão de Processos estar estável em
  uso real — Opção A (Processo espelho por Projeto, exige
  sincronização) vs. Opção B (Processo único "guarda-chuva", referência
  só em texto). Definir no momento de implementar a automação de
  criação de tarefas (ex.: disparo por mudança de Situação do
  Projeto). Próximo passo do plano de 4 etapas em
  `CONCEITO-OBRA-PROPOSTA-PROJETO.md` (passos 1 e 2 já concluídos).
- **Remover Lixeira/TrashedFilter individual de Projeto (`perseu/comercial`)/
  PF/PJ (`perseu/pessoas`)**: o filtro "Excluídos" e as ações de
  Restaurar/Excluir Permanentemente em cada Resource individual
  continuam ativos, redundantes com a Lixeira Central
  (`perseu/auditoria`) — só devem ser removidos depois que o usuário
  confirmar explicitamente que a Lixeira Central substitui bem esse
  acesso. Não remover sem essa confirmação.

## Filament — mecanismos que valem lembrar

- **`CreateAction`/`EditAction` abrem em MODAL por padrão**, mesmo fora
  de um Relation Manager — só passam a navegar para uma página cheia
  quando o Resource declara essa page (`'create'`/`'edit'`) em
  `getPages()` E `static::getResource()::hasPage('create'|'edit')`
  retorna `true` (`Filament\Resources\Pages\Page::getDefaultActionUrl()`).
  Para um cadastro simples que deve abrir sempre em modal (padrão já
  usado por Filial/`BranchesRelationManager` e por Referência de
  Preços), basta reduzir `getPages()` do Resource para só `'index'` —
  as `CreateAction`/`EditAction` já existentes na tabela/header não
  precisam de nenhuma outra mudança.
- **`Livewire::test()->callAction()`/`callTableAction()` rodado via
  `artisan tinker` (fora de um `TestCase` real do PHPUnit) é
  inconsistente para checar erros de validação** —
  `getErrorBag()`/`assertActionVisible()` dependem de
  `PHPUnit\Framework\Assert::$instance`, que só existe dentro de um
  test runner de verdade. Ao validar uma Action assim, confie no
  ESTADO NO BANCO após a chamada, não na ausência/presença de erro
  reportada pelo teste. Para simular edição de um campo dentro de uma
  Action montada, escrever direto no path
  `mountedActions.0.data.{campo}` é mais confiável que encadear
  `fillForm()`/`setTableActionData()`.
- **`Livewire::test(...)->fillForm([...])` é um NO-OP TOTAL quando
  rodado via `artisan tinker`** (achado real, 2026-09-04, plugin
  `perseu/comercial` — ItemProjeto/Item Avulso) — não preenche
  NADA, silenciosamente, sem erro. Causa raiz confirmada lendo o
  código-fonte: `Filament\Forms\Testing\TestsForms::fillForm()` delega
  pra `InteractsWithSchemas::fillFormDataForTesting()`, que começa com
  `if (! app()->runningUnitTests()) { return; }` — e
  `app()->runningUnitTests()` só é `true` dentro de um `TestCase` real
  do PHPUnit/Pest (setado pelo próprio framework de teste), NUNCA
  dentro de `artisan tinker`. Sintoma característico: `$get('data.
  campo')` volta `null`/vazio logo depois de um `fillForm(['campo' =>
  'valor'])`, e pior — se HOUVE uma Action montada e desmontada antes
  (`mountAction`/`unmountAction`) no mesmo teste, o PRÓPRIO
  `fillForm()` pode até reportar sucesso mas os campos preenchidos por
  `$set()` em Actions anteriores (ex.: um `Hidden` de controle) somem
  igual, porque nada de fato foi escrito. **Correção**: usar
  `$test->set('data.campo', $valor)` (método NATIVO do Livewire
  `Testable`, sem esse guard — vai pelo pipeline de update de verdade,
  dispara `afterStateUpdated()` normalmente) em vez de `fillForm()`,
  SEMPRE que o teste rodar via `tinker` e não via `TestCase` real. Pra
  um `RichEditor`, `$test->set('data.campo', '<p>HTML</p>')` funciona
  igual — o `StateCast` do componente converte pro JSON interno do
  TipTap corretamente pelo pipeline normal de update (confirmado por
  teste real, não presumido). Só escrever um `TestCase`/Pest de
  verdade (`ddev artisan test`) resolve `fillForm()` também, se algum
  dia for preciso testar o preenchimento em si (não só o resultado).

## Comandos e fluxo úteis

- `ddev artisan optimize:clear` — rodar ao final de qualquer tarefa que
  mexa em rotas, permissões, config ou navegação.
- `shield:generate --resource=XResource,YResource` — gera permissões
  para Resources; **não sincroniza sozinho com a role Admin**, sempre
  seguir com `$admin->givePermissionTo(...)` manual.
- `route:list` — confirmar qual versão de um Resource duplicado está
  ativa, e conferir que slugs/URLs não mudaram após um rename/Cluster.
- Rename de tabela/coluna já aplicada: sempre `Schema::rename()`/
  `renameColumn()` numa migration NOVA — nunca editar uma migration já
  rodada, nunca dropar/recriar (perde FKs). O nome da CONSTRAINT em si
  não é renomeado automaticamente pelo MySQL/MariaDB nesses casos
  (resíduo cosmético, sem efeito funcional). Se o Model renomeado tem
  logs de auditoria já gravados, ver a nota de `activity_log.subject_type`
  na "Convenção para Model novo de cadastro de negócio" acima.
- Excluir permissões órfãs em massa: `Permission::delete()` via
  Eloquent pode falhar em guard `sanctum` — usar `DELETE` SQL direto em
  `permissions`/`role_has_permissions`/`model_has_permissions` como
  alternativa, seguido de `permission:cache-reset`.
- **Nunca chamar `__()` (ou qualquer coisa que dependa de algo só
  disponível no `boot()`, como config/binding de outro pacote) dentro
  de `packageRegistered()`/`register()` de um ServiceProvider** — a
  fase `register()` de TODOS os providers termina antes da fase
  `boot()` de qualquer um; use uma Closure lida depois
  (`->label(fn () => __(...))`) ou mova para `packageBooted()`/`boot()`.
- `Panel::configureUsing()` em `packageRegistered()` aplica a callback
  a TODOS os painéis registrados — sempre guardar com `if
  ($panel->getId() !== 'admin') { return; }` quando a intenção é só o
  painel admin.
- **`FilamentAsset::register([Css::make(...), ...])` (ex.:
  `AdminPanelProvider::boot()`) NÃO serve o CSS/JS diretamente do
  caminho passado em tempo de requisição** — o `<link>`/`<script>`
  gerado sempre aponta pra uma cópia estática publicada em
  `public/css/{package}/{id}.css` (`package` default `'app'`), nunca
  pro `resource_path(...)` original (`Filament\Support\Assets\Css::
  getHref()`/`getRelativePublicPath()`, confirmado lendo o código-fonte
  do pacote). Depois de registrar (ou editar) um asset assim, **rodar
  `ddev artisan filament:assets`** (também roda automaticamente dentro
  de `filament:upgrade`, que por sua vez roda em `post-autoload-dump` —
  então um `composer install`/`update` também resolve) — sem isso o
  arquivo publicado fica ausente/desatualizado e a regra CSS
  simplesmente não é aplicada, SEM nenhum erro visível (o `<link>`
  aponta pra um arquivo que não existe ou está com conteúdo antigo).
  Achado real: `resources/css/filament/admin-input-no-spinner.css`
  (`perseu/comercial`, esconder setas de `<input type=number>`) foi
  registrado mas o publish nunca rodou — a regra simplesmente não
  tinha efeito nenhum, sem nenhum erro no console. Os arquivos
  publicados em `public/css/app/*.css` SÃO versionados no git (mesmo
  padrão já usado por `admin-topbar.css`/`admin-select-badge.css`) —
  sempre commitar o arquivo publicado junto do registro em PHP.
