<?php

namespace Perseu\Auditoria\Filament\Resources;

use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Perseu\Auditoria\Filament\Resources\AuditoriaResource\Pages\ListAuditoria;
use Perseu\Auditoria\Filament\Resources\AuditoriaResource\Pages\ViewAuditoria;
use Perseu\Auditoria\Support\SubjectTypeCatalog;
use Rmsramos\Activitylog\Resources\Activitylog\ActivitylogResource;
use Spatie\Activitylog\Models\Activity;
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
 * página ganhou filtro por cadastro/módulo de origem
 * (`SubjectTypeCatalog`) e um filtro de busca textual (nome, razão
 * social, número de Obra etc., via `whereHasMorph` — não existe coluna
 * própria pra isso em `activity_log`, o valor é derivado do model
 * relacionado). `table()` sobrescreve o do pacote por completo (em vez
 * de tentar compor com os métodos estáticos do pai via `->filters()`/
 * `->columns()` encadeado, que SUBSTITUI a lista anterior em vez de
 * somar — checar `Filament\Tables\Concerns\HasFilters`/`HasColumns`
 * antes de tentar "aditivar" no futuro).
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
            ->filters([
                static::getModuloFilterComponent(),
                static::getSubjectTypeFilterComponent(),
                static::getBuscaRegistroFilterComponent(),
                static::getEventFilterComponent(),
                static::getDateFilterComponent(),
            ]);
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
     * soft-deleted). Não é `->searchable()` (não existe coluna pra
     * buscar) — a busca por este texto é o filtro dedicado
     * `getBuscaRegistroFilterComponent()`, que sabe em qual coluna de
     * cada Model procurar.
     */
    public static function getSubjectReferenceColumnComponent(): Column
    {
        return TextColumn::make('subject_reference')
            ->label(__('auditoria::filament/resources/auditoria.table.columns.subject_reference'))
            ->getStateUsing(fn (Activity $record) => SubjectTypeCatalog::referenceFor($record->subject)
                ?? __('auditoria::filament/resources/auditoria.table.columns.subject_reference_unavailable'))
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

    public static function getBuscaRegistroFilterComponent(): Filter
    {
        return Filter::make('busca')
            ->label(__('auditoria::filament/resources/auditoria.table.filters.busca.label'))
            ->form([
                TextInput::make('valor')
                    ->label(__('auditoria::filament/resources/auditoria.table.filters.busca.valor')),
            ])
            ->query(function (Builder $query, array $data): Builder {
                if (blank($data['valor'] ?? null)) {
                    return $query;
                }

                return SubjectTypeCatalog::applyBusca($query, $data['valor']);
            })
            ->indicateUsing(fn (array $data): ?string => filled($data['valor'] ?? null)
                ? $data['valor']
                : null);
    }
}
