# Plugin `perseu/comercial`

> Convenções e decisões específicas deste plugin. Para o que vale para
> o projeto inteiro (convenção de Model de cadastro de negócio, regras
> de nomenclatura do sistema, comandos DDEV, etc.), veja o `CLAUDE.md`
> da raiz. Para o histórico narrado (o "porquê" de uma decisão antiga),
> veja a seção "Ver também" no final deste arquivo.

Gestão comercial de Projetos do Perseu — o cadastro de negócio central
da F.A. Marcenaria (marcenaria industrial).

## Estado atual (Models e navegação)

- **Models**: `Projeto`, `ItemProjeto`, `TipoProjeto`, `SituacaoProjeto`,
  `ReferenciaPreco` (`plugins/perseu/comercial/src/Models/`).
  `ItemProjeto` é o único SEM Resource/navegação própria — vive só
  dentro do form de `ProjetoResource` (Section "Itens", ver "Tabela
  `itens_projeto`..." mais abaixo).
- **Clusters de navegação**: `Projetos` (agrupa `ProjetoResource`,
  `TipoProjetoResource`, `SituacaoProjetoResource`, slug
  `comercial/projetos` etc.) e `Referencias` (agrupa
  `ReferenciaPrecoResource`, slug `comercial/referencias`) — ambos com
  `getNavigationGroup() => NavigationGroup::Comercial`, item único no
  dropdown "Comercial" da topbar, cada um com sua própria sidebar. Ver
  "Navegação: Cluster vs. grupo achatado" no `CLAUDE.md` da raiz para o
  mecanismo geral.

## Nomenclatura: "Projeto" (não "Obra", não "Processo")

O cadastro central chama-se **Projeto** (`Perseu\Comercial\Models\Projeto`,
tabela `projetos`, numeração automática `AAT####` via
`GeradorNumeroProjeto`). Já foi renomeado duas vezes:
"Projeto" → "Obra" (28/08/2026) → "Projeto" de novo (02/09/2026), esta
segunda vez para liberar espaço depois que
`Webkul\Project\Models\Processo` (plugin `webkul/projects`, "Gestão de
Processos") deixou de se chamar "Project"/"Projeto". Ver tabela de
nomenclatura vigente do sistema inteiro no `CLAUDE.md` da raiz — **não
confundir com `Processo`, de outro plugin, mesmo que os nomes já
tenham colidido no passado.**

`projetos.revisao` existe (`unsignedInteger`, `default(0)`, sem lógica
de autoincremento, exibido como Placeholder somente-leitura
zero-padded em 2 dígitos) — fora do `$fillable`, sem input editável em
lugar nenhum. A ideia conceitual atual é que "Projeto + Revisão" já
representa o que seria uma "Proposta", sem Model/Resource separado por
enquanto — ver `CONCEITO-OBRA-PROPOSTA-PROJETO.md` (raiz do projeto)
para o desenho de negócio completo (fases Proposta/Projeto, situações,
fluxo até Pedido de Compra) e a seção "Ver também" abaixo para o
detalhamento técnico dos dois renames.

## Cluster "Referências" e Referência de Preços

Reúne cadastros de apoio usados para compor Propostas/Contratos no
futuro: Preços (`ReferenciaPreco`, implementado), Propostas (modelo/
template), Contratos, Termos de Entrega, Termos de Garantia — estes
últimos quatro apenas citados/planejados, sem Resource criado ainda
(ver "Pendências" abaixo).

Convenção de nomenclatura de campos percentuais/monetários usada em
`ReferenciaPreco` (e que deve seguir sendo usada em qualquer campo
novo do gênero, aqui ou em outro plugin): monetário `decimal(10,2)`
com `->prefix('R$')`, percentual `decimal(5,2)` com `->suffix('%')`.
Se uma tabela crescer muito em colunas, considere
`->toggleable(isToggledHiddenByDefault: true)` nas colunas menos
usadas do dia a dia (mantém tudo editável no form, só não some com a
listagem).

`referencias_precos.fator_mao_obra` ("Fator Mão de Obra"/"Labor
Factor") — atenção ao nome: "mão de obra" aqui é o termo comum de
"trabalho humano", SEM relação com o cadastro `Projeto` (nem com o
antigo nome "Obra" desse cadastro). Não renomear por engano ao mexer
em qualquer tarefa que envolva a palavra "obra".

### Data/Hora de criação como identidade visual (2026-09-02)

`descricao` sozinha NÃO é única em `ReferenciaPreco` — de propósito,
sem `->unique()` no form nem constraint de banco (nunca existiu). Duas
referências podem ter a mesma Descrição desde que criadas em momentos
diferentes (ex.: revisões de uma mesma tabela de preços ao longo do
tempo) — a combinação Descrição + `created_at` é o identificador
"conceitual" pro usuário, não uma regra de validação bloqueante
(`created_at` tem precisão de segundo; duas criações simultâneas
poderiam colidir em teoria, então não virou constraint rígida). Por
isso `created_at` aparece de forma visível:
- No form: `Placeholder::make('created_at')` logo abaixo da Descrição
  (mesmo padrão de `numero_projeto`/`data_cadastro` em
  `ProjetoResource`) — mostra "Preenchido automaticamente ao salvar"
  quando o registro ainda não existe.
- Na tabela: coluna logo após Descrição, formatada `d/m/Y H:i`, visível
  por padrão (removido o `toggleable(isToggledHiddenByDefault: true)`
  que existia antes — a data/hora precisa aparecer de cara pra
  diferenciar duas linhas com a mesma Descrição).

### Vínculo Projeto → Referência de Preços + trava de exclusão/edição (2026-09-02)

`projetos.referencia_preco_id` (FK nullable, `nullOnDelete()`) —
campo opcional no cabeçalho do Projeto (Section "Dados do Projeto",
mesma linha do Endereço da Obra, ao lado direito: `Grid::make(12)`
com Endereço `columnSpan(8)` + Referência de Preços `columnSpan(4)`),
usado futuramente pra calcular o valor de Venda do Projeto.
`Projeto::referenciaPreco(): BelongsTo` / `ReferenciaPreco::projetos(): HasMany`
(relação inversa, só existe pra alimentar a trava abaixo).

- **Opções do Select com Descrição + Data/Hora** —
  `->getOptionLabelFromRecordUsing(fn ($r) => "{$r->descricao} — {$r->created_at->format('d/m/Y H:i')}")`,
  não só `titleAttribute: 'descricao'` — necessário porque
  `ReferenciaPreco.descricao` não é única (ver seção anterior); sem
  isso duas referências com a mesma Descrição apareceriam idênticas no
  dropdown, sem como diferenciar visualmente qual é qual.
- **Aviso em vermelho quando vazio** — `->hint(...)->hintColor('danger')`,
  não `->helperText()` (que não tem parâmetro de cor built-in, só
  `Text::make($content)` sem `->color()` — confirmado lendo
  `Filament\Forms\Components\Concerns\HasHelperText`). `->hint()`
  (`Filament\Forms\Components\Concerns\HasHint`) é o mecanismo oficial
  pra texto colorido ao lado do label — mesma cor da paleta usada em
  qualquer outro lugar do Filament (`danger` = vermelho), reativo via
  `Get` (`->live()` no campo).
- **Trava de exclusão/edição** — `ReferenciaPreco` com pelo menos um
  `Projeto` vinculado não pode ser excluída nem editada. Implementada
  via `->before()` nas Actions (`EditAction`/`DeleteAction`/
  `ForceDeleteAction`, + `DeleteBulkAction`/`ForceDeleteBulkAction`) de
  `ReferenciaPrecoResource`, não via Policy — decisão deliberada: se a
  Policy negasse `update`/`delete` quando vinculada, o botão simplesmente
  DESAPARECERIA da tabela sem nenhuma explicação ao usuário; a tarefa
  pediu explicitamente uma mensagem clara "ao tentar". `->before()`
  mantém o botão visível/clicável (a Policy continua controlando só a
  permissão normal) e intercepta a tentativa com
  `Notification::make()->danger()` + `$action->halt()` antes do
  form/exclusão de fato acontecer. Os fechamentos `fn (ReferenciaPreco
  $record, EditAction $action) => ...` resolvem `$record`/`$action` via
  injeção nomeada/tipada oficial do Filament
  (`Action::resolveDefaultClosureDependencyForEvaluationByName()`, mesmo
  mecanismo confirmado e documentado no CLAUDE.md de `perseu/pessoas`
  pro rename de Tipo de Endereço) — `$action` resolve porque
  `is_a($this, $typedParameterClassName)` é verdadeiro pro tipo
  declarado no parâmetro.
- **`SubjectTypeCatalog`/`TrashCatalog` (Auditoria)**: conferido, SEM
  mudança necessária — os dois já registram `Projeto` e `ReferenciaPreco`
  como Models independentes; `referencia_preco_id` é só uma FK interna,
  não precisa de referência/busca própria. Como o campo entrou no
  `$fillable` de `Projeto`, `LogsBusinessActivity` já audita mudanças
  nele automaticamente, sem código extra.

### Section "Itens" e reposicionamento de Salvar/Cancelar (2026-09-03)

`ProjetoResource::form()` ganhou uma segunda Section, "Itens", irmã de
"Cabeçalho" — por ora só a interface (`Select` de origem, não
persistido, `dehydrated(false)`, com 4 opções fixas: Item Avulso, Item
de Linha, Promob, SketchUp + botão "Inserir"). A lógica real de cada
origem e a listagem dos itens já inseridos ficam para uma etapa futura
(depende de uma tabela de Itens que ainda não existe).

**Lista de origens simplificada de 7 para 4 em 2026-09-05** — as 5
opções mais específicas (Item de Linha, Promob Plus, Promob Start,
Sketchup Hellomob, Sketchup CutList, CortCloud) foram substituídas por
"Item de Linha" (mantida) + duas guarda-chuva, "Promob" e "SketchUp" —
nenhuma das 5 chegou a ser usada em registro real, então não houve
dado pra migrar. **Correção no mesmo dia**: a primeira versão desta
simplificação (7 → 3) removeu "Item de Linha" por engano — deveria ter
sido mantida junto de "Item Avulso", já que é a origem mais parecida
com um cadastro central de Produto (ver
`CONCEITO-OBRA-PROPOSTA-PROJETO.md`, "Itens do Projeto: dois tipos") e
não deveria ter sido descartada como as outras. Ordem final do Select:
Item Avulso, Item de Linha, Promob, SketchUp. "Item de Linha" e
"SketchUp" continuam só placeholder; "Promob" ganhou o modal de
upload/Checar Total (ver subseção própria abaixo) — serão
detalhadas/implementadas aos poucos, à medida que for necessário.

Como a Section "Itens" vai ganhar sua própria dinâmica de salvar/
editar/excluir por item (ações individuais e imediatas, fora do ciclo
de "Salvar alterações" do formulário principal), os botões Salvar/
Cancelar da página foram **reposicionados** para logo após a Section
"Cabeçalho" e ANTES de "Itens" — não fazia mais sentido eles ficarem
no fim da página, depois de uma Section que não usa esse mesmo ciclo
de salvamento.

- Por padrão o Filament renderiza Salvar/Cancelar FORA do array de
  `components()` do `form()` do Resource — `CreateRecord`/`EditRecord`
  os anexam via `->footer([$this->getFormActionsContentComponent()])`
  dentro de `getFormContentComponent()`, sempre depois de TUDO que o
  Resource declarar.
- Solução: `ProjetoResource::form()` chama
  `$schema->getLivewire()->getFormActionsContentComponent()`
  diretamente como um item do array `components()`, entre as duas
  Sections. `getFormActionsContentComponent()` é público em
  `CreateRecord`/`EditRecord` e monta o mesmo `Actions::make([...])`
  com `getSubmitFormAction()`/`getCancelFormAction()` que a página já
  usaria — chamado uma única vez, sem duplicar lógica de submit.
  `$schema->getLivewire()` retorna a página (Create/Edit) porque o
  Schema já vem vinculado a ela nesse ponto (`Schema::make($this)`,
  `Filament\Schemas\Concerns\BelongsToLivewire`).
- Contrapartida obrigatória: `CreateProjeto`/`EditProjeto` sobrescrevem
  `getFormContentComponent()` (copiado do vendor, só removendo a
  chamada a `->footer([...])`) — senão o mesmo
  `Actions::make(...)->key('form-actions')` seria chamado DUAS vezes
  (uma pelo Resource, outra pelo rodapé padrão da página), duplicando
  os botões na tela com a mesma key colidindo. Confirmado por teste
  (`Livewire::test()`): sem esse override, `fi-sc-actions`/`type="submit"`
  apareciam duplicados; com o override, aparecem uma única vez, na
  posição certa.

### Botão "Atribuir Processos" — só em EditProjeto (2026-09-04)

`EditProjeto::getFormActions()` sobrescreve o método padrão do
`EditRecord` (`protected function getFormActions(): array`, retorna
`[$this->getSaveFormAction(), $this->getCancelFormAction()]`) pra
acrescentar um terceiro botão, `Action::make('atribuirProcessos')`,
na MESMA linha de Salvar/Cancelar reposicionada (ver "Section 'Itens'
e reposicionamento de Salvar/Cancelar" acima) — sem ação real ainda,
só notificação placeholder (mesmo padrão das origens do dropdown de
Itens ainda não implementadas).

- **Só existe em `EditProjeto`, nunca em `CreateProjeto`** — a forma
  mais idiomática de condicionar isso não foi um `->visible()` checando
  a página/registro, e sim simplesmente NÃO sobrescrever
  `getFormActions()` em `CreateProjeto` (que continua herdando o
  padrão do `CreateRecord`, só Salvar/Cancelar). Mesmo padrão já usado
  por `getHeaderActions()` no mesmo arquivo (`DeleteAction` também só
  existe em Edit, nunca em Create) — motivo: só depois de salvo pelo
  menos uma vez o Projeto tem Número/Data de Cadastro preenchidos, faz
  sentido "atribuir Processos" a um Projeto que ainda não existe no
  banco.
- **Por que funciona sem tocar em `ProjetoResource::form()`**: quem
  monta a linha de botões é
  `$schema->getLivewire()->getFormActionsContentComponent()` (ver
  subseção anterior) — `getLivewire()` retorna a INSTÂNCIA REAL da
  página (`CreateProjeto` ou `EditProjeto`, dependendo de qual rota
  está ativa), e `getFormActionsContentComponent()` (herdado, não
  sobrescrito) chama `$this->getFormActions()` nessa mesma instância —
  por polimorfismo normal do PHP, a versão de `EditProjeto` é chamada
  quando `$this` é um `EditProjeto`, a de `CreateRecord` quando é um
  `CreateProjeto`. Nenhuma mudança no Resource foi necessária.

### Cabeçalho estilo planilha para "Item Avulso" (2026-09-03, colunas corrigidas 2026-09-03, Imp.% removido 2026-09-03, ícones de ajuda no cabeçalho 2026-09-04, "Custo Unitário" abreviado 2026-09-04)

Ao clicar "Inserir" com "Item Avulso" selecionado, em vez da
notificação placeholder aparece um cabeçalho de colunas — `Grid::make(24)`
com **9 colunas visíveis** (`columnSpan`: Item 1, Referência 4,
Descrição 7, Qtde. 1, Valor Unit. 3, Valor Total 3, Porc.% 1, Custo
Unitário 3, última coluna sem rótulo 1 — soma 24), no espaço reservado
logo abaixo do Select+Botão, dentro da mesma Section "Itens". A
inspiração é a aba "00" do Excel `260000 Cliente Padrão Proposta
00.xlsm` usado hoje pela F.A. Marcenaria. Todos os rótulos aparecem
abreviados ("Qtde.", "Valor Unit.", "Porc.%") — **"Porc.%" é a mesma
coluna que já passou por "Desconto" → "Porcentagem" → "Porc.%"**, não
confundir com uma coluna nova; a última coluna (1) continua reservada,
sem texto — sem uso definido ainda (a coluna que ANTES se chamava
"Total Custo" foi renomeada para "Custo Unitário" em 2026-09-03 —
representa o custo unitário do item, não mais um "total"). As outras 3
origens do dropdown (Item de Linha, Promob, SketchUp) continuam com a
notificação placeholder normal (Promob ganhou depois um modal próprio,
ver subseção "Fluxo Promob" abaixo); só "Item Avulso" tem este
cabeçalho/linha de input próprios até agora.

**Coluna "Imp.%" removida da tela em 2026-09-03** (ver subseção "Imp.%
removido..." mais abaixo) — a distribuição ATUAL de 9 colunas acima já
reflete essa remoção; o histórico da distribuição anterior de 10
colunas (com Imp.% visível) fica só no git blame/HISTORICO-DESENVOLVIMENTO.md,
não repetido aqui.

**Coluna "Porc.%" renomeada para "%" em 2026-09-04** — reduz a
proximidade visual do rótulo com "Custo Unitário" ao lado; é a mesma
coluna interna `porcentagem`/`novo_item_porcentagem`, só mudou o texto
exibido (pt_BR e en).

**Rótulo "Custo Unitário" abreviado para "Custo Unit." em 2026-09-04**
(só pt_BR — mesmo padrão já usado em "Valor Unit.") — o texto completo
quebrava em duas linhas por ser mais longo que a largura de
`columnSpan(3)` (mesma largura de Valor Unit./Valor Total). O `en`
("Unit Cost") não precisou de abreviação — já é curto o bastante,
tamanho equivalente a "Unit Price". O tooltip do ícone de ajuda dessa
coluna (`custo-unitario-tooltip`) continua com o texto completo "Valor
de Custo Digitado ou Importado" — só o rótulo visível foi abreviado.

**Ícones de ajuda (2026-09-04, correção de posicionamento):** 4 das
colunas do cabeçalho (Referência, Descrição, %, Custo Unitário) têm um
ícone "?" com tooltip anexado ao PRÓPRIO `Text` do cabeçalho — via
`Flex::make([Text::make(...), Icon::make('heroicon-o-question-mark-circle')
->tooltip(...)])->dense()->verticallyAlignCenter()->columnSpan(N)` —
não mais a `->hintIcon()` no campo de input da linha de baixo (ver
subseção seguinte, "Toolbar do RichEditor..."). Motivo: cada campo da
linha de INPUT tem `->hiddenLabel()`/é um `Text::make('')` vazio (o
rótulo de verdade só existe na linha de CABEÇALHO, acima); um
`->hintIcon()` se ancora ao label NATIVO do campo em que é chamado, e
com esse label oculto/vazio o ícone ficava flutuando sozinho sobre o
input, sem nenhuma relação visual com o texto do rótulo (achado real
da primeira tentativa, corrigido nesta data).

- **`Icon`, não `Text::make()->icon()`** — `Filament\Schemas\Components\Text
  ::toEmbeddedHtml()` só desenha o ícone informado via `->icon()` no
  modo `->badge()` (pill com fundo/borda, indesejado no cabeçalho
  estilo planilha); no modo normal (usado por todo o cabeçalho) o
  ícone é simplesmente ignorado no render — confirmado lendo o
  código-fonte do componente (`vendor/filament/schemas/src/Components/
  Text.php`), não presumido. `Filament\Schemas\Components\Icon` é o
  componente certo — implementa `HasTooltip` e desenha o `x-tooltip`
  Alpine igual ao `Text`/`hintIcon`.
- **`Flex`, não `Grid` aninhado** — `Flex` é um `Component` de verdade
  (herda `CanSpanColumns` de `Filament\Schemas\Components\Component`),
  então aceita `->columnSpan()` do `Grid(24)` pai igual a qualquer
  outro componente da linha.
- **`->dense()` no `Flex`, não um `class` Tailwind arbitrário via
  `->extraAttributes()`** — o painel admin usa o CSS PRÉ-COMPILADO do
  Filament (sem build Tailwind próprio escaneando PHP deste plugin,
  ver "`FilamentAsset::register()`..." no CLAUDE.md da raiz), então um
  utilitário Tailwind que o Filament não usa em lugar nenhum do seu
  próprio código-fonte (ex.: `'gap-1'` cru) simplesmente NÃO tem efeito
  — a classe não existe no CSS publicado. `->dense()` usa `.fi-dense`
  (gap-3, já compilado — `HasGap::isDense()`, herdado por qualquer
  Component via `Filament\Schemas\Concerns\HasGap`), gap menor que o
  `gap-6` default do `Flex` sem quebrar a dependência do CSS já
  publicado.

- **`Filament\Schemas\Components\Text`, não `Placeholder`** — é só
  rótulo/label de coluna (um `<span>`, sem wrapper de campo de
  formulário com label+conteúdo empilhados), mais leve e mais adequado
  a um cabeçalho estilo planilha. `Grid` (não Flex) porque todo campo
  tem largura fixa em número de colunas, mesmo critério já registrado
  em "Grid vs. static::flexRow()" (`plugins/perseu/pessoas/CLAUDE.md`).
- **Estado via `Hidden::make('origem_item_inserida')`** (`dehydrated(false)`,
  fora do `$fillable`) — guarda qual origem teve seu botão "Inserir"
  clicado por último; a Action de "Inserir" faz `$set()` nesse campo
  (`'item_avulso'` só quando essa origem é a selecionada, `null` nos
  demais casos) e o `Grid::make(24)` do cabeçalho usa
  `->visible(fn (Get $get) => $get('origem_item_inserida') === 'item_avulso')`.
  Precisa ser um campo à parte do `Select::make('origem_item_selecionada')`
  (que reflete a opção escolhida no dropdown, mudando a cada seleção) —
  o cabeçalho só deve reagir ao CLIQUE em "Inserir", não à troca de
  opção no Select antes de clicar.
- **Achado de teste**: `Livewire\Testing\Testable::html()` devolve o
  HTML do ÚLTIMO ciclo de vida real do componente (`$this->lastState`)
  — chamar a action via `$test->instance()->mountAction(...)` direto
  (bypass do pipeline) executa a lógica (inclusive `Notification::make()
  ->send()`, cujo efeito fica visível lendo `session('filament.notifications')`
  depois) mas NÃO atualiza esse HTML cacheado; para inspecionar o HTML
  pós-clique é preciso passar pelo pipeline de verdade
  (`$test->call('mountAction', 'inserirItem', [], ['schemaComponent' => 'form'])`).
  Também por passar pelo pipeline completo, essa segunda forma já
  consome/limpa a notificação da sessão como aconteceria numa
  requisição real — para inspecionar o CONTEÚDO da notificação em teste,
  usar a chamada direta (`$test->instance()->mountAction(...)`); para
  inspecionar o HTML renderizado, usar `$test->call(...)`. Nenhuma das
  duas cobre as duas coisas ao mesmo tempo.

### Linha de INPUT de "Item Avulso" + botão "Mobilização e Frete" (2026-09-03)

Logo abaixo do cabeçalho de colunas (subseção anterior), um SEGUNDO
`Grid::make(24)` com os MESMOS `columnSpan` (1,4,7,1,3,3,1,3,1 — ver
"Imp.% removido" mais abaixo pra a distribuição ATUAL) traz os campos
de verdade — ainda nenhuma persistência (todos `dehydrated(false)`,
prefixo `novo_item_*`, fora do `$fillable` de `Projeto`); a tabela
`itens_projeto` (ou nome equivalente) e a ação real de confirmar/
salvar ficam pra uma próxima etapa.

- **Item** (1): `Placeholder` só-leitura, formato `###`. Ainda não há
  tabela de Itens pra contar registros reais — fixo em `"001"` por
  enquanto (todo Projeto "começa" sem nenhum item confirmado, já que
  confirmar/persistir é tarefa futura). Quando a tabela existir, trocar
  por uma contagem real de itens do Projeto.
- **Referência** (4): sem campo, só `Text::make('')` reservando a
  coluna (Item Avulso não usa essa coluna).
- **Descrição** (7): `RichEditor::make('novo_item_descricao')` sem
  `->toolbarButtons()` — o conjunto DEFAULT do Filament
  (`RichEditor::getDefaultToolbarButtons()`) já cobre bold/italic/
  underline/strike/sub/superscript/link, h2/h3, alinhamento,
  blockquote/codeBlock/listas, tabela, anexos, undo/redo — não precisa
  declarar nada pra "todas as ferramentas padrão" pedidas.
  **Investigação (2026-09-03): toolbar "tipo bubble menu" (só em foco,
  esconde ao perder foco) — NÃO implementada, descartada por conflito
  de UX, não por dificuldade técnica pura.** O mecanismo existe e é de
  primeira classe (`RichEditor::floatingToolbars()`, documentado em
  `vendor/filament/forms/docs/10-rich-editor.md`, "Customizing floating
  toolbars") — usa `@tiptap/extension-bubble-menu` por baixo
  (`vendor/filament/forms/resources/js/components/rich-editor.js`,
  `BubbleMenuPlugin`). Mas o `shouldShow` que decide quando o bubble
  aparece é HARDCODED no JS do pacote (não configurável via PHP): pra
  qualquer chave que NÃO seja `'paragraph'` (ex.: `'heading'`,
  `'table'`) já basta o CURSOR estar dentro do nó
  (`editor.isFocused && editor.isActive(key)`, sem precisar de seleção)
  — mas pra `'paragraph'` (o nó onde vive praticamente todo texto
  digitado normalmente) a condição EXTRA `!editor.state.selection.empty`
  é exigida, ou seja, o bubble só aparece com TEXTO SELECIONADO, não
  com o cursor simplesmente posicionado/em foco. Migrar TODAS as
  ferramentas padrão pra dentro de `floatingToolbars(['paragraph' =>
  [...]])` (esvaziando a toolbar fixa) trocaria "sempre visível" por
  "só aparece com seleção" — quebra ferramentas que não fazem sentido
  como bubble de seleção (undo/redo, anexar arquivo, inserir tabela:
  ações de documento, não de trecho selecionado) e impede o fluxo
  comum de "clicar em Negrito ANTES de digitar" (sem seleção prévia,
  não haveria toolbar visível pra clicar). Um focus/blur "puro" (sem
  depender de seleção) exigiria (a) sobrescrever a Blade view do
  componente — inviável hoje porque o `RichEditor` não tem NENHUM
  arquivo `.blade.php` no pacote pra publicar/copiar, é 100% renderizado
  via `toEmbeddedHtml()` em PHP (~500 linhas) — ou (b) fazer fork do
  asset JS compilado (`rich-editor.js`) pra expor um estado `isFocused`
  reativo e religar `x-show` manualmente no wrapper da toolbar,
  mantido à mão em todo upgrade do Filament (alto risco de quebrar
  silenciosamente numa atualização futura). Estimativa: 1-2 dias +
  manutenção contínua — não vale o custo/risco agora.
  **Decisão seguinte (2026-09-03): toolbar removida de vez**
  (`->toolbarButtons([])`), sem tentar escondê-la condicionalmente —
  ver subseção seguinte.

### Toolbar do RichEditor de "Item Avulso" removida — atalhos de teclado (2026-09-03, texto fixo trocado por ícone com balão em 2026-09-03, ícone movido pro cabeçalho em 2026-09-04)

Em vez do "aparece só em foco" (descartado acima), a decisão foi tirar
a barra de ferramentas de vez: `RichEditor::make('novo_item_descricao')
->toolbarButtons([])` — `getToolbarButtons()` retorna array vazio,
`RichEditor::toEmbeddedHtml()` pula o `<div class="fi-fo-rich-editor-
toolbar">` inteiro (`if ((! $isDisabled) && filled($toolbarButtons))`),
sem afetar nada mais: as extensões TipTap continuam TODAS carregadas
(`toolbarButtons()` só controla quais BOTÕES aparecem, não quais
extensões/marcas o editor sabe processar), então os atalhos de teclado
de cada uma continuam funcionando normalmente.

**Orientação ao usuário: ícone com balão, não mais texto fixo.** A
primeira versão usava `->helperText(...)` (texto sempre visível abaixo
do campo) — trocado por `->hintIcon('heroicon-o-question-mark-circle',
tooltip: __(...))`, um ícone ao lado do rótulo que só mostra o balão ao
passar o mouse/focar. Motivo: o texto fixo ocupava espaço permanente
na tela mesmo quando o usuário não precisa da informação; o ícone
resolve isso sem perder a orientação. Funciona mesmo com
`->hiddenLabel()` no campo — o slot "after label" que carrega o ícone
(`Filament\Forms\Components\Concerns\HasHint::setUpHint()`) é
independente do texto do rótulo: `field-wrapper.blade.php` só esconde
o TEXTO do label (`fi-sr-only`, texto ainda existe pra leitor de tela)
quando `hiddenLabel()` está ativo, mas o container do label continua
renderizando se houver qualquer conteúdo em `afterLabel` (nosso caso) —
confirmado lendo o Blade do componente, não presumido.

**Achado real (corrigido em 2026-09-04): o `->hintIcon()` acima
ficava POSICIONADO ERRADO.** O campo (`RichEditor` desta linha de
INPUT) tem `->hiddenLabel()` — o rótulo "Descrição" de verdade vive
SÓ na linha de CABEÇALHO acima (`Text::make(...)`, Grid::make(24)
anterior), não neste campo. `->hintIcon()` se ancora ao label NATIVO
do componente em que é chamado — com esse label vazio/oculto, o ícone
ficava flutuando sozinho sobre a linha de input, sem nenhuma relação
visual com o texto "Descrição" acima. Correção: o `->hintIcon()` foi
REMOVIDO daqui — o ícone (mesmo tooltip) agora vive no `Text` do
cabeçalho, junto com outros 3 (Referência, %, Custo Unitário), ver
detalhes técnicos (`Icon`/`Flex`/`->dense()`) na subseção "Cabeçalho
estilo planilha para 'Item Avulso'" acima.

**Atalhos confirmados** (lidos direto do bundle compilado
`vendor/filament/forms/dist/components/rich-editor.js`, procurando
`addKeyboardShortcuts()` de cada extensão TipTap realmente carregada
em `vendor/filament/forms/resources/js/components/rich-editor/extensions.js`
— não presumidos da documentação genérica do TipTap):

| Atalho | Ação |
|---|---|
| `Ctrl+B` | Negrito (bold) |
| `Ctrl+I` | Itálico (italic) |
| `Ctrl+U` | Sublinhado (underline) |
| `Ctrl+Shift+S` | Tachado (strike) |
| `Ctrl+E` | Código inline (code) |
| `Ctrl+Shift+H` | Marca-texto (highlight) |
| `Ctrl+Shift+B` | Citação (blockquote) |
| `Ctrl+Shift+7` / `Ctrl+Shift+8` | Lista numerada / com marcadores |
| `Ctrl+Alt+C` | Bloco de código |
| `Ctrl+Alt+0` | Volta pra parágrafo normal |
| `Ctrl+Shift+L/E/R/J` | Alinhar esquerda/centro/direita/justificado |
| `Ctrl+Z` / `Ctrl+Shift+Z` ou `Ctrl+Y` | Desfazer / refazer |

O tooltip do ícone só cita os 3 primeiros (Bold/Italic/Underline) por
brevidade (`hintIconTooltip` só aceita string simples, sem HTML/quebra
de linha) — os demais ficam registrados aqui caso o tooltip precise
crescer no futuro. **Confirmado que NÃO existe**
uma extensão de lista de tarefas carregada (`Mod-Shift-9`/`toggleTaskList`
aparece no bundle, mas pertence a um "listKit" que não é importado em
`extensions.js` — só `BulletList`/`ListItem`/`OrderedList` individuais
são de fato usados), então esse atalho específico NÃO funcionaria
mesmo citando-o — por isso não faz parte da tabela acima.

**Limitação de teste confirmada**: `Livewire::test()` não serve pra
verificar visualmente que o texto continua formatado (negrito/itálico)
sem a toolbar — o conteúdo do `RichEditor` é renderizado inteiramente
no CLIENTE via TipTap/Alpine (`wire:ignore` na div raiz, conteúdo
passado como JSON via `$wire.entangle()`, DOM populado por JS depois
do carregamento da página), então o HTML devolvido por
`Testable::html()` nunca contém o texto formatado renderizado — só o
wrapper/toolbar/estado inicial. O que DÁ pra confirmar via teste:
que `$set('novo_item_descricao', '<p><strong>...</strong></p>')`
converte corretamente pro formato interno JSON do TipTap (`RichEditor
StateCast`, campo guarda `{"type":"doc","content":[...,{"marks":
[{"type":"bold"}]}]}`, não a string HTML crua) preservando a marca
`bold` — ou seja, a INTEGRIDADE do dado sobrevive à remoção da
toolbar (esperado, já que `toolbarButtons()` é puramente de
renderização, não mexe no processamento de marcas) — mas a
confirmação visual de que aparece formatado na tela exige navegador de
verdade.
- **Qtde.** (1), **Porc.%** (1) e **Custo Unit.** (3, este último desde
  2026-09-05): `TextInput` `->numeric()->integer()` (Custo Unit. sem
  `->integer()`, aceita decimal), `->live(onBlur: true)`, disparam o
  recálculo (ver fórmula abaixo). Porc.% SEM `->minValue()` — aceita
  negativo de propósito (acréscimo/desconto). **Sem setas de
  incremento/decremento** (2026-09-03, estendido ao Custo Unit. em
  2026-09-05 — mesmo asset reaproveitado, não duplicado):
  `->extraInputAttributes(['class' => 'fi-input-no-spinner'])` +
  `resources/css/filament/admin-input-no-spinner.css` (registrado em
  `AdminPanelProvider::boot()` via `FilamentAsset::register()`, mesmo
  mecanismo já usado por `admin-topbar.css`/`admin-select-badge.css`).
  `->step(null)` sozinho NÃO remove essas setas — são desenhadas pelo
  próprio motor do navegador via pseudo-elemento
  (`::-webkit-inner-spin-button` no Chrome/Edge, `-moz-appearance` no
  Firefox), e pseudo-elemento não é endereçável por
  `->extraInputAttributes(['style' => ...])` (atributo `style` inline
  só aceita propriedades do próprio elemento) — por isso precisa de uma
  folha de estilo de verdade. Escopado à classe
  `.fi-input-no-spinner` (só nesses 3 campos), não em todo
  `input[type=number]` do painel, pra não afetar outros campos
  numéricos do sistema (ex.: os de `ReferenciaPreco`).
  **Bug real na primeira tentativa (corrigido em 2026-09-03)**: a
  classe estava correta no HTML (confirmado via `Livewire::test()`,
  `<input ... class="fi-input-no-spinner fi-input" />`), mas a regra
  CSS não tinha NENHUM efeito no navegador — causa raiz:
  `FilamentAsset::register([Css::make(...)])` não serve o arquivo
  direto de `resource_path(...)`, só gera um `<link>` pra uma cópia
  publicada em `public/css/app/admin-input-no-spinner.css`
  (`Filament\Support\Assets\Css::getHref()`), e essa publicação
  (`ddev artisan filament:assets`) nunca tinha rodado depois do
  registro — o `<link>` apontava pra um arquivo inexistente, sem
  nenhum erro visível. Ver "Comandos e fluxo úteis" no CLAUDE.md da
  raiz pro mecanismo geral (vale pra qualquer asset registrado assim,
  não só este). Corrigido rodando `filament:assets` e commitando o
  arquivo publicado (mesmo padrão de `admin-topbar.css`/
  `admin-select-badge.css`, ambos versionados em `public/css/app/`).
- **Valor Unitário** (3) e **Valor Total** (3): `TextInput`
  `->disabled()` — nunca digitados, só `$set()` pelo recálculo.
  `disabled()` já implica não-dehydratado, mas mantido
  `->dehydrated(false)` explícito por consistência com os outros
  campos `novo_item_*`.
- **Imp.% removido da tela em 2026-09-03** — vira `Hidden::make('novo_item_imposto')`
  (sem `columnSpan` próprio; `Hidden::setUp()` já usa
  `columnSpan(['default' => 'hidden'])`, não consome espaço no Grid).
  O ESPAÇO que a coluna ocupava (columnSpan 1) foi redistribuído:
  Referência 3→4, Descrição 6→7, Valor Unitário 2→3, última coluna 3→1
  (ver nova distribuição completa na subseção do cabeçalho, acima).
  **O valor continua vindo normalmente** da Referência de Preços
  ATUALMENTE selecionada no Cabeçalho (`referencia_preco_id`, não
  necessariamente a salva no banco — se o usuário troca a Referência
  antes de clicar "Inserir", vale a escolha atual) e ENTRA na fórmula
  do mesmo jeito, só não aparece mais como coluna própria. **Achado de
  teste** (ainda válido, campo continua populado do mesmo jeito, só
  mudou de `TextInput` visível pra `Hidden`): NÃO dá pra popular via
  `->default(fn (?Projeto $record) => $record?->referenciaPreco?->imposto)`
  — o campo vive dentro de um Grid com `->visible()` condicional a
  `origem_item_inserida`, e o `fill()` inicial da página (Create/Edit)
  não hidrata campos que COMEÇAM escondidos; confirmado via
  `Livewire::test()` — o campo ficava sempre `null` mesmo com
  Referência vinculada, mesmo depois do Grid virar visível
  (visibilidade muda o RENDER, não re-executa `fill()`). Correção: a
  própria Action "Inserir" (quando `$origem === 'item_avulso'`) faz
  `$set('novo_item_imposto', ReferenciaPreco::find($get('referencia_preco_id'))?->imposto)`
  explicitamente — e aproveita pra resetar todos os `novo_item_*` a
  cada clique (linha nova sempre em branco). Sem Referência vinculada,
  fica em branco (`null`) — tratado como 0% no cálculo (ver fórmula
  abaixo); o aviso em vermelho já existe no campo "Referência de
  Preços" do Cabeçalho.
- **Custo Unitário** (3): `TextInput` `->numeric()->minValue(0)`
  (só positivo), `->live(onBlur: true)`, dispara o recálculo. Sem setas
  de incremento/decremento desde 2026-09-05 — mesmo `.fi-input-no-spinner`
  de Qtde./Porc.%, ver acima.
- **Última coluna** (1, antes 3 — encolhida pra sobrar espaço com a
  remoção do Imp.%): `Actions::make([Action::make('confirmarItemAvulso')
  ->iconButton()])` — SEM ação real (notificação placeholder própria,
  chaves `notification.confirmar-pendente-*`); a persistência de fato é
  a próxima tarefa. `->verticallyAlignStart()` (2026-09-03, antes
  `->verticallyAlignEnd()` por engano/herança do padrão usado no botão
  "Inserir") — `.fi-sc-actions` é um `flex flex-col h-full` (`vendor/
  filament/schemas/resources/css/components/actions.css`), então
  `verticalAlignment` vira `justify-content` no eixo vertical desse
  container de altura cheia: `start` = topo, `end` = fim. Alinhar ao
  TOPO deixa o ícone na mesma altura dos outros campos da linha (Item,
  Descrição, Qtde. etc., que começam todos no topo por padrão) — sem
  isso o ícone ficava visivelmente mais baixo que o resto da linha.
- **Botão "Mobilização e Frete"**: ao lado de "Inserir" (mesmo
  `Actions::make([...])`, `columnSpan` do Grid de 12 colunas aumentado
  de 2 pra 6 pra caber os dois botões), sem ação própria —
  reaproveita o MESMO par de traduções `notification.pendente-title`/
  `pendente-body` do placeholder das origens não implementadas
  (Promob/SketchUp), passando `'origem' => 'Mobilização e Frete'`
  como se fosse mais uma origem pendente
  (evita criar strings novas pra um texto idêntico em espírito).

**Fórmula de cálculo** (`recalcularValoresItemAvulso()`, chamada pelo
`->afterStateUpdated()` de Qtde./Porc.%/Custo Unitário — os três campos
usados nela):

```
Valor Unitário = Custo Unitário × (1 + Porc.%/100) × (1 + Imp.%/100)
Valor Total    = Valor Unitário × Quantidade
```

Se Quantidade OU Custo Unitário estiverem vazios/zerados, os DOIS
campos calculados ficam em branco (`null`) — mesmo que Valor Unitário
matematicamente só dependesse de Custo Unitário (sem precisar de
Quantidade), essa é a regra pedida: os dois calculados ficam juntos,
tudo ou nada. Sem Imp.% disponível (Projeto sem Referência de Preços),
entra como 0% na fórmula — decisão registrada aqui, não bloqueia o
cálculo. "Imp.%" na fórmula é só o nome interno do valor (variável
`$imposto`/campo `novo_item_imposto`) — desde 2026-09-03 não existe
mais como rótulo de coluna na tela (ver "Imp.% removido da tela"
acima), só o cálculo por baixo dos panos.

### Tabela `itens_projeto` + persistência real de Item Avulso (2026-09-04)

Até aqui a linha de Item Avulso só CALCULAVA em tempo real, sem gravar
nada (`novo_item_*`, todos `dehydrated(false)`). Esta tarefa criou a
tabela real e o fluxo completo de inserir/editar.

**Model/migration** — `Perseu\Comercial\Models\ItemProjeto`, tabela
`itens_projeto` (migration `2026_09_04_160000_create_itens_projeto_table`,
**precisou ser adicionada manualmente ao array `->hasMigrations([...])`
de `ComercialServiceProvider`** — o próprio arquivo já tem um comentário
alertando sobre isso desde uma tarefa anterior, `loadMigrationsFrom()`
NUNCA descobre migrations sozinho nesse pacote). `LogsBusinessActivity`,
mesma convenção de qualquer Model de cadastro de negócio (ver CLAUDE.md
da raiz) — `Projeto::itens(): HasMany` / `ItemProjeto::projeto(): BelongsTo`.
**SEM `SoftDeletes`** — divergência deliberada da convenção padrão, ver
"Exclusão de item + renumeração contígua" mais abaixo pro motivo
completo (incompatível com a renumeração exigida pela exclusão).

- **`origem`**: `Perseu\Comercial\Enums\OrigemItemProjeto` (enum PHP
  nativo `string`, `implements HasLabel`, mesmo padrão de
  `Perseu\Pessoas\Enums\TipoEndereco`) — os mesmos 3 valores já usados
  como chave em `ProjetoResource::origensItemOptions()` (não duplicar/
  renomear essas strings sem atualizar os dois lugares; o Select em si
  NÃO foi migrado pra usar o enum, continua com o array próprio — risco
  desnecessário de mexer em código já funcionando só por "elegância").
- **`produto_id`/`situacao_item_id`**: colunas `unsignedBigInteger`
  NULLABLE, **sem FK de verdade** — confirmado por grep que os
  cadastros de Produto e Situação de Item ainda não existem em NENHUM
  plugin. Adicionar `->constrained()` numa migration nova quando esses
  cadastros forem criados. Nenhuma UI usa esses dois campos ainda
  (Item Avulso não tem Produto vinculado, por definição — ver
  `CONCEITO-OBRA-PROPOSTA-PROJETO.md`, "Itens do Projeto: dois tipos").
- **`numero_item`**: string(3), único por `projeto_id`
  (`unique(['projeto_id', 'numero_item'])`), gerado em
  `ItemProjeto::boot()` (`creating`) — maior `numero_item` já usado
  NAQUELE Projeto (`MAX(...)`) + 1, começando em `001`. Diferente de
  `numero_projeto` (`Projeto`), números AQUI SÃO reaproveitados —
  excluir um item renumera os seguintes pra fechar o buraco (ver
  "Exclusão de item + renumeração contígua" abaixo), por isso o `MAX()`
  não pode (nem precisa) considerar excluídos — `ItemProjeto` não usa
  `SoftDeletes`. **Sem tabela de sequência própria** (diferente de
  `numero_projeto`/`GeradorNumeroProjeto`) — a tarefa pediu
  explicitamente "maior número já usado + 1", um `MAX()` simples já
  atende; concorrência coberta só parcialmente, ver
  `confirmarItemAvulso()` abaixo.
- **SEM Resource/Policy própria** — divergência deliberada do passo 4-6
  da "Convenção para Model novo de cadastro de negócio" (CLAUDE.md da
  raiz): `ItemProjeto` não tem navegação/CRUD Filament independente,
  vive 100% dentro do form de `ProjetoResource` (a mesma UI de sempre,
  Section "Itens"). Acesso é gated pela `ProjetoPolicy::update` já
  existente (só chega nessa UI quem já tem permissão de editar o
  Projeto) — sem Policy/permissões Shield próprias por enquanto. Se um
  dia `ItemProjeto` ganhar uma tela própria (ex.: listagem
  administrativa fora do contexto de um Projeto), reconsiderar.
- **`SubjectTypeCatalog` registrado, `TrashCatalog` NÃO** — `ItemProjeto`
  aparece na Central de Auditoria (rótulo "Item de Projeto", busca por
  `descricao`/`numero_item`, referência `"{numero_item} — {descrição
  truncada}"`) porque QUALQUER Model com `LogsBusinessActivity` já é
  auditado; sem entrada em `TrashCatalog::models()` porque a exclusão é
  DEFINITIVA (sem `SoftDeletes`, ver "Exclusão de item + renumeração
  contígua" abaixo) — não existe Lixeira possível pra ele. Ver
  `plugins/perseu/auditoria/CLAUDE.md`.

**Fluxo de INSERÇÃO** (`ProjetoResource::confirmarItemAvulso()`,
chamado pelo ícone ✓ da última coluna):

1. Sem `$record` (página de CRIAÇÃO do Projeto, ainda não salva) —
   bloqueia com notificação "salve o Projeto primeiro", mesmo critério
   já usado pelo botão "Atribuir Processos" (só existe depois de salvo).
2. Validação MANUAL — Descrição (texto puro não-vazio, `strip_tags()`),
   Quantidade > 0, Custo Unitário > 0 — via `ValidationException
   ::withMessages(['data.novo_item_quantidade' => ...])`, **não**
   `->required()` nos campos do Schema. Motivo: os campos `novo_item_*`
   são compartilhados por TODA a Section "Itens", inclusive quando
   nenhum item está sendo inserido; `->required()` no Schema faria o
   Salvar/Cancelar do CABEÇALHO (formulário diferente, ver "Section
   'Itens' e reposicionamento de Salvar/Cancelar" acima) exigir esses
   campos também sempre que a linha estivesse visível, mesmo sem clicar
   em confirmar — efeito colateral indesejado. `ValidationException
   ::withMessages()` é o MESMO mecanismo usado por
   `Filament\Auth\Pages\Login::throwFailureValidationException()`
   (vendor) — confirmado lendo o código-fonte, não presumido — e
   funciona idêntico dentro de uma Action sem `->schema()` própria:
   propaga pelo pipeline normal de chamada do Livewire e aparece como
   erro inline no campo certo, com `'data.'` de prefixo (`statePath('data')`
   de `CreateRecord`/`EditRecord`).
3. Sem erros: `$record->itens()->create([...])` dentro de
   `DB::transaction()` com `lockForUpdate()` nas linhas já existentes
   daquele `projeto_id` — trava concorrência de dois cliques rápidos NO
   MESMO Projeto enquanto o `MAX()+1` do `numero_item` é calculado.
   **Sem proteção no PRIMEIRO item de um Projeto** (nada pra travar
   ainda) — risco aceito, não é um fluxo multi-usuário simultâneo real
   (um usuário editando um Projeto de cada vez).
4. `resetarLinhaItemAvulso()` — fecha a linha de input
   (`origem_item_inserida`/`item_em_edicao_id` = `null`, todos os
   `novo_item_*` limpos). **NÃO reseta `origem_item_selecionada`** de
   propósito — o Select continua em "Item Avulso", já que inserir outro
   item da mesma origem em seguida é o caso comum.

### Imposto obsoleto ao gravar — corrigido (2026-09-05)

Achado real de concorrência (ver `INVESTIGACAO-TRANSACOES-CONCORRENCIA.md`,
risco R1): `novo_item_imposto` (lido uma vez ao abrir a linha — ver
"Imp.% removido da tela" acima) ficava em CACHE no estado do Livewire
por todo o tempo que o usuário levava preenchendo/revendo o item. Se
outra sessão mudasse o `imposto` da Referência de Preços nesse
meio-tempo, o valor gravado usava o Imp.% ANTIGO, sem ninguém perceber
— um bug de corretude de dado financeiro, silencioso.

**Correção**: `confirmarItemAvulso()` não usa mais `novo_item_imposto`
pra gravar — dentro da MESMA `DB::transaction()` da gravação, busca o
`imposto` FRESCO com `ReferenciaPreco::where('id', $referenciaPrecoId)
->lockForUpdate()->value('imposto')` e recalcula
`valor_unitario`/`valor_total` com esse valor. `lockForUpdate()` na
Referência de Preços fecha a janela de corrida por completo (não só
reduz) entre o clique em "Confirmar" e o commit. O resultado é gravado
em `itens_projeto.imposto_aplicado` (`decimal(5,2)`, nullable) — cópia/
snapshot do Imp.% efetivamente usado, NÃO um FK vivo — preserva o
histórico do cálculo mesmo que a Referência de Preços mude depois
(também útil pra explicar/auditar um valor calculado meses depois).
`novo_item_imposto` continua existindo e sendo usado — mas só pra
PRÉVIA em tela (`recalcularValoresItemAvulso()`, a cada tecla), que
pode ficar obsoleta sem problema (é só exibição); a gravação de
verdade sempre relê o banco. `Perseu\Comercial\...\ProjetoResource
::calcularValoresItemAvulso()` foi extraído como função PURA (sem
`Get`/`Set`) justamente pra essa separação: a prévia e a gravação usam
a MESMA fórmula, só com fontes diferentes de Imp.% (cache vs. fresco).
`itemAvulsoMudou()` também passou a comparar `imposto_aplicado`.

**Fluxo de EDIÇÃO**: ícone de edição (`heroicon-o-pencil-square`) em
cada linha da listagem chama `abrirEdicaoItemAvulso()` — seta
`item_em_edicao_id` + `origem_item_inserida = 'item_avulso'` (reaproveita
a MESMA linha de input/visibilidade da inserção, só muda o que
`confirmarItemAvulso()` faz internamente: `create` vs. `update`) e
preenche `novo_item_*` com os dados atuais do item, **recalculando
Valor Unitário/Total a partir do Imposto ATUAL da Referência de Preços
do Cabeçalho** (não o Imp.% usado quando o item foi originalmente
criado — a tarefa pediu explicitamente "recalculados normalmente" ao
entrar em edição; se a Referência não mudou, bate exatamente com o
valor já salvo). Ao confirmar, `itemAvulsoMudou()` compara os valores
atuais (normalizados — `round(...,2)` nos decimais, `(int)` na
quantidade, `trim()` na descrição) contra os já gravados; **sem
diferença nenhuma, NÃO chama `update()`** (nem grava log de auditoria
"updated" vazio) — só fecha a linha de volta pra modo exibição.

**Só um item em edição por vez** — decisão mais simples entre as
sugeridas na tarefa: `item_em_edicao_id` é um único campo (não uma
lista); abrir a edição de outro item simplesmente SOBRESCREVE esse
valor (e os `novo_item_*`), descartando silenciosamente qualquer edição
não confirmada do item anterior. Clicar "Inserir" de novo (pra um item
NOVO) também cancela qualquer edição em andamento
(`item_em_edicao_id = null` dentro da própria Action `inserirItem`).

**Listagem dos itens já inseridos** — `Group::make()->schema(fn (Get
$get, $livewire) => [...])`, uma `Grid::make(24)` por item
(`linhaExibicaoItem()`), MESMA distribuição de `columnSpan` do
cabeçalho/input (1,4,7,1,3,3,1,3,1), com `Text` somente-leitura + um
`ActionGroup` (Editar/Excluir) na última coluna. O item ATUALMENTE em
edição é OMITIDO da listagem (`->reject()`) — seus dados já aparecem na
linha de input logo acima; mostrar as duas ao mesmo tempo duplicaria a
linha. Mostra TODOS os itens do Projeto, não só os de origem Item
Avulso — única origem com persistência real até agora, mas a área é a
mesma pras 7 (task pediu explicitamente). Descrição aparece em TEXTO
PURO (`Str::stripTags()`) na listagem — o dado gravado é HTML
(RichEditor), mas exibir a formatação de verdade ali exigiria um
componente `Html`/`View` em vez de `Text`, com risco de quebrar a
altura/alinhamento de uma grid pensada pra uma linha só; a formatação
completa continua disponível ao entrar em modo edição.

### Itens não apareciam ao abrir a tela de edição — corrigido (2026-09-05)

Achado real: a listagem acima lia `$record->itens()->orderBy(...)->get()`
DIRETO no fecho do `Group` (reconsultando o banco a cada avaliação do
Schema) — em tese sempre atualizado, mas o usuário reportou a área
"Itens" vazia ao abrir uma tela de edição de verdade com itens já
salvos. **Correção**: `EditProjeto` ganhou uma property pública
`Collection $itensCarregados`, hidratada do banco no `mount()`
(`recarregarItens()`, chamado depois de `parent::mount($record)`) — o
`Group::schema()` da Section "Itens" passou a ler
`$livewire->itensCarregados` (injeção por nome `$livewire`, resolve
pra `$this->getLivewire()` — mesmo mecanismo já documentado pra
`$get`/`$set`/`$record`) em vez de reconsultar `$record->itens()` a
cada render. `$livewire instanceof EditProjeto` é o critério de
visibilidade certo (não `$record` truthy) — só `EditProjeto` declara/
hidrata essa property (mesmo padrão já usado por "Atribuir Processos":
`CreateProjeto` simplesmente não tem a property, e a checagem de tipo
já cobre o caso).

- **`confirmarItemAvulso()`/`excluirItemAvulso()` chamam
  `$livewire->recarregarItens()`** depois de escrever no banco (dentro
  da própria `DB::transaction()` já existente) — sem isso, inserir/
  editar/excluir um item só apareceria atualizado na tela depois de um
  reload completo (regressão do comportamento já testado antes desta
  correção). Os dois métodos ganharam `$livewire` como parâmetro a
  mais (injeção por nome, mesmo mecanismo de `$get`/`$set`/`$record`).
- **Achado real de INICIALIZAÇÃO**: `public Collection $itensCarregados;`
  SEM valor padrão disparava "Typed property ... must not be accessed
  before initialization" — `EditRecord::mount()` (vendor) chama
  `fillForm()`, que já avalia o Schema INTEIRO (inclusive o `Group`
  dinâmico da Section "Itens") pra montar a árvore de componentes,
  ANTES de qualquer código customizado no `mount()` sobrescrito de
  `EditProjeto` rodar. Corrigido setando
  `$this->itensCarregados = new Collection()` como a PRIMEIRA linha do
  `mount()` sobrescrito, ANTES de `parent::mount($record)` —
  confirmado por teste que essa avaliação PRECOCE (com a Collection
  ainda vazia) não "congela" o que vai pra tela final: o Schema é
  reavaliado de novo pro render de verdade, já com
  `itensCarregados` populado por `recarregarItens()` (chamado DEPOIS
  de `parent::mount()`, quando `$this->record` já existe).
- **Causa raiz do bug original permanece não 100% confirmada** — não
  foi possível reproduzir o "itens vazios" com a implementação anterior
  (`$record->itens()` direto no fecho) via `Livewire::test()` mesmo
  simulando exatamente o cenário relatado (itens criados via Eloquent
  puro, ANTES de qualquer interação Livewire, depois abrindo a página
  numa instância nova) — sempre carregou corretamente nos testes desta
  sessão. A hidratação explícita em `mount()` foi implementada mesmo
  assim, por ser exatamente o que a tarefa pediu e por ser mais
  robusta/previsível que depender de timing de avaliação de Schema
  dinâmico (categoria de sutileza real do Filament, confirmada pelo
  achado de inicialização acima) — se o sintoma original tinha outra
  causa (cache de view/config desatualizado no ambiente onde foi
  observado, por exemplo), esta mudança não teria como fazer mal de
  qualquer forma.

### Excluir Item redirecionava pra ListProjetos — corrigido (2026-09-05)

Achado real: excluir um `ItemProjeto` (ícone de lixeira da Section
"Itens") redirecionava a tela inteira pra `ListProjetos`, abandonando a
edição do Projeto atual. **Causa raiz**: `Filament\Resources\Pages\
Concerns\InteractsWithRecord::getDefaultActionSuccessRedirectUrl()`
(vendor, herdado por `EditRecord`/`EditProjeto`) redireciona pra
`$this->getResourceUrl()` **sempre que a Action que acabou de rodar é
`instanceof DeleteAction` (ou `ForceDeleteAction`) — sem checar qual
registro ela de fato excluiu**:

```php
// vendor/filament/filament/src/Resources/Pages/Concerns/InteractsWithRecord.php
public function getDefaultActionSuccessRedirectUrl(Action $action): ?string
{
    return match (true) {
        $action instanceof DeleteAction, $action instanceof ForceDeleteAction => $this->getResourceUrl(),
        default => null,
    };
}
```

Isso dispara automaticamente para QUALQUER `DeleteAction` na página —
não só o botão "Excluir" do cabeçalho (`EditProjeto::getHeaderActions()`,
onde o redirecionamento faz sentido: o PRÓPRIO Projeto da página foi
excluído). `DeleteAction::make("excluirItemProjeto{$item->id}")`
(`linhaExibicaoItem()`) usa essa MESMA classe só pelo visual/
confirmação padrão (ícone de lixeira, cor "danger", modal de
confirmação já ligado por padrão) — ela exclui um `ItemProjeto`, não o
`Projeto`, então nunca deveria redirecionar. Esse mecanismo é chamado
automaticamente por `InteractsWithActions::callMountedAction()` (linha
~283, `$action->dispatchSuccessRedirect()`) depois de QUALQUER Action
terminar com sucesso — não é algo que `DeleteAction`/nossa Action
precisem chamar explicitamente, o Filament já faz isso por baixo dos
panos pra toda Action da página.

**Correção**: `EditProjeto` sobrescreve `getDefaultActionSuccessRedirectUrl()`
verificando o RECORD de fato vinculado à Action (`$action->getRecord()`,
resolve pro `ItemProjeto` explicitamente passado via `->record($item)`)
em vez de confiar só na CLASSE da Action:

```php
public function getDefaultActionSuccessRedirectUrl(Action $action): ?string
{
    if ($action->getRecord() instanceof ItemProjeto) {
        return null;
    }

    return parent::getDefaultActionSuccessRedirectUrl($action);
}
```

Essa checagem cobre automaticamente qualquer Action futura da Section
"Itens" que algum dia use `DeleteAction`/`ForceDeleteAction` sobre um
`ItemProjeto`, sem precisar lembrar de `->successRedirectUrl(...)` em
cada uma individualmente — e não afeta o `DeleteAction::make()` do
cabeçalho (que exclui o `Projeto` da própria página, `$action->getRecord()`
não é `instanceof ItemProjeto`, cai no `parent::...()` normal).

**`inserirItem`/`confirmarItemAvulso`/`editarItemProjeto{id}` NÃO
precisaram de correção** — nenhuma delas é `instanceof DeleteAction`/
`ForceDeleteAction`, então `parent::getDefaultActionSuccessRedirectUrl()`
já retornava `null` (sem redirecionar) por padrão pra elas, confirmado
empiricamente via `Livewire::test()->assertRedirect()` (falha, como
esperado — nenhuma dessas três dispara redirect, nem antes nem depois
desta correção). O usuário relatou que "editar e confirmar" também
redirecionava, mas isso não foi reproduzido em nenhum teste — o fix
acima cobre o caso concretamente confirmado (excluir) e, por ser uma
checagem geral por RECORD (não por nome de Action específica), também
cobriria qualquer variante do problema em edição que viesse a usar
`DeleteAction`/`ForceDeleteAction` no futuro.

### Exclusão de item + renumeração contígua (2026-09-04)

Cada linha da listagem tem um `ActionGroup` (ícone de reticências,
`heroicon-m-ellipsis-vertical`) com Editar e Excluir — **não dois
`iconButton()` lado a lado**: a última coluna é `columnSpan(1)`, a
mesma largura estreita de "Item"/"Qtde."/"%" (calibrada pra caber só
UM ícone), e alargar essa coluna só pra caber dois ícones quebraria o
alinhamento com cabeçalho/linha de input (que só precisam de uma
ação). `ActionGroup` resolve sem mexer no grid — um único gatilho,
dropdown com as duas opções.

- **`Excluir` é `DeleteAction::make(...)->record($item)`** — reaproveita
  o MESMO mecanismo/visual de qualquer outra exclusão do sistema
  (ícone de lixeira, cor "danger", `->requiresConfirmation()` já ligado
  por padrão em `setUp()`, ver `table()` deste Resource pro mesmo
  padrão). `->record($item)` é OBRIGATÓRIO: sem ele a Action resolveria
  o record do CONTAINER (o `Projeto`, não o `ItemProjeto`), já que essa
  linha não vive dentro de uma Table de verdade. `->action()`
  substitui o `$record->delete()` padrão do `DeleteAction` pela
  exclusão + renumeração de verdade (`excluirItemAvulso()`) — a
  notificação de sucesso embutida do `DeleteAction` não dispara mais
  (não é chamada por esse `->action()` customizado); `excluirItemAvulso()`
  manda a própria.
- **Exclusão DEFINITIVA, sem `SoftDeletes`** — decisão deliberada,
  registrada com a rationale completa no Model (`ItemProjeto`): a
  renumeração exige que o número excluído fique DE VERDADE livre pro
  índice único `(projeto_id, numero_item)` da migration; uma linha
  soft-deleted continuaria ocupando esse slot, bloqueando o item
  seguinte de virar esse mesmo número. `LogsBusinessActivity` continua
  funcionando sem `SoftDeletes` (o evento `deleted` padrão do Spatie já
  cobre a exclusão de verdade; só o listener extra de `forceDeleted` é
  que não se registra, e não faz falta aqui). Consistente com o próprio
  enunciado da tarefa: Item de Projeto é "um detalhe operacional, não
  um cadastro central auditado como Obra/Pessoa".
- **`excluirItemAvulso()`**: `DB::transaction()` — exclui o item, depois
  busca (`lockForUpdate()`) todos os itens do MESMO Projeto com
  `numero_item` MAIOR que o excluído, em ORDEM CRESCENTE, e decrementa
  cada um em 1. A ordem crescente é essencial: o item logo depois do
  excluído libera o número dele ANTES do próximo item da lista
  precisar desse mesmo número — sem essa ordem, dois itens poderiam
  colidir temporariamente no mesmo `numero_item` e violar o índice
  único no meio do laço. Tudo dentro de uma única transação — se a
  renumeração de um item falhasse no meio, sem transação o Projeto
  ficaria com um buraco permanente na sequência.
- **Achado real: `update()` NÃO renumerava, `forceFill()` sim.**
  `numero_item` fica de propósito FORA do `$fillable` de `ItemProjeto`
  (só `ItemProjeto::boot()` deve escrevê-lo) — `update(['numero_item'
  => ...])` respeita mass assignment e IGNORA SILENCIOSAMENTE qualquer
  chave fora do `$fillable`, SEM erro nenhum. A primeira versão deste
  método usava `update()`: a query encontrava os itens certos, o
  código "renumerava" sem nenhuma exceção, mas o valor no banco não
  mudava — só descoberto isolando o método via Reflection (fora do
  Filament) e imprimindo o SQL/resultado passo a passo, já que o
  sintoma (nenhum erro, só o resultado errado) não apontava a causa
  sozinho. `forceFill(['numero_item' => ...])->save()` ignora o guard
  de propósito — a única exceção deliberada que este método precisa.

**Achado de teste importante** (ver também CLAUDE.md da raiz,
"Filament — mecanismos que valem lembrar"): validar este fluxo via
`Livewire::test()` rodado em `artisan tinker` SÓ funcionou depois de
trocar TODO `->fillForm([...])` por `->set('data.campo', $valor)` —
`fillForm()` é um no-op silencioso fora de um `TestCase` real
(`app()->runningUnitTests()` falso). Com `->set()`, o fluxo completo
foi validado de ponta a ponta: bloqueio de inserção incompleta (zero
registros no banco), numeração `001`/`002` sequencial, edição com
mudança persistindo, edição SEM mudança confirmada via `updated_at`
inalterado, exclusão com confirmação obrigatória (`mountAction` sozinho
NÃO executa uma Action com `->requiresConfirmation()` — precisa de um
`callMountedAction()` separado, diferente das Actions sem confirmação
usadas até então neste fluxo), renumeração correta após excluir, e um
`Livewire::test()` NOVO (segunda instância, simulando reload de
página) mostrando os itens — e a exclusão — vindos do banco.

### Fluxo Promob: modal de upload + "Checar Total" (2026-09-05)

Selecionar "Promob" + clicar "Inserir" abre um MODAL (em vez da
notificação placeholder que as outras origens não implementadas ainda
usam) — upload de um ou mais XMLs exportados pelo Promob e uma rotina
de conferência ("Checar Total") que soma métricas dos XMLs de item e
compara contra o XML "000" (total do projeto). **Não cria nenhum
`ItemProjeto`** — só calcula e mostra o resultado, sem persistir nada;
fechar/cancelar descarta tudo. **Resultado PRINCIPAL exibido: as 5
métricas do VBA do usuário** (Peças/m²/Metro Linear/Custo/Misc — ver
subseção "Rotina 'Checar Total' ajustada..." mais abaixo, que
substituiu/complementou o comportamento descrito nesta subseção
original); Custo/Preço com margem (parágrafos originais logo abaixo)
virou informação COMPLEMENTAR.

- **Mecanismo do modal — mesmo usado por "Adicionar Endereço", só sem
  o atalho específico de Select**: `Select::make('endereco_id')
  ->createOptionForm([...])->createOptionUsing(...)` (Grid do
  Cabeçalho) é só uma CASCA em cima do mecanismo geral de
  `Filament\Actions\Action` com `->form()`/`->schema()` — por baixo dos
  panos, todo Action com formulário próprio já abre como modal
  automaticamente (sem precisar de `->modal()` explícito), e é
  exatamente isso que `createOptionForm()` monta (uma Action interna
  ligada ao Select, cujo resultado vira a opção escolhida). Como o
  upload do Promob não está criando uma opção de relacionamento — só
  rodando um cálculo, sem retornar nada pro campo — não fazia sentido
  usar `createOptionForm()`; a Action `inserirItemPromob`
  (`Actions::make([...])` da Section "Itens", ao lado de `inserirItem`)
  usa o mecanismo geral diretamente: `Action::make(...)->form([FileUpload,
  Text])->modalWidth(Width::Small)->modalSubmitActionLabel(...)->action(...)`.
  `Width::Small` deliberadamente menor que o modal de Endereço (~7
  campos) — este só tem upload + resultado.
- **Só um dos dois botões "Inserir" fica visível por vez** —
  `inserirItem` (Item Avulso/Item de Linha/SketchUp, com a notificação
  placeholder pras duas últimas) ganhou
  `->visible(fn (Get $get) => $get('origem_item_selecionada') !== 'promob')`;
  `inserirItemPromob` tem a condição inversa
  (`=== 'promob'`). Na tela sempre aparece um único botão "Inserir" na
  mesma posição — só muda de comportamento conforme a origem
  selecionada, mesmo efeito visual de um switch, sem precisar de lógica
  condicional dentro de uma Action só.
- **Resultado exibido via property no Livewire, não via `$get`/`$set`
  do formulário** — `HasPromobResultado` (trait em
  `ProjetoResource/Concerns/`, aplicado a `CreateProjeto` E
  `EditProjeto`, diferente de `$itensCarregados` que só existe em
  `EditProjeto`, porque o upload do Promob não depende do Projeto já
  estar salvo) declara `public ?array $promobResultado = null`. A
  Action seta `$livewire->promobResultado` dentro de `->action()`, e um
  `Text::make(fn ($livewire) => ...)` dentro do MESMO `->form([...])`
  da Action lê essa property pra renderizar o resultado — mesmo padrão
  já validado por `$itensCarregados` (Get/Set/`$livewire` resolvidos
  por nome, injeção do Filament). Motivo de não usar `$get`/`$set`
  aqui: uma Action com `->form()` próprio ganha um Schema/statePath
  DEDICADO (`mountedActions.{n}.data`, não o `data.*` da página) — para
  não precisar confirmar empiricamente se `Get`/`Set` injetados dentro
  do `->action()` de uma Action COM form próprio apontam pro schema da
  Action ou da página, a property no Livewire contorna a ambiguidade
  de vez (e seria necessária de qualquer forma pra sobreviver ao reset
  do form entre uma chamada e outra).
- **O modal NUNCA fecha sozinho** — `->action()` sempre termina com
  `$action->halt()` (mesmo mecanismo de "Trava de exclusão/edição" em
  `ReferenciaPreco`, ver seção correspondente acima), mesmo quando o
  resultado é sucesso — a tarefa pediu explicitamente que o modal só
  EXIBE o resultado, sem fechar/persistir; fechar é sempre uma ação
  manual do usuário (botão "Cancelar", já dado de graça pelo Filament
  em toda Action com form). `->mountUsing(function (?Schema $schema,
  $livewire) { $livewire->promobResultado = null; $schema?->fill(); })`
  reseta o resultado anterior toda vez que o modal é reaberto — precisa
  chamar `$schema?->fill()` manualmente porque sobrescrever
  `mountUsing()` substitui o comportamento PADRÃO (que só faz o fill),
  não o complementa.
- **`FileUpload::make('arquivos_xml')->multiple()->preserveFilenames()`**
  — `preserveFilenames()` é OBRIGATÓRIO aqui: o parser identifica XML
  "000" (total) vs. XML de item pelo NOME do arquivo (ver
  `PromobChecagemTotal::numeroItemDoArquivo()`), e sem
  `preserveFilenames()` o Filament troca o nome por um ULID aleatório
  ao salvar (`BaseFileUpload::getUploadedFileNameForStorageUsing()`,
  vendor) — perderia a informação necessária. Disco `local`, diretório
  `promob-uploads-tmp`: os arquivos são só temporários pro cálculo —
  `ProjetoResource::processarUploadPromob()` lê e IMEDIATAMENTE apaga
  cada um (`Storage::disk('local')->delete($caminho)`, sucesso ou erro)
  pra não acumular XML nenhum em `storage/app/promob-uploads-tmp`
  (confirmado: diretório nem existe mais depois de um teste de ponta a
  ponta).
- **Achado real: upload de XML bloqueado pelo gate GLOBAL de upload
  temporário do Livewire, não pelo `acceptedFileTypes()` do campo** —
  `acceptedFileTypes(['text/xml', 'application/xml'])` no
  `FileUpload` NÃO bastou; toda requisição de upload passa PRIMEIRO
  pelo endpoint genérico `livewire/upload-file`
  (`config/livewire.php` → `temporary_file_upload.rules`), que valida
  contra uma whitelist FIXA e GLOBAL (compartilhada por TODO
  `FileUpload` do sistema, não por campo) — o default do Livewire
  (imagens, vídeo, áudio, pdf, doc/xls/ppt, txt, csv, zip) não incluía
  `xml`/`text/xml`/`application/xml` em lugar nenhum, então o upload
  falhava com 422 ANTES de qualquer validação do Filament rodar.
  Corrigido acrescentando `xml` ao `mimes:` e `text/xml,application/xml`
  ao `mimetypes:` dessa config (`config/livewire.php`) — mudança
  global e aditiva (só amplia o que É ACEITO pelo gate de upload
  temporário; não afeta nenhum campo existente, já que
  `acceptedFileTypes()` de cada campo continua restringindo o que
  aquele campo específico aceita).
- **`PromobXmlParser`/`PromobChecagemTotal`** (`src/Services/`) — o
  parser só LÊ os totais já calculados pelo Promob (`TOTALPRICES/
  MARGINS/ORDER|BUDGET/@VALUE` em `LISTING`/`AMBIENT`/`CATEGORY`, mais
  `ITEM[@COMPONENT="Y"]` recursivo dentro de `CATEGORY/ITEMS` pra
  Referência/Descrição/dimensões/Custo/Preço de cada componente) —
  NUNCA recalcula nada, seguindo a mesma lógica do VBA existente do
  usuário. `PromobChecagemTotal::checar()` identifica XML "000" vs.
  item pelo nome do arquivo (**convenção corrigida em 2026-09-05 — ver
  "Validação do nome do arquivo..." mais abaixo**, a suposição original
  de "caracteres 10-12"/6 dígitos de projeto ficou obsoleta), soma
  Custo/Preço de todos os itens e compara contra o total do "000"
  (tolerância R$ 0,01). **Se não bater, o diagnóstico
  por item compara CATEGORY a CATEGORY** (a mesma `DESCRIPTION`/número
  em ambos os arquivos), NÃO a CATEGORY do "000" contra o total do
  DOCUMENTO do item — confirmado nos 3 XMLs de exemplo que a CATEGORY
  "001" tem o MESMO valor nos dois arquivos (166,6/499,8), enquanto o
  total do DOCUMENTO do item inteiro (222,6/603,8) é maior, porque
  também soma outras categorias daquele mesmo item ("Acessórios"/
  "Hettich"/"Processo de Fabricação", que no "000" aparecem agrupadas à
  parte, não por item) — comparar categoria com documento inteiro
  sempre acusaria diferença, mesmo sem problema real. Categorias do
  "000" sem XML de item correspondente enviado (ex.: essas mesmas
  "Acessórios"/"Hettich"/"Processo de Fabricação" quando aparecem como
  CATEGORY própria no "000") são ignoradas no diagnóstico — sem como
  comparar sem o arquivo.
- **Confirmado com os 3 XMLs de exemplo reais** (`2630001 - 000 Total
  Geral.xml`/`001 Superior.xml`/`002 Inferior.xml` — renomeados em
  2026-09-05 pra seguir a convenção real do nome de arquivo, ver
  abaixo; conteúdo idêntico ao original `260000 - ...`, salvos em
  `tests/Fixtures/Promob/` pra teste automatizado) — o total BATE
  exatamente: Custo R$ 704,40, Preço R$ 2.145,20, validado tanto por
  teste automatizado (`tests/Feature/PromobChecagemTotalTest.php`,
  Pest) quanto manualmente pelo navegador.
- **Teste automatizado cobre parser + "Checar Total" isoladamente**
  (`PromobChecagemTotalTest.php`) — extração de Custo/Preço do "000" e
  de um item, conferência batendo exatamente, diagnóstico apontando a
  categoria certa quando um XML é alterado (teste simula alterar SÓ a
  CATEGORY, mantendo o total do documento consistente com a mudança —
  replicar uma mudança real, não um número solto). **Atenção**: os
  XMLs de exemplo têm quebra de linha `CRLF` (exportação do Promob no
  Windows) — qualquer `str_replace()` num teste precisa considerar
  isso (não montar o texto de busca com `"\n"` esperando bater; usar um
  trecho sem quebra de linha, ou `"\r\n"` explícito).
- **`ddev artisan test` NÃO deve ser usado sem antes checar
  `TEST_TOKEN`** — achado real (2026-09-05), ver "Comandos e fluxo
  úteis" no CLAUDE.md da raiz: rodar a suíte sem essa variável definida
  roda `migrate:fresh` direto no banco de desenvolvimento
  compartilhado (não um banco isolado de teste), e foi exatamente o que
  aconteceu ao validar o teste desta tarefa — o banco de dev precisou
  ser restaurado de um backup, perdendo dados cadastrados depois dele.
  **Mesmo definindo `TEST_TOKEN`, o isolamento não funciona neste
  ambiente hoje** — `ensureWorkerDatabase()` tenta `CREATE DATABASE
  db_p{token}`, mas o usuário `db` do MariaDB só tem privilégio sobre o
  banco `db` (`GRANT ALL PRIVILEGES ON db.*`, sem `CREATE` global),
  então falha com `Access denied` (falha SEGURA — não cai de volta pro
  banco compartilhado, só erra) — testado e confirmado nesta tarefa.
  Até alguém decidir conceder esse privilégio ao usuário `db`
  (mudança de infraestrutura fora do escopo de uma tarefa de feature,
  não feita sem pedido explícito), validar lógica pura (como este
  parser) por um script avulso/`tinker` é o único jeito seguro — nunca
  `artisan test`/`pest` de verdade neste ambiente.

### Rotina "Checar Total" ajustada pras 5 métricas do VBA (2026-09-05)

A primeira versão da rotina "Checar Total" (subseção acima) só
comparava Custo/Preço (valores COM margem, `TOTALPRICES/MARGINS/
ORDER|BUDGET/@VALUE`). O usuário já usa uma macro VBA própria
(`CompararTotalGeral`/`EscreverResumoFixo`/`ColetarComponentes`) que
compara **5 métricas** por componente (`ITEM[@COMPONENT="Y"]`, "peça"
de verdade — matéria-prima, não grupo/submontagem) — essa passou a ser
a comparação PRINCIPAL exibida no modal; Custo/Preço com margem
(subseção acima) virou informação COMPLEMENTAR, ao final do mesmo
texto.

**As 5 métricas** (`PromobXmlParser::metricas()`), acumuladas
percorrendo TODO `AMBIENT`/`CATEGORY`/`ITEMS`/`ITEM` do XML (sem
agrupar por categoria — aqui só interessa o total geral do arquivo
inteiro):

| Métrica | Fórmula (por componente `COMPONENT="Y"`, some tudo) |
|---|---|
| Tot. Peças | `Σ REPETITION` |
| Tot. m² | `Σ (REPETITION × QUANTITY)` |
| Tot. Metro Linear | `Σ ((WIDTH + DEPTH) × 2 × REPETITION / 1000)` (perímetro × repetição, mm → m) |
| Tot. Custo | `Σ (PRICE/@TOTAL + PRICE/@TOTALCOMPONENTS)` — custo PRÓPRIO do componente |
| Tot. Misc | `TOTALPRICES/MARGINS/ORDER/@VALUE` da RAIZ (`LISTING`) MENOS Tot. Custo |

- **Achado real de double-counting — por que a árvore NÃO desce além
  de um `COMPONENT="Y"`**: as 4 primeiras métricas somam por
  componente andando pela árvore `ITEM > ITEMS > ITEM > ...`, mas a
  recursão (`acumularMetricasComponentes()`) só continua descendo
  quando o nó atual é `COMPONENT="N"` (grupo/submontagem, sem
  contribuição própria) — ao achar um `COMPONENT="Y"`, conta ele e
  PARA, sem olhar dentro dos filhos dele. Motivo: se um componente de
  verdade tiver, dentro da própria árvore, outro componente agregado
  também `COMPONENT="Y"` (ex.: um tampo com uma porta agregada), o
  `PRICE/@TOTALCOMPONENTS` do PAI já reflete o que está "rolado" desse
  filho — descer e somar o filho de novo, separadamente, duplicaria o
  valor. **Nos 3 XMLs de exemplo não existe nenhum caso desses**
  (confirmado por script Python percorrendo a árvore procurando
  `COMPONENT="Y"` aninhado dentro de outro `COMPONENT="Y"` — zero
  ocorrências), então essa regra não muda o resultado numérico destes
  arquivos especificamente, mas é a implementação CORRETA pra
  qualquer XML real que tenha esse caso (`CustoProprioItem` do VBA,
  não a soma "com filhos" que já existia em `extrairComponentes()`,
  usada só pelo diagnóstico por CATEGORY do Custo/Preço complementar,
  nunca somada item a item).
- **Achado real de arredondamento em cascata**: a primeira
  implementação arredondava `m2`/`mlinear`/`custo`/`misc` já dentro de
  `PromobXmlParser::metricas()` (por ARQUIVO), e só depois somava os
  arquivos de item — isso introduzia uma "diferença" de até ~0,01
  quando comparado ao XML "000" (que calcula o mesmo total de uma vez
  só, sem essa rolagem de arredondamento por arquivo), MESMO com dados
  idênticos (confirmado com os 3 XMLs de exemplo: `mlinear` dava
  80,60 na soma dos parciais vs. 80,61 no "000", uma diferença
  inteiramente artificial). Corrigido: `metricas()` retorna os valores
  CRUS (float de precisão total, sem `round()`); `PromobChecagemTotal
  ::compararMetricas()` soma os valores crus de todos os itens, calcula
  a diferença (`geral - soma dos crus`) e só arredonda no fim, pra
  exibição. Regra geral a lembrar: nunca arredondar um valor
  intermediário que ainda vai ser somado/subtraído com outros — só
  arredondar no último passo, antes de formatar pra tela.
- **`PromobChecagemTotal::checar()` NÃO lança mais exceção sem o XML
  "000"** — comportamento mudado nesta tarefa pra bater com o VBA:
  antes (subseção acima) lançava `RuntimeException`; agora
  `compararMetricas()`/`compararCustoPreco()` retornam
  `tem_geral: false`/`bateu: null` e só a SOMA das métricas parciais
  (sem diferença calculada, já que não há o que comparar) — o modal
  mostra um aviso ("Nenhum XML 'Geral' enviado...") em vez de um erro.
- **Diferença exibida SEM tolerância, valor cru** — diferente da
  comparação complementar de Custo/Preço (que usa ±R$ 0,01 de
  tolerância e um "bateu"/"não bateu" booleano), a comparação das 5
  métricas mostra a DIFERENÇA NUMÉRICA diretamente (`geral MENOS soma
  das parciais`, métrica a métrica) — mesmo comportamento do VBA, que
  deixa o usuário interpretar se o valor é zero/aceitável. A cor do
  resultado no modal (`corResultadoPromob()`) usa zero exato (`!= 0`
  em cada uma das 5 diferenças) só pra decidir a COR (verde/amarelo),
  não pra decidir se mostra ou esconde a diferença.
- **Confirmado com os 3 XMLs de exemplo reais** — as 5 métricas batem
  EXATAMENTE (diferença zero em todas): Tot. Peças 51, Tot. m² 8,23,
  Tot. Metro Linear 80,61, Tot. Custo R$ 552,40, Tot. Misc R$ 152,00 —
  tanto na soma dos 2 XMLs de item quanto no XML "000" isoladamente.
  Validado por teste automatizado (`PromobChecagemTotalTest.php`,
  ainda não executado via `artisan test`/Pest nesta tarefa por falta de
  isolamento de banco seguro, ver achado de `TEST_TOKEN` acima — valores
  conferidos por script PHP avulso, sem depender do Laravel/Pest) e
  manualmente pelo navegador.

### Validação do nome do arquivo + "Checar Total" condicional + botão "Criar Itens" (2026-09-05)

**Convenção REAL do nome de arquivo, confirmada pelo usuário** (a
suposição anterior — "chars 10-12", 6 dígitos de projeto — ficou
obsoleta, os 3 XMLs de exemplo foram RENOMEADOS pra seguir a convenção
certa): os primeiros **7 dígitos** são o Número do Projeto, seguidos
de `<espaço>-<espaço>`, seguidos de um código de **3 dígitos** que é o
Número do Item (`000` = XML do Projeto Geral/consolidado, só
conferência). O resto do nome (depois do número do item) é descrição
livre, não validada. Ex.: `2630001 - 001 Superior.xml` → Projeto
`2630001`, Item `001`. `PromobChecagemTotal::identificarArquivo()`
(regex `/^(\d{7})\s*-\s*(\d{3})/`, ancorada no início do nome sem
extensão) substituiu o antigo `numeroItemDoArquivo()` — retorna
`['numero_projeto' => ..., 'numero_item' => ...]`, lança
`RuntimeException` (mensagem em português puro, sem `__()` — mesma
convenção já usada nas exceções desta classe) se o nome não bater com
o padrão.

**Parte 1 — validação contra o Projeto atual, lote inteiro rejeitado
se qualquer arquivo for inválido** (`PromobChecagemTotal::
validarNomesDeArquivos()`, chamado por `ProjetoResource::
calcularResultadoPromob()` ANTES de rodar `checar()`): compara o
`numero_projeto` de CADA arquivo contra `$record->numero_projeto` —
**decisão deliberada: rejeita o LOTE INTEIRO (nenhum cálculo roda) se
QUALQUER arquivo for de outro Projeto ou tiver nome fora do padrão**,
em vez de descartar só os arquivos problemáticos e seguir com os
válidos. Motivo: uma checagem "silenciosamente incompleta" (que
ignorasse um arquivo errado e comparasse só o resto) apareceria pro
usuário como um resultado normal/confiável, escondendo exatamente o
tipo de erro que essa validação existe pra pegar — pior que travar e
pedir pra corrigir o upload. Todas as mensagens de erro (uma por
arquivo problemático) são concatenadas e mostradas juntas no mesmo
resultado do modal (`$livewire->promobResultado = ['erro' => ...]`),
reaproveitando o mesmo `Text` que já exibe qualquer erro.

**Parte 2 — "Checar Total" começa desabilitado, só libera com um XML
"000" válido do Projeto atual entre os arquivos** (`PromobChecagemTotal
::possuiXmlGeralValido()`, só olha NOMES — não abre/lê conteúdo, mais
barato pra rodar a cada render). Com só o "000" (sem nenhum parcial),
o resultado mostra os totais do "000" normalmente, "soma das parciais"
= 0 pra todas as 5 métricas — sem tratamento especial, é só a
matemática normal do "geral menos zero".

**Parte 3 — botão "Criar Itens"**: mesma condição de habilitação do
"Checar Total" (precisa do "000" válido — sem ele não há "diferença"
pra decidir se pede confirmação). Roda a MESMA `calcularResultadoPromob()`
internamente mesmo sem o usuário ter clicado "Checar Total" antes; se
QUALQUER uma das 5 métricas de diferença for `!= 0`, pede confirmação
("Divergência de valores" / "Arquivos com divergência de valores.
Confirma a criação dos Itens?"); confirmando (ou se já bate tudo, sem
precisar confirmar), mostra a notificação placeholder já usada por
outras origens/ações pendentes ("Mobilização e Frete") — **ainda SEM
criar nenhum `ItemProjeto`**, essa é uma tarefa futura.

**Três achados reais/armadilhas do Filament, todos descobertos
depurando esta tarefa** (documentados aqui em detalhe porque nenhum é
óbvio e todos custaram tempo de investigação — valem pra qualquer
Action futura que precise de múltiplos botões de rodapé interagindo
com o mesmo formulário):

1. **O botão de "submit" automático do modal (`modalSubmitAction()`)
   NUNCA passa por `prepareModalAction()`** — só
   `getExtraModalFooterActions()` chama isso (que faz
   `schemaContainer($this->getSchemaContainer())`, necessário pra
   `Get`/`$record` injetados funcionarem dentro da Action). Um
   `->disabled(fn (Get $get) => ...)` no botão de submit quebra com
   `Call to a member function makeGetUtility() on null`. Solução:
   `->modalSubmitAction(false)` (remove o submit automático) e usar
   `->extraModalFooterActions([...])` pra TODOS os botões do rodapé —
   nenhum vira "o" submit, mas todos ficam com `Get`/`$record`
   funcionando.
2. **`Get $get` dentro de uma Action do rodapé (`extraModalFooterActions`)
   NÃO aponta pro Schema PRÓPRIO da Action mãe montada** — aponta pro
   container de onde a Action mãe está DECLARADA (aqui, o `Actions::
   make([...])` da Section "Itens", ligado ao form da PÁGINA), não pro
   Schema dedicado que `getMountedActionSchema()` cria com `statePath(
   "mountedActions.{n}.data")` (onde os CAMPOS do `->form()` da Action
   mãe de fato vivem). `$get('arquivos_xml')` sempre voltava `null`,
   mesmo com arquivos selecionados. **E mesmo se apontasse certo, não
   adiantaria** — ver achado 3. Correção: ler direto de
   `$livewire->mountedActions[...]['data']['arquivos_xml']` — **NÃO um
   índice fixo** (a primeira versão deste achado dizia "índice `0` é
   sempre a Action mãe", o que é FALSO depois do primeiro cancelamento
   — ver achado 6 mais abaixo, que corrige isso) — e sim a entrada MAIS
   RECENTE cujo `name` é `inserirItemPromob`, ver `ProjetoResource::
   arquivosXmlPromobAtuais()`/`indiceMountedActionInserirItemPromob()`.
3. **Nenhuma Action do rodapé sem `->form()` próprio jamais desidrata
   o Schema da Action MÃE** — `callMountedAction()` (vendor) só chama
   `$schema->getState()` (o gatilho de `FileUpload::saveUploadedFiles()`,
   que move um upload de "temporário no Livewire" pra "arquivo de
   verdade no disco configurado") quando a Action sendo executada TEM
   seu próprio Schema (`mountedActionHasSchema()`) — "Checar Total"/
   "Criar Itens" não têm. Ou seja: mesmo com o `Get` acertado, o valor
   de `arquivos_xml` NUNCA seria desidratado nesse fluxo — ficaria pra
   sempre como objeto `Livewire\Features\SupportFileUploads\
   TemporaryUploadedFile` cru. **Solução (também mais simples que
   tentar forçar a desidratação)**: não esperar por ela — ler o NOME
   ORIGINAL (`UploadedFile::getClientOriginalName()`) e o CONTEÚDO
   (`UploadedFile::get()`) DIRETO do objeto cru, que já funcionam sem
   nenhum "save". Consequência: o campo `FileUpload::make('arquivos_xml')`
   não precisa mais de `->disk()`/`->directory()`/`->preserveFilenames()`
   nem de limpeza manual de diretório temporário — o Livewire cuida do
   próprio ciclo de vida do upload temporário sozinho.
4. **`Action::shouldOpenModal()` abre modal se `hasCustomModalHeading()`
   OU `hasModalDescription()` forem verdadeiros — INDEPENDENTE do
   resultado de `requiresConfirmation()`** (achado real: com
   `->modalHeading()`/`->modalDescription()` como STRINGS FIXAS no
   `criarItensPromob`, o modal de confirmação aparecia SEMPRE, mesmo
   quando `requiresConfirmation()` calculava `false` porque as 5
   métricas batiam). Correção: `modalHeading`/`modalDescription`
   viraram Closures que retornam `null` quando não precisa confirmar
   (`ProjetoResource::promobPrecisaConfirmarCriacao()`, chamado pelas
   três — `requiresConfirmation`/`modalHeading`/`modalDescription` —
   pra manter os três SEMPRE de acordo entre si).
5. **`$action->halt()` numa Action de rodapé "chata" (sem `->form()`
   nem `requiresConfirmation()`) deixa ela PRESA em `mountedActions`
   pra sempre, quebrando o fechamento do modal PAI** — versão CORRIGIDA
   deste achado (a primeira redação, de 2026-09-05, estava incompleta:
   dizia que só `criarItensPromob` tinha esse problema e que
   `checarTotalPromob` "nunca teve" — **errado**, só não tinha sido
   testado o suficiente: `checarTotalPromob` tinha o MESMO bug,
   silencioso — o modal continuava VISÍVEL e aparentemente normal
   depois de clicar nele, mas nem "Cancelar" nem Esc conseguiam mais
   fechá-lo depois disso, ver achado 6). `halt()` deixa a Action sem
   passar por `unmountAction()` (que faz `array_pop($this
   ->mountedActions)`) — pra uma Action com form/confirmação, isso é
   EXATAMENTE o objetivo (manter o modal DELA aberto); pra uma Action
   "chata" sem nenhum dos dois, não existe modal próprio pra manter
   aberto — só sobra uma entrada FANTASMA empilhada por cima da Action
   mãe, e o mecanismo do Filament que decide "qual modal fechar ao
   clicar Cancelar/Esc" se confunde com essa entrada extra sem
   conteúdo. **Regra sem exceção**: `$action->halt()` só em Action que
   TEM `->form()` ou `requiresConfirmation()` — qualquer Action "chata"
   (`checarTotalPromob` incluída) deve terminar normalmente, sem
   `halt()`; terminar uma Action aninhada só a remove da pilha,
   nunca fecha quem a chamou.
6. **O botão "Cancelar"/"X" do modal fecha via Alpine PURO — sem
   NENHUMA requisição ao servidor** — `Action::close()` (usado por
   `getModalCancelAction()`, vendor) faz `getJsClickHandler()` retornar
   `null` quando `shouldClose()` é `true`, e sem isso
   `getLivewireClickHandler()` também fica `null` — o botão renderiza
   SEM `wire:click`, só com `x-on:click="close()"` (Alpine). Ou seja,
   `unmountAction()` NUNCA roda ao cancelar. Como `$livewire
   ->mountedActions` é uma property PÚBLICA do Livewire (persistida no
   snapshot da página entre requisições, não recriada do zero a cada
   uma), cancelar deixa pra trás uma entrada "fantasma" de
   `inserirItemPromob` — reabrir empurra outra (`mountAction()` sempre
   dá `$this->mountedActions[] = [...]`, sem checar se já existe uma
   com o mesmo nome), então o índice de `inserirItemPromob` cresce a
   cada ciclo abrir→cancelar→reabrir. **Ler um índice FIXO (`0`, como a
   primeira versão fazia) é um bug garantido depois do primeiro
   cancelamento** — a correção foi buscar sempre a ÚLTIMA entrada cujo
   `name` é `inserirItemPromob` (`ProjetoResource::
   indiceMountedActionInserirItemPromob()`), nunca um índice fixo. Foi
   a causa raiz ÚNICA dos 3 bugs relatados em 2026-09-06 (estado não
   resetado entre aberturas, "Checar Total" habilitado à toa,
   confirmação de "Criar Itens" não disparando) — todos liam
   `arquivos_xml` de uma sessão ERRADA (antiga), então pareciam 3
   sintomas diferentes mas eram 1 causa só. Resetar em `mountUsing()`
   (pedido original da tarefa, "resetar só na entrada") continua sendo
   a estratégia CERTA — nunca foi o reset em si que falhava, e sim ler
   a entrada errada depois.

## Limitações conhecidas

- Situação de Projeto e Tipo de Projeto usam o padrão `ManageRecords`
  do Filament (uma página só, modal) — sem `SoftDeletes`, sem Lixeira,
  sem aba de Atividades própria (mas continuam auditados pela
  Central, ver `plugins/perseu/auditoria/CLAUDE.md`). Não expandir
  isso preventivamente — só se/quando virar necessidade real, como
  decisão própria.

## Pendências

- **PDF de Proposta**: ao final do fluxo comercial, gerar PDF no
  estilo do documento real da F.A. Marcenaria (cabeçalho projeto/
  contratante/contratada, itens/serviços com valores, condições de
  pagamento, cláusulas, assinaturas). Avaliar `barryvdh/laravel-dompdf`
  (já no `composer.json` do projeto). Cluster "Referências" (Preços)
  já existe como base de dados para isso; Propostas/Contratos/Termos
  de Entrega/Garantia ainda não têm Model/Resource.
- **Vínculo Projeto ↔ Processo** e **Remover Lixeira individual de
  Projeto**: pendências cross-plugin — ver `CLAUDE.md` da raiz.
- **Cálculo do valor de Venda do Projeto a partir da Referência de
  Preços**: o vínculo (`referencia_preco_id`) já existe (ver "Vínculo
  Projeto → Referência de Preços" acima), mas o cálculo em si ainda
  não foi implementado — próximo passo natural depois desta etapa.
- **Origens "Item de Linha" e "SketchUp"**: continuam só com a
  notificação placeholder no Select — `itens_projeto` já está
  preparada pra receber qualquer uma (`origem`/`produto_id`), mas a
  lógica própria de cada uma ainda não foi desenhada ("Item de Linha"
  precisa de um cadastro de Produto, que ainda não existe). "Promob"
  já saiu do placeholder — ganhou modal de upload + rotina "Checar
  Total" (ver "Fluxo Promob" acima), mas ainda NÃO cria nenhum
  `ItemProjeto` de verdade (só compara totais) — esse é o próximo passo
  natural quando o mapeamento Referência/Custo Promob → colunas de
  `itens_projeto` for desenhado. Lista de origens simplificada de 7
  para 4 opções em 2026-09-05 (ver "Section 'Itens'..." acima) — as 5
  opções mais específicas removidas (Promob Plus/Start, Sketchup
  Hellomob/CutList, CortCloud — "Item de Linha" foi MANTIDA, só as
  outras 4 substituídas por "Promob"/"SketchUp") podem voltar como
  sub-opções dentro de "Promob"/"SketchUp" no futuro, se necessário.

## Ver também (histórico narrado, `HISTORICO-DESENVOLVIMENTO.md`)

- "Rename 'Projeto' → 'Obra' no plugin `perseu/comercial`" (28/08/2026)
- "Cluster 'Obras' no plugin `perseu/comercial` — investigação e
  implementação" (29/08/2026)
- "Cluster 'Referências' no plugin perseu/comercial, com o cadastro de
  Preços" (30/08/2026)
- "Referência de Preços: campos de Imposto/Despesas + criação/edição
  em modal" (30/08/2026)
- "Referência de Preços: mais 4 campos (Valor por Peças + 3 Fatores) e
  decisão de não poluir a listagem" (30/08/2026)
- "Remoção do campo 'Revisão' de Obra — pertencia conceitualmente à
  Proposta" (01/09/2026)
- "'Revisão' volta a existir em Obra — replanejamento: sem cadastro de
  Proposta separado, por ora" (02/09/2026)
- "Rename Obra → Projeto no plugin `perseu/comercial`" (02/09/2026)
