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
