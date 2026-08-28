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

## Tema Bonsai (qalainau/bonsai-theme) — REMOVIDO definitivamente em 2026-08-24
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
benefício do tema. **Não está mais ativo no projeto.** O restante desta
seção é mantido como registro histórico da investigação (útil se algo
parecido for cogitado no futuro), não como estado atual.

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
pela nossa Comercial\Obra (ver "Rename Projeto → Obra" abaixo — nome
já resolvido), vinculadas a uma Obra real.

Questão em aberto, a decidir DEPOIS que o plugin estiver estável e em
uso real (não decidir prematuramente): vínculo entre nossa
Perseu\Comercial\Obra e o Project do plugin de tarefas — Opção A
(criar um Project espelho por Obra, mais organizado, exige
sincronização) vs. Opção B (um Project único "guarda-chuva", mais
simples, referência só em texto). Definir quando formos implementar a
automação de criação de tarefas, analisando o momento certo de
disparo (ex: mudança de Situação da Obra).

(A segunda questão que existia aqui — nome final do cadastro — está
resolvida, ver seção seguinte.)

## Rename "Projeto" → "Obra" no plugin `perseu/comercial` (2026-08-28)

Decisão de nomenclatura citada acima como pendente desde o handoff
original do projeto está **resolvida**: o cadastro de negócio chamado
"Projeto" virou **"Obra"**, que é a função real desse cadastro (obras
de marcenaria da F.A. Marcenaria) — não só o texto da tela, um rename
completo (Model, tabela, colunas, namespace, rotas, permissões,
traduções). Decisão consciente durante a implementação: o rename
cobriu também `SituacaoProjeto`→`SituacaoObra` e `TipoProjeto`→
`TipoObra` (a tarefa original só citava o cadastro principal
explicitamente, mas deixar "Situação do Projeto"/"Tipo do Projeto" na
tela depois do cadastro principal virar "Obra" seria inconsistente —
confirmado com o usuário antes de ampliar o escopo).

**Continua valendo, sem mudança**: o plugin de Tarefas
(`webkul/projects`) tem sua própria entidade "Project"/"Task" em
inglês, **totalmente separada** — não foi tocado nesta tarefa (ver
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
referencia. Sem efeito funcional (nome de constraint não é visível na
UI nem usado em código); não foi corrigido por ser cosmético e exigir
DROP/ADD CONSTRAINT (mais invasivo que o benefício justifica).

A migration antiga que criava a tabela (`..._create_projetos_table`)
**não foi editada** — já tinha rodado; reverter/renomear uma migration
já aplicada é sempre uma migration nova.

### Permissões Shield — geradas de novo, antigas removidas (não deixadas soltas)

`shield:generate --resource=ObraResource,SituacaoObraResource,TipoObraResource`
gerou as novas chaves (`view_any_comercial_obra`,
`view_any_comercial_situacao::obra`, `view_any_comercial_tipo::obra`,
etc. — o `::` vem da convenção de geração de chave deste projeto pra
nomes de Resource com mais de uma palavra, ver seção de Auditoria
acima). `shield:generate` sozinho **não sincroniza com a role Admin**
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

`LogsBusinessActivity` (trait, ver seção de Auditoria) e
`ActivitylogRelationManager` continuaram funcionando normalmente só
por estarem presentes em `Obra`/`ObraResource` com os nomes novos —
nada precisou ser reconfigurado "do zero", confirmado criando uma Obra
de teste, editando, e conferindo o log de atividade (`causer`/
`changes` corretos) e o ciclo completo de Lixeira (soft-delete →
aparece no `TrashedFilter` → `Restaurar` → `Excluir Permanentemente`,
com o log de atividade da própria exclusão continuando em
`activity_log`, tabela separada, mesmo depois do registro sumir de
`obras`).

## Navegação de módulos com múltiplos itens irmãos: grupo compartilhado "achatado", não Cluster — histórico e mecanismo correto (2026-08-24)

Esta seção documenta a investigação completa (duas tentativas, a
primeira incorreta) de como fazer Categorias/Pessoas Físicas/Pessoas
Jurídicas (grupo "Pessoas") e Projetos/Situações/Tipos de Projeto
(grupo "Comercial") aparecerem juntos DENTRO da topbar colorida do
painel, na mesma linha do nome do módulo — igual a "Projetos" (plugin
`webkul/projects`) e "Estoque" (`webkul/inventories`).

### Tentativa 1 (INCORRETA, revertida): `SubNavigationPosition::Top`

A primeira correção aplicada (`PessoasCluster`/`ComercialCluster`
declarando `protected static ?SubNavigationPosition
$subNavigationPosition = SubNavigationPosition::Top;`) produzia algo
DIFERENTE do pedido: abas "em pílula" (`x-filament-panels::page.sub-navigation.tabs`)
renderizadas ABAIXO do cabeçalho da página, como um bloco separado —
não itens dentro da própria topbar. Essa propriedade controla a
**sub-navegação de página** (`Filament\Pages\Concerns\HasSubNavigation`,
só existe quando a página pertence a um Cluster via `$cluster =
XxxCluster::class`), que é um mecanismo TOTALMENTE INDEPENDENTE da
topbar principal do painel. Essa tentativa foi revertida por completo.

### Mecanismo correto: dropdown da topbar principal, agrupado por `NavigationGroup`

O painel Admin tem `->topNavigation()` habilitado
(`app/Providers/Filament/AdminPanelProvider.php`, também citado em
AUDITORIA-ESTRUTURA.md) — com isso, `Filament::getNavigation()`
(`vendor/filament/filament/resources/views/livewire/topbar.blade.php`)
itera todos os `NavigationGroup`s registrados no painel e, para cada
grupo com label (nosso painel sempre dá label a todos via
`->navigationGroups(...)` em `AdminPanelProvider`, um por caso do enum
`Webkul\Support\Enums\NavigationGroup`), renderiza um
`<x-filament::dropdown>` na topbar com o nome do grupo, contendo TODOS
os itens de navegação (`NavigationItem`) que declaram esse mesmo
`getNavigationGroup()`.

**Isso é automático e não exige nenhuma configuração além de múltiplos
Resources/Pages/Clusters de nível superior declararem o MESMO
`getNavigationGroup()`** — não há necessidade de um Cluster "guarda-
chuva" para isso. Confirmado renderizando o loop real do
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
`webkul/projects` é clusterizado sob um "Project" Cluster (esse Cluster
não existe) — `ProjectResource`/`TaskResource` declaram
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

`PessoasCluster` e `ComercialCluster` foram **removidos** (arquivos
`plugins/perseu/pessoas/src/Filament/Clusters/PessoasCluster.php` e
`plugins/perseu/comercial/src/Filament/Clusters/ComercialCluster.php`
apagados, junto dos lang files dedicados a eles em
`resources/lang/{pt_BR,en}/clusters/{pessoas,comercial}.php`, que só
continham o label do Cluster). Os seis Resources deixaram de ser
clusterizados:
- `CategoriaPessoaResource`, `PessoaFisicaResource`,
  `PessoaJuridicaResource` (plugin Pessoas)
- `ProjetoResource`, `SituacaoProjetoResource`, `TipoProjetoResource`
  (plugin Comercial)

removendo `protected static ?string $cluster = PessoasCluster::class;`
(ou `ComercialCluster::class`) de cada um, e adicionando diretamente:
```php
public static function getNavigationGroup(): string|\UnitEnum
{
    return NavigationGroup::Pessoas; // ou ::Comercial
}
```
(mesmo padrão de `ProjectResource`/`TaskResource`/`Configurations` em
`webkul/projects` e dos 5 Clusters + `Overview` em
`webkul/inventories`.)

Como o slug de um Resource clusterizado ganha o prefixo do Cluster
automaticamente (`Cluster::prependClusterSlug()`), e isso deixa de
existir ao remover `$cluster`, cada Resource passou a declarar o slug
completo manualmente — mesma técnica já usada por
`ProjectResource::$slug = 'project/projects'` — para preservar as URLs
exatamente como eram antes (confirmado via `route:list` depois da
mudança, nenhuma URL mudou):
- `CategoriaPessoaResource::$slug = 'pessoas/categoria-pessoas'`
- `PessoaFisicaResource::$slug = 'pessoas/pessoas-fisicas'`
- `PessoaJuridicaResource::$slug = 'pessoas/pessoas-juridicas'`
- `ProjetoResource::$slug = 'comercial/projetos'`
- `SituacaoProjetoResource::$slug = 'comercial/situacao-projetos'`
- `TipoProjetoResource::$slug = 'comercial/tipo-projetos'`

Os dois `config/filament-shield.php` (`perseu/pessoas` e
`perseu/comercial`) tinham uma seção `'pages' => ['exclude' =>
[PessoasCluster::class]]` (ou `ComercialCluster::class`) — necessária
antes porque o Cluster "puro" (sem nenhuma Page autônoma apontando
para ele) escapava da heurística de auto-exclusão do Shield e viraria
um toggle de permissão morto na tela de Funções. Com o Cluster
removido, essa seção inteira (import + `'pages'`) foi apagada — não há
mais nenhum Cluster para excluir.

Os diretórios `Filament/Clusters/Pessoas/Resources/*` e
`Filament/Clusters/Comercial/Resources/*` foram MANTIDOS como estão
(não foi feito rename de pasta/namespace) — Filament não usa convenção
de pasta para resolver Cluster, só a propriedade `$cluster` (ver
`Filament\Resources\Resource\Concerns\BelongsToCluster::getCluster()`,
puramente `return static::$cluster;`), então o nome da pasta
"Clusters/Pessoas"/"Clusters/Comercial" hoje é só um artefato
cosmético do histórico do código, sem efeito funcional. Se algum dia
incomodar, um rename de namespace é seguro mas não foi feito aqui para
não ampliar o escopo desta correção.

Verificado após a correção:
1. `route:list` — todas as URLs preservadas
   (`admin/pessoas/pessoas-fisicas`, `admin/comercial/projetos`, etc.).
2. Renderização real do loop do `topbar.blade.php` em tinker — o
   dropdown "Pessoas" passou a ter 3 itens (Categorias, Pessoas
   Físicas, Pessoas Jurídicas) e "Comercial" passou a ter 3 itens
   (Projetos, Situações, Tipos de Projeto), no mesmo formato que
   "Projetos" (4 itens) — a estrutura pedida.
3. `getCachedSubNavigation()` das páginas de listagem reais
   (`ListPessoasFisicas`, `ListProjetos`) passou a retornar 0 grupos e
   `getCluster()` passou a retornar `null` — confirmando que o menu
   lateral/abas em pílula da tentativa 1 desapareceu por completo
   (mesmo estado de `ProjectResource`/`TaskResource`, que nunca tiveram
   esse bloco).

### Regra para qualquer Resource/Page/Cluster novo do Perseu que deva compartilhar módulo com outros

Para um conjunto de Resources/Pages aparecerem juntos como itens do
MESMO dropdown na topbar (padrão "Projetos"/"Estoque"/"Pessoas"/
"Comercial"): **NÃO os agrupe sob um Cluster comum** — declare
`getNavigationGroup()` apontando para o MESMO caso de
`Webkul\Support\Enums\NavigationGroup` diretamente em cada um, sem
`$cluster`. Um Cluster continua sendo a ferramenta certa quando o
objetivo é OUTRO: uma sub-hierarquia dentro de um item já "achatado"
(ex.: `Configurations` dentro do grupo Project/Inventory, agrupando
vários Resources de configuração que por sua vez não precisam aparecer
soltos na topbar) — nesse caso sim, `$cluster = Configurations::class`
nos Resources filhos e `getNavigationGroup()` só no Cluster.

Continua valendo, para QUALQUER caso de `NavigationGroup` novo
(Cluster ou não): sempre declarar `getNavigationGroup()` explicitamente
em pelo menos um dos itens que o usam (criando o caso + label/ícone +
traduções nos 4 idiomas em `lang/{pt_BR,en,es,ar}/admin.php` se ainda
não existir um adequado). Nunca deixar um Cluster/Resource de nível
superior sem `getNavigationGroup()` — cai num grupo anônimo (label
`null`) compartilhado com qualquer outro item igualmente sem grupo,
escondendo-o do dropdown da topbar como entrada própria.

## Integração BrasilAPI em Pessoa Jurídica: busca de CNPJ para viabilizar NF-e (2026-08-27, ampliada em 2026-08-28)

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
  sugeriria — confirmado com uma chamada real antes de mapear (ver
  seção "Reaproveitamento de utilitários..." sobre a mesma prática já
  seguida para o mapeamento de CNPJ original). A API devolve `porte` como
  TEXTO (`"DEMAIS"`, `"MICRO EMPRESA"`...) e `codigo_porte` como o código
  numérico — o oposto do que o nome do campo sugere. `BrasilApiCnpjLookup`
  mapeia `codigo_porte` → nossa coluna `porte` (o código) e o `porte` da
  API → nossa coluna `descricao_porte` (o texto), documentado inline no
  código para não confundir quem for reler isso depois.
- **Situação Cadastral é a exceção à regra de "só preenche vazio"** — ao
  contrário de todos os outros campos (razão social, telefone, CNAE,
  porte, regime tributário), `situacao_cadastral`/
  `descricao_situacao_cadastral` são **sempre sobrescritos** a cada busca
  bem-sucedida, e **limpos** (`null`) quando o CNPJ é inválido/não
  encontrado. Faz sentido porque é um campo 100% somente leitura (nunca
  digitado pelo usuário, só reflete a Receita Federal) — não haveria
  "valor manual do usuário" para proteger, e manter um valor de uma busca
  antiga (de um CNPJ diferente do atual) seria enganoso.
- Demais campos seguem a regra de sempre: **nunca sobrescreve um campo já
  preenchido** — cada `Set` é precedido por um `blank($get(...))`. Não
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
  avaliado e **não** foi implementado — decidido que pertence ao momento
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
- Dados que a API de CNPJ retorna mas **ainda não têm campo
  correspondente** no cadastro (decisão de produto pendente de
  avaliação, não implementação): capital social (`capital_social`),
  sócios/QSA (`qsa`), CNAEs secundários (`cnaes_secundarios` — só o CNAE
  principal é mapeado), datas de opção/exclusão do Simples/MEI (só o
  resultado final do regime é guardado, não as datas).

### NCM removido do cadastro de Pessoa Jurídica (2026-08-28)

A primeira versão desta integração (2026-08-27) também adicionava um
campo `ncm` (Select com busca assíncrona na BrasilAPI) em Pessoa
Jurídica. **Foi um equívoco de escopo, revertido no dia seguinte**: NCM
(Nomenclatura Comum do Mercosul) é uma classificação de
**produto/mercadoria**, não de empresa — não pertence ao cadastro de
Pessoa Jurídica. Revertido via nova migration
(`2026_08_28_100000_drop_ncm_from_pessoas_juridicas_table.php` — a
migration original de criação da coluna,
`2026_08_27_100000_add_ncm_to_pessoas_juridicas_table.php`, **não** foi
editada, já que tinha sido commitada/rodada; reverter uma migration já
aplicada é sempre uma migration nova, nunca uma edição retroativa da
antiga), removendo também o campo do `form()`, do `$fillable` do model e
as chaves de tradução `ncm`.

`Perseu\Pessoas\Support\BrasilApiNcmLookup` **foi mantido no código**
(não deletado) — tem um comentário no topo do arquivo marcando
"RESERVADO PARA USO FUTURO" — porque a lógica em si (busca assíncrona na
BrasilAPI, `GET /api/ncm/v1`) é reaproveitável quando o futuro cadastro
de Produto/Material for criado, que é onde NCM realmente pertence. Só o
ponto de uso (`getSearchResultsUsing`/`getOptionLabelUsing` num Select)
precisa ser reconectado lá — a classe em si não muda.

Pendências de outras APIs da BrasilAPI avaliadas mas fora de escopo
(feriados nacionais, bancos/PIX) — ver `PENDENCIAS-INTEGRACOES.md`.

## Excluir Pessoa Jurídica em cascata (Endereços/Contatos) + CNPJ de registro excluído (2026-08-28)

**Bug relatado**: excluir uma Pessoa Jurídica e recriar o cadastro com o
mesmo CNPJ trazia de volta o Endereço antigo. **Causa raiz confirmada
por teste controlado** (não a hipótese inicialmente suspeitada): não
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
   (sem o `SoftDeletingScope` do Eloquent) — ou seja, **um CNPJ de
   registro soft-deleted já bloqueava a recriação**, só que com uma
   mensagem genérica ("já se encontra registrado") sem explicar o
   motivo real. Se a recriação já estava bloqueada, como o Endereço
   antigo reaparecia? Reproduzido durante os próprios testes
   desta correção: bastou restaurar manualmente (via tinker,
   `->restore()`) um registro soft-deleted que já tinha essa "lixeira
   fantasma" — algo plausível de ter acontecido durante os testes desta
   semana (o projeto ainda não tem Lixeira/Restore funcional na UI, só
   acesso via tinker/DB) — para os Endereços órfãos reaparecerem
   instantaneamente, já que a relação nunca tinha sido desfeita. A causa
   raiz de fundo é (1): sem isso corrigido, QUALQUER restauração futura
   (manual ou por uma Lixeira que venha a ser implementada) reproduziria
   o mesmo sintoma.

**Correção 1 — cascata em `Perseu\Pessoas\Models\PessoaJuridica::boot()`**:
listener em `static::deleting(...)` (dispara tanto em soft-delete quanto
em `forceDelete()` — `SoftDeletes::delete()` ainda passa pelos eventos
normais do Model) que:
- Apaga todos os `Contato` da relação (`hasMany` direto por
  `pessoa_juridica_id` — sempre pertence a uma única Pessoa Jurídica,
  `->delete()` em massa é seguro).
- Para cada `Endereco` da relação (`BelongsToMany`, que o schema permite
  compartilhar entre múltiplas Pessoas Física/Jurídica mesmo que nada no
  sistema hoje crie isso na prática): desvincula da Pessoa Jurídica
  (`detach()`) e só apaga o registro de fato se, depois disso, nenhuma
  outra Pessoa Física/Jurídica ainda o referenciar — evita apagar um
  Endereço que porventura esteja em uso por outro cadastro.

  Nem Endereço nem Contato usam `SoftDeletes` (ver "Estrutura do plugin
  de Pessoas"), então a limpeza é sempre definitiva (`->delete()` real),
  mesmo quando a própria Pessoa Jurídica está só indo pra lixeira — não
  faz sentido preservar dados "vivos" de um cadastro pai que nem existe
  mais ativamente; se o usuário restaurar a Pessoa Jurídica depois,
  reconstrói Endereço/Contato do zero (mesma UX de excluir e recadastrar
  hoje).

**Correção 2 — mensagem clara para CNPJ de registro excluído**: o
`->unique()` do campo `cnpj` ganhou
`modifyRuleUsing: fn (Unique $rule) => $rule->whereNull('deleted_at')`
— volta a considerar só registros ATIVOS (comportamento que a maioria
dos devs assumiria por padrão, mas não é o default do Laravel). Um novo
`Perseu\Pessoas\Rules\CnpjNaoExcluido` (mesmo padrão de `CnpjValido`)
assume especificamente o caso "existe um soft-deleted com este CNPJ",
com mensagem própria orientando o usuário a restaurar ou apagar
definitivamente antes de recriar — situação que vai continuar
acontecendo até a decisão maior sobre Lixeira/Restore de Pessoa
Jurídica ser tomada (fora de escopo desta correção).

**Escopo**: só `Perseu\Pessoas\Models\PessoaJuridica`. `PessoaFisica`
tem exatamente a mesma estrutura (`SoftDeletes` + `enderecos()`
`BelongsToMany` sem cascata) e provavelmente tem o mesmo problema
latente, mas isso não foi corrigido aqui — o bug relatado era
especificamente sobre Pessoa Jurídica, e não foi confirmado/testado
para Pessoa Física.

**Atualização (2026-08-28, tarefa de Auditoria/Lixeira):** a lacuna
acima foi fechada — ver seção "Auditoria (log de atividade) + Lixeira
completa", que também reavaliou e CORRIGIU um detalhe importante desta
correção original: o hook trocou de `deleting` para `forceDeleting`
(deixando de rodar num soft-delete comum), porque a Lixeira completa
feita naquela tarefa depende de Endereço/Contato permanecerem intactos
enquanto o registro pai só está soft-deleted (pra `Restaurar`
funcionar de verdade).

## Auditoria (log de atividade) + Lixeira completa (2026-08-28)

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
(`Webkul\PluginManager\PackageServiceProvider`, ver
GUIA-CRIACAO-PLUGIN.md) — **sem migrations próprias**: as migrations
do pacote spatie (`activity_log`) foram publicadas para
`database/migrations/` do próprio app (`php artisan vendor:publish
--tag=activitylog-migrations`) e rodam via `artisan migrate` normal,
de propósito fora do ciclo de instalar/desinstalar do plugin-manager —
é infraestrutura compartilhada por QUALQUER plugin que audite Models,
não dado específico do plugin `auditoria`; desinstalar o plugin não
deveria arriscar dropar a tabela de log de outros módulos.

`perseu/pessoas` e `perseu/comercial` ganharam
`->hasDependency('auditoria')` nos respectivos ServiceProviders (os
Models deles referenciam `Perseu\Auditoria\Traits\LogsBusinessActivity`
diretamente — sem o plugin de auditoria instalado, essas classes
quebrariam).

### `Perseu\Auditoria\Traits\LogsBusinessActivity` — ponto único de configuração

Em vez de cada Model escrever seu próprio `getActivitylogOptions()`
(Spatie), todo Model de cadastro de negócio só faz `use
LogsBusinessActivity;`, que aplica o padrão do projeto:
`logFillable()` (audita os mesmos campos que já são
mass-assignable — um campo novo no `$fillable` já entra na auditoria
automaticamente, sem editar nada de auditoria) + `logOnlyDirty()` +
`dontSubmitEmptyLogs()` (só grava log quando algo realmente mudou).
`causer` (quem fez) e `event` (created/updated/deleted) são
automáticos do Spatie. Aplicado em TODOS os Models de cadastro de
negócio hoje existentes: `perseu/pessoas` (`PessoaJuridica`,
`PessoaFisica`, `CategoriaPessoa`, `Setor`, `Endereco`, `Contato`) e
`perseu/comercial` (`Projeto`, `SituacaoProjeto`, `TipoProjeto`) — ou
seja, TODOS os Models são auditados, mesmo os 4 sem Lixeira/aba
própria (ver limitação abaixo), porque auditar não depende de
SoftDeletes nem de ter uma página de Edit dedicada.

`config('activitylog.subject_returns_soft_deleted_models')` foi
mudado de `false` (padrão do pacote) para `true` — sem isso, a aba de
Atividades de um registro perderia a referência ao "assunto" assim que
ele fosse soft-deleted (`$activity->subject` viraria `null`), o que
não faz sentido dado que o sistema usa soft delete extensivamente.

### Página de Auditoria dentro de Configurações (não item de topo próprio)

`rmsramos/activitylog` cria, por padrão, seu próprio item de
navegação de nível superior. A tarefa pediu especificamente
Configurações → Auditoria (mesmo padrão de "Marca"/`ManageBranding`),
não um menu próprio. Mecanismo: `Perseu\Auditoria\Filament\Resources\
AuditoriaResource extends \Rmsramos\Activitylog\Resources\Activitylog\
ActivitylogResource` com `protected static ?string $cluster =
Webkul\Support\Filament\Clusters\Settings::class;` — a mesma técnica
que `Webkul\Support\Filament\Resources\ActivityTypeResource` já usa
(um Resource "de verdade" clusterizado, achado como referência viva —
`ManageBranding` é uma `SettingsPage` ligada a uma classe de Settings
do `filament/spatie-laravel-settings-plugin`, base errada pra uma tela
com tabela/filtros/timeline como esta). Registrado via
`ActivitylogPlugin::make()->resource(AuditoriaResource::class)` (troca
o Resource padrão do pacote pelo nosso — só o nosso fica registrado,
não os dois).

**`getPages()` precisou ser sobrescrito com Pages próprias**
(`ListAuditoria`/`ViewAuditoria`, cada uma só reapontando
`$resource`/`getResource()` para `AuditoriaResource::class`): as Pages
originais do pacote (`Rmsramos\Activitylog\Resources\Activitylog\
Pages\*`) têm esse valor FIXO na classe base `ActivitylogResource` —
herdar `getPages()` sem reapontar faria as rotas/permissões
resolverem pro Resource de topo do pacote, não pro nosso clusterizado.

**Bug corrigido durante a implementação**: `Panel::configureUsing()`
(usado em `packageRegistered()`) aplica a callback a TODOS os painéis
registrados, não só o admin — sem um `if ($panel->getId() !== 'admin')
{ return; }` antes de `$panel->plugin(ActivitylogPlugin::make())`, a
tela de log de atividade também aparecia registrada no painel
`customer`, o que não faz sentido (cliente não deveria ver auditoria
interna do sistema). Confirmado via `route:list` antes/depois do
fix (`settings/activitylogs` no painel customer sumiu).

**Bug 2 corrigido depois (2026-08-28, correção pontual)**: o menu
"Auditoria" mostrava a chave crua de tradução
(`Auditoria::filament/resources/auditoria.plural-model-label`) em vez
do texto "Auditoria". Causa raiz: `AuditoriaServiceProvider::
packageRegistered()` montava o `ActivitylogPlugin` com
`->label(__(...))`/`->pluralLabel(__(...))` — `__()` **avaliado
imediatamente**, não dentro de uma Closure. `packageRegistered()`
roda a partir de `PackageServiceProvider::register()`
(spatie/laravel-package-tools), e a fase `register()` de TODOS os
service providers do Laravel sempre termina ANTES da fase `boot()` de
qualquer um — só no `boot()` (`bootPackageTranslations()`, disparado
por `->hasTranslations()`) o namespace `auditoria::` é de fato
registrado no `Translator`. Ou seja, o `__()` eager rodava sempre
ANTES do namespace existir, retornava a própria chave (comportamento
padrão do Laravel pra tradução "não encontrada") — e pior: o
`Translator` cacheia esse resultado vazio por
namespace+grupo+locale pro resto do request
(`Illuminate\Translation\Translator::$loaded`), então mesmo chamadas
LEGÍTIMAS feitas bem depois no mesmo request (`AuditoriaResource::
getPluralModelLabel()`, chamada de verdade só na hora de montar a
navegação, já com tudo registrado) recebiam a mesma resposta
envenenada — não era uma questão de "tentar nesse método
específico", o cache já estava poluído antes de qualquer tentativa
legítima rodar.

Diagnosticado comparando `app('translator')->getLoader()->load(...)`
(sempre lê do disco, funcionava) com `__()`/`Translator::get()`
(sempre falhava, mesmo isoladamente) — a diferença apontou direto pro
cache interno do Translator, não pro arquivo/chave/registro do
namespace em si (que estavam corretos o tempo todo).

**Correção**: trocar `->label(__(...))` por
`->label(fn () => __(...))` (idem `pluralLabel`) — `ActivitylogPlugin::
label()`/`pluralLabel()` aceitam `string|Closure`, e `getLabel()`/
`getPluralLabel()` só avaliam a Closure quando o valor é realmente
lido (`$this->evaluate($this->label)`), bem depois do `boot()` de
todos os providers já ter terminado. **Regra geral pra qualquer
`ServiceProvider` deste projeto**: nunca chamar `__()` (ou qualquer
outra coisa que dependa de algo registrado só no `boot()`, como uma
config ou binding de outro pacote) diretamente dentro de
`packageRegistered()`/`register()` — só dentro de uma Closure lida
depois, ou dentro de `packageBooted()`/`boot()`.

### Permissões (Shield) — chaves reais, não as citadas na tarefa original

A tarefa original citava `view_any_activity_log`/`view_activity_log`
como exemplo — a convenção REAL de geração de chaves deste projeto
(`Webkul\PluginManager\PermissionManager::managePermissions()`) deriva
a chave do nome/namespace do **Resource**, não do Model subjacente:
pra um Resource `Perseu\Auditoria\Filament\Resources\AuditoriaResource`
(plugin `auditoria`), o resultado é `view_any_auditoria_auditoria` /
`view_auditoria_auditoria` (confirmado rodando `auditoria:install` e
consultando a tabela `permissions` — não adivinhado da leitura do
algoritmo). `config/filament-shield.php` do plugin declara só
`['view_any', 'view']` pra esse Resource — log de atividade é gerado
pelo sistema, nunca criado/editado/excluído manualmente pela UI.

`Perseu\Auditoria\Policies\ActivityPolicy` (`Gate::policy(Activity::class,
ActivityPolicy::class)`, registrado em `packageBooted()`) controla ao
mesmo tempo a página de Auditoria E a aba "Atividades" embutida em
Pessoa Jurídica/Física/Projeto — **sem nenhum código extra na aba**:
`RelationManager::canViewForRecord()` (padrão do Filament, ver
`vendor/filament/filament/src/Resources/RelationManagers/RelationManager.php`)
já resolve o Model da relação (`activities()`, fornecido pelo trait
`LogsActivity` do Spatie) e chama `authorize('viewAny', Activity::class)`
sozinho — isso já É a "permissão separada de ver/editar o próprio
registro" pedida na tarefa: um usuário pode editar uma Pessoa Jurídica
sem ver sua aba de Atividades, e vice-versa, dependendo só de
`view_any_auditoria_auditoria`. Testado criando uma role/usuário sem
essa permissão: a aba não aparece no HTML da página de edição, mas a
página em si continua acessível (o usuário tem permissão de
ver/editar a PJ, só não a de auditoria).

Os Resources de Pessoa Jurídica/Física/Projeto **já tinham**
`restore`/`restore_any`/`force_delete`/`force_delete_any` declarados
em `config/filament-shield.php` e sincronizados com a role Admin de
uma tarefa anterior — só faltava a UI (`TrashedFilter`/`RestoreAction`/
`ForceDeleteAction`) de fato usar essas permissões, que é o que esta
tarefa completou.

**Atribuir a novas roles no futuro** (hoje só existe o usuário Admin):
Configurações → Funções → criar/editar uma Função → marcar os toggles
de permissão desejados (`Ver qualquer Log de Atividade`/`Ver Log de
Atividade` pra habilitar a aba de Atividades e a página de Auditoria;
`Restaurar`/`Restaurar Todos`/`Excluir Permanentemente`/`Excluir
Permanentemente Todos` de Pessoa Jurídica/Física/Projeto pra habilitar
a Lixeira desses cadastros) → atribuir a Função ao usuário (Segurança
→ Usuários → editar → aba de Roles). Mesmo painel usado pra outras
permissões do sistema, nada específico de auditoria além de saber
quais toggles marcar.

### Lixeira completa — `TrashedFilter` + `RestoreAction` + `ForceDeleteAction`

Aplicado em `PessoaJuridicaResource`, `PessoaFisicaResource` e
`ProjetoResource` (os 3 Resources cujo Model usa `SoftDeletes` E tem
página de Edit dedicada — ver limitação abaixo): `Filament\Tables\
Filters\TrashedFilter` (classe nativa do Filament, não um helper
próprio — confirmado lendo o pacote: é uma `TernaryFilter` com
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
`TrashedFilter`, o que deixaria as duas ações inalcançáveis, já que
sem o filtro o registro soft-deleted nunca aparece na tabela pra
começo de conversa — não copiado por estar incompleto).

### Limitação conhecida — `CategoriaPessoa`/`Setor`/`SituacaoProjeto`/`TipoProjeto` não têm Lixeira nem aba de Atividades

A tarefa original citava Categoria/Setor como já usando `SoftDeletes`
— **não usam** (confirmado por grep antes de implementar, não
assumido). Além disso, os 4 Resources desses Models
(`CategoriaPessoaResource`, `SetorResource`, `SituacaoProjetoResource`,
`TipoProjetoResource`) usam o padrão `ManageRecords` do Filament (uma
página só, criar/editar via modal) — estruturalmente incompatível com
RelationManager (a aba de Atividades exige uma página de Edit/View
dedicada, que esses 4 não têm). Decisão consciente (confirmada com o
usuário durante a implementação): não expandir o escopo desta tarefa
pra também adicionar `SoftDeletes` a esses 4 Models e reestruturar os
Resources pra List+Edit separados — os 4 Models JÁ SÃO auditados
(`LogsBusinessActivity`, ver acima), só não têm Lixeira nem aba visual
de Atividades. Reavaliar como uma decisão própria se/quando isso virar
necessidade real (mesmo espírito da seção "Flags de sistema em
Categoria de Pessoa" — não expandir schema/estrutura preventivamente).

### Convenção para todo Model de cadastro de negócio criado a partir de agora

1. Usar `SoftDeletes` (`Illuminate\Database\Eloquent\SoftDeletes`).
2. Usar `Perseu\Auditoria\Traits\LogsBusinessActivity` (não escrever
   `getActivitylogOptions()` à mão, a não ser que o Model precise de
   algo diferente do padrão — aí sobrescrever o método depois do
   `use`, que vence normalmente por resolução de trait do PHP).
3. Se o Model participa de uma relação `BelongsToMany` com `Endereco`
   e/ou `HasMany` com `Contato` (ou dado análogo sem SoftDeletes
   próprio): usar `Perseu\Pessoas\Traits\CascadesRelatedDataOnForceDelete`
   (ou o padrão equivalente — `forceDeleting`, nunca `deleting`, pra
   não quebrar a Lixeira) pra não deixar dados órfãos numa exclusão
   definitiva.
4. O Resource correspondente precisa ter página de Edit/View dedicada
   (não `ManageRecords`) pra poder ganhar `TrashedFilter` +
   `RestoreAction`/`ForceDeleteAction` + `ActivitylogRelationManager`
   na aba de Atividades — se o cadastro for simples o bastante pra
   usar `ManageRecords` (like Categoria/Setor), aceitar que ele fica
   sem Lixeira/aba visual (mas continua auditado) até uma decisão
   consciente de reestruturar, não implementar isso por padrão em todo
   cadastro novo sem necessidade real.
5. Adicionar `RestoreAction`/`ForceDeleteAction`/`RestoreBulkAction`/
   `ForceDeleteBulkAction` + `TrashedFilter` no `table()` do Resource,
   e `Rmsramos\Activitylog\RelationManagers\ActivitylogRelationManager::class`
   em `getRelations()`.
6. Declarar `restore`/`restore_any`/`force_delete`/`force_delete_any`
   no `config/filament-shield.php` do plugin (junto do básico), e
   rodar `shield:generate` (ou reinstalar o plugin) pra sincronizar com
   a role Admin.
