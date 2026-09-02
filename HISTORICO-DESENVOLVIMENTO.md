# Histórico de Desenvolvimento — Perseu

Este arquivo reúne o histórico narrado de investigações, bugs
corrigidos, tentativas revertidas e decisões já tomadas ao longo das
sessões de trabalho neste projeto. Foi extraído do `CLAUDE.md`
(que mantém só a referência de uso frequente) em 2026-09-01, quando o
`CLAUDE.md` ultrapassou o limite de contexto confiável do Claude Code.

Use este arquivo quando precisar entender o **porquê** de uma decisão
antiga, uma investigação já feita, ou o caminho que um bug percorreu
até ser corrigido. Para saber o estado ATUAL do sistema e as
convenções vigentes, consulte `CLAUDE.md`.

As seções abaixo seguem a ordem cronológica original.

---

## Tema Bonsai (qalainau/bonsai-theme) — investigação completa (removido em 2026-08-24)

**O pacote `qalainau/bonsai-theme` foi removido do projeto em
2026-08-24** (`composer remove qalainau/bonsai-theme`, plugin retirado
de `AdminPanelProvider`, CSS de correção de conflitos específicos dele
apagados — `admin-select.css`, `admin-entry-label.css`,
`admin-entry-content.css`, `admin-radio-gap.css`). Motivo: conflitos
recorrentes de espaçamento/tipografia com qualquer tela ou plugin fora
dos nossos próprios módulos (`perseu/pessoas`, `perseu/comercial`), que
já têm sistema de compactação independente (`HasCompactFieldWidth`,
`HasRelationManagerDividers`) e não dependem do Bonsai para densidade
visual — o custo de investigar e corrigir cada novo conflito superou o
benefício do tema. Não está mais ativo no projeto. O restante desta
seção é registro histórico da investigação (útil se algo parecido for
cogitado no futuro).

O pacote estava instalado e ativo neste projeto até a remoção acima
(registrado como plugin Filament em `AdminPanelProvider`), para deixar
o painel mais denso. O CSS dele
(`vendor/qalainau/bonsai-theme/resources/css/bonsai.css`) zera
agressivamente TODOS os gaps de grid/flex/form do Filament usando
`!important` — por exemplo `.fi-sc-form, .fi-sc-form.fi-dense { gap: 0
!important; }` e `.fi-sc-flex, .fi-sc-flex.fi-dense { gap: 0
!important; }` (junto de várias outras regras `.fi-*` com `!important`
para padding/font-size/etc., é o "modo denso" do tema).

**Qualquer `style` inline customizado que tente aumentar um `gap`
nessas classes precisa do próprio `!important`, ou é silenciosamente
ignorado** — um `!important` de stylesheet de autor sempre vence um
`style=""` inline sem `!important`, não importa a especificidade.
Isso já causou espaço "sumindo" sem explicação aparente em duas
tarefas seguidas (`HasCompactFieldWidth::flexRow()` e
`HasRelationManagerDividers::getFormContentComponent()`, ambos
corrigidos adicionando `!important` ao valor do `gap` no `style`) antes
de a causa real ser encontrada — o pacote não tem nenhuma opção de
configuração para excluir elementos específicos da compactação (a
classe do plugin só registra um CSS estático, nada mais).

Ao adicionar qualquer novo `->extraAttributes(['style' => '...'])` que
mexa com `gap` (ou outra propriedade que o Bonsai também força — vale
conferir `bonsai.css` antes), inclua `!important` desde o início e não
assuma que o valor "só não teve efeito visual por outro motivo" sem
checar esse arquivo primeiro. `max-width`/`margin-right` usados por
`HasCompactFieldWidth::compact()`/`compactByLabel()`/`flexRow()` não
são afetados (o Bonsai não força essas propriedades nos seletores que
usamos) — o problema era especificamente `gap`.

---

## Rename "Projeto" → "Obra" no plugin `perseu/comercial` (2026-08-28)

Decisão de nomenclatura citada como pendente desde o handoff original
do projeto foi resolvida: o cadastro de negócio chamado "Projeto"
virou "Obra", que é a função real desse cadastro (obras de marcenaria
da F.A. Marcenaria) — não só o texto da tela, um rename completo
(Model, tabela, colunas, namespace, rotas, permissões, traduções).
Decisão consciente durante a implementação: o rename cobriu também
`SituacaoProjeto`→`SituacaoObra` e `TipoProjeto`→`TipoObra` (a tarefa
original só citava o cadastro principal explicitamente, mas deixar
"Situação do Projeto"/"Tipo do Projeto" na tela depois do cadastro
principal virar "Obra" seria inconsistente — confirmado com o usuário
antes de ampliar o escopo).

**Continua valendo, sem mudança**: o plugin de Tarefas
(`webkul/projects`) tem sua própria entidade "Project"/"Task" em
inglês, totalmente separada — não foi tocado nesta tarefa (ver
`ESTUDO-PLUGIN-PROJECTS.md`) e não deve ser confundido com este
rename.

### O que mudou

| Antes | Depois |
|---|---|
| `Perseu\Comercial\Models\Projeto` | `Perseu\Comercial\Models\Obra` |
| `Perseu\Comercial\Models\SituacaoProjeto` | `Perseu\Comercial\Models\SituacaoObra` |
| `Perseu\Comercial\Models\TipoProjeto` | `Perseu\Comercial\Models\TipoObra` |
| `Perseu\Comercial\Services\GeradorNumeroProjeto` | `Perseu\Comercial\Services\GeradorNumeroObra` |
| `ProjetoResource`/`SituacaoProjetoResource`/`TipoProjetoResource` | `ObraResource`/`SituacaoObraResource`/`TipoObraResource` (+ Pages, Policies) |
| tabela `projetos` | tabela `obras` |
| tabela `situacoes_projeto` | tabela `situacoes_obra` |
| tabela `tipos_projeto` | tabela `tipos_obra` |
| tabela `projeto_numero_sequencias` | tabela `obra_numero_sequencias` |
| tabela `projeto_situacao` | tabela `obra_situacao` |
| coluna `projetos.tipo_projeto_id` | `obras.tipo_obra_id` |
| coluna `projetos.numero_projeto` | `obras.numero_obra` |
| coluna `obra_numero_sequencias.tipo_projeto_id` | `tipo_obra_id` |
| colunas `projeto_situacao.projeto_id`/`situacao_projeto_id` | `obra_situacao.obra_id`/`situacao_obra_id` |
| slug `comercial/projetos` | slug `comercial/obras` |

**Numeração automática (prefixo AAT####) não mudou** — o código de
`GeradorNumeroObra::gerar()` é idêntico ao antigo
`GeradorNumeroProjeto::gerar()`, só o nome da classe/tabela mudou; o
prefixo letra vem de `TipoObra->codigo` (mesmo mecanismo de sempre,
testado gerando uma Obra nova depois do rename: `26T0001` — ano +
código do tipo + sequencial, igual a antes).

### Migration de rename — `Schema::rename()`/`renameColumn()`, nunca drop/recriar

`2026_08_28_120000_rename_projeto_to_obra.php`: tabelas primeiro
(`Schema::rename()`), colunas depois (`Schema::table(...)
->renameColumn()`, já com o NOME NOVO da tabela). FKs entre as tabelas
renomeadas continuam funcionando sem precisar dropar/recriar —
MySQL/MariaDB atualiza a constraint automaticamente para apontar pro
novo nome de tabela/coluna quando `RENAME TABLE`/`RENAME COLUMN` são
usados (confirmado consultando `information_schema.KEY_COLUMN_USAGE`
depois de rodar a migration: a FK de `obras.tipo_obra_id` já apontava
pra `tipos_obra.id` corretamente). Único resíduo cosmético: os NOMES
das constraints em si continuam com o prefixo antigo (ex.:
`projetos_tipo_projeto_id_foreign`) — MySQL/MariaDB não renomeia o
nome da constraint automaticamente nesse cenário, só o que ela
referencia. Sem efeito funcional; não foi corrigido por ser cosmético
e exigir DROP/ADD CONSTRAINT (mais invasivo que o benefício
justifica).

A migration antiga que criava a tabela (`..._create_projetos_table`)
não foi editada — já tinha rodado; reverter/renomear uma migration já
aplicada é sempre uma migration nova.

### Permissões Shield — geradas de novo, antigas removidas (não deixadas soltas)

`shield:generate --resource=ObraResource,SituacaoObraResource,TipoObraResource`
gerou as novas chaves (`view_any_comercial_obra`,
`view_any_comercial_situacao::obra`, `view_any_comercial_tipo::obra`,
etc.). `shield:generate` sozinho não sincroniza com a role Admin
(diferente do fluxo completo de `{plugin}:install`, que faz isso como
parte do processo) — precisou de um `$admin->givePermissionTo(...)`
manual logo em seguida. As 22 permissões antigas (`_projeto`,
`_situacao::projeto`, `_tipo::projeto`, guards `web` e `sanctum`, 44
linhas no total) foram apagadas da tabela `permissions` — não
deixadas como lixo. `Permission::delete()` via Eloquent deu erro
("Class name must be a valid object or a string") especificamente nas
linhas de guard `sanctum` (funcionou normalmente pras de guard `web`)
— causa não investigada a fundo (aparenta ser um hook do próprio
Shield relacionado a esse guard), contornado apagando as linhas
restantes via SQL direto (`DELETE FROM permissions WHERE name LIKE
'%projeto%'` + limpeza correspondente em `role_has_permissions`) em
vez do Eloquent.

### Auditoria/Lixeira sobreviveram ao rename sem reconfiguração

`LogsBusinessActivity` e `ActivitylogRelationManager` continuaram
funcionando normalmente só por estarem presentes em `Obra`/
`ObraResource` com os nomes novos — nada precisou ser reconfigurado
"do zero", confirmado criando uma Obra de teste, editando, e
conferindo o log de atividade (`causer`/`changes` corretos) e o ciclo
completo de Lixeira (soft-delete → aparece no `TrashedFilter` →
`Restaurar` → `Excluir Permanentemente`, com o log de atividade da
própria exclusão continuando em `activity_log`, tabela separada, mesmo
depois do registro sumir de `obras`).

---

## Navegação de módulos com múltiplos itens irmãos: investigação completa (2026-08-24)

Esta seção documenta a investigação completa (duas tentativas, a
primeira incorreta) de como fazer Categorias/Pessoas Físicas/Pessoas
Jurídicas (grupo "Pessoas") e Projetos/Situações/Tipos de Projeto
(grupo "Comercial") aparecerem juntos DENTRO da topbar colorida do
painel, na mesma linha do nome do módulo — igual a "Projetos" (plugin
`webkul/projects`) e "Estoque" (`webkul/inventories`). A regra final
resultante desta investigação está em `CLAUDE.md`.

### Tentativa 1 (INCORRETA, revertida): `SubNavigationPosition::Top`

A primeira correção aplicada (`PessoasCluster`/`ComercialCluster`
declarando `protected static ?SubNavigationPosition
$subNavigationPosition = SubNavigationPosition::Top;`) produzia algo
DIFERENTE do pedido: abas "em pílula" (`x-filament-panels::page.sub-navigation.tabs`)
renderizadas ABAIXO do cabeçalho da página, como um bloco separado —
não itens dentro da própria topbar. Essa propriedade controla a
sub-navegação de página (`Filament\Pages\Concerns\HasSubNavigation`,
só existe quando a página pertence a um Cluster via `$cluster =
XxxCluster::class`), que é um mecanismo TOTALMENTE INDEPENDENTE da
topbar principal do painel. Essa tentativa foi revertida por completo.

### Mecanismo correto: dropdown da topbar principal, agrupado por `NavigationGroup`

O painel Admin tem `->topNavigation()` habilitado
(`app/Providers/Filament/AdminPanelProvider.php`) — com isso,
`Filament::getNavigation()`
(`vendor/filament/filament/resources/views/livewire/topbar.blade.php`)
itera todos os `NavigationGroup`s registrados no painel e, para cada
grupo com label (nosso painel sempre dá label a todos via
`->navigationGroups(...)` em `AdminPanelProvider`, um por caso do enum
`Webkul\Support\Enums\NavigationGroup`), renderiza um
`<x-filament::dropdown>` na topbar com o nome do grupo, contendo TODOS
os itens de navegação (`NavigationItem`) que declaram esse mesmo
`getNavigationGroup()`.

Isso é automático e não exige nenhuma configuração além de múltiplos
Resources/Pages/Clusters de nível superior declararem o MESMO
`getNavigationGroup()` — não há necessidade de um Cluster
"guarda-chuva" para isso. Confirmado renderizando o loop real do
`topbar.blade.php` via `view()->render()` em tinker (autenticado com
`Auth::guard('web')->login($user)` + `Filament::setCurrentPanel(...)`):
cada `NavigationGroup` sempre vira um dropdown, e a lista de itens
dentro dele é simplesmente `$group->getItems()` — nenhuma lógica
especial correlaciona isso a "estar dentro de um Cluster".

### A causa raiz real do menu vertical anterior

`Filament\Resources\Resource\Concerns\HasNavigation::registerNavigationItems()`
tem esta guarda logo no início:
```php
if (filled(static::getCluster())) {
    return; // Resource clusterizado NÃO se registra sozinho na nav principal
}
```
Ou seja: um Resource com `$cluster = XxxCluster::class` NUNCA aparece
como item independente na navegação principal (nem no dropdown da
topbar) — só o próprio Cluster aparece, como item único. Era
exatamente isso que fazia o dropdown "Pessoas" ter só 1 item (o
Cluster "Pessoas"), enquanto "Projetos" tinha 4 (Projetos, Tarefas,
Configurações, Configurações) — porque nenhum Resource do plugin
`webkul/projects` é clusterizado sob um "Project" Cluster (esse
Cluster não existe) — `ProjectResource`/`TaskResource` declaram
`getNavigationGroup() => NavigationGroup::Project` DIRETAMENTE, sem
`$cluster`, e o Cluster `Configurations` (que agrupa Tag/Milestone/etc.
como sub-hierarquia própria) TAMBÉM declara
`getNavigationGroup() => NavigationGroup::Project` diretamente — os
três compartilham o grupo como itens de nível superior "achatados",
sem nenhum deles ser filho do outro. Confirmado o MESMO padrão em
`webkul/inventories`: `Operations`, `Products`, `Configurations`,
`Reporting`, `PluginSettings` (5 Clusters) e a Page `Overview` TODOS
declaram `getNavigationGroup() => NavigationGroup::Inventory`
diretamente — não existe um Cluster "Inventory" que os envolva.

### Correção aplicada

`PessoasCluster` e `ComercialCluster` foram removidos (arquivos
`plugins/perseu/pessoas/src/Filament/Clusters/PessoasCluster.php` e
`plugins/perseu/comercial/src/Filament/Clusters/ComercialCluster.php`
apagados, junto dos lang files dedicados a eles em
`resources/lang/{pt_BR,en}/clusters/{pessoas,comercial}.php`, que só
continham o label do Cluster). Os seis Resources deixaram de ser
clusterizados: `CategoriaPessoaResource`, `PessoaFisicaResource`,
`PessoaJuridicaResource` (plugin Pessoas) e `ProjetoResource`,
`SituacaoProjetoResource`, `TipoProjetoResource` (plugin Comercial),
removendo `protected static ?string $cluster = PessoasCluster::class;`
(ou `ComercialCluster::class`) de cada um, e adicionando diretamente
`getNavigationGroup()` (mesmo padrão de `ProjectResource`/
`TaskResource`/`Configurations` em `webkul/projects` e dos 5 Clusters
+ `Overview` em `webkul/inventories`).

Como o slug de um Resource clusterizado ganha o prefixo do Cluster
automaticamente (`Cluster::prependClusterSlug()`), e isso deixa de
existir ao remover `$cluster`, cada Resource passou a declarar o slug
completo manualmente — mesma técnica já usada por
`ProjectResource::$slug = 'project/projects'` — para preservar as URLs
exatamente como eram antes (confirmado via `route:list` depois da
mudança, nenhuma URL mudou): `CategoriaPessoaResource::$slug =
'pessoas/categoria-pessoas'`, `PessoaFisicaResource::$slug =
'pessoas/pessoas-fisicas'`, `PessoaJuridicaResource::$slug =
'pessoas/pessoas-juridicas'`, `ProjetoResource::$slug =
'comercial/projetos'`, `SituacaoProjetoResource::$slug =
'comercial/situacao-projetos'`, `TipoProjetoResource::$slug =
'comercial/tipo-projetos'`.

Os dois `config/filament-shield.php` (`perseu/pessoas` e
`perseu/comercial`) tinham uma seção `'pages' => ['exclude' =>
[PessoasCluster::class]]` (ou `ComercialCluster::class`) — necessária
antes porque o Cluster "puro" (sem nenhuma Page autônoma apontando
para ele) escapava da heurística de auto-exclusão do Shield e viraria
um toggle de permissão morto na tela de Funções. Com o Cluster
removido, essa seção inteira foi apagada.

Os diretórios `Filament/Clusters/Pessoas/Resources/*` e
`Filament/Clusters/Comercial/Resources/*` foram MANTIDOS como estão
(não foi feito rename de pasta/namespace) — Filament não usa convenção
de pasta para resolver Cluster, só a propriedade `$cluster` (ver
`Filament\Resources\Resource\Concerns\BelongsToCluster::getCluster()`,
puramente `return static::$cluster;`), então o nome da pasta
"Clusters/Pessoas"/"Clusters/Comercial" hoje é só um artefato
cosmético do histórico do código, sem efeito funcional.

Verificado após a correção: `route:list` (todas as URLs preservadas),
renderização real do loop do `topbar.blade.php` em tinker (dropdown
"Pessoas" passou a ter 3 itens, "Comercial" passou a ter 3 itens, no
mesmo formato de "Projetos"), e `getCachedSubNavigation()` das páginas
de listagem reais retornando 0 grupos e `getCluster()` retornando
`null` (confirmando que o menu lateral/abas em pílula da tentativa 1
desapareceu por completo).

---

## Integração BrasilAPI em Pessoa Jurídica: implementação completa (2026-08-27, ampliada em 2026-08-28)

`PessoaJuridicaResource::form()` busca automaticamente os dados
cadastrais da empresa na BrasilAPI (`GET
https://brasilapi.com.br/api/cnpj/v1/{cnpj}`, pública, sem
autenticação, fonte minhaReceita/Receita Federal) assim que o campo
CNPJ perde o foco com 14 dígitos válidos — mesmo padrão de UX de
`ViaCepLookup` (usado pela busca de CEP nos Relation Managers de
Endereços): `->live(onBlur: true)` + `->afterStateUpdated()`.

- `Perseu\Pessoas\Support\BrasilApiCnpjLookup::fill(Set $set, Get $get,
  ?string $cnpj)` faz a chamada (timeout de 8s, try/catch, `Cache::remember`
  por 10 min — uma resposta `null`, seja por 404 ou erro de rede, não
  fica em cache de fato, já que `Cache::get` não distingue "chave
  ausente" de "valor null", então uma falha temporária não é mascarada
  numa nova tentativa) e usa `Set` para preencher `razao_social`,
  `nome_fantasia`, `telefone` (`ddd_telefone_1`, com fallback para
  `ddd_telefone_2` se o principal vier vazio — `ddd_fax` nunca é usado),
  `email`, `cnae`+`cnae_descricao` (código formatado a partir de
  `cnae_fiscal`, inteiro puro na resposta da API, para o padrão mascarado
  `9999-9/99` já usado pelo campo; descrição de `cnae_fiscal_descricao`
  guardada à parte para exibir como `->helperText()` do campo `cnae`),
  `data_abertura`, `porte`+`descricao_porte` e, quando possível inferir
  com segurança, `regime_tributario`.
- **`porte`/`descricao_porte`: nomes INVERTIDOS na resposta real da API**
  em relação ao padrão `cnae_fiscal`/`cnae_fiscal_descricao` que o nome
  sugeriria — confirmado com uma chamada real antes de mapear. A API
  devolve `porte` como TEXTO (`"DEMAIS"`, `"MICRO EMPRESA"`...) e
  `codigo_porte` como o código numérico — o oposto do que o nome do
  campo sugere. `BrasilApiCnpjLookup` mapeia `codigo_porte` → nossa
  coluna `porte` (o código) e o `porte` da API → nossa coluna
  `descricao_porte` (o texto), documentado inline no código para não
  confundir quem for reler isso depois.
- **Situação Cadastral é a exceção à regra de "só preenche vazio"** — ao
  contrário de todos os outros campos (razão social, telefone, CNAE,
  porte, regime tributário), `situacao_cadastral`/
  `descricao_situacao_cadastral` são sempre sobrescritos a cada busca
  bem-sucedida, e limpos (`null`) quando o CNPJ é inválido/não
  encontrado. Faz sentido porque é um campo 100% somente leitura (nunca
  digitado pelo usuário, só reflete a Receita Federal) — não haveria
  "valor manual do usuário" para proteger, e manter um valor de uma busca
  antiga (de um CNPJ diferente do atual) seria enganoso.
- Demais campos seguem a regra de sempre: nunca sobrescreve um campo já
  preenchido — cada `Set` é precedido por um `blank($get(...))`. Não
  existe no Filament uma forma simples de "perguntar antes de
  sobrescrever" sem interromper o fluxo automático; "só preenche vazio"
  foi a opção escolhida por ser a mais segura sem quebrar a automação. O
  mesmo `form()` é usado por `CreatePessoaJuridica` e
  `EditPessoaJuridica`, então a busca funciona igual nos dois casos —
  trocar o CNPJ depois, em edição, dispara a mesma busca de novo.
- Se a API retornar 404 (CNPJ inexistente) ou falhar/der timeout, o
  campo CNPJ mostra um aviso em vermelho via `->hint(fn (Get $get) =>
  $get('cnpj_lookup_erro'))->hintColor('danger')` — `hint()`/
  `hintColor()` (`Filament\Forms\Components\Concerns\HasHint`) é o
  mecanismo nativo do Filament para texto reativo colorido ao lado do
  label. `cnpj_lookup_erro` é uma chave de estado "solta" (sem
  `Hidden::make()` declarando o campo) — `Set`/`Get` do Filament operam
  sobre o array de dados do componente Livewire por dot-path, então
  escrever/ler uma chave nunca declarada como componente funciona
  normalmente. O formulário nunca fica bloqueado nesse caso.
- **`Select::options(EnumClass::class)` casta o estado do campo para a
  INSTÂNCIA do enum, não o valor cru** — pegou o código de desprevenido
  uma vez: `HasOptions::options()` chama `->enum($options)` internamente
  quando recebe uma enum class, então `$get('regime_tributario')` dentro
  de `BrasilApiCnpjLookup::fill()` retorna um `RegimeTributario` (objeto),
  não um `int`. Um `(int) $get(...)` direto lança "Object... could not be
  converted to int". Sempre desembrulhar com
  `$valor instanceof MeuEnum ? $valor->value : $valor` antes de comparar/
  castar um valor de Select-com-enum lido via `Get` a partir de OUTRO
  campo (dentro do próprio field a Livewire/Filament já lida com isso
  transparentemente — o problema só aparece ao ler via `Get` cross-field).
- **Regime Tributário é um Select com opção "Não Informado" como padrão**
  (`RegimeTributario::NaoInformado = 0`, `->default(0)`) — não uma
  ausência/`null`. Como `blank(0) === false` no Laravel, a lógica de
  "só preenche automaticamente se ainda não escolhido" não pode usar
  `blank()` aqui; compara o valor desembrulhado contra
  `RegimeTributario::NaoInformado->value` explicitamente. Preenchimento
  automático: `opcao_pelo_mei` verdadeiro → MEI; senão
  `opcao_pelo_simples` verdadeiro → Simples Nacional; senão fica em "Não
  Informado" (Lucro Presumido vs. Lucro Real não são distinguíveis pela
  resposta pública da API — usuário escolhe manualmente).
- **Indicador de Contribuinte do ICMS** (`IndicadorContribuinteIcms`:
  Contribuinte/Isento/Não Contribuinte) é só cadastro por enquanto —
  corresponde ao futuro `indIEDest` da NF-e, mas sem nenhuma lógica de
  emissão associada ainda. Nunca preenchido automaticamente (não existe
  esse dado no retorno do CNPJ) e sem valor padrão pré-selecionado — é
  sempre uma escolha manual do usuário. Indicador de Consumidor Final foi
  avaliado e não foi implementado — decidido que pertence ao momento
  da emissão/compra, não ao cadastro da pessoa; revisitar quando o módulo
  de NF-e for implementado.
- **Endereço é criado automaticamente no `Create`, sem NENHUM campo de
  endereço na ficha principal** — o formulário de Pessoa Jurídica NUNCA
  ganhou (e não deve ganhar) campos próprios de `cep`/`logradouro`/
  `numero`/etc.; endereço continua sendo só a relação `enderecos`,
  gerenciada pelo `EnderecosRelationManager` já registrado em
  `PessoaJuridicaResource::getRelations()` (mesmo componente que
  `PessoaFisicaResource` usa — CRUD completo, com busca de CEP via
  `ViaCepLookup`, que só fica disponível como aba depois que o registro
  é salvo, comportamento nativo do Filament para RelationManagers).
  **Uma primeira versão desta correção (2026-08-27/28) errou isso**:
  adicionou um bloco de campos de endereço "soltos"
  (`->dehydrated(false)`) direto na ficha principal, visível mesmo antes
  de salvar — corrigido removendo esse bloco por completo (nenhum campo
  de endereço na ficha, nunca).
  `CreatePessoaJuridica::afterCreate()` NÃO lê nada do formulário para
  montar o Endereço — chama
  `BrasilApiCnpjLookup::buscar($this->record->cnpj)` de novo (o
  `Cache::remember` de 10 min da própria classe normalmente reaproveita
  a mesma resposta já obtida quando o CNPJ foi digitado, sem nova
  chamada de rede) e usa `BrasilApiCnpjLookup::enderecoFrom($data)` (método
  público dedicado, único ponto que sabe extrair os campos de endereço de
  uma resposta crua da API) para montar o array aceito por
  `Endereco::create()`. Se `buscar()` retornar `null` (CNPJ não
  encontrado, inválido, ou o campo nunca perdeu o foco), nenhum Endereço
  é criado — o usuário cria manualmente pela aba de Endereços, como já
  funcionava antes desta funcionalidade existir. O Endereço criado é
  anexado à relação com `tipo = TipoEndereco::Comercial` e
  `principal = true`, e fica imediatamente visível/editável na aba de
  Endereços do próprio registro recém-criado. `afterCreate()` só existe
  em `CreateRecord` (não em `EditRecord`), então não há como isso rodar
  de novo numa edição nem risco de duplicar o Endereço a cada save.
- Situação Cadastral é renderizada como um badge nativo do Filament
  (`x-filament::badge`, via `Illuminate\Support\Facades\Blade::render()`
  dentro de um `Placeholder::make(...)->content(...)`) em vez de HTML/CSS
  hand-rolled — garante que a aparência acompanha o tema/versão do
  Filament automaticamente. Cores conforme os códigos oficiais informados
  para esta tarefa: `02` Ativa (success/verde), `03` Suspensa
  (warning/amarelo), `04`/`05` Inapta/Nula (danger/vermelho), `01`/`08`
  Baixada (gray). Dois campos `Hidden::make()` (`situacao_cadastral` e
  `descricao_situacao_cadastral`) guardam os dados reais — o Placeholder
  em si é só a representação visual, lida via `Get` a partir desses
  hidden fields.
- Dados que a API de CNPJ retorna mas ainda não têm campo
  correspondente no cadastro (decisão de produto pendente de
  avaliação, não implementação): capital social (`capital_social`),
  sócios/QSA (`qsa`), CNAEs secundários (`cnaes_secundarios` — só o CNAE
  principal é mapeado), datas de opção/exclusão do Simples/MEI (só o
  resultado final do regime é guardado, não as datas).

### NCM removido do cadastro de Pessoa Jurídica (2026-08-28)

A primeira versão desta integração (2026-08-27) também adicionava um
campo `ncm` (Select com busca assíncrona na BrasilAPI) em Pessoa
Jurídica. Foi um equívoco de escopo, revertido no dia seguinte: NCM
(Nomenclatura Comum do Mercosul) é uma classificação de
produto/mercadoria, não de empresa — não pertence ao cadastro de
Pessoa Jurídica. Revertido via nova migration
(`2026_08_28_100000_drop_ncm_from_pessoas_juridicas_table.php` — a
migration original de criação da coluna,
`2026_08_27_100000_add_ncm_to_pessoas_juridicas_table.php`, não foi
editada, já que tinha sido commitada/rodada; reverter uma migration já
aplicada é sempre uma migration nova, nunca uma edição retroativa da
antiga), removendo também o campo do `form()`, do `$fillable` do model e
as chaves de tradução `ncm`.

`Perseu\Pessoas\Support\BrasilApiNcmLookup` foi mantido no código (não
deletado) — tem um comentário no topo do arquivo marcando "RESERVADO
PARA USO FUTURO" — porque a lógica em si (busca assíncrona na
BrasilAPI, `GET /api/ncm/v1`) é reaproveitável quando o futuro cadastro
de Produto/Material for criado, que é onde NCM realmente pertence. Só o
ponto de uso (`getSearchResultsUsing`/`getOptionLabelUsing` num Select)
precisa ser reconectado lá — a classe em si não muda.

Pendências de outras APIs da BrasilAPI avaliadas mas fora de escopo
(feriados nacionais, bancos/PIX) — ver `PENDENCIAS-INTEGRACOES.md`.

---

## Excluir Pessoa Jurídica em cascata (Endereços/Contatos) + CNPJ de registro excluído (2026-08-28)

**Bug relatado**: excluir uma Pessoa Jurídica e recriar o cadastro com o
mesmo CNPJ trazia de volta o Endereço antigo. Causa raiz confirmada
por teste controlado (não a hipótese inicialmente suspeitada): não
existia (e nunca existiu) nenhuma lógica de `updateOrCreate`/restauração
silenciosa no fluxo de criação — `CreatePessoaJuridica` sempre usa
`create()` puro, sempre gera um `id` novo. O problema real era outro,
em duas partes que juntas produziam o sintoma:

1. **Excluir uma Pessoa Jurídica (mesmo só soft-delete) nunca excluía
   Endereços/Contatos vinculados** — eles ficavam "vivos" no banco,
   ainda ligados via pivot/FK a um registro que só estava na lixeira.
   Não havia nenhum model event, observer, nem lógica no Resource
   cuidando disso — confirmado lendo `PessoaJuridica.php` (sem
   `boot()`) e o Resource (só `DeleteAction::make()` padrão, sem
   customização).
2. A validação `->unique(ignoreRecord: true)` do campo `cnpj` usa
   `Illuminate\Validation\Rule::unique()`, que consulta a tabela crua
   (sem o `SoftDeletingScope` do Eloquent) — ou seja, um CNPJ de
   registro soft-deleted já bloqueava a recriação, só que com uma
   mensagem genérica ("já se encontra registrado") sem explicar o
   motivo real. Se a recriação já estava bloqueada, como o Endereço
   antigo reaparecia? Reproduzido durante os próprios testes desta
   correção: bastou restaurar manualmente (via tinker, `->restore()`)
   um registro soft-deleted que já tinha essa "lixeira fantasma" —
   algo plausível de ter acontecido durante os testes desta semana (o
   projeto ainda não tinha Lixeira/Restore funcional na UI, só acesso
   via tinker/DB) — para os Endereços órfãos reaparecerem
   instantaneamente, já que a relação nunca tinha sido desfeita. A causa
   raiz de fundo é (1): sem isso corrigido, QUALQUER restauração futura
   (manual ou por uma Lixeira que viesse a ser implementada)
   reproduziria o mesmo sintoma.

**Correção 1 — cascata em `Perseu\Pessoas\Models\PessoaJuridica::boot()`**:
listener em `static::deleting(...)` (dispara tanto em soft-delete quanto
em `forceDelete()` — `SoftDeletes::delete()` ainda passa pelos eventos
normais do Model) que apaga todos os `Contato` da relação e, para cada
`Endereco`, desvincula (`detach()`) e só apaga de fato se nenhuma outra
Pessoa Física/Jurídica ainda o referenciar. **Esta versão inicial do
hook (`deleting`) foi corrigida numa tarefa posterior** (ver "Auditoria
(log de atividade) + Lixeira completa" abaixo) para `forceDeleting`,
já que a Lixeira completa feita naquela tarefa depende de
Endereço/Contato permanecerem intactos enquanto o registro pai só está
soft-deleted (pra `Restaurar` funcionar de verdade).

**Correção 2 — mensagem clara para CNPJ de registro excluído**: o
`->unique()` do campo `cnpj` ganhou
`modifyRuleUsing: fn (Unique $rule) => $rule->whereNull('deleted_at')`
— volta a considerar só registros ATIVOS. Um novo
`Perseu\Pessoas\Rules\CnpjNaoExcluido` assume especificamente o caso
"existe um soft-deleted com este CNPJ", com mensagem própria orientando
o usuário a restaurar ou apagar definitivamente antes de recriar.

**Escopo original**: só `Perseu\Pessoas\Models\PessoaJuridica`.
`PessoaFisica` tinha exatamente a mesma estrutura e provavelmente o
mesmo problema latente, mas não foi corrigido nesta tarefa (só
confirmado/corrigido depois — ver "Bug de 'registro fantasma' em
Pessoa Física" abaixo).

---

## Auditoria (log de atividade) + Lixeira completa — implementação original (2026-08-28)

Antes desta tarefa o Perseu não tinha NENHUMA auditoria (nem
`created_by`/`updated_by`, nem log de nenhum tipo) e o soft delete
existente (`PessoaJuridica`/`PessoaFisica`/`Projeto`) não tinha Lixeira
funcional na UI — só marcava `deleted_at`, sem filtro pra ver
excluídos nem ação de restaurar/apagar definitivamente. Essas duas
lacunas foram resolvidas juntas: `spatie/laravel-activitylog` +
`rmsramos/activitylog` (plugin Filament v5 pronto pra ele) via um novo
plugin próprio `perseu/auditoria`, e a Lixeira completada nos 3
Resources que já usavam `SoftDeletes`.

### Plugin `perseu/auditoria`

Segue exatamente o mesmo esqueleto de `perseu/pessoas`
(`Webkul\PluginManager\PackageServiceProvider`) — sem migrations
próprias: as migrations do pacote spatie (`activity_log`) foram
publicadas para `database/migrations/` do próprio app (`php artisan
vendor:publish --tag=activitylog-migrations`) e rodam via `artisan
migrate` normal, de propósito fora do ciclo de instalar/desinstalar do
plugin-manager — é infraestrutura compartilhada por QUALQUER plugin
que audite Models, não dado específico do plugin `auditoria`;
desinstalar o plugin não deveria arriscar dropar a tabela de log de
outros módulos.

`perseu/pessoas` e `perseu/comercial` ganharam `->hasDependency('auditoria')`
nos respectivos ServiceProviders (os Models deles referenciam
`Perseu\Auditoria\Traits\LogsBusinessActivity` diretamente — sem o
plugin de auditoria instalado, essas classes quebrariam).

`config('activitylog.subject_returns_soft_deleted_models')` foi
mudado de `false` (padrão do pacote) para `true` — sem isso, a aba de
Atividades de um registro perderia a referência ao "assunto" assim que
ele fosse soft-deleted (`$activity->subject` viraria `null`), o que
não faz sentido dado que o sistema usa soft delete extensivamente.

### Página de Auditoria dentro de Configurações (não item de topo próprio)

`rmsramos/activitylog` cria, por padrão, seu próprio item de navegação
de nível superior. A tarefa pediu especificamente Configurações →
Auditoria (mesmo padrão de "Marca"/`ManageBranding`), não um menu
próprio. Mecanismo: `Perseu\Auditoria\Filament\Resources\
AuditoriaResource extends \Rmsramos\Activitylog\Resources\Activitylog\
ActivitylogResource` com `protected static ?string $cluster =
Webkul\Support\Filament\Clusters\Settings::class;` — a mesma técnica
que `Webkul\Support\Filament\Resources\ActivityTypeResource` já usa
(achada como referência viva — `ManageBranding` é uma `SettingsPage`
ligada a uma classe de Settings do `filament/spatie-laravel-settings-plugin`,
base errada pra uma tela com tabela/filtros/timeline como esta).
Registrado via `ActivitylogPlugin::make()->resource(AuditoriaResource::class)`
(troca o Resource padrão do pacote pelo nosso).

`getPages()` precisou ser sobrescrito com Pages próprias
(`ListAuditoria`/`ViewAuditoria`, cada uma só reapontando
`$resource`/`getResource()` para `AuditoriaResource::class`): as Pages
originais do pacote têm esse valor FIXO na classe base
`ActivitylogResource` — herdar `getPages()` sem reapontar faria as
rotas/permissões resolverem pro Resource de topo do pacote, não pro
nosso clusterizado.

**Bug corrigido durante a implementação**: `Panel::configureUsing()`
(usado em `packageRegistered()`) aplica a callback a TODOS os painéis
registrados, não só o admin — sem um `if ($panel->getId() !== 'admin')
{ return; }` antes de `$panel->plugin(ActivitylogPlugin::make())`, a
tela de log de atividade também aparecia registrada no painel
`customer`, o que não faz sentido. Confirmado via `route:list`
antes/depois do fix (`settings/activitylogs` no painel customer
sumiu).

**Bug 2 corrigido depois (2026-08-28, correção pontual)**: o menu
"Auditoria" mostrava a chave crua de tradução
(`Auditoria::filament/resources/auditoria.plural-model-label`) em vez
do texto "Auditoria". Causa raiz: `AuditoriaServiceProvider::
packageRegistered()` montava o `ActivitylogPlugin` com
`->label(__(...))`/`->pluralLabel(__(...))` — `__()` avaliado
imediatamente, não dentro de uma Closure. `packageRegistered()` roda a
partir de `PackageServiceProvider::register()`
(spatie/laravel-package-tools), e a fase `register()` de TODOS os
service providers do Laravel sempre termina ANTES da fase `boot()` de
qualquer um — só no `boot()` (`bootPackageTranslations()`, disparado
por `->hasTranslations()`) o namespace `auditoria::` é de fato
registrado no `Translator`. Ou seja, o `__()` eager rodava sempre
ANTES do namespace existir, retornava a própria chave — e pior: o
`Translator` cacheia esse resultado vazio por namespace+grupo+locale
pro resto do request (`Illuminate\Translation\Translator::$loaded`),
então mesmo chamadas LEGÍTIMAS feitas bem depois no mesmo request
recebiam a mesma resposta envenenada.

Diagnosticado comparando `app('translator')->getLoader()->load(...)`
(sempre lê do disco, funcionava) com `__()`/`Translator::get()`
(sempre falhava, mesmo isoladamente) — a diferença apontou direto pro
cache interno do Translator, não pro arquivo/chave/registro do
namespace em si (que estavam corretos o tempo todo).

**Correção**: trocar `->label(__(...))` por `->label(fn () => __(...))`
(idem `pluralLabel`) — `ActivitylogPlugin::label()`/`pluralLabel()`
aceitam `string|Closure`, e `getLabel()`/`getPluralLabel()` só avaliam
a Closure quando o valor é realmente lido (`$this->evaluate($this->label)`),
bem depois do `boot()` de todos os providers já ter terminado. A regra
geral derivada disso (nunca chamar `__()` dentro de
`packageRegistered()`/`register()`) já está em `CLAUDE.md`.

### Permissões (Shield) — chaves reais, não as citadas na tarefa original

A tarefa original citava `view_any_activity_log`/`view_activity_log`
como exemplo — a convenção REAL de geração de chaves deste projeto
(`Webkul\PluginManager\PermissionManager::managePermissions()`) deriva
a chave do nome/namespace do Resource, não do Model subjacente: pra um
Resource `Perseu\Auditoria\Filament\Resources\AuditoriaResource`
(plugin `auditoria`), o resultado é `view_any_auditoria_auditoria` /
`view_auditoria_auditoria` (confirmado rodando `auditoria:install` e
consultando a tabela `permissions` — não adivinhado da leitura do
algoritmo). `config/filament-shield.php` do plugin declara só
`['view_any', 'view']` pra esse Resource — log de atividade é gerado
pelo sistema, nunca criado/editado/excluído manualmente pela UI.

`Perseu\Auditoria\Policies\ActivityPolicy` (`Gate::policy(Activity::class,
ActivityPolicy::class)`, registrado em `packageBooted()`) controla ao
mesmo tempo a página de Auditoria E a aba "Atividades" embutida em
Pessoa Jurídica/Física/Projeto (na época) — sem nenhum código extra na
aba: `RelationManager::canViewForRecord()` (padrão do Filament) já
resolve o Model da relação (`activities()`, fornecido pelo trait
`LogsActivity` do Spatie) e chama `authorize('viewAny', Activity::class)`
sozinho — isso já era a "permissão separada de ver/editar o próprio
registro" pedida na tarefa. Testado criando uma role/usuário sem essa
permissão: a aba não aparece no HTML da página de edição, mas a página
em si continua acessível.

**Nota**: a aba "Atividades" embutida citada aqui foi removida numa
tarefa posterior (ver "Central de Auditoria única, sem abas de
'Atividades' nos registros individuais" abaixo) — hoje a auditoria só
é vista pela Central.

### Lixeira completa — `TrashedFilter` + `RestoreAction` + `ForceDeleteAction`

Aplicado em `PessoaJuridicaResource`, `PessoaFisicaResource` e
`ProjetoResource` (os 3 Resources cujo Model usava `SoftDeletes` E
tinha página de Edit dedicada): `Filament\Tables\Filters\TrashedFilter`
(classe nativa do Filament — uma `TernaryFilter` com
`true`=`withTrashed()`, `false`=`onlyTrashed()`,
branco=`withoutTrashed()`) em `->filters([...])`, e
`RestoreAction`/`ForceDeleteAction` em `->recordActions([...])` +
`RestoreBulkAction`/`ForceDeleteBulkAction`/`DeleteBulkAction` num
`BulkActionGroup` em `->toolbarActions([...])` — mesmo padrão visual
usado pelos Models originais do AureusERP (ex.:
`Webkul\Employee\Filament\Clusters\Configurations\Resources\SkillTypeResource\
RelationManagers\SkillsRelationManager`, achada como referência viva
via grep — sem essa referência real, um exemplo do próprio código
(`WorkLocationResource`) tinha `RestoreAction`/`ForceDeleteAction` SEM
`TrashedFilter`, o que deixaria as duas ações inalcançáveis; não
copiado por estar incompleto).

### Limitação conhecida na época — `CategoriaPessoa`/`Setor`/`SituacaoProjeto`/`TipoProjeto` sem Lixeira nem aba de Atividades

A tarefa original citava Categoria/Setor como já usando `SoftDeletes` —
não usavam (confirmado por grep antes de implementar, não assumido).
Além disso, os 4 Resources desses Models usam o padrão `ManageRecords`
do Filament (uma página só, criar/editar via modal) — estruturalmente
incompatível com RelationManager (a aba de Atividades exige uma página
de Edit/View dedicada, que esses 4 não têm). Decisão consciente
(confirmada com o usuário durante a implementação): não expandir o
escopo desta tarefa pra também adicionar `SoftDeletes` a esses 4
Models e reestruturar os Resources — os 4 Models JÁ SÃO auditados
(`LogsBusinessActivity`), só não têm Lixeira nem aba visual de
Atividades. Continua valendo hoje (ver `CLAUDE.md`, "Limitações
conhecidas").

### Convenção definida nesta tarefa para todo Model de cadastro de negócio criado a partir daqui

A versão consolidada e atual desta convenção está em `CLAUDE.md`
("Convenção para Model novo de cadastro de negócio"). Resumo do que
foi decidido nesta tarefa: usar `SoftDeletes`, usar
`LogsBusinessActivity` em vez de escrever `getActivitylogOptions()` à
mão, usar (então) `CascadesRelatedDataOnForceDelete` para relações
`BelongsToMany`/`HasMany` sem SoftDeletes próprio, garantir página de
Edit/View dedicada no Resource (não `ManageRecords`) se quiser
Lixeira/aba de Atividades, adicionar as Actions/Filter de Lixeira na
`table()`, e declarar `restore`/`restore_any`/`force_delete`/
`force_delete_any` no `config/filament-shield.php` do plugin.

---

## Cluster "Obras" no plugin `perseu/comercial` — investigação e implementação (2026-08-29)

Criado `Perseu\Comercial\Filament\Clusters\Obras`, agrupando
`ObraResource`/`TipoObraResource`/`SituacaoObraResource` numa área com
sidebar lateral própria (Tipos de Obra, Situações de Obra, Obras),
igual ao clique em "Configurações" no plugin de Tarefas. Antes de
implementar, foi investigado a fundo o commit `26cfef4f7` ("Remove
Clusters em favor de registro direto de Resources", ver seção
"Navegação de módulos" acima) — para confirmar que este Cluster novo
não reintroduz o mesmo problema.

### O que o commit `26cfef4f7` revelou (lido via `git show`, não suposto)

O `PessoasCluster`/`ComercialCluster` antigos tinham um objetivo
DIFERENTE do de hoje: fazer Categorias/Pessoas Físicas/Pessoas
Jurídicas (e Projetos/Situações/Tipos de Projeto) aparecerem como
itens FLAT/irmãos dentro do dropdown da topbar, replicando o padrão
"Projetos" (que tem `ProjetoResource`, `TaskResource` e o Cluster
`Configurations` como 3 itens irmãos dentro do MESMO dropdown
"Project"). Só que um Cluster, por definição, tira TODOS os seus
Resources filhos da navegação principal — só o Cluster em si aparece
lá, como item único. Resultado observado então: o dropdown "Pessoas"
tinha 1 item só (o Cluster), enquanto "Projetos" tinha vários — o
oposto do padrão desejado naquele momento. Os dois arquivos-histórico
`tarefa-cluster-navegacao-horizontal.md`/`tarefa-navegacao-topbar-correta.md`
(no próprio commit) documentam duas tentativas de correção erradas
antes da remoção final.

Ou seja: o Cluster do Filament em si nunca teve um bug — o problema
era usar um Cluster (item único + sidebar) quando o resultado visual
desejado era o OPOSTO (múltiplos itens irmãos na topbar, sem
sidebar). São dois mecanismos de navegação genuinamente diferentes do
Filament, cada um certo para um objetivo diferente.

### Por que o Cluster "Obras" de hoje é seguro (objetivo é o OPOSTO do que causou o problema em 26cfef4f7)

Desta vez o objetivo pedido é exatamente o que um Cluster resolve: UM
item ("Obras") no dropdown "Comercial" da topbar, que ao ser clicado
abre uma sidebar própria com os 3 cadastros — o mesmo padrão já usado
e funcionando hoje por `Webkul\Support\Filament\Clusters\Settings`
("Configurações") e por `Webkul\Project\Filament\Clusters\Configurations`
("Configurações" dentro do grupo "Projetos"). Efeito colateral aceito
conscientemente: o dropdown "Comercial" da topbar passou a ter 1 item
só ("Obras") em vez dos 3 itens irmãos que tinha antes desta tarefa —
o roadmap de `CONCEITO-OBRA-PROPOSTA-PROJETO.md` deve adicionar NOVOS
itens irmãos ao Cluster "Obras" nesse mesmo dropdown no futuro, não
substituir esta decisão. (Nota: um segundo Cluster, "Referências", foi
de fato adicionado depois — ver seção correspondente abaixo.)

### Risco novo verificado e evitado: ícone do grupo + ícone do item dentro da sidebar do Cluster

O commit `0035a3bef` ("Corrige erro 500 no cluster de Configurações do
plugin de Tarefas") mostrou que a sidebar de QUALQUER Cluster lança
`\Exception` quando um grupo de navegação DENTRO da sidebar do Cluster
tem ícone (`NavigationGroup` enum) E os itens desse grupo também têm
`$navigationIcon` próprio — que era exatamente o caso de
`ObraResource`/`SituacaoObraResource`/`TipoObraResource` antes desta
tarefa (cada um com `getNavigationGroup() => NavigationGroup::Comercial`,
que tem ícone, MAIS `$navigationIcon` próprio). Migrar os 3 Resources
para dentro do Cluster `Obras` SEM remover esse `getNavigationGroup()`
teria reintroduzido o mesmo 500 — só que na sidebar do Cluster
"Obras", não na de "Configurações".

**Correção aplicada**: `getNavigationGroup()` foi REMOVIDO dos 3
Resources — dentro de um Cluster, quem aparece no dropdown da topbar é
o Cluster, não o Resource. Confirmado por comparação direta de código:
nenhum Resource dentro de `Webkul\Project\Filament\Clusters\Configurations`
declara `getNavigationGroup()` — mesmo padrão replicado aqui.
Confirmado em runtime via tinker: os 3 itens vêm dentro de um único
`NavigationGroup` sintético com `label = null` (sem cabeçalho/ícone de
grupo visível), então a exceção nunca é disparada.

### Slugs e ordem

`Obras::$slug = 'comercial'` (a URL do próprio Cluster,
`admin/comercial`, redireciona automaticamente pro primeiro item da
sidebar). `ObraResource::$slug = 'obras'`, `TipoObraResource::$slug =
'tipo-obras'`, `SituacaoObraResource::$slug = 'situacao-obras'` — como
a rota final de um Resource clusterizado é `{slug do Cluster}/{slug do
Resource}`, essa escolha fez as 3 URLs finais ficarem EXATAMENTE
iguais às de antes desta tarefa. Ordem na sidebar via
`$navigationSort` (1 = Obras, 2 = Tipos de Obra, 3 = Situações —
cadastro principal primeiro, cadastros de apoio depois).

### Breadcrumb — evitado o mesmo bug do commit `0035a3bef`/Configurations

`Filament\Clusters\Cluster::getClusterBreadcrumb()` cai, por padrão,
em `static::$title ?? str(class_basename)->beforeLast('Cluster')->kebab()
->replace('-', ' ')->ucwords()` — puramente derivado do nome da
classe, SEM tradução. Confirmado por leitura de código que é
exatamente isso que faz o Cluster "Configurations" (`webkul/projects`)
mostrar "Configurations >" em inglês no breadcrumb hoje (bug
conhecido, fora de escopo desta tarefa, não corrigido). `Obras`
sobrescreve `getClusterBreadcrumb()` explicitamente, retornando
`static::getNavigationLabel()` (que já usa
`__('comercial::filament/clusters/obras.navigation.title')`) —
confirmado em runtime que o breadcrumb mostra "Obras" corretamente,
não o nome da classe nem inglês.

### Shield — mesma exclusão de página "fantasma" já usada por PessoasCluster/ComercialCluster

`Obras` é um Cluster "puro" (nenhuma Page própria declara `$cluster =
Obras::class` além dos 3 Resources). Adicionado `'pages' => ['exclude'
=> [Obras::class]]` em `config/filament-shield.php` —
`shield:generate --resource=ObraResource,SituacaoObraResource,TipoObraResource`
confirmou "Entities processed: 3" (não 4), e as 22 permissões geradas
continuaram com os mesmos nomes de antes (nenhuma permissão nova,
nenhum toggle morto).

### Validado (via tinker, autenticado como o próprio usuário Admin)

`Filament::getNavigation()` (grupo "Comercial" com 1 item só, "Obras");
`app(Obras::class)->getCachedSubNavigation()` (3 itens na ordem
correta, sem exceção de ícone); `Livewire::test()` renderizou sem
exceção `ListObras`, `ManageTiposObra`, `ManageSituacoesObra` e
`EditObra`; numeração automática de Obra confirmada intacta;
`route:list` e `shield:generate` conferidos antes/depois; `ddev
artisan optimize:clear` executado ao final.

**Achado incidental, fora de escopo, NÃO introduzido por esta tarefa**
(confirmado comparando o mesmo teste com `git stash` do código desta
tarefa aplicado/revertido): a aba "Auditoria" e o filtro de Lixeira de
`ObraResource` não apareciam no HTML retornado por
`Livewire::test(...)->html()` mesmo ANTES desta tarefa — parece uma
particularidade de como esse teste renderiza RelationManagers/painéis
de filtro (possivelmente carregados via Livewire aninhado/lazy), não
um bug real da UI (a mesma checagem em `EditPessoaJuridica` encontrava
"Auditoria" normalmente). Não investigado a fundo por ser preexistente
e não relacionado ao Cluster.

---

## Central de Auditoria única, sem abas de "Atividades" nos registros individuais (2026-08-29)

A aba "Atividades" (`ActivitylogRelationManager`) embutida em Pessoa
Jurídica/Física/Obra foi removida — decisão consciente de não duplicar
informação: a página central "Configurações → Auditoria" (já existente
desde a tarefa anterior) passou a ser o ÚNICO lugar do sistema pra ver
histórico de atividade, com filtros para compensar a perda do atalho
"aba dentro do próprio registro".

### Onde a aba existia (levantado por grep antes de remover)

Só 3 Resources chegaram a ter `ActivitylogRelationManager::class` em
`getRelations()`: `ObraResource` (removido o método inteiro, ficava
sozinho ali), `PessoaFisicaResource` e `PessoaJuridicaResource`
(mantidos os outros Relation Managers de cada um). Os Resources com
padrão `ManageRecords` NUNCA tiveram essa aba — nada a remover neles.

### `Perseu\Auditoria\Support\SubjectTypeCatalog` — mapeamento único FQCN → rótulo/módulo/referência

Levantados por grep todos os 9 Models realmente auditados hoje
(confirmados também com uma query real em `activity_log.subject_type`,
já que este projeto NÃO define `Relation::morphMap()`): `Obra`,
`TipoObra`, `SituacaoObra` (módulo "Comercial"); `PessoaFisica`,
`PessoaJuridica`, `CategoriaPessoa`, `Setor`, `Endereco`, `Contato`
(módulo "Pessoas"). A classe nova
`plugins/perseu/auditoria/src/Support/SubjectTypeCatalog.php`
centraliza:
- `label()`/`subjectTypeOptions()` — rótulo amigável traduzido (texto
  DUPLICADO de propósito em relação ao `model-label` de cada Resource
  original — a central de Auditoria não deve chamar Resources de
  outros plugins só pra montar seu próprio rótulo). Um Model auditado
  no futuro e ainda não mapeado aqui não quebra nada — cai no fallback
  `Str::of($fqcn)->classBasename()->headline()`, só não ganha filtro
  dedicado nem referência amigável até ser adicionado ao mapa.
- `moduloOptions()`/`subjectTypesForModulo()` — agrupamento de um
  nível acima (Comercial/Pessoas).
- `referenceFor(?Model $subject)` — texto que identifica O REGISTRO
  específico: `numero_obra` + `descricao` pra Obra, `razao_social` pra
  Pessoa Jurídica, `nome` pra Pessoa Física, `descricao` pra
  Tipo/Situação de Obra, Categoria e Setor, `logradouro`+`numero` pra
  Endereço, e nome da `PessoaFisica` vinculada (fallback pro `cargo`)
  pra Contato. Retorna `null` quando o `subject` não existe mais
  (excluído em definitivo).
- `applyBusca(Builder $query, string $termo)` — filtro de busca
  textual via `whereHasMorph('subject', [...9 classes...], fn ($q,
  $type) => match($type) {...})`.

### `AuditoriaResource::table()` sobrescrito por completo, não encadeado

`ActivitylogResource::table()` (rmsramos/activitylog) monta
`->columns([...])->filters([...])` a partir de métodos estáticos
reutilizáveis — mas chamar `parent::table($table)` e encadear mais
`->filters([...])`/`->columns([...])` por cima SUBSTITUIRIA a lista
anterior (não soma), então `AuditoriaResource::table()` foi reescrito
do zero reaproveitando os métodos estáticos do pai individualmente
mais os novos: `getSubjectTypeColumnComponent()` sobrescrito (antes
mostrava nome de classe cru + id, agora mostra só o rótulo amigável do
TIPO via `SubjectTypeCatalog::label()`),
`getSubjectReferenceColumnComponent()` (novo, `getStateUsing()` +
`SubjectTypeCatalog::referenceFor()`), `getModuloFilterComponent()`
(novo, `->query()` próprio com `whereIn`, já que "módulo" não é coluna
real), `getSubjectTypeFilterComponent()` (novo, SEM `->query()`
custom, já que `subject_type` É coluna real) e
`getBuscaRegistroFilterComponent()` (removido numa tarefa posterior,
ver abaixo).

### Validação (via `Livewire::test(ListAuditoria::class)`, autenticado, com os 68 logs reais já existentes no ambiente)

`SubjectTypeCatalog::subjectTypeOptions()`/`moduloOptions()`
conferidos manualmente (9 tipos, 2 módulos, nenhum rótulo cru); HTML
renderizado sem FQCN vazado; filtro "Módulo=Comercial" (20 registros,
batendo com contagem direta); filtro "Cadastro=Obra" (7 registros,
idem); filtro de busca por nome achando corretamente um log de Pessoa
Jurídica cuja `razao_social` continha aquele nome; filtro
"Evento=created" nativo do pacote continuou batendo. Confirmado que as
abas "Atividades" desapareceram de `EditObra`/`EditPessoaFisica`/
`EditPessoaJuridica` (mesmo teste que antes desta tarefa encontrava
"Auditoria" no HTML de Pessoa Física/Jurídica agora não encontra mais
— confirmando remoção real, não a limitação de renderização já
observada antes). Permissão de acesso continua sendo só
`view_any_auditoria_auditoria`/`view_auditoria_auditoria`.

---

## Central de Auditoria: filtro por usuário + escopo real do filtro de busca (2026-08-29)

Adicionado `getCauserFilterComponent()` em `AuditoriaResource` — um
`SelectFilter::make('causer_id')->searchable()->options(...)`, listando
`Webkul\Security\Models\User` por nome. `causer_id` é coluna real de
`activity_log`, então (mesmo raciocínio já usado pro filtro
`subject_type`) não precisou de `->query()` customizado. Não restringe
também por `causer_type`: uma consulta direta confirmou só dois
valores possíveis neste banco — vazio (ações sem usuário autenticado,
ex. seeders/tinker) ou `Webkul\Security\Models\User` — o projeto não
chama `causedBy()` manualmente em lugar nenhum, o Spatie Activitylog
detecta o causer sozinho a partir do usuário autenticado no guard
padrão. Validado combinando o filtro com módulo/cadastro/evento
simultaneamente contra contagem direta no banco.

### Escopo real do filtro de busca textual ("Nome, razão social, número...") na época

| Cadastro | Coluna(s) pesquisada(s) |
|---|---|
| Obra | `descricao`, `numero_obra` |
| Tipo de Obra, Situação de Obra, Categoria de Pessoa, Setor | `descricao` |
| Pessoa Física | `nome` |
| Pessoa Jurídica | `razao_social`, `nome_fantasia`, `cnpj` |
| Endereço | `logradouro`, `bairro`, `municipio` |
| Contato | `cargo` |

Ou seja: só pesquisava dentro dos 9 Models de cadastro de negócio
auditados — não pesquisava nomes de usuário/causer (esse era o filtro
de Usuário, separado), nem qualquer dado fora desses Models (ex.: nome
da empresa/tenant que usa o sistema, que vive em
`Webkul\Security\Models\Company`/Branding, não em `Pessoa Jurídica`
nem em nenhum Model auditado).

**Causa confirmada do "Sem registros" ao buscar "fa marcenaria"**
(reproduzido exatamente via `Livewire::test()`, retornou `total=0`,
batendo com o relato do usuário): nenhum dos registros de Pessoa
Jurídica cadastrados continha o texto "fa marcenaria" ou "marcenaria"
(confirmado com `LIKE '%marcenaria%'` direto no banco, zero linhas nas
3 tabelas relevantes). "F.A. Marcenaria" é a empresa DONA do sistema (o
tenant, registrada como `Company`/Branding), não um cliente ou
fornecedor cadastrado como Pessoa Jurídica — não havia nenhum registro
de negócio com esse nome pra encontrar. Não era um bug do filtro; o
"Sem registros" estava correto dado os dados reais. Essa investigação
motivou a sugestão (implementada na tarefa seguinte) de melhorar o
label/ajuda do filtro.

---

## Busca da Central de Auditoria unificada na caixa "Pesquisar" padrão (2026-08-29)

A sugestão da tarefa anterior (melhorar o label do filtro dedicado)
virou uma decisão maior: o `Filter` separado "Nome, razão social,
número..." foi removido, e a mesma lógica de busca
(`SubjectTypeCatalog::applyBusca()`) passou a alimentar a caixa
"Pesquisar" padrão do Filament (topo da tabela) — as duas caixas de
busca (a padrão do Filament + o filtro dedicado) confundiam o usuário
sobre qual fazia o quê.

### Mecanismo: `->searchable(query: ...)` na coluna, não um Filter

`getSubjectReferenceColumnComponent()` (coluna "Registro") ganhou
`->searchable(query: fn (Builder $query, string $search): Builder =>
SubjectTypeCatalog::applyBusca($query, $search))`. Isso funciona porque
`Filament\Tables\Columns\Concerns\CanBeSearchable::searchable()` aceita
um segundo parâmetro `Closure $query` — quando presente,
`InteractsWithTableQuery::applySearchConstraint()` chama ESSE closure
em vez de montar a comparação padrão por nome de coluna (o que
quebraria aqui: a coluna é `subject_reference`, um nome inventado só
pra `getStateUsing()`, não existe de verdade em `activity_log`).
`Table::searchPlaceholder(...)` substitui o placeholder genérico
"Pesquisar" por um texto explicando o que a busca cobre.

### Case-insensitive: já vinha "de graça" da collation, sem precisar de `LOWER()`

Antes de mexer em código, foi conferido se este projeto tem algum
padrão já estabelecido de busca case-insensitive — achado no próprio
Filament: `Filament\Support\generate_search_term_expression()` (usada
pela busca padrão de QUALQUER `TextColumn::searchable()` sem `query:`
customizado) só força `Str::lower()` no termo quando o driver do banco
é `pgsql`; pra `mysql`/`mariadb` (este projeto), o padrão é confiar na
collation da própria coluna. Uma checagem com `SHOW FULL COLUMNS` em
cada uma das 14 colunas reais que `applyBusca()` compara confirmou que
TODAS usam `utf8mb4_unicode_ci` — case-insensitive por definição.
Confirmado empiricamente também: `PessoaJuridica::where('razao_social',
'like', '%smartfit%')` (minúsculo) encontrou normalmente "SMARTFIT..."
(maiúsculo). Não foi adicionado `LOWER()` nos dois lados — seria
redundante e inconsistente com o próprio padrão do Filament pra
MySQL/MariaDB.

Validado: busca minúscula encontrando registro maiúsculo; busca por
parte de número de Obra; filtro dedicado antigo confirmado ausente do
HTML; busca combinada com módulo/causer com interseção `AND` correta.

---

## Botão "Editar" morto no detalhe do log de Auditoria — removido via config oficial do pacote (2026-08-29)

Na tela de detalhe de um log (Configurações → Auditoria → Visualizar),
o card "Mudanças" tinha um botão "Editar" que não fazia nada ao
clicar. Investigado até a origem: é o `Action::make('edit')` de
`Rmsramos\Activitylog\Resources\Activitylog\Schemas\ActivitylogForm`
(vendor do pacote) — não é "editar o log", é um link pensado pra abrir
a tela de edição do REGISTRO ORIGINAL afetado (ícone de olho, mas
rótulo hardcoded `__('activitylog::action.edit')` = "Editar" em
pt_BR — inconsistência já existente no próprio pacote, não introduzida
por nós).

**Causa raiz confirmada (não só suposta)**: `ActivitylogResource::
getResourceUrl()` monta o nome da rota via convenção fixa
`filament.{painel}.resources.{plural-kebab-do-basename}.edit` — mas
TODOS os nossos Resources auditados eram clusterizados (Obras dentro
do Cluster `Obras`, Pessoa Física/Jurídica dentro do Cluster
`Pessoas`), então a rota real sempre leva o slug do Cluster no meio
(`filament.admin.comercial.resources.obras.edit`). `route()` lança
`RouteNotFoundException`, capturada internamente, e o método sempre
devolve `'#'` — confirmado chamando `ActivitylogResource::getResourceUrl()`
manualmente num log de um registro AINDA VIVO: mesmo com o registro
existindo e `canViewResource()` retornando `true`, a URL ainda saía
`'#'`. Não era um problema de permissão nem de registro excluído — era
estrutural (o pacote não tem suporte a Resources clusterizados/com
slug customizado nessa convenção).

**Correção**: `ActivitylogPlugin::isResourceActionHidden(true)`
(extensão OFICIAL do pacote pra isso, não um fork do Schema)
adicionada no encadeamento de `ActivitylogPlugin::make()` em
`AuditoriaServiceProvider::packageRegistered()`. Decisão consciente de
ESCONDER (não consertar o link): mesmo que a URL funcionasse, um log
de auditoria não deveria oferecer edição do registro original a partir
dali — contraria o propósito de imutabilidade da auditoria. As outras
duas ações do mesmo card ("Restaurar" — reverte pra um valor anterior
de um log de `updated`; "Restaurar Modelo" — desfaz um soft-delete a
partir do log de `deleted`) têm flags de visibilidade PRÓPRIAS e não
foram afetadas — confirmado com `git stash` (antes/depois) que só o
"Editar" sumiu.

---

## Restaurar um registro excluído a partir da Auditoria — levantamento, NÃO implementado (2026-08-29)

Pedido explícito de só investigar e relatar, sem implementar nada.
Registrado aqui pra não perder o levantamento.

### Onde restaurar hoje (na época do levantamento)

Não existia atalho a partir da Auditoria — era preciso ir até o
Resource do cadastro específico, aplicar o `TrashedFilter` ("Lixeira")
e usar `RestoreAction`/`RestoreBulkAction` ali (Obra, Pessoa
Física/Jurídica). Os outros 6 Models auditados (Tipo/Situação de Obra,
Categoria, Setor, Endereço, Contato) não tinham Lixeira nenhuma — não
havia pra onde "restaurar" esses via UI, só via tinker/DB direto.

### Atalho "Ir para a Lixeira deste cadastro" a partir de um log `deleted` — viável, esforço pequeno (ideia, não implementada)

Confirmado por leitura do código-fonte do Filament: `Filament\Resources\
Pages\ListRecords` declara `#[Url(as: 'filters')] public ?array
$tableFilters` — o Livewire já sincroniza o estado dos filtros da
tabela com a query string `?filters[...]=...` da própria URL,
mecanismo OFICIAL do Filament. `TrashedFilter` é um `TernaryFilter`
com `queries(true: withTrashed(), false: onlyTrashed(), blank:
withoutTrashed())` — o estado "somente excluídos" corresponde a `value
= false`. Um link do tipo `ObraResource::getUrl('index', ['filters' =>
['trashed' => ['value' => false]]])` deveria abrir a listagem já com
"Somente excluídos" aplicado (a codificação exata de `false` na query
string não foi validada ponta-a-ponta num teste de HTTP real).

Pra existir de fato, faltaria: (1) um mapa FQCN → Resource::class
(natural extensão de `SubjectTypeCatalog`, cuidado pra não confundir
com o mapa de rótulos/módulo já existente); (2) uma `Action` visível
só quando `$record->event === 'deleted'` E o `subject_type` está nesse
novo mapa. Estimativa: pequena — algumas horas, a maior parte só
validando a codificação exata da query string do `TrashedFilter` num
navegador real (`Livewire::test()`/tinker não simulam fielmente o
ciclo de request HTTP que popula `#[Url]`).

### Regra pra quando uma ação de restaurar/reverter A PARTIR da Auditoria for implementada (registrado, não implementado)

Se um dia a Auditoria ganhar uma ação que restaure/reverta o registro
DIRETAMENTE dali, essa ação deve exigir confirmação prévia
(`->requiresConfirmation()`) com uma modal que informe, no mínimo: (1)
qual registro será afetado — cadastro + referência, não só o ID
técnico; (2) o que exatamente a ação vai mudar; (3) um alerta explícito
sobre possível inconsistência de relacionamentos — registros com
`CascadesRelatedDataOnForceDelete`/lógica equivalente em
`forceDeleting` já apagaram Endereços/Contatos numa exclusão
DEFINITIVA, e restaurar um registro que passou por isso não traz esses
dados relacionados de volta. Motivo de registrar isso sem implementar:
a lógica de cascata já existe e é fácil de esquecer o alerta quando a
funcionalidade de restaurar-a-partir-da-Auditoria for implementada de
fato.

---

## Lixeira Central (Configurações → Lixeira) agregando Excluídos de todos os cadastros (2026-08-29)

Nova página `Perseu\Auditoria\Filament\Pages\Lixeira`, ao lado de
"Auditoria" no cluster de Configurações — lista os registros
soft-deleted de TODOS os cadastros com Lixeira numa tabela só, com
Restaurar/Excluir Permanentemente por linha e em lote.

### Escopo real: só 3 Models (confirmado por grep, não pela lista de exemplo da tarefa)

A tarefa citava "Obra, Pessoa Jurídica, Pessoa Física, Categoria,
Setor" como exemplo — Categoria de Pessoa e Setor NÃO usam
`SoftDeletes` (confirmado com grep: só `Obra`, `PessoaJuridica`,
`PessoaFisica`), mesma limitação já documentada. `Perseu\Auditoria\
Support\TrashCatalog::models()` é a lista oficial (subconjunto de
`SubjectTypeCatalog`) — adicionar um Model novo aqui quando ele ganhar
`SoftDeletes` + Lixeira de UI no futuro.

### Por que NÃO é uma `VIEW` de banco com `UNION ALL` — dependência circular de plugins

Antes de escrever código, foi avaliada a abordagem "clássica" de
agregação multi-tabela: uma `VIEW` SQL unindo `obras`/
`pessoas_juridicas`/`pessoas_fisicas` — daria paginação/ordenação
nativa do SQL de graça. Descartada: essa `VIEW` teria que viver numa
migration de algum plugin, e o candidato óbvio (`perseu/auditoria`, já
DEPENDÊNCIA de `comercial`/`pessoas` por causa do trait
`LogsBusinessActivity`) declarar também `->hasDependency('comercial')`/
`->hasDependency('pessoas')` seria um CICLO — o
`Webkul\PluginManager\Models\Plugin` (`plugin_dependencies`) não foi
desenhado pra suportar isso. Nota técnica à parte: migrations do
Laravel rodam por ordem de TIMESTAMP no nome do arquivo, não por
`hasDependency()` (isso só afeta a UI de gestão de plugins) — então o
problema real não era de ORDEM DE EXECUÇÃO da migration, e sim do
GRAFO DE DEPENDÊNCIA declarado ficar inconsistente/circular.

### Mecanismo real: `Table::records(Closure)` — hook oficial do Filament v4 pra tabelas sem Eloquent Builder

`Filament\Tables\Table\Concerns\HasRecords::records(Closure $dataSource)`
é o mecanismo OFICIAL (não workaround) do Filament v4 pra uma tabela
cuja fonte de dados não é uma query Eloquent simples — quando
presente, `Table::hasQuery()` retorna `false`, e
`Concerns\HasRecords::getTableRecords()` chama o closure passando o
estado atual como parâmetros NOMEADOS (`filters`, `sortColumn`,
`sortDirection`, `page`, `recordsPerPage`, `search`,
`columnSearches`) em vez de montar uma `Builder` — a aplicação desse
estado fica inteiramente por conta do closure. `getSelectedTableRecords()`
já tem um ramo próprio pra `! hasQuery()` — bulk actions funcionam sem
nenhuma configuração extra.

Cada linha da tabela é um array (`Filament\Support\ArrayRecord`, chave
`'__key'` por padrão), não uma instância real de Model — os 3 Models
têm PKs numéricas que colidem entre si — a chave de cada linha é
sintética: `"{$slugDoModel}-{$id}"`.

### Trade-off consciente: pagina em PHP, não em SQL

`buildPaginator()` busca TODOS os registros excluídos que passam nos
filtros ativos (não só a página pedida) pras 3 fontes, junta numa
`Collection` só, ordena em PHP, e só DEPOIS fatia a página com
`->slice()` + `LengthAwarePaginator` manual. Correto pro volume real
deste sistema (Lixeira de ERP interno) — errado em escala muito maior,
onde a alternativa de `VIEW` (resolvendo a dependência circular de
outro jeito) voltaria a valer a pena.

### Reaproveitamento de lógica — o requisito mais crítico da tarefa

Nem `Filament\Actions\RestoreAction`/`ForceDeleteAction` prontas
(usadas nos Resources individuais) foram reaproveitadas AQUI
diretamente — essas classes assumem que `$record` recebido por suas
closures internas é o Model de verdade; como nossas linhas são
arrays, isso quebraria. Em vez disso, `Action::make('restaurar')`/
`Action::make('excluir_definitivamente')` próprias resolvem o Model
real (`resolveModel()`: `$model::onlyTrashed()->find($id)`) e chamam
`->restore()`/`->forceDelete()` NELE — é isso que satisfaz
"reaproveitar a lógica, não duplicar": a cascata de Endereços/Contatos
mora no `forceDeleting` do PRÓPRIO Model, então chamar `->forceDelete()`
no Model real dispara essa lógica automaticamente, de onde quer que a
chamada venha.

Validado de ponta a ponta: Obra e Pessoa Jurídica de teste (a PJ com
Endereço + Contato vinculados de propósito) soft-deletadas, aparecendo
juntas na Lixeira; `Restaurar` devolvendo a Obra pro estado normal;
`Excluir Permanentemente` na Pessoa Jurídica removendo Endereço e
Contato junto (cascata funcionando); ação em lote testada com Obra E
Pessoa Jurídica selecionadas ao mesmo tempo, tanto pra Restaurar
quanto pra Excluir Permanentemente.

### Permissão — sem Resource/Policy própria, de propósito

Pedido explícito da tarefa: NÃO criar uma permissão genérica
"gerenciar lixeira de tudo". `Lixeira` não é um
`Filament\Resources\Resource` — é um `Filament\Pages\Page implements
Tables\Contracts\HasTable`, sem nenhuma entrada nova em
`config/filament-shield.php`. Cada linha verifica a Policy JÁ
REGISTRADA do Model real (`Gate::allows('restore', $modelReal)` /
`Gate::allows('forceDelete', $modelReal)`). `canAccess()` da própria
página checa `restoreAny`/`forceDeleteAny` de QUALQUER um dos 3
Models.

Validado com um usuário/Role de teste temporários (criados e apagados
só pro teste): Role com `restore_any_comercial_obra` mas SEM
`restore_comercial_obra`/`force_delete_comercial_obra`, e com
permissão completa em Pessoa Jurídica — a página abriu normalmente, as
DUAS linhas apareceram juntas, mas `Gate::allows` confirmou `false`
pra Obra e `true` pra Pessoa Jurídica (visibilidade por linha, não por
página inteira). Ação em lote com seleção mista soma quantos foram
processados vs. pulados por falta de permissão numa notificação só.

### "Excluído por" — cruzado com `activity_log`, em lote (não por linha)

Pra cada tipo de Model presente na página atual, uma única query
(`Activity::where('subject_type', $model)->where('event','deleted')->whereIn('subject_id', $ids)`)
busca o log de exclusão mais recente de cada `subject_id` — no máximo
3 queries extras por carregamento de página, não uma por linha.

### Filtros: Módulo/Cadastro/Período — sem `->query()`, aplicados manualmente

Diferença importante em relação à Auditoria: como esta tabela não tem
`Builder` (`records()`), o Filament NUNCA chama `->query()` de filtro
nenhum aqui — os filtros só existem pra desenhar a UI; a aplicação de
fato é manual, lendo `$filters[...]` dentro de `collectRows()`. Sem
caixa de busca textual unificada (não foi pedida nesta tarefa).

### Filtro "Excluídos" nos Resources individuais — MANTIDO, não removido

A tarefa pediu explicitamente pra só remover o filtro "Excluídos"/ações
de Restaurar/Excluir Permanentemente de dentro de cada Resource
individual DEPOIS de confirmar com o usuário que a Lixeira Central já
substitui bem esse acesso — essa confirmação nunca chegou a acontecer
nesta tarefa, então `ObraResource`/`PessoaFisicaResource`/
`PessoaJuridicaResource` continuaram com `TrashedFilter`/
`RestoreAction`/`ForceDeleteAction` exatamente como antes. Ver
`CLAUDE.md` para o estado atual dessa pendência.

---

## `forceDeleted` nunca foi logado pelo Spatie Activitylog — descoberto e corrigido, não só "renomeado" (2026-08-29)

Usuário tentou buscar "defi" na caixa "Pesquisar" da Auditoria
esperando achar os logs de "Excluído Definitivamente" — não achou
nada, e o dropdown "Eventos" nem sequer listava essa opção.

**Causa raiz não era rótulo/tradução faltando — era o evento nunca ter
sido gravado**, confirmado por dois caminhos independentes antes de
mexer em qualquer código: (1) lendo `vendor/spatie/laravel-activitylog/
src/Traits/LogsActivity.php`, `eventsToBeRecorded()` só retorna
`created`/`updated`/`deleted` (+ `restored` se o Model usa
`SoftDeletes`) — nenhuma menção a `forceDeleted` em lugar nenhum do
pacote; (2) uma query direta (`Activity::distinct()->pluck('event')`)
num banco com histórico real de vários `forceDelete()` só retornava
`created/updated/deleted/restored`, nunca `forceDeleted`.
`Illuminate\Database\Eloquent\SoftDeletes::forceDelete()` já dispara os
eventos Eloquent `forceDeleting`/`forceDeleted` nativamente (é assim
que `CascadesRelatedDataOnForceDelete` já se pendura em
`forceDeleting` pra cascata de Endereço/Contato) — só não existia
NENHUM listener registrando isso como uma `Activity`.

### Correção: listener próprio em `forceDeleted`, fora da maquinaria do Spatie

`Perseu\Auditoria\Traits\LogsBusinessActivity::bootLogsBusinessActivity()`
(novo) registra `static::forceDeleted(function ($model) {
activity()->causedBy(auth()->user())->performedOn($model)->event('forceDeleted')->log('forceDeleted');
})` — só quando o Model usa `SoftDeletes` (checado ANTES de registrar:
`forceDeleted()` é um método estático que só existe nesse trait,
chamá-lo num Model sem `SoftDeletes` explodiria com "Call to undefined
method"). Deliberadamente NÃO tentei encaixar isso em
`eventsToBeRecorded()`/no fluxo interno do `LogsActivity::
bootLogsActivity()` (PHP não dá `parent::` entre traits) — um listener
PRÓPRIO e independente em `forceDeleted` é mais simples e não arrisca
interações sutis com o mecanismo genérico do pacote. Respeita o mesmo
"kill switch" global do Spatie (`ActivityLogStatus::disabled()`).

**Validado criando uma Obra de teste, soft-deletando e force-deletando
de verdade**: a `Activity` com `event = 'forceDeleted'` apareceu no
banco com `causer` preenchido corretamente.

### Rótulo traduzido — override de UMA chave via `lang/vendor/`, não fork do pacote

`vendor/rmsramos/activitylog/resources/lang/{pt_BR,en}/action.php` não
tinham a chave `event.forceDeleted`. Em vez de publicar/duplicar o
arquivo inteiro do pacote, foram criados `lang/vendor/activitylog/
{pt_BR,en}/action.php` com SÓ a chave nova — mecanismo padrão do
Laravel (`Illuminate\Translation\FileLoader::loadNamespaceOverrides()`,
`array_replace_recursive()` por CIMA da tradução original do pacote).
`pt_BR`: "excluído definitivamente".

### Busca ampliada: "Pesquisar" agora cobre Registro E Evento ao mesmo tempo

`AuditoriaResource::getEventColumnComponent()` foi sobrescrito só pra
somar `->searchable(query: ...)`: o termo digitado é comparado contra
o RÓTULO TRADUZIDO de cada evento, descobre quais valores TÉCNICOS
batem, e faz `whereIn('event', [...])`. Quando nada bate, retorna
`whereRaw('1 = 0')` (não a query sem alteração, que bateria com
QUALQUER termo). Como o Filament já soma com `OR` automático entre
TODAS as colunas marcadas `searchable()`, bastou marcar a coluna
`event` como buscável.

**Validado reproduzindo exatamente o teste do usuário**: buscar "defi"
passou a encontrar o(s) log(s) de exclusão definitiva (antes: zero
resultados); demais combinações de busca+filtro conferidas sem
regressão.

---

## Auditoria: período padrão de 1 ano + filtro de Eventos multi-seleção (2026-08-29)

Decisão que motivou esta tarefa: não implementar exclusão automática
de logs antigos — a tabela `activity_log` é leve e o histórico tem
valor de auditoria/fiscal a longo prazo (ver `CLAUDE.md`, pendências).
Em vez disso, dois ajustes de usabilidade: a lista abre já filtrada
pro último ano (ajustável/limpável livremente), e o filtro de Eventos
virou "desmarque o que não quer ver" em vez de "marque só um".

### Período padrão — `->default()` no `DatePicker`, formato ISO (não o de exibição)

`getDateFilterComponent()` sobrescrito com `->default(now()->subYear()->toDateString())`
só no campo "Criado a partir de". **Achado importante, confirmado
empiricamente antes de decidir o formato**: `ActivitylogPlugin::get()->
getDateFormat()` (`d/m/Y`, usado pelo pacote só para EXIBIÇÃO) NÃO é o
formato que o `DatePicker` realmente usa no valor dehydratado/state
interno — testado passando os dois formatos: `Y-m-d` funcionou
normalmente; `d/m/Y` quebrou a renderização do indicador do filtro com
`Could not parse '29/08/2026'` (`ActivitylogPlugin::getDateParser()`
usa `Carbon::parse()` sem formato explícito — ambíguo pra
`dd/mm/yyyy`, falha quando o dia é > 12). Se tivesse assumido `d/m/Y`
sem testar, o default quebraria a página pra qualquer usuário cujo dia
atual fosse > 12.

Validado forçando via SQL direto a `created_at` de um log real pra 2
anos atrás (teste revertido ao final): com o default aplicado, esse
log some da lista; limpando o filtro manualmente, ele volta.

### Filtro de Eventos — multi-seleção com tudo marcado, não `DISTINCT event`

`getEventFilterComponent()` sobrescrito: `SelectFilter::make('event')
->multiple()` com `->default(eventoKeys())` (todos os 5 valores
técnicos marcados). `eventoKeys()` é uma lista FIXA
(`created/updated/deleted/forceDeleted/restored`), não o `DISTINCT
event` que a versão original do pacote usa — precisava que as 5
opções aparecessem sempre marcáveis mesmo se algum evento ainda não
tivesse ocorrido neste banco. Mesma lista reaproveitada por
`getEventColumnComponent()` (busca por evento, tarefa anterior).

Validado: desmarcar `forceDeleted` tirou exatamente 1 registro da
lista (batendo com contagem direta); combinado com filtro de Módulo
intersecciona corretamente.

### Item 4 da tarefa — filtro "alinhado na coluna Eventos": não é nativo, mantido no painel de Filtros

Avaliado antes de implementar: `Filament\Tables\Enums\FiltersLayout`
controla só a POSIÇÃO do painel de filtros inteiro em relação à
tabela, nunca um dropdown ancorado a uma coluna específica. Não existe
esse recurso nativo nesta versão do Filament. Construir isso do zero
seria consideravelmente mais trabalho por puro ganho estético — não
implementado, mantido no painel de Filtros padrão.

---

## Localização do cadastro de Empresa (Company) pro padrão brasileiro — Empresa e Filiais (2026-08-30)

`Webkul\Support\Models\Company` (Configurações → Empresas) — Model
CORE do AureusERP, integrado a multi-tenancy/segurança — foi
LOCALIZADO pro padrão brasileiro de Pessoa Jurídica (CNPJ, busca
automática via BrasilAPI, endereço com CEP/UF), decisão consciente de
adaptar os campos em vez de substituir o Model por `PessoaJuridica`
(mexer na integração de segurança/multi-empresa seria arriscado demais
pro ganho). Filial (`Branch`) NÃO é um Model separado — é a MESMA
tabela `companies`, auto-referencial via `parent_id` — por isso
`BranchesRelationManager` recebeu exatamente o mesmo tratamento, com o
próprio `form()`/`infolist()` (não herda do `CompanyResource`, é uma
classe irmã que duplica a estrutura desde a origem do AureusERP).

### Estudo obrigatório ANTES de mexer em qualquer coluna — dois agentes de investigação, não suposição

Antes de qualquer alteração, dois levantamentos extensos confirmaram:

1. **`registration_number` E `company_id` (coluna própria, string
   única — NÃO a PK numérica) SÃO usados internamente**: `Company::boot()`
   copia `registration_number` → `Partner.company_registry` em
   `creating` E `saved`; ambos aparecem em
   `Http/Resources/V1/CompanyResource.php` (API pública) e em
   `database/seeders/CompanySeeder.php`/`database/factories/CompanyFactory.php`.
   Decisão: campos ESCONDIDOS do formulário (`->hidden()`), NÃO
   removidos — coluna, `$fillable` e sincronização com Partner
   continuam intactos.
2. **`tax_id` — desvio deliberado da instrução literal da tarefa**: em
   vez de remover `tax_id` e criar uma coluna `cnpj` nova, `tax_id` foi
   reaproveitado como CNPJ (mesmo tratamento dado a `name`→Razão
   Social). Já tinha `unique()->nullable()` e já sincronizava pra
   `Partner.tax_id`; nenhum PDF/e-mail do sistema hoje exibe `tax_id`
   (confirmado por grep completo), então repropor seu conteúdo não
   quebra nada visível.
3. **`name` — usado bem além do CRUD de Empresa**: aparece no
   `company-switcher.blade.php` (dropdown de troca de empresa na
   topbar) e em SEIS templates de impressão/PDF diferentes via
   `$record->company->name`, além de ser serializado inteiro
   (`current_company()->toArray()`) em TODO e-mail automático do
   sistema. Como a COLUNA `name` nunca foi renomeada (só o LABEL do
   formulário virou "Razão Social"), nenhuma dessas dezenas de pontos
   de uso foi afetada.
4. **Endereço nos PDFs vem de `Company->partner->{campo}`, não de
   `Company->{campo}` diretamente** — `Company::boot()` sincroniza
   `street1`/`street2`/`city`/`zip`/`state_id`/`country_id` pro
   Partner vinculado, e é o Partner que os templates de impressão leem.
   `bairro`/`numero` (as duas colunas genuinamente novas) NÃO entram
   nesse sync (nenhum consumidor precisa deles ainda, já que não
   existe emissão de NF-e implementada) — ponto de atenção se/quando a
   emissão de NF-e for implementada: estender `Company::boot()` pra
   sincronizar esses dois campos também.
5. **Nenhuma Policy/Guard/scope de multi-tenancy depende do VALOR**
   desses campos (`CompanyPolicy` é só permissão-string;
   `CompanyContext`/`RestrictToAllowedCompanies`/`CompanyScope`
   operam só sobre o `id` numérico).
6. **Achado incidental corrigido de graça**: o infolist de
   `BranchesRelationManager` (aba "Informações de endereço")
   referenciava `address.street1`/`address.city`/etc. — `Company`
   NUNCA teve uma relação/accessor `address` (bug pré-existente da
   implementação AureusERP original), então essa aba sempre mostrou só
   "—" vazio, mesmo com os campos preenchidos. Corrigido pra
   `street1`/`city`/etc. Um bug idêntico existe em
   `plugins/webkul/sales/resources/views/sales/quotation.blade.php`
   (`$record->company->address`, sempre `null`) — não corrigido, fora
   do escopo desta tarefa.
7. **Achado que exigia atenção do usuário, não corrigido
   silenciosamente**: o registro real "Fa Marcenaria" (única Empresa
   cadastrada) tinha `tax_id = "Inscrição"` — claramente um valor de
   teste/placeholder. Como o campo agora tem `->rule(new CnpjValido())`,
   a PRÓXIMA tentativa de salvar esse registro falharia a validação até
   o CNPJ real ser digitado ali. Não foi alterado via tinker/seed
   (seria mexer em dado real sem autorização).

### Reaproveitamento da lógica de CNPJ — generalização mínima, não recriação

`Perseu\Pessoas\Support\BrasilApiCnpjLookup::fill()` ganhou um 4º
parâmetro opcional `string $razaoSocialField = 'razao_social'`
(default preserva 100% o comportamento pra quem já chama sem
informá-lo). Endereço INLINE (Company tem UM endereço no próprio
formulário, ao contrário de Pessoa Jurídica, que usa a relação
`enderecos`) não existia como necessidade antes desta tarefa. Nova
classe `Webkul\Support\Support\CompanyCnpjLookup::fillEndereco()`
reaproveita `BrasilApiCnpjLookup::buscar()`/`enderecoFrom()` só pra
fazer o mapeamento Company-específico (`logradouro`→`street1`,
`complemento`→`street2`, `municipio`→`city`, `cep`→`zip`, `bairro`/
`numero` diretos, `uf`→`state_id` via `State::where('code', $uf)->
whereHas('country', code BR)`). Ficou em `webkul/support` (não em
`perseu/pessoas`) pra não misturar conhecimento do schema legado do
AureusERP dentro do plugin de Pessoas.

`Perseu\Pessoas\Enums\RegimeTributario`/`IndicadorContribuinteIcms` e
o cast correspondente em `Company::$casts` foram REUTILIZADOS
diretamente de `perseu/pessoas`, não duplicados — decisão consciente:
`webkul/support` tem `->isCore()` (excluído do grafo de
`plugin_dependencies`), então importar uma classe de `perseu/pessoas`
não introduz o risco de dependência circular que motivou a decisão
equivalente na Lixeira Central. `SituacaoCadastralBadge` foi
DUPLICADA como classe própria em `webkul/support` em vez de
reaproveitada da Resource de Pessoa Jurídica — só 15 linhas, sem
estado, e não fazia sentido puxar uma classe de renderização
específica de um Resource de outro plugin.

### Migration — só 11 colunas novas, todo o resto reaproveitado

`2026_08_30_100000_add_brazilian_fields_to_companies_table` adiciona:
`nome_fantasia`, `cnae`, `cnae_descricao`, `regime_tributario`,
`porte`, `descricao_porte`, `situacao_cadastral`,
`descricao_situacao_cadastral`, `indicador_contribuinte_icms`,
`bairro`, `numero` — todas `nullable()`. REAPROVEITADAS sem migration:
`name`, `tax_id`, `founded_date`, `street1`/`street2`/`city`/`zip`,
`state_id`/`country_id`.

### Validado de ponta a ponta (Empresa E Filial)

Via `Livewire::test()`: buscar um CNPJ preenche todos os campos
esperados tanto no formulário de Empresa quanto no modal de criação de
Filial (usando CNPJs diferentes, confirmando busca independente).
Edição manual de campo já preenchido aceita normalmente. Criação
completa confirmou sincronização pro Partner vinculado. Logo/Cor
(branding) não foram tocados.

---

## Bug de "registro fantasma" em Pessoa Física — só metade existia, confirmado por reprodução (2026-08-30)

Investigação (não suposição) da mesma vulnerabilidade já corrigida em
Pessoa Jurídica, aplicada a `PessoaFisica`:

1. **Cascata de `forceDeleting()` (Endereços/Contatos)**: JÁ corrigida
   — `PessoaFisica` já usava `CascadesRelatedDataOnForceDelete` desde
   a tarefa "Auditoria (log de atividade) + Lixeira completa", que
   fechou essa lacuna nos dois Models ao mesmo tempo (a nota antiga
   dizendo "escopo só PessoaJuridica" ficou desatualizada por aquela
   tarefa posterior). Confirmado de novo por reprodução: criar Pessoa
   Física + Endereço + Contato, `forceDelete()` → os dois somem;
   soft-delete + `restore()` → os dois continuam intactos.
2. **Validação de CPF vs. registro soft-deleted**: bug real,
   confirmado por reprodução antes de corrigir — o campo `cpf` em
   `PessoaFisicaResource` tinha só `->unique(ignoreRecord: true)` (sem
   `whereNull('deleted_at')`) e nenhuma Rule equivalente a
   `CnpjNaoExcluido`. Reproduzido via `Livewire::test()`: criar uma
   Pessoa Física, soft-deletá-la, e tentar criar outra com o MESMO CPF
   falhava silenciosamente — exatamente o mesmo sintoma que motivou a
   correção original em Pessoa Jurídica.

### Correção — mesmo padrão, nova classe `CpfNaoExcluido`

`plugins/perseu/pessoas/src/Rules/CpfNaoExcluido.php` (novo) — cópia
fiel de `CnpjNaoExcluido` trocando CNPJ por CPF. Não extraída pra uma
Rule genérica compartilhada (`RegistroNaoExcluido` parametrizável): as
duas regras já são pequenas e auto-contidas, e generalizar agora seria
antecipar reuso sem um terceiro caso real. `PessoaFisicaResource::form()`
ganhou `->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule)
=> $rule->whereNull('deleted_at'))` + `->rule(fn (?PessoaFisica $record)
=> new CpfNaoExcluido($record?->id))` no campo `cpf`.

### Validado por reprodução (não só leitura de código)

Recriado o cenário exato: Pessoa Física + Endereço + Contato de teste,
soft-deletada — tentar criar uma nova com o mesmo CPF passou a falhar
com a mensagem "Já existe um cadastro excluído com este CPF..."
(confirmado via `$test->getErrorBag()->all()`, já que
`Livewire::test()->html()` nem sempre reflete mensagens de erro de
validação no snapshot, mesmo com o erro real presente); excluindo
definitivamente esse mesmo registro depois, a criação com o mesmo CPF
passou a ser permitida normalmente. Todos os registros de teste foram
limpos ao final.

---

## Cluster "Referências" no plugin perseu/comercial, com o cadastro de Preços (2026-08-30)

Segundo Cluster do módulo Comercial, mesmo padrão técnico do Cluster
`Obras` (mesma estrutura de classe, `getClusterBreadcrumb()`,
`$cluster` nos Resources filhos, exclusão em `filament-shield.php` —
ver seção "Cluster 'Obras'" acima, não reinvestigado de novo, só
replicado). Reúne cadastros de apoio pra compor Propostas/Contratos no
futuro: Preços implementado nesta tarefa; Propostas (modelo/template),
Contratos, Termos de Entrega, Termos de Garantia — apenas
citados/planejados, SEM Resource/Model/migration criados (a ideia de
longo prazo é gerar PDF desses documentos com dados do sistema,
reaproveitando `barryvdh/laravel-dompdf` já presente no
`composer.json` — ver `CLAUDE.md`, "Roadmap — Geração de PDF de
Proposta").

### Slug do Cluster — `comercial/referencias`, não `comercial` (já ocupado)

Diferente de `Obras` (que pôde usar o slug "nu" `comercial`, por ser o
primeiro/único Cluster do módulo na época), `Referencias::$slug =
'comercial/referencias'` — `admin/comercial` já pertence ao Cluster
Obras. `ReferenciaPrecoResource::$slug = 'precos'` (relativo ao
Cluster), URL final `admin/comercial/referencias/precos`.

### `ReferenciaPreco` — cadastro de múltiplas tabelas "vivas" simultâneas, NÃO histórico/versionamento

Esclarecimento confirmado com o usuário antes de desenhar o schema:
cada registro é uma tabela de preços INDEPENDENTE que pode coexistir
com outras ao mesmo tempo (ex.: "Tabela Padrão" e "Tabela Cliente
Corporativo" simultaneamente ativas) — a escolha de QUAL usar acontece
na hora de montar uma Proposta (fora de escopo aqui), não é uma
"versão vigente vs. antiga" no tempo. Por isso o campo principal se
chama `descricao` ("Descrição da Referência"), não algo como
"vigência"/"período".

Campos monetários (`laminacao`/`corte`/`hora_producao`/`hora_execucao`,
`decimal(10,2)`) e percentual (`retencao_tecnica`, `decimal(5,2)`) —
unidade de medida (metro linear vs. m²) exibida no formulário via
`->suffix(...)` traduzido ao lado de cada campo monetário. Coluna da
tabela usa `->money('BRL')` (label da coluna já inclui a unidade entre
parênteses, já que o `->suffix()` do form não se aplica a
`TextColumn`). Nenhum cálculo implementado — só CRUD, conforme escopo
pedido; o uso desses valores em Propostas é trabalho futuro.

### Convenção de Model novo seguida à risca — `SoftDeletes` desde a criação, sem esperar um bug pra adicionar depois

Diferente de `Obra`/`PessoaFisica`/`PessoaJuridica` (que ganharam
`SoftDeletes`/Lixeira numa tarefa posterior à criação),
`ReferenciaPreco` já nasce com `SoftDeletes` + `LogsBusinessActivity` +
`TrashedFilter`/`RestoreAction`/`ForceDeleteAction` no próprio
Resource + entrada em `TrashCatalog`/`SubjectTypeCatalog` — seguindo a
"Convenção para Model novo de cadastro de negócio" (ver `CLAUDE.md`)
desde o primeiro commit, não como correção posterior. Sem aba de
Atividades própria — auditoria só pela Central.

### Validado de ponta a ponta

`route:list` confirmou as 4 rotas esperadas; `shield:generate
--resource=ReferenciaPrecoResource` processou exatamente 1 entidade
(confirma que a exclusão do Cluster funcionou) e gerou as 10
permissões esperadas, sincronizadas manualmente com o Admin. Criado um
registro de teste real via `Livewire::test()` com todos os 6 campos
preenchidos — confirmado aparecendo na Central de Auditoria e, depois
de excluído (soft delete), na Lixeira Central — restaurado por lá com
sucesso, e removido definitivamente ao final. Navegação completa
conferida: grupo "Comercial" foi de 1 pra 2 itens (Obras +
Referências).

---

## Referência de Preços: campos de Imposto/Despesas + criação/edição em modal (2026-08-30)

Depois de estudar a planilha real de Proposta da F.A. Marcenaria
(cálculo de preço + cláusulas contratuais num único documento),
completada a cadeia de cálculo do cadastro de Referência de Preços
(criado na tarefa anterior) com 3 campos que faltavam: Imposto,
Despesas Variáveis, Despesas Fixas. Margem de Lucro deliberadamente
NÃO entrou — é resultado calculado (lucro bruto), não parâmetro de
entrada do cadastro.

### Decisão: os 3 campos novos são percentuais, não valor fixo

A tarefa pediu pra confirmar valor fixo vs. percentual caso não
estivesse óbvio pelo contexto. Decisão: percentual (`decimal(5,2)`,
mesmo formato de `retencao_tecnica`) para os 3 — Imposto incide sobre
o preço de venda (alíquota, sempre percentual por natureza) e Despesas
Variáveis/Fixas, na forma como aparecem na planilha real (uma linha de
"DRE"/rateio de custos operacionais sobre o preço), são tipicamente
expressas como um percentual de rateio sobre o faturamento. Todos os
3 são `->required()`, exibidos com `->suffix('%')`.

Migration em ALTER separada
(`2026_08_30_140000_add_imposto_despesas_to_referencias_precos_table`),
não editando a migration de criação já aplicada. `ReferenciaPreco::
$fillable`/`$casts` atualizados com os 3 campos. Tabela ganhou uma
coluna por campo (mesmo `formatStateUsing` de porcentagem já usado por
`retencao_tecnica` — extraído pra um método
`ReferenciaPrecoResource::formatPercent()` reaproveitado pelas 4
colunas percentuais).

### Criar/editar em modal — mesmo mecanismo que já faz Filial funcionar assim

Investigado como `BranchesRelationManager` (Filial) já abre
criação/edição em modal: o mecanismo real está em
`Filament\Resources\Pages\Page::getDefaultActionUrl()`:

```php
if (($action instanceof EditAction) && (static::getResource()::hasPage('edit')) && ...) {
    return $this->getResourceUrl('edit', ['record' => $action->getRecord()]);
}
return null; // sem URL → Action cai no comportamento padrão: abrir modal
```

Ou seja: `CreateAction`/`EditAction` sempre abrem modal por padrão —
só passam a navegar pra uma página cheia quando o Resource declara
uma page `create`/`edit` em `getPages()` E essa combinação de
`hasPage(...)` retorna `true`. `BranchesRelationManager` nunca teve
essa page pra começo de conversa (RelationManager não registra pages
próprias), por isso sempre foi modal.

**Correção aplicada em `ReferenciaPrecoResource`**: `getPages()`
reduzido pra só `'index'` (removidas as entradas `'create'`/`'edit'`);
apagadas as classes `CreateReferenciaPreco`/`EditReferenciaPreco`
(`Pages/`) e seus arquivos de tradução — sem mais nenhuma referência a
elas em lugar nenhum do código (conferido por grep antes de apagar). O
`CreateAction::make()` já existente em
`ListReferenciasPrecos::getHeaderActions()` e o `EditAction::make()`
já existente em `ReferenciaPrecoResource::table()->recordActions([...])`
não precisaram de nenhuma mudança de código — só de `hasPage(...)`
passar a `false` pra virarem modal automaticamente.

### Validado (Livewire::test() com limitação conhecida, mais confirmação direta em banco)

`route:list` confirmou que só a rota `index` sobrou. **Achado de
teste, não de produto**: `Livewire::test()->callAction('create', [...])`
criou o registro corretamente no banco com os 3 campos novos, mas
`$test->instance()->getErrorBag()->all()` reportou mensagens de campo
obrigatório mesmo com o registro certo já salvo — inconsistência da
própria camada de teste (`assertActionVisible()` interno do
`callAction()` depende de `Illuminate\Testing\Assert`, que por sua vez
depende de `PHPUnit\Framework\Assert::$instance` estar configurado por
um test runner real do PHPUnit; rodando via `artisan tinker` — fora de
um `TestCase` de verdade — essa dependência não está montada). O dado
no banco (não a mensagem de erro da camada de teste) foi usado como
fonte de verdade: registro criado com os valores exatos enviados.

Pela mesma razão, a edição via `fillForm()`/`setTableActionData()`
encadeado depois de `mountTableAction()` não persistiu o valor num
teste inicial — contornado escrevendo diretamente no path de estado
real da action (`mountedActions.0.data.despesas_fixas`, descoberto via
`getMountedActionSchemaName()`/`Schema::getStatePath()`), que
persistiu corretamente. Confirma que o mecanismo de edição em modal
funciona; a fragilidade está apenas na forma de simular o
preenchimento de uma Action dentro do `Livewire::test()` rodado via
tinker, não no código do Resource.

Central de Auditoria e Lixeira Central confirmadas SEM NENHUM IMPACTO
(esperado — essas integrações dependem só de `LogsBusinessActivity`/
`SubjectTypeCatalog`/`TrashCatalog` no nível do Model, nunca da
página/rota usada pra criar o registro).

---

## Referência de Preços: mais 4 campos (Valor por Peças + 3 Fatores) e decisão de não poluir a listagem (2026-08-30)

Mais uma rodada de campos da composição de custo real da empresa:
Valor por Peças (monetário, `decimal(10,2)`) e três fatores
percentuais (Fator Madeiras, Fator Ferragens e Miscelânias, Fator Mão
de Obra, todos `decimal(5,2)`) — mesmo padrão dos campos já existentes.
Migration nova em ALTER
(`2026_08_30_150000_add_valor_pecas_fatores_to_referencias_precos_table`),
não tocando nas duas migrations anteriores já aplicadas.

### Decisão: os 4 campos novos ficam ocultos por padrão NA LISTAGEM (não no modal)

Antes desta tarefa a tabela já tinha 9 colunas de dados. Adicionar
mais 4 visíveis de cara deixaria a listagem larga demais pra leitura
rápida — decisão consciente de marcar os 4 novos com
`->toggleable(isToggledHiddenByDefault: true)`: continuam 100%
editáveis no modal e disponíveis na listagem via botão de alternar
colunas — só não aparecem de cara.

### Validado

Confirmado (mesma ressalva sobre `callAction()` já registrada acima)
que a criação via modal com os 12 campos (8 antigos + 4 novos) salva
tudo corretamente, sem erros. Log de `created` do Spatie Activitylog
confirmado com os 4 novos campos presentes em
`properties.attributes`; edição de um campo novo gerou log de
`updated` com `old`/`attributes` corretos (`logOnlyDirty`, só o campo
alterado). Rótulo/referência da Central de Auditoria inalterados, como
esperado — não dependem de quais colunas o Model tem.

---

## Remoção do campo "Revisão" de Obra — pertencia conceitualmente à Proposta (2026-09-01)

`obras.revisao` (`unsignedInteger`, default `0`) veio junto com a
numeração AAT#### desde a migration original de criação do cadastro,
mas — conforme `CONCEITO-OBRA-PROPOSTA-PROJETO.md` — revisões existem
na Proposta (e no Projeto), não na Obra: a Obra é a entidade raiz e
permanente, sem revisões próprias. Removido por completo (não só
ocultado), depois de confirmar ausência de uso real.

### Evidência levantada antes de remover (grep amplo, não suposição)

`grep -ril "revisao"`/`"revision"` em `plugins/perseu/comercial` e no
restante do repositório encontrou só: a migration original (coluna),
`Obra::$fillable`, o `Placeholder::make('revisao_display')`
somente-leitura em `ObraResource::form()`, e as duas chaves de
tradução. Nenhuma lógica de negócio, cálculo, Model relacionado, ou
policy referenciava a coluna. Confirmado especificamente que
`GeradorNumeroObra::gerar()` nunca leu/gravou `revisao` — o algoritmo
é inteiramente independente dela.

### O que mudou

Nova migration (`2026_09_01_100000_drop_revisao_from_obras_table`,
`dropColumn('revisao')`) — a migration original não foi editada.
`Obra::$fillable` teve `'revisao'` removida. `ObraResource::form()`
teve o `Placeholder` removido. Traduções removidas.

### Rebalanceamento do Grid — preservando o alinhamento com a Linha 2

O comentário já existente no `form()` documentava um invariante:
"Nome da Obra" (Linha 1) e o Select de Cliente (Linha 2) ficam
alinhados verticalmente porque ambos são o 2º item de cada
`Grid::make(12)` com a MESMA soma de `columnSpan` antes deles.
Remover `revisao` sem compensar deixaria essa soma desbalanceada —
corrigido aumentando `data_cadastro` de `columnSpan(1)` para
`columnSpan(2)` (em vez de `numero_obra`, já que uma data por extenso
ocupa mais espaço visual).

### Validado

`Schema::hasColumn('obras', 'revisao')` confirmado `false`. Criada uma
Obra de teste real: `numero_obra` gerado normalmente,
`data_cadastro` preenchido automaticamente. `Livewire::test(EditObra::class, ...)`
confirmou que o HTML renderizado não continha mais nenhuma ocorrência
de "Revis" e que `numero_obra` continuava exibido corretamente.

### Nota — Cluster "Propostas" implementado e revertido (2026-09-01)

Um primeiro desenho do Cluster "Propostas" (Situação de Proposta +
cabeçalho da Proposta, com "Revisão" voltando a existir ali) chegou a
ser implementado, validado e documentado logo após esta remoção — e
depois descartado por completo a pedido do usuário, que decidiu
repensar o desenho do zero, sem reaproveitar nada da primeira
tentativa. Nenhum arquivo, tabela, permissão ou entrada em
`SubjectTypeCatalog`/`TrashCatalog` daquela tentativa permanece no
projeto. Só a remoção do campo "Revisão" de Obra (documentada acima) e
uma correção de `hasMigrations()` em `ComercialServiceProvider.php`
sobreviveram dessa sessão de trabalho. Registrado aqui só pra quem for
desenhar o Cluster "Propostas" de novo no futuro não estranhar o
histórico do git — não há nenhum detalhe de design daquela tentativa
preservado de propósito.

---

## "Revisão" volta a existir em Obra — replanejamento: sem cadastro de Proposta separado, por ora (2026-09-02)

Depois de reverter a tentativa de Cluster "Propostas" (nota acima), o
usuário decidiu por um caminho mais simples por enquanto: "Revisão"
volta a existir, mas dentro do próprio cadastro de Obra, sem Model/
Resource separado. Ideia conceitual: a combinação "Obra + Revisão" já
representa o que seria uma "Proposta" — mas o nome do cadastro/menu
continua "Obra" (rename é decisão futura, em aberto).

### Mesmo comportamento exato de antes da remoção

`obras.revisao` — `unsignedInteger`, `default(0)`, sem NENHUMA lógica
de autoincremento (nunca teve) — exibido como `Placeholder`
somente-leitura zero-padded em 2 dígitos, mesmo texto/formato/posição
de antes (o `columnSpan(2)` que `data_cadastro` tinha ganhado
temporariamente pra compensar a ausência de Revisão foi revertido de
volta pra `columnSpan(1)`).

Migration nova (`2026_09_02_100000_add_revisao_back_to_obras_table`),
não um `down()` da migration de remoção (que continua intacta) — mesma
convenção de sempre.

`revisao` continua FORA do `$fillable` — não havia necessidade real de
editá-lo via formulário (o campo nunca teve, nem tem agora, um input
editável em lugar nenhum, só o `default(0)` da migration).

### Gap do `hasMigrations()` — não repetido desta vez

Aprendendo com o gap encontrado e corrigido na tarefa anterior (3
migrations que nunca tinham sido registradas), a migration nova desta
tarefa já foi adicionada a `ComercialServiceProvider::hasMigrations()`
no mesmo commit que a criou.

### Validado

`Schema::hasColumn('obras', 'revisao')` confirmado `true`. Criada uma
Obra de teste real: `numero_obra` gerado normalmente, `revisao`
persistido como `0` no banco (o valor em memória logo após
`Model::create()` aparece `null` até um `refresh()` ou nova consulta —
comportamento padrão do Eloquent pra colunas com `default()` no schema
que não são passadas explicitamente na inserção, não um bug; o
Placeholder do formulário sempre lê do registro já carregado via
rota/consulta fresca). `Livewire::test(EditObra::class, ...)` confirmou
o HTML renderizado mostrando "Revisão" com o valor "00". Navegação e
rotas de Obra conferidas sem nenhuma mudança.

---

## Rename interno Project → Processo no plugin webkul/projects (2026-09-02)

Passo 1 da "Decisão final de nomenclatura" registrada em
`CONCEITO-OBRA-PROPOSTA-PROJETO.md` (02/09/2026): a palavra "Projeto"
precisa ficar livre para a entidade de negócio de `perseu/comercial`
(hoje "Obra", rename `Obra → Projeto` ainda pendente, passo 2). Para
isso, a entidade interna do plugin NÚCLEO `webkul/projects`
(`Webkul\Project\Models\Project`, hoje "Projetos"/"Project" em todo o
código, apesar do rótulo do MENU já ter virado "Gestão de Processos"
numa tarefa anterior só de label em `lang/{locale}/admin.php`) foi
renomeada de ponta a ponta para Processo — Model, tabelas, colunas de
FK, Filament Resource, páginas, permissões Shield e traduções nos 4
idiomas. Diferente das tarefas de rename anteriores deste projeto
(Projeto→Obra), esta mexeu num plugin core do AureusERP (não um
`perseu/*`), então o cuidado foi maior: backup confirmado antes de
começar, e um levantamento explícito de outros pontos de integração
(`webkul/timesheets` re-confirmado sem uso real do Model `Project`, já
que ele só referencia a tabela genérica `analytic_records`) antes de
tocar em qualquer arquivo.

**Não confundir com `perseu/comercial`**: `Webkul\Project\*` (este
plugin, entidade interna "Processo") e `Perseu\Comercial\*` (Obra,
futuro "Projeto") são namespaces e tabelas totalmente independentes,
sem nenhuma FK entre si hoje — a única relação é conceitual (ver
"Relação entre Projeto (Comercial) e Processo (Gestão de Processos):
ainda não desenhada tecnicamente" no documento de conceito).

### Duas decisões de escopo tomadas com o usuário antes de codificar

1. **Camada de API REST e testes automatizados**: o plugin tem uma API
   REST própria (`Http/Controllers/API/V1/*`, `Http/Resources/V1/*`,
   `Http/Requests/*`, ~18 arquivos) e uma suíte de testes (`tests/`).
   Perguntado ao usuário se isso deveria ser renomeado junto —
   resposta: não, manter só Filament + Model + banco no escopo, já que
   nada no Perseu consome essa API hoje. `tests/` e as CLASSES/ROTAS da
   API (`ProjectController`, `ProjectRequest`,
   `Http\Resources\V1\ProjectResource`, rota
   `admin/api/v1/projects/projects`, etc.) foram mantidos exatamente
   como estavam.
2. **Descoberta no meio da tarefa**: essa mesma camada de API consome
   DIRETAMENTE o Model/colunas que estavam sendo renomeados (não é uma
   cópia) — sem nenhum ajuste, os ~18 arquivos quebrariam em runtime
   (`Class "Project" not found`, coluna `project_id` inexistente,
   etc.), mesmo mantendo suas próprias classes/rotas intactas. Levado
   de volta ao usuário antes de prosseguir (a superfície real, 18
   arquivos, era maior do que a estimativa inicial ao fazer a primeira
   pergunta) — decisão: corrigir só as referências internas desses 18
   arquivos (chamadas ao Model, nomes de coluna, `allowedIncludes`/
   `whenLoaded()` de relacionamento) o suficiente para não quebrar,
   preservando ao máximo nomes de classe/rota/campo do contrato JSON já
   existente. Única exceção de contrato aceita conscientemente: o campo
   `processo_id` no payload de entrada/saída da API (era `project_id`)
   e o include `processo`/`processo_id` como filtro — inevitável, já
   que a COLUNA em si mudou de nome.

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
`->processo`, `$this->pageFilters['selectedProjects']`→
`['selectedProcessos']`, lang keys `project`→`processo`).

### Migration — `Schema::rename()`/`renameColumn()`, mesma técnica do Projeto→Obra

`2026_09_02_100000_rename_project_to_processo.php`: tabelas primeiro,
colunas depois (já com o nome novo da tabela), FKs preservadas
automaticamente pelo MySQL/MariaDB. Incluiu
`analytic_records.project_id`→`processo_id` apesar de essa ser uma
tabela nominalmente "compartilhada" — confirmado por grep que as
colunas `project_id`/`task_id` dessa tabela são contribuídas SÓ pela
migration do próprio `webkul/projects`, não por `webkul/analytics` nem
nenhum outro plugin, então era seguro incluir no rename. `Timesheet
extends Record` (a subclasse que usa essa tabela) foi atualizada
junto. Migration com `down()` completo e testada rodando `up()`: as 4
tabelas novas existem, as 4 antigas sumiram, `processo_id` confirmado
presente em `projects_tasks`, `projects_milestones`,
`projects_task_stages`, `analytic_records`.

### Exceções conscientes — deixadas de propósito com o nome antigo

- **`Webkul\Project\Enums\ProjectVisibility`** (níveis
  privado/interno/público de visibilidade de um Processo) — CLASSE,
  ARQUIVO e chaves de tradução mantidos 100% intactos. Motivo duplo:
  (1) é usado tanto pela camada Filament (dentro do escopo) quanto por
  `Http\Requests\ProjectRequest.php` (fora do escopo, API preservada
  por decisão do usuário) — renomear forçaria tocar a API; (2) o texto
  traduzido de cada valor nunca conteve a palavra "Project"/"Projeto",
  então não haveria ganho visível ao renomear, só risco.
- **`Webkul\Project\Settings\TaskSettings::$enable_project_stages`**
  (Spatie `laravel-settings`) — a PROPRIEDADE PHP e a CHAVE de
  tradução foram mantidas sem mudança — renomear uma propriedade de
  settings exigiria uma migration de dados pro blob JSON armazenado,
  desproporcional ao escopo pedido. O VALOR exibido ao usuário, porém,
  foi atualizado pra dizer "processo" em vez de "projeto" nos 4
  idiomas — separação deliberada entre estabilidade do identificador
  interno e precisão do texto visível.
- **`protected static string $routePath = 'project';`** (`Dashboard`)
  e slugs com prefixo `project/` (`ProcessoResource::$slug =
  'project/processos'`, mesmo padrão em `ProcessoStageResource`) —
  mantidos assim de propósito: esse segmento é o namespace de rota
  COMPARTILHADO do plugin inteiro, não algo específico da entidade
  Processo — renomear quebraria/mudaria TODAS as URLs do plugin, muito
  além do escopo pedido. `route:list` confirmado inalterado nesse
  aspecto.
- **`ProjectPlugin`** (`Webkul\Project\ProjectPlugin`, classe Filament
  `Plugin` que registra os caminhos de descoberta do próprio plugin) —
  não renomeada: é o nome do PLUGIN INTEIRO (equivalente a "o pacote
  webkul/projects"), não da entidade Processo.

### Auto-correção durante a implementação: relação inversa de `ProcessoStage`

Ao escrever `ProcessoStage.php`, uma relação inversa `projects():
HasMany` (um Estágio tem muitos Processos) foi inicialmente OMITIDA
por engano. Só depois, investigando por que
`ProjectStageController`/`ProjectStageResource` (camada de API,
preservados) referenciam `'projects'`/`whenLoaded('projects')`, foi
confirmado via `git show HEAD:...ProjectStage.php` que essa relação JÁ
EXISTIA no código original committado — restaurada corretamente como
`public function processos(): HasMany { return
$this->hasMany(Processo::class, 'stage_id'); }`.

### Shield — mesmo fluxo já estabelecido (gerar, sincronizar, remover órfãs)

`shield:generate --resource=ProcessoResource,ProcessoStageResource
--panel=admin` processou 2 entidades e gerou 22 permissões novas
(guard `web` — confirmado que este plugin só usa permissões Shield no
guard `web`, diferente de `perseu/*` que também tem entradas `sanctum`
pra algumas delas). A permissão do widget renomeado
(`widget_project_top_processos_widget`) não é gerada por
`shield:generate` (widgets usam `HasWidgetShield`/
`getWidgetPermission()` próprio) — criada manualmente via tinker
(`Permission::firstOrCreate(...)`, guards `web` e `sanctum`,
espelhando os guards que a permissão antiga tinha). As 23 permissões
novas foram sincronizadas à role Admin via `givePermissionTo()`. As
permissões órfãs antigas (`*_project_project`/`*_project_project::stage`
nos guards `web` e `sanctum`, mais `widget_project_top_projects_widget`
nos dois guards — 35 linhas no total) foram removidas via `DELETE` SQL
direto (não `Permission::delete()` via Eloquent, mesma precaução da
tarefa Projeto→Obra, embora desta vez a exclusão direta via SQL não
tenha esbarrado no erro de guard `sanctum` encontrado naquela tarefa).
`permission:cache-reset` executado depois.

### Validado (tinker + Livewire, não só leitura de código)

1. Ciclo completo de criação (dentro de transação revertida, com
   usuário autenticado): `Processo::create()` → `TaskStage::create()`
   → `Task::create()` → `Milestone::create()`, todos com `company_id`
   derivado corretamente via `boot()`, relações
   `$processo->tasks()`/`->milestones()`/`->tags()` e a relação
   inversa `$stage->processos()` todas retornando os registros
   corretos; `$task->update(...)` disparando o listener `updated()`
   (sync de timesheets) sem erro.
2. Páginas Filament renderizadas via `Livewire::test()`: `ListProcessos`
   (contém "Processos", zero ocorrências de "Projetos"),
   `ManageProcessoStages` (contém "etapas do processo", zero "etapas
   do projeto"), `ListTasks` (coluna "Processo" presente) — todas sem
   exceção.
3. `route:list` conferido: as rotas da API (`admin/api/v1/projects/*`,
   incluindo `.../projects/projects`, `.../projects/project-stages`)
   continuam com os nomes/URIs antigos, exatamente como decidido.
4. `find ... | php -l` em todos os 84 arquivos PHP do plugin: nenhum
   erro de sintaxe.
5. Varredura final `grep -rn "Project"`/`"project_id"`/`"->project"` em
   `src/` (fora de `Http/`, camada preservada): só restam as exceções
   conscientes documentadas acima — nenhum resíduo acidental.
6. `ddev artisan optimize:clear` executado duas vezes (antes e depois
   do `shield:generate`).

### Pendência explícita (registrada, não corrigida aqui)

A camada de API REST (`Http/Controllers/API/V1/*`,
`Http/Resources/V1/*`, `Http/Requests/*`) e a suíte `tests/` continuam
com nomenclatura "Project"/"project" — decisão consciente, não uma
omissão. Se essa API algum dia passar a ser consumida por algum
sistema externo real, revisitar como uma tarefa própria de rename
(nesse ponto, o `processo_id` que já mudou no payload precisará ser
comunicado como breaking change aos consumidores). Ver `CLAUDE.md`
para o resumo do estado atual desta nomenclatura.
