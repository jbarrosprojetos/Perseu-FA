# Plugin `perseu/comercial`

> Convenções e decisões específicas deste plugin. Para o que vale para
> o projeto inteiro (convenção de Model de cadastro de negócio, regras
> de nomenclatura do sistema, comandos DDEV, etc.), veja o `CLAUDE.md`
> da raiz. Para o histórico narrado (o "porquê" de uma decisão antiga),
> veja a seção "Ver também" no final deste arquivo.

Gestão comercial de Projetos do Perseu — o cadastro de negócio central
da F.A. Marcenaria (marcenaria industrial).

## Estado atual (Models e navegação)

- **Models**: `Projeto`, `TipoProjeto`, `SituacaoProjeto`,
  `ReferenciaPreco` (`plugins/perseu/comercial/src/Models/`).
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
persistido, `dehydrated(false)`, com 7 opções fixas: Item Avulso, Item
de Linha, Promob Plus, Promob Start, Sketchup Hellomob, Sketchup
CutList, CortCloud + botão "Inserir" com notificação placeholder). A
lógica real de cada origem e a listagem dos itens já inseridos ficam
para uma etapa futura (depende de uma tabela de Itens que ainda não
existe).

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

### Cabeçalho estilo planilha para "Item Avulso" (2026-09-03, colunas corrigidas 2026-09-03)

Ao clicar "Inserir" com "Item Avulso" selecionado, em vez da
notificação placeholder aparece um cabeçalho de colunas — `Grid::make(24)`
com 10 colunas (`columnSpan`: Item 1, Referência 3, Descrição 6, Qtde.
1, Valor Unit. 2, Valor Total 3, Imp.% 1, Porc.% 1, Custo Unitário 3,
última coluna sem rótulo 3 — soma 24), no espaço reservado logo abaixo
do Select+Botão, dentro da mesma Section "Itens". A inspiração é a aba
"00" do Excel `260000 Cliente Padrão Proposta 00.xlsm` usado hoje pela
F.A. Marcenaria. Todos os rótulos aparecem abreviados ("Qtde.", "Valor
Unit.", "Imp.%", "Porc.%") — **"Porc.%" é a mesma coluna que já passou
por "Desconto" → "Porcentagem" → "Porc.%"**, não confundir com uma
coluna nova; a última coluna (3) continua reservada, sem texto — sem
uso definido ainda (a coluna que ANTES se chamava "Total Custo" foi
renomeada para "Custo Unitário" em 2026-09-03, ver subseção seguinte —
representa o custo unitário do item, não mais um "total"). As outras 6
origens do dropdown continuam com a notificação placeholder normal; só
"Item Avulso" tem comportamento próprio até agora.

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
`Grid::make(24)` com os MESMOS `columnSpan` (1,3,6,1,2,3,1,1,3,3) traz
os campos de verdade — ainda nenhuma persistência (todos
`dehydrated(false)`, prefixo `novo_item_*`, fora do `$fillable` de
`Projeto`); a tabela `itens_projeto` (ou nome equivalente) e a ação
real de confirmar/salvar ficam pra uma próxima etapa.

- **Item** (1): `Placeholder` só-leitura, formato `###`. Ainda não há
  tabela de Itens pra contar registros reais — fixo em `"001"` por
  enquanto (todo Projeto "começa" sem nenhum item confirmado, já que
  confirmar/persistir é tarefa futura). Quando a tabela existir, trocar
  por uma contagem real de itens do Projeto.
- **Referência** (3): sem campo, só `Text::make('')` reservando a
  coluna (Item Avulso não usa essa coluna).
- **Descrição** (6): `RichEditor::make('novo_item_descricao')` sem
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
  manutenção contínua — não vale o custo/risco agora. Toolbar
  permanece sempre visível, com o conjunto DEFAULT completo.
- **Qtde.** (1) e **Porc.%** (1): `TextInput` `->numeric()->integer()`,
  `->live(onBlur: true)`, disparam o recálculo (ver fórmula abaixo).
  Porc.% SEM `->minValue()` — aceita negativo de propósito (acréscimo/
  desconto).
- **Valor Unitário** (2) e **Valor Total** (3): `TextInput`
  `->disabled()` — nunca digitados, só `$set()` pelo recálculo.
  `disabled()` já implica não-dehydratado, mas mantido
  `->dehydrated(false)` explícito por consistência com os outros
  campos `novo_item_*`.
- **Imp.%** (1): `TextInput` `->disabled()`, valor da Referência de
  Preços ATUALMENTE selecionada no Cabeçalho (`referencia_preco_id`,
  não necessariamente a salva no banco — se o usuário troca a
  Referência antes de clicar "Inserir", vale a escolha atual). **Achado
  de teste**: NÃO dá pra popular via `->default(fn (?Projeto $record) =>
  $record?->referenciaPreco?->imposto)` — o campo vive dentro de um
  Grid com `->visible()` condicional a `origem_item_inserida`, e o
  `fill()` inicial da página (Create/Edit) não hidrata campos que
  COMEÇAM escondidos; confirmado via `Livewire::test()` — o campo
  ficava sempre `null` mesmo com Referência vinculada, mesmo depois do
  Grid virar visível (visibilidade muda o RENDER, não re-executa
  `fill()`). Correção: a própria Action "Inserir" (quando
  `$origem === 'item_avulso'`) faz
  `$set('novo_item_imposto', ReferenciaPreco::find($get('referencia_preco_id'))?->imposto)`
  explicitamente — e aproveita pra resetar todos os `novo_item_*` a
  cada clique (linha nova sempre em branco). Sem Referência vinculada,
  fica em branco (`null`) — SEM aviso duplicado nesta coluna estreita
  (columnSpan 1); o aviso em vermelho já existe no campo "Referência de
  Preços" do Cabeçalho.
- **Custo Unitário** (3): `TextInput` `->numeric()->minValue(0)`
  (só positivo), `->live(onBlur: true)`, dispara o recálculo.
- **Última coluna** (3): `Actions::make([Action::make('confirmarItemAvulso')
  ->iconButton()])` — SEM ação real (notificação placeholder própria,
  chaves `notification.confirmar-pendente-*`); a persistência de fato é
  a próxima tarefa.
- **Botão "Mobilização e Frete"**: ao lado de "Inserir" (mesmo
  `Actions::make([...])`, `columnSpan` do Grid de 12 colunas aumentado
  de 2 pra 6 pra caber os dois botões), sem ação própria —
  reaproveita o MESMO par de traduções `notification.pendente-title`/
  `pendente-body` do placeholder das 6 origens, passando `'origem' =>
  'Mobilização e Frete'` como se fosse mais uma origem pendente
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
cálculo.

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
