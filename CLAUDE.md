# Contexto do projeto Perseu

> Para o histórico narrado de tarefas, bugs corrigidos e investigações
> já feitas (o "porquê" de uma decisão antiga), consulte
> `HISTORICO-DESENVOLVIMENTO.md`. Este arquivo mantém só a referência
> de uso frequente e o estado atual do sistema.

Este projeto é baseado no AureusERP, customizado para uma marcenaria
industrial (Perseu).

## Antes de qualquer tarefa de código, consulte:
- AUDITORIA-ESTRUTURA.md — como funciona controle de acesso, usuários,
  empresas e branding
- GUIA-CRIACAO-PLUGIN.md — passo a passo para criar novos plugins seguindo
  as convenções deste projeto
- HISTORICO-DESENVOLVIMENTO.md — contexto histórico, se precisar entender
  o porquê de uma decisão

## Convenções e decisões do projeto
- O cadastro de pessoas é um plugin próprio (`perseu/pessoas`), com
  tabelas separadas para Pessoa Física e Pessoa Jurídica (não o modelo
  de tabela única "partners" do AureusERP original).
- Uma Categoria de Pessoa pode se aplicar a PF, PJ, ou ambos (relação
  muitos-para-muitos).
- Contatos ligam uma Pessoa Física a uma Pessoa Jurídica (representante).
- Usuários de login sempre se vinculam a uma Pessoa Física.
- O plugin de Pessoas NÃO altera o comportamento automático de criação
  de Partner ao salvar um User (código do core, em
  `Webkul\Security\Models\User::boot()`). Esse Partner "técnico"
  continua existindo por baixo dos panos, sem interface própria
  visível. As tabelas `pessoas_fisicas`/`pessoas_juridicas` são
  independentes e não substituem essa lógica do core — apenas
  coexistem com ela.

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
a fonte única que alimenta o seletor de idioma da topbar
(`resources/views/filament/components/language-switcher.blade.php`),
o RTL (`Webkul\Support\Traits\HasRtlSupport`), o middleware
`App\Http\Middleware\SetLocale` e o Select de idioma em Perfil/Usuário
— **todo Model/Resource novo só precisa de arquivo de tradução em
`lang/pt_BR/` e `lang/en/` (ou `resources/lang/pt_BR|en/` de plugin),
não mais 4 idiomas.** Não recriar diretórios `lang/es`/`lang/ar` nem
adicionar `es`/`ar` de volta a `supported_locales` sem decisão
consciente nova.

## Atenção: Resources duplicados entre plugins
Alguns Resources existem em mais de um plugin (ex: CompanyResource
existe em "security" E "support" — o de "support" é o que efetivamente
serve as rotas, o de "security" tem `shouldRegisterNavigation=false`).
Antes de editar um Resource, confirme com `route:list` ou busca por
nome de classe qual versão está realmente ativa, para não editar a
versão errada.

## Distinção entre Favicon e Logo (Branding)
No BrandSettings, "favicon" representa a identidade do PRODUTO Perseu
(o software em si) e é usado em lugares que remetem ao "sistema" (ex:
página de Ajuda). "light_logo"/"dark_logo" representam a identidade da
EMPRESA CLIENTE que usa o sistema (ex: topbar). Ao adicionar imagens de
marca em novas telas, considere qual das duas identidades faz sentido
em cada contexto.

## Tema Bonsai — removido, não reinstalar
`qalainau/bonsai-theme` foi removido definitivamente do projeto
(2026-08-24) por conflitos recorrentes de espaçamento/tipografia com
`perseu/pessoas`/`perseu/comercial` (que já têm compactação própria via
`HasCompactFieldWidth`/`HasRelationManagerDividers`). Não está mais
ativo. Se algo parecido for cogitado no futuro, veja o histórico da
investigação em `HISTORICO-DESENVOLVIMENTO.md` antes de reinstalar
qualquer tema de densidade — o CSS desses temas costuma usar
`!important` agressivo em gaps de grid/flex/form, que exige
`!important` também em qualquer `style` inline nosso para não ser
silenciosamente ignorado.

## Estrutura do plugin de Pessoas (plugins/perseu/pessoas)
- Organizado com `getNavigationGroup() => NavigationGroup::Pessoas`
  (SEM Cluster — ver seção "Navegação: Cluster vs. grupo achatado"
  abaixo), com 3 itens: Categorias, Pessoas Físicas, Pessoas Jurídicas.
- Contatos NÃO é um Resource/item de menu próprio — é um Relation
  Manager dentro da tela de edição de Pessoa Jurídica (mesmo padrão do
  `BranchesRelationManager` em Company).
- Endereços também não é item de menu — é uma tabela própria
  (`enderecos`) com pivots (`pessoa_fisica_endereco`,
  `pessoa_juridica_endereco`), exibida como Relation Manager dentro de
  Pessoa Física e Pessoa Jurídica. O tipo de endereço (Residencial,
  Comercial, Cobrança, etc.) é um enum PHP (`TipoEndereco`) com valor
  inteiro, não uma tabela separada. `tipo`/`principal` são colunas do
  PIVOT, não da tabela `enderecos`.
- No Select de "tipo" do Relation Manager de Endereços, as opções são
  filtradas por contexto: Pessoa Física (Residencial, Cobrança,
  Entrega, Outro) vs. Pessoa Jurídica (Comercial, Cobrança, Entrega,
  Obra, Outro) — o enum continua com todas as opções, a tela só
  restringe visualmente.
- Os dois Relation Managers de Endereços reaproveitam o mesmo
  `form()`/`table()` via o trait
  `Perseu\Pessoas\Traits\HasEnderecoRelationManagerSchema` — a classe
  concreta só declara `$relationship`, `translationPrefix()` e
  `tipoEnderecoOptions()`.
- Itens novos de menu podem ser promovidos depois, caso surja
  necessidade real (ex: uma listagem geral de contatos).

## Utilitários compartilhados de busca externa (CEP, CNPJ)
- **CEP**: `Perseu\Pessoas\Support\ViaCepLookup::fill(Set $set, ?string
  $cep)` — consulta `https://viacep.com.br/ws/{cep}/json/` (pública,
  sem autenticação), preenche `logradouro`/`bairro`/`municipio`/`uf`.
  Classe utilitária pura, sem estado, sem depender de Model/Resource —
  chamável de qualquer formulário que tenha esses campos, dentro ou
  fora do plugin Pessoas. Usada por `HasEnderecoRelationManagerSchema`
  e pelo `createOptionForm` de Endereço em `ObraResource`.
- **CNPJ**: `Perseu\Pessoas\Support\BrasilApiCnpjLookup::fill(Set $set,
  Get $get, ?string $cnpj, string $razaoSocialField = 'razao_social')`
  — consulta `https://brasilapi.com.br/api/cnpj/v1/{cnpj}` (timeout
  8s, try/catch, `Cache::remember` 10 min), preenche razão
  social/nome fantasia/telefone/e-mail/CNAE/data de
  abertura/porte/regime tributário/situação cadastral. Usada por
  `PessoaJuridicaResource` e (via `Webkul\Support\Support\
  CompanyCnpjLookup`, que reaproveita `buscar()`/`enderecoFrom()`) por
  `Company`/`Branch`. **Regras a seguir ao estender**: nunca sobrescreve
  campo já preenchido pelo usuário (`blank($get(...))` antes de cada
  `Set`), EXCETO Situação Cadastral (sempre sobrescrita/limpa — é
  somente-leitura, reflete a Receita Federal); `Select::options(EnumClass::class)`
  casta o estado pra INSTÂNCIA do enum, então desembrulhe com
  `$v instanceof MeuEnum ? $v->value : $v` antes de comparar valor lido
  via `Get` cross-field; enums com valor "Não Informado"/0 não são
  `blank()` — compare contra o valor explícito do case, não `blank()`.
- **NCM** (`Perseu\Pessoas\Support\BrasilApiNcmLookup`) — reservado
  para uso futuro (cadastro de Produto/Material, ainda não criado). NCM
  é classificação de produto, não de Pessoa Jurídica/Company — não
  reconectar num Select de PJ/Company.

## Regra de largura de campos em formulários

Convenção de largura visual dos campos, implementada em
`Perseu\Pessoas\Traits\HasCompactFieldWidth`
(`plugins/perseu/pessoas/src/Traits/HasCompactFieldWidth.php`):
- Campos de conteúdo curto/formato conhecido (telefone, CPF, CNPJ, RG,
  CEP, DatePicker, Select/dropdown, Toggle) devem ter largura visual
  LIMITADA (não esticar para preencher a coluna inteira).
- Campos de conteúdo livre/variável (nome, e-mail, profissão,
  observações, textos longos) devem ocupar a largura normal da coluna.

Reaproveitado por qualquer Resource que `use` o trait (`perseu/pessoas`
hoje; se um plugin fora dele precisar, promova o trait para um local
compartilhado nesse momento — não antecipar).

### Layout da linha: Grid vs. `static::flexRow()`

Nunca aninhar `Grid::make()` dentro de outro `Grid::make()` — o Grid
interno ocupa a célula inteira do Grid externo como um único
componente, empurrando os componentes seguintes para fora da linha.

- **Linha só com campos compactos, ou colunas de largura igual**:
  `Grid::make(N)` com os campos diretamente dentro (`columnSpan()` em
  cada campo quando as colunas não forem iguais). Espaço vazio sobrando
  ao final é aceitável aqui.
- **Linha que mistura campos compactos com um campo de largura normal
  que deve ocupar o espaço restante**: use `static::flexRow([...])` —
  usa o componente oficial `Filament\Schemas\Components\Flex` (nunca
  `Group::make()->extraAttributes(['class' => 'flex ...'])`, que não
  funciona: `Schema::toHtml()` sempre envolve os filhos numa
  `<div class="fi-grid">` de 1 coluna antes de chegarem ao container
  pai).

```php
use Perseu\Pessoas\Traits\HasCompactFieldWidth;

class MeuResource extends Resource
{
    use HasCompactFieldWidth;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            static::flexRow([
                static::compact(
                    TextInput::make('cnpj')->mask('99.999.999/9999-99'),
                    chars: 18, // tamanho da máscara
                ),
                static::compact(
                    DatePicker::make('data_abertura'),
                    chars: 10, extraSlack: 2, // valor + ícone de calendário
                ),
                static::compactByLabel(
                    Toggle::make('ativo'), // sem .fi-input-wrp: largura vem só do label
                ),
                static::grow(
                    TextInput::make('email'), // largura normal, ocupa o espaço restante
                ),
            ]),

            // linha só com compactos, sem campo "normal" — Grid está OK aqui
            Grid::make(2)->schema([
                static::compact(
                    Select::make('regime_tributario')->options(RegimeTributario::class),
                    chars: static::maxEnumLabelChars(RegimeTributario::class),
                ),
                static::compact(TextInput::make('inscricao_estadual'), chars: 14),
            ]),
        ]);
    }
}
```

### Cálculo de largura (`static::compact()` / `static::compactByLabel()`)

A largura é calculada em caracteres esperados (`ch`, largura do "0" na
fonte atual): `largura = max(chars, comprimento do label) + folga`
(folga padrão +2ch; `extraSlack` soma folga extra para ícones internos
como o do DatePicker). Calculado automaticamente via
`$component->getLabel()` — não calcule à mão.

- `static::compact($component, chars: N, extraSlack: 0)` — campos com
  `.fi-input-wrp` (TextInput, Select, DatePicker/DateTimePicker).
  Aplica `max-width` no `.fi-input-wrp` (não no `<input>` — ver nota) e
  `->grow(false)`.
- `static::compactByLabel($component, extraSlack: 0)` — campos sem
  `.fi-input-wrp` próprio (ex.: `Toggle`). Aplica `max-width` via
  `->extraFieldWrapperAttributes()` no `.fi-fo-field` e `->grow(false)`.
- `static::maxEnumLabelChars(EnumClass::class)` — comprimento do maior
  label de um enum `HasLabel`, para Selects cuja largura deve
  acompanhar o enum.
- `static::grow($component)` — chama `->grow()` (true) explicitamente.

Ao calcular `chars`, use o tamanho real da máscara (CPF `999.999.999-99`
= 14, telefone `(99) 99999-9999` = 15); sem máscara fixa, use um valor
razoável comentado no código.

**`->extraInputAttributes()` e `->maxWidth()` NÃO funcionam para
isso** — o mecanismo correto é `->extraAttributes(['style' =>
'max-width: Nch;'])`, já usado internamente pelos helpers acima.

**Todo campo dentro de `flexRow()` deve passar por `compact()`,
`compactByLabel()` ou `grow()`** — um campo cru herda `grow()` = true
por padrão do `Flex` e vai esticar, disputando espaço com o campo
marcado por `grow()`.

### Divisores entre o formulário e os Relation Managers (páginas de Edit)

Numa página `EditRecord` com Relation Manager(s), use o trait
`Perseu\Pessoas\Traits\HasRelationManagerDividers`:

```php
use Perseu\Pessoas\Traits\HasRelationManagerDividers;

class EditPessoaJuridica extends EditRecord
{
    use HasRelationManagerDividers;
    protected static string $resource = PessoaJuridicaResource::class;
    // ...
}
```

Sobrescreve `getFormContentComponent()` (gap de `6rem` no
`Form::make(...)` — o Relation Manager renderiza FORA do `form()` do
Resource, então esse espaçamento não pode vir de dentro do
`form()`) e `content()` (insere `<hr><hr>` + um `Group` com fundo
acinzentado — mesmas classes do `Section->secondary()` do Filament —
entre form e Relation Managers). Aplicado em `EditPessoaJuridica` e
`EditPessoaFisica`; reaproveitar em qualquer página `Edit{Xxx}` futura
que ganhe seu primeiro Relation Manager.

## Flags de sistema em Categoria de Pessoa — escopo deliberadamente limitado

`categorias_pessoa.e_cliente`/`e_fornecedor` são, deliberadamente, as
ÚNICAS flags de sistema fixas nessa tabela — porque são os dois papéis
que módulos do sistema precisam filtrar de forma confiável (Comercial
precisa de `e_cliente`, futuro módulo de Compras vai precisar de
`e_fornecedor`). **Qualquer papel novo (Parceiro, Representante,
Transportadora...) deve virar uma Categoria de Pessoa comum (tag
livre), NÃO um novo campo `e_algumaCoisa`.** Se alguém pedir "adicionar
uma flag para categoria X", pergunte primeiro se existe um módulo que
vai *filtrar* programaticamente por essa categoria — se não existir
ainda, a Categoria comum resolve sem crescer o schema.

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

Sempre que criar um novo Model de cadastro de negócio (Obra, Pessoa
Física/Jurídica, Referência de Preços, etc.), siga desde o primeiro
commit:

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
   "Auditoria e Lixeira — arquitetura atual" abaixo).
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
   Central até ser adicionado.

Convenção de nomenclatura de campos percentuais/monetários (visto em
Referência de Preços): monetário `decimal(10,2)` com `->prefix('R$')`,
percentual `decimal(5,2)` com `->suffix('%')`. Se uma tabela crescer
muito em colunas, considere `->toggleable(isToggledHiddenByDefault:
true)` nas colunas menos usadas do dia a dia (mantém tudo editável no
form, só não some com a listagem).

## Auditoria e Lixeira — arquitetura atual

- **Não há aba "Atividades" em registros individuais.** A ÚNICA tela
  de auditoria é a Central: Configurações → Auditoria
  (`Perseu\Auditoria\Filament\Resources\AuditoriaResource`, clusterizado
  em `Webkul\Support\Filament\Clusters\Settings`). Permissão:
  `view_any_auditoria_auditoria`/`view_auditoria_auditoria`.
- `Perseu\Auditoria\Support\SubjectTypeCatalog` é o mapeamento único
  FQCN → rótulo/módulo/referência amigável/escopo de busca para os 9
  Models hoje auditados (Obra, TipoObra, SituacaoObra, ReferenciaPreco
  — módulo Comercial; PessoaFisica, PessoaJuridica, CategoriaPessoa,
  Setor, Endereco, Contato — módulo Pessoas). O projeto não define
  `Relation::morphMap()`, então `activity_log.subject_type` guarda o
  FQCN completo.
- A caixa "Pesquisar" padrão da tabela de Auditoria busca ao mesmo
  tempo pelo REGISTRO (via `SubjectTypeCatalog::applyBusca()`, coluna
  por coluna conforme o tipo — ver tabela abaixo) e pelo EVENTO
  (rótulo traduzido, ex. "excluído definitivamente"), via
  `->searchable(query: ...)` nas colunas correspondentes, que o
  Filament já soma com `OR` automaticamente. Não há filtro de busca
  textual separado (foi removido — só a caixa padrão).

  | Cadastro | Coluna(s) pesquisada(s) |
  |---|---|
  | Obra | `descricao`, `numero_obra` |
  | Tipo de Obra, Situação de Obra, Categoria de Pessoa, Setor | `descricao` |
  | Pessoa Física | `nome` |
  | Pessoa Jurídica | `razao_social`, `nome_fantasia`, `cnpj` |
  | Endereço | `logradouro`, `bairro`, `municipio` |
  | Contato | `cargo` |

  A busca por texto SÓ cobre esses 9 Models — não busca nome de
  usuário (existe filtro dedicado "Usuário", coluna `causer_id`) nem
  nome da empresa dona do sistema (Branding/`Company`, que não é um
  Model auditado).
- Filtros disponíveis: Módulo, Cadastro (`subject_type`), Usuário
  (`causer_id`), Eventos (multi-seleção, todos marcados por padrão —
  `created/updated/deleted/forceDeleted/restored`), Período (default:
  último ano em "Criado a partir de" — usa formato `Y-m-d`, NUNCA
  `d/m/Y`, no `->default()` do `DatePicker`; o parser interno do
  pacote quebra com dias > 12 em `d/m/Y`).
- **`forceDeleted` precisa de listener próprio** — o Spatie
  Activitylog não grava esse evento nativamente
  (`LogsActivity::eventsToBeRecorded()` só cobre
  `created/updated/deleted/restored`). `LogsBusinessActivity` já
  resolve isso (ver convenção acima) — não reimplementar.
- **Decisão de produto**: não há exclusão automática de logs antigos —
  `activity_log` é mantida para sempre (tabela leve, valor de
  auditoria/fiscal a longo prazo).
- O botão "Editar" no card "Mudanças" do detalhe de um log
  (`rmsramos/activitylog`) está escondido de propósito
  (`ActivitylogPlugin::isResourceActionHidden(true)`) — link pra editar
  o registro original, não funciona com Resources clusterizados
  (nossos Resources auditados são todos clusterizados) e contraria a
  imutabilidade da auditoria de qualquer forma.
- **Lixeira Central** (Configurações → Lixeira,
  `Perseu\Auditoria\Filament\Pages\Lixeira`) agrega os Excluídos de
  TODOS os cadastros com `SoftDeletes` numa tabela só (hoje: Obra,
  Pessoa Física, Pessoa Jurídica, Referência de Preços —
  `Perseu\Auditoria\Support\TrashCatalog::models()` é a lista oficial;
  Categoria/Setor/Tipo/Situação NÃO têm `SoftDeletes`, então não
  aparecem ali). Usa `Filament\Tables\Table\Concerns\HasRecords::records(Closure)`
  (mecanismo oficial do Filament v4 para tabela sem Eloquent Builder —
  cada linha é um `Filament\Support\ArrayRecord`, não uma instância de
  Model). Restaurar/Excluir Permanentemente chamam o Model real
  (`$model::onlyTrashed()->find($id)->restore()`/`forceDelete()`), o
  que já dispara a cascata de `CascadesRelatedDataOnForceDelete`
  automaticamente. Sem Resource/Policy própria — cada linha verifica a
  Policy do Model real (`Gate::allows('restore'|'forceDelete',
  $modelReal)`).
- **Pendente**: o filtro "Excluídos"/`RestoreAction`/`ForceDeleteAction`
  em cada Resource individual (Obra/PF/PJ) continuam ativos — só devem
  ser removidos depois que o usuário confirmar que a Lixeira Central
  substitui bem esse acesso. Não remover sem essa confirmação.
- **Não implementado (levantado, ver `HISTORICO-DESENVOLVIMENTO.md`)**:
  atalho "Ir para a Lixeira deste cadastro" a partir de um log
  `deleted`, e qualquer ação de restaurar/reverter DIRETAMENTE da tela
  de Auditoria. Se algum dia implementar a segunda, exigir
  `->requiresConfirmation()` citando o registro afetado, o que a ação
  vai mudar, e um alerta sobre a cascata de `forceDeleting` não trazer
  Endereços/Contatos de volta numa restauração.

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
"Configurações", "Obras", "Referências" — ver
`Perseu\Comercial\Filament\Clusters\{Obras,Referencias}`). Nesse caso:
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
traduções nos 4 idiomas se ainda não existir um adequado) — nunca
deixar um Cluster/Resource de topo sem isso, ou cai num grupo anônimo
compartilhado, escondido do dropdown da topbar.

## Nomenclatura atual do sistema (estado vigente — pode confundir se olhar código antigo)

| Conceito | Nome ATUAL | Namespace/plugin |
|---|---|---|
| Entidade interna de gestão de processos (Kanban/tarefas internas) | **Processo** (antes "Project"/"Projeto") | `Webkul\Project\Models\Processo` — plugin core `webkul/projects` |
| Cadastro de negócio de marcenaria (obras da F.A. Marcenaria) | **Obra** (antes "Projeto") — rename para **"Projeto"** é decisão futura, ainda PENDENTE | `Perseu\Comercial\Models\Obra` — plugin `perseu/comercial` |

Não confundir os dois: são namespaces/tabelas totalmente independentes,
sem FK entre si. A relação entre eles ainda não foi desenhada
tecnicamente (ver `CONCEITO-OBRA-PROPOSTA-PROJETO.md`).

`obras.revisao` existe (`unsignedInteger`, `default(0)`, sem lógica de
autoincremento, exibido como Placeholder somente-leitura zero-padded
em 2 dígitos) — fora do `$fillable`, sem input editável em lugar
nenhum. A ideia conceitual atual é que "Obra + Revisão" já representa
o que seria uma "Proposta", sem Model/Resource separado por enquanto.

**Pendência conhecida**: a camada de API REST
(`Http/Controllers/API/V1/*`, `Http/Resources/V1/*`,
`Http/Requests/*`) e a suíte `tests/` do plugin `webkul/projects`
continuam usando nomenclatura "Project"/"project" (decisão consciente,
não omissão — nada no Perseu consome essa API hoje). Se essa API
passar a ser consumida externamente, revisitar como tarefa própria de
rename (o campo `processo_id` no payload já é um breaking change em
relação ao nome antigo `project_id`).

## Roadmap / Pendências em aberto

- **PDF de Proposta**: ao final do fluxo comercial, gerar PDF no
  estilo do documento real da F.A. Marcenaria (cabeçalho obra/
  contratante/contratada, itens/serviços com valores, condições de
  pagamento, cláusulas, assinaturas). Avaliar `barryvdh/laravel-dompdf`
  (já no `composer.json`). Cluster "Referências" (Preços) já existe
  como base de dados para isso; Propostas/Contratos/Termos de
  Entrega/Garantia ainda não têm Model/Resource.
- **Vínculo Obra ↔ Processo** (plugin de Tarefas): decidir só depois
  do plugin de Tarefas estar estável em uso real — Opção A (Processo
  espelho por Obra, exige sincronização) vs. Opção B (Processo único
  "guarda-chuva", referência só em texto). Definir no momento de
  implementar a automação de criação de tarefas (ex.: disparo por
  mudança de Situação da Obra).
- **Rename Obra → Projeto**: decisão de nomenclatura final tomada
  (`CONCEITO-OBRA-PROPOSTA-PROJETO.md`), passo 1 (Project → Processo no
  plugin core) já feito; passo 2 (Obra → Projeto em
  `perseu/comercial`) ainda não iniciado.
- **Company/Branch e NF-e**: `bairro`/`numero` de Company não são
  sincronizados para o Partner vinculado hoje (nenhum consumidor
  precisa ainda) — estender `Company::boot()` quando a emissão de NF-e
  for implementada. O registro real de Company tem `tax_id` de
  teste/placeholder — precisa do CNPJ real da F.A. Marcenaria na
  próxima edição (a validação `CnpjValido` vai bloquear até lá).
- **Restaurar a partir da Auditoria**: levantado, não implementado —
  ver "Auditoria e Lixeira" acima e `HISTORICO-DESENVOLVIMENTO.md`.
- **Remover Lixeira/TrashedFilter individual de Obra/PF/PJ**: só
  depois de confirmação explícita do usuário de que a Lixeira Central
  já basta.

## Limitações conhecidas

- Categoria de Pessoa, Setor, Situação de Obra e Tipo de Obra usam o
  padrão `ManageRecords` do Filament (uma página só, modal) — sem
  `SoftDeletes`, sem Lixeira, sem aba de Atividades própria (mas
  continuam auditados pela Central). Não expandir isso preventivamente
  — só se/quando virar necessidade real, como decisão própria.
- `plugins/webkul/sales/resources/views/sales/quotation.blade.php`
  referencia `$record->company->address`, que nunca existiu como
  relação/accessor em `Company` (sempre `null`) — bug pré-existente do
  AureusERP original, não corrigido (fora do escopo dos plugins
  `perseu/*`).

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
  (resíduo cosmético, sem efeito funcional).
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
