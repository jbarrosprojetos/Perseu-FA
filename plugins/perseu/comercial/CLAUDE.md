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

Novo botão "Documentos" em `ProjetoResource\Pages\EditProjeto::getFormActions()`, posicionado ANTES de "Atribuir Processos" (a pedido explícito do usuário). Mesmo padrão dos outros botões-placeholder dessa página: sem ação real ainda, só uma notificação — o mecanismo de GERAR um documento a partir de um template com dados de um Projeto específico ainda não foi implementado.

**Pendência explícita, decidida mas não implementada** — mecanismo de marcadores de texto: o template Excel vai conter marcadores no formato `%Campo%` (ex.: `%Desc_Obra%`) em células específicas, que o Perseu vai reconhecer e substituir pelos dados reais na hora de gerar o documento de um Projeto. Ainda não há: (a) o dicionário/lista oficial de nomes de campo disponíveis, (b) o parser/substituidor desses marcadores, (c) a tela/ação de "gerar documento" que efetivamente usa um Documento (template) + um Projeto pra produzir o arquivo final. Também decidido: os documentos GERADOS (Proposta, O.S., Plano de Corte etc. de um Projeto específico) não ficam armazenados permanentemente no Perseu — são oferecidos pra download na hora e o usuário salva onde quiser (OneDrive, SharePoint, local). Um link direto do SharePoint/OneDrive foi descartado como forma de referenciar o template-base porque esses links são amarrados a usuário/tenant, podem expirar e mudam se o arquivo for movido — por isso o template fica em arquivo físico dentro do próprio Perseu.

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
