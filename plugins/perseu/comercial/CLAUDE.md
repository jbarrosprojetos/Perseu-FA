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

### Cabeçalho estilo planilha para "Item Avulso" (2026-09-03, colunas corrigidas 2026-09-03, Imp.% removido 2026-09-03, ícones de ajuda no cabeçalho 2026-09-04, "Custo Unitário" abreviado 2026-09-04, virou cabeçalho FIXO da listagem em 2026-09-06)

Este cabeçalho de colunas — `Grid::make(24)` com **9 colunas visíveis**
(`columnSpan`: Item 1, Referência 4, Descrição 7, Qtde. 1, Valor Unit.
3, Valor Total 3, Porc.% 1, Custo Unitário 3, última coluna sem rótulo
1 — soma 24) — é hoje o cabeçalho FIXO da tabela de itens já inseridos
(sempre visível, independente de origem/seleção — ver "Item Avulso
migrado de linha inline pra Form Modal" mais abaixo pra essa mudança).
Até 2026-09-06 ele só aparecia ao clicar "Inserir" com "Item Avulso"
selecionado, condicionado a um `Hidden` de estado (ver bullet
"Estado via `Hidden::make(...)`" logo abaixo, HISTÓRICO — esse campo
foi removido); a distribuição de colunas em si não mudou. A inspiração
é a aba "00" do Excel `260000 Cliente Padrão Proposta 00.xlsm` usado
hoje pela F.A. Marcenaria. Todos os rótulos aparecem abreviados
("Qtde.", "Valor Unit.", "Porc.%") — **"Porc.%" é a mesma coluna que já
passou por "Desconto" → "Porcentagem" → "Porc.%"**, não confundir com
uma coluna nova; a última coluna (1) continua reservada, sem texto —
sem uso definido ainda (a coluna que ANTES se chamava "Total Custo" foi
renomeada para "Custo Unitário" em 2026-09-03 — representa o custo
unitário do item, não mais um "total"). As outras 3 origens do dropdown
(Item de Linha, Promob, SketchUp) continuam com a notificação
placeholder normal (Promob ganhou depois um modal próprio, ver
subseção "Fluxo Promob" abaixo); só "Item Avulso" tem esta tabela/
modal próprios até agora.

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
- **HISTÓRICO (removido em 2026-09-06): estado via `Hidden::make('origem_item_inserida')`**
  (`dehydrated(false)`, fora do `$fillable`) — guardava qual origem
  tinha seu botão "Inserir" clicado por último; a Action de "Inserir"
  fazia `$set()` nesse campo (`'item_avulso'` só quando essa origem era
  a selecionada, `null` nos demais casos) e o `Grid::make(24)` do
  cabeçalho usava `->visible(fn (Get $get) => $get('origem_item_inserida')
  === 'item_avulso')`. Deixou de existir quando o cabeçalho virou fixo
  (sempre visível) e a linha de INPUT (que também dependia desse campo)
  virou modal — ver "Item Avulso migrado de linha inline pra Form
  Modal" mais abaixo.
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

### Toolbar do RichEditor de "Item Avulso" — removida na linha inline, REABILITADA no modal (2026-09-03, texto fixo trocado por ícone com balão em 2026-09-03, ícone movido pro cabeçalho em 2026-09-04, campo migrado pra dentro do modal em 2026-09-06, toolbar reabilitada em 2026-09-06, ampliada pra padrão COMPLETO no mesmo dia)

**HISTÓRICO (até 2026-09-06): linha de INPUT inline, sem toolbar.** Até
a migração pra Form Modal (ver "Item Avulso migrado de linha inline
pra Form Modal" mais abaixo), o campo de Descrição vivia numa linha de
INPUT inline, um SEGUNDO `Grid::make(24)` logo abaixo do cabeçalho de
colunas, com os MESMOS `columnSpan` do cabeçalho — `RichEditor::make('novo_item_descricao')`
com `->hiddenLabel()` (o rótulo de verdade vivia só na linha de
CABEÇALHO acima), `->toolbarButtons([])` (SEM toolbar visual — só
atalhos de teclado, ver investigação do bubble menu abaixo) e um
`->hintIcon()` pro texto de orientação dos atalhos. Logo depois da
migração pro modal (mesma data, 2026-09-06), o campo passou a se
chamar só `descricao`, ganhou `->label()`/`->helperText()` PRÓPRIOS
(`descricao-atalhos` virou `->helperText()` comum) — mas a toolbar
continuava desativada nesse primeiro momento, por inércia da decisão
antiga.

**Toolbar REABILITADA (mesmo dia, 2026-09-06)** — o motivo original
pra remover a toolbar (linha de input inline, espaço horizontal muito
apertado — 7 de 24 colunas) deixou de existir: dentro do Form Modal
sobra espaço de sobra (a `Descrição` usa `->columnSpanFull()` da
largura TOTAL do modal). Primeira tentativa: `->toolbarButtons([['bold',
'italic', 'underline', 'bulletList']])` — conjunto REDUZIDO, só os 4
botões considerados essenciais pra uma descrição curta de item. O
`->helperText('descricao-atalhos')` (texto "Use atalhos de teclado
para formatar...") foi REMOVIDO do campo nesse mesmo momento — não faz
mais sentido pedir pro usuário decorar atalhos com a toolbar visual
presente. A chave de tradução `descricao-atalhos` **continua existindo
e em uso** — é o tooltip do ícone de ajuda da coluna "Descrição" no
CABEÇALHO fixo da listagem (`linhaExibicaoItem`/cabeçalho da tabela,
mecanismo `Icon`/`Flex`/`->dense()` documentado na subseção "Cabeçalho
estilo planilha..." acima), que é uma UI completamente separada do
modal.

**Ampliada pro padrão COMPLETO do Filament (mesmo dia, 2026-09-06,
tarefa seguinte)** — decisão explícita do usuário: começar com TODOS
os botões disponíveis (`RichEditor::getDefaultToolbarButtons()`, 20
botões — negrito/itálico/sublinhado/tachado/subscrito/sobrescrito/
link, cabeçalho (H2)/subtítulo (H3), alinhar início/centro/fim,
citação/bloco de código/lista com marcadores/lista ordenada, tabela/
anexar arquivos, desfazer/refazer) e só reduzir DEPOIS, com uso real,
se algum botão se mostrar desnecessário — em vez de já cortar
preventivamente sem ter usado. Implementação: `->toolbarButtons([...])`
foi REMOVIDO por completo do campo (nenhuma customização) —
`RichEditor::toEmbeddedHtml()` usa `getDefaultToolbarButtons()` como
fallback sempre que `toolbarButtons()` não foi chamado, então a
ausência da chamada já é suficiente pra voltar ao padrão inteiro do
pacote (não precisa listar os 20 botões manualmente). **Confirmado por
teste de navegador (Playwright)**: os 20 botões aparecem, cabem numa
única linha sem overflow nem quebra de layout no modal (não precisou
nem quebrar em duas linhas, como a tarefa antecipava como aceitável);
formatar um trecho como H2 (botão "Cabeçalho") e salvar persiste
corretamente no banco (`<h2>...</h2>`, confirmado lendo o registro).
**Se o usuário decidir reduzir a toolbar no futuro** (subconjunto
específico pra descrição de item), a forma é reintroduzir
`->toolbarButtons([[...]])` com os botões escolhidos — mesma sintaxe
já testada na tentativa reduzida acima, só o CONTEÚDO do array muda.

`RichEditor::toEmbeddedHtml()` só pula o `<div class="fi-fo-rich-editor-
toolbar">` quando `toolbarButtons()` resolve pra um array vazio
(`if ((! $isDisabled) && filled($toolbarButtons))`) — com botões
(reduzidos ou o default completo), a barra sempre renderiza
normalmente. As extensões TipTap SEMPRE estiveram todas carregadas,
independente da toolbar (`toolbarButtons()` só controla quais BOTÕES
aparecem, não quais extensões/marcas o editor sabe processar) — os
atalhos de teclado (tabela abaixo) sempre funcionaram, com ou sem
toolbar reduzida/completa/nenhuma.

**Investigação (2026-09-03, ainda válida): toolbar "tipo bubble menu"
(só em foco, esconde ao perder foco) — NÃO implementada, descartada
por conflito de UX, não por dificuldade técnica pura.** O mecanismo
existe e é de primeira classe (`RichEditor::floatingToolbars()`,
documentado em `vendor/filament/forms/docs/10-rich-editor.md`,
"Customizing floating toolbars") — usa `@tiptap/extension-bubble-menu`
por baixo (`vendor/filament/forms/resources/js/components/rich-editor.js`,
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
(`->toolbarButtons([])`), sem tentar escondê-la condicionalmente.

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
verificar visualmente que o texto aparece formatado (negrito/itálico) —
o conteúdo do `RichEditor` é renderizado inteiramente no CLIENTE via
TipTap/Alpine (`wire:ignore` na div raiz, conteúdo passado como JSON
via `$wire.entangle()`, DOM populado por JS depois do carregamento da
página), então o HTML devolvido por `Testable::html()` nunca contém o
texto formatado renderizado — só o wrapper/toolbar/estado inicial. O
que DÁ pra confirmar via teste: que `$set('descricao', '<p><strong>...</strong></p>')`
converte corretamente pro formato interno JSON do TipTap (`RichEditor
StateCast`, campo guarda `{"type":"doc","content":[...,{"marks":
[{"type":"bold"}]}]}`, não a string HTML crua) preservando a marca
`bold` — ou seja, a INTEGRIDADE do dado sobrevive independente de ter
ou não toolbar visual (esperado, já que `toolbarButtons()` é puramente
de renderização, não mexe no processamento de marcas) — mas a
confirmação visual de que aparece formatado na tela (ou de que os
botões da toolbar aplicam a marca certa ao clicar) exige navegador de
verdade. **Confirmado por Playwright nesta tarefa (2026-09-06)**:
clicar o botão "Negrito" da toolbar (dentro do modal, com texto
selecionado) aplica a marca corretamente — `<strong>` persistido no
banco após salvar.
**Os campos abaixo (Quantidade/%/Custo Unitário/Valor Unitário/Valor
Total/Imp.%) vivem hoje dentro do Form Modal** (`camposFormularioItemAvulso()`,
ver "Item Avulso migrado de linha inline pra Form Modal" mais abaixo) —
até 2026-09-06 viviam na linha de INPUT inline descrita no início desta
subseção, com prefixo `novo_item_*` nos nomes; os detalhes técnicos
abaixo (CSS sem-spinner, fórmula, tratamento de Imp.%) continuam
valendo integralmente, só os NOMES dos campos e o CONTAINER (modal, não
mais um Grid condicional na página) mudaram.

- **Quantidade** (`quantidade`), **% Acréscimo/Desconto** (`porcentagem`)
  e **Custo Unitário** (`custo_unitario`, desde 2026-09-05): `TextInput`
  `->numeric()->integer()` (Custo Unitário sem `->integer()`, aceita
  decimal), `->live(onBlur: true)`, disparam o recálculo (ver fórmula
  abaixo). `porcentagem` SEM `->minValue()` — aceita negativo de
  propósito (acréscimo/desconto). **Sem setas de incremento/decremento**
  (2026-09-03, estendido ao Custo Unitário em 2026-09-05 — mesmo asset
  reaproveitado, não duplicado):
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
- **Valor Unitário** (`valor_unitario`) e **Valor Total** (`valor_total`):
  `TextInput` `->disabled()` — nunca digitados, só `$set()` pelo
  recálculo. `disabled()` já implica não-dehydratado, mas mantido
  `->dehydrated(false)` explícito por consistência com os outros
  campos calculados.
- **Custo Unitário** (`custo_unitario`): `TextInput` `->numeric()->minValue(0)`
  (só positivo), `->live(onBlur: true)`, dispara o recálculo. Sem setas
  de incremento/decremento desde 2026-09-05 — mesmo `.fi-input-no-spinner`
  de Quantidade/%, ver acima.
- **Imp.% (`imposto`)** — `Hidden::make('imposto')->dehydrated(false)`,
  sem coluna própria na tela desde 2026-09-03 (ver "Imp.% removido"
  no histórico desta subseção). **Preenchimento atual (desde a
  migração pra modal, 2026-09-06)**: `preencherFormularioItemAvulso()`
  — chamada pelo `->mountUsing()` das duas Actions que abrem o modal —
  busca `ReferenciaPreco::find($get('referencia_preco_id'))?->imposto`
  e faz `$schema?->fill([...'imposto' => $imposto, ...])` toda vez que
  o modal ABRE (criação ou edição), sempre com o valor ATUAL da
  Referência de Preços do Cabeçalho (não necessariamente a salva no
  banco — se o usuário trocar a Referência antes de abrir o modal, vale
  a escolha atual). Sem Referência vinculada, fica em branco (`null`) —
  tratado como 0% no cálculo (ver fórmula abaixo); o aviso em vermelho
  já existe no campo "Referência de Preços" do Cabeçalho. **Esse valor
  é só CACHE pra prévia em tela** (`recalcularValoresItemAvulso()`, a
  cada tecla) — a gravação de verdade (`salvarItemAvulso()`) sempre relê
  o banco no momento do clique em Criar/Salvar, ver "Imposto obsoleto ao
  gravar" mais abaixo (o mecanismo de releitura fresca não mudou com a
  migração pro modal, só ONDE o valor de prévia é populado). **HISTÓRICO
  (até 2026-09-06, já não se aplica)**: quando este campo vivia na linha
  de INPUT inline (dentro de um `Grid` com `->visible()` condicional),
  não dava pra popular via `->default(fn (?Projeto $record) => ...)` —
  o `fill()` inicial da página não hidratava campos que COMEÇAVAM
  escondidos (confirmado via `Livewire::test()`); a Action "Inserir" da
  época fazia esse `$set()` manualmente ao clicar. Um Form Modal não tem
  esse problema — `mountUsing()` roda (e o `$schema?->fill()` popula
  tudo) toda vez que o modal abre, then já visível desde o primeiro
  frame.
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
`$imposto`/campo `imposto`, prefixo `novo_item_` removido na migração
pro modal, ver "Item Avulso migrado de linha inline pra Form Modal"
mais abaixo) — desde 2026-09-03 não existe mais como rótulo de coluna
na tela (ver "Imp.% removido da tela" acima), só o cálculo por baixo
dos panos.

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
  atende; concorrência coberta só parcialmente, ver `salvarItemAvulso()`
  abaixo (HISTÓRICO: chamava-se `confirmarItemAvulso()` até a migração
  pro Form Modal, 2026-09-06).
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

**Fluxo de INSERÇÃO** (`ProjetoResource::salvarItemAvulso()`, chamado
pelo `->action()` da Action `inserirItemAvulso` — HISTÓRICO: até
2026-09-06 esse método se chamava `confirmarItemAvulso()` e era
disparado pelo ícone ✓ de uma linha de input inline; ver "Item Avulso
migrado de linha inline pra Form Modal" mais abaixo pra essa migração
completa):

1. Sem `$record` (página de CRIAÇÃO do Projeto, ainda não salva) —
   bloqueia com notificação "salve o Projeto primeiro", mesmo critério
   já usado pelo botão "Atribuir Processos" (só existe depois de salvo).
2. Validação NATIVA do Schema (`->required()`/`->rules(['gt:0'])`/
   `->validationMessages()` em `camposFormularioItemAvulso()`) —
   Quantidade > 0, Custo Unitário > 0. Descrição usa `->rule()` com uma
   Closure própria (`textoPlanoRichEditor()`, ver achado do
   `RichEditor` mais abaixo) em vez de só `->required()`, porque
   "preenchido" pro Laravel (documento TipTap não-vazio) não é o mesmo
   que "tem texto visível" (usuário só formatou um parágrafo vazio).
   **HISTÓRICO (até 2026-09-06)**: essa validação era MANUAL
   (`ValidationException::withMessages(['data.novo_item_quantidade' =>
   ...])`), porque os campos `novo_item_*` da linha inline eram
   compartilhados por TODA a Section "Itens" — um `->required()` ali
   teria exigido esses campos também no Salvar/Cancelar do CABEÇALHO.
   O Form Modal elimina esse problema: `camposFormularioItemAvulso()`
   é um Schema PRÓPRIO da Action (`mountedActions.{n}.data`), isolado
   do form da página — `->required()`/`->rules()` nativos já bastam,
   sem efeito colateral nenhum no restante do formulário.
3. Sem erros: `$record->itens()->create([...])` (ou `update()` em modo
   edição, ver "Fluxo de EDIÇÃO" mais abaixo) dentro de
   `DB::transaction()` com `lockForUpdate()` nas linhas já existentes
   daquele `projeto_id` — trava concorrência de dois cliques rápidos NO
   MESMO Projeto enquanto o `MAX()+1` do `numero_item` é calculado.
   **Sem proteção no PRIMEIRO item de um Projeto** (nada pra travar
   ainda) — risco aceito, não é um fluxo multi-usuário simultâneo real
   (um usuário editando um Projeto de cada vez).
4. O PRÓPRIO Filament fecha o modal ao final de um `->action()` sem
   `$action->halt()` (comportamento padrão de qualquer Action com
   `->form()`) — nenhum reset explícito é necessário aqui, porque o
   modal recebe um `$schema?->fill()` limpo toda vez que ABRE de novo
   (`preencherFormularioItemAvulso()`, chamada por `->mountUsing()`),
   não ao fechar. **NÃO reseta `origem_item_selecionada`** (campo da
   página, fora do modal) — o Select continua em "Item Avulso", já que
   inserir outro item da mesma origem em seguida é o caso comum.

### Imposto obsoleto ao gravar — corrigido (2026-09-05)

Achado real de concorrência (ver `INVESTIGACAO-TRANSACOES-CONCORRENCIA.md`,
risco R1): `novo_item_imposto` (lido uma vez ao abrir a linha — ver
"Imp.% removido da tela" acima) ficava em CACHE no estado do Livewire
por todo o tempo que o usuário levava preenchendo/revendo o item. Se
outra sessão mudasse o `imposto` da Referência de Preços nesse
meio-tempo, o valor gravado usava o Imp.% ANTIGO, sem ninguém perceber
— um bug de corretude de dado financeiro, silencioso.

**Correção**: `salvarItemAvulso()` (HISTÓRICO: `confirmarItemAvulso()`
até a migração pro modal) não usa o `imposto` do formulário pra gravar
— dentro da MESMA `DB::transaction()` da gravação, busca o `imposto`
FRESCO com `ReferenciaPreco::where('id', $referenciaPrecoId)
->lockForUpdate()->value('imposto')` e recalcula
`valor_unitario`/`valor_total` com esse valor. `lockForUpdate()` na
Referência de Preços fecha a janela de corrida por completo (não só
reduz) entre o clique em "Criar"/"Salvar" e o commit. O resultado é
gravado em `itens_projeto.imposto_aplicado` (`decimal(5,2)`, nullable)
— cópia/snapshot do Imp.% efetivamente usado, NÃO um FK vivo — preserva
o histórico do cálculo mesmo que a Referência de Preços mude depois
(também útil pra explicar/auditar um valor calculado meses depois). O
campo `imposto` do modal continua existindo e sendo usado — mas só pra
PRÉVIA em tela (`recalcularValoresItemAvulso()`, a cada tecla), que
pode ficar obsoleta sem problema (é só exibição); a gravação de
verdade sempre relê o banco. `Perseu\Comercial\...\ProjetoResource
::calcularValoresItemAvulso()` foi extraído como função PURA (sem
`Get`/`Set`) justamente pra essa separação: a prévia e a gravação usam
a MESMA fórmula, só com fontes diferentes de Imp.% (cache vs. fresco).
`itemAvulsoMudou()` também passou a comparar `imposto_aplicado`. **Essa
lógica de concorrência sobreviveu INTEIRA à migração pro Form Modal
(2026-09-06)** — só mudou de onde ela é chamada (`->action()` do modal,
não mais de um ícone ✓ de linha inline).

**Fluxo de EDIÇÃO** (migrado pra modal em 2026-09-06 — ver "Item Avulso
migrado de linha inline pra Form Modal" mais abaixo pros detalhes
completos da migração): o `ActionGroup` (Editar/Excluir) de cada linha
da listagem abre uma Action `editarItemAvulso{id}` que reaproveita o
MESMO `->form()` (`camposFormularioItemAvulso()`) e o mesmo
`->action()` (`salvarItemAvulso()`) da inserção — só muda o
`->mountUsing()`, que chama `preencherFormularioItemAvulso($schema,
$get, $record, (string) $item->id)` passando o ID do item (em vez de
`null`) pra preencher com os dados atuais dele. `preencherFormularioItemAvulso()`
sempre **recalcula Valor Unitário/Total a partir do Imposto ATUAL da
Referência de Preços do Cabeçalho** (não o Imp.% usado quando o item
foi originalmente criado — a tarefa original pediu explicitamente
"recalculados normalmente" ao entrar em edição; se a Referência não
mudou, bate exatamente com o valor já salvo). Ao confirmar,
`itemAvulsoMudou()` compara os valores atuais (normalizados —
`round(...,2)` nos decimais, `(int)` na quantidade, `trim()` na
descrição) contra os já gravados; **sem diferença nenhuma, NÃO chama
`update()`** (nem grava log de auditoria "updated" vazio) — só fecha o
modal.

**"Só um item em edição por vez" deixou de ser uma decisão de estado
pra virar consequência natural do modal** — HISTÓRICO (até 2026-09-06):
`item_em_edicao_id` era um único campo de página; abrir a edição de
outro item SOBRESCREVIA esse valor, descartando silenciosamente
qualquer edição não confirmada do item anterior. Um Form Modal já é
inerentemente exclusivo (só um `mountedAction` de cada vez tem sentido
de UX — o Filament nem oferece dois modais abertos ao mesmo tempo),
então essa garantia não precisa mais de nenhum campo de controle
dedicado.

**Listagem dos itens já inseridos** — `Group::make()->schema(fn
($livewire) => [...])`, uma `Grid::make(24)` por item
(`linhaExibicaoItem()`), MESMA distribuição de `columnSpan` do
cabeçalho (1,4,7,1,3,3,1,3,1), com `Text` somente-leitura + um
`ActionGroup` (Editar/Excluir) na última coluna. **Desde a migração pro
modal (2026-09-06), TODOS os itens aparecem sempre** — não existe mais
"item atualmente em edição" pra omitir da listagem (HISTÓRICO: até
então, o item em edição sumia da listagem via `->reject()`, porque seus
dados apareciam duplicados na linha de input logo acima; sem linha de
input inline, essa omissão deixou de fazer sentido). Mostra TODOS os
itens do Projeto, não só os de origem Item Avulso — única origem com
persistência real até agora, mas a área é a mesma pras 4 origens (task
original pediu explicitamente). Descrição aparece em TEXTO PURO
(`Str::stripTags()`) na listagem — o dado gravado é HTML (RichEditor),
mas exibir a formatação de verdade ali exigiria um componente
`Html`/`View` em vez de `Text`, com risco de quebrar a altura/
alinhamento de uma grid pensada pra uma linha só; a formatação completa
continua disponível ao entrar em modo edição (modal).

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

- **`confirmarItemAvulso()` (hoje `salvarItemAvulso()`, ver "Item Avulso
  migrado de linha inline pra Form Modal")/`excluirItemAvulso()` chamam
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

### Item Avulso migrado de linha inline pra Form Modal (2026-09-06)

Toda a UI de inserir/editar um Item Avulso — até aqui uma linha de
INPUT inline (`Grid::make(24)` visível condicionalmente, campos
`novo_item_*` compartilhados com o resto da Section "Itens") — foi
migrada pro MESMO padrão técnico já usado pelo upload do Promob
(`Action::make()->form()->modal()`, ver "Fluxo Promob" abaixo). O
cabeçalho de colunas (`Grid::make(24)` com os rótulos + ícones de
ajuda) **não mudou** — virou o cabeçalho FIXO da tabela de itens já
inseridos, sempre visível, sem depender de nenhum estado de "linha
inserida" (ver "Cabeçalho estilo planilha..." acima). Todas as
subseções anteriores desta Section já foram atualizadas inline com o
estado ATUAL; esta subseção documenta a migração em si — mecanismo e
achados novos.

- **Duas Actions, um `->form()` e um `->action()` compartilhados**:
  `inserirItemAvulso` (visível só quando `origem_item_selecionada ===
  'item_avulso'`, substitui o antigo branch `item_avulso` dentro de
  `inserirItem`) e `editarItemAvulso{$item->id}` (uma por linha da
  listagem, dentro do `ActionGroup`) chamam a MESMA
  `camposFormularioItemAvulso(): array` pro `->form()` e a MESMA
  `salvarItemAvulso(array $data, Get $get, ?Projeto $record, $livewire): void`
  pro `->action()` — só o `->mountUsing()`/`->modalHeading()`/
  `->modalSubmitActionLabel()` diferem (criação vs. edição, `null` vs.
  `(string) $item->id`). Evita duplicar a definição dos 7 campos (e o
  risco de uma das duas cópias divergir da outra com o tempo).
- **Diferente do Promob: SEM `->modalSubmitAction(false)`** — o modal
  de Item Avulso usa o botão de submit AUTOMÁTICO do Filament
  (`->modalSubmitActionLabel()` só troca o RÓTULO, "Criar"/"Salvar").
  O achado 1 documentado em "Validação do nome do arquivo..." abaixo
  (`Get`/`$record` quebram em `extraModalFooterActions()` sem
  `->modalSubmitAction(false)` + `prepareModalAction()`) só se aplica a
  botões de RODAPÉ extras — este modal não tem nenhum, então o submit
  automático (que sempre passa pelo pipeline certo) é mais simples e
  suficiente.
- **Validação nativa do Schema substitui `ValidationException::withMessages()`**
  — possível porque `camposFormularioItemAvulso()` agora é um Schema
  PRÓPRIO da Action (isolado do form da página), ver "Fluxo de
  INSERÇÃO" acima pro motivo completo.
- **Reset de estado: SÓ no `mountUsing()`, nunca no fechar** — mesma
  lição já aprendida e documentada no bugfix do modal do Promob (ver
  achado 6 em "Validação do nome do arquivo..." abaixo): o botão
  Cancelar de um modal fecha via Alpine PURO (`x-on:click="close()"`,
  sem `wire:click`), então qualquer reset que dependesse do fechamento
  simplesmente NUNCA rodaria. `preencherFormularioItemAvulso()` —
  chamada pelo `->mountUsing()` das DUAS Actions — sempre faz um
  `$schema?->fill([...])` completo (todos os 7 campos, inclusive
  `item_id`/`imposto`) toda vez que o modal ABRE, então um Cancelar
  seguido de reabertura (inserir outro item, ou editar um item
  diferente) nunca herda lixo de uma sessão anterior do modal —
  confirmado por teste de navegador (Playwright): preencher parcialmente
  o modal de inserção, Cancelar, reabrir "Inserir" mostra todos os
  campos limpos de novo.

**Achado real (bug novo, não presumido — descoberto e corrigido nesta
tarefa): `RichEditor` dentro de um `->rule()` customizado recebe um
ARRAY, nunca a string HTML.** Sintoma: salvar o modal (mesmo com todos
os campos preenchidos) estourava um "Internal Server Error" — 500 puro,
sem nenhuma mensagem de validação visível — com `ErrorException: Array
to string conversion` apontando pra dentro da Closure de validação da
Descrição (que fazia `(string) $value` antes de `strip_tags()`). Causa
raiz, confirmada lendo o vendor:

- `Filament\Schemas\Concerns\CanBeValidated::validate()` chama
  `$livewire->validate($rules, ...)` **direto sobre o ESTADO BRUTO do
  Livewire** — nunca sobre o resultado de `$component->getState()`
  (que já teria passado pelo `StateCast` de desidratação). As regras
  (`->rule()`/`->rules()`) sempre veem o valor CRU do componente, antes
  de qualquer conversão de saída.
- Pra um `RichEditor`, o estado CRU nunca é a string HTML — é sempre o
  documento TipTap em ARRAY (`Filament\Forms\Components\RichEditor\
  StateCasts\RichEditorStateCast::set()`, chamado toda vez que o campo
  é HIDRATADO/preenchido — inclusive pelo `$schema?->fill()` de
  `preencherFormularioItemAvulso()` — sempre devolve
  `$editor->getDocument()`, um array). Só o `get()` do MESMO StateCast
  (chamado na DESIDRATAÇÃO, `$component->getState()`, que só acontece
  DEPOIS da validação passar) devolve a string HTML final — é esse
  valor que `salvarItemAvulso()` recebe em `$data['descricao']`, correto
  e já testado antes desta correção.
- Ou seja: um `(string) $value`/`strip_tags($value)` direto dentro de
  um `->rule()` de `RichEditor` está **sempre** operando sobre o valor
  ERRADO (array, não HTML) — não é um caso de borda, é o comportamento
  normal pra QUALQUER `RichEditor` com regra de validação customizada,
  em qualquer Schema deste projeto.

**Correção**: `textoPlanoRichEditor(mixed $value): string` — função
nova, pura — trata os dois formatos possíveis (`string` HTML, via
`strip_tags()`; `array` TipTap, percorrendo recursivamente os nós
`content`/`text` acumulando o texto puro). A regra de `descricao` virou
`blank(static::textoPlanoRichEditor($value))`, sem nenhum cast direto.
**Vale para qualquer `RichEditor` futuro que precise de uma regra de
validação própria além de `->required()`** (ex.: "não pode ter só
espaço em branco", como aqui) — `->required()` sozinho NÃO pega esse
caso (um documento TipTap com só um parágrafo vazio ainda é um array
"preenchido" pro Laravel, `required` não falha), mas qualquer Closure
de `->rule()` precisa passar pelo `textoPlanoRichEditor()` (ou
equivalente) em vez de assumir string.

**Confirmado por teste de navegador (Playwright), checklist completo**:
modal "Inserir" abre com cálculo ao vivo funcionando (Quantidade/%/
Custo Unitário → Valor Unitário/Total); modal "Editar" de um item
existente abre PRÉ-PREENCHIDO com rótulo "Salvar" (não "Criar");
salvar sem alterar nada fecha o modal sem chamar `update()`
(`itemAvulsoMudou()` intacto); Cancelar + reabrir (tanto "Inserir" pra
um item novo quanto "Editar" de outro item) sempre mostra o modal
limpo/correto, nunca resíduo de uma sessão anterior; Excluir continua
com confirmação + renumeração intactas (`DeleteAction`/`excluirItemAvulso()`
não foram tocados nesta tarefa). Usuário de teste temporário e o
`ItemProjeto` de teste criado durante a validação foram removidos ao
final (`forceDelete()` no usuário; o item de teste foi o mesmo excluído
como parte do próprio checklist de "Excluir").

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

### "Criar Itens" do Promob — geração real dos Itens + Notas de cálculo (2026-09-06)

O botão "Criar Itens" (até aqui um stub — só checava divergência e
mostrava notificação placeholder) passou a criar de verdade os
`ItemProjeto`/`NotaProjeto` a partir dos XMLs processados. Depende da
tabela `notas_projeto` (com `item_projeto_id`) já existir — ver "Notas
do Projeto" logo abaixo.

**Parte 0 — campos da Referência de Preços**: dos 9 campos confirmados
pela fórmula abaixo, **8 já existiam** (`laminacao`, `corte`,
`hora_producao`, `hora_execucao`, `valor_pecas`, `fator_madeiras`,
`fator_ferragens_miscelanias`, `fator_mao_obra` — migrations de
2026-08-30). **Só `fator_acabamento_corte` precisou ser criado**
(migration `2026_09_06_100000_add_fator_acabamento_corte_to_referencias_precos_table`,
`decimal(5,2)` nullable, campo adicionado ao form/tabela do
`ReferenciaPrecoResource` entre Fator Ferragens/Miscelânea e Fator Mão
de Obra). **Atenção pro usuário revisar a Referência "Padrão" (id 8)
já cadastrada**: `fator_acabamento_corte` ficou `NULL` (campo novo,
sem valor ainda — o form marca como `->required()`, então a PRÓXIMA
edição vai pedir esse valor) e `fator_mao_obra` está `0.00` hoje — com
esses dois em zero, `AcabamentoCorte`/`MãoDeObra` (ver fórmula) saem
zerados em qualquer "Criar Itens" rodado ANTES de alguém preencher
esses valores de verdade.

**Parte 1 — numeração**: o código de 3 dígitos no NOME do arquivo XML
(`001`, `002`...) é usado APENAS pra ORDEM de processamento
(`PromobChecagemTotal::ordenarNomesDeArquivosDeItens()`, ordena os
nomes — exceto o "000" — pelo código numérico) — o `numero_item` real
gravado em `itens_projeto` continua vindo do MESMO mecanismo já
existente (`ItemProjeto::boot()`, maior número já usado no Projeto +
1). Confirmado por teste: um Projeto já com itens `001`/`002` (Item
Avulso) recebeu `003`/`004` pros dois itens do Promob, mesmo os XMLs
se chamando "001"/"002" — nunca reaproveita o código do nome do
arquivo.

**Fórmula de Custo Unitário** (`ProjetoResource::calcularDetalhamentoCustoPromob()`,
aplicada por ITEM — usa `PromobXmlParser::metricas()` daquele XML
individual, não o agregado do Projeto):

```
Madeira              = Tot.Custo(item) × FatorMadeira
FerragensMiscelanea  = Tot.Misc(item) × FatorFerragensMiscelanea
Laminacao            = Tot.MLinear(item) × ValorLaminacao
Corte                = Tot.MLinear(item) × ValorCorte
PecasDoItem          = Tot.Peças(item) × ValorPorPeca
AcabamentoCorte      = (Laminacao + Corte + PecasDoItem) × FatorAcabamentoCorte
MaoObraProducao      = Tot.m²(item) × ValorHoraProducao
MaoObraExecucao      = Tot.m²(item) × ValorHoraExecucao
MaoDeObra            = (MaoObraProducao + MaoObraExecucao) × FatorMaoDeObra
CustoUnitario        = Madeira + FerragensMiscelanea + AcabamentoCorte + MaoDeObra
```

Os "Fatores" são percentuais gravados como número cru (`200.00` =
200%) — cada um entra na fórmula `/100` antes de multiplicar (`×2.0`,
não `×200`). Valor Unitário/Total reaproveitam a MESMA fórmula/função
pura já usada no Item Avulso (`calcularValoresItemAvulso()`, sem
duplicar). **Confirmado por teste** com os 3 XMLs de exemplo e uma
Referência com todos os 9 campos preenchidos (Madeira 200%, Ferragens/
Misc. 120%, Acabamento/Corte 120%, Mão de Obra 100%, Laminação
R$3,50/m, Corte R$2,50/m, Peça R$1,00, Hora Produção/Execução
R$10,00/m²): Custo Unitário do item "Superior" bateu com o cálculo
manual passo a passo (R$ 619,24) e do "Inferior" também (R$ 1.474,13).

**Trava: exige Referência de Preços vinculada ao Projeto** — sem
`referencia_preco_id`, TODO Fator/Valor da fórmula seria `0`,
produzindo Itens com Custo Unitário zerado silenciosamente (diferente
do Imposto do Item Avulso, que degrada pra 0% sem bloquear — aqui
zerar o custo inteiro seria enganoso). `criarTodosItensPromob()`
bloqueia com notificação clara antes de criar qualquer coisa.

**Fluxo — SEM modal por item, tudo automático (versão atual,
2026-09-06, substituiu a versão original no MESMO dia — ver "Histórico:
versão original com modal por item" mais abaixo pro porquê)**:

1. `criarItensPromob` (já existente) continua desabilitado até "Checar
   Total" rodar com sucesso pelo menos uma vez nesta sessão do modal
   (`HasPromobResultado::$promobChecagemFeitaComSucesso`, resetado em
   `mountUsing()` de `inserirItemPromob`).
2. Ao clicar (confirmado ou direto, se sem divergência — mesma regra
   de sempre: qualquer uma das 5 métricas de diferença != 0 pede
   confirmação, `promobPrecisaConfirmarCriacao()`):
   `ProjetoResource::criarTodosItensPromob()` roda, numa ÚNICA
   `DB::transaction()`:
   - Cria a Nota GERAL de checagem (`tipo_sistema = true`,
     `item_projeto_id = null`, texto = nome do arquivo "000" +
     `DATE`/`HOUR` do PRÓPRIO XML + tabela HTML compacta das 5
     métricas de diferença).
   - Relê a Referência de Preços com `lockForUpdate()` UMA VEZ pro
     lote inteiro (não item a item — não há mais interação do usuário
     no meio do processo que pudesse deixar o valor ficar obsoleto
     entre um item e o próximo).
   - Para CADA XML de item (ordem de
     `PromobChecagemTotal::ordenarNomesDeArquivosDeItens()`): calcula
     o detalhamento (`calcularDetalhamentoCustoPromob()`), cria o
     `ItemProjeto` (`origem = promob`, Descrição = texto do nome do
     arquivo via `PromobChecagemTotal::descricaoDoArquivo()`,
     Quantidade = `1`, % = `0` — DEFAULTS, sem modal de revisão) + a
     `NotaProjeto` de cálculo vinculada (`item_projeto_id`, mesmo
     resumo HTML de sempre).
   - Se QUALQUER coisa falhar no meio (XML corrompido, etc.), a
     transação inteira desfaz — diferente da versão anterior (que
     commitava item por item), aqui não sobra mais Item "parcial"
     criado se algo falhar no meio, já que não há mais motivo pra isso
     (sem interação do usuário a preservar entre um item e outro).
3. Ao final: `recarregarItens()` (mesmo mecanismo do Item Avulso) +
   notificação de sucesso com quantos Itens foram criados.
4. **Ajustes finos ficam pro fluxo de edição já existente**: se algum
   Item precisar de correção (Descrição, Quantidade, %, Custo
   Unitário), o usuário edita DEPOIS, 1 item por vez, pelo ícone de
   lápis já existente na listagem (`editarItemAvulso{id}` — mesmo
   modal usado por Item Avulso, funciona pra qualquer origem, já
   validado e sem nenhum dos bugs do mecanismo antigo).

**Coluna "Referência" (2026-09-06, nova; conteúdo REVISADO no mesmo
dia)** — `itens_projeto.referencia` (migration
`2026_09_06_110000_add_referencia_to_itens_projeto_table`, `string`
nullable), reservada desde a criação da tabela pro futuro "Item de
Linha" mas nunca usada até agora (a listagem já tinha essa coluna no
grid, sempre vazia pra Item Avulso — `linhaExibicaoItem()`).

Versão original do mesmo dia gravava aqui o nome do arquivo XML + data/
hora do próprio XML (`"{nome} — {data} {hora}"`). O usuário reconsiderou
logo em seguida: essa informação já fica disponível, com mais detalhe,
na `NotaProjeto` de cálculo vinculada a cada item (ícone "Cálculos"), e
duplicá-la também na coluna encheria a base à toa sem necessidade —
mesmo com `NULL` custando essencialmente nada em armazenamento, o
problema aqui era volume de dado repetido, não custo de disco.

**Versão atual**: a coluna guarda só um LABEL curto de ORIGEM, igual
pras duas origens existentes:
- Item criado via Promob (`criarTodosItensPromob()`) →
  `referencia = "Promob"`.
- Item criado como Item Avulso (`salvarItemAvulso()`, só no `create()`,
  nunca no `update()` — ver "Achado real: `origem`/`referencia`
  reescritos na edição" abaixo) → `referencia = "Item Avulso"`.

Serve só pra identificar de relance a origem na listagem, sem duplicar
detalhe que já mora em outro lugar por item (Promob: ícone "Cálculos";
Item Avulso: a própria Descrição digitada pelo usuário). **Design
futuro (ainda não implementado)**: quando "Item de Linha" existir, essa
MESMA coluna passa a mostrar o código de referência REAL do Produto
vinculado — não mais um label estático — "e o mesmo depois" quando
SketchUp for implementado.

**Achado real: `origem`/`referencia`/Custo Unitário na edição
compartilhada (2026-09-06)** — `editarItemAvulso{id}`/
`camposFormularioItemAvulso()`/`salvarItemAvulso()` são o mesmo modal e
o mesmo método de gravação usados pra editar Item de QUALQUER origem
(apesar do nome, ver item 4 do fluxo acima), não só Item Avulso. Duas
correções feitas quando o usuário pediu pra verificar o comportamento
de edição de item Promob:

1. **`origem`/`referencia` reescritos silenciosamente** — antes desta
   correção, `salvarItemAvulso()` gravava `origem = OrigemItemProjeto::ItemAvulso`
   incondicionalmente em TODO `update()`, inclusive editando um item
   Promob: o item virava "Item Avulso" pro resto do sistema (perdendo,
   por exemplo, o rótulo "Promob" da coluna Referência e o critério que
   mostra o ícone "Cálculos", que depende só de ter Nota de sistema
   vinculada — esse não quebrava, mas a origem em si já estava errada).
   Corrigido: `origem`/`referencia` só são gravados no `create()` (item
   novo = sempre Item Avulso); no `update()`, os dois campos ficam como
   já estavam no registro, nunca reescritos.
2. **Custo Unitário editável em item Promob** — o campo não tinha
   nenhuma trava por origem; um item criado via importação (custo
   CALCULADO por `calcularDetalhamentoCustoPromob()`, não digitado)
   podia ser alterado livremente pelo mesmo modal genérico de edição,
   destruindo a rastreabilidade do cálculo sem reprocessar o XML.
   Corrigido com um campo `Hidden::make('origem_atual')` novo
   (preenchido em `preencherFormularioItemAvulso()` com
   `$item?->origem?->value`, só CACHE de exibição/controle de UI) +
   `->disabled()`/`->dehydrated(false)` condicionados a esse valor no
   `TextInput::make('custo_unitario')`. Dupla proteção no backend
   (`salvarItemAvulso()`): quando o item sendo editado é de origem
   Promob, o Custo Unitário nem é lido de `$data` (pode nem chegar lá,
   já que o campo desabilitado não é dehydratado) — o valor já gravado
   no registro é reaproveitado pro recálculo de Valor Unitário/Total,
   então mesmo um POST manipulado no DOM não teria efeito.

**Ícone "Cálculos" (Parte 5)** — `ProjetoResource::acaoVerCalculosItem()`,
na `ActionGroup` de cada linha de `linhaExibicaoItem()` (junto de
Editar/Excluir), só aparece quando `$item->notas()->where('tipo_sistema',
true)->exists()` — ou seja, Itens criados via Promob (sempre ganham
uma Nota de sistema vinculada); Item Avulso nunca tem nota vinculada,
então nunca mostra o ícone (confirmado por teste). Abre um modal
SOMENTE LEITURA (`linhaExibicaoNotaSomenteLeitura()`, mesmo cabeçalho
número/autor/data/badge "Sistema" de `linhaExibicaoNota()` do modal
geral de Notas, sem coluna de ações) — nunca editar/excluir por aqui,
mesmo pro super usuário (a edição de nota de sistema, quando permitida,
é só pelo modal geral de Notas do Projeto).

**Estilo visual do resumo/tabela** (texto das duas Notas de sistema) —
`style=` INLINE (`font-size:11px`, bordas leves `#e5e7eb`/`#d1d5db`,
`text-align:right` nas colunas numéricas, SEM fundo colorido por
linha), nunca classes Tailwind arbitrárias tipo `text-xs` — mesmo
motivo já documentado em "FilamentAsset::register()" no CLAUDE.md da
raiz: o painel usa CSS pré-compilado do Filament, uma classe que o
próprio Filament não usa em lugar nenhum simplesmente não tem efeito.
Inline style funciona em QUALQUER lugar que renderize esse HTML —
dentro do modal geral de Notas (`Html::make(new HtmlString($nota->texto))`)
e dentro do modal "Cálculos", sem depender de nenhum CSS externo.

**Histórico: versão original com modal por item, substituída no MESMO
dia (2026-09-06)** — a primeira versão desta tarefa abria um Form
Modal por item (Referência SOMENTE LEITURA/Descrição/Quantidade/%
editáveis ANTES de confirmar CADA item, com o modal do próximo item se
auto-abrindo sozinho depois de cada "Criar", via
`$livewire->mountAction('processarItemPromob', ...)` chamado de DENTRO
do `->action()` da Action anterior). Esse mecanismo causou uma
sequência de bugs reais, todos achados testando pelo navegador de
verdade (a suíte via `Livewire::test()`/tinker nunca pegou nenhum):

1. **Índice fixo de `schemaComponent`** — as chamadas de
   `mountAction()` assumiam `inserirItemPromob` sempre na posição `0`
   da pilha de `$livewire->mountedActions`; qualquer cancelamento
   anterior do modal Promob na mesma sessão do navegador deixava uma
   entrada fantasma pra trás, empurrando a posição real pra um índice
   MAIOR — usar `mountedActionSchema0` de qualquer jeito resolvia
   contra o Schema ERRADO. Sintomas: "Referência" vazia e
   `Undefined array key "descricao"` ao confirmar um item. Corrigido
   recalculando o índice dinamicamente
   (`indiceMountedActionInserirItemPromob()`, a mesma função já usada
   por `arquivosXmlPromobAtuais()`).
2. **Campos "atrasados" um item** — mesmo com o índice corrigido, os
   campos Descrição/Custo Unitário/Valor Unitário/Valor Total do modal
   do item N mostravam os valores do item N-1 (só "Referência" e o
   resumo de cálculo, recalculados via Closure a cada render, sempre
   corretos). O `$data` usado na GRAVAÇÃO saía correto quando o
   usuário não mexia em nada (confirmado testando), então o problema
   era só de EXIBIÇÃO. Uma tentativa de correção (`->key()` dinâmico
   por campo, forçando o Livewire a tratar cada input como um elemento
   novo) NÃO resolveu o atraso visual e AINDA introduziu uma regressão
   nova (editar a Descrição ANTES de "Criar" voltava a disparar o
   MESMO erro do bug 1) — revertida sem causa raiz confirmada.

**Decisão do usuário**: em vez de continuar investigando o bug 2 (que
exigiria inspecionar a resposta de rede real do Livewire, sem acesso a
essa ferramenta nesta sessão), o usuário preferiu simplificar o fluxo
inteiro — criar todos os Itens automaticamente (mesma fórmula/regras,
sem modal de revisão por item) e resolver qualquer ajuste fino DEPOIS
pelo fluxo de edição já existente. Isso eliminou a CLASSE INTEIRA de
bugs do mecanismo antigo (nenhuma Action é mais montada de dentro do
`->action()` de outra, nenhum `mountAction()` com `schemaComponent`
dinâmico) e removeu boa parte do código: `acaoProcessarItemPromob()`,
`camposFormularioItemPromob()`, `preencherFormularioItemPromob()`,
`itemAtualDaFilaPromob()`, `tituloModalItemPromob()`,
`salvarItemPromobEAvancar()`, `encerrarFilaItensPromob()`,
`cancelarFilaItensPromob()`, `mountarProximoItemPromob()`, e as
properties de fila de `HasPromobResultado`
(`$promobFilaNomesArquivos`/`$promobFilaIndiceAtual`/
`$promobFilaTotalCriados`) — nenhuma delas tem mais uso.
`indiceMountedActionInserirItemPromob()` permanece (ainda usada por
`arquivosXmlPromobAtuais()`, independente do mecanismo removido).

**Validação (2026-09-06)**: sem `Livewire::test()` via `TestCase` real
(ver CLAUDE.md da raiz sobre `TEST_TOKEN`), mas com `Livewire::test()`
em `artisan tinker`, upload simulado via `Illuminate\Http\UploadedFile
::fake()->createWithContent()` contra os 3 XMLs de exemplo reais e uma
Referência de Preços de TESTE (removida ao final, nunca a real).
Confirmado ponta a ponta pelo pipeline de verdade: sem divergência,
"Criar Itens" segue direto (sem pedir confirmação); Nota geral criada
ANTES de qualquer item; os 2 itens processados na ORDEM certa (Superior
antes de Inferior, batendo com a ordem numérica do nome do arquivo),
cada um com `referencia` gravada corretamente (nome do arquivo + data/
hora); `numero_item` gerado pelo sistema (`003`/`004`, não `001`/`002`
do nome do arquivo, num Projeto que já tinha 2 Itens Avulsos); Custo
Unitário/Valor Unitário/Total batendo com o cálculo manual;
`ItemProjeto::notas()` retornando a Nota certa por item; ícone
"Cálculos" presente só nos 2 itens do Promob, ausente nos 2 Itens
Avulsos pré-existentes; cenário de divergência (removendo um XML de
item) exigindo confirmação antes de criar; bloqueio sem Referência de
Preços vinculada. **Não testado nesta tarefa**: verificação VISUAL no
navegador (sem ferramenta de browser disponível nesta sessão) —
cobertura funcional completa via o pipeline real do Livewire, não a
aparência/layout renderizado de fato; o usuário testa e confirma pelo
navegador antes de considerar concluído. Todo dado de teste (Referência
de Preços temporária, Itens/Notas de Promob criados durante a
validação) foi removido ao final; a Referência "Padrão" (id 8) e os
Itens Avulsos pré-existentes do Projeto de teste não foram tocados.

### Notas do Projeto: tabela `notas_projeto`, regra de 24h e Actions aninhadas em modal (2026-09-05)

Histórico de notas/anotações por Projeto — ícone (`heroicon-o-document-text`)
no canto direito do título da Section "Cabeçalho" (`Section::headerActions()`,
`->visible()` só com o Projeto já salvo, mesmo critério de "Atribuir
Processos"), abrindo um modal com a lista de notas + campo de nova nota.

**Model/migrations** — `Perseu\Comercial\Models\NotaProjeto`, tabela
`notas_projeto` (migration `2026_09_05_170000_create_notas_projeto_table`
+ `2026_09_05_180000_add_item_projeto_id_to_notas_projeto_table` — a
segunda ALTERA a tabela já criada pela primeira, nunca editando a
migration antiga, ver "Comandos e fluxo úteis" no CLAUDE.md da raiz —,
ambas adicionadas ao array `->hasMigrations([...])` de
`ComercialServiceProvider`, mesmo alerta já documentado pra
`itens_projeto`). `LogsBusinessActivity`, registrada em
`SubjectTypeCatalog` (rótulo "Nota de Projeto", busca por
`texto`/`numero_nota`, referência `"#{numero_nota} — {texto truncado}"`) —
sem `TrashCatalog` (sem `SoftDeletes`, sem Lixeira própria, mesma
divergência de `ItemProjeto`). Sem Resource/Policy própria — vive 100%
dentro do form de `ProjetoResource`, gated pela `ProjetoPolicy::update`
já existente, mesma decisão de `ItemProjeto` (por isso também ausente de
`config/filament-shield.php`).

- **`usuario_id`**: FK nullable pra `users`, `nullOnDelete()` (não
  `cascadeOnDelete()` — perder o usuário autor não deveria apagar o
  HISTÓRICO da nota). **Decisão registrada**: uma nota de USUÁRIO
  sempre grava `auth()->id()` no momento da criação (nunca nulo na
  prática); a coluna é nullable só pra sobreviver a uma exclusão futura
  do usuário autor e pra uma nota de SISTEMA que não tenha um usuário
  claro por trás da ação que a gerou. A geração automática de notas de
  sistema em si (ex.: "Item X foi excluído") é uma tarefa FUTURA — esta
  tarefa só preparou `tipo_sistema` e a regra de que, se existirem,
  nunca são editáveis/excluíveis pela tela (pela UI de usuário comum —
  ver "Dono da nota + Super usuário" abaixo pra exceção).
- **`item_projeto_id`** (2026-09-05, segunda tarefa): FK nullable pra
  `itens_projeto`, `nullOnDelete()` (mesmo motivo de `usuario_id` —
  excluir o Item não deveria apagar o HISTÓRICO da nota, só o vínculo).
  Vincula uma nota a um Item específico do Projeto — base do futuro
  ícone "Cálculos" no menu de cada item (`ItemProjeto::notas()`/
  `NotaProjeto::item()`), que mostrará as notas de SISTEMA vinculadas
  àquele item; a geração automática dessas notas em si é de uma tarefa
  futura (junto da criação de Itens via Promob), esta tarefa só
  preparou a COLUNA e a relação. `NULL` cobre os 3 casos possíveis
  (nota de usuário; nota de sistema sobre o Projeto como um todo; nota
  de sistema sobre um item específico é o único caso com valor) — sem
  precisar de nenhum valor sentinela tipo "item 0".
- **`numero_nota`**: `unsignedInteger`, único por `projeto_id`
  (`unique(['projeto_id', 'numero_nota'])`), gerado em
  `NotaProjeto::boot()` (`creating`) com o MESMO critério de
  `ItemProjeto::numero_item` (`MAX()` daquele Projeto + 1, começando em
  1) — mas **SEM renumeração ao excluir** (diferente de `ItemProjeto`):
  excluir a nota `1` de `1`/`2`/`3` deixa `2`/`3` como estão, e a
  próxima nota criada vira `4` (não reaproveita o `1` livre). Decisão
  explícita da tarefa: aqui o número é só um identificador sequencial
  de cada nota ao longo do tempo (não uma lista visível ao
  cliente/proposta como os itens), então preservar a IDENTIDADE de cada
  nota já existente importa mais que manter a sequência contígua.
  Confirmado por teste (ver "Validação" abaixo): excluir a nota `1`
  mantém `numero_nota` da nota `2` inalterado, e a nota seguinte criada
  recebe `3` (não `1`).
- **SEM `SoftDeletes`** — divergência deliberada, mas por um motivo
  DIFERENTE do de `ItemProjeto` (que precisa do slot numérico
  realmente livre pra renumeração, que aqui não existe): uma nota de
  USUÁRIO excluída dentro do prazo de 24h não precisa de Lixeira
  própria, e uma nota de SISTEMA nunca é excluída pela UI
  (`tipo_sistema` já garante isso sozinho, sem precisar de
  `SoftDeletes` como segunda trava).

**Regra de prazo de 24 horas + dono + super usuário** (`NotaProjeto
::podeSerEditadaPor(User $usuario)`/`podeSerExcluidaPor(User $usuario)`,
hoje idênticos — mantidos como dois métodos separados porque a tarefa
previu que possam divergir no futuro, mesmo sem necessidade concreta
ainda; ambos delegam pra `ehSuperUsuario()`/`dentroDoPrazoDeEdicao()`):

1. Nota de USUÁRIO (`tipo_sistema = false`): editável/excluível pela
   tela só dentro de 24h a partir de `created_at`
   (`created_at->addHours(24)->isFuture()`) **e só pelo PRÓPRIO autor**
   (`usuario_id === $usuario->id`) — outro usuário comum, mesmo dentro
   do prazo, não pode mexer na nota de alguém diferente (2026-09-05,
   segunda tarefa; a primeira versão desta regra checava só o prazo,
   sem checar o dono). Depois das 24h (ou pra qualquer usuário que não
   seja o autor), permanente pra usuário comum — nenhum ícone de
   editar/excluir aparece.
2. Nota de SISTEMA (`tipo_sistema = true`): NUNCA editável/excluível
   por um usuário comum, independente do prazo.
3. **Super usuário — Role `Admin` (guard `web`)**: pode editar (só o
   TEXTO) ou excluir QUALQUER nota, de qualquer autor, mesmo fora do
   prazo e mesmo de sistema — usado pra testes/manutenção.
   `ehSuperUsuario()` checa ISSO especificamente porque foi o que a
   investigação desta tarefa confirmou ser, na prática, a role de
   privilégio total do sistema hoje (556 permissões atribuídas — ver
   docblock do método pro porquê NÃO é a role `Sistema`/guard `sanctum`
   nem o mecanismo `config('filament-shield.super_admin.*')`, ambos
   propósitos/nomes diferentes e sem relação com isto).
4. **Dupla validação, nunca só esconder o botão**: `linhaExibicaoNota()`
   lê `auth()->user()` e usa `podeSerEditadaPor()`/`podeSerExcluidaPor()`
   pra decidir se o `ActionGroup` (editar/excluir) aparece, MAS
   `salvarEdicaoNota()`/`excluirNotaProjeto()` releem a nota FRESCA do
   banco (`NotaProjeto::find($nota->id)`, não o `$nota` fechado no
   Closure da listagem) e checam de novo o USUÁRIO ATUAL antes de
   gravar/excluir — mesmo cuidado já validado por
   `salvarItemAvulso()`/Imposto obsoleto (nunca confiar em dado lido
   antes do clique de fato acontecer). Também estruturalmente
   reforçado: como o `ActionGroup` só é CONSTRUÍDO quando as duas
   checagens são verdadeiras, uma nota que o usuário logado não pode
   mexer nem tem a Action `editarNota{id}`/`excluirNota{id}`
   REGISTRADA na árvore do Schema — não é só um botão escondido por
   CSS, a Action simplesmente não existe pra ser encontrada/chamada
   (confirmado por teste: um segundo usuário tentando montar a Action
   de edição da nota de outro usuário nem consegue, `mountedActions`
   fica só com o modal pai).

**`created_at` avança quando o PRÓPRIO autor edita dentro do prazo, mas
NUNCA quando é o super usuário fazendo manutenção** (`salvarEdicaoNota()`):

- Autor editando a própria nota, dentro das 24h, com o texto de fato
  mudando: `created_at = now()` — "reinicia" a janela de edição.
  **Isso NÃO reordena a listagem** (ordenada por `numero_nota`, que não
  muda numa edição) — a nota mantém sua posição/número, só a data/hora
  exibida avança. Confirmado como o comportamento mais simples e sem
  problema aparente: `numero_nota` já garante uma posição estável
  independente de `created_at`, então não existe nenhum efeito colateral
  de reordenação pra tratar.
- Super usuário editando (nota de outro autor, nota já fora do prazo,
  ou nota de sistema): `created_at` NUNCA muda — decisão explícita, pra
  não forjar uma data de criação falsa num registro que é edição de
  MANUTENÇÃO, não uma nota nova.
- **Caso de borda decidido**: se o PRÓPRIO super usuário edita uma nota
  SUA, recém-criada (dentro do prazo), `created_at` avança do mesmo
  jeito — a regra real que decide é `usuario_id === $usuario->id &&
  dentroDoPrazoDeEdicao()` (dono + prazo), não "é super usuário sim/
  não"; um super usuário mexendo na PRÓPRIA nota recente é uma edição
  normal como qualquer usuário, não uma manutenção de terceiro.
- Nunca escreve `usuario_id`/`numero_nota`/`tipo_sistema` — o form deste
  modal (`acaoEditarNota()`) só expõe o `RichEditor` de texto, então
  mesmo um super usuário fisicamente não tem como alterar esses campos
  por essa tela, confirmado por teste (passo 9 da "Validação" abaixo).
  `forceFill()` (não `update()`) pra gravar `texto`+`created_at` juntos
  — `created_at` fica fora do `$fillable` (gerido pelo Eloquent), então
  `update(['created_at' => ...])` seria ignorado silenciosamente, mesmo
  achado já documentado pra `numero_item`.

**Conteúdo do modal** (`camposModalNotasProjeto()`): lista das notas
(`Group::make()->schema(fn () => $record->notas()->orderByDesc('numero_nota')
->get()->map(...)->all())`, mais recente primeiro, lida DIRETO do banco
a cada avaliação — sem property de cache como `EditProjeto
::$itensCarregados`, porque este Group vive dentro do Schema PRÓPRIO da
Action `notasProjeto` (construído só quando o modal monta via
`mountAction()`, não no `mount()` da página) — o achado de timing que
motivou `$itensCarregados` (Schema avaliado cedo demais por
`fillForm()` durante o `mount()` da página) não se aplica aqui) + campo
`RichEditor::make('nova_nota')` (toolbar DEFAULT completa, mesmo padrão
de Item Avulso) + Action FLAT `adicionarNota` (sem `->form()` próprio,
`Get`/`Set` resolvem pro MESMO Schema de `nova_nota` por serem
declaradas como IRMÃS no mesmo array, igual a `inserirItem`/
`mobilizacaoFrete` lendo `origem_item_selecionada`). `notasProjeto`
usa `->modalSubmitAction(false)` — é só um CONTAINER, toda interação
real acontece via `adicionarNota`/`editarNota{id}`/`excluirNota{id}`;
fecha só pelo "Cancelar" nativo.

Cada nota (`linhaExibicaoNota()`): número/autor/data-hora (+ badge
"Sistema" via `Text::make(...)->badge()` quando `tipo_sistema`) numa
linha (`Flex` + `Grid(12)`), texto renderizado como HTML DE VERDADE via
`Filament\Schemas\Components\Html::make(new HtmlString($nota->texto))`
— diferente da listagem de Item Avulso (texto puro, `Str::stripTags()`)
porque aqui não há a mesma restrição de altura de uma grid de planilha.
Editar (`acaoEditarNota()`, Action com `->form()` próprio, mesmo padrão
de `editarItemAvulso{id}`) e Excluir (`acaoExcluirNota()`, `DeleteAction
->record($nota)`, confirmação nativa) só aparecem quando permitido —
`ActionGroup` (dropdown), não dois ícones lado a lado, mesmo critério
de `linhaExibicaoItem()`.

**Achado real confirmado por teste (2026-09-05): `schemaComponent`
correto pra montar uma Action ANINHADA dentro de outra Action já
montada não é o nome do schema onde a Action-PAI foi DECLARADA, e sim
`"mountedActionSchema{indice}"` (o schema DEDICADO que a Action-pai já
montada recebeu).** `Filament\Actions\Concerns\InteractsWithActions
::resolveSchemaComponentAction()` faz `$schema = $this->getSchema($schemaName);
$schema->getAction($nome, ...)` — ou seja, o `schemaComponent` informado
tem que apontar pro Schema que CONTÉM a Action procurada como
descendente, não pro Schema de onde a Action-PAI foi originalmente
declarada. Pra `editarItemAvulso{id}` (Item Avulso, um nível só —
declarada direto no Schema `form` da página), `schemaComponent: 'form'`
funciona porque a Action-pai (a página) e a Action procurada vivem no
MESMO Schema. Mas `adicionarNota`/`editarNota{id}`/`excluirNota{id}`
(Notas do Projeto) vivem dentro do `->form()` PRÓPRIO da Action
`notasProjeto` já montada — um Schema SEPARADO, cacheado por
`InteractsWithActions::getMountedActionSchema()` sob a chave
`"mountedActionSchema{$indiceDeAninhamento}"` (não anexado como filho do
Schema `form` da página) — `schemaComponent: 'form'` NÃO encontra essas
3 Actions (`ActionNotResolvableException`, silenciosamente engolida por
`mountAction()`, que só desmonta e retorna `null` sem erro visível).
Corrigido usando `schemaComponent: 'mountedActionSchema0'` (índice `0`
= posição de `notasProjeto` na pilha `mountedActions` no momento do
teste) pra montar as 3 Actions aninhadas — depois de montada,
`editarNota{id}`/`excluirNota{id}` passam a ser elas mesmas o TOPO da
pilha (índice 1), e `callMountedAction()` (sem nenhum `context`) já as
alcança normalmente, sem precisar desse cálculo de novo. **Isso é uma
particularidade de TESTE (`Livewire::test()->call('mountAction', ...)`
simulando manualmente o clique)** — no navegador de verdade, o Blade/JS
do Filament já calcula e embute o `schemaComponent` certo sozinho no
`wire:click` de qualquer Action aninhada, então nenhuma mudança de
código foi necessária, só o entendimento de COMO testar esse cenário
via `Livewire::test()`. Vale para qualquer Action aninhada em modal
futura, aqui ou em outro plugin: o índice muda conforme a posição na
pilha de `mountedActions` no momento do teste, não é sempre `0`.

**Validação (2026-09-05)**: sem `Livewire::test()` via `TestCase` real
(ver "Comandos e fluxo úteis" no CLAUDE.md da raiz sobre `TEST_TOKEN`),
mas com `Livewire::test()` rodado em `artisan tinker` contra o registro
de um Projeto real — fluxo completo confirmado ponta a ponta pelo
PIPELINE de verdade (`->call('mountAction', ...)`/`->call('callMountedAction')`,
não bypass): abrir o modal; adicionar uma nota vazia bloqueado (nada
criado); adicionar uma nota de verdade (contador de notas +1, `Get`
lendo o HTML corretamente, `nova_nota` resetado pro estado vazio do
RichEditor depois); abrir a edição dessa nota pré-preenchida com o
texto atual; salvar a edição persiste o novo texto e desmonta de volta
pro modal pai; excluir a nota remove o registro E não redireciona pra
`ListProjetos` (fix em `EditProjeto::getDefaultActionSuccessRedirectUrl()`,
ver abaixo). Separadamente, via Eloquent puro: duas notas sequenciais
(`1`/`2`); excluir a `1` mantém `2` inalterada; a próxima nota criada
recebe `3` (sem reaproveitar `1`); uma nota com `created_at` forçado
pra 25h atrás retorna `false` pro próprio autor (método named na época
`podeEditar()`/`podeExcluir()`, renomeados pra `podeSerEditadaPor()`/
`podeSerExcluidaPor()` na segunda tarefa — ver abaixo); uma nota
`tipo_sistema = true` retorna `false` pra usuário comum mesmo recém-
criada; uma releitura fresca (`NotaProjeto::find()`) confirma que o
bloqueio de backend funcionaria mesmo se alguém forçasse a Action
depois do prazo.

**Validação da regra de dono + super usuário (2026-09-05, segunda
tarefa)** — dois usuários comuns temporários criados via tinker
(`is_active = true`, mesma `default_company_id`/`allowedCompanies()`
do admin de teste, + as 3 permissões Shield de Projeto atribuídas
direto via `givePermissionTo()`, SEM role — precisou desse cuidado
extra porque um usuário sem `is_active`/empresa/permissão nenhuma
sequer consegue passar pelo `canAccessPanel()`/Policy da tela, o que
antes quebrava o próprio `Livewire::test()` com um erro genérico de
snapshot, não um erro de permissão claro) e o usuário real "zemane"
(role `Admin`) como super usuário. Confirmado ponta a ponta pelo
PIPELINE de verdade: usuário dono edita a própria nota dentro do prazo
→ sucesso, `created_at` avança; um SEGUNDO usuário comum tenta editar
essa mesma nota → nem consegue montar a Action (`mountedActions` fica
só com o modal pai, texto permanece o do dono); super usuário edita
essa mesma nota (de outro autor) → sucesso, texto muda, `created_at`
**não** muda, e `usuario_id`/`numero_nota`/`tipo_sistema` permanecem
intactos; super usuário exclui a nota → sucesso, sem redirecionar pra
`ListProjetos`. Separadamente, via Eloquent puro (`podeSerEditadaPor()`
direto): dono dentro do prazo `true`; outro usuário comum `false`;
super usuário `true`; dono de uma nota com `created_at` forçado pra 25h
atrás `false`; super usuário na mesma nota antiga `true`; usuário comum
numa nota de sistema `false`; super usuário numa nota de sistema
`true`. **Não testado nesta tarefa**: verificação VISUAL no navegador
(sem ferramenta de browser disponível nesta sessão) — a validação
cobriu o comportamento funcional completo via o pipeline real do
Livewire, mas não a aparência/layout renderizado de fato.

`EditProjeto::getDefaultActionSuccessRedirectUrl()` ganhou a MESMA
checagem já usada pra `ItemProjeto` (ver "Excluir Item redirecionava
pra ListProjetos" acima), agora cobrindo `NotaProjeto` também —
`excluirNota{id}` é um `DeleteAction::make(...)->record($nota)`
aninhado (nível 2 da pilha, dentro do modal "Notas do Projeto"), e sem
essa checagem o mesmo bug reapareceria: qualquer `DeleteAction`/
`ForceDeleteAction` bem-sucedido redireciona pra `ListProjetos` por
padrão, verificando só a CLASSE da Action, não qual registro ela
excluiu de fato.

### Fluxo Promob: 3 ajustes finos (2026-09-06)

Três pequenos ajustes no fluxo de "Criar Itens" do Promob, pedidos via
handoff depois da tarefa de geração real dos Itens (ver "'Criar Itens'
do Promob — geração real dos Itens + Notas de cálculo" acima):

1. **Excluir a Nota de cálculo junto com o Item** —
   `ProjetoResource::excluirItemAvulso()` agora chama `$item->notas()
   ->delete()` ANTES de `$item->delete()`, dentro da mesma
   `DB::transaction()` já existente. `$item->notas()` (relação
   `HasMany` de `ItemProjeto`, filtrada por `item_projeto_id`) só
   pega a(s) nota(s) de cálculo VINCULADAS a este item específico —
   nunca a Nota GERAL do Projeto (`item_projeto_id = null`), que
   continua intacta. Sem isso, a nota de cálculo virava órfã (row
   ainda existente com `item_projeto_id` apontando pra um item já
   excluído).
2. **Confirmação de divergência restrita ao super usuário** — nova
   regra: havendo qualquer uma das 5 métricas de diferença `!= 0`, só
   o super usuário (mesmo critério de `NotaProjeto::ehSuperUsuario()`,
   role `Admin`/guard `web`) pode confirmar e seguir com a criação.
   Qualquer outro usuário tem a criação BLOQUEADA (não apenas pede
   confirmação) — motivo, nas palavras do usuário: "Mantenho o
   funcionamento diferente pro superusuário pra algum caso muito
   atípico e evitar travar todo o processo"; o padrão esperado é
   diferença = 0, e permitir seguir com diferença é uma via de exceção
   só pro super usuário. Implementado em duas camadas (nunca confiar
   só em esconder um botão/modal na tela, mesmo padrão já usado pelas
   Notas):
   - `ProjetoResource::promobUsuarioPodeConfirmarDivergencia()`
     (método novo) — `(new NotaProjeto())->ehSuperUsuario(auth()
     ->user())`, mesmo padrão de instanciar `NotaProjeto()` só pra
     chamar um método que não depende de nenhum atributo da nota em
     si, já usado por `podeSerEditadaPor()`/`podeSerExcluidaPor()`
     neste mesmo Resource.
   - `promobPrecisaConfirmarCriacao()` (existente) agora retorna
     `false` direto (sem nem calcular o resultado do Promob) quando o
     usuário atual não é super usuário — pra quem não pode confirmar,
     não faz sentido abrir o modal de confirmação (`requiresConfirmation`/
     `modalHeading`/`modalDescription` da Action `criarItensPromob`
     dependem todos deste método, ver "Três achados reais/armadilhas
     do Filament" acima).
   - `criarTodosItensPromob()` (existente) ganhou uma trava redundante
     ANTES de criar qualquer coisa: se há divergência E o usuário não
     é super usuário, envia notificação de erro
     (`divergencia-bloqueada-title`/`-body`) e retorna sem criar nada
     — validação de segurança no backend, independente do que a tela
     mostrou/escondeu.
3. **Nota GERAL só quando há divergência** — `criarTodosItensPromob()`
   só cria a `NotaProjeto` GERAL de checagem (`item_projeto_id = null`)
   quando `! diferencaMetricasZerada($resultado['metricas']['diferenca'])`;
   diferença = 0 é o caso comum/esperado e não precisa de registro. A
   Nota de cálculo POR ITEM (`item_projeto_id` preenchido,
   `renderizarResumoCalculoItemPromob()`) continua sendo criada sempre,
   pra qualquer item — só a Nota GERAL do lote passou a ser
   condicional.

## "Mobilização e Frete": de botão dedicado a origem do dropdown (2026-09-06)

- Existia um botão `Action::make('mobilizacaoFrete')` FIXO (sempre
  visível, sem `->visible()`) ao lado do "Inserir" em "Itens" —
  decisão do usuário: remover esse botão dedicado e tratar
  "Mobilização e Frete" como mais uma opção do dropdown
  `Select::make('origem_item_selecionada')` ("Origem do Item"),
  seguindo o mesmo padrão de "Item de Linha"/"SketchUp" (que também
  não têm Action própria ainda).
- Mudança só de UI/estrutura — SEM regra de negócio nova (cálculo,
  campos de form, persistência): confirmado com o usuário antes de
  implementar.
- `mobilizacao_frete` virou a 5ª opção de `origensItemOptions()` e o
  5º case do enum `OrigemItemProjeto` (`MobilizacaoFrete`). Como
  nenhuma origem nova ganha Action própria automaticamente, o botão
  genérico `Action::make('inserirItem')` (visível pra qualquer
  origem fora de `promob`/`item_avulso`) já cobre "Mobilização e
  Frete" sozinho — mesma notificação placeholder
  (`notification.pendente-title`/`-body`) que "Item de Linha"/
  "SketchUp" recebem hoje. Nenhuma Action nova foi criada.
- Chave de tradução `itens.mobilizacao-frete` (label do botão antigo)
  foi REMOVIDA de `projeto.php` (pt_BR/en) — o label agora vem de
  `itens.origens.mobilizacao-frete`, junto das outras origens.
- Quando "Mobilização e Frete" ganhar lógica de negócio de verdade,
  seguir o mesmo padrão já validado de `inserirItemAvulso`/
  `inserirItemPromob`: Action própria com `->visible(fn (Get $get) =>
  $get('origem_item_selecionada') === 'mobilizacao_frete')`, no lugar
  de reaproveitar o `inserirItem` genérico.

## "Mobilização e Frete": tabela `fretes_mobilizacao` e modal de cálculo (2026-09-06)

- Ganhou lógica de negócio real (a promessa da seção anterior):
  Action própria `inserirMobilizacaoFrete`/`editarMobilizacaoFrete{id}`,
  form modal com o MESMO padrão técnico de `inserirItemAvulso`
  (`->form()`, Action de submit normal do Filament).
- Nova tabela `fretes_mobilizacao` (migration
  `2026_09_06_120000_create_fretes_mobilizacao_table`), Model
  `FreteMobilizacao`, vínculo 1-pra-1 com `ItemProjeto` via
  `item_projeto_id` (`unique()` na migration, `cascadeOnDelete()` —
  excluir o Item exclui o Frete junto). `ItemProjeto::freteMobilizacao()`
  é a relação `HasOne` correspondente.
- Campos da tabela (só os IMPUTADOS pelo usuário, ver
  `ProjetoResource::camposInputMobilizacaoFrete()`):
  `prazo_obra_dias`, `qtde_vistoria`, `funcionarios_vistoria`,
  `funcionarios_obra`, `valor_cafe_manha`, `valor_almoco`,
  `valor_jantar`, `valor_hotel`, `dias_viagem`, `valor_aviao`,
  `valor_onibus`, `km`, `qtde_frete`, `valor_frete_viagem`,
  `valor_translado` — chegou a esse desenho depois de simular a
  planilha original (`260000 Cliente Padrão Proposta 00 somente
  frete.xlsm`, aba "00 - MF") com o usuário, "teste de mesa" antes de
  qualquer código.
- **Totais NÃO são coluna da tabela** (decisão explícita do usuário) —
  `ProjetoResource::calcularTotaisMobilizacaoFrete()` é função PURA
  (mesmo espírito de `calcularValoresItemAvulso()`) que recebe os 15
  campos de input e devolve `total_mobilizacao_vistoria`/
  `total_mobilizacao_obra`/`total_frete`/`total_geral` sob demanda —
  usada tanto pela prévia reativa em tela
  (`recalcularTotaisMobilizacaoFrete()`) quanto por
  `preencherFormularioMobilizacaoFrete()` (modo edição).
- Fórmulas (herdadas da planilha original, com as simplificações
  pedidas pelo usuário):
  - Mobilização (vistoria/obra) = dias × funcionários ×
    (hotel+café+almoço+jantar) + dias de viagem × funcionários ×
    (avião+ônibus) ×2 + `valor_translado`. A MESMA verba de translado
    entra tanto no total de vistoria quanto no de obra (2x no total
    geral) — comportamento herdado de propósito da planilha original
    (lá era "uber"), não um bug: confirmado com o usuário.
  - Frete = `qtde_frete` × `valor_frete_viagem` — modelo simplificado:
    UM valor por viagem, sem tentar identificar região (a planilha
    original tinha um `IF` de região que estava QUEBRADO,
    `#REF!` — motivo a mais pra simplificar em vez de consertar).
  - `km` é só informativo neste modelo — sem campo de valor por km,
    não entra em nenhum total (a planilha original também tinha
    "estacionamento" calculado e nunca somado a lugar nenhum; aqui
    nem `valor_estacionamento`/`valor_pedagio`/`valor_km` viraram
    campo, por pedido do usuário).
- Modal (`camposFormularioMobilizacaoFrete()`), na ordem: Descrição →
  Section "Dados de Mobilização e Frete" (os 15 campos de input, Grid
  de 4 colunas) → Section "Totais (calculados)" (os 4 totais,
  `disabled()`/`dehydrated(false)`, só exibição) → Quantidade/
  Acréscimo/Custo Unitário → Valor Unitário/Valor Total.
  - Quantidade: sugestão inicial 1, mas EDITÁVEL — multiplica o total
    de novo (o usuário decidiu assim de propósito: "o frete poderá
    ter configurações únicas ou de um total mesmo").
  - Acréscimo (`porcentagem`): editável, campo de `itens_projeto`,
    MESMA regra de cálculo dos demais itens.
  - Custo Unitário: **DESABILITADO** (não "hidden" — o usuário
    corrigiu esse termo explicitamente), alimentado automaticamente
    pelo `total_geral` calculado acima
    (`recalcularTotaisMobilizacaoFrete()` faz `$set('custo_unitario',
    ...)` a cada tecla nos campos de input). Achado real: um campo
    `->disabled()` no Filament, por padrão, NÃO desidrata — igual ao
    Custo Unitário de item Promob (`camposFormularioItemAvulso()`),
    MAS aqui o valor calculado precisa mesmo chegar em
    `$data['custo_unitario']`, então tem `->dehydrated()` explícito
    reativando o envio (sem isso, `salvarMobilizacaoFrete()` receberia
    `custo_unitario` ausente do `$data`).
  - Valor Unitário/Valor Total: `calcularValoresItemAvulso()`
    REAPROVEITADA sem nenhuma alteração — mesma fórmula (Custo
    Unitário × (1+Porc.%/100) × (1+Imp.%/100), × Quantidade) de
    qualquer outro Item do Projeto.
- `salvarMobilizacaoFrete()` grava nas DUAS tabelas na MESMA
  `DB::transaction()`: `itens_projeto` (origem
  `OrigemItemProjeto::MobilizacaoFrete`, mesmo padrão de
  `lockForUpdate()`/Imposto fresco do banco de `salvarItemAvulso()`)
  e `FreteMobilizacao::updateOrCreate(['item_projeto_id' => ...], [...])`
  — `updateOrCreate` pelo `item_projeto_id` cobre criação E edição sem
  precisar de branch separado (diferente de `salvarItemAvulso()`, que
  faz `if ($item) update() else create()` porque só grava numa
  tabela).
- `linhaExibicaoItem()`: o ícone "Editar" de cada linha agora se
  ramifica pela origem do item — `editarItemAvulso{id}` (form de Item
  Avulso) pra qualquer origem exceto Mobilização e Frete,
  `editarMobilizacaoFrete{id}` (form novo, preenchido também com os
  dados de `$item->freteMobilizacao`) só pra ela.
- **Achado real (`artisan migrate` respondendo "Nothing to migrate"
  com o arquivo já presente em `database/migrations/`)**: a migration
  nova (`2026_09_06_120000_create_fretes_mobilizacao_table`) só passou
  a rodar depois de acrescentada também em
  `ComercialServiceProvider::configureCustomPackage()` →
  `hasMigrations([...])` — essa lista é EXPLÍCITA, não um scan de
  diretório (`loadMigrationsFrom()` só carrega o que está nomeado
  aqui, ver o comentário já existente no próprio array sobre as "3
  entradas que faltavam"). **Checklist obrigatório pra QUALQUER
  migration nova deste plugin, a partir de agora**: 1) criar o arquivo
  em `database/migrations/`, 2) adicionar o nome (sem `.php`) no fim
  do array `hasMigrations([...])` do Service Provider, só então 3)
  `artisan migrate` — pular o passo 2 dá exatamente esse sintoma
  enganoso de "nada a migrar" mesmo com o arquivo certo no lugar
  certo.

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

## Cluster "Referências" — Condições Financeiras (2026-09-08)

Novo cadastro de apoio, mesmo padrão técnico de `ReferenciaPreco`
(catálogo com várias condições coexistindo ao mesmo tempo, NÃO
histórico/versionamento; CRUD em modal via `CondicaoFinanceiraResource`,
sem pages `create`/`edit` em `getPages()`; Lixeira própria via
`SoftDeletes`/`TrashedFilter`; trava de exclusão/edição com Projeto
vinculado igual a `ReferenciaPreco::bloquearSeVinculada()`).

**Model/migration** — `Perseu\Comercial\Models\CondicaoFinanceira`,
tabela `condicoes_financeiras` (migrations
`2026_09_08_100000_create_condicoes_financeiras_table` +
`2026_09_08_100001_add_condicao_financeira_id_to_projetos_table`,
**adicionadas ao array `hasMigrations([...])` de
`ComercialServiceProvider`** desde o início — ver comentário já
existente nesse arquivo sobre esse gotcha).

Campos do catálogo, decididos em conversa (não em planilha, diferente
de Mobilização e Frete):
- `descricao` — obrigatória, mesma convenção de "Data/Hora de criação
  como identidade visual" que `ReferenciaPreco` (Descrição sozinha não
  é única).
- `porcentagem_entrada` (`decimal(5,2)`, "Porcentagem de Entrada") —
  por ora só informativo; a regra de como desconta do valor financiado
  fica para a tarefa do cálculo do Total do Projeto.
- `qtde_parcelas` (`unsignedInteger`, default 1).
- `intervalo_dias` (`unsignedInteger`, default 30, "Intervalo em
  Dias") — renomeado de "Dias Fixos" a pedido do usuário; por ora só
  informativo (espaçamento entre parcelas), sem entrar em cálculo
  ainda.
- `forma_pagamento` (`string`, cast pro enum
  `Perseu\Comercial\Enums\FormaPagamento` — 3 valores: Pix/Transferência,
  Boleto, Cartão, escolha ÚNICA, mesmo padrão de `OrigemItemProjeto`).
- `taxa_mensal` (`decimal(5,2)`, "Taxa Mensal", sufixo "% a.m.") —
  **decisão importante**: NÃO se guarda uma tabela fixa de fatores por
  quantidade de parcelas (a tabela de exemplo 1x–10x que o usuário
  trouxe, baseada no Sistema Price a 5% a.m., foi só ilustração da
  fórmula). O fator de amortização (`fator = i / (1 - (1+i)^-n)`) será
  **calculado em tempo real** a partir desta taxa mensal + a
  `qtde_parcelas` escolhida no Projeto, na tarefa futura do cálculo do
  Total do Projeto — esta tarefa só registra o modelo de dados, sem
  implementar o cálculo ainda.

**Vínculo com Projeto** — `projetos.condicao_financeira_id` (FK
nullable, `nullOnDelete()`, mesmo padrão de `referencia_preco_id`),
`Projeto::condicaoFinanceira(): BelongsTo` /
`CondicaoFinanceira::projetos(): HasMany`.

**Layout do form de Projeto** — a Grid::make(12) que já tinha Endereço
(8) + Referência de Preços (4) foi redistribuída a pedido do usuário
para caber o novo Select: Endereço da Obra `columnSpan(6)` (50%) +
Referência de Preços `columnSpan(3)` (25%) + Condições Financeiras
`columnSpan(3)` (25%), os três lado a lado. Select de Condições
Financeiras sem `->required()`/sem `->hint()` de aviso (diferente de
Referência de Preços) — vínculo puramente opcional por ora, sem trava
de negócio identificada ainda.

**Resource/Policy/permissões** — `CondicaoFinanceiraResource` (Cluster
Referências, slug `condicoes-financeiras`, ícone
`heroicon-o-banknotes`), `CondicaoFinanceiraPolicy` (permissões
`*_comercial_condicao::financeira`, mesmo padrão de nomenclatura
`Model::class` → `snake::snake` usado em `TipoProjetoPolicy`/
`ReferenciaPrecoPolicy`), registrada em `Gate::policy()` no
`ComercialServiceProvider::packageBooted()`. **Pendência**: como
qualquer Resource novo com Filament Shield, as permissões
`*_comercial_condicao::financeira` precisam ser geradas (comando
`shield:generate` ou equivalente já usado no projeto) e atribuídas aos
roles que devem enxergar o menu "Condições Financeiras" — não feito
nesta tarefa (Cowork não tem acesso ao terminal ddev/artisan).
**Pendência 2**: `TrashCatalog`/`SubjectTypeCatalog` (plugin
`perseu/auditoria`, fora da pasta mirror `PerseuFA_comercial` acessível
por este ambiente) não foram conferidos/atualizados — checar se
`CondicaoFinanceira` precisa de entrada própria, mesmo processo já
documentado para `ItemProjeto`.

**Próximo passo natural** (ainda não iniciado): usar `taxa_mensal` +
`qtde_parcelas` do Projeto para calcular o fator de Price e o Total do
Projeto — o usuário explicitamente adiou isso para uma tarefa futura.

### Section "Condições Financeiras" no form de Projeto (2026-09-08)

Segunda parte da tarefa "Condições Financeiras" — o cálculo do Total do
Projeto adiado na primeira parte (ver seção acima) foi implementado
aqui. Nova Section, item IRMÃO de "Cabeçalho"/"Itens" no
`ProjetoResource::form()`, posicionada **DEPOIS de "Itens"** (pedido
explícito do usuário — o Total depende da soma dos itens já
inseridos). Só visível com Projeto já persistido
(`->visible(fn (?Projeto $record) => $record !== null)`), mesmo
critério de `$livewire instanceof EditProjeto` já usado pela listagem
de itens — some inteira em `CreateProjeto`.

Todos os 5 campos são `Placeholder` (somente leitura, sem
persistência nenhuma — é só uma prévia calculada ao vivo):
- **Total do Projeto** — soma de `valor_total` de TODOS os itens do
  Projeto (`$record->itens()->sum('valor_total')`), independente da
  origem.
- **Condições** — descrição da `CondicaoFinanceira` vinculada (ou
  aviso "Nenhuma condição financeira selecionada").
- **Quantidade de Parcelas** — vem da própria Condição Financeira
  (`qtde_parcelas` do catálogo), SEM override por Projeto — decisão
  implícita, não existe campo próprio disso em `Projeto`.
- **Valor Entrada** — `Total × Porcentagem de Entrada / 100`.
- **Valor da Parcela** — `(Total − Entrada) × fator de Price`, usando
  Taxa Mensal + Qtde. Parcelas da Condição Financeira (decisão
  explícita do usuário: "vai ter condições que poderá retornar valores
  maiores" — confirma que os juros do Price DEVEM aumentar o total
  pago, não é uma divisão simples).

**`calcularFatorPrice(float $taxaMensalPercentual, int $qtdeParcelas): float`**
— `fator = i / (1 - (1+i)^-n)`, com taxa 0%/não cadastrada caindo numa
divisão simples `1/n` (evita divisão por zero, mesmo resultado do
limite de Price quando `i -> 0`).

**`calcularCondicoesFinanceirasProjeto(?Projeto $record, Get $get): array`**
— função central reaproveitada pelos 5 Placeholders. Lê
`condicao_financeira_id` via `Get $get` (não `$record->
condicao_financeira_id`) de propósito — reflete a seleção ATUAL do
Select na tela, inclusive antes de salvar. Por isso o Select
`condicao_financeira_id` (Grid de Endereço/Preços/Condições, ver seção
acima) ganhou `->live()` nesta tarefa — antes não recalculava nada ao
trocar de condição sem salvar.

**Pendência conhecida, não resolvida nesta tarefa**: `porcentagem_entrada`
descontar do valor financiado ANTES do fator de Price (não incide
juros sobre a parte de entrada) — essa foi a interpretação assumida
(consistente com a única pergunta que chegou a ser feita sobre o
assunto), mas não houve confirmação explícita final do usuário sobre
esse ponto específico (a resposta dele focou só em confirmar que o
fator de Price deveria ser usado, não a ordem entrada/juros). Se algum
dia o valor da parcela parecer errado, checar esse detalhe primeiro.
## Cluster "Referências" — Documentos (2026-09-11)

Novo cadastro de apoio `DocumentoResource` (`Filament\Clusters\Referencias\Resources\DocumentoResource`), mesmo padrão técnico de `ReferenciaPrecoResource`/`CondicaoFinanceiraResource` (CRUD em modal, sem `create`/`edit` em `getPages()`, Lixeira própria com `TrashedFilter`/`RestoreAction`/`ForceDeleteAction`). Migration `2026_09_11_100000_create_documentos_table` (tabela `documentos`: `descricao`, `arquivo` nullable, timestamps, softDeletes). Model `Documento`, Policy `DocumentoPolicy` (permissões `comercial_documento`, modelo single-word — não confundir com `condicao::financeira`/`referencia::preco`, que são compound-word). `navigationSort = 3` (depois de Condições Financeiras).

Cada registro é um **template** de Excel (`.xls`/`.xlsx`/`.xlsm`) enviado pelo usuário operacional — o arquivo em si (upload via `Filament\Forms\Components\FileUpload`) fica no disco `local` (privado, `storage/app/`, NUNCA público), diretório `documentos-templates`; a coluna `arquivo` guarda só o CAMINHO relativo, não o conteúdo do arquivo.

**Decisão de arquitetura confirmada com o usuário**: arquivo binário nunca vira BLOB no banco de dados — é prática padrão (Laravel/Filament por padrão fazem assim): guardar em disco/filesystem é mais rápido, mais barato, não infla dump de backup (`mysqldump`) e não tem ganho nenhum de compressão/performance guardando como BLOB. O banco só guarda caminho/metadados.

**Sem trava de exclusão/edição por vínculo** (diferente de Referência de Preços/Condição Financeira, que travam se estiverem em uso por algum Projeto) — Documento ainda não tem nenhum Model apontando pra ele. Reconsiderar quando a geração de documento por Projeto for implementada, se fizer sentido rastrear qual Documento foi usado em qual geração.

Botão "Documentos" em `ProjetoResource\Pages\EditProjeto::getFormActions()`, posicionado ANTES de "Atribuir Processos" (a pedido explícito do usuário). Abre um `Select` com os templates cadastrados (`DocumentoResource`), gera o arquivo via `DocumentoTemplateService::gerar()` e devolve como download — decisão confirmada: os documentos GERADOS não ficam armazenados permanentemente no Perseu, são oferecidos pra download na hora (`response()->download(...)->deleteFileAfterSend(true)`) e o usuário salva onde quiser (OneDrive, SharePoint, local). Um link direto do SharePoint/OneDrive foi descartado como forma de referenciar o template-base porque esses links são amarrados a usuário/tenant, podem expirar e mudam se o arquivo for movido — por isso o template fica em arquivo físico dentro do próprio Perseu.

### Geração via marcadores `%Campo%` — `DocumentoTemplateService` (2026-09-11)

Mecanismo de "mail merge" simplificado: o template Excel contém marcadores de texto no formato `%NomeDoCampo%` digitados direto nas células pelo usuário operacional (sem precisar saber programar) — `DocumentoTemplateService::gerar(Projeto $projeto, Documento $documento)` carrega o template (`PhpOffice\PhpSpreadsheet\IOFactory::load()`), varre TODAS as abas e resolve 3 tipos de marcador, sempre com regex (não `str_replace`) pra não confundir com "%" solto em texto normal de cláusula contratual (ex.: "1% de juros moratórios"):

1. **Escalares** (`%Obra_Desc%`, `%Cliente_Nome%`, `%Contratada_CNPJ%` etc.) — substituição célula a célula via `substituirEscalares()`. Dicionário completo em `construirMarcadoresEscalares()`: `Obra_Desc`/`Projeto_Num`/`Projeto_Revisao` (campos do próprio Projeto); `Cliente_*` (Nome/Documento/Email/Contato/Endereco/Numero/Complemento/CEP/Bairro/Cidade/Estado) — Cliente é sempre `PessoaFisica` OU `PessoaJuridica` nunca os dois (confirmado com o usuário), endereço vem de `Projeto::endereco()` (Model próprio, não amarrado à Pessoa), contato de `Projeto::contatoPessoaFisica()`; `Contato_Email` (email desse contato, separado de `Cliente_Email`); `Contratada_*` — SEMPRE a `Company` raiz (`parent_id` nulo, `Webkul\Support\Models\Company`) — "por enquanto usaremos a razão social da empresa principal" (decisão explícita do usuário; matriz/filial por Projeto fica pra revisitar se um dia for preciso).
2. **Linha de Itens** (`%Itens_Num%`, `%Itens_Desc%`, `%Itens_Qtde%`, `%Itens_ValUnit%`, `%Itens_ValTotal%`, mapeados em `MAPA_CAMPOS_ITEM`) — a PRIMEIRA linha da aba com algum marcador `%Itens_*%` sozinho numa célula é tratada como "linha-molde" (`localizarLinhaItens()`), duplicada uma vez por Item do Projeto (`expandirItens()`, via `insertNewRowBefore`), preservando formatação e mesclagem de célula. `ValUnit`/`ValTotal` são gravados como NÚMERO real com formatação de moeda na célula (nunca como texto "R$ 1.234,56") — texto quebra qualquer fórmula `SOMA()` que o template já tenha pra somar a coluna. Se a linha logo abaixo do bloco de Itens tiver uma fórmula `=SOMA(...)` cujo range comece numa das colunas de Itens, `ajustarFormulasDeTotal()` ajusta automaticamente o intervalo pra cobrir as N linhas recém-inseridas (a fórmula do template só precisa somar a própria linha-molde, ex. `=SOMA(AA16:AA16)` — o ajuste dinâmico cobre 1 a N itens).
3. **Bloco de Condições Financeiras** (`%Condicoes_Financeiras%`, sozinho numa célula) — expande pro texto multi-linha (`WrapText` ligado) com os mesmos 5 valores da Section "Condições Financeiras" do form de Projeto, calculados por `CondicoesFinanceirasCalculator` (fórmula Price extraída de `ProjetoResource::calcularFatorPrice()`/`calcularCondicoesFinanceirasProjeto()` pra funcionar fora do contexto Livewire — lê `$projeto->condicao_financeira_id` direto do Model persistido, nunca `Get $get` de estado de formulário ainda não salvo).

O arquivo GERADO é sempre `.xlsx` puro, independente da extensão do template de origem (`.xls`/`.xlsx`/`.xlsm`) — `$spreadsheet->setHasMacros(false)` é chamado antes de salvar, senão o PhpSpreadsheet mantém o `xl/vbaProject.bin` do template de origem e marca o Content Type do pacote como "macro-enabled" mesmo escrevendo com o Writer `'Xlsx'`, e o Excel recusa abrir o arquivo por causa da incompatibilidade entre esse Content Type e a extensão `.xlsx`.

Escopo atual dos marcadores: abas `V` (Vistoria), `00` (Proposta), `O` (Ordem de Serviço) e `E` (Entrega) têm marcadores completos; `V`/`O`/`E` não têm `ValUnit`/`ValTotal` nos Itens (decisão do usuário — "abas O e E sem valores"); aba `C` (Checklist) só tem os marcadores de cabeçalho por enquanto ("sem conteúdo por enquanto"); abas `P` (Lista de Compras) e `Plano de Corte` ficam pra uma tarefa futura, deliberadamente fora de escopo.

**Bugs de template encontrados e corrigidos nessa rodada (2026-09-11), documentados porque o padrão se repete em templates reaproveitados de propostas reais**: fórmulas de dropdown/lista quebradas apontando pra range apagado (`#REF!`, dezenas de validações duplicadas nas abas `00`/`O`/`E`/`C`); `VLOOKUP` morto referenciando range apagado; marcador de Itens com lixo colado (`, %Itens_ValTotal%` em vez de `%Itens_ValTotal%` — quebra a expansão da linha, já que o match é exato); fórmula de total somando só a linha-molde (`=SOMA(AA16:AD16)`) em vez da coluna inteira de Itens; linhas extras pré-formatadas sobrando abaixo da linha-molde (aba `V` tinha 6 linhas fantasmas sem marcador, herdadas de quando a aba foi duplicada manualmente — removidas, junto com as dezenas de mesclagens de célula presas nelas, que impedem o `openpyxl`/PhpSpreadsheet de simplesmente excluir a linha sem desfazer a mesclagem primeiro); macro VBA (`xl/vbaProject.bin`) sobrando em template sem necessidade real de macro. **Decisão do usuário**: templates novos serão desenhados limpos desde o início (sem macro, sem fórmula de dropdown, sem linha sobrando) — evita essa classe inteira de bug em vez de caçar cada um depois.

**Bug de código encontrado e corrigido** (não era do template): `Worksheet::duplicateStyle()` do PhpSpreadsheet, quando recebe um intervalo de VÁRIAS colunas como origem (ex.: `"A18:AD18"`), não preserva o estilo de cada coluna — aplica o estilo de uma única célula (a primeira do intervalo) em TODO o destino. Isso fazia uma coluna com formatação de destaque (cinza + borda, usada pro número do item) "vazar" pra linha inteira nas linhas de Itens recém-inseridas. Corrigido duplicando estilo coluna a coluna (célula única → célula única) em vez de um intervalo largo de uma vez só.

## Necessidade de Materiais / Aba P — componentes do Promob (2026-09-12)

Primeira etapa da futura Aba P (Lista de Compras/Necessidade de Materiais) e do futuro "Plano de Corte" — **duas tarefas SEPARADAS**, não a mesma coisa com nomes diferentes. Handoff original em `handoff_aba_p.md`; decisões e implementação desta tarefa registradas aqui.

**Decisão de arquitetura**: persistir os componentes do XML no momento de "Criar Itens" (mesmo momento que já existe, não um upload separado), vinculados ao `ItemProjeto` — não ao `Projeto` diretamente, pra excluir um Item excluir seus componentes junto (`cascadeOnDelete`) e manter o totalizado sempre coerente. Motivo de persistir em vez de reprocessar o XML sob demanda: o upload de XML é **descartável** (`Storage::disk('local')->delete()` logo depois de calculado o resultado — nenhum arquivo Promob fica salvo no servidor), então um momento futuro que precise desses dados (gerar Aba P, checar divergência) não teria mais o XML original disponível, e poderia haver risco de usar XML de origem errada.

Nova tabela `itens_projeto_componentes` (migration `2026_09_12_100000_create_itens_projeto_componentes_table`, SEM `SoftDeletes` — mesmo motivo de `ItemProjeto`, ver comentário no Model): `item_projeto_id` (FK cascade), `origem` (`OrigemComponenteItem`: `xml_promob`/`manual`), `componentizado` (bool, migration `120000`), `categoria` (string nullable, migration `130000`), `referencia`, `descricao`, `largura`/`altura`/`profundidade`, `repeticao`, `quantidade`, `custo`, `preco`. `ItemProjeto` ganhou `custo_total_xml`/`arquivo_origem`/`arquivo_gerado_em` (migration `110000`) — registro do arquivo de origem, substituindo a antiga `NotaProjeto` de sistema com o cálculo congelado (removida nesta tarefa: `acaoVerCalculosItem()`/`linhaExibicaoNotaSomenteLeitura()` excluídas, `criarTodosItensPromob()` não cria mais `$novoItem->notas()->create([...])`). A checagem GERAL de divergência (Nota do XML "000" comparando total somado dos itens) **continua existindo sem alteração** — só a Nota de cálculo POR ITEM que saiu.

**`PromobXmlParser::componentesParaMateriais()`** — extrai TODOS os itens de verdade do XML (madeira/painel E ferragens/acessórios), diferente de `metricas()`/`acumularMetricasComponentes()` (que só soma `COMPONENT="Y"`, intocada, ainda usada pra validar contra o XML "000"). Achado real: ferragens (dobradiças, pistões etc.) aparecem como `CATEGORY` próprias, irmãs da categoria numerada do item, com `ITEM` folha `COMPONENT="N"` mas com `REFERENCE`/`PRICE` completos — um item de verdade, comprável. **Regra usada É ESTRUTURAL, não pelo atributo `COMPONENT`**: um `ITEM` sem filhos (`ITEMS`) é sempre uma folha (madeira `Y` ou ferragem `N`); um `ITEM` com filhos é sempre agrupador, nunca extraído. Folhas de custo zero (ex. "Processo de Fabricação") são descartadas.

**`componentizado`** guarda o `COMPONENT` ORIGINAL do XML (`true`="Y" madeira/painel, `false`="N" ferragem/acessório) — permite `ItemProjeto::metricasPromob()` reconstruir AO VIVO a mesma divisão Custo(madeira)/Misc(ferragens) que a fórmula original de Custo Unitário sempre usou, a partir dos componentes persistidos (nunca precisa reler o XML, que já foi descartado). Decisão **deliberada** de usar o `COMPONENT` do Promob (não nome de categoria) pra essa divisão — ver "Estudo do XML" abaixo pra por que nome de categoria não é confiável pra isso.

**`categoria`** guarda o `CATEGORY/@DESCRIPTION` de origem (numerado `"001 "`, nome de ambiente `"Cozinha"`/`"Dormitório"`, ou nome de fabricante/finalidade `"Acessórios"`/`"Hettich"`/`"Processo de Fabricação"` — sem lista fixa) — metadado de agrupamento, usado pra exibir a lista de Materiais separada por categoria no modal (nunca usado pra decidir `componentizado`).

**UI**: ícone "Materiais" no menu do item abre modal único (substituiu os ícones separados de Componentes/Cálculos) com a lista de componentes AGRUPADA por categoria + a seção "Cálculos" ao vivo logo abaixo, tudo renderizado como `<table>` HTML (`Html::make(new HtmlString(...))`) em vez de `Grid`/`Text::make()` por coluna — motivo: `Grid`/`Text` não reservava altura extra quando a Descrição quebrava em 2+ linhas, sobrepondo a linha seguinte; `<table>`/`<td>` com `white-space: normal; word-break: break-word; vertical-align: top` deixa a linha inteira crescer sem sobrepor nada.

**Edição de componentes: decisão consciente de NÃO implementar CRUD por ora** — "melhor não fazer a edição... o Promob tem que estar sempre coerente com o sistema" (o registro precisa continuar batendo com o que o Promob realmente exportou). Ideia registrada pro futuro, se reconsiderado: habilitar edição só pra superusuário.

**Só usa CUSTO, nunca o PREÇO/margem do Promob** — decisão explícita do usuário: margens são sempre calculadas pelo Perseu via Referência de Preços, o Promob só fornece o custo bruto.

**Pendência registrada, deliberadamente não implementada agora**: mecanismo de limpeza de componentes/dados quando um Projeto atinge estado final (aprovado/declínio), pra não inchar o banco com revisões antigas — usuário pediu pra só deixar anotado.

## Estudo do XML do Promob — estrutura completa pra Plano de Corte (2026-09-13)

Investigação do que o XML do Promob traz além do que já é lido hoje (`PromobXmlParser`) — feita analisando 3 XMLs reais fornecidos pelo usuário (um projeto completo, mais um par de exports da MESMA peça com o veio girado de propósito, como teste controlado). **Nada disso está persistido ainda, EXCETO `categoria`** (ver seção acima) — é conhecimento registrado pra quando o Plano de Corte for desenhado de verdade. Fixtures dos 2 XMLs do teste de veio salvas em `tests/Fixtures/Promob/` (`260000 - veio vertical.xml`/`260000 - veio horizontal.xml`) pra poder reproduzir a análise sem depender do usuário reenviar.

### Lógica do atributo `REFERENCE`

Formato muda por origem da peça: catálogo PRÓPRIO do Promob (madeira/painel + ferragem genérica) usa código com pontos — `{biblioteca}.{modelo}.{espessura/variação}.{cor}.{material}` pra folhas (ex. `1.2001.18.Branco.MDF` = Lateral 18mm Branco MDF), `{biblioteca}.{modelo}.{variação}.{cor}` sem material pra agrupadores (ex. `1.0082.700.Branco`), `{biblioteca}.{modelo}.000` pra ferragem genérica de "Acessórios" (ex. `1.1086.000`, Dobradiça). Ferragem de fabricante/parceiro (plugin de catálogo externo, ex. Hettich) usa o SKU cru do próprio fabricante, sem pontos (ex. `9079603`) — o Promob só importa o código do parceiro, não gera código próprio.

### Bloco `<REFERENCES>` dentro de cada `ITEM` — não lido hoje pelo parser

Cada `ITEM` folha carrega um sub-bloco com campos nomeados (`<NOME REFERENCE="valor"/>`) muito mais rico que só `REFERENCE`/`PRICE`:

- **Madeira/painel**: `MATERIAL` (MDF/Aglom), `MODEL`/`MODEL_DESCRIPTION` (cor/acabamento, ex. "Branco"), `THICKNESS` (espessura em mm — confirmado sempre igual ao que já vem no `HEIGHT` do `ITEM`, ver nota sobre `WIDTH`/`HEIGHT`/`DEPTH` abaixo), `MAXWIDTH`/`MAXDEPTH` (dimensão da CHAPA de origem — **sempre `2750`×`1830`~`1850`mm** nos exemplos vistos), `LINE`/`LINE_EXT` (linha do produto, ex. "Cores"), `FORMA` (só em portas, ex. "Porta/Frente Reta"). O sub-campo `PRICE` traz uma chave residual (ex. `"18.Branco.MDF"` = `REFERENCE` completo menos o prefixo do modelo) — idêntica entre peças de modelo diferente que usam a MESMA chapa (Lateral/Base/Porta todas em MDF Branco 18mm batem nessa chave) — candidata a chave de consolidação de compra por matéria-prima real (mais confiável que só Referência+Descrição pra somar "quantos m² de MDF Branco 18mm comprar no total").
- **Ferragem de parceiro** (ex. Hettich): `FORNECEDOR`, `CATEGORIA` (ex. "Parceiro Hettich"), `ATIVO`, `PEDIDOFABRICA` — dá pra agrupar lista de compras por fornecedor além de por categoria.

### Fita de borda (`FITA_BORDA_1..4`)

Valor = espessura da fita em mm (`"0.4"` = 0,4mm; `"0"` = sem fita naquele lado) — confirmado batendo com o painel "Integração c/ Plano de Corte" do próprio Promob. 4 slots independentes (cada lado pode ter espessura própria), mas **estruturalmente agrupados 2 a 2 por dimensão da peça** — validado em 181 itens reais de um projeto completo: **zero casos** de um lado ativo em {1,2} e outro em {3,4} ao mesmo tempo sem serem os 4 juntos (peças com fita na volta toda, ex. portas). Qual das duas dimensões cada par representa foi provado com o campo `PERIMETRO_FITA` (string tipo `"0+770+0+0"`, mesma ordem de `FITA_BORDA_1..4`, só presente em `"Prateleira Linear"` neste conjunto de dados) — a posição 2 bateu exatamente com o `WIDTH` da peça (770mm), confirmando que o par {1,2} corresponde às arestas na direção `WIDTH` e, por eliminação, {3,4} às arestas na direção `DEPTH`. Dentro de um par, os dois lados são independentes (pode ter fita só num lado, nos dois, ou em nenhum) — quando os dois lados de um mesmo par têm fita, sempre vieram com a mesma espessura entre si nos dados vistos (mas o campo permite valores diferentes em teoria). Cor/linha da fita: só 2 specs possíveis por peça, não 4 — `SUPPLIER_FITA`/`MODEL_DESCRIPTION_FITA`/`LINE_FITA` (fita "normal") e `SUPPLIER_FITA_FRO`/`MODEL_DESCRIPTION_FITA_FRO`/`LINE_FITA_FRO` (fita "frontal", provavelmente a face mais visível) — visto um caso real de fita normal e frontal com cores DIFERENTES na mesma peça (`Grafite`/`Branco`).

### `PLATECUTTINGROTATE` — orientação do veio, PROVADO por teste controlado

Atributo do próprio `ITEM` (não dentro de `<REFERENCES>`). Teste feito: usuário reexportou o MESMO projeto trocando o veio de "em pé" (vertical) pra horizontal em todas as peças com material amadeirado (Nogal Terracota Nature/Arauco), e comparei os 2 XMLs peça a peça. **Resultado limpo**: a ÚNICA diferença real em cada uma das 6 ocorrências das peças em Nogal foi `PLATECUTTINGROTATE: "Y" → "N"` — `WIDTH`/`HEIGHT`/`DEPTH`, `FITA_BORDA_1..4`, material/cor, e até o custo/preço total do projeto (`PromobXmlParser::parse()`) ficaram bit-a-bit idênticos entre os 2 arquivos. Confirma: `"Y"` = veio em pé (vertical, orientação original/default), `"N"` = veio horizontal (girado) — **independente das dimensões da peça** (não dá pra inferir a orientação só olhando `WIDTH`/`DEPTH`, é um campo à parte). Materiais sem veio (Branco, Grafite/Duratex Trama, Aço) vêm sempre `"NONE"` neste conjunto de dados — só o material com veio real (Nogal) alternou entre `"Y"`/`"N"`.

**Revisão 2026-09-17 — o que esse teste NÃO provava**: ele provou o SENTIDO de cada valor ("Y"=vertical, "N"=horizontal), mas a implementação original (`veio_travado = PLATECUTTINGROTATE==="Y"`, ver seção "O que falta persistir" abaixo) deu um passo a mais nunca testado: tratar `"N"` como "sem restrição pro motor de nesting". Usuário reportou uma peça real (Carvalho Mel Sonoma, "Tamponamento Inferior 18", 800×220, `PLATECUTTINGROTATE="N"`) com o veio saindo errado no nosso Plano de Corte mas correto no Promob Cut Pro real. Investigação cruzando o XML do projeto com um PDF real do Promob Cut Pro do MESMO projeto (Lista de Cortes, coluna "Dimensão", casado por `UNIQUEID`) em 11 peças de Carvalho Mel Sonoma: **peças "Y" sempre saem com a dimensão invertida** (profundidade primeiro) **em relação ao XML cru; peças "N" sempre saem na MESMA ordem do XML** — 11 de 11, sem exceção, incluindo um par perfeito (mesma peça/mesma medida 1434,5×755, uma instância "Y" outra "N", cada uma numa orientação diferente, cada uma batendo com a regra). Conclusão: `"N"` é uma trava tão rígida quanto `"Y"`, só que na orientação OPOSTA — nenhuma das duas é "livre". Só `"NONE"`/ausente é livre de verdade (nenhuma peça com veio real observada com esse valor). Corrigido em `PromobXmlParser`/`PlanoCorteNestingService`/`PlanoCortePackingSolverService` — novo campo `veio_eixo_fixo` (`'largura'`|`'profundidade'`|`null`) substitui o uso de `veio_travado` (mantido só por compatibilidade) nos dois motores de nesting.

### Sentido do veio na peça = eixo Altura (não Largura)

Confirmado com 2 portas reais (medidas informadas pelo usuário batendo exato com o XML): "Porta do armário inferior" 394×692×18 (Largura×Altura×Espessura) e "Porta dos basculantes" 692×342×18 — em ambas, `Largura = WIDTH do XML`, `Altura = DEPTH do XML`, `Espessura = HEIGHT do XML` (correspondência exata, não aproximada). As duas têm veio "em pé" no render — e a Altura é o eixo do veio nas duas, **mesmo sendo o valor MAIOR num caso (692) e o MENOR no outro (342)** — ou seja, não é "o lado maior" que carrega o veio, é sempre a Altura, independente de magnitude. Ambas também vieram com `PLATECUTTINGROTATE="Y"`, batendo com a teoria.

**Chapa**: confirmado pelo usuário que o veio da CHAPA em si sempre corre no lado de `2750`mm (`MAXWIDTH`). Regra de nesting resultante pro futuro algoritmo de Plano de Corte: quando `PLATECUTTINGROTATE` indica veio travado, a Altura da peça (eixo do veio) precisa ficar paralela ao lado de 2750mm da chapa — não pode girar 90°, senão o veio sai atravessado.

**Cuidado ao reconstruir orientação real**: os nomes `WIDTH`/`HEIGHT`/`DEPTH` do XML NÃO correspondem de forma fixa aos rótulos "Largura"/"Altura"/"Profundidade" que o Promob mostra na própria tela — a correspondência muda por TIPO de peça. Pra `"Lateral"`, a tela mostra Largura=15 (a espessura) enquanto o XML tem `WIDTH=700`/`HEIGHT=15` — ou seja, ali `Largura(tela) = HEIGHT(xml)` e `Altura(tela) = WIDTH(xml)`. Já pra `"Porta"`/`"Frente"`, é `Largura(tela) = WIDTH(xml)` e `Altura(tela) = DEPTH(xml)` (ver parágrafo acima). O único que parece FIXO em todos os casos vistos é `HEIGHT(xml) = Espessura` (thickness da chapa). Isso não muda nada no que já é persistido hoje (`ItemProjetoComponente::largura`/`altura`/`profundidade` continuam sendo `WIDTH`/`HEIGHT`/`DEPTH` crus, sem reinterpretar — regra de ouro do parser) — só registro pra não estranhar se um dia comparar visualmente com a tela do Promob e os números parecerem "trocados": são os mesmos valores, só o rótulo que muda por tipo de peça.

### O que falta persistir (deferido, aguardando escopo do Plano de Corte)

**Atualização (2026-09-13, retomado)**: todos estes campos JÁ FORAM persistidos — ver migration `2026_09_13_100000_add_dados_plano_corte_to_itens_projeto_componentes_table` e `PromobXmlParser::coletarComponentesMateriais()`. Escopo travado pelo usuário: só enriquecer os campos (flat), sem hierarquia/árvore de montagem — `ItemProjetoComponente` ganhou `material`/`cor`/`espessura`/`fornecedor` (unifica `SUPPLIER`+`FORNECEDOR`)/`chapa_largura`/`chapa_comprimento`/`fita_borda_1..4`/`fita_cor`/`fita_cor_frontal`/`perimetro_fita`/`veio_travado` (bool, `PLATECUTTINGROTATE==="Y"`). `PERIMETRO_FITA` guardado como string crua do Promob, sem parsear.

**Atualização (2026-09-17)**: `veio_travado` (bool) mantido só por compatibilidade — ver "Revisão 2026-09-17" acima. Novo campo `veio_eixo_fixo` (string nullable, migration `2026_09_17_100000_add_veio_eixo_fixo_to_itens_projeto_componentes_table`) é o que os motores de nesting usam de fato: `'largura'` (era `"N"`) ou `'profundidade'` (era `"Y"`) — qual dimensão crua da peça fica travada no eixo do veio da chapa — ou `null` (era `"NONE"`/ausente), a única situação realmente livre.

## Plano de Corte — motor de nesting + botão "Produção" (2026-09-13)

Usuário forneceu 3 PDFs reais gerados pelo Promob Cut pro MESMO projeto já usado nas fixtures (`24-260000`/`2630001`): "Lista de Compras" (chapas sintetizadas + bordas em metro linear + acessórios), "Preview de Corte" (desenho de cada chapa com as peças posicionadas) e "Etiquetas" (uma por peça física, com código de barras). Pedido: implementar um motor de corte próprio que chegue num resultado equivalente, sem tentar clonar o algoritmo proprietário do Promob nem bater exatamente com os números dele.

**Decisão de arquitetura confirmada com o usuário**: um botão **"Produção"**, em `EditProjeto::getFormActions()` logo depois de "Documentos", MESMO padrão dessa Action (form modal → gera → `response()->download()->deleteFileAfterSend(true)`) — **nada fica persistido no banco**. O nesting é recalculado do zero a cada clique a partir dos `ItemProjetoComponente` já salvos do Projeto inteiro (todos os Itens, não só um) — barato de reprocessar, não precisa de tabela nova. Os 3 relatórios saem num **PDF único** (perguntado ao usuário — "seria possível arquivo único?" — sim, e é o que foi implementado: 3 blocos de página, `page-break-after` entre eles).

**Antes de gerar, o form pergunta** (decisão explícita do usuário — kerf/fresa e limpeza de bordas não vêm do XML do Promob, são parâmetro de MÁQUINA da marcenaria, então precisam ser perguntados toda vez, não fica config persistida): tipo de equipamento (Serra/CNC, `Radio` com `->live()`), espessura da serra OU da fresa (campo condicional via `Get $get`, só um dos dois aparece) e limpeza das bordas da chapa em mm (margem descontada do perímetro antes de encaixar).

### `PlanoCorteNestingService` — algoritmo de guilhotina

Classe SEM Eloquent/DB (`src/Services/PlanoCorteNestingService.php`), recebe/devolve só arrays puros — testável com um script PHP simples sem subir o Laravel. Agrupa componentes `componentizado=true` (madeira/painel) por `material+cor+espessura+fornecedor+chapa_largura+chapa_comprimento`, explode `repeticao` em peças físicas individuais, ordena da maior pra menor dimensão, e encaixa via **guilhotina "melhor encaixe"** (Best Area Fit): cada chapa começa como 1 retângulo livre (área útil = chapa menos `limpeza_bordas` dos 4 lados); a cada peça, escolhe entre TODOS os retângulos livres de TODAS as chapas já abertas do grupo o que sobra menos área, encaixa no canto superior-esquerdo, e substitui o retângulo por até 2 filhos ("direita da peça" + "embaixo da peça, largura cheia do retângulo original", com `kerf` descontado como espaço morto) — como só o retângulo ESCOLHIDO é dividido (os outros ficam intocados), por indução os retângulos livres nunca se sobrepõem, e portanto peças nunca se sobrepõem. Se não coube em nenhuma chapa já aberta do grupo, abre uma nova; se não cabe numa chapa em BRANCO em nenhuma orientação permitida, cai em `nao_classificados` em vez de travar num loop infinito.

**Convenção de eixos pro veio** (aplica a pesquisa da seção acima): eixo X da chapa = `chapa_largura` (lado de 2750mm, onde o veio da CHAPA corre); eixo Y = `chapa_comprimento`. ~~Orientação NATURAL de toda peça (sempre usada quando `veio_travado=true`) coloca `profundidade` no eixo X e `largura` no eixo Y... Rotação 90° só é permitida quando `veio_travado=false`.~~ **Superado pela "Revisão 2026-09-17" acima** — `"N"` não é "sem trava", é trava na orientação OPOSTA de `"Y"`. Regra atual: `veio_eixo_fixo` (`'largura'`|`'profundidade'`|`null`) diz qual dimensão crua da peça vai pro eixo X; quando não-nulo, só existe UMA orientação válida (nunca duas) — rotação livre só quando `veio_eixo_fixo===null`. Ver docblock de `PlanoCorteNestingService`, "Revisão 2026-09-17", pra fórmula completa.

**Verificação feita** (script standalone, `PromobXmlParser::componentesParaMateriais()` das 3 fixtures reais → `PlanoCorteNestingService::gerar()` com kerf=4mm/limpeza=10mm): geometricamente correto — nenhuma sobreposição, nenhuma peça fora da área útil, em 9 chapas geradas cobrindo 100 peças de madeira/painel. Contagem de peças por chapa bateu de forma notável com o PDF real: nosso grupo "MDF/Branco/15mm" encaixou exatamente 31 peças na 1ª chapa, o MESMO número que o PDF real "Cores.Branco - MDF - 15, Chapa 1, Peças: 31" — mesmo o nosso algoritmo sendo uma heurística própria, não uma cópia do Promob. Contagem exata de chapas não bate 1-pra-1 porque as fixtures usadas (`veio vertical.xml` + `veio horizontal.xml`) são o MESMO projeto exportado 2x — dado duplicado de propósito, não um bug.

### `PlanoCorteFitasService` — metros lineares de fita por espessura/cor

Soma o comprimento de borda ativa (`FITA_BORDA_1..4 > 0`) agrupado por (espessura da fita, cor) — não precisa de nesting, fita é vendida em rolo. **Convenção** (ver "Fita de borda (`FITA_BORDA_1..4`)" acima, seção "Estudo do XML do Promob" — a prova com `PERIMETRO_FITA` já estava certa lá; **Revisão 2026-09-16**: esta seção tinha reescrito a convenção AO CONTRÁRIO por engano, e o código (`PlanoCorteFitasService`/`PlanoCorteRelatorioService::bordasPorLado()`) foi implementado seguindo o erro daqui, não a prova original — usuário reportou o tracejado da fita marcando o lado errado da peça, reconferido `PERIMETRO_FITA` contra `WIDTH`/`DEPTH` em mais 10 peças reais do XML "2610015 Olga 86", confirmando a versão original): par `{FITA_BORDA_1,2}` acompanha a `largura` da peça, par `{3,4}` acompanha a `profundidade` — cada lado ativo do par soma 1x aquele comprimento. **Simplificação deliberada**: usa sempre `fita_cor` (ignora `fita_cor_frontal`) — nas fixtures disponíveis as duas colunas sempre vinham iguais, sem caso real de fita frontal com cor diferente da fita normal pra validar a separação; revisar se um projeto futuro tiver as duas cores diferentes na mesma peça. Números calculados contra as fixtures ficaram na mesma ordem de grandeza do PDF real (ex. cor Grafite: 11.76m calculado vs 9.09m do PDF — fixtures são um subconjunto do projeto completo, não bate exato; como largura/profundidade das peças testadas eram parecidas em magnitude, o pareamento trocado não gerou uma diferença grande o bastante pra essa checagem pegar o erro).

### `PlanoCorteRelatorioService` — PDF único via Dompdf

Monta HTML puro (mesmo padrão já usado em `ProjetoResource::renderizarListaMateriais()` — string concatenada, sem Blade/views) com 3 seções (Materiais/Preview de Corte/Etiquetas) e renderiza com `Dompdf\Dompdf` em A4 paisagem. **Requer `dompdf/dompdf` no `composer.json` da RAIZ do Perseu-FA** (mesmo padrão já usado por `phpoffice/phpspreadsheet`, exigido por `DocumentoTemplateService` e também declarado só na raiz, nunca no `composer.json` deste plugin) — se ainda não estiver instalado, rodar `composer require dompdf/dompdf` na raiz.

**Cortes de escopo deliberados nesta primeira versão**: sem código de barras nas etiquetas (só texto — evita mais uma dependência só pra isso); numeração de peça sequencial simples (1, 2, 3...) dentro da chapa, não o esquema "1.A/2.A" (letra por tamanho + repetição) do Promob; "Aproveitamento %"/"Cortes" são do nosso algoritmo, não uma réplica do Promob Cut. Etiquetas em grade A4 (4 colunas), não uma etiqueta por página como o Promob (pedido explícito do usuário — imprimir em impressora comum, sem rolo de etiqueta dedicado).

**Ainda fora de escopo**: sintetizar chapas na tela de Materiais/Aba P existente (que hoje é POR ITEM, enquanto o nesting é do Projeto inteiro — precisaria repensar a UI); persistir o resultado do Plano de Corte pra não recalcular a cada clique (decisão deliberada de NÃO fazer isso agora, mesmo padrão ephemeral de "Documentos"); campo configurável de kerf/limpeza padrão por Projeto ou global (hoje pergunta toda vez).

### Revisão 2026-09-13 (mesmo dia, feedback do usuário testando) — cortes em tiras + veio pela maior dimensão

Usuário testou o PDF gerado e trouxe 2 observações de quem opera/acompanha marcenaria de verdade, mais 2 referências externas — todas incorporadas:

1. **"a espessura da serra deve aparecer entre as peças... considerada pra não reduzir a peça cortada"** — a geometria JÁ estava certa (o kerf só consome espaço morto entre peças na subdivisão da guilhotina, o tamanho da peça em si — `largura_corte`/`comprimento_corte` — nunca é reduzido por ele), mas não aparecia DE FORMA VISÍVEL no PDF. Corrigido: `PlanoCorteRelatorioService::montarHtmlPreviewCorte()` agora mostra "Espessura de corte considerada: Xmm" no cabeçalho de cada chapa E desenha linhas tracejadas vermelhas (`.linha-corte`) nas fronteiras entre prateleiras (ver item 2), centralizadas no meio do vão real de kerf.

2. **"a seccionadora, no caso serra, faz cortes retos pelo comprimento e/ou largura da chapa, então considerar os cortes em tiras"** — achado real importante: o algoritmo anterior (guilhotina livre, melhor-encaixe em árvore recursiva de profundidade arbitrária) é geometricamente válido mas não reflete como um operador de seccionadora trabalha de verdade (ele não reposiciona a régua dezenas de vezes num padrão arbitrário). `PlanoCorteNestingService::gerar()` agora recebe `$tipoEquipamento` e usa DOIS algoritmos diferentes:
   - **Serra** → `encaixarGrupoEmTiras()`: shelf-packing clássico de 2 estágios — 1º estágio corta a chapa inteira em tiras horizontais (corte reto, largura cheia); 2º estágio corta cada tira em peças (cortes transversais). Caso particular de guilhotina (2 níveis fixos, não árvore arbitrária).
   - **CNC** → `encaixarGrupoLivre()` (o algoritmo original, renomeado): guilhotina livre, sem a restrição de tiras — CNC não precisa reposicionar régua fisicamente, então pode aproveitar melhor a chapa com um padrão mais complexo.
   - Resultado de cada chapa ganhou `prateleiras` (lista de `{y, altura}`, só preenchida no modo Serra) — usado pelo PDF pra desenhar as linhas do 1º estágio.
   - Verificação geométrica (mesmo script standalone, agora rodando os 2 modos): nenhuma sobreposição, nenhuma peça fora da chapa, e no modo Serra o vão real entre prateleiras consecutivas sempre ≥ kerf configurado.

3. **Link oficial do Promob** (["Cut Pro — Interpretar sentido de veio"](https://suporte.promob.com/hc/pt-br/articles/31121272983569-Cut-Pro-Interpretar-sentido-de-veio)) — confirma e GENERALIZA o que já tínhamos: o veio da CHAPA corre ao longo da sua **maior dimensão** (não especificamente "o lado de 2750mm" — nas fixtures reais a maior dimensão sempre É o lado de 2750mm, mas a regra do Promob é geral). Código atualizado pra usar `max(chapa_largura, chapa_comprimento)` em vez de assumir sempre `chapa_largura`, cobrindo o caso hipotético de um material futuro vir com a chapa "deitada". A doc também confirma a exigência que já tínhamos implementado: "o veio da peça precisa corresponder ao veio da chapa pro sistema de encaixe funcionar corretamente".

4. **[OpenCutList](https://docs.opencutlist.org/features/parts/parts-list/cutting-diagrams)** (extensão open-source de lista de corte pro SketchUp, sugerida pelo usuário) — pesquisado via WebFetch da própria documentação: usa Bin Packing buscando "o menor número de painéis necessários", e confirma a MESMA regra de guilhotina que já tínhamos adotado por conta própria — "todos os cortes devem atravessar o painel ou o retalho sem parar no meio ou fazer curvas". Não documenta publicamente diferença de tratamento serra vs. CNC nem como trata kerf — não deu pra aproveitar detalhe de algoritmo específico de lá além de confirmar que a abordagem geral (guilhotina) está alinhada com o que ferramentas do mesmo tipo já fazem. Não chegamos a avaliar se ele cobre nesting de CNC — ficou registrado como dúvida do próprio usuário, não investigada a fundo.

**Pendências que ficam pra próxima rodada** (não pedidas ainda, só identificadas durante essa revisão): agrupar as linhas "Fita X" da seção Bordas por espessura real de fita (hoje a largura física do rolo, tipo "22mm"/"19mm" do PDF do Promob, não é calculada — ver docblock de `PlanoCorteFitasService`).

### Revisão 2026-09-13 (rodada 3, usuário testou o PDF gerado da rodada 2) — kerf ainda invisível, prateleiras com alturas diferentes, página quebrada

Usuário gerou o PDF de verdade (botão "Produção", equipamento Serra) e apontou problemas reais na saída:

1. **"não percebo o espaço da serra entre as peças"** — a linha vermelha tracejada da rodada anterior só marcava a fronteira ENTRE prateleiras (1º estágio); entre peças da MESMA prateleira (2º estágio, cortes transversais) não tinha marcação nenhuma — as caixas eram desenhadas encostadas, apesar do kerf já estar corretamente reservado como espaço morto na matemática do encaixe. Corrigido de forma mais simples e geral que "desenhar uma linha por corte": cada caixa de peça agora é desenhada ENCOLHIDA meio-kerf pra dentro em cada lado (`PlanoCorteRelatorioService::montarHtmlPreviewCorte()`, variável `$folgaKerfMm`) — o vão em branco entre DUAS peças vizinhas quaisquer (mesma prateleira, prateleiras diferentes, ou no modo CNC) reproduz o kerf INTEIRO. É só uma correção de DESENHO — os números impressos na etiqueta de cada peça continuam sendo `largura_corte`/`comprimento_corte`, a peça real, sem encolher.

2. **"a seccionadora... faz cortes retos... considerar os cortes em tiras"** — revisando a implementação da rodada anterior à luz do PDF real, achamos um bug de fundo que o usuário não chegou a descrever explicitamente mas que o mesmo princípio exige corrigir: `encaixarGrupoEmTiras()` permitia uma peça mais BAIXA entrar numa prateleira mais ALTA (contanto que coubesse), deixando uma sobra de material ainda presa embaixo dela — nem o corte "rip" (fronteira da prateleira) nem o corte "cross" (entre peças) passam por ali, então essa sobra exigiria um 3º corte que o algoritmo não sabia que precisava existir. Corrigido: uma prateleira só aceita peça cuja altura bata com a da prateleira dentro de 0,5mm (`PlanoCorteNestingService::TOLERANCIA_ALTURA_PRATELEIRA_MM`) — agora toda prateleira é uma tira DE VERDADE, uniforme, onde os 2 cortes bastam pra liberar cada peça. Custo aceito: mais chapas (verificado nas fixtures: 9→10 chapas no modo Serra com esse fix) — já confirmado com o usuário que não perseguimos "Aproveitamento %" ótimo, só corte fisicamente executável.

3. **"a pagina esta saindo separada do cabeçalho"** — bug de CSS/paginação do Dompdf: o cabeçalho de cada chapa (texto normal) e o `.chapa-container` (bloco com peças `position:absolute`, sem texto em fluxo normal) eram 2 blocos IRMÃOS soltos na mesma "página" lógica; quando o container não cabia no espaço restante da página atual, o Dompdf empurrava só ELE pra página seguinte (por não ter como "quebrar" um bloco cujo conteúdo é todo posicionamento absoluto), deixando o cabeçalho sozinho pra trás. Corrigido: cabeçalho + container agora ficam dentro de UM único `<div class="pagina-chapa">` (`page-break-before: always; page-break-inside: avoid;`) — força os dois a nascerem juntos no topo de uma página nova, que sempre tem espaço de sobra (o desenho nunca passa de ~165mm de altura contra ~190mm úteis de uma A4 paisagem). O mesmo padrão foi usado pra separar a seção de Etiquetas (`.nova-pagina`, SEM `page-break-inside:avoid` porque a tabela de etiquetas pode legitimamente ocupar várias páginas).

**Verificação feita**: script standalone reconfirmou geometria válida nos 2 modos (Serra agora com 10 chapas ao invés de 9, CNC inalterado em 9) MAIS uma checagem nova — dentro de cada prateleira, todas as peças têm a mesma altura dentro de 0,5mm (senão acusa "sobra presa"). Como o Dompdf real não estava disponível pra testar aqui (só existe declarado no `composer.json` da RAIZ do Perseu-FA, que não está sincronizado neste ambiente, e o pacote não é instalável neste sandbox — Packagist bloqueado pelo proxy de rede daqui), a correção de paginação foi validada renderizando o MESMO HTML produzido pela classe real (via Reflection, sem tocar Eloquent) com `wkhtmltopdf` (motor diferente, mas mesmo CSS de paginação) — confirmado visualmente: cabeçalho e desenho na mesma página em todos os casos, prateleiras com alturas uniformes, vão de kerf visível entre toda peça vizinha.

### Revisão 2026-09-13 (rodada 4) — corte pode começar pela largura OU pela altura; DOIS algoritmos viram UM

Usuário reexaminou o PDF real do Promob Cut Pro (`2630001 - Preview de corte.pdf`, MESMO projeto usado nas fixtures) com mais atenção e apontou: "os cortes também podem iniciar tanto pela largura quanto pela altura, tirando tiras tanto na vertical quanto horizontal" — E "as páginas ainda estão saindo separadas do desenho do título" (não ficou claro se isso é sobre a versão JÁ corrigida na rodada 3 ou ainda a versão anterior, já que nenhum PDF novo foi anexado nesta mensagem — de toda forma, reforçamos a correção de paginação abaixo por segurança).

1. **Corte em ambas as direções** — reexaminando a Chapa 1 do grupo "Cores.Branco - MDF - 15" no PDF real: **Aproveitamento: 95,9%**, e o padrão de corte mostra claramente o 1º corte daquela chapa como uma linha VERTICAL a 350mm da borda esquerda (símbolo "»" no canto superior, seta pra baixo) — criando uma coluna de ALTURA CHEIA (1830mm) — e só depois cortes horizontais dentro dela. Isso contraria a suposição da rodada 2 (Serra = sempre tiras horizontais primeiro). Corrigido: os dois algoritmos (`encaixarGrupoEmTiras` do modo Serra e `encaixarGrupoLivre` do modo CNC) foram FUNDIDOS num só, `PlanoCorteNestingService::encaixarGrupo()` — mesma guilhotina "melhor encaixe", mas agora `dividirRetangulo()` escolhe a DIREÇÃO do corte a cada subdivisão: a folga que sobra MAIOR (largura ou altura) vira um retângulo "cheio" (aproveitável por peças grandes depois), a menor vira um retângulo "estreito" — reproduz o padrão observado no PDF real, sem fixar uma direção só. `$tipoEquipamento` continua sendo aceito pela API (usado só pra decidir qual espessura de ferramenta perguntar no form), mas não muda mais o algoritmo de encaixe em si. Resultado nas fixtures: aproveitamento do grupo MDF/Branco/15mm subiu pra 87,7%/91,3%/22,3% nas 3 chapas (rodada 2 tinha algo bem pior, tiras horizontais fixas), e o total de chapas do projeto caiu de 10 pra 8 — bem mais perto do comportamento real do Promob. A estrutura de retorno perdeu a chave `prateleiras` (não existe mais um conceito de "tira" fixa) — `PlanoCorteRelatorioService` não desenha mais uma linha vermelha específica de "1º estágio"; o vão em branco ao redor de cada peça (kerf visual, ver rodada 3) já mostra toda fronteira de corte, seja ela vertical ou horizontal.

2. **Reforço da correção de paginação** (por segurança, já que não ficou claro se o feedback era sobre a versão pré ou pós rodada-3): trocado o wrapper de `<div class="pagina-chapa">` (`page-break-before`+`page-break-inside:avoid`) por uma `<table class="tabela-pagina-chapa">` de UMA linha/UMA célula contendo cabeçalho + `.chapa-container` juntos, com `page-break-before: always` na própria tabela. Tabelas de 1 linha são um padrão mais testado no Dompdf pra manter conteúdo junto (o Dompdf é conhecido por lidar mal com `page-break-inside:avoid` em `<div>`s que só têm filhos `position:absolute`) — como a tabela é uma única linha, não tem "meio" pra quebrar.

**Ainda não verificado com o Dompdf real** (só disponível no `composer.json` da RAIZ do Perseu-FA, não instalável neste sandbox de desenvolvimento — Packagist bloqueado pelo proxy) — testado aqui com `wkhtmltopdf` sobre o HTML real gerado pela classe (via Reflection): cabeçalho e desenho ficaram juntos em todas as chapas, e o padrão de corte agora mistura colunas e linhas (não é mais só tiras horizontais). Usuário precisa confirmar com uma geração nova, real, no Dompdf.

**Pendências que ficam pra próxima rodada**: se compensa desenhar alguma indicação visual da direção/ordem dos cortes (tipo a seta "»" do PDF do Promob) — hoje o desenho mostra só as peças posicionadas e o vão de kerf, sem indicar a sequência/direção de corte.

### Revisão 2026-09-13 (rodada 5) — usuário testou com o Dompdf REAL: 2 páginas em branco + peças do mesmo tamanho espalhadas

Usuário gerou um PDF de verdade com o Dompdf da raiz do Perseu-FA (1ª vez que uma rodada desta feature foi confirmada com o Dompdf real, não só com `wkhtmltopdf`) e trouxe 2 problemas:

1. **"esta melhorando, mas... esta pulando 2 paginas em branco"** — o título FICOU na mesma página do desenho (rodada 4 funcionou nesse ponto!), mas agora sobravam 2 páginas em branco entre cada chapa. Causa raiz identificada comparando com a seção de Etiquetas (que NUNCA teve esse problema): a rodada 4 tinha trocado o wrapper de `<div>` pra uma `<table>` de 1 linha achando que seria mais confiável no Dompdf pra manter cabeçalho+desenho juntos — só que isso é um bug CONHECIDO do Dompdf (tabela + `page-break-before` gera páginas extras em branco, mesmo quando o conteúdo da linha cabe numa página só). Corrigido: revertido pra uma `<div class="cabecalho" style="page-break-before: always;">` simples, sem tabela nenhuma e sem `page-break-inside` — o mesmo padrão que a seção de Etiquetas já usava com sucesso. Como o corte de página força uma página NOVA e vazia antes do cabeçalho, cabeçalho (~20mm) + `.chapa-container` (até ~165mm) cabem juntos sem precisar de nada além disso.

2. **"a tentativa de alinhar as peças iguais é o ideal para diminuir a quantidade de cortes, da peça 14 a 21, precisaremos de vários cortes... se fossem alinhadas um corte de fora a fora e depois só seccionando"** — achado real: a busca "melhor encaixe" (Best Area Fit) pura escolhe SEMPRE o retângulo livre com menos sobra de área, sem NENHUMA noção de "essa peça é do mesmo tamanho da anterior, tenta continuar do lado dela" — então peças idênticas podiam acabar espalhadas em cantos bem diferentes da chapa sempre que outro retângulo, em outro lugar, sobrasse uma área ligeiramente menor. Corrigido: `PlanoCorteNestingService::encaixarGrupo()` ganhou `$continuacaoPorTamanho` — antes da busca global, tenta primeiro encaixar a peça atual nos retângulos que sobraram da ÚLTIMA peça do MESMO tamanho (largura×comprimento natural), criando fileiras/colunas contíguas da mesma peça (1 corte "de fora a fora" isolando a faixa + só seccionamento dentro dela) em vez de espalhar. Só cai pra busca global quando nenhum retângulo "preferido" serve mais. Verificado nas fixtures (script `verificar4.php`): geometria continua válida (zero sobreposições), aproveitamento praticamente igual (mesmas ~8 chapas no total), e visualmente (renderizado com `wkhtmltopdf` sobre o HTML real da classe) as peças "Lateral de Gaveta 15mm"/"Sarrafo 15mm" que antes apareciam espalhadas em 3-4 cantos diferentes da chapa agora aparecem agrupadas em colunas de 2-3 peças iguais lado a lado.

**Pendências que ficam pra próxima rodada**: o agrupamento por tamanho é uma heurística "gulosa" (tenta continuar o último retângulo, não faz uma busca ótima por MAIOR grupo contíguo possível) — pode não agrupar 100% das peças idênticas em alguns casos; se o usuário achar que ainda sobra espalhamento demais, dá pra evoluir pra uma heurística mais forte (ex. reservar retângulos específicos por tamanho ANTES de começar a encaixar, olhando a quantidade total de cada tamanho no grupo).

### Revisão 2026-09-14 (rodada 6) — blocos por tamanho ANTES de encaixar, contagem real de cortes, página quebrada de novo

Usuário testou de novo (peças 14-21 ainda espalhadas no PDF da rodada 5) e trouxe 3 pontos, mais uma pesquisa própria sobre como o Promob Cut Pro/Corte Certo resolvem isso de verdade (2D Cutting Stock Problem + Binary Trees pra Serra, Irregular Shape Packing pra CNC — todos proprietários; não tentamos clonar, só usamos como confirmação de que "guilhotina = corte de ponta a ponta" é a regra certa, não invenção nossa).

1. **Agrupamento ainda insuficiente + "quantidade de cortes" nunca foi calculada.** A heurística reativa da rodada 5 ("continuação") só colava a peça atual na sobra da peça anterior do MESMO tamanho — não resolvia quando a 1ª peça de um tamanho não caía num lugar generoso, nem agrupava peças com só UM lado igual (pedido explícito do usuário: "basta um dos lados ser igual pra agrupar se o veio permitir"). Reescrito: `PlanoCorteNestingService::agruparEmBlocos()` roda ANTES de qualquer encaixe, juntando peças em "blocos" (faixas retas coladas) em 2 passadas gulosas — tamanho EXATO primeiro, depois só largura OU só comprimento em comum — cada bloco então entra no mesmo Best Area Fit de sempre, tratado como se fosse 1 peça só. `quantidade_cortes` deixou de ser `count(peças)` (proxy) e virou uma contagem real: cortes de isolamento do bloco (0-2, mesma régua do `dividirRetangulo()`) + (N-1) cortes de seccionamento internos — mostrado agora no cabeçalho de cada chapa e numa coluna nova "Cortes" na tabela de Chapas do Materiais.
   - **Verificado** (fixtures, `verificar6.php` + `diag_blocos.php`): as 13 peças "Lateral de Gaveta 15mm" (o caso 14-21 citado) caem AGORA numa coluna só (mais 3 numa segunda coluna) — confirmado visualmente (render `wkhtmltopdf`, chapa MDF/Branco/15mm nº1): 1 corte de fora a fora + seccionamentos, exatamente o pedido.
   - **Trade-off medido, avisando o usuário**: nas fixtures de teste, o total de chapas subiu de 8 pra 9 (o grupo Branco/15mm passou a precisar de 3 chapas) — reservar uma faixa reta pra peças iguais é menos flexível que espalhar cada peça no melhor buraco disponível peça a peça, então custa um pouco de aproveitamento em troca de menos cortes/mais prático de operar. Isolamos a causa (script de teste com o agrupamento por tamanho EXATO desligado deu o MESMO resultado — a perda vem só do agrupamento exato, não do agrupamento por 1 lado só) — decisão de aceitar esse trade-off ou pedir ajuste fica com o usuário, não decidimos sozinhos.

2. **Título e desenho separados de novo.** Causa provável: `$larguraDisponivelMm`/`$alturaDisponivelMm` (área do desenho) e a altura do cabeçalho eram só ESTIMATIVAS ("margens de ~12mm") — sem fixar a margem de página de verdade, o Dompdf usa o default dele (pode ser maior), e o cabeçalho de 3 linhas sem limite de largura podia crescer mais que o previsto. Corrigido com margem EXPLÍCITA (`@page { margin: 8mm; }`, dá 277×194mm úteis, conhecidos) + cabeçalho da Preview de Corte reduzido a 2 linhas curtas com `white-space:nowrap;overflow:hidden;text-overflow:ellipsis` (altura previsível, não cresce com nome comprido) + cabeçalho e desenho dentro de um `<div class="pagina-chapa">` só, com `page-break-inside:avoid` como reforço (SEM `<table>` — a rodada 5 já provou que tabela + `page-break-before` gera páginas em branco no Dompdf real). Testado com `wkhtmltopdf` (não o Dompdf real, segue indisponível neste ambiente — Packagist bloqueado pelo proxy): 10 páginas certas (1 Materiais + 1 por chapa), título e desenho sempre juntos, zero páginas em branco. **Ainda não confirmado com o Dompdf real** — é a medida determinística (margem+cabeçalho fixos) que deve resolver por si só, mesmo se `page-break-inside` não funcionar perfeitamente.

**Pendências**: (a) usuário precisa confirmar se o trade-off de ~1 chapa a mais por causa do agrupamento é aceitável, ou se prefere agrupar só quando o grupo for grande o bastante pra compensar; (b) confirmar com o Dompdf real que título+desenho realmente ficam juntos agora.

### Revisão 2026-09-14 (rodada 7) — meta VOLTOU a ser perseguir o aproveitamento real do Promob

Usuário topou o trade-off da rodada 6 (mandou uma imagem de referência nova, a mesma chapa Branco/15mm de sempre) e pediu pra perseguir o aproveitamento real do Promob "com a lógica de o que encaixa melhor numa faixa horizontal e/ou vertical, evitando sobras irregulares" — revertendo a decisão mais antiga desta tarefa de "não perseguir %". Também sugeriu 2 bibliotecas prontas (`dagmike/BinPacking`, `6px/bin-packer`); conferimos os dois READMEs (`WebFetch`) antes de decidir: `dagmike/BinPacking` é um port do `juj/RectangleBinPack` (a implementação de referência do MaxRects — mesma família de heurísticas Best Area Fit/Best Short Side Fit que já íamos implementar), mas nenhuma das duas trata kerf, veio travado, guilhotina (corte ponta a ponta) ou agrupamento de peças iguais — o cerne do problema aqui —, então seguimos com implementação própria em vez de adicionar dependência nova (que também não daria pra testar aqui, Packagist bloqueado).

Duas técnicas de bin-packing 2D estabelecidas (públicas, não segredo do Promob — Jylänki, "A Thousand Ways to Pack the Bin"): (1) peças de tamanho EXATO iguais agora viram uma GRADE (rows×cols escolhida testando todas as combinações que cabem, ficando com a de menos posições vazias) em vez de só uma faixa 1D — `PlanoCorteNestingService::montarBlocosGrade()`; (2) o encaixe roda com 2 critérios de "melhor encaixe" (Best Area Fit de sempre + Best Short Side Fit, que reduz sobras finas/irregulares). MEDIDO que grade sozinha não é sempre melhor que faixa 1D (ajudou 1 grupo, atrapalhou outro) — por isso `encaixarGrupo()` roda o encaixe completo em 4 combinações (grade/faixa × área/lado curto) e fica com a que abrir menos chapas. Resultado nas fixtures: de volta a 8 chapas no total (empatando com a rodada 5, que não tinha agrupamento de verdade), com o agrupamento aplicado só onde ele realmente ajuda — verificado geometricamente (`verificar6.php`) e visualmente (`wkhtmltopdf`): grades reais aparecem quando o fator é bom (ex. 15 peças em grade 3×5), e viram faixa 1D quando o fator é ruim (ex. 13 peças, primo, só cabe 1×13 mesmo).

Ainda não é o algoritmo do Promob — é ajuste empírico, "cada caso é um caso" (usuário). Pendência: seguir testando contra PDFs reais do usuário e refinando.

### Revisão 2026-09-14 (rodada 8) — "recorte" de bloco: usa sobra já aberta antes de abrir chapa nova

Usuário pediu pra olhar pra sobra ("temos de olhar um pouco para sobra tentar não fracionar (...) podíamos encaixar as peças menores e a sobra ficar melhor (...) agrupar peças menores que possam encaixar nos vãos") e deu um alvo concreto pro grupo Branco/15mm: 2 chapas, a 1ª com 90%+ (igual ao PDF real do Promob que mandou).

**Achado importante antes de mexer no algoritmo**: os scripts de teste do sandbox (não o código enviado, só os scripts de verificação) vinham contando o projeto real "260000" DUAS vezes — ele tem 2 exports XML (`veio vertical.xml`/`veio horizontal.xml`) de um teste controlado de veio anterior, e os 2 juntos duplicavam o material desse projeto. Isso tornava a pergunta "por que 3 chapas em vez de 2" sem resposta possível — geometricamente impossível caber em 2 chapas com o material duplicado. Corrigido nos scripts (`verificar6.php`/`render_test.php`); o `PlanoCorteNestingService.php` em produção nunca teve esse bug (sempre usa os componentes reais do Projeto do usuário, não esses arquivos de fixture).

Com a fixture corrigida, isolamos a causa real da perda de aproveitamento: um script de rastreamento (log de cada decisão de `encaixarBloco()`) mostrou um bloco de 4 peças "Lateral 15" coladas (350×1412mm) abrindo chapa nova mesmo a chapa 1 tendo várias sobras de 400-1000mm de altura já abertas — só que nenhuma delas com os 1412mm inteiros que o bloco precisava. Um bloco só era testado INTEIRO contra as sobras; se não coubesse inteiro em nenhuma, ia pra chapa nova, mesmo sobrando espaço suficiente pra ALGUMAS das peças dele.

Correção (`encaixarBloco()`, `reconstruirBloco()`, `buscarMelhorGrade()`, `colocarBlocoNoRetangulo()`): quando um bloco não cabe inteiro em nenhuma sobra já aberta, tenta um "recorte" dele ANTES de abrir chapa nova — testa as N-1 primeiras peças, depois N-2, ... até 1, e fica com a MAIOR fatia que couber numa chapa JÁ ABERTA; o resto volta recursivamente pro mesmo encaixe (podendo recortar de novo, ou só aí abrir chapa nova). Fisicamente é só mais um corte reto dividindo a faixa/grade em duas partes menores — continua 100% guilhotina.

**Medido** (fixtures corrigidas, kerf 4mm): Branco/15mm foi de 65,4%/35,2% (2 chapas) pra 83,9%/16,8% (2 chapas) — perto do "1ª chapa 90%+, 2ª com o restante" que o usuário descreveu. Grafite/15mm foi de 2 chapas (56,9%/7,4%) pra 1 chapa só (64,3%). Total de chapas de todos os grupos nas fixtures: 7 → 6. Nenhum grupo regrediu; geometria continua OK (`verificar6.php`, sem sobreposição/fora dos limites). Visualmente (`wkhtmltopdf`) a sobra da chapa 1 ficou bem mais consolidada, não mais um bloco fragmentado grande.

Ainda não chega nos 90%+ exatos na 1ª chapa (fica ~84%) — não reportado como "resolvido 100%", é uma melhoria medida e honesta. "Cada caso é um caso" segue valendo.

### Revisão 2026-09-14 (rodada 9) — esgota a chapa mais antiga antes de espalhar pra outra já aberta

Usuário reparou no resultado da rodada 8 (83,9%/16,8%) que ainda dava pra ver peças da chapa 2 que cabiam na chapa 1, e sugeriu o caminho certo: começar pelas peças maiores agrupando as iguais, depois analisar os espaços e encaixar as menores nas sobras. Conferimos com um rasterizador (grid de 5mm sobre as peças já colocadas) que a chapa 1 realmente tinha ~15% de área livre de verdade, e que os retângulos livres da bookkeeping batiam com essa área real — não era bug de fragmentação. O problema era outro: `encaixarBloco()` busca o melhor encaixe GLOBAL (menor sobra entre TODAS as chapas já abertas); quando a chapa 2 tinha sido aberta pra uma peça grande que não cabe na chapa 1 de jeito nenhum (a "Prateleira Linear" 513×770mm), toda peça menor processada DEPOIS achava um encaixe fácil na chapa 2 (quase vazia) e nunca chegava a tentar o "recorte" (rodada 8) contra as sobras que ainda existiam na chapa 1.

Correção (`encaixarBlocoSequencial()`, nova forma de encaixar, testada lado a lado com a de sempre): em vez de buscar globalmente, ESGOTA cada chapa já aberta, na ordem (mais antiga primeiro) — tenta o bloco inteiro, se não couber recorta (N-1, N-2...) só nela, e só passa pra próxima chapa quando nem 1 peça coube na atual. `encaixarGrupo()` agora testa 8 combinações (grade/faixa × área/lado curto × global/sequencial) e fica com a melhor — precisou de um desempate novo no score (`-maior aproveitamento`), porque a SOMA do aproveitamento não muda só por redistribuir peças entre chapas já abertas, então sem esse desempate as duas formas empatavam sempre.

**Medido** (fixtures corrigidas, kerf 4mm — o padrão real do modo Serra): Branco/15mm foi de 83,9%/16,8% pra **92,7%/8%** — a chapa 1 ficou com as 34 peças que cabem nela de algum jeito, a chapa 2 só com a 1 peça (Prateleira Linear) que não cabe em lugar nenhum da chapa 1. Bateu o alvo do usuário ("1ª chapa 90%+, 2ª só com o resto"). Nenhum outro grupo regrediu, geometria OK, total de chapas nas fixtures continua 6 (a consolidação não muda quantas chapas abrem, só onde cada peça cai).

### Revisão 2026-09-14 (rodada 10) — faixa 1D como alternativa da grade em cada fatia do "recorte"

Usuário rastreou o PDF da rodada 9 peça por peça e reparou que 1 peça de um grupo de 8 idênticas ("Lateral de Gaveta 15mm") tinha ficado sozinha, separada das outras 7, e pediu explicitamente pra ir agrupando das maiores pras menores mantendo cada grupo junto. Rastreando de novo (log de cada decisão de encaixe), achamos a causa: o "recorte" (rodadas 8/9) só tenta GRADE (rows×cols) pra cada fatia — quando a grade de 8 não cabia mas a de 7 cabia, sobrava 1 peça isolada, mesmo quando uma FAIXA 1D reta das 8 (mais comprida e mais fina que a grade) cabia perfeitamente no mesmo lugar.

Correção: pra cada tamanho testado no recorte, se o grupo é de peças EXATAS (toda faixa 1D também é válida pra elas), tenta grade primeiro e só se ela não couber tenta faixa 1D antes de cair pro tamanho menor. Aplicado nos dois caminhos de recorte. **Medido**: as 8 peças que ficavam 7+1 separadas agora formam 1 faixa reta de 8 só (confirmado visualmente) — mesmo aproveitamento de antes (92,7%/8%) e 1 corte a menos no total. Nenhum outro grupo mudou.

O usuário também notou 2 outros grupos ainda fragmentados (Contra Frente/Posterior de Gaveta em pares, Sarrafo em peças soltas) — rastreado e confirmado: aí nem a grade nem a faixa 1D de mais de 2 peças cabem em nenhuma sobra já aberta naquele ponto (já ocupada pelos blocos maiores processados antes) — não é mais "só testar 1 forma", é a sobra disponível mesmo sendo pequena ali. Resolver isso de verdade precisaria de uma reordenação/otimização bem mais ampla (testar ordens de processamento diferentes, ou um solver de bin-packing 2D de verdade). Fica como pendência conhecida — combinado com o usuário reverter a rodada 10 se a próxima tentativa nessa linha não render mais nada.

### Revisão 2026-09-14 (rodada 11) — grupos EXATOS pequenos com dimensão em comum se unem (Passo 1.5); Sarrafo só ficou parcialmente resolvido

Usuário pediu pra unificar os 4 "Sarrafo 15mm" (2× 770×70mm + 2× 370×70mm, mesma largura 70mm) numa faixa só, deixando explícito que só esse caso precisa — Contra Frente/Posterior de Gaveta pode continuar avulso.

**Primeira tentativa (revertida)**: mudar o Passo 2 de `agruparEmBlocos()` pra rodar sobre TODAS as peças (não só as que sobraram do Passo 1) e competir num único `formarGruposGulosos()` combinado. Testado: regrediu o Branco/15mm de 92,7% pra 90,9%, mudou a composição da chapa 2 (1→9 peças), subiu o total de cortes (105→114) e mexeu até em grupos sem nenhuma relação com o pedido (Branco/18mm 12→14 cortes). Reverção imediata (`git`-style: cópia do checkpoint da rodada 10 de volta por cima do arquivo em produção), confirmada por `verificar_full.php` batendo exatamente com os números da rodada 10 de novo.

**Segunda tentativa (a que ficou)**: em vez de mexer no Passo 2 (largo demais), um "Passo 1.5" novo e restrito — `unirGruposPequenosPorEixoComum()` — que roda só entre o Passo 1 e o Passo 2: pega os grupos de tamanho EXATO que o Passo 1 já formou, filtra só os PEQUENOS (até 3 peças — de propósito, pra nunca tocar grupos grandes já funcionando bem, tipo a grade de 8 "Lateral de Gaveta" ou o grupo de 4 "Base 15"), e une os que compartilham largura OU comprimento — testando as duas leituras (a peça pode girar, então um grupo pequeno "oferece" tanto a largura quanto o comprimento da orientação em que nasceu como dimensão candidata pra achar par com outro grupo).

**Bug encontrado e corrigido na mesma rodada**: a primeira versão desse Passo 1.5 comparava só a orientação ORIGINAL de cada grupo pequeno (a que o Passo 1 escolheu, meio arbitrária) — pros Sarrafos, isso comparava largura=70 (achava o par certo) mas montava a faixa unida SEMPRE empilhando ao longo do comprimento útil (1810mm), quando o jeito que cabe de verdade é girando o grupo inteiro e empilhando ao longo da LARGURA útil (2730mm) — a soma das 4 peças (2292mm) só cabe no segundo jeito. `php -l` passou mas o teste geométrico (`verificar_full.php` + um script isolado testando só `agruparEmBlocos()` nos 4 sarrafos) mostrou que a união nunca acontecia de verdade (2 grupos de 2 continuavam separados). Corrigido testando as duas formas de virar faixa (valor comum vira largura fixa OU comprimento fixo, girando as peças que puderem) e ficando com a que cabe.

**Medido** (fixtures corrigidas, kerf 4mm): zero regressão — os 5 grupos de material mantêm exatamente o mesmo aproveitamento/peças por chapa de antes (Branco/18mm 37,9%/10pç; Aglom/Branco/6mm 8,8%/2pç; Branco/15mm 92,7%/34pç + 8%/1pç; Branco/6mm 36,9%/8pç; Grafite/15mm 64,3%/20pç). Total de cortes caiu de 105 pra 103 (1 a menos no Branco/15mm, 1 a menos no Grafite/15mm — este último por um efeito colateral positivo: o Passo 1.5 também uniu peças "Fundo Travessa 15" que tinham o mesmo problema).

**Resultado real pro pedido específico (Sarrafo)**: o Passo 1.5 agora forma corretamente 1 grupo só com as 4 peças (confirmado isolado via Reflection, testando só `agruparEmBlocos()` com os 4 sarrafos) — o bug de agrupamento está corrigido. MAS, ao testar o pipeline completo, a posição final das 4 peças na chapa NÃO MUDOU em relação à rodada 10: 3 delas (as que a fixture chama de "32/33/34" no rastreamento) ficam próximas na faixa inferior (com um desalinhamento de 8mm entre 2 delas — o mesmo "conflito" que o usuário já tinha notado e disse não ser preocupante, contanto que a sequência de corte tire essa faixa primeiro), e a 4ª peça continua isolada lá em cima, perto da coluna de Contra Frente/Posterior. Rastreado o motivo: mesmo com as 4 formando 1 bloco só na hora de AGRUPAR, na hora de ENCAIXAR o "recorte" (rodadas 8/9/10) ainda quebra esse bloco em fatias quando não acha 1 retângulo livre único grande o bastante — e o espaço abaixo das fileiras de peças anteriores é genuinamente fragmentado ali (as fileiras acima terminam em alturas Y diferentes, formando uma "escada", não uma linha reta), então fisicamente não existe 1 retângulo guilhotina só cobrindo a largura toda naquela altura. Isso bate com o que a rodada 10 já tinha documentado como pendência (Sarrafo/Contra Frente fragmentados por falta de espaço, não por bug de agrupamento) — o Passo 1.5 corrigiu a PARTE que era bug (agrupamento), mas a parte que é limite geométrico real da fileira acima continua exigindo uma reordenação mais ampla (não tentada aqui, por ser mais arriscada e o usuário ter pedido pra reverter se não rendesse).

**Decisão**: mantido (não revertido) porque não regride nada e corrige um bug latente de verdade (a leitura de orientação errada no agrupamento por dimensão comum) — mas reportado ao usuário como parcialmente resolvido, não como "pedido atendido 100%".

## Investigação (2026-09-14): trocar o motor de nesting por um engine externo (`packingsolver`), e o que dá pra aproveitar do SketchUp

Depois da rodada 11, o usuário perguntou se dava pra usar o **OpenCutList** (extensão de corte do SketchUp, tem desenho técnico parecido com o Promob Cut Pro) como motor do Plano de Corte, aproveitando também um item futuro de roadmap ("itens do SketchUp"). Investigação em duas frentes, com fontes primárias (repositórios/docs oficiais) e um teste EMPÍRICO de verdade (não só teórico) contra a fixture real.

### OpenCutList em si: não dá pra usar como motor

Ele só roda DENTRO do SketchUp desktop (Ruby+JS+C++, sem CLI/servidor/headless documentado em lugar nenhum) e a licença é GPLv3 (copiar o código dele pra dentro do Perseu traria obrigação de copyleft). Export de diagrama de corte é SVG/DXF (não gera PDF). **Descartado como motor direto.**

### O achado real: `fontanf/packingsolver` — motor C++, MIT, já testado aqui

Cavando o repositório do OpenCutList, o algoritmo de empacotamento de verdade que ele usa não é dele — é um projeto separado, `fontanf/packingsolver` (github.com/fontanf/packingsolver), C++, **licença MIT** (uso livre em projeto fechado). Tem um solver `rectangleguillotine` — exatamente o nosso caso (só cortes de ponta a ponta) — com suporte NATIVO a: kerf (`--cut-thickness`), veio travado por peça (coluna `ORIENTED` no CSV de peças — 1 peça não pode rotacionar, exatamente nosso "pode_rotacionar"), aparas de borda por lado com tipo Hard/Soft (`LEFT_TRIM`/`RIGHT_TRIM`/`BOTTOM_TRIM`/`TOP_TRIM` no CSV de chapas — cobre nossa "limpeza de bordas" de graça), múltiplas chapas disponíveis (`COPIES` no CSV de chapas), e guilhotina irrestrita (`--number-of-stages-unlimited`).

**Clonado e COMPILADO de verdade no sandbox** (não só lido a documentação): `git clone` + `cmake` + build C++ (Boost 1.84, HiGHS, vários sub-repos do mesmo autor via FetchContent) — demorou ~10min com 2 núcleos. Único obstáculo: o proxy do sandbox bloqueia download direto de `.zip` do GitHub (retorna 403) — contornado clonando o HiGHS via `git` normal e apontando `FETCHCONTENT_SOURCE_DIR_HIGHS` pra essa cópia local, sem mudar nada do projeto. Também precisou `liblapack-dev`/`libblas-dev` (não vinham instalados). Isso é específico do NOSSO sandbox — no ambiente Windows do usuário (ou num CI), provavelmente nenhum desses dois problemas aparece.

**Teste empírico, MESMA fixture real (Branco/15mm, 35 peças, 2 chapas 2750×1830, kerf 4mm, limpeza 10mm)**:
- Rodando `--objective bin-packing` (minimizar Nº de chapas) puro: confirma 2 chapas são o mínimo (prova matemática, gap 0%) — mas ele DISTRIBUI as peças mais ou menos igual entre as 2 (76,3%/24,4%), porque essa métrica não tem preferência por "encher a 1ª, deixar só o resto na 2ª" — o MESMO problema que resolvemos sozinhos na rodada 9 com o desempate `-maior aproveitamento`.
- Rodando em modo **knapsack por chapa** (enche 1 chapa o máximo possível, tira essas peças, repete pro resto — o mesmo padrão sequencial que já usamos): chapa 1 ficou com 31 das 35 peças e **95,3% de aproveitamento** (contra os nossos 92,7%) — e olha que nem tinha achado o ótimo ainda (gap 4% restante em 20s de busca, ou seja, dava pra melhorar mais com mais tempo). Chapa 2 ficou com as 4 peças restantes (2 sarrafos de 770×70 + 2 "Lateral de Gaveta" 478×130), 4,7% de aproveitamento.

**Conclusão honesta**: o motor deles, usado do jeito ingênuo (bin-packing puro), teria REGREDIDO nosso resultado (76%/24% é pior que nosso 92,7%/8% pro padrão que o usuário quer). Usado do jeito certo (knapsack sequencial, chapa por chapa — a MESMA ideia da nossa rodada 9), ele BATE nosso número (95,3% > 92,7%) usando um solver de busca em árvore de verdade (não uma heurística gulosa como a nossa), e de quebra já resolve kerf/veio-travado/aparas de borda que hoje é código nosso. Mas ele TAMBÉM não junta peças iguais numa faixa só por padrão (os 2 sarrafos que sobraram pra chapa 2 não vieram grudados um no outro nem nos 2 que ficaram na chapa 1) — ou seja, o problema da rodada 11 (Sarrafo) não se resolve de graça só trocando de motor; precisaríamos de uma camada de agrupamento nossa por cima dele, do jeito que já temos hoje.

**Como integraria**: compilar o binário 1 vez (CI/GitHub Actions pra Windows, ou aqui mesmo cross-compilando) e versionar o `.exe` no repo, chamado do PHP via `Symfony\Process` (mesmo padrão que já usamos com `wkhtmltopdf`) — não precisa recompilar em cada request nem no deploy. Gera um `items.csv`/`bins.csv` temporário, chama o binário, lê o `solution.csv` (formato: `PLATE_ID,NODE_ID,X,Y,WIDTH,HEIGHT,TYPE,CUT,PARENT` — árvore de cortes, dá pra reconstruir a posição de cada peça e a sequência de corte de verdade a partir do `PARENT`). Licença MIT permite isso sem restrição.

### SketchUp: não dá pra ler `.skp` fora do app de forma prática hoje

Existe sim um jeito oficial de ler/escrever `.skp` sem o SketchUp completo — o **SketchUp C API / SDK** da própria Trimble (`developer.trimble.com/docs/sketchup`) — mas é C++, só Windows/macOS (sem Linux), com dicionário de atributos (a mesma metadata que Dynamic Components/extensões usam) acessível via API. Achei também um parser open-source "clean-room" (`OpenSKP`, MIT, multi-linguagem) mas está em estágio bem inicial (v0.1.0), não daria pra confiar em produção ainda. A API Ruby do SketchUp (a mais documentada) só roda DENTRO do app desktop, sem modo headless.

**Caminho realista pro item futuro "itens do SketchUp"**: não é ler o `.skp` direto — é replicar o padrão que já usamos com o Promob: o usuário roda uma extensão no SketchUp (Ruby, pode inclusive reaproveitar o mesmo esquema de atributos do OpenCutList — material/espessura/veio) que exporta um JSON/CSV com a lista de peças, e o Perseu importa esse arquivo do mesmo jeito que importa o XML do Promob hoje. O recurso nativo "Generate Report" do próprio SketchUp já exporta CSV de Dynamic Components (mas não achei confirmação de automação via Ruby API — provavelmente só manual).

### Outras engines de nesting 2D open-source consideradas (e descartadas)

Pesquisadas pra não ficar restrito só ao achado do OpenCutList: **SVGnest/Deepnest** (MIT, mas é nesting de forma IRREGULAR/polígono, não guilhotina retangular — modelo errado pro nosso caso, e o Deepnest original está abandonado); pacotes PHP no Packagist (`dvdoug/boxpacker` é 3D pra caixa de frete, `padam87/bin-packer`/`dagmike/bin-packing` são 2D mas sem guilhotina) — **nenhum pacote PHP nativo faz guilhotina 2D**; **Google OR-Tools** (Apache-2.0, sem modelo de guilhotina pronto, sem binding PHP, exigiria montar um microserviço Python à parte); **libnest2d** (do PrusaSlicer, LGPL-3.0, também é nesting irregular, não guilhotina). Achado alternativo interessante mas não testado: **opcut** (github.com/bozokopic/opcut, GPL-3.0, guilhotina com kerf, tem CLI e servidor HTTP/REST) — mesma categoria de uso do packingsolver (processo externo, não copiar código), mas packingsolver already comprovado empiricamente e com licença mais permissiva (MIT), então não vale a pena testar o opcut também por ora.

### Recomendação

Não decidido nada ainda — é uma investigação, a decisão de migrar (ou não) fica com o usuário. Mas os números favorecem considerar o `packingsolver` como upgrade de motor: mesma fixture real, aproveitamento melhor (95,3% vs 92,7%) usando busca em árvore de verdade em vez de heurística gulosa, e reduz código nosso (kerf/veio-travado/aparas de borda viram configuração do solver, não lógica PHP pra manter). O trade-off é a complexidade operacional nova: manter um binário C++ compilado versionado (não é `composer install`), e ainda precisaríamos de uma camada de agrupamento por cima pra resolver o caso Sarrafo/continuidade (isso NÃO vem de graça com o motor). SketchUp como fonte de dados é uma frente separada e mais distante — não tem atalho técnico pronto, seria construir uma extensão de exportação nossa, do mesmo jeito que hoje dependemos do XML do Promob.

## Dropdown "Otimizadores" (2026-09-14) — Nativa / Packing Solver lado a lado

Decisão do usuário depois da investigação acima: "podemos manter o nosso como está... podemos criar um dropdown, otimizadores com Opção na lista: Nativa que seria o nosso, e Packing Solver e direcionar para o novo modelo, ao longo do projeto podemos aprender mais e decidir qual ficará." Implementado exatamente assim — EXPLORATÓRIO, os dois motores convivem, nada foi decidido de vez.

### O que mudou

- **`PlanoCorteNestingService.php` (o algoritmo nativo) — ZERO alterações.** Confirmado por checksum idêntico ao checkpoint da rodada 11 depois de toda esta tarefa.
- **`src/Services/PlanoCortePackingSolverService.php` (NOVO)** — mesma assinatura e MESMO formato de retorno de `PlanoCorteNestingService::gerar()` (substituto plug-and-play). Repete a MESMA lógica de agrupamento por material/expansão de repetição/convenção de eixo do veio que o nativo tem (duplicada de propósito — o pedido foi manter o nativo intocado, então esta classe não depende dele). Pra cada grupo de material, roda um loop "knapsack sequencial de 1 chapa por vez": gera `items.csv`/`bins.csv`, chama o binário do packingsolver via `Illuminate\Support\Facades\Process`, lê o `solution.csv` (árvore de cortes de guilhotina — folha = `NODE_ID` que nunca aparece como `PARENT` de ninguém; cortes = nº de folhas − 1, teorema confirmado empiricamente), tira as peças colocadas do "pool" e repete pro que sobrou até esvaziar ou até um limite de segurança de iterações. Kerf vira `--cut-thickness`; limpeza de bordas vira `LEFT_TRIM`/`RIGHT_TRIM`/`BOTTOM_TRIM`/`TOP_TRIM` da chapa em tamanho CHEIO (as coordenadas que voltam já incluem o deslocamento da margem, confirmado testando — não precisa somar `limpezaBordas` de volta). Veio travado vira `ORIENTED=1`.
- **Achado NOVO nesta rodada (bug do binário, não nosso)**: `--number-of-stages-unlimited` — que a investigação anterior tinha listado como parâmetro a usar — faz o binário QUEBRAR ("wrong item dimensions", erro interno do `SolutionBuilder`) em instâncias pequenas (testado com só 2 peças, reproduzido de forma isolada e determinística). Sem essa flag (deixando o solver usar o limite de estágios padrão dele), o mesmo teste com as 5 fixtures reais roda sem travar em NENHUM grupo, com resultado praticamente igual (95,8%/4,9% no grupo principal, contra os 95,3%/4,7% medidos antes — ainda melhor que os 92,7%/8% do nativo). `PlanoCortePackingSolverService` NÃO usa mais essa flag.
- **`config/comercial.php` (NOVO — 1º config deste plugin)** — `packingsolver.binario` (caminho do executável, lido de `PACKINGSOLVER_BINARIO` no `.env` da raiz, `null` por padrão) e `packingsolver.tempo_limite_segundos` (padrão 8s por chapa, `PACKINGSOLVER_TEMPO_LIMITE`). Registrado via `mergeConfigFrom()` em `ComercialServiceProvider::packageRegistered()`.
- **`PlanoCorteRelatorioService`** ganhou um 4º parâmetro no construtor, `$otimizador` (padrão `'nativa'`), que só decide qual das duas classes chamar — o resto (montagem do HTML/PDF) é idêntico pros dois motores.
- **`EditProjeto.php`** — a tela de "Produção" ganhou `Select::make('otimizador')` (opções "Nativa"/"Packing Solver (experimental)", padrão "Nativa") como PRIMEIRO campo do form, antes do equipamento de corte. O `->action()` agora envolve a geração num `try/catch` — se o Packing Solver falhar (binário não configurado, timeout, erro do solver), mostra uma `Notification` de erro clara em vez de estourar um erro 500; o usuário pode tentar de novo com "Nativa".
- Chaves de tradução novas em `pt_BR`/`en` (`form-actions.producao.form.otimizador*` e `form-actions.producao.notification-erro`).

### Validado nesta tarefa (não só no código, testado de verdade)

Reescrita standalone da lógica nova (sem depender do Laravel) rodada contra o binário REAL compilado no sandbox e as MESMAS fixtures reais desta tarefa (Promob XML de verdade, `PromobXmlParser`): todos os 5 grupos de material processados sem erro, geometria conferida (sem sobreposição, nenhuma peça fora da área útil) com o mesmo script de verificação (`x/y/xMax/yMax` contra os limites, par a par contra sobreposição) usado nas rodadas anteriores. Script fica em `packingsolver_test/teste_servico.php` no scratchpad da sessão (não versionado no plugin).

### Pendência real, ainda não resolvida — o binário pro ambiente do usuário

O único binário que existe até agora foi compilado NESTE sandbox (Linux, ligado dinamicamente a `liblapack`/`libblas`/`libbz2`/`libz`/`libstdc++`/`libgfortran`). O ambiente do usuário é Windows (`C:\Users\Projeto Studio\Perseu-FA-fonte-completa` — ver "Ambiente local" no `CLAUDE.md` da raiz; a pasta antiga `C:\Perseu\PerseuFA_comercial` citada aqui em revisões anteriores já foi removida) — ainda falta compilar (ou conseguir um binário pronto) do `fontanf/packingsolver` pro Windows antes de "Packing Solver" funcionar de verdade lá. Até isso acontecer, escolher essa opção no dropdown vai mostrar a notificação de erro ("binário não encontrado/configurado") — comportamento esperado e claro, não um bug. O repositório documenta build via CMake + Visual Studio no Windows (plataforma oficialmente testada por eles, ao contrário deste sandbox Linux) — próximo passo natural quando o usuário quiser testar essa opção de verdade.

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
