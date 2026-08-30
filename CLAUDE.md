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

## Cluster "Obras" no plugin `perseu/comercial` — sub-área com sidebar, distinta do Cluster removido em 26cfef4f7 (2026-08-29)

Criado `Perseu\Comercial\Filament\Clusters\Obras`
(`plugins/perseu/comercial/src/Filament/Clusters/Obras.php`), agrupando
`ObraResource`/`TipoObraResource`/`SituacaoObraResource` numa área com
sidebar lateral própria (Tipos de Obra, Situações de Obra, Obras),
igual ao clique em "Configurações" no plugin de Tarefas. Antes de
implementar, foi investigado a fundo o commit `26cfef4f7` ("Remove
Clusters em favor de registro direto de Resources"), que tinha
removido um `PessoasCluster`/`ComercialCluster` anterior — para
confirmar que este Cluster novo não reintroduz o mesmo problema.

### O que o commit `26cfef4f7` revelou (lido via `git show`, não suposto)

O `PessoasCluster`/`ComercialCluster` antigos (`git show 26cfef4f7` —
classes de 15 linhas, só `$navigationIcon` +
`getNavigationLabel()`, sem `getNavigationGroup()` próprio) tinham um
objetivo DIFERENTE do de hoje: fazer Categorias/Pessoas
Físicas/Pessoas Jurídicas (e Projetos/Situações/Tipos de Projeto)
aparecerem como itens FLAT/irmãos dentro do dropdown da topbar,
replicando o padrão "Projetos" (que tem `ProjetoResource`,
`TaskResource` e o Cluster `Configurations` como 3 itens irmãos dentro
do MESMO dropdown "Project"). Só que um Cluster, por definição
(`HasNavigation::registerNavigationItems()`: `if
(filled(static::getCluster())) { return; }`), tira TODOS os seus
Resources filhos da navegação principal — só o Cluster em si aparece
lá, como item único. Resultado observado então: o dropdown "Pessoas"
tinha 1 item só (o Cluster), enquanto "Projetos" tinha vários — o
oposto do padrão desejado naquele momento. Os dois arquivos-histórico
`tarefa-cluster-navegacao-horizontal.md`/`tarefa-navegacao-topbar-correta.md`
(no próprio commit) documentam duas tentativas de correção erradas
antes da remoção final (`SubNavigationPosition::Top`, que criava abas
em pílula abaixo do cabeçalho — mecanismo errado) — a remoção do
Cluster e o rename direto de `getNavigationGroup()` em cada Resource
(ver seção "Navegação de módulos..." acima) foi a correção real.

**Ou seja: o Cluster do Filament em si nunca teve um bug** — o
problema era usar um Cluster (item único + sidebar) quando o resultado
visual desejado era o OPOSTO (múltiplos itens irmãos na topbar, sem
sidebar). São dois mecanismos de navegação genuinamente diferentes do
Filament, cada um certo para um objetivo diferente.

### Por que o Cluster "Obras" de hoje é seguro (objetivo é o OPOSTO do que causou o problema em 26cfef4f7)

Desta vez o objetivo pedido é exatamente o que um Cluster resolve: UM
item ("Obras") no dropdown "Comercial" da topbar, que ao ser clicado
abre uma sidebar própria com os 3 cadastros — o mesmo padrão já usado
e funcionando hoje por `Webkul\Support\Filament\Clusters\Settings`
("Configurações") e por `Webkul\Project\Filament\Clusters\Configurations`
("Configurações" dentro do grupo "Projetos", ver seção "Navegação de
módulos..."). Não é o mesmo Cluster que foi removido usado do mesmo
jeito — é a ferramenta certa aplicada ao caso para o qual ela já era
recomendada (ver regra "Um Cluster continua sendo a ferramenta certa
quando o objetivo é OUTRO: uma sub-hierarquia dentro de um item já
achatado" na mesma seção). Efeito colateral aceito conscientemente: o
dropdown "Comercial" da topbar passa a ter 1 item só ("Obras") em vez
dos 3 itens irmãos que tinha antes desta tarefa — o roadmap de
`CONCEITO-OBRA-PROPOSTA-PROJETO.md` (Proposta, Projeto) deve adicionar
NOVOS itens irmãos ao Cluster "Obras" nesse mesmo dropdown no futuro,
não substituir esta decisão.

### Risco novo verificado e evitado: ícone do grupo + ícone do item dentro da sidebar do Cluster

O commit `0035a3bef` ("Corrige erro 500 no cluster de Configurações do
plugin de Tarefas") mostrou que a sidebar de QUALQUER Cluster lança
`\Exception` (`vendor/filament/filament/resources/views/components/sidebar/group.blade.php`:
"Navigation group [X] has an icon but one or more of its items also
have icons...") quando um grupo de navegação DENTRO da sidebar do
Cluster tem ícone (`NavigationGroup` enum, que carrega ícone) E os
itens desse grupo também têm `$navigationIcon` próprio — que é
exatamente o caso de `ObraResource`/`SituacaoObraResource`/
`TipoObraResource` antes desta tarefa (cada um com `getNavigationGroup()
=> NavigationGroup::Comercial`, que tem ícone, MAIS `$navigationIcon`
próprio). Migrar os 3 Resources para dentro do Cluster `Obras` SEM
remover esse `getNavigationGroup()` teria reintroduzido o mesmo 500 —
só que na sidebar do Cluster "Obras", não na de "Configurações".

**Correção aplicada**: `getNavigationGroup()` foi REMOVIDO dos 3
Resources (não faz mais sentido de qualquer forma — dentro de um
Cluster, quem aparece no dropdown da topbar é o Cluster, não o
Resource; `getNavigationGroup()` de um Resource clusterizado só
afetaria a sidebar INTERNA do Cluster). Confirmado por comparação
direta de código: nenhum Resource dentro de
`Webkul\Project\Filament\Clusters\Configurations` (`TaskStageResource`,
`ProjectStageResource`, `MilestoneResource`, `TagResource`,
`ActivityPlanResource`) declara `getNavigationGroup()` — mesmo padrão
replicado aqui. Confirmado em runtime via tinker
(`app(Obras::class)->getCachedSubNavigation()`): os 3 itens vêm dentro
de um único `NavigationGroup` sintético com `label = null` (sem
cabeçalho/ícone de grupo visível), então a exceção nunca é
disparada — os `$navigationIcon` de cada Resource (`heroicon-o-
clipboard-document-list`/`heroicon-o-tag`/`heroicon-o-flag`) aparecem
normalmente ao lado de cada item da sidebar.

### Slugs e ordem

- `Obras::$slug = 'comercial'` (a URL do próprio Cluster,
  `admin/comercial`, redireciona automaticamente pro primeiro item da
  sidebar — `Cluster::mount()`).
- `ObraResource::$slug = 'obras'`, `TipoObraResource::$slug =
  'tipo-obras'`, `SituacaoObraResource::$slug = 'situacao-obras'` —
  como a rota final de um Resource clusterizado é `{slug do Cluster}/
  {slug do Resource}` (`HasRoutes::registerRoutes()`,
  `$cluster::prependClusterSlug($panel, '')` + `getRoutePrefix()`
  próprio), essa escolha faz as 3 URLs finais ficarem EXATAMENTE iguais
  às de antes desta tarefa (`admin/comercial/obras`,
  `admin/comercial/tipo-obras`, `admin/comercial/situacao-obras` —
  confirmado via `route:list`), só a de Situação que também mudou de
  nome do slug internamente (era `situacao-obras`, continua
  `situacao-obras`) — nenhuma URL de resource realmente quebrou para
  quem tivesse um link salvo.
- Ordem na sidebar via `$navigationSort` (1 = Obras, 2 = Tipos de Obra,
  3 = Situações — cadastro principal primeiro, cadastros de apoio
  depois, pedido explícito da tarefa; o Cluster "Configurations" de
  Tarefas não tem um "Resource principal" análogo pra comparar, então
  não há um padrão establecido contrário a seguir aqui).

### Breadcrumb — evitado o mesmo bug do commit `0035a3bef`/Configurations

`Filament\Clusters\Cluster::getClusterBreadcrumb()` cai, por padrão,
em `static::$title ?? str(class_basename)->beforeLast('Cluster')->kebab()
->replace('-', ' ')->ucwords()` — puramente derivado do nome da classe,
SEM tradução. Confirmado por leitura de código que é exatamente isso
que faz o Cluster "Configurations" (`webkul/projects`) mostrar
"Configurations >" em inglês no breadcrumb hoje (bug conhecido, fora
de escopo desta tarefa, não corrigido) — a classe só sobrescreve
`getNavigationLabel()`, não `getClusterBreadcrumb()`. `Obras` sobrescreve
`getClusterBreadcrumb()` explicitamente, retornando
`static::getNavigationLabel()` (que já usa `__('comercial::filament/clusters/obras.navigation.title')`)
— confirmado em runtime via `app(ListObras::class)->getBreadcrumbs()` e
`app(ManageTiposObra::class)->getBreadcrumbs()`: ambos mostram "Obras"
corretamente, não o nome da classe nem inglês.

### Shield — mesma exclusão de página "fantasma" já usada por PessoasCluster/ComercialCluster

`Obras` é um Cluster "puro" (nenhuma Page própria declara `$cluster =
Obras::class` além dos 3 Resources) — mesma situação de
`PessoasCluster`/`ComercialCluster` antes de serem removidos (ver
comentário mantido em `config/filament-shield.php`). Adicionado
`'pages' => ['exclude' => [Obras::class]]` nesse config, exatamente
como era feito antes — `shield:generate --resource=ObraResource,
SituacaoObraResource,TipoObraResource` confirmou "Entities processed: 3"
(não 4), e as 22 permissões geradas continuam com os mesmos nomes de
antes (`view_any_comercial_obra`, etc. — nenhuma permissão nova, nenhum
toggle morto "Obras Cluster" na tela de Funções).

### Validado (via tinker, autenticado como o próprio usuário Admin)

1. `Filament::getNavigation()`: grupo "Comercial" agora tem 1 item só
   ("Obras" → `/admin/comercial`); todos os outros grupos (Dashboard,
   Projetos, Pessoas, Módulos, Configurações, Ajuda) inalterados.
2. `app(Obras::class)->getCachedSubNavigation()`: 3 itens na ordem
   Obras/Tipos de Obra/Situações, sem exceção de ícone.
3. `Livewire::test()` renderizou sem exceção `ListObras`,
   `ManageTiposObra`, `ManageSituacoesObra` e `EditObra` (record
   existente, id=2) — confirma que não há 500 em nenhuma das 4 páginas
   dentro da nova estrutura.
4. Numeração automática: `Obra::create([...])` (dentro de transação
   revertida) gerou `numero_obra` normalmente
   (`GeradorNumeroObra::gerar()`, chamado em `Obra::boot()`'s
   `creating`, é 100% independente de rota/Cluster/navegação — nenhum
   arquivo desse fluxo foi tocado nesta tarefa).
5. **Achado incidental, fora de escopo, NÃO introduzido por esta
   tarefa** (confirmado comparando o mesmo teste com `git stash` do
   código desta tarefa aplicado/revertido): a aba "Auditoria"
   (`ActivitylogRelationManager`) e o filtro de Lixeira
   (`TrashedFilter`) de `ObraResource` não aparecem no HTML retornado
   por `Livewire::test(...)->html()` mesmo ANTES desta tarefa — parece
   uma particularidade de como esse teste renderiza
   RelationManagers/painéis de filtro (possivelmente carregados via
   Livewire aninhado/lazy), não um bug real da UI (a mesma checagem em
   `EditPessoaJuridica` encontra "Auditoria" normalmente). Não
   investigado a fundo por ser preexistente e não relacionado ao
   Cluster — mencionar caso surja de novo em outra tarefa.
6. `route:list` e `shield:generate` conferidos antes/depois; `ddev
   artisan optimize:clear` executado ao final.

## Central de Auditoria única, sem abas de "Atividades" nos registros individuais (2026-08-29)

A aba "Atividades" (`ActivitylogRelationManager`) embutida em Pessoa
Jurídica/Física/Obra foi **removida** — decisão consciente de não
duplicar informação: a página central "Configurações → Auditoria" (já
existente desde a tarefa anterior, ver seção acima) passou a ser o
ÚNICO lugar do sistema pra ver histórico de atividade, com filtros
para compensar a perda do atalho "aba dentro do próprio registro".

### Onde a aba existia (levantado por grep antes de remover)

Só 3 Resources chegaram a ter `ActivitylogRelationManager::class` em
`getRelations()`: `ObraResource` (removido o método inteiro, ficava
sozinho ali), `PessoaFisicaResource` e `PessoaJuridicaResource`
(mantidos os outros Relation Managers de cada um —
`EnderecosRelationManager`/`ContatosRelationManager`). Os Resources
com padrão `ManageRecords` (`CategoriaPessoaResource`, `SetorResource`,
`SituacaoObraResource`, `TipoObraResource`) NUNCA tiveram essa aba (ver
"Limitação conhecida" na seção anterior) — nada a remover neles.

### `Perseu\Auditoria\Support\SubjectTypeCatalog` — mapeamento único FQCN → rótulo/módulo/referência

Levantados por grep (`grep -rl LogsBusinessActivity plugins/`) todos os
9 Models realmente auditados hoje — confirmados também com uma query
real em `activity_log.subject_type` (`SELECT DISTINCT subject_type`),
já que este projeto NÃO define `Relation::morphMap()` (confirmado por
grep), então a coluna grava o FQCN completo, não um alias curto:
`Obra`, `TipoObra`, `SituacaoObra` (módulo "Comercial"); `PessoaFisica`,
`PessoaJuridica`, `CategoriaPessoa`, `Setor`, `Endereco`, `Contato`
(módulo "Pessoas"). A classe nova
`plugins/perseu/auditoria/src/Support/SubjectTypeCatalog.php`
centraliza:
- `label()`/`subjectTypeOptions()` — rótulo amigável traduzido (chaves
  em `auditoria::filament/resources/auditoria.subject_types.*`, texto
  DUPLICADO de propósito em relação ao `model-label` de cada Resource
  original — a central de Auditoria não deve chamar Resources de
  outros plugins só pra montar seu próprio rótulo). Um Model auditado
  no futuro e ainda não mapeado aqui não quebra nada — cai no fallback
  `Str::of($fqcn)->classBasename()->headline()`, só não ganha filtro
  dedicado nem referência amigável até ser adicionado ao mapa.
- `moduloOptions()`/`subjectTypesForModulo()` — agrupamento de um nível
  acima (Comercial/Pessoas), implementado por ser simples (só inverter
  o mapa FQCN→módulo já necessário para outra coisa) — ver filtro
  "Módulo" abaixo.
- `referenceFor(?Model $subject)` — texto que identifica O REGISTRO
  específico (não o tipo): `numero_obra` + `descricao` pra Obra,
  `razao_social` pra Pessoa Jurídica, `nome` pra Pessoa Física,
  `descricao` pra Tipo/Situação de Obra, Categoria e Setor,
  `logradouro`+`numero` pra Endereço, e nome da `PessoaFisica`
  vinculada (fallback pro `cargo`) pra Contato. Retorna `null` quando o
  `subject` não existe mais (excluído em definitivo — `subject` já vem
  eager-loaded com `withTrashed()` por
  `ActivitylogResource::getEloquentQuery()`, então só é `null` mesmo
  em exclusão definitiva, não soft-delete).
- `applyBusca(Builder $query, string $termo)` — filtro de busca textual
  (nome, razão social, número de Obra etc.) via `whereHasMorph('subject',
  [...9 classes...], fn ($q, $type) => match($type) {...})`: como não
  existe uma coluna própria em `activity_log` pra isso (é derivado do
  model relacionado, campo diferente por tipo), esse é o mecanismo do
  Eloquent desenhado exatamente pra "buscar num relacionamento
  polimórfico, com condição diferente por tipo concreto".

### `AuditoriaResource::table()` sobrescrito por completo, não encadeado

`ActivitylogResource::table()` (rmsramos/activitylog) monta
`->columns([...])->filters([...])` a partir de métodos estáticos
reutilizáveis (`getCauserNameColumnComponent()`,
`getEventColumnComponent()`, etc.) — mas chamar `parent::table($table)`
e encadear mais `->filters([...])`/`->columns([...])` por cima
SUBSTITUIRIA a lista anterior (não soma), então `AuditoriaResource::table()`
foi reescrito do zero reaproveitando os métodos estáticos do pai
individualmente (`static::getCauserNameColumnComponent()`, etc.) mais
os novos abaixo:
- `getSubjectTypeColumnComponent()` **sobrescrito** (mesmo nome do
  pai): antes mostrava `Str::of($fqcn)->afterLast('\\')->headline() . ' # ' . $subject_id`
  (nome de classe cru + id numérico) — agora mostra
  `SubjectTypeCatalog::label()` (só o rótulo amigável do TIPO de
  cadastro), porque `getSubjectReferenceColumnComponent()` (novo) passou
  a cobrir a referência ao registro específico com texto melhor
  (nome/razão social/número).
- `getSubjectReferenceColumnComponent()` (novo) — `TextColumn` com
  `getStateUsing()` (não é coluna real de `activity_log`), retorna
  `SubjectTypeCatalog::referenceFor($record->subject)` ou uma mensagem
  de "registro excluído definitivamente" quando não há mais subject.
  Não é `->searchable()` (a busca por este texto é o filtro dedicado,
  não a busca padrão da tabela — não existe coluna real pra indexar).
- `getModuloFilterComponent()` (novo) — `SelectFilter` com `->query()`
  próprio (`whereIn('subject_type', SubjectTypeCatalog::subjectTypesForModulo(...))`),
  porque "módulo" não é uma coluna real — sem `->query()` customizado,
  o comportamento padrão de `SelectFilter::apply()`
  (`Filament\Tables\Filters\SelectFilter`) tentaria
  `where('modulo', $valor)` e falharia (coluna não existe).
- `getSubjectTypeFilterComponent()` (novo) — `SelectFilter` SEM
  `->query()` custom: como `subject_type` É uma coluna real, o
  comportamento padrão do `SelectFilter` (`where('subject_type', $valor)`)
  já resolve sozinho.
- `getBuscaRegistroFilterComponent()` (novo) — `Filter` com um
  `TextInput` no form, delegando pra `SubjectTypeCatalog::applyBusca()`.

Filtro "Módulo" (item opcional da tarefa) foi implementado por ser
simples dado o design acima (o mapa FQCN→módulo já existia pra outra
coisa, o filtro é só inverter esse mapa e aplicar `whereIn`) — não
achatado/descartado por complexidade.

### Validação (via `Livewire::test(ListAuditoria::class)`, autenticado, com os 68 logs reais já existentes no ambiente)

1. `SubjectTypeCatalog::subjectTypeOptions()`/`moduloOptions()`
   conferidos manualmente (9 tipos, 2 módulos, nenhum rótulo cru).
2. HTML renderizado da listagem: nenhum FQCN vazado em texto visível
   (só dentro de `value=""` de `<option>`, esperado); referências
   reais aparecem certas na tabela (ex.: `2630001 — Smarta Itaoca` pra
   uma Obra, razão social completa pra Pessoa Jurídica, nome da Pessoa
   Física vinculada pra Contato).
3. Filtro "Módulo=Comercial": 20 registros (bate com
   `Activity::whereIn('subject_type', [Obra, TipoObra, SituacaoObra])->count()`
   direto no banco). Filtro "Cadastro=Obra": 7 registros (bate com
   `Activity::where('subject_type', Obra::class)->count()`). Filtro de
   busca por um nome de Pessoa Física: achou corretamente um log de
   Pessoa Jurídica cuja `razao_social` continha aquele nome (pessoa
   física que também é MEI, registrada com o próprio nome na razão
   social) — comportamento correto do `whereHasMorph`, não bug. Filtro
   "Evento=created" nativo do pacote (não tocado nesta tarefa):
   continuou batendo com a contagem direta no banco.
4. Confirmado que as abas "Atividades" desapareceram de
   `EditObra`/`EditPessoaFisica`/`EditPessoaJuridica` (mesmo teste de
   `Livewire::test()->html()` que antes desta tarefa encontrava
   "Auditoria" no HTML de Pessoa Física/Jurídica — ver achado
   incidental da tarefa anterior — agora não encontra mais, confirmando
   que a remoção teve efeito real, não é só a mesma limitação de
   renderização já observada antes). Relation Managers que
   PERMANECERAM (Endereços em ambos, Contatos em Pessoa Jurídica)
   continuam presentes normalmente.
5. Permissão de acesso à central: continua sendo só
   `view_any_auditoria_auditoria`/`view_auditoria_auditoria`
   (`ActivityPolicy`, docblock atualizado pra não citar mais a aba
   removida) — nenhuma permissão nova criada, nenhuma removida.
6. `route:list` (`admin/settings/activitylogs`, inalterada) e
   navegação completa (`Filament::getNavigation()`) conferidos
   antes/depois — nenhum outro grupo afetado; `ddev artisan
   optimize:clear` executado ao final.

## Central de Auditoria: filtro por usuário + escopo real do filtro de busca (2026-08-29)

Adicionado `getCauserFilterComponent()` em `AuditoriaResource` — um
`SelectFilter::make('causer_id')->searchable()->options(...)`, listando
`Webkul\Security\Models\User` por nome. `causer_id` é coluna real de
`activity_log`, então (mesmo raciocínio já usado pro filtro
`subject_type`) não precisou de `->query()` customizado — o
comportamento padrão do `SelectFilter` já faz `where('causer_id',
$valor)` sozinho. Não restringe também por `causer_type`: uma consulta
direta (`SELECT DISTINCT causer_type FROM activity_log`) confirmou só
dois valores possíveis neste banco — vazio (ações sem usuário
autenticado, ex. seeders/tinker, 11 das 70 linhas hoje) ou
`Webkul\Security\Models\User` — o projeto não chama `causedBy()`
manualmente em lugar nenhum (confirmado por grep), o Spatie Activitylog
detecta o causer sozinho a partir do usuário autenticado no guard
padrão, então não existe hoje um cenário de `causer_id` ambíguo entre
dois tipos de causer diferentes. Validado combinando o filtro com
módulo/cadastro/evento simultaneamente (`tableFilters.causer_id.value`
+ outro filtro ao mesmo tempo via `Livewire::test()->set(...)`) contra
contagem direta no banco — interseção `AND` correta em todos os casos
testados.

### Escopo real do filtro de busca textual ("Nome, razão social, número...")

Registrado aqui pra não precisar reler `SubjectTypeCatalog::applyBusca()`
do zero da próxima vez que a pergunta "o que esse filtro pesquisa"
aparecer — a lista abaixo é exatamente o `match` daquele método (uma
tabela de decisão fixa por tipo de `subject`, já que `whereHasMorph`
não pesquisa a mesma coluna em todo tipo):

| Cadastro | Coluna(s) pesquisada(s) |
|---|---|
| Obra | `descricao`, `numero_obra` |
| Tipo de Obra, Situação de Obra, Categoria de Pessoa, Setor | `descricao` |
| Pessoa Física | `nome` |
| Pessoa Jurídica | `razao_social`, `nome_fantasia`, `cnpj` |
| Endereço | `logradouro`, `bairro`, `municipio` |
| Contato | `cargo` |

Ou seja: **só pesquisa dentro dos 9 Models de cadastro de negócio
auditados** (o mesmo mapa de `SubjectTypeCatalog`) — não pesquisa
nomes de usuário/causer (esse é o filtro de Usuário, separado, acima),
nem qualquer dado fora desses Models (ex.: nome da empresa/tenant que
usa o sistema, que vive em `Webkul\Security\Models\Company`/Branding,
não em `Pessoa Jurídica` nem em nenhum Model auditado).

**Causa confirmada do "Sem registros" ao buscar "fa marcenaria"**
(reproduzido exatamente via `Livewire::test()->set('tableFilters.busca.valor',
'fa marcenaria')`, retornou `total=0`, batendo com o relato do
usuário): nenhum dos 2 registros de Pessoa Jurídica cadastrados hoje
(`razao_social`/`nome_fantasia` = "JULIO CESAR DE BARROS
15955943889"/"PROJETO STUDIO" e "SMARTFIT ESCOLA DE GINASTICA E DANCA
S.A"/"SMART FIT") nem nenhuma `Obra.descricao` contém o texto "fa
marcenaria" ou "marcenaria" (confirmado com `LIKE '%marcenaria%'`
direto no banco, zero linhas nas 3 tabelas relevantes). **"F.A.
Marcenaria" é a empresa DONA do sistema (o tenant, registrada como
`Company`/Branding — ver seção sobre Favicon/Logo), não um cliente ou
fornecedor cadastrado como Pessoa Jurídica** — não há nenhum registro
de negócio com esse nome pra encontrar hoje. Não é um bug do filtro;
o "Sem registros" está correto dado os dados reais atuais. Se um
cliente/fornecedor real chamado algo parecido com "F.A. Marcenaria"
for cadastrado como Pessoa Jurídica no futuro, a busca vai encontrá-lo
normalmente pela `razao_social`/`nome_fantasia`.

Sugestão feita ao usuário (não implementada — pendia de confirmação, ver
tarefa original): melhorar o label "Nome, razão social, número..." pra
algo mais explicativo (ex.: "Buscar por nome, razão social ou número
da Obra") e/ou adicionar `->helperText(...)` no campo do filtro,
citando explicitamente que a busca é sobre OS CADASTROS (Obra, Pessoa
Física/Jurídica etc.), não sobre a empresa que usa o sistema nem sobre
o usuário que fez a ação.

## Busca da Central de Auditoria unificada na caixa "Pesquisar" padrão (2026-08-29)

A sugestão da tarefa anterior (melhorar o label do filtro dedicado)
virou uma decisão maior: o `Filter` separado "Nome, razão social,
número..." foi **removido**, e a mesma lógica de busca
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
`Table::searchPlaceholder(__('...search_placeholder'))` substitui o
placeholder genérico "Pesquisar" por um texto explicando o que a busca
cobre (`Buscar por nome, razão social ou número da Obra...`).

### Case-insensitive: já vinha "de graça" da collation, sem precisar de `LOWER()`

Antes de mexer em código, foi conferido se este projeto tem algum
padrão já estabelecido de busca case-insensitive — achado no próprio
Filament: `Filament\Support\generate_search_term_expression()` (usada
pela busca padrão de QUALQUER `TextColumn::searchable()` sem `query:`
customizado) só força `Str::lower()` no termo quando o driver do banco
é `pgsql`; pra `mysql`/`mariadb` (este projeto), o padrão é confiar na
collation da própria coluna. Uma checagem com `SHOW FULL COLUMNS`
em cada uma das 14 colunas reais que `applyBusca()` compara
(`obras.descricao`/`numero_obra`, `pessoas_fisicas.nome`,
`pessoas_juridicas.razao_social`/`nome_fantasia`/`cnpj`,
`tipos_obra.descricao`, `situacoes_obra.descricao`,
`categorias_pessoa.descricao`, `setores.descricao`,
`enderecos.logradouro`/`bairro`/`municipio`, `contatos.cargo`)
confirmou que TODAS usam `utf8mb4_unicode_ci` — uma collation
case-insensitive por definição (sufixo `_ci`), o que o MySQL/MariaDB já
respeita em comparações `LIKE` sem qualquer `LOWER()` extra. Confirmado
empiricamente também (não só por collation): `PessoaJuridica::where('razao_social',
'like', '%smartfit%')` (minúsculo) encontrou normalmente
"SMARTFIT ESCOLA DE GINASTICA E DANCA S.A" (maiúsculo). **Não foi
adicionado `LOWER()` nos dois lados** — seria redundante (o
comportamento já é case-insensitive) e inconsistente com o próprio
padrão do Filament pra MySQL/MariaDB, que também não adiciona.
Se um dia uma coluna nova entrar em `applyBusca()` com collation
diferente (`_bin`/`_cs`, raríssimo neste projeto — nenhuma migration
força collation própria hoje), essa premissa deixa de valer e precisa
ser revista.

### Validado (via `Livewire::test()->set('tableSearch', ...)`, dados reais)

1. Busca minúscula "smartfit" encontrou a Pessoa Jurídica cuja razão
   social é "SMARTFIT ESCOLA DE GINASTICA E DANCA S.A" (maiúscula).
2. Busca por parte do número de uma Obra ("2630" de "2630001")
   encontrou o log correto.
3. Filtro dedicado "Nome, razão social, número..." confirmado ausente
   do HTML renderizado; placeholder novo confirmado presente.
4. Busca + `modulo`, busca + `causer_id` combinados: interseção `AND`
   correta (inclusive um caso em que o mesmo termo curto batia com um
   registro de Comercial E um de Pessoas ao mesmo tempo — cada filtro
   de módulo restringiu certo pro seu lado).
5. Navegação completa (`Filament::getNavigation()`) conferida — nenhum
   grupo afetado; `ddev artisan optimize:clear` executado ao final.

## Botão "Editar" morto no detalhe do log de Auditoria — removido via config oficial do pacote (2026-08-29)

Na tela de detalhe de um log (Configurações → Auditoria → Visualizar),
o card "Mudanças" tinha um botão "Editar" que não fazia nada ao
clicar. Investigado até a origem: é o `Action::make('edit')` de
`Rmsramos\Activitylog\Resources\Activitylog\Schemas\ActivitylogForm`
(vendor do pacote) — **não é "editar o log"**, é um link pensado pra
abrir a tela de edição do REGISTRO ORIGINAL afetado (ícone de olho,
mas rótulo hardcoded `__('activitylog::action.edit')` = "Editar" em
pt_BR — inconsistência já existente no próprio pacote, não introduzida
por nós).

**Causa raiz confirmada (não só suposta) por que sempre ficava morto
neste projeto**: `ActivitylogResource::getResourceUrl()` monta o nome
da rota via convenção fixa `filament.{painel}.resources.{plural-kebab-
do-basename}.edit` (ex.: pra `Perseu\Comercial\Models\Obra`, tenta
`filament.admin.resources.obras.edit`) — mas TODOS os nossos Resources
auditados são clusterizados (Obras dentro do Cluster `Obras`, Pessoa
Física/Jurídica dentro do Cluster `Pessoas`), então a rota real sempre
leva o slug do Cluster no meio (`filament.admin.comercial.resources.obras.edit`,
ver seção do Cluster "Obras" acima). `route()` lança
`RouteNotFoundException`, capturada internamente, e o método sempre
devolve `'#'` — confirmado chamando `ActivitylogResource::getResourceUrl()`
manualmente num log de um registro AINDA VIVO (não excluído): mesmo
com o registro existindo e `canViewResource()` retornando `true`, a
URL ainda saía `'#'`. Não é um problema de permissão nem de registro
excluído — é estrutural (o pacote não tem suporte a Resources
clusterizados/com slug customizado nessa convenção).

**Correção**: `ActivitylogPlugin::isResourceActionHidden(true)`
(extensão OFICIAL do pacote pra isso, não um fork do Schema) adicionada
no encadeamento de `ActivitylogPlugin::make()` em
`AuditoriaServiceProvider::packageRegistered()`. Decisão consciente de
ESCONDER (não consertar o link): mesmo que a URL funcionasse, um log
de auditoria não deveria oferecer edição do registro original a partir
dali — contraria o propósito de imutabilidade da auditoria. As outras
duas ações do mesmo card ("Restaurar" — reverte pra um valor anterior
de um log de `updated`; "Restaurar Modelo" — desfaz um soft-delete a
partir do log de `deleted`) têm flags de visibilidade PRÓPRIAS
(`getIsRestoreActionHidden()`/`canRestoreSubjectFromSoftDelete()`) e
não foram afetadas — confirmado com `git stash` (antes/depois) que só
o "Editar" sumiu, o resto do card (Usuário, Assunto, Descrição,
Mudanças) continua idêntico.

## Restaurar um registro excluído a partir da Auditoria — levantamento, NÃO implementado (2026-08-29)

Pedido explícito de só investigar e relatar, sem implementar nada
agora. Registrado aqui pra não perder o levantamento.

### Onde restaurar hoje

Não existe atalho a partir da Auditoria — é preciso ir até o Resource
do cadastro específico, aplicar o `TrashedFilter` ("Lixeira") e usar
`RestoreAction`/`RestoreBulkAction` ali. Caminho de clique real (3
Resources que têm essa Lixeira hoje — ver "Auditoria (log de
atividade) + Lixeira completa"):
- Obra: Comercial → Obras → filtro "Lixeira" (`without_trashed`/
  `with_trashed`/`only_trashed`) → botão "Restaurar" na linha.
- Pessoa Física/Jurídica: Pessoas → Pessoas Físicas/Jurídicas → mesmo
  filtro → "Restaurar".
- Os outros 6 Models auditados (Tipo/Situação de Obra, Categoria,
  Setor, Endereço, Contato) não têm Lixeira nenhuma hoje (limitação já
  registrada antes) — não há pra onde "restaurar" esses via UI, só via
  tinker/DB direto.

### Atalho "Ir para a Lixeira deste cadastro" a partir de um log `deleted` — viável, esforço pequeno

Confirmado por leitura do código-fonte do Filament (não só suposição):
`Filament\Resources\Pages\ListRecords` declara
`#[Url(as: 'filters')] public ?array $tableFilters` — ou seja, o
Livewire já sincroniza o estado dos filtros da tabela com a query
string `?filters[...]=...` da própria URL, mecanismo OFICIAL do
Filament (usado por ele mesmo pra "links de filtro compartilháveis"),
não algo que precisaríamos construir do zero. `TrashedFilter` é um
`TernaryFilter` com `queries(true: withTrashed(), false: onlyTrashed(),
blank: withoutTrashed())` — ou seja, o estado que interessa aqui
(“somente excluídos”) corresponde a `value = false`. Um link do tipo
`ObraResource::getUrl('index', ['filters' => ['trashed' => ['value' => false]]])`
deveria abrir a listagem de Obras já com "Somente excluídos" aplicado
(a codificação exata de `false` na query string — `0`, `false`, chave
ausente — não foi validada ponta-a-ponta num teste de HTTP real nesta
tarefa, só confirmada a existência do mecanismo via código-fonte;
checar isso num teste de navegador de verdade é o primeiro passo se
formos implementar).

Pra existir de fato, faltaria:
1. Um mapa FQCN → Resource::class (pequeno, só pros 3 Models que têm
   Lixeira hoje — Obra, PessoaFisica, PessoaJuridica) — natural
   extensão de `SubjectTypeCatalog`, mas cuidado pra não confundir com
   o mapa de rótulos/módulo já existente (nome de método separado, ex.
   `resourceUrlFor()`, pra não modelos sem Lixeira acabarem com um link
   quebrado).
2. Uma `Action` visível só quando `$record->event === 'deleted'` E o
   `subject_type` está nesse novo mapa — no header da página
   `ViewAuditoria` (mais natural, já que é aí que se vê o evento
   "Excluído") ou como ação de linha em `ListAuditoria`.

**Estimativa**: pequena — algumas horas, a maior parte só validando a
codificação exata da query string do `TrashedFilter` num navegador
real (`Livewire::test()`/tinker não simulam fielmente o ciclo de
request HTTP que popula `#[Url]`, confirmado tentando e não
conseguindo reproduzir de forma confiável nesta investigação — validar
isso é trabalho de implementação, não de levantamento).

### Regra pra quando uma ação de restaurar/reverter A PARTIR da Auditoria for implementada (registrado, não implementado)

Se um dia a Auditoria ganhar uma ação que restaure/reverta o registro
DIRETAMENTE dali (diferente do atalho acima, que só leva pra tela do
Resource — lá as ações já existentes de `RestoreAction`/`ForceDeleteAction`
já têm suas próprias confirmações padrão do Filament):

**Essa ação deve exigir confirmação prévia (`->requiresConfirmation()`)
com uma modal que informe, no mínimo**:
1. Qual registro será afetado — cadastro (rótulo de
   `SubjectTypeCatalog::label()`) + referência
   (`SubjectTypeCatalog::referenceFor()`, ex.: "Obra 2610001 — Nome da
   Obra"), não só o ID técnico.
2. O que exatamente a ação vai mudar (ex.: "vai restaurar os valores
   anteriores a esta edição" pra um `updated`, ou "vai desfazer a
   exclusão (soft delete) deste registro" pra um `deleted`).
3. Um alerta explícito sobre possível inconsistência de
   relacionamentos: registros com `CascadesRelatedDataOnForceDelete`/
   lógica equivalente em `forceDeleting` (`PessoaJuridica`/
   `PessoaFisica`, ver seção "Excluir Pessoa Jurídica em cascata") já
   apagaram Endereços/Contatos numa exclusão DEFINITIVA — restaurar um
   registro que passou por isso não traz esses dados relacionados de
   volta, e a modal precisa deixar isso claro pro usuário não assumir
   que "restaurar" devolve o registro 100% como era antes.

Motivo de registrar isso agora sem implementar: a lógica de cascata
(`forceDeleting`) já existe e é fácil de esquecer o alerta quando a
funcionalidade de restaurar-a-partir-da-Auditoria for implementada de
fato — melhor a regra já estar escrita aqui do que descoberta de novo
na hora.

## Lixeira Central (Configurações → Lixeira) agregando Excluídos de todos os cadastros (2026-08-29)

Nova página `Perseu\Auditoria\Filament\Pages\Lixeira`
(`plugins/perseu/auditoria/src/Filament/Pages/Lixeira.php`), ao lado de
"Auditoria" no cluster de Configurações — lista os registros
soft-deleted de TODOS os cadastros com Lixeira hoje numa tabela só,
com Restaurar/Excluir Permanentemente por linha e em lote.

### Escopo real: só 3 Models (confirmado por grep, não pela lista de exemplo da tarefa)

A tarefa citava "Obra, Pessoa Jurídica, Pessoa Física, Categoria,
Setor" como exemplo — **Categoria de Pessoa e Setor NÃO usam
`SoftDeletes`** (confirmado com `grep -rl "use SoftDeletes"
plugins/perseu/*/src/Models/*.php`: só `Obra`, `PessoaJuridica`,
`PessoaFisica`), mesma limitação já documentada na seção "Limitação
conhecida" de uma tarefa anterior. `Perseu\Auditoria\Support\TrashCatalog::models()`
é a lista oficial (subconjunto de `SubjectTypeCatalog`, que cobre os 9
Models AUDITADOS — a maioria sem Lixeira de UI) — adicionar um Model
novo aqui quando ele ganhar `SoftDeletes` + Lixeira de UI no próprio
Resource no futuro.

### Por que NÃO é uma `VIEW` de banco com `UNION ALL` — dependência circular de plugins

Antes de escrever código, foi avaliada a abordagem "clássica" de
agregação multi-tabela: uma `VIEW` SQL unindo `obras`/
`pessoas_juridicas`/`pessoas_fisicas` (com `WHERE deleted_at IS NOT
NULL`), com um Model de leitura por cima — daria paginação/ordenação
nativa do SQL de graça. **Descartada**: essa `VIEW` teria que viver
numa migration de algum plugin, e o candidato óbvio (`perseu/auditoria`,
onde `SubjectTypeCatalog` já importa Models de `comercial`/`pessoas`)
já é DEPENDÊNCIA de `comercial`/`pessoas`
(`->hasDependency('auditoria')` nos dois, por causa do trait
`LogsBusinessActivity` — ver `ComercialServiceProvider`/
`PessoasServiceProvider`). Se `auditoria` também declarasse
`->hasDependency('comercial')`/`->hasDependency('pessoas')` (pra
ordenar a criação da `VIEW` depois das tabelas que ela referencia),
seria um CICLO (`comercial → auditoria → comercial`) — o
`Webkul\PluginManager\Models\Plugin` (`plugin_dependencies`, pivot de
UI/gestão de plugins) não foi desenhado pra suportar isso. Nota
técnica à parte: migrations do Laravel rodam por ordem de TIMESTAMP no
nome do arquivo, não por `hasDependency()` (isso só afeta a UI de
gestão de plugins/ordem de instalação) — então o problema real não era
de ORDEM DE EXECUÇÃO da migration, e sim do GRAFO DE DEPENDÊNCIA
declarado ficar inconsistente/circular.

### Mecanismo real: `Table::records(Closure)` — hook oficial do Filament v4 pra tabelas sem Eloquent Builder

`Filament\Tables\Table\Concerns\HasRecords::records(Closure $dataSource)`
é o mecanismo OFICIAL (não workaround) do Filament v4 pra uma tabela
cuja fonte de dados não é uma query Eloquent simples — quando presente,
`Table::hasQuery()` retorna `false`, e
`Concerns\HasRecords::getTableRecords()` (`filament/tables`, lido no
vendor antes de implementar) chama o closure passando o estado atual
como parâmetros NOMEADOS (`filters`, `sortColumn`, `sortDirection`,
`page`, `recordsPerPage`, `search`, `columnSearches`) em vez de montar
uma `Builder` — a aplicação desse estado (filtrar/ordenar/paginar) fica
inteiramente por conta do closure. Confirmado também que
`getSelectedTableRecords()` (`HasBulkActions`) já tem um ramo próprio
pra `! hasQuery()` (usa os registros já retornados por `records()`,
filtrados pelas chaves selecionadas) — bulk actions funcionam sem
nenhuma configuração extra.

Cada linha da tabela é um **array** (`Filament\Support\ArrayRecord`,
chave `'__key'` por padrão), não uma instância real de Model — os 3
Models têm PKs numéricas que colidem entre si (Obra #5 e Pessoa
Jurídica #5 são registros DIFERENTES) — a chave de cada linha é
sintética: `"{$slugDoModel}-{$id}"` (ex.: `"obra-5"`,
`"pessoajuridica-11"`).

### Trade-off consciente: pagina em PHP, não em SQL

`buildPaginator()` busca TODOS os registros excluídos que passam nos
filtros ativos (não só a página pedida) pras 3 fontes, junta numa
`Collection` só, ordena em PHP (`sortBy`/`sortByDesc` conforme a coluna
clicada), e só DEPOIS fatia a página com `->slice()` +
`LengthAwarePaginator` manual. Correto pro volume real deste sistema
(Lixeira de ERP interno — dezenas/poucas centenas de linhas, não
milhões) — errado em escala muito maior, onde a alternativa de `VIEW`
(resolvendo a dependência circular de outro jeito, ex.: migration no
próprio app, fora do ciclo de plugins, mesmo padrão já usado pra
`activity_log`) voltaria a valer a pena. Não implementado assim agora
por ser complexidade desnecessária pro volume atual.

### Reaproveitamento de lógica — o requisito mais crítico da tarefa

**Nem `Filament\Actions\RestoreAction`/`ForceDeleteAction` prontas
(usadas nos Resources individuais) foram reaproveitadas AQUI
diretamente** — essas classes assumem que `$record` recebido pelas
suas closures internas é o Model de verdade (chamam `$record->restore()`
literalmente); como nossas linhas são arrays, isso quebraria
(`Call to a member function restore() on array`). Em vez disso,
`Action::make('restaurar')`/`Action::make('excluir_definitivamente')`
próprias resolvem o Model real
(`resolveModel()`: `$model::onlyTrashed()->find($id)`) e chamam
`->restore()`/`->forceDelete()` NELE — **é isso que satisfaz "reaproveitar
a lógica, não duplicar"**: a cascata de Endereços/Contatos ao excluir
definitivamente uma Pessoa Jurídica/Física mora no `forceDeleting` do
PRÓPRIO Model (`boot()`, ver seção "Excluir Pessoa Jurídica em
cascata"/"Auditoria... Lixeira completa"), então chamar `->forceDelete()`
no Model real dispara essa lógica automaticamente, de onde quer que a
chamada venha (Resource individual OU Lixeira Central) — reescrever a
cascata aqui teria sido exatamente o risco de "registro fantasma" que
a tarefa pediu pra evitar.

**Validado de ponta a ponta** (não só lido no código): criados uma
Obra e uma Pessoa Jurídica de teste (a PJ com Endereço + Contato
vinculados de propósito), soft-deletadas, confirmado que ambas
aparecem juntas na Lixeira; `Restaurar` via
`Livewire::test()->callTableAction('restaurar', $key)` devolveu a Obra
pro estado normal (visível de novo em Comercial → Obras);
`Excluir Permanentemente` na Pessoa Jurídica removeu o Endereço e o
Contato vinculados junto (cascata funcionando, mesma verificada nas
tarefas anteriores) — nenhum dado ficou "fantasma". Ação em lote
testada com uma Obra E uma Pessoa Jurídica selecionadas AO MESMO
TEMPO, tanto pra Restaurar quanto pra Excluir Permanentemente — as
duas foram processadas corretamente numa única chamada.

### Permissão — sem Resource/Policy própria, de propósito

Pedido explícito da tarefa: NÃO criar uma permissão genérica
"gerenciar lixeira de tudo". `Lixeira` não é um `Filament\Resources\Resource`
(não tem Model próprio pra ser dono de uma Policy) — é um
`Filament\Pages\Page implements Tables\Contracts\HasTable`, sem
nenhuma entrada nova em `config/filament-shield.php`. Cada linha
verifica a Policy JÁ REGISTRADA do Model real
(`Gate::allows('restore', $modelReal)` / `Gate::allows('forceDelete',
$modelReal)` — mesmíssimo `ObraPolicy`/`PessoaFisicaPolicy`/
`PessoaJuridicaPolicy` que já controla `RestoreAction`/`ForceDeleteAction`
no Resource individual). `canAccess()` da própria página (controla se
"Lixeira" aparece na sidebar de Configurações) checa
`restoreAny`/`forceDeleteAny` de QUALQUER um dos 3 Models — se o
usuário não tiver nenhuma dessas 6 permissões, o item nem aparece.

**Validado com um usuário/Role de teste temporários** (criados e
apagados só pra este teste): Role com `restore_any_comercial_obra`
(dá acesso à página) mas SEM `restore_comercial_obra`/
`force_delete_comercial_obra`, e com permissão completa de restaurar/
excluir definitivamente em Pessoa Jurídica. Resultado: a página abriu
normalmente, as DUAS linhas (Obra e Pessoa Jurídica de teste)
apareceram juntas, mas `Gate::allows('restore'|'forceDelete', ...)`
confirmou `false` pra Obra e `true` pra Pessoa Jurídica — exatamente o
comportamento esperado (visibilidade por linha, não por página
inteira). Ação em lote com seleção mista (itens permitidos e não
permitidos) soma quantos foram processados vs. pulados por falta de
permissão numa notificação só, em vez de falhar silenciosamente ou
travar a operação inteira.

### "Excluído por" — cruzado com `activity_log`, em lote (não por linha)

Pra cada tipo de Model presente na página atual, uma única query
(`Activity::where('subject_type', $model)->where('event','deleted')->whereIn('subject_id', $ids)`)
busca o log de exclusão mais recente de cada `subject_id` — no máximo
3 queries extras por carregamento de página (uma por Model presente),
não uma por linha. Quando não existe log de exclusão pra um registro
(ex.: apagado antes da auditoria existir no sistema), a coluna mostra
vazio sem erro.

### Filtros: Módulo/Cadastro/Período — sem `->query()`, aplicados manualmente

Igual à Auditoria, filtros por Módulo e Cadastro (`SubjectTypeCatalog`
reaproveitado) e um novo filtro de período (`Excluído de`/`Excluído
até`, mesma UX do filtro de data da Auditoria, aqui sobre `deleted_at`).
**Diferença importante em relação à Auditoria**: como esta tabela não
tem `Builder` (`records()`), o Filament NUNCA chama `->query()` de
filtro nenhum aqui — confirmado lendo `HasRecords::getTableRecords()`
antes de escrever o código. Os filtros só existem pra desenhar a UI
(`SelectFilter`/`Filter` com `->options()`/`->form()`); a aplicação de
fato é manual, lendo `$filters['modulo']['value']`/
`$filters['subject_type']['value']`/`$filters['excluido_em']['excluido_de']`
dentro de `collectRows()`. Sem caixa de busca textual unificada (ao
contrário da Auditoria) — não foi pedida nesta tarefa, mantido o
escopo do que foi solicitado (Módulo/Cadastro/Período).

### Filtro "Excluídos" nos Resources individuais — MANTIDO, não removido

A tarefa pediu explicitamente pra só remover o filtro "Excluídos"/ações
de Restaurar/Excluir Permanentemente de dentro de cada Resource
individual DEPOIS de confirmar com o usuário que a Lixeira Central já
substitui bem esse acesso — **essa confirmação ainda não aconteceu**,
então `ObraResource`/`PessoaFisicaResource`/`PessoaJuridicaResource`
continuam com `TrashedFilter`/`RestoreAction`/`ForceDeleteAction`
exatamente como antes. Revisitar isso só depois de uso real da Lixeira
Central confirmar que ela é suficiente.

## `forceDeleted` nunca foi logado pelo Spatie Activitylog — descoberto e corrigido, não só "renomeado" (2026-08-29)

Usuário tentou buscar "defi" na caixa "Pesquisar" da Auditoria
esperando achar os logs de "Excluído Definitivamente" (exclusão
permanente, disparada pela Lixeira Central ou pelo `ForceDeleteAction`
de cada Resource) — não achou nada, e o dropdown "Eventos" nem sequer
listava essa opção.

**Causa raiz não era rótulo/tradução faltando — era o evento nunca ter
sido gravado**, confirmado por dois caminhos independentes antes de
mexer em qualquer código: (1) lendo `vendor/spatie/laravel-activitylog/src/Traits/LogsActivity.php`,
`eventsToBeRecorded()` só retorna `created`/`updated`/`deleted` (+
`restored` se o Model usa `SoftDeletes`) — nenhuma menção a
`forceDeleted` em lugar nenhum do pacote; (2) uma query direta
(`Activity::distinct()->pluck('event')`) num banco com histórico real
de vários `forceDelete()` (inclusive os da tarefa da Lixeira Central)
só retornava `created/updated/deleted/restored`, nunca `forceDeleted`.
`Illuminate\Database\Eloquent\SoftDeletes::forceDelete()` já dispara os
eventos Eloquent `forceDeleting`/`forceDeleted` nativamente (é assim
que `CascadesRelatedDataOnForceDelete` já se pendura em
`forceDeleting` pra cascata de Endereço/Contato) — só não existia
NENHUM listener registrando isso como uma `Activity`.

### Correção: listener próprio em `forceDeleted`, fora da maquinaria do Spatie

`Perseu\Auditoria\Traits\LogsBusinessActivity::bootLogsBusinessActivity()`
(novo) registra `static::forceDeleted(function ($model) {
activity()->causedBy(auth()->user())->performedOn($model)->event('forceDeleted')->log('forceDeleted');
})` — só quando o Model usa `SoftDeletes`
(`in_array(SoftDeletes::class, class_uses_recursive(static::class))`,
checado ANTES de registrar: `forceDeleted()` é um método estático que
só existe nesse trait, chamá-lo num Model sem `SoftDeletes` explodiria
com "Call to undefined method"). Deliberadamente NÃO tentei encaixar
isso em `eventsToBeRecorded()`/no fluxo interno do
`LogsActivity::bootLogsActivity()` (que é `protected static`, herdado
via `use LogsActivity` dentro do nosso próprio trait — PHP não dá
`parent::` entre traits, então "estender" aquele método exigiria
reescrever a lógica inteira dele aqui) — um listener PRÓPRIO e
independente em `forceDeleted` é mais simples e não arrisca interações
sutis com o mecanismo genérico do pacote, que assume um conjunto fixo
de nomes de evento em vários pontos internos
(`shouldLogEvent()`/`attributeValuesToBeLogged()`).

Respeita o mesmo "kill switch" global do Spatie
(`app(Spatie\Activitylog\ActivityLogStatus::class)->disabled()`, o
mecanismo por trás de `activity()->withoutLogs(fn () => ...)`) — se
algum código no futuro envolver um `forceDelete()` em
`withoutLogs()`, este listener também respeita isso.

**Validado criando uma Obra de teste, soft-deletando e force-deletando
de verdade**: a `Activity` com `event = 'forceDeleted'` apareceu no
banco com `causer` preenchido corretamente.

### Rótulo traduzido — override de UMA chave via `lang/vendor/`, não fork do pacote

O pacote de UI (`rmsramos/activitylog`) também nunca precisou de um
rótulo pra um evento que nunca existia, então
`vendor/rmsramos/activitylog/resources/lang/{pt_BR,en}/action.php` não
tinham a chave `event.forceDeleted`. Em vez de publicar/duplicar o
arquivo inteiro do pacote (grande, e divergiria em atualizações
futuras), foram criados `lang/vendor/activitylog/{pt_BR,en}/action.php`
com SÓ a chave nova — mecanismo padrão do Laravel
(`Illuminate\Translation\FileLoader::loadNamespaceOverrides()`, que
faz `array_replace_recursive()` do que estiver em
`lang/vendor/{namespace}/{locale}/{grupo}.php` por CIMA da tradução
original do pacote, não substituindo o arquivo inteiro) — confirmado
que as chaves antigas (`created`/`deleted`/`updated`/`restored`,
`modal`, `view` etc.) continuam funcionando normalmente depois do
override. `pt_BR`: "excluído definitivamente" (via `ucwords()` já
aplicado por `getEventColumnComponent()`, vira "Excluído
Definitivamente" na tela — mesmo padrão dos outros eventos).

### Busca ampliada: "Pesquisar" agora cobre Registro E Evento ao mesmo tempo

`AuditoriaResource::getEventColumnComponent()` foi sobrescrito (antes
herdava direto do pacote) só pra somar `->searchable(query: ...)`: o
termo digitado é comparado contra o RÓTULO TRADUZIDO de cada evento
("excluído definitivamente", não `forceDeleted`, já que é isso que o
usuário digita), descobre quais valores TÉCNICOS batem, e faz
`whereIn('event', [...])` — mesma técnica de "tradução inversa" já
usada em outros lugares deste projeto. Quando nada bate, retorna
`whereRaw('1 = 0')` (não a query sem alteração — um grupo `orWhere(fn
($q) => ...)` vazio equivale a `true` em SQL, bateria com QUALQUER
termo, não com nenhum — mesmo cuidado já tomado em
`SubjectTypeCatalog::applyBusca()`).

Como o Filament já soma com `OR` automático entre TODAS as colunas
marcadas `searchable()` de uma tabela (`InteractsWithTableQuery::applySearchConstraint()`,
o mesmo mecanismo que já une as buscas de múltiplas colunas nativas do
Filament), bastou marcar a coluna `event` como buscável — nenhuma
mudança foi necessária em `getSubjectReferenceColumnComponent()`
(a busca por registro) pra elas conviverem na mesma caixa.

**Validado reproduzindo exatamente o teste do usuário**: buscar "defi"
agora encontra o(s) log(s) de exclusão definitiva (antes: zero
resultados); buscar "Reg" continua sem resultado (não existe evento
nem dado de registro contendo esse texto — comportamento correto, não
um problema novo); buscar "restaurado" encontra todos os eventos de
restauração; combinado com o filtro Módulo (`defi` + módulo=Comercial
→ encontra; `defi` + módulo=Pessoas → não encontra, já que o teste foi
numa Obra) confirma que busca por evento e filtros continuam
interseccionando corretamente; a busca por nome/razão social/número
(já existente) continua funcionando sem regressão.

## Auditoria: período padrão de 1 ano + filtro de Eventos multi-seleção (2026-08-29)

Decisão que motivou esta tarefa: **não implementar exclusão automática
de logs antigos** — a tabela `activity_log` é leve e o histórico tem
valor de auditoria/fiscal a longo prazo, registrar isso aqui evita a
pergunta se repetir. Em vez disso, dois ajustes de usabilidade na
listagem (que já mostra tudo, pra sempre): a lista abre já filtrada
pro último ano (ajustável/limpável livremente), e o filtro de Eventos
virou "desmarque o que não quer ver" em vez de "marque só um".

### Período padrão — `->default()` no `DatePicker`, formato ISO (não o de exibição)

`getDateFilterComponent()` sobrescrito (cópia do original do pacote +
`->default(now()->subYear()->toDateString())` só no campo "Criado a
partir de"; "Criado até" sem default, de propósito — só o passado é
limitado). **Achado importante, confirmado empiricamente antes de
decidir o formato**: `ActivitylogPlugin::get()->getDateFormat()`
(`d/m/Y`, usado pelo pacote só para EXIBIÇÃO) NÃO é o formato que o
`DatePicker` realmente usa no valor dehydratado/state interno —
testado passando os dois formatos via `Livewire::test()->set(...)`:
`Y-m-d` funcionou normalmente; `d/m/Y` quebrou a renderização do
indicador do filtro com `Could not parse '29/08/2026'`
(`ActivitylogPlugin::getDateParser()` usa `Carbon::parse()` sem
formato explícito — ambíguo pra `dd/mm/yyyy`, falha quando o dia é
> 12). Se tivesse assumido `d/m/Y` (o valor mais "óbvio" à primeira
vista, já que é o que aparece na tela) sem testar, o default quebraria
a página pra qualquer usuário cujo dia atual fosse > 12.

Validado forçando via SQL direto a `created_at` de um log real pra 2
anos atrás (teste revertido ao final): com o default aplicado, esse
log some da lista (101 de 102); limpando o filtro manualmente, ele
volta (102) — confirma que o default filtra de verdade E que
"ajustar/limpar livremente" continua funcionando.

### Filtro de Eventos — multi-seleção com tudo marcado, não `DISTINCT event`

`getEventFilterComponent()` sobrescrito: `SelectFilter::make('event')->multiple()`
com `->default(eventoKeys())` (todos os 5 valores técnicos marcados),
pra abrir exatamente como hoje (tudo visível) mas já pronto pra
desmarcar. `eventoKeys()` (novo método, `protected static`) é uma
lista FIXA (`created/updated/deleted/forceDeleted/restored`), não o
`DISTINCT event` que a versão original do pacote usa — precisamos que
as 5 opções apareçam sempre marcáveis mesmo se algum evento (ex.:
`restored`) ainda não tiver ocorrido nenhuma vez neste banco;
`DISTINCT` simplesmente não a listaria antes da primeira ocorrência.
Mesma lista reaproveitada por `getEventColumnComponent()` (busca por
evento, tarefa anterior) — extraída pra não duplicar o array em dois
lugares.

Validado: desmarcar `forceDeleted` (deixando só
`created/updated/deleted/restored`) tira exatamente 1 registro da
lista (101 de 102, batendo com `Activity::whereIn(...)->count()`
direto no banco); combinado com o filtro de Módulo ao mesmo tempo
(`event=created` + `modulo=comercial`) intersecciona corretamente.

### Item 4 da tarefa — filtro "alinhado na coluna Eventos": não é nativo, mantido no painel de Filtros

Avaliado antes de implementar: `Filament\Tables\Enums\FiltersLayout`
(`AboveContent`/`BelowContent`/`BeforeContent`/`AfterContent`/`Dropdown`/`Modal`/`Hidden`
— lido no vendor) controla só a POSIÇÃO do painel de filtros inteiro
em relação à tabela, nunca um dropdown ancorado a uma coluna
específica (padrão "cabeçalho de coluna do Excel"). Não existe esse
recurso nativo nesta versão do Filament. Construir isso do zero
(dropdown customizado embutido no header da coluna, sincronizado com o
mesmo estado de filtro) seria consideravelmente mais trabalho por
puro ganho estético, sem funcionalidade nova — mantido no painel de
Filtros padrão (ícone de funil), só com a multi-seleção pedida.

## Localização do cadastro de Empresa (Company) pro padrão brasileiro — Empresa e Filiais (2026-08-30)

`Webkul\Support\Models\Company` (Configurações → Empresas) — Model
CORE do AureusERP, integrado a multi-tenancy/segurança — foi
LOCALIZADO pro padrão brasileiro de Pessoa Jurídica (CNPJ, busca
automática via BrasilAPI, endereço com CEP/UF), decisão consciente de
**adaptar os campos em vez de substituir o Model** por `PessoaJuridica`
(mexer na integração de segurança/multi-empresa seria arriscado demais
pro ganho). Filial (`Branch`) NÃO é um Model separado — é a MESMA
tabela `companies`, auto-referencial via `parent_id`
(`Company::branches(): HasMany` = `hasMany(Company::class, 'parent_id')`)
— por isso `BranchesRelationManager` recebeu exatamente o mesmo
tratamento, com o próprio `form()`/`infolist()` (não herda do
`CompanyResource`, é uma classe irmã que duplica a estrutura desde a
origem do AureusERP).

### Estudo obrigatório ANTES de mexer em qualquer coluna — dois agentes de investigação, não suposição

Antes de qualquer alteração, dois levantamentos extensos (não
suposição) confirmaram:

1. **`registration_number` E `company_id` (coluna própria, string
   única — NÃO a PK numérica, nome infeliz do AureusERP original) SÃO
   usados internamente**: `Company::boot()` copia
   `registration_number` → `Partner.company_registry` em `creating`
   E `saved`; ambos aparecem em `Http/Resources/V1/CompanyResource.php`
   (API pública) e em `database/seeders/CompanySeeder.php`/
   `database/factories/CompanyFactory.php`. **Decisão**: campos
   ESCONDIDOS do formulário (`->hidden()`), NÃO removidos — coluna,
   `$fillable` e sincronização com Partner continuam intactos.
2. **`tax_id` — desvio deliberado da instrução literal da tarefa**: em
   vez de remover `tax_id` e criar uma coluna `cnpj` nova, `tax_id` foi
   **reaproveitado como CNPJ** (mesmo tratamento dado a `name`→Razão
   Social). Já tinha `unique()->nullable()` (exatamente o que CNPJ
   precisa) e já sincronizava pra `Partner.tax_id`
   (`Company::boot()`); nenhum PDF/e-mail do sistema hoje exibe
   `tax_id` (confirmado por grep completo), então repropor seu
   conteúdo não quebra nada visível.
3. **`name` — usado bem além do CRUD de Empresa**: aparece no
   `company-switcher.blade.php` (dropdown de troca de empresa na
   topbar) e em SEIS templates de impressão/PDF diferentes (cotação de
   vendas, pedidos de compra, romaneio/nota de separação, fatura/
   recibo/nota de crédito) via `$record->company->name`, além de ser
   serializado inteiro (`current_company()->toArray()`) em TODO e-mail
   automático do sistema (`$payload['from']['company']`). Como a
   COLUNA `name` nunca foi renomeada (só o LABEL do formulário virou
   "Razão Social"), nenhuma dessas dezenas de pontos de uso foi afetada
   — confirmado que esse é exatamente o motivo de nunca renomear uma
   coluna existente neste tipo de Model amplamente consumido, só
   adicionar novas.
4. **Endereço nos PDFs vem de `Company->partner->{campo}`, não de
   `Company->{campo}` diretamente** — `Company::boot()` sincroniza
   `street1`/`street2`/`city`/`zip`/`state_id`/`country_id` pro
   Partner vinculado, e é o Partner que os templates de impressão leem.
   `bairro`/`numero` (as duas colunas genuinamente novas) NÃO entram
   nesse sync hoje (nenhum consumidor precisa deles ainda, já que não
   existe emissão de NF-e implementada) — ponto de atenção se/quando a
   emissão de NF-e for implementada: estender `Company::boot()` pra
   sincronizar esses dois campos também.
5. **Nenhuma Policy/Guard/scope de multi-tenancy depende do VALOR**
   desses campos (`CompanyPolicy` é só permissão-string;
   `CompanyContext`/`RestrictToAllowedCompanies`/`CompanyScope`
   operam só sobre o `id` numérico) — confirmado antes de mexer, não
   assumido.
6. **Achado incidental corrigido de graça**: o infolist de
   `BranchesRelationManager` (aba "Informações de endereço") referenciava
   `address.street1`/`address.city`/etc. — `Company` NUNCA teve uma
   relação/accessor `address` (bug pré-existente da implementação
   AureusERP original, não desta tarefa), então essa aba sempre
   mostrou só "—" vazio, mesmo com os campos preenchidos no formulário.
   Corrigido pra `street1`/`city`/etc. (os atributos reais) já que
   esta tarefa reescreveu exatamente essa seção. Um bug idêntico existe
   em `plugins/webkul/sales/resources/views/sales/quotation.blade.php`
   (`$record->company->address`, sempre `null`) — **não corrigido**,
   plugin/contexto diferente, fora do escopo desta tarefa.
7. **Achado que exige atenção do usuário, não corrigido
   silenciosamente**: o registro real "Fa Marcenaria" (única Empresa
   cadastrada hoje) tem `tax_id = "Inscrição"` — claramente um valor de
   teste/placeholder, não um CNPJ. Como o campo agora tem
   `->rule(new CnpjValido())`, a PRÓXIMA tentativa de salvar esse
   registro (mesmo editando outro campo) vai falhar a validação até o
   CNPJ real ser digitado ali. Não foi alterado via tinker/seed
   (seria mexer em dado real sem autorização) — o usuário precisa
   digitar o CNPJ real da F.A. Marcenaria na primeira edição depois
   desta mudança.

### Reaproveitamento da lógica de CNPJ — generalização mínima, não recriação

`Perseu\Pessoas\Support\BrasilApiCnpjLookup::fill()` ganhou um 4º
parâmetro opcional `string $razaoSocialField = 'razao_social'`
(default preserva 100% o comportamento pra quem já chama sem
informá-lo — Pessoa Jurídica) — só troca `$set('razao_social', ...)`
por `$set($razaoSocialField, ...)`, usado como `fill($set, $get,
$state, razaoSocialField: 'name')` pro campo `name` (legado) de
Company. Único ponto tocado na classe original; todo o resto
(CNAE, situação cadastral, regime tributário, porte etc.) já usava
NOMES DE CAMPO IDÊNTICOS aos escolhidos pra Company, então funcionou
sem nenhuma outra mudança.

Endereço INLINE (Company tem UM endereço no próprio formulário, ao
contrário de Pessoa Jurídica, que usa a relação `enderecos` — ver
seção "Estrutura do plugin de Pessoas") não existia como necessidade
antes desta tarefa, então não tinha equivalente em
`BrasilApiCnpjLookup`. Nova classe
`Webkul\Support\Support\CompanyCnpjLookup::fillEndereco()` reaproveita
`BrasilApiCnpjLookup::buscar()`/`enderecoFrom()` (ambos públicos,
sem estado) só pra fazer o mapeamento Company-específico
(`logradouro`→`street1`, `complemento`→`street2`, `municipio`→`city`,
`cep`→`zip`, `bairro`/`numero` diretos, `uf`→`state_id` via
`State::where('code', $uf)->whereHas('country', code BR)` — os 27
estados brasileiros já vêm seedados com `code` de 2 letras batendo
exatamente com o formato da BrasilAPI, confirmado via tinker antes de
implementar). Ficou em `webkul/support` (não em `perseu/pessoas`) pra
não misturar conhecimento do schema legado do AureusERP
(`street1`/`state_id` como FK) dentro do plugin de Pessoas.

`Perseu\Pessoas\Enums\RegimeTributario`/`IndicadorContribuinteIcms` e
o cast correspondente em `Company::$casts` (necessário pra
`TextColumn`/`TextEntry` renderizarem o rótulo do enum em vez do
inteiro cru, mesmo mecanismo já usado em `PessoaJuridica`) foram
REUTILIZADOS diretamente de `perseu/pessoas`, não duplicados —
decisão consciente: `webkul/support` tem `->isCore()` (excluído do
grafo de `plugin_dependencies` usado pela UI de gestão de plugins, ver
`Webkul\PluginManager\Models\Plugin::getAllPluginPackages()`), então
importar uma classe de `perseu/pessoas` não introduz o risco de
dependência circular que motivou a decisão equivalente na Lixeira
Central (`comercial`/`pessoas` → `auditoria`) — `support` nunca é
instalado/desinstalado dinamicamente, então a direção da dependência
não entra nesse grafo de forma alguma. `SituacaoCadastralBadge`
(renderização do badge de Situação Cadastral) foi DUPLICADA como
classe própria em `webkul/support` em vez de reaproveitada da
Resource de Pessoa Jurídica — só 15 linhas, sem estado, e não fazia
sentido puxar uma classe de RENDERIZAÇÃO específica de um Resource de
outro plugin pra dentro de dois lugares (`CompanyResource`/
`BranchesRelationManager`) que não compartilham base comum.

### Migration — só 11 colunas novas, todo o resto reaproveitado

`2026_08_30_100000_add_brazilian_fields_to_companies_table` (registrada
em `SupportServiceProvider::hasMigrations()`, lista explícita como as
demais deste plugin) adiciona: `nome_fantasia`, `cnae`,
`cnae_descricao`, `regime_tributario`, `porte`, `descricao_porte`,
`situacao_cadastral`, `descricao_situacao_cadastral`,
`indicador_contribuinte_icms`, `bairro`, `numero` — todas
`nullable()`, sem quebrar seeders/factories existentes (confirmado
rodando `CompanyFactory::new()->make()` depois da migration).
REAPROVEITADAS sem migration: `name` (Razão Social), `tax_id` (CNPJ),
`founded_date` (Data de Abertura), `street1`/`street2`/`city`/`zip`
(Logradouro/Complemento/Município/CEP), `state_id`/`country_id`
(UF/País, já FK pras tabelas `states`/`countries`, ambas já seedadas
com os 27 estados brasileiros).

### Validado de ponta a ponta (Empresa E Filial), com o CNPJ real de teste já usado em tarefas anteriores

Via `Livewire::test()` (não só leitura de código): buscar um CNPJ
preenche `name`/`nome_fantasia`/`street1`/`bairro`/`numero`/`city`/
`zip`/`state_id`(FK resolvida)/`country_id`(Brasil, id 31)/`cnae`/
`situacao_cadastral`/`regime_tributario` — tanto no formulário de
Empresa quanto no modal de criação de Filial dentro do
`BranchesRelationManager` (usando um CNPJ diferente do de Empresa,
confirmando que cada registro busca e preenche independentemente).
Edição manual de um campo já preenchido pela API foi aceita
normalmente (`->set()` sobrescreve sem resistência, mesma UX de
Pessoa Jurídica). Criação completa (`->call('create')`/
`->callMountedAction()`, dentro de transação revertida) confirmou
Empresa E Filial salvas corretamente, incluindo a sincronização pro
Partner vinculado (`$company->partner->tax_id`/`street1` batendo com
os valores da Empresa). Logo/Cor (branding) não foram tocados —
confirmado que a seção continua renderizando normalmente.

## Bug de "registro fantasma" em Pessoa Física — só metade existia, confirmado por reprodução (2026-08-30)

Investigação (não suposição) da mesma vulnerabilidade já corrigida em
Pessoa Jurídica (ver "Excluir Pessoa Jurídica em cascata"), aplicada a
`PessoaFisica`:

1. **Cascata de `forceDeleting()` (Endereços/Contatos)**: **JÁ
   corrigida** — `PessoaFisica` já usa
   `Perseu\Pessoas\Traits\CascadesRelatedDataOnForceDelete` desde a
   tarefa "Auditoria (log de atividade) + Lixeira completa"
   (2026-08-28), que fechou essa lacuna nos dois Models ao mesmo
   tempo (a nota antiga dizendo "escopo só PessoaJuridica... não foi
   corrigido aqui" ficou desatualizada por aquela tarefa posterior).
   Confirmado de novo por reprodução: criar Pessoa Física + Endereço +
   Contato, `forceDelete()` → os dois somem do banco; soft-delete +
   `restore()` → os dois continuam intactos (cascata só dispara em
   exclusão definitiva de fato, não no soft delete).
2. **Validação de CPF vs. registro soft-deleted**: **bug real,
   confirmado por reprodução antes de corrigir** — o campo `cpf` em
   `PessoaFisicaResource` tinha só `->unique(ignoreRecord: true)`
   (sem `whereNull('deleted_at')`) e nenhuma Rule equivalente a
   `CnpjNaoExcluido`. Reproduzido via `Livewire::test()`: criar uma
   Pessoa Física, soft-deletá-la, e tentar criar outra com o MESMO CPF
   falhava silenciosamente (nenhum registro novo, mensagem genérica de
   "já se encontra registrado") — exatamente o mesmo sintoma que
   motivou a correção original em Pessoa Jurídica.

### Correção — mesmo padrão, nova classe `CpfNaoExcluido`

- `plugins/perseu/pessoas/src/Rules/CpfNaoExcluido.php` (novo) —
  cópia fiel de `CnpjNaoExcluido` trocando CNPJ por CPF (`PessoaFisica::onlyTrashed()->where('cpf', $value)`).
  Não extraída pra uma Rule genérica compartilhada (`RegistroNaoExcluido`
  parametrizável por Model/coluna): as duas regras já são pequenas e
  auto-contidas, e generalizar agora seria antecipar reuso sem um
  terceiro caso real (mesmo critério já usado no projeto pra não
  promover abstrações cedo demais — ver seção sobre
  `HasCompactFieldWidth`).
- `PessoaFisicaResource::form()`: `->unique(ignoreRecord: true,
  modifyRuleUsing: fn (Unique $rule) => $rule->whereNull('deleted_at'))`
  + `->rule(fn (?PessoaFisica $record) => new CpfNaoExcluido($record?->id))`
  no campo `cpf` — mesma dupla proteção de CNPJ (unique só considera
  ativos + regra dedicada bloqueia com mensagem clara quando o CPF
  pertence a um soft-deleted).
- Chave de tradução `pessoas::validation.rules.cpf-excluido` (pt_BR/en),
  mesmo texto de `cnpj-excluido` adaptado.

### Validado por reprodução (não só leitura de código)

Recriado o cenário exato: Pessoa Física + Endereço + Contato de teste,
soft-deletada — tentar criar uma nova com o mesmo CPF agora falha com
a mensagem "Já existe um cadastro excluído com este CPF..." (confirmado
via `$test->getErrorBag()->all()`, já que — como já registrado nesta
sessão pra outros formulários — `Livewire::test()->html()` nem sempre
reflete mensagens de erro de validação no snapshot, mesmo com o erro
real presente); excluindo definitivamente esse mesmo registro depois,
a criação com o mesmo CPF passa a ser permitida normalmente (dentro de
transação revertida). Todos os registros de teste foram limpos ao
final (`PessoaFisica::withTrashed()->count()` de volta a 3, o estado
original).

## Cluster "Referências" no plugin perseu/comercial, com o cadastro de Preços (2026-08-30)

Segundo Cluster do módulo Comercial, mesmo padrão técnico do Cluster
`Obras` (mesma estrutura de classe, `getClusterBreadcrumb()`,
`$cluster` nos Resources filhos, exclusão em `filament-shield.php` —
ver seção "Cluster 'Obras'..." acima, não reinvestigado de novo, só
replicado). Reúne cadastros de apoio pra compor Propostas/Contratos no
futuro: **Preços** implementado nesta tarefa; **Propostas (modelo/
template), Contratos, Termos de Entrega, Termos de Garantia — apenas
citados/planejados, SEM Resource/Model/migration criados** (a ideia de
longo prazo é gerar PDF desses documentos com dados do sistema,
reaproveitando `barryvdh/laravel-dompdf` já presente no
`composer.json` — ver "Roadmap — Geração de PDF de Proposta" acima,
mesma pendência, agora com um lar definido no menu).

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
chama `descricao` ("Descrição da Referência", texto livre que
identifica a tabela), não algo como "vigência"/"período".

Campos monetários (`laminacao`/`corte`/`hora_producao`/`hora_execucao`,
`decimal(10,2)`) e percentual (`retencao_tecnica`, `decimal(5,2)`) —
unidade de medida (metro linear vs. m²) exibida no formulário via
`->suffix(...)` traduzido (`/m linear`/`/m²`) ao lado de cada campo
monetário, já que os nomes dos campos sozinhos não deixam isso óbvio
pro usuário digitando. Coluna da tabela usa `->money('BRL')` (label da
coluna já inclui a unidade entre parênteses, ex. "Laminação (m
linear)", já que o `->suffix()` do form não se aplica a `TextColumn`).
Nenhum cálculo implementado — só CRUD, conforme escopo pedido; o uso
desses valores em Propostas é trabalho futuro.

### Convenção de Model novo seguida à risca — `SoftDeletes` desde a criação, sem esperar um bug pra adicionar depois

Diferente de `Obra`/`PessoaFisica`/`PessoaJuridica` (que ganharam
`SoftDeletes`/Lixeira numa tarefa posterior à criação), `ReferenciaPreco`
já nasce com `SoftDeletes` + `LogsBusinessActivity` +
`TrashedFilter`/`RestoreAction`/`ForceDeleteAction` no próprio
Resource + entrada em `Perseu\Auditoria\Support\TrashCatalog`/
`SubjectTypeCatalog` (4 pontos: `labelSlugs()`, `modulos()`,
`referenceFor()` — reaproveita o mesmo `descricao` de Tipo/Situação de
Obra/Categoria/Setor — e `applyBusca()`) — exatamente a "Convenção
para todo Model de cadastro de negócio criado a partir de agora" já
documentada, seguida desde o primeiro commit deste Model, não como
correção posterior. Sem aba de Atividades própria (`ActivitylogRelationManager`)
— auditoria só pela Central, decisão já vigente desde que essa aba foi
removida dos demais Models.

### Validado de ponta a ponta (não só leitura de código)

`route:list` confirmou as 4 rotas esperadas
(`admin/comercial/referencias`, `.../precos`, `.../precos/create`,
`.../precos/{record}/edit}`); `shield:generate --resource=ReferenciaPrecoResource`
processou exatamente 1 entidade (confirma que a exclusão do Cluster em
`filament-shield.php` funcionou, mesmo padrão de Obras) e gerou as 10
permissões esperadas (`*_comercial_referencia::preco`), sincronizadas
manualmente com o Admin (`shield:generate` não sincroniza sozinho,
mesma ressalva já documentada). Criado um registro de teste real via
`Livewire::test()` com todos os 6 campos preenchidos — confirmado
aparecendo na Central de Auditoria (rótulo "Preço", filtro por
Cadastro E por Módulo=Comercial funcionando) e, depois de excluído
(soft delete), na Lixeira Central — restaurado por lá com sucesso, e
removido definitivamente ao final da validação (nenhum dado de teste
deixado no banco). Navegação completa conferida antes/depois: grupo
"Comercial" foi de 1 pra 2 itens (Obras + Referências), nenhum outro
grupo afetado.

## Referência de Preços: campos de Imposto/Despesas + criação/edição em modal (2026-08-30)

Depois de estudar a planilha real de Proposta da F.A. Marcenaria
(cálculo de preço + cláusulas contratuais num único documento),
completada a cadeia de cálculo do cadastro de Referência de Preços
(criado na tarefa anterior) com 3 campos que faltavam: **Imposto**,
**Despesas Variáveis**, **Despesas Fixas**. Margem de Lucro
deliberadamente NÃO entrou — é resultado calculado (lucro bruto), não
parâmetro de entrada do cadastro.

### Decisão: os 3 campos novos são percentuais, não valor fixo

A tarefa pediu pra confirmar valor fixo vs. percentual caso não
estivesse óbvio pelo contexto. Decisão: **percentual** (`decimal(5,2)`,
mesmo formato de `retencao_tecnica`, que já era percentual) para os 3
— Imposto incide sobre o preço de venda (alíquota, sempre percentual
por natureza) e Despesas Variáveis/Fixas, na forma como aparecem na
planilha real (uma linha de "DRE"/rateio de custos operacionais sobre
o preço), são tipicamente expressas como um percentual de rateio sobre
o faturamento, não um valor fixo em R$ — esse é o padrão mais comum
pra esse tipo de campo numa fórmula de precificação, e é consistente
com o campo percentual que já existia no mesmo cadastro. Todos os 3
são `->required()` (mesmo padrão dos demais campos numéricos do
formulário) e exibidos com `->suffix('%')`.

Migration em ALTER separada
(`2026_08_30_140000_add_imposto_despesas_to_referencias_precos_table`),
não editando a migration de criação já aplicada (mesma convenção de
sempre — ver rename Projeto→Obra). `ReferenciaPreco::$fillable`/
`$casts` atualizados com os 3 campos (`decimal:2`, mesmo padrão dos
demais). Tabela ganhou uma coluna por campo (mesmo `formatStateUsing`
de porcentagem já usado por `retencao_tecnica` — extraído pra um
método `ReferenciaPrecoResource::formatPercent()` reaproveitado pelas
4 colunas percentuais, pra não repetir a mesma closure 4 vezes).

### Criar/editar em modal — mesmo mecanismo que já faz Filial funcionar assim

Investigado como `BranchesRelationManager` (Filial, dentro de
Configurações → Empresas) já abre criação/edição em modal: não tem
nada de especial no próprio `CreateAction`/`EditAction` (RelationManagers
não têm páginas de Create/Edit dedicadas — só a página de listagem
existe pra qualquer RelationManager). O mecanismo real está em
`Filament\Resources\Pages\Page::getDefaultActionUrl()` (`vendor/filament/filament/src/Resources/Pages/Page.php`):

```php
if (($action instanceof EditAction) && (static::getResource()::hasPage('edit')) && ...) {
    return $this->getResourceUrl('edit', ['record' => $action->getRecord()]);
}
return null; // sem URL → Action cai no comportamento padrão: abrir modal
```

Ou seja: `CreateAction`/`EditAction` (`Filament\Actions\*`, não algo
específico de RelationManager) **sempre abrem modal por padrão** — só
passam a navegar pra uma página cheia quando o Resource declara uma
page `create`/`edit` em `getPages()` E essa combinação de
`hasPage(...)` retorna `true`. `BranchesRelationManager` nunca teve
essa page pra começo de conversa (RelationManager não registra pages
próprias), por isso sempre foi modal. Confirmado lendo o próprio
`EditAction::setUp()`/`CreateAction::setUp()` (`vendor/filament/actions/src/{Edit,Create}Action.php`):
o `$this->action(...)` de cada uma já faz `$record->update($data)`/
`$record->save()` diretamente — a Action em si nunca soube navegar pra
lugar nenhum, isso sempre foi um comportamento adicionado por cima
(`getDefaultActionUrl()`), não removido.

**Correção aplicada em `ReferenciaPrecoResource`**: `getPages()`
reduzido pra só `'index'` (removidas as entradas `'create'`/`'edit'`);
apagadas as classes `CreateReferenciaPreco`/`EditReferenciaPreco`
(`Pages/`) e seus arquivos de tradução (`pages/{create,edit}-referencia-preco.php`,
pt_BR/en) — sem mais nenhuma referência a elas em lugar nenhum do
código (conferido por grep antes de apagar). O `CreateAction::make()`
já existente em `ListReferenciasPrecos::getHeaderActions()` e o
`EditAction::make()` já existente em `ReferenciaPrecoResource::table()->recordActions([...])`
**não precisaram de nenhuma mudança de código** — só de `hasPage(...)`
passar a `false` pra virarem modal automaticamente (confirmado com
`ReferenciaPrecoResource::hasPage('create')`/`hasPage('edit')`
retornando `false` depois da mudança). `DeleteAction` que existia como
header action de `EditReferenciaPreco` (a página que deixou de existir)
não precisou de reposição — já existia um `DeleteAction::make()`
equivalente em `recordActions()` da própria tabela, cobrindo a mesma
necessidade.

### Validado (Livewire::test() com limitação conhecida, mais confirmação direta em banco)

`route:list` confirmou que só a rota `index`
(`admin/comercial/referencias/precos`) sobrou — `create`/`edit` como
rotas HTTP separadas desapareceram de fato (a página cheia não existe
mais, só o modal).

**Achado de teste, não de produto**: `Livewire::test()->callAction('create', [...])`
criou o registro corretamente no banco com os 3 campos novos com os
valores exatos enviados, mas `$test->instance()->getErrorBag()->all()`
reportou mensagens de campo obrigatório mesmo com o registro certo já
salvo — inconsistência da própria camada de teste (`assertActionVisible()`
interno do `callAction()` depende de `Illuminate\Testing\Assert`, que
por sua vez depende de `PHPUnit\Framework\Assert::$instance` estar
configurado por um test runner real do PHPUnit; rodando via
`artisan tinker` — fora de um `TestCase` de verdade — essa dependência
não está montada, e o comportamento da asserção fica inconsistente).
O dado no banco (não a mensagem de erro da camada de teste) foi usado
como fonte de verdade: registro criado com `imposto=12.50`,
`despesas_variaveis=8.25`, `despesas_fixas=15.75`, batendo exatamente
com o que foi enviado.

Pela mesma razão, a edição via `fillForm()`/`setTableActionData()`
encadeado depois de `mountTableAction()` não persistiu o valor num
teste inicial (state path da action não resolvido corretamente entre
chamadas separadas nesse contexto de tinker) — contornado escrevendo
diretamente no path de estado real da action
(`mountedActions.0.data.despesas_fixas`, descoberto via
`getMountedActionSchemaName()`/`Schema::getStatePath()`), que persistiu
corretamente (`despesas_fixas` no banco foi de 1.00 pra 20.00, zero
erros). Confirma que o mecanismo de edição em modal funciona; a
fragilidade está apenas na forma de simular o preenchimento de uma
Action (não de uma página/formulário comum) dentro do `Livewire::test()`
rodado via tinker, não no código do Resource.

Central de Auditoria e Lixeira Central confirmadas SEM NENHUM IMPACTO
(esperado — essas integrações dependem só de `LogsBusinessActivity`/
`SubjectTypeCatalog`/`TrashCatalog` no nível do Model, nunca da
página/rota usada pra criar o registro): criado um registro real
diretamente via Eloquent (equivalente ao que o modal faz por baixo dos
panos), confirmado log de `created`, rótulo "Preço" e referência
corretos em `SubjectTypeCatalog`, soft-delete gerando log de `deleted`
e aparecendo em `TrashCatalog::onlyTrashedQuery()`, `restore()` limpando
`deleted_at`, e `forceDelete()` gerando o log de `forceDeleted` — ciclo
completo, sem nenhuma regressão. Registro e logs de teste removidos ao
final.

## Referência de Preços: mais 4 campos (Valor por Peças + 3 Fatores) e decisão de não poluir a listagem (2026-08-30)

Mais uma rodada de campos da composição de custo real da empresa,
completando o cadastro de Referência de Preços: **Valor por Peças**
(monetário, `decimal(10,2)`) e três fatores percentuais (**Fator
Madeiras**, **Fator Ferragens e Miscelânias**, **Fator Mão de Obra**,
todos `decimal(5,2)`) — mesmo padrão dos campos já existentes (moeda
com `->prefix('R$')`, percentual com `->suffix('%')`). Migration nova
em ALTER (`2026_08_30_150000_add_valor_pecas_fatores_to_referencias_precos_table`),
não tocando nas duas migrations anteriores já aplicadas (mesma
convenção de sempre).

### Decisão: os 4 campos novos ficam ocultos por padrão NA LISTAGEM (não no modal)

Antes desta tarefa a tabela já tinha 9 colunas de dados (Descrição +
4 monetários + 4 percentuais) + Criado em. Adicionar mais 4 visíveis
de cara deixaria a listagem larga demais pra leitura rápida — decisão
consciente de marcar os 4 novos (`valor_pecas`, `fator_madeiras`,
`fator_ferragens_miscelanias`, `fator_mao_obra`) com
`->toggleable(isToggledHiddenByDefault: true)`: continuam 100%
editáveis no modal de criar/editar (todos os campos, sem exceção,
aparecem lá) e continuam disponíveis na listagem pra quem quiser via
botão de alternar colunas — só não aparecem de cara pra não sobrecarregar
a leitura da tabela no dia a dia. Nenhum campo antigo teve sua
visibilidade alterada.

### Validado

Confirmado (mesma ressalva já registrada sobre `callAction()` ser
inconsistente em `Livewire::test()` rodado fora de um `TestCase` real
do PHPUnit — às vezes reporta erros de campo obrigatório mesmo quando
o registro é salvo corretamente, às vezes bloqueia de fato; usar
diretamente o state path da action, `mountedActions.0.data.*`, é o
jeito confiável de reproduzir o preenchimento real do modal neste
ambiente de teste) que a criação via modal com os 12 campos (8
antigos + 4 novos) salva tudo corretamente, sem erros. Log de
`created` do Spatie Activitylog confirmado com os 4 novos campos
presentes em `properties.attributes`; edição de um campo novo
(`fator_mao_obra`) gerou log de `updated` com `old`/`attributes`
corretos (`logOnlyDirty`, só o campo alterado). Rótulo/referência da
Central de Auditoria (`SubjectTypeCatalog`) inalterados, como esperado
— não dependem de quais colunas o Model tem.
