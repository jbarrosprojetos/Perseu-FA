# Plugin `perseu/pessoas`

> Convenções e decisões específicas deste plugin. Para o que vale para
> o projeto inteiro (convenção de Model de cadastro de negócio, regras
> de nomenclatura do sistema, comandos DDEV, etc.), veja o `CLAUDE.md`
> da raiz. Para o histórico narrado (o "porquê" de uma decisão antiga),
> veja a seção "Ver também" no final deste arquivo.

Cadastro de Pessoas Físicas e Jurídicas do Perseu — substitui o modelo
de tabela única "partners" do AureusERP original por tabelas separadas
por tipo de pessoa.

## Convenções e decisões do módulo

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

## Estado atual (Models)

`CategoriaPessoa`, `Contato`, `Endereco`, `PessoaFisica`,
`PessoaJuridica`, `Setor` (`plugins/perseu/pessoas/src/Models/`).

## Estrutura de navegação

- Organizado com `getNavigationGroup() => NavigationGroup::Pessoas`
  (SEM Cluster — ver seção "Navegação: Cluster vs. grupo achatado" no
  `CLAUDE.md` da raiz), com 3 itens: Categorias, Pessoas Físicas,
  Pessoas Jurídicas.
- Contatos NÃO é um Resource/item de menu próprio — é um Relation
  Manager dentro da tela de edição de Pessoa Jurídica (mesmo padrão do
  `BranchesRelationManager` em Company, `webkul/support`).
- Endereços também não é item de menu — é uma tabela própria
  (`enderecos`) com pivots (`pessoa_fisica_endereco`,
  `pessoa_juridica_endereco`), exibida como Relation Manager dentro de
  Pessoa Física e Pessoa Jurídica. `principal` é coluna do PIVOT
  (característica da relação Pessoa↔Endereço); o tipo de endereço NÃO
  é mais coluna de pivot — ver "Tipo de Endereço como tag" abaixo.
- No `CheckboxList` de tags do Relation Manager de Endereços, as
  opções são filtradas por contexto: Pessoa Física (Residencial,
  Cobrança, Entrega, Obra, Outro) vs. Pessoa Jurídica (Comercial,
  Cobrança, Entrega, Obra, Outro) — o enum continua com todas as
  opções, a tela só restringe visualmente. `TipoEndereco::Obra`
  (endereço de canteiro de obras/execução) é um conceito diferente do
  cadastro "Projeto" de `perseu/comercial` — não confundir os dois ao
  mexer em qualquer um.

### Tipo de Endereço como tag (múltiplas finalidades por endereço, 2026-09-02)

Um mesmo `Endereco` pode servir a mais de uma finalidade ao mesmo
tempo (ex: Comercial + Obra) — "Tipo de Endereço" deixou de ser um
valor único e virou uma TAG (múltipla escolha). Antes disso, `tipo`
era uma coluna `unsignedTinyInteger` de valor único nos PIVOTS
`pessoa_fisica_endereco`/`pessoa_juridica_endereco`.

- **Modelagem**: N:N entre `Endereco` e o enum `TipoEndereco`, tabela
  `endereco_tipo` (`endereco_id`, `tipo`, unique nos dois juntos) —
  **não** entre a relação Pessoa↔Endereço e o tipo, mesmo `tipo`
  historicamente tendo vivido no pivot Pessoa↔Endereço. Confirmado por
  query antes de decidir: nenhum `Endereco` é hoje compartilhado entre
  duas Pessoas (cada linha de `enderecos` pertence a exatamente um
  pivot PF ou PJ), então a tag pertence naturalmente ao Endereço em
  si.
- `Perseu\Pessoas\Models\EnderecoTipo` — Model simples (uma linha =
  uma tag), `tipo` castado direto pra `TipoEndereco` (`$casts`).
  `Endereco::tipos(): HasMany`. `principal` continua na tabela pivot
  Pessoa↔Endereço, sem mudança — não é uma tag, é característica da
  relação (`withPivot('principal')`, `'tipo'` removido de todo
  `withPivot()` do plugin).
- **Formulário** (`HasEnderecoRelationManagerSchema::form()`):
  `CheckboxList::make('tipos')` no lugar do antigo `Select::make('tipo')`
  de escolha única. Ao criar um endereço NOVO, todas as opções vêm
  marcadas por padrão (`->default(array_keys(...))` — só se aplica
  quando não há estado existente, ou seja, só no Create); o usuário
  desmarca as que não se aplicam. Endereços já cadastrados mostram só
  as tags reais que têm.
- **Create/EditAction customizados**: como `tipos` não é mais coluna
  de pivot (`withPivot()`), o split automático de pivot vs. atributo
  do Filament (`$relationship->getPivotColumns()`, ver
  `Filament\Actions\{Create,Edit}Action`) não sabe lidar com ele — a
  chave `tipos` simplesmente é descartada silenciosamente pelo
  `fill()`/`update()` do Endereco (não está no `$fillable`, e o
  projeto não tem `Model::preventSilentlyDiscardingAttributes()`
  ativado). Por isso o trait sincroniza manualmente via
  `->after(fn (array $data, Endereco $record) => static::syncTipos($record, $data['tipos'] ?? []))`
  (Create e Edit) e `->mutateRecordDataUsing(...)` (Edit, pra
  pré-popular o CheckboxList com as tags reais salvas ao abrir o modal
  — sem isso, o form usaria o `->default()` de "tudo marcado" também
  na edição, o que é errado). `array $data`/`Model $record` são
  injeções NOMEADAS oficiais do Filament em
  `Action::resolveDefaultClosureDependencyForEvaluationByName()`
  (`'data' => $this->getData()`, `'record' => $this->getRecord()`) —
  não é uma gambiarra, é a forma documentada de customizar esse ponto.
  `syncTipos()` é `delete()` + `createMany()` (substitui o conjunto
  inteiro, mais simples que fazer diff attach/detach).
- **Tabela**: `TextColumn::make('tipos')->badge()` com
  `->getStateUsing()` lendo `$record->tipos->pluck('tipo')->map(fn ($t) => $t->getLabel())`
  — `->badge()` em cima de estado array renderiza um badge por item
  (mesmo mecanismo já usado por `situacoes.descricao` em
  `ProjetoResource`). `->modifyQueryUsing(fn ($q) => $q->with('tipos'))`
  no `table()` evita N+1.
- **Fluxos automáticos (sem CheckboxList) continuam com tag ÚNICA e
  deliberada**, NÃO "tudo marcado por padrão" — essa regra vale só
  para quando um humano preenche o formulário manualmente:
  `CreatePessoaJuridica::afterCreate()` (endereço vindo da busca de
  CNPJ, tag "Comercial") e `ProjetoResource::createOptionUsing()` do
  Select de Endereço (tag "Obra", canteiro de obra/execução).
- **Migração de dados**: `2026_09_02_120000_create_endereco_tipo_table`
  — cada linha existente em `pessoa_fisica_endereco`/
  `pessoa_juridica_endereco` virou UMA linha em `endereco_tipo` com o
  mesmo `tipo` que já tinha (sem marcar tags extras), depois a coluna
  `tipo` foi dropada dos dois pivots. Validado 1:1 contra um snapshot
  tirado antes de migrar — nenhum dado perdido ou alterado.
- **Testando isso via `Livewire::test()` em `tinker`**: montar a
  Action `create`/`edit` de um Relation Manager isoladamente
  (`Livewire::test(EnderecosRelationManager::class, [...])
  ->mountAction('create')`) NÃO funciona neste ambiente — `mountedActions`
  fica vazio, sem erro (Relation Managers em Filament v4 renderizam
  como "islands" assíncronas que não populam em teste isolado via
  tinker; mesma limitação documentada no `CLAUDE.md` da raiz sobre
  `callAction()`/`callTableAction()` via tinker). Validar a lógica de
  sincronização diretamente (chamar o equivalente de `syncTipos()` e
  checar o banco), não via mount de Action.
- Os dois Relation Managers de Endereços reaproveitam o mesmo
  `form()`/`table()` via o trait
  `Perseu\Pessoas\Traits\HasEnderecoRelationManagerSchema` — a classe
  concreta só declara `$relationship`, `translationPrefix()` e
  `tipoEnderecoOptions()`.
- Itens novos de menu podem ser promovidos depois, caso surja
  necessidade real (ex: uma listagem geral de contatos).

## Utilitários compartilhados de busca externa (CEP, CNPJ)

Definidos aqui, mas reaproveitados por outros plugins (`perseu/comercial`,
`webkul/support`) — mantidos neste plugin por serem, em espírito,
utilitários de "dados de Pessoa/Endereço", não porque sejam de uso
exclusivo daqui.

- **CEP**: `Perseu\Pessoas\Support\ViaCepLookup::fill(Set $set, ?string
  $cep)` — consulta `https://viacep.com.br/ws/{cep}/json/` (pública,
  sem autenticação), preenche `logradouro`/`bairro`/`municipio`/`uf`.
  Classe utilitária pura, sem estado, sem depender de Model/Resource —
  chamável de qualquer formulário que tenha esses campos, dentro ou
  fora do plugin Pessoas. Usada por `HasEnderecoRelationManagerSchema`
  e pelo `createOptionForm` de Endereço em
  `Perseu\Comercial\...\ProjetoResource`.
- **CNPJ**: `Perseu\Pessoas\Support\BrasilApiCnpjLookup::fill(Set $set,
  Get $get, ?string $cnpj, string $razaoSocialField = 'razao_social')`
  — consulta `https://brasilapi.com.br/api/cnpj/v1/{cnpj}` (timeout
  8s, try/catch, `Cache::remember` 10 min), preenche razão
  social/nome fantasia/telefone/e-mail/CNAE/data de
  abertura/porte/regime tributário/situação cadastral. Usada por
  `PessoaJuridicaResource` e (via `Webkul\Support\Support\
  CompanyCnpjLookup`, que reaproveita `buscar()`/`enderecoFrom()`) por
  `Company`/`Branch` em `webkul/support` — ver `CLAUDE.md` daquele
  plugin. **Regras a seguir ao estender**: nunca sobrescreve
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

### Tema Bonsai — gotcha do `!important` em `gap` (contexto completo)

O tema `qalainau/bonsai-theme` (removido do projeto em 2026-08-24 —
ver "Tema Bonsai" no `CLAUDE.md` da raiz e o histórico completo em
`HISTORICO-DESENVOLVIMENTO.md`) forçava `gap: 0 !important` em várias
classes `.fi-*` de grid/flex/form. Isso já causou espaço "sumindo" sem
explicação aparente DUAS vezes justamente neste plugin
(`HasCompactFieldWidth::flexRow()` e
`HasRelationManagerDividers::getFormContentComponent()`, ambos
corrigidos adicionando `!important` ao valor do `gap` no `style`) antes
da causa real (o tema) ser encontrada e removida. O tema não está mais
ativo, mas **qualquer `!important` de `gap` que sobrar em código deste
plugin é resíduo dessa investigação — não remover sem entender que ele
só era necessário por causa do tema, agora ausente** (pode já não ser
mais necessário; testar visualmente antes de tirar).

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

## Limitações conhecidas

- Categoria de Pessoa e Setor usam o padrão `ManageRecords` do
  Filament (uma página só, modal) — sem `SoftDeletes`, sem Lixeira,
  sem aba de Atividades própria (mas continuam auditados pela
  Central). Não expandir isso preventivamente — só se/quando virar
  necessidade real, como decisão própria.

## Pendências

- **Remover Lixeira/TrashedFilter individual de PF/PJ**: ver
  pendência global equivalente (também cobre `Projeto`, de
  `perseu/comercial`) no `CLAUDE.md` da raiz — só depois de
  confirmação explícita do usuário de que a Lixeira Central substitui
  bem esse acesso.

## Ver também (histórico narrado, `HISTORICO-DESENVOLVIMENTO.md`)

- "Tema Bonsai (qalainau/bonsai-theme) — investigação completa"
- "Integração BrasilAPI em Pessoa Jurídica: implementação completa"
- "Excluir Pessoa Jurídica em cascata (Endereços/Contatos) + CNPJ de
  registro excluído"
- "Bug de 'registro fantasma' em Pessoa Física — só metade existia,
  confirmado por reprodução"
