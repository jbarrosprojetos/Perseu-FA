<?php

namespace Perseu\Auditoria\Filament\Resources;

use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Rmsramos\Activitylog\ActivitylogPlugin;
use Perseu\Auditoria\Filament\Resources\AuditoriaResource\Pages\ListAuditoria;
use Perseu\Auditoria\Filament\Resources\AuditoriaResource\Pages\ViewAuditoria;
use Perseu\Auditoria\Support\SubjectTypeCatalog;
use Rmsramos\Activitylog\Resources\Activitylog\ActivitylogResource;
use Spatie\Activitylog\Models\Activity;
use Webkul\Security\Models\User;
use Webkul\Support\Filament\Clusters\Settings;

/**
 * Reaproveita o `ActivitylogResource` do rmsramos/activitylog (tabela,
 * filtros, timeline etc. já prontos — ver README do pacote) só
 * reposicionando a navegação: em vez do item de topo próprio que o
 * pacote cria por padrão, aparece dentro do cluster de Configurações
 * (`Webkul\Support\Filament\Clusters\Settings`), como "Auditoria", ao
 * lado de "Marca" — mesma técnica de `$cluster` que
 * `Webkul\Support\Filament\Resources\ActivityTypeResource` já usa
 * (Resource "de verdade" clusterizado, não uma Page de settings como
 * `ManageBranding` — ver CLAUDE.md).
 *
 * Registrada via `->resource(AuditoriaResource::class)` em
 * `ActivitylogPlugin::make()` (ver AuditoriaServiceProvider), que
 * substitui o resource padrão do pacote — só esta classe fica
 * registrada, não as duas.
 *
 * `getPages()` é sobrescrito com Pages PRÓPRIAS (ListAuditoria/
 * ViewAuditoria) em vez de herdar as do pacote: as Pages originais
 * (`Rmsramos\Activitylog\Resources\Activitylog\Pages\*`) têm o
 * `$resource`/`getResource()` fixos na classe base
 * `ActivitylogResource`, então herdar `getPages()" sem reapontar as
 * Pages faria as rotas/permissões resolverem para o resource errado
 * (o de topo do pacote, não este, clusterizado).
 *
 * Central única de auditoria (2026-08-29): esta lista passou a ser o
 * ÚNICO lugar do sistema pra ver histórico de atividade — as abas
 * "Atividades" que existiam dentro de Pessoa Jurídica/Física/Obra
 * (`ActivitylogRelationManager`) foram removidas (informação
 * duplicada, já que esta lista mostra TUDO de qualquer módulo). Pra
 * compensar a perda do atalho "aba dentro do próprio registro", esta
 * página ganhou filtro por cadastro/módulo/usuário de origem
 * (`SubjectTypeCatalog`) e busca textual (nome, razão social, número
 * de Obra etc.) — inicialmente um `Filter` separado, depois unificada
 * (2026-08-29) na caixa "Pesquisar" padrão do Filament, ver
 * `getSubjectReferenceColumnComponent()`. `table()` sobrescreve o do
 * pacote por completo (em vez de tentar compor com os métodos
 * estáticos do pai via `->filters()`/`->columns()` encadeado, que
 * SUBSTITUI a lista anterior em vez de somar — checar
 * `Filament\Tables\Concerns\HasFilters`/`HasColumns` antes de tentar
 * "aditivar" no futuro).
 */
class AuditoriaResource extends ActivitylogResource
{
    protected static ?string $cluster = Settings::class;

    public static function getNavigationLabel(): string
    {
        return __('auditoria::filament/resources/auditoria.navigation.title');
    }

    public static function getModelLabel(): string
    {
        return __('auditoria::filament/resources/auditoria.model-label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('auditoria::filament/resources/auditoria.plural-model-label');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAuditoria::route('/'),
            'view'  => ViewAuditoria::route('/{record}'),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                static::getCauserNameColumnComponent(),
                static::getEventColumnComponent(),
                static::getSubjectTypeColumnComponent(),
                static::getSubjectReferenceColumnComponent(),
                static::getCreatedAtColumnComponent(),
            ])
            ->defaultSort(
                config('filament-activitylog.resources.default_sort_column', 'created_at'),
                config('filament-activitylog.resources.default_sort_direction', 'desc')
            )
            ->searchPlaceholder(__('auditoria::filament/resources/auditoria.table.search_placeholder'))
            ->filters([
                static::getModuloFilterComponent(),
                static::getSubjectTypeFilterComponent(),
                static::getCauserFilterComponent(),
                static::getEventFilterComponent(),
                static::getDateFilterComponent(),
            ]);
    }

    /**
     * Sobrescreve o do pacote (`ActivitylogResource::getEventColumnComponent()`)
     * só pra somar `->searchable(query: ...)` — pedido do usuário depois
     * de tentar digitar "defi" (esperando achar "Excluído
     * Definitivamente") na caixa "Pesquisar" e não achar nada, já que
     * a busca só cobria os campos do registro de origem
     * (`getSubjectReferenceColumnComponent()`). O termo digitado é
     * comparado contra o RÓTULO TRADUZIDO de cada evento (o que o
     * usuário vê na tela, ex. "excluído definitivamente"), não contra
     * o valor técnico salvo em `event` (`forceDeleted`) — daí primeiro
     * descobrir quais valores técnicos têm rótulo que bate com o termo,
     * e só então `whereIn('event', [...])`. Mesmo `whereRaw('1 = 0')`
     * de `SubjectTypeCatalog::applyBusca()` quando nada bate — devolver
     * a query sem alteração faria o grupo `orWhere(fn ($q) => ...)`
     * ficar vazio, o que equivale a "true" em SQL (bateria com
     * qualquer termo, não com nenhum).
     *
     * Resultado: um único termo na caixa "Pesquisar" agora casa com
     * QUALQUER UMA das duas coisas (Filament soma com `OR` automático
     * entre colunas `searchable()` — `InteractsWithTableQuery::applySearchConstraint()`,
     * mesmo mecanismo que já une múltiplas colunas buscáveis) — o
     * registro de origem (`subject_reference`) OU o evento
     * (`event`, aqui).
     */
    public static function getEventColumnComponent(): Column
    {
        return TextColumn::make('event')
            ->label(__('activitylog::tables.columns.event.label'))
            ->formatStateUsing(fn (?string $state) => $state ? ucwords(__('activitylog::action.event.' . $state)) : '-')
            ->badge()
            ->color(fn (?string $state): string => match ($state) {
                'draft'        => 'gray',
                'updated'      => 'warning',
                'created'      => 'success',
                'deleted'      => 'danger',
                'forceDeleted' => 'danger',
                'restored'     => 'info',
                default        => 'primary',
            })
            ->searchable(query: function (Builder $query, string $search): Builder {
                $termoBuscado = Str::lower($search);

                $eventosCorrespondentes = collect(static::eventoKeys())
                    ->filter(fn (string $evento): bool => str_contains(
                        Str::lower(__('activitylog::action.event.' . $evento)),
                        $termoBuscado,
                    ))
                    ->values()
                    ->all();

                if (empty($eventosCorrespondentes)) {
                    return $query->whereRaw('1 = 0');
                }

                return $query->whereIn('event', $eventosCorrespondentes);
            })
            ->sortable();
    }

    /**
     * Valores técnicos de `event` que este projeto conhece — fixos (não
     * lidos via `DISTINCT event` do banco, como o
     * `ActivitylogResource::getEventFilterComponent()` original fazia)
     * de propósito: `getEventFilterComponent()` abaixo precisa que os 5
     * apareçam sempre como opção marcável no filtro, mesmo que hoje não
     * exista nenhum log de um tipo específico ainda (ex.: `restored`
     * antes da primeira restauração já ter acontecido) — usado também
     * pela busca por evento em `getEventColumnComponent()`, um só lugar
     * pra não duplicar a lista.
     */
    protected static function eventoKeys(): array
    {
        return ['created', 'updated', 'deleted', 'forceDeleted', 'restored'];
    }

    /**
     * Sobrescreve o do pacote (`ActivitylogResource::getSubjectTypeColumnComponent()`)
     * pra mostrar o rótulo amigável do cadastro (`SubjectTypeCatalog::label()`)
     * em vez do basename cru da classe PHP + `#id` — o `#id`/indicador de
     * excluído continuavam fazendo sentido ali quando essa coluna era a
     * única referência ao registro, mas agora `getSubjectReferenceColumnComponent()`
     * (abaixo) cobre isso com um texto melhor (nome/razão social/número),
     * então esta coluna volta a ser só o "tipo de cadastro".
     */
    public static function getSubjectTypeColumnComponent(): Column
    {
        return TextColumn::make('subject_type')
            ->label(__('auditoria::filament/resources/auditoria.table.columns.subject_type'))
            ->formatStateUsing(fn (?string $state) => SubjectTypeCatalog::label($state))
            ->badge()
            ->sortable();
    }

    /**
     * Referência textual ao registro específico afetado (nome, razão
     * social, número de Obra...) — não é uma coluna real de
     * `activity_log`, o valor vem do `subject` (já eager-loaded por
     * `ActivitylogResource::getEloquentQuery()`, inclusive
     * soft-deleted).
     *
     * `->searchable(query: ...)` liga a caixa "Pesquisar" padrão do
     * Filament (topo da tabela) direto em
     * `SubjectTypeCatalog::applyBusca()` — como a coluna não tem um
     * valor de banco próprio pra comparar (`getStateUsing`, não
     * `make('coluna_real')`), sem esse `query:` custom o Filament
     * tentaria (e falharia) buscar por uma coluna `subject_reference`
     * inexistente em `activity_log`
     * (`Filament\Tables\Columns\Concerns\InteractsWithTableQuery::applySearchConstraint()`
     * só monta a comparação padrão por coluna quando NÃO há
     * `$searchQuery` definido). Existia um `Filter` dedicado
     * ("Nome, razão social, número...") pra isso antes de 2026-08-29 —
     * removido por redundância com a caixa "Pesquisar" (duas caixas de
     * busca fazendo a mesma coisa confundia o usuário).
     *
     * Case-insensitive "de graça": todas as 14 colunas reais que
     * `applyBusca()` compara (`descricao`, `numero_obra`, `nome`,
     * `razao_social`, `nome_fantasia`, `cnpj`, `logradouro`, `bairro`,
     * `municipio`, `cargo`) usam collation `utf8mb4_unicode_ci`
     * (confirmado com `SHOW FULL COLUMNS` em cada tabela) — `LIKE` do
     * MySQL/MariaDB já é case-insensitive nessa collation, sem precisar
     * de `LOWER()` nos dois lados. Mesmo padrão que o próprio Filament
     * usa pra busca padrão em MySQL/MariaDB
     * (`Filament\Support\generate_search_term_expression()`: só força
     * `Str::lower()` no termo por padrão quando o driver é `pgsql` —
     * em `mysql`/`mariadb` conta com a collation da coluna pra isso,
     * exatamente como aqui).
     */
    public static function getSubjectReferenceColumnComponent(): Column
    {
        return TextColumn::make('subject_reference')
            ->label(__('auditoria::filament/resources/auditoria.table.columns.subject_reference'))
            ->getStateUsing(fn (Activity $record) => SubjectTypeCatalog::referenceFor($record->subject)
                ?? __('auditoria::filament/resources/auditoria.table.columns.subject_reference_unavailable'))
            ->searchable(query: fn (Builder $query, string $search): Builder => SubjectTypeCatalog::applyBusca($query, $search))
            ->wrap();
    }

    public static function getModuloFilterComponent(): SelectFilter
    {
        return SelectFilter::make('modulo')
            ->label(__('auditoria::filament/resources/auditoria.table.filters.modulo.label'))
            ->options(SubjectTypeCatalog::moduloOptions())
            ->query(function (Builder $query, array $data): Builder {
                if (blank($data['value'] ?? null)) {
                    return $query;
                }

                return $query->whereIn('subject_type', SubjectTypeCatalog::subjectTypesForModulo($data['value']));
            });
    }

    public static function getSubjectTypeFilterComponent(): SelectFilter
    {
        return SelectFilter::make('subject_type')
            ->label(__('auditoria::filament/resources/auditoria.table.filters.subject_type.label'))
            ->options(SubjectTypeCatalog::subjectTypeOptions());
    }

    /**
     * `causer_id` é coluna real de `activity_log` — sem `->query()`
     * customizado, o `SelectFilter` já aplica `where('causer_id', $valor)`
     * sozinho (comportamento padrão, ver `getSubjectTypeFilterComponent()`
     * acima pro mesmo raciocínio). Não restringe também por `causer_type`:
     * uma checagem direta em `activity_log.causer_type` (`SELECT DISTINCT
     * causer_type`) confirmou só dois valores possíveis: vazio (ações sem
     * usuário autenticado, ex. seeders/tinker) ou
     * `Webkul\Security\Models\User` — não há outro tipo de causer hoje
     * (nem o `causedBy()` manual de
     * `Perseu\Auditoria\Traits\LogsBusinessActivity::bootLogsBusinessActivity()`,
     * adicionado depois desta nota pra logar `forceDeleted`, muda isso —
     * ele também sempre passa um `Webkul\Security\Models\User` ou
     * `null`), então um `causer_id` já identifica o log sem ambiguidade.
     */
    public static function getCauserFilterComponent(): SelectFilter
    {
        return SelectFilter::make('causer_id')
            ->label(__('auditoria::filament/resources/auditoria.table.filters.causer.label'))
            ->searchable()
            ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all());
    }

    /**
     * Sobrescreve o do pacote (`ActivitylogResource::getEventFilterComponent()`)
     * pra virar multi-seleção com TUDO marcado por padrão — pedido do
     * usuário: o caso de uso mais comum é "não quero ver isto" (ex.:
     * desmarcar "Excluído Definitivamente" pra escondê-lo), não "quero
     * ver só isto". Com um `SelectFilter` de seleção única (o padrão do
     * pacote) só dava pra isolar UM evento por vez, nunca "todos menos
     * um". `->options()` usa `eventoKeys()` (fixo) em vez do
     * `DISTINCT event` que o pacote usa — precisamos que as 5 opções
     * sempre apareçam, mesmo que algum evento ainda não tenha ocorrido
     * nenhuma vez neste banco.
     *
     * `->default(eventoKeys())` é o que faz a lista abrir já mostrando
     * TUDO (equivalente a nenhum filtro aplicado) em vez de vazia — sem
     * isso, um `SelectFilter::multiple()` sem seleção nenhuma
     * filtraria pra ZERO resultados, não pra "sem filtro".
     */
    public static function getEventFilterComponent(): SelectFilter
    {
        return SelectFilter::make('event')
            ->label(__('activitylog::tables.filters.event.label'))
            ->multiple()
            ->options(collect(static::eventoKeys())
                ->mapWithKeys(fn (string $evento): array => [$evento => ucwords(__('activitylog::action.event.' . $evento))])
                ->all())
            ->default(static::eventoKeys());
    }

    /**
     * Sobrescreve o do pacote (`ActivitylogResource::getDateFilterComponent()`)
     * só pra somar `->default(...)` no campo "Criado a partir de" —
     * pedido do usuário: a lista deve abrir mostrando só o último 1
     * ano, sem precisar de exclusão automática de logs antigos (decisão
     * tomada de manter o histórico completo pra sempre, ver
     * PENDENCIAS-TECNICAS.md). "Criado até" fica SEM default de
     * propósito — só o passado é limitado, não existe um "futuro" a
     * esconder.
     *
     * Resto do método é uma cópia fiel do original (`indicateUsing`/
     * `form`/`query`) — não dava pra só "somar" o default por cima
     * (`static::getDatePickerCompoment('created_from')` já devolve o
     * campo pronto, mas o restante do Filter precisa ser reconstruído
     * porque não há como interceptar só o form original).
     *
     * **Formato do valor**: `->default()` usa `now()->subYear()->toDateString()`
     * (`Y-m-d`), NÃO `ActivitylogPlugin::get()->getDateFormat()`
     * (`d/m/Y`, usado só para EXIBIÇÃO) — confirmado testando os dois
     * formatos via `Livewire::test()->set('tableFilters.created_at.created_from', ...)`:
     * `Y-m-d` funciona normalmente, `d/m/Y` quebra a renderização do
     * indicador do filtro com `Could not parse '29/08/2026'`
     * (`ActivitylogPlugin::getDateParser()` usa `Carbon::parse()` sem
     * formato explícito, que interpreta `dd/mm/yyyy` de forma ambígua
     * e falha quando o dia é > 12). O valor DEHYDRATADO do `DatePicker`
     * é sempre ISO internamente, independente do `->format()` de
     * exibição configurado no campo.
     */
    public static function getDateFilterComponent(): Filter
    {
        return Filter::make('created_at')
            ->label(__('activitylog::tables.filters.created_at.label'))
            ->indicateUsing(function (array $data): array {
                $indicators = [];
                $parser     = ActivitylogPlugin::get()->getDateParser();

                if ($data['created_from'] ?? null) {
                    $indicators['created_from'] = __('activitylog::tables.filters.created_at.created_from_indicator', [
                        'created_from' => $parser($data['created_from'])
                            ->format(ActivitylogPlugin::get()->getDateFormat()),
                    ]);
                }

                if ($data['created_until'] ?? null) {
                    $indicators['created_until'] = __('activitylog::tables.filters.created_at.created_until_indicator', [
                        'created_until' => $parser($data['created_until'])
                            ->format(ActivitylogPlugin::get()->getDateFormat()),
                    ]);
                }

                return $indicators;
            })
            ->form([
                static::getDatePickerCompoment('created_from')
                    ->default(now()->subYear()->toDateString()),
                static::getDatePickerCompoment('created_until'),
            ])
            ->query(function (Builder $query, array $data): Builder {
                return $query
                    ->when(
                        $data['created_from'] ?? null,
                        fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                    )
                    ->when(
                        $data['created_until'] ?? null,
                        fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                    );
            });
    }
}
