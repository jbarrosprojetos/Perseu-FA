# Contexto do projeto Perseu

Este projeto é baseado no AureusERP, customizado para uma marcenaria
industrial (Perseu).

## Antes de qualquer tarefa de código, consulte:
- AUDITORIA-ESTRUTURA.md — como funciona controle de acesso, usuários,
  empresas e branding
- GUIA-CRIACAO-PLUGIN.md — passo a passo para criar novos plugins seguindo
  as convenções deste projeto

## Convenções e decisões do projeto:
- O cadastro de pessoas será feito como plugin próprio, com tabelas
  separadas para Pessoa Física e Pessoa Jurídica (não o modelo de
  tabela única "partners" do AureusERP original)
- Uma Categoria de Pessoa pode se aplicar a PF, PJ, ou ambos (relação
  muitos-para-muitos)
- Contatos ligam uma Pessoa Física a uma Pessoa Jurídica (representante)
- Usuários de login sempre se vinculam a uma Pessoa Física
- O plugin de Pessoas NÃO deve alterar o comportamento automático de criação
  de Partner ao salvar um User (código do core, em
  Webkul\Security\Models\User::boot()). Esse Partner "técnico" continua
  existindo por baixo dos panos, sem interface própria visível. As tabelas
  pessoas_fisicas/pessoas_juridicas são independentes e não substituem essa
  lógica do core — apenas coexistem com ela.

## Idioma
Todo o sistema deve ser traduzido/adaptado para português do Brasil,
incluindo campos específicos do Brasil (CPF, CNPJ, RG, Inscrição
Estadual) quando aplicável.

## Atenção: Resources duplicados entre plugins
Alguns Resources existem em mais de um plugin (ex: CompanyResource
existe em "security" E "support" — o de "support" é o que efetivamente
serve as rotas, o de "security" tem shouldRegisterNavigation=false).
Antes de editar um Resource, confirme com `route:list` ou busca por
nome de classe qual versão está realmente ativa, para não editar a
versão errada.

## Distinção entre Favicon e Logo (Branding)
No BrandSettings, "favicon" representa a identidade do PRODUTO Perseu
(o software em si) e é usado em lugares que remetem ao "sistema"
(ex: página de Ajuda). "light_logo"/"dark_logo" representam a
identidade da EMPRESA CLIENTE que usa o sistema (ex: topbar). Ao
adicionar imagens de marca em novas telas, considerar qual das duas
identidades faz sentido em cada contexto.

## Tema Bonsai (qalainau/bonsai-theme) sobrescreve gap/espaçamento com !important
O pacote `qalainau/bonsai-theme` está instalado e ativo neste projeto
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
usamos) — o problema é especificamente `gap`.

## Estrutura do plugin de Pessoas (plugins/perseu/pessoas)
- Organizado como Cluster "Pessoas" no menu principal, com 3 itens:
  Categorias, Pessoas Físicas, Pessoas Jurídicas.
- Contatos NÃO é um Resource/item de menu próprio — é um Relation
  Manager dentro da tela de edição de Pessoa Jurídica (mesmo padrão do
  BranchesRelationManager em Company).
- Endereços também não é item de menu — é uma tabela própria
  (enderecos) com pivots (pessoa_fisica_endereco,
  pessoa_juridica_endereco), exibida como Relation Manager dentro de
  Pessoa Física e Pessoa Jurídica. O tipo de endereço (Residencial,
  Comercial, Cobrança, etc.) é um enum PHP com valor inteiro, não uma
  tabela separada.
- Itens novos de menu podem ser promovidos depois, caso surja
  necessidade real (ex: uma listagem geral de contatos).

## Filtro de Tipo de Endereço por contexto (implementado na Fase 3)
No Select de "tipo" do Relation Manager de Endereços, as opções do enum
TipoEndereco são filtradas conforme o contexto:
- Pessoa Física: Residencial, Cobrança, Entrega, Outro
- Pessoa Jurídica: Comercial, Cobrança, Entrega, Obra, Outro
O enum continua com todas as opções; a tela apenas restringe visualmente
o que faz sentido em cada caso.

`tipo` e `principal` são colunas do PIVOT (`pessoa_fisica_endereco`/
`pessoa_juridica_endereco`), não da tabela `enderecos` — por isso não
há cast de enum no Eloquent por padrão (o model `Endereco` não tem
`$casts` para `tipo`, e não existe uma classe de Pivot customizada).
No Relation Manager, `TextColumn::make('pivot.tipo')` usa
`->formatStateUsing(fn ($state) => TipoEndereco::tryFrom((int) $state)?->getLabel())`
para mostrar o label traduzido em vez do inteiro cru.

Os dois Relation Managers de Endereços (um em cada Resource, já que o
filtro de `tipo` difere) reaproveitam o mesmo form()/table() via o
trait `Perseu\Pessoas\Traits\HasEnderecoRelationManagerSchema`
(`plugins/perseu/pessoas/src/Traits/HasEnderecoRelationManagerSchema.php`).
A classe concreta só declara `$relationship = 'enderecos'` e implementa
`translationPrefix()` e `tipoEnderecoOptions(): array<TipoEndereco>`
(os únicos dois pontos que variam entre PF e PJ). O campo `cep` usa
`->live(onBlur: true)->afterStateUpdated(...)` chamando a API pública
ViaCEP (`https://viacep.com.br/ws/{cep}/json/`, sem autenticação) para
preencher `logradouro`/`bairro`/`municipio`/`uf` automaticamente — a
chamada é envolta em `try/catch` e checa `$response->successful()`
antes de usar o resultado, já que é uma dependência de rede externa
que pode falhar.

Como `enderecos()` é `belongsToMany(...)->withPivot('tipo', 'principal')`,
o `CreateAction`/`EditAction` do Filament já separam automaticamente os
campos do pivot dos campos do model `Endereco` ao salvar
(`Filament\Actions\{Create,Edit}Action` verificam
`$relationship->getPivotColumns()` quando a relationship é
`BelongsToMany`) — não foi preciso nenhum `mutateFormDataUsing` manual
para isso.

## Regra de largura de campos em formulários

Convenção de largura visual dos campos:
- Campos de conteúdo curto/formato conhecido (telefone, CPF, CNPJ, RG,
  CEP, DatePicker, Select/dropdown, Toggle) devem ter largura visual
  LIMITADA (não esticar para preencher a coluna inteira), mesmo em
  telas grandes.
- Campos de conteúdo livre/variável (nome, e-mail, profissão,
  observações, textos longos) devem ocupar a largura normal da coluna
  (sem limitação).

Isso é implementado pelo trait `Perseu\Pessoas\Traits\HasCompactFieldWidth`
(`plugins/perseu/pessoas/src/Traits/HasCompactFieldWidth.php`), reaproveitado
por qualquer Resource que `use` o trait. Exemplo aplicado:
`PessoaFisicaResource::form()` — deve ser reaproveitado no Resource de
Pessoa Jurídica (Fase futura) e em qualquer outro Resource do plugin de
Pessoas com campos desse tipo (CNPJ, Inscrição Estadual, CEP nos Relation
Managers de Endereços, etc.). Se um plugin FORA de `perseu/pessoas`
precisar do mesmo helper, promova o trait para um local compartilhado
nesse momento (não antecipar isso agora).

### Layout da linha: Grid vs. `static::flexRow()`

Nunca aninhar `Grid::make()` dentro de outro `Grid::make()` — o Grid
interno ocupa a célula inteira do Grid externo como um único
componente, empurrando os componentes seguintes da grid externa para
fora da linha.

- **Linha só com campos compactos, ou colunas de largura igual (nenhuma
  precisa "absorver" o espaço sobrando)**: `Grid::make(N)` com os
  campos diretamente dentro (`columnSpan()` em cada campo quando as
  colunas não forem iguais). Espaço vazio sobrando ao final da linha é
  aceitável aqui, já que não há um campo "normal" para preenchê-lo.
- **Linha que mistura campos compactos com um campo de largura normal
  que deve ocupar o espaço restante** (ex.: Telefone compacto + E-mail
  normal): um `Grid` de colunas iguais deixaria espaço vazio dentro da
  coluna do campo compacto. Use `static::flexRow([...])` em vez disso
  — usa o componente oficial `Filament\Schemas\Components\Flex`
  (não uma Grid disfarçada de `flex` via CSS; ver nota abaixo).

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
                    chars: 10, extraSlack: 2, // "10/11/1971" + ícone de calendário
                ),
                static::compactByLabel(
                    Toggle::make('ativo'), // Toggle não tem ".fi-input-wrp": largura vem só do label
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

A largura NÃO é um valor de pixel fixo — é calculada em caracteres
esperados, na unidade CSS `ch` (largura do caractere "0" na fonte
atual): `largura = (chars + folga) . "ch"`. A folga padrão é +2ch
(acomoda o padding interno do input); campos com ícone dentro do input
(ex.: calendário do DatePicker) somam uma folga extra via `extraSlack`.

O `chars` passado nunca é a largura final sozinho: o label do campo
(`->label()`, texto já traduzido) não quebra linha, então se for mais
largo que o valor esperado ele "vaza" visualmente para o campo vizinho
mesmo com a caixa do input tendo espaço de sobra (aconteceu com "Data
de Nascimento", label de 18 caracteres vs. os 10 caracteres do valor
"10/11/1971"). Por isso a largura final é sempre
`max(chars, comprimento do label) + folga`, calculada automaticamente
via `$component->getLabel()` — não calcule isso à mão ao chamar esses
métodos.

- `static::compact($component, chars: N, extraSlack: 0)` — para campos
  com `.fi-input-wrp` (TextInput, Select, DatePicker/DateTimePicker).
  Aplica `max-width` no `.fi-input-wrp` (a caixa com borda/fundo
  visíveis — **não** no `<input>` em si, ver nota abaixo) e
  `->grow(false)`.
- `static::compactByLabel($component, extraSlack: 0)` — mesma lógica,
  para campos sem `.fi-input-wrp` próprio cuja largura vem só do label
  (ex.: `Toggle`, que renderiza um `<button>` de tamanho fixo). Aplica
  `max-width` no wrapper do campo (`.fi-fo-field`, o item flex em si)
  via `->extraFieldWrapperAttributes()` e `->grow(false)`.
- `static::maxEnumLabelChars(EnumClass::class)` — calcula dinamicamente
  (via `mb_strlen` sobre `getLabel()` de cada `case`) o comprimento do
  maior label de um enum `HasLabel`, para Selects cuja largura deve
  acompanhar o enum sem precisar contar caracteres na mão.

Ao calcular `chars`, use o tamanho real da máscara/formato esperado
(ex.: CPF `999.999.999-99` = 14, telefone `(99) 99999-9999` = 15); para
campos sem máscara fixa (ex.: RG), use um valor razoável baseado no
padrão mais comum, comentando a máscara/exemplo de referência no
código. Se o cálculo dinâmico via enum não for viável para algum caso,
pode-se usar um valor fixo, mas comente no código que ele deve ser
revisado caso o enum ganhe um novo case maior.

**Nota — `->extraInputAttributes()` NÃO dá a largura visual correta**
(estiliza só o `<input>`/`<select>`, cuja caixa com borda/fundo visível
é na verdade o `.fi-input-wrp` que o envolve — limitar só o input deixa
espaço vazio dentro da borda). `->maxWidth()`, disponível na base
`Component`, também não funciona para isso (só é consumido por
containers como Modal/Slideover, sem efeito em campos de formulário).
O mecanismo correto é `->extraAttributes(['style' => 'max-width:
Nch;'])`, que é o que `static::compact()`/`static::compactByLabel()`
já usam internamente — não chame `extraInputAttributes()` ou
`maxWidth()` manualmente para esse fim.

### `static::flexRow()`: gap do container + margem por campo

`static::flexRow($components)` monta `Flex::make($components)->dense()`
e adiciona dois espaçamentos, ambos fixados via `style` inline (não
classes Tailwind, que exigiriam rebuild de CSS):

- `gap: 2ch` no container (`.fi-sc-flex`) — `->dense()` sozinho só
  alterna entre dois valores fixos do Filament (`gap-6`/`gap-3`, sem
  setter numérico), insuficiente para o label de um campo não esbarrar
  no vizinho.
- `margin-right: 2ch` em CADA campo da linha (`.fi-fo-field`, via
  `->extraFieldWrapperAttributes(merge: true)`), como reforço além do
  gap do container — necessário porque a caixa de um campo pode ser
  exatamente do tamanho do seu conteúdo (ver cálculo de largura acima),
  sem respiro próprio.

Todo campo passado para `static::flexRow()` fica `fi-growable`
(`flex-1`) **por padrão**, a não ser que `->grow(false)` seja chamado —
`Flex::toEmbeddedHtml()` chama `$schemaComponent->canGrow()` sem
argumento, e `CanGrow::canGrow(bool $default = true)` retorna `true`
quando `grow()` nunca foi chamado. `static::compact()` e
`static::compactByLabel()` já chamam `->grow(false)` internamente, e
`static::grow()` chama `->grow()` (true) explicitamente — ou seja, todo
campo de uma `flexRow()` deve passar por um desses três helpers; um
campo cru (sem nenhum deles) herda o `grow()` padrão do Flex e vai
esticar, disputando espaço com o campo marcado por `static::grow()`.

**Nota — `Group::make()->extraAttributes(['class' => 'flex ...'])` não
é alternativa válida ao `Flex`**: não funciona, porque `Schema::toHtml()`
sempre envolve a lista de componentes filhos na sua própria
`<div class="fi-grid">` (CSS Grid de 1 coluna por padrão) antes de
chegarem ao container pai — o único filho real da div `flex` do
`Group` acaba sendo essa grid interna de 1 coluna, e os campos
continuam empilhados verticalmente em vez de lado a lado. O componente
`Flex` (usado por `static::flexRow()`) renderiza os filhos
diretamente, sem essa grid interna.

### Divisores entre o formulário e os Relation Managers (páginas de Edit)

Numa página `EditRecord` que tem Relation Manager(s), o Filament monta a
página com `EditRecord::content()` empilhando dois componentes
independentes: `getFormContentComponent()` (o `form()` do Resource +
botões Salvar/Cancelar, que são o `->footer()` desse componente, não
um item separado) e `getRelationManagersContentComponent()` — ou seja,
o Relation Manager é renderizado **fora** do `form()` do Resource,
mesmo aparecendo logo abaixo dele na tela. Isso explica por que não dá
pra resolver o espaçamento só editando `form()`: os pontos de costura
ficam nesses dois métodos da página (`Filament\Resources\Pages\EditRecord`),
não no Resource.

Não existe um componente `Divider` dedicado em `filament/schemas` — o
jeito idiomático de colocar uma linha simples é `Html::make('<hr ...>')`
(`Filament\Schemas\Components\Html`), com as mesmas classes de borda que
o próprio Filament usa em outros lugares (`border-gray-200
dark:border-white/10`, que já se adaptam ao dark mode).

Solução reutilizável: trait `Perseu\Pessoas\Traits\HasRelationManagerDividers`
(`plugins/perseu/pessoas/src/Traits/HasRelationManagerDividers.php`), para
`use` na página `Edit{Xxx}` de qualquer Resource com Relation Manager:

```php
use Perseu\Pessoas\Traits\HasRelationManagerDividers;

class EditPessoaJuridica extends EditRecord
{
    use HasRelationManagerDividers;

    protected static string $resource = PessoaJuridicaResource::class;
    // ...
}
```

O trait sobrescreve dois métodos:
- `getFormContentComponent()` — **sem linha `<hr>` aqui**, só espaço:
  entre o fim do `form()` embutido (`EmbeddedSchema::make('form')`) e o
  `->footer()` com os botões Salvar/Cancelar, o espaço vem de
  `->extraAttributes(['style' => 'gap: 6rem;'])` no próprio
  `Form::make(...)`. Não dá pra alcançar "antes dos botões" de outro
  jeito, já que o footer é interno a esse componente. Esse `gap`
  sobrescreve o `gap-6` (24px) padrão do Filament: `<form
  class="fi-sc-form">` é `flex flex-col gap-6` (`filament/schemas`,
  `resources/css/components/form.css`) — esse gap é o que efetivamente
  separa os dois filhos diretos do flex (o bloco do formulário
  embutido inteiro — TODOS os campos, como um único `.fi-grid` — e o
  bloco do `->footer()`), então sobrescrevê-lo diretamente (mesma
  técnica do `gap` do `Flex` em `HasCompactFieldWidth::flexRow()`) é o
  jeito confiável de controlar esse espaço — colocar mais margem num
  `<hr>` dentro do formulário embutido não teve o mesmo efeito visual
  (foi tentado antes e removido).

  O valor subiu de `4rem` para `6rem` depois que a Section placeholder
  "Endereços" foi removida do `form()` (Fase 3, substituída pelo
  Relation Manager de Endereços): o `gap` em si nunca deixou de
  funcionar (ele separa os dois filhos do flex, não depende de qual
  campo é o último dentro do formulário embutido), mas a Section tinha
  peso visual próprio (`.fi-section`: padding, borda, sombra,
  título+descrição) logo antes do respiro — sem ela, o último campo
  (`Textarea` de Observações, uma caixa bem mais compacta) faz o
  formulário terminar de forma mais abrupta, e o mesmo respiro em
  pixels passa a parecer insuficiente. Se o último campo do `form()`
  mudar de novo no futuro, reavaliar se `6rem` ainda é suficiente.
- `content()` — insere um bloco com duas linhas (`<hr><hr>`, 2px cada,
  `my-12 space-y-4`) entre `getFormContentComponent()` e
  `getRelationManagersContentComponent()`, **e** envolve o Relation
  Manager num `Group::make([...])->extraAttributes(['class' => ...])`
  com fundo levemente acinzentado (`rounded-xl bg-gray-50 p-6
  dark:bg-white/5`) — as mesmas classes que o próprio `Section` do
  Filament usa na sua variante `->secondary()` (`.fi-section.fi-secondary`
  em `filament/support`), reaproveitadas em vez de inventar uma cor. Uma
  linha fina sozinha não bastou para o bloco parecer visualmente
  separado do formulário; o fundo + linhas mais grossas + mais margem
  resolveram isso.

Aplicado em `EditPessoaJuridica` (Relation Managers de Endereços e
Contatos) e `EditPessoaFisica` (Relation Manager de Endereços, desde a
Fase 3). Reaproveitar em qualquer página `Edit{Xxx}` futura que ganhe
seu primeiro Relation Manager.

## Flags de sistema em Categoria de Pessoa — decisão consciente de escopo limitado

A tabela `categorias_pessoa` tem duas colunas booleanas dedicadas,
além dos campos de aplicação a PF/PJ: `e_cliente` e `e_fornecedor`
(ambas `boolean`, default `false`, adicionadas em migrations
separadas — `2026_08_22_100000_add_e_cliente_to_categorias_pessoa_table`
e `2026_08_22_100001_add_e_fornecedor_to_categorias_pessoa_table`).

Essas são, deliberadamente, as **únicas** flags de sistema fixas nessa
tabela. A razão: "Cliente" e "Fornecedor" são os dois papéis que
módulos do sistema precisam poder filtrar de forma confiável e
estável — o plugin Comercial (futuro) precisa saber quem pode ser
Contratante em Projetos a partir de `e_cliente`; o módulo de Compras
(futuro) precisará de `e_fornecedor` de forma análoga. Um filtro assim
não pode depender de nomenclatura livre da categoria (ex: confiar que
uma categoria chamada "Clientes" sempre vai se chamar exatamente
"Clientes") — precisa de uma coluna estável e sem ambiguidade.

Qualquer outro papel ou classificação adicional que apareça no futuro
(ex: "Parceiro", "Representante", "Transportadora") **não** deve virar
automaticamente um novo campo `e_algumaCoisa` na tabela. O caminho
padrão para isso é uma Categoria de Pessoa comum (uma "tag" livre, sem
flag de sistema dedicada) — o cadastro de Categorias já suporta isso
sem qualquer alteração de schema. Antes de adicionar um novo campo
booleano fixo do mesmo tipo de `e_cliente`/`e_fornecedor`, reavaliar
essa decisão com o cliente: o custo de uma migration + coluna
dedicada só se justifica quando um módulo do sistema realmente precisa
filtrar por esse papel de forma confiável, não para toda classificação
que surgir.

Ponto de atenção para consultas futuras/manual do sistema: se alguém
pedir "adicionar uma flag para categoria X", a resposta padrão é
perguntar se existe um módulo do sistema que vai *filtrar* por essa
categoria de forma programática — se não existir ainda, a Categoria
comum (sem flag) resolve o caso sem crescer o schema.

## Reaproveitamento de utilitários entre os plugins Pessoas e Comercial

O plugin Comercial já depende diretamente de models do plugin Pessoas
desde a Fase 1 (`PessoaFisica`, `PessoaJuridica`, `Endereco`, `Contato`
— FKs de `projetos` apontam para tabelas do Pessoas). Essa dependência
de Comercial → Pessoas é uma decisão arquitetural já assumida deste
projeto (Comercial é construído sobre o cadastro de Pessoas, não um
plugin independente e reutilizável fora deste contexto) — não é
acoplamento acidental.

Com base nisso, quando o `createOptionForm` de Endereço em
`ProjetoResource` (Comercial) precisou da mesma busca automática de CEP
via ViaCEP que já existia em
`Perseu\Pessoas\Traits\HasEnderecoRelationManagerSchema` (usada pelos
Relation Managers de Endereços de PessoaFisica/PessoaJuridica), a lógica
foi **extraída** (não duplicada) para
`Perseu\Pessoas\Support\ViaCepLookup::fill(Set $set, ?string $cep)` —
uma classe utilitária pura, sem estado, sem depender de Model/Resource
específico. O trait `HasEnderecoRelationManagerSchema` foi atualizado
para delegar a essa classe em vez de manter sua própria cópia
(`fillAddressFromCep()` foi removido).

Por que extrair para uma classe própria em vez de simplesmente chamar
`HasEnderecoRelationManagerSchema` direto do Comercial (o método já era
estático): o nome do trait é `...RelationManagerSchema` — chamá-lo a
partir de um Resource comum (`ProjetoResource`, que não é um Relation
Manager e não tem colunas de pivot `tipo`/`principal`) seria uma
referência enganosa para quem lesse o código depois. `ViaCepLookup` tem
nome neutro e escopo mínimo (só a consulta ViaCEP + preenchimento dos
campos), então pode ser chamado de qualquer formulário do projeto que
tenha os campos `cep`/`logradouro`/`bairro`/`municipio`/`uf`, dentro ou
fora do plugin Pessoas, sem herdar nada que não faça sentido no
contexto de quem chama.

Se um plugin fora de `perseu/pessoas`/`perseu/comercial` precisar dessa
mesma lógica no futuro, isso já está resolvido — `ViaCepLookup` não tem
nenhuma dependência do plugin Comercial nem de Relation Managers.

## Controle de visibilidade da Debugbar via Role com Guard Sanctum

A barra de Debug (`barryvdh/laravel-debugbar`, mostra queries SQL,
models carregados, tempo de execução, etc.) **não** é controlada por
`APP_DEBUG` neste projeto — ela fica condicionada a o usuário
autenticado possuir pelo menos uma Role (Função) com
`guard_name = 'sanctum'`, independente do que estiver configurado no
`.env`.

### Mecanismo

- `Webkul\Security\Models\User::$guard_name = ['web', 'sanctum']` (ver
  AUDITORIA-ESTRUTURA.md) — um usuário pode ter roles em qualquer um
  dos dois guards. `HasRoles::roles()` (Spatie Permission) retorna as
  roles do usuário em QUALQUER guard, sem filtrar — o filtro por
  `guard_name = 'sanctum'` é feito manualmente
  (`$user->roles()->where('guard_name', 'sanctum')->exists()`).
- Middleware `App\Http\Middleware\ControlDebugbarVisibility`, registrado
  no array `->authMiddleware([Authenticate::class,
  ControlDebugbarVisibility::class])` de `AdminPanelProvider` (roda
  DEPOIS do `Authenticate`, garantindo `$request->user()` já resolvido)
  — a cada requisição autenticada, chama
  `app(Fruitcake\LaravelDebugbar\LaravelDebugbar::class)->enable()` se o
  usuário tiver a role, ou `->disable()` caso contrário.
- `config/debugbar.php` ganhou a chave `'force_allow_enable' =>
  env('DEBUGBAR_FORCE_ALLOW_ENABLE', true)` (ausente na versão
  publicada originalmente, mas já suportada pelo pacote). Sem isso,
  `Fruitcake\LaravelDebugbar\LaravelDebugbar::canBeEnabled()` retorna
  `false` sempre que `APP_DEBUG=false` (ou `APP_ENV` for
  `production`/`testing`), e nesse caso o
  `ServiceProvider::boot()` do pacote faz um *early return* — os
  listeners que injetam a barra na resposta (`RequestHandled`) e as
  rotas de assets nunca chegam a ser registrados. Chamar
  `Debugbar::enable()` em runtime não teria nenhum efeito nesse cenário,
  porque a "cola" que liga a Debugbar à resposta HTTP simplesmente não
  existiria — daí essa chave precisar ficar sempre `true` pro controle
  por Role funcionar de fato independente de `APP_DEBUG`.

### Como habilitar a Debugbar para um usuário

1. Configurações > Funções > Nova Função.
2. Preencher "Guard" com **Sanctum** (não "Web" — as roles usadas para
   permissões normais do painel usam guard `web`; esta é
   deliberadamente uma role "técnica" separada, sem nenhuma permissão
   de Filament Shield associada a ela).
3. Salvar e atribuir essa Função ao usuário (Segurança > Usuários >
   editar o usuário > aba de Roles).
4. Da próxima requisição em diante (não precisa logout/login), a barra
   aparece para esse usuário.

Uma role "Sistema" (guard `sanctum`) já existe neste ambiente para
esse fim.

### Por que por Role/Guard em vez de só `APP_DEBUG`

`APP_DEBUG` é global — vale para TODOS os usuários e expõe a Debugbar
(que mostra queries SQL, stack traces, etc.) para qualquer um com
acesso ao painel enquanto estiver ligado, inclusive em ambientes tipo
staging. Condicionar por Role permite ligar a barra para uma pessoa
específica (ex: o próprio desenvolvedor investigando um problema
pontual) sem expor esse nível de detalhe a todo mundo — inclusive em
produção, se necessário, sem precisar mexer no `.env` nem reiniciar
nada.

## Roadmap — Geração de PDF de Proposta (pendente)
Ao final do fluxo comercial (Projeto + itens de orçamento, ainda não
desenvolvidos), o sistema deve gerar um PDF de proposta para o
cliente, no estilo do documento real da F.A. Marcenaria (cabeçalho
com dados da obra/contratante/contratada, tabela de itens/serviços
com valores, condições de pagamento, cláusulas de multa/rescisão,
assinaturas). O formulário de cadastro em si NÃO precisa espelhar
visualmente esse documento — só captar os dados necessários para
gerá-lo. Avaliar o pacote barryvdh/laravel-dompdf (já presente no
composer.json do projeto) para essa geração.

## Decisão pendente: plugin de Tarefas (webkul/projects) + integração
Decidido manter o plugin "projects" do AureusERP instalado (não é
mais só teste), para servir dois propósitos: (1) tarefas/iniciativas
internas criadas manualmente pela direção (ex: mutirão de limpeza,
inventário, prospecção de clientes) e (2) tarefas automáticas geradas
pelo nosso Comercial\Projeto, vinculadas a uma Obra real.

Duas questões em aberto, a decidir DEPOIS que o plugin estiver
estável e em uso real (não decidir prematuramente):

1. Vínculo entre nosso Perseu\Comercial\Projeto e o Project do plugin
   de tarefas: Opção A (criar um Project espelho por Obra, mais
   organizado, exige sincronização) vs. Opção B (um Project único
   "guarda-chuva", mais simples, referência só em texto). Definir
   quando formos implementar a automação de criação de tarefas,
   analisando o momento certo de disparo (ex: mudança de Situação do
   Projeto).

2. Nome final: cogitando renomear nosso Perseu\Comercial\Projeto para
   "Obra" ou "Proposta" (mantendo o plugin de tarefas como
   "Projetos"/"Projects"), OU manter nosso nome como está e renomear
   o plugin de tarefas para algo tipo "Tarefas". Decisão a tomar após
   uso real de ambos, para ver qual nomenclatura reflete melhor o
   dia a dia da empresa.
