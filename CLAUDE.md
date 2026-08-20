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

## Filtro de Tipo de Endereço por contexto (aplicar na Fase 3)
No Select de "tipo" do Relation Manager de Endereços, filtrar as
opções do enum TipoEndereco conforme o contexto:
- Pessoa Física: Residencial, Cobrança, Entrega, Outro
- Pessoa Jurídica: Comercial, Cobrança, Entrega, Obra, Outro
O enum continua com todas as opções; a tela apenas restringe visualmente
o que faz sentido em cada caso.

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
