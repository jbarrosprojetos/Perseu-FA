# Plugin `perseu/auditoria`

> Convenções e decisões específicas deste plugin. Para o que vale para
> o projeto inteiro (convenção de Model de cadastro de negócio, regras
> de nomenclatura do sistema, comandos DDEV, etc.), veja o `CLAUDE.md`
> da raiz. Para o histórico narrado (o "porquê" de uma decisão antiga),
> veja a seção "Ver também" no final deste arquivo.

Auditoria (log de atividade) para os cadastros de negócio do Perseu —
central única de auditoria + Lixeira central, consumida por qualquer
outro plugin que tenha um Model de cadastro de negócio (ver "Convenção
para Model novo de cadastro de negócio" no `CLAUDE.md` da raiz, passos
2, 6 e 7).

## Estado atual

- `Perseu\Auditoria\Traits\LogsBusinessActivity` — trait que qualquer
  Model de cadastro de negócio de outro plugin usa (`perseu/comercial`,
  `perseu/pessoas` hoje).
- `Perseu\Auditoria\Support\SubjectTypeCatalog` — mapeamento único
  FQCN → rótulo/módulo/referência amigável/escopo de busca.
- `Perseu\Auditoria\Support\TrashCatalog` — lista de Models com
  Lixeira de UI de verdade (subconjunto do catálogo acima).
- `Perseu\Auditoria\Filament\Resources\AuditoriaResource` — Central de
  Auditoria (Configurações → Auditoria).
- `Perseu\Auditoria\Filament\Pages\Lixeira` — Lixeira Central
  (Configurações → Lixeira).
- `Perseu\Auditoria\Policies\ActivityPolicy` — controla o acesso à
  página central de Auditoria.

## Auditoria e Lixeira — arquitetura atual

- **Não há aba "Atividades" em registros individuais.** A ÚNICA tela
  de auditoria é a Central: Configurações → Auditoria
  (`Perseu\Auditoria\Filament\Resources\AuditoriaResource`, clusterizado
  em `Webkul\Support\Filament\Clusters\Settings`). Permissão:
  `view_any_auditoria_auditoria`/`view_auditoria_auditoria`.
- `Perseu\Auditoria\Support\SubjectTypeCatalog` é o mapeamento único
  FQCN → rótulo/módulo/referência amigável/escopo de busca para os 10
  Models hoje auditados (Projeto, ItemProjeto, TipoProjeto,
  SituacaoProjeto, ReferenciaPreco — módulo Comercial; PessoaFisica,
  PessoaJuridica, CategoriaPessoa, Setor, Endereco, Contato — módulo
  Pessoas). `ItemProjeto` (2026-09-04) é o único desses SEM Resource/
  Lixeira de UI própria — vive só dentro do form de `ProjetoResource`
  (ver `plugins/perseu/comercial/CLAUDE.md`), por isso não entra em
  `TrashCatalog::models()` (nenhuma UI de exclusão ainda). O
  projeto não define `Relation::morphMap()`, então
  `activity_log.subject_type` guarda o FQCN completo — **isso importa
  ao renomear um Model auditado: os logs já gravados ficam com o FQCN
  ANTIGO, e precisam de uma atualização de dados (`DB::table('activity_log')
  ->where('subject_type', $antigo)->update(['subject_type' => $novo])`)
  dentro da própria migration de rename, ou o histórico de auditoria
  anterior ao rename fica órfão** (ver "Rename Obra → Projeto" no
  histórico, seção "Ver também" — achado desta tarefa, que o rename
  anterior `Project → Processo` não precisou tratar porque `Processo`
  não é um Model auditado).
- A caixa "Pesquisar" padrão da tabela de Auditoria busca ao mesmo
  tempo pelo REGISTRO (via `SubjectTypeCatalog::applyBusca()`, coluna
  por coluna conforme o tipo — ver tabela abaixo) e pelo EVENTO
  (rótulo traduzido, ex. "excluído definitivamente"), via
  `->searchable(query: ...)` nas colunas correspondentes, que o
  Filament já soma com `OR` automaticamente. Não há filtro de busca
  textual separado (foi removido — só a caixa padrão).

  | Cadastro | Coluna(s) pesquisada(s) |
  |---|---|
  | Projeto | `descricao`, `numero_projeto` |
  | Item de Projeto | `descricao`, `numero_item` |
  | Tipo de Projeto, Situação de Projeto, Categoria de Pessoa, Setor | `descricao` |
  | Pessoa Física | `nome` |
  | Pessoa Jurídica | `razao_social`, `nome_fantasia`, `cnpj` |
  | Endereço | `logradouro`, `bairro`, `municipio` |
  | Contato | `cargo` |

  A busca por texto SÓ cobre esses 10 Models — não busca nome de
  usuário (existe filtro dedicado "Usuário", coluna `causer_id`) nem
  nome da empresa dona do sistema (Branding/`Company`, que não é um
  Model auditado — ver `plugins/webkul/support/CLAUDE.md`).
- Filtros disponíveis: Módulo, Cadastro (`subject_type`), Usuário
  (`causer_id`), Eventos (multi-seleção, todos marcados por padrão —
  `created/updated/deleted/forceDeleted/restored`), Período (default:
  último ano em "Criado a partir de" — usa formato `Y-m-d`, NUNCA
  `d/m/Y`, no `->default()` do `DatePicker`; o parser interno do
  pacote quebra com dias > 12 em `d/m/Y`).
- **`forceDeleted` precisa de listener próprio** — o Spatie
  Activitylog não grava esse evento nativamente
  (`LogsActivity::eventsToBeRecorded()` só cobre
  `created/updated/deleted/restored`). `LogsBusinessActivity` já
  resolve isso (ver "Convenção para Model novo de cadastro de negócio"
  no `CLAUDE.md` da raiz) — não reimplementar.
- **Decisão de produto**: não há exclusão automática de logs antigos —
  `activity_log` é mantida para sempre (tabela leve, valor de
  auditoria/fiscal a longo prazo).
- O botão "Editar" no card "Mudanças" do detalhe de um log
  (`rmsramos/activitylog`) está escondido de propósito
  (`ActivitylogPlugin::isResourceActionHidden(true)`) — link pra editar
  o registro original, não funciona com Resources clusterizados
  (nossos Resources auditados são todos clusterizados) e contraria a
  imutabilidade da auditoria de qualquer forma.
- **Lixeira Central** (Configurações → Lixeira,
  `Perseu\Auditoria\Filament\Pages\Lixeira`) agrega os Excluídos de
  TODOS os cadastros com `SoftDeletes` numa tabela só (hoje: Projeto,
  Pessoa Física, Pessoa Jurídica, Referência de Preços —
  `Perseu\Auditoria\Support\TrashCatalog::models()` é a lista oficial;
  Categoria/Setor/Tipo/Situação NÃO têm `SoftDeletes`, então não
  aparecem ali). Usa `Filament\Tables\Table\Concerns\HasRecords::records(Closure)`
  (mecanismo oficial do Filament v4 para tabela sem Eloquent Builder —
  cada linha é um `Filament\Support\ArrayRecord`, não uma instância de
  Model). Restaurar/Excluir Permanentemente chamam o Model real
  (`$model::onlyTrashed()->find($id)->restore()`/`forceDelete()`), o
  que já dispara a cascata de `CascadesRelatedDataOnForceDelete`
  (`perseu/pessoas`) automaticamente. Sem Resource/Policy própria —
  cada linha verifica a Policy do Model real (`Gate::allows('restore'|
  'forceDelete', $modelReal)`). **Cada chamada envolta em
  `DB::transaction()` desde 2026-09-05** (achado real de concorrência,
  ver `INVESTIGACAO-TRANSACOES-CONCORRENCIA.md`) — sem isso, uma falha
  no meio (ex.: a cascata de Endereço/Contato, ou o próprio log de
  auditoria do evento `forceDeleted`) deixava o registro parcialmente
  excluído/restaurado. Em `bulkAct()` (ação em lote), CADA REGISTRO é
  transacional individualmente — o lote inteiro continua permitindo
  sucesso parcial entre registros diferentes (comportamento já existente
  e intencional, preservado).

  A Lixeira Central foi desenhada assim (e não como uma `VIEW` de
  banco fazendo `UNION ALL`) de propósito, pra evitar uma dependência
  circular de plugins: `comercial` e `pessoas` já dependem de
  `auditoria` (`->hasDependency('auditoria')`, por causa do trait
  `LogsBusinessActivity`) — uma `VIEW` referenciando as tabelas de
  negócio viveria naturalmente em `auditoria` (mesmo lugar desta
  página), o que exigiria `auditoria->hasDependency('comercial')` e
  `auditoria->hasDependency('pessoas')` — ciclo de dependência que o
  `Webkul\PluginManager` não foi desenhado pra suportar.
- **Pendente**: o filtro "Excluídos"/`RestoreAction`/`ForceDeleteAction`
  em cada Resource individual (Projeto/PF/PJ) continuam ativos — só
  devem ser removidos depois que o usuário confirmar que a Lixeira
  Central substitui bem esse acesso. Ver pendência global no
  `CLAUDE.md` da raiz. Não remover sem essa confirmação.
- **Não implementado (ver "Pendências" abaixo)**: atalho "Ir para a
  Lixeira deste cadastro" a partir de um log `deleted`, e qualquer ação
  de restaurar/reverter DIRETAMENTE da tela de Auditoria.

## Pendências

- **Restaurar a partir da Auditoria**: levantado, NÃO implementado —
  ver seção anterior. Se algum dia implementar, exigir
  `->requiresConfirmation()` citando o registro afetado, o que a ação
  vai mudar, e um alerta sobre a cascata de `forceDeleting` não trazer
  Endereços/Contatos de volta numa restauração. Ver "Ver também"
  abaixo para o levantamento original completo.

## Ver também (histórico narrado, `HISTORICO-DESENVOLVIMENTO.md`)

- "Auditoria (log de atividade) + Lixeira completa — implementação
  original" (28/08/2026)
- "Central de Auditoria única, sem abas de 'Atividades' nos registros
  individuais" (29/08/2026)
- "Central de Auditoria: filtro por usuário + escopo real do filtro de
  busca" (29/08/2026)
- "Busca da Central de Auditoria unificada na caixa 'Pesquisar'
  padrão" (29/08/2026)
- "Botão 'Editar' morto no detalhe do log de Auditoria — removido via
  config oficial do pacote" (29/08/2026)
- "Restaurar um registro excluído a partir da Auditoria —
  levantamento, NÃO implementado" (29/08/2026)
- "Lixeira Central (Configurações → Lixeira) agregando Excluídos de
  todos os cadastros" (29/08/2026)
- "`forceDeleted` nunca foi logado pelo Spatie Activitylog — descoberto
  e corrigido, não só 'renomeado'" (29/08/2026)
- "Auditoria: período padrão de 1 ano + filtro de Eventos
  multi-seleção" (29/08/2026)
- "Rename Obra → Projeto no plugin `perseu/comercial`" (02/09/2026) —
  seção "`activity_log.subject_type` também precisou ser atualizado"
