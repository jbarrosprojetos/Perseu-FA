<?php

namespace Perseu\Auditoria\Filament\Pages;

use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Perseu\Auditoria\Support\SubjectTypeCatalog;
use Perseu\Auditoria\Support\TrashCatalog;
use Spatie\Activitylog\Models\Activity;
use Webkul\Support\Filament\Clusters\Settings;

/**
 * Lixeira Central — agrega os registros excluídos (soft-deleted) de
 * TODOS os Models com Lixeira de verdade hoje (`TrashCatalog::models()`
 * — Projeto, Pessoa Jurídica, Pessoa Física), numa única listagem, pra não
 * precisar visitar cada Resource individualmente só pra restaurar ou
 * limpar a lixeira.
 *
 * DIFERENTE da página de Auditoria (que lista `activity_log`, uma
 * tabela só): esta página opera sobre os registros de negócio reais,
 * cada um numa tabela própria (`projetos`, `pessoas_juridicas`,
 * `pessoas_fisicas`). Um `Filament\Resources\Resource` (e o `table()`
 * "normal" do Filament) é desenhado em torno de UM Model/UMA query —
 * não há suporte nativo a "misturar" 3 Models heterogêneos numa
 * `Illuminate\Database\Eloquent\Builder` só.
 *
 * Mecanismo escolhido: `Table::records(Closure $dataSource)`
 * (`Filament\Tables\Table\Concerns\HasRecords`), um hook OFICIAL do
 * Filament v4 pra tabelas cuja fonte de dados não é uma Eloquent query
 * simples — quando presente, `hasQuery()` retorna `false` e
 * `HasRecords::getTableRecords()` (`filament/tables`) chama esse
 * closure passando o estado atual de filtros/busca/ordenação/paginação
 * como parâmetros nomeados (`filters`, `sortColumn`, `sortDirection`,
 * `page`, `recordsPerPage`) em vez de aplicar tudo isso numa Builder —
 * a aplicação desse estado fica inteiramente por nossa conta dentro do
 * closure (ver `buildPaginator()` abaixo).
 *
 * Alternativa avaliada e descartada: uma `VIEW` de banco fazendo
 * `UNION ALL` das 3 tabelas, com um Model "de leitura" por cima. Foi
 * descartada por criar uma dependência circular de plugins: `comercial`
 * e `pessoas` já dependem de `auditoria` (`->hasDependency('auditoria')`,
 * por causa do trait `LogsBusinessActivity`) — uma `VIEW` referenciando
 * `projetos`/`pessoas_juridicas`/`pessoas_fisicas` viveria naturalmente em
 * `auditoria` (mesmo lugar desta página), o que exigiria
 * `auditoria->hasDependency('comercial')` e
 * `auditoria->hasDependency('pessoas')` — ciclo de dependência
 * (`comercial → auditoria → comercial`) que o `Webkul\PluginManager`
 * não foi desenhado pra suportar. A abordagem por `records()` evita
 * esse problema por completo: nenhuma migration nova, nenhuma tabela
 * cross-plugin, e o merge é feito 100% em PHP.
 *
 * Trade-off consciente da abordagem em PHP: cada carregamento de
 * página busca TODOS os registros excluídos que passam nos filtros
 * ativos (não só a página atual) pra poder ordenar/paginar o conjunto
 * combinado corretamente, e só então fatia a página pedida — correto
 * para o volume real deste sistema (uma lixeira de ERP interno tende a
 * ter dezenas/poucas centenas de linhas, não milhões). Se um dia isso
 * crescer demais, a alternativa de `VIEW` (com a dependência de
 * plugins resolvida de alguma forma, ex.: mover a `VIEW` pra uma
 * migration do próprio app, fora do ciclo de instalar/desinstalar de
 * qualquer plugin — mesmo padrão já usado pra `activity_log`) volta a
 * valer a pena.
 *
 * Cada linha é um ARRAY (`Filament\Support\ArrayRecord`), não uma
 * instância real do Model — os 3 Models têm PKs numéricas que colidem
 * entre si (ex.: Projeto #5 e Pessoa Jurídica #5), então a chave única de
 * cada linha (`ArrayRecord::getKeyName()`, `'__key'` por padrão) é
 * sintética (`"{$modelKey}-{$id}"`). As Actions de Restaurar/Excluir
 * Definitivamente NÃO usam `Filament\Actions\RestoreAction`/
 * `ForceDeleteAction` prontas (que chamam `$record->restore()`
 * assumindo `$record` ser o Model de verdade — quebraria com um
 * array) — em vez disso, resolvem o Model real
 * (`resolveModel()`) e chamam `->restore()`/`->forceDelete()` NELE.
 * Isso é o que importa pra "reaproveitar a lógica, não duplicar": a
 * cascata de Endereços/Contatos ao excluir definitivamente uma Pessoa
 * Jurídica/Física mora no `forceDeleting` do PRÓPRIO Model (ver
 * `CascadesRelatedDataOnForceDelete`/boot() de cada um), então chamar
 * `->forceDelete()` no Model real dispara essa lógica automaticamente
 * — reescrever a cascata aqui seria exatamente o risco de "registro
 * fantasma" que a tarefa pediu pra evitar.
 *
 * Permissões: SEM Resource/Policy própria pra esta página — de
 * propósito (a tarefa pediu explicitamente pra NÃO criar uma permissão
 * genérica "gerenciar lixeira de tudo"). Cada linha usa a Policy JÁ
 * REGISTRADA do Model real (`Gate::allows('restore', $modelReal)` /
 * `Gate::allows('forceDelete', $modelReal)`) — o mesmo
 * `ProjetoPolicy`/`PessoaFisicaPolicy`/`PessoaJuridicaPolicy` que já
 * controla o Resource individual. Um usuário sem `restore_comercial_projeto`
 * não vê o botão Restaurar numa linha de Projeto aqui, exatamente como não
 * veria em Comercial → Projetos.
 */
class Lixeira extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $cluster = Settings::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-trash';

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return __('auditoria::filament/pages/lixeira.navigation.title');
    }

    public function getTitle(): string
    {
        return __('auditoria::filament/pages/lixeira.navigation.title');
    }

    public static function getClusterBreadcrumb(): ?string
    {
        return __('auditoria::filament/pages/lixeira.navigation.title');
    }

    /**
     * Sem permissão própria: o item só aparece na sidebar de
     * Configurações se o usuário tiver ALGUMA permissão de
     * restaurar/excluir definitivamente em pelo menos um dos Models de
     * `TrashCatalog` — mesmo raciocínio de
     * `Cluster::canAccessClusteredComponents()` (checa os filhos), só
     * que aqui não há Resources filhos de verdade pra delegar.
     */
    public static function canAccess(): bool
    {
        foreach (TrashCatalog::models() as $model) {
            if (Gate::allows('restoreAny', $model) || Gate::allows('forceDeleteAny', $model)) {
                return true;
            }
        }

        return false;
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            EmbeddedTable::make(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (?array $filters, ?string $sortColumn, ?string $sortDirection, int|string $page, int|string $recordsPerPage): LengthAwarePaginator => $this->buildPaginator(
                $filters ?? [],
                $sortColumn,
                $sortDirection ?? 'desc',
                (int) $page,
                (int) $recordsPerPage,
            ))
            ->columns([
                TextColumn::make('subject_type_label')
                    ->label(__('auditoria::filament/pages/lixeira.table.columns.subject_type'))
                    ->badge(),
                TextColumn::make('subject_reference')
                    ->label(__('auditoria::filament/pages/lixeira.table.columns.subject_reference'))
                    ->wrap(),
                TextColumn::make('deleted_at')
                    ->label(__('auditoria::filament/pages/lixeira.table.columns.deleted_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('deleted_by')
                    ->label(__('auditoria::filament/pages/lixeira.table.columns.deleted_by'))
                    ->placeholder('—'),
            ])
            ->defaultSort('deleted_at', 'desc')
            // Filtros abaixo NÃO têm `->query()`: numa tabela sem
            // Eloquent Builder (`records()`), o Filament nunca chama
            // `->query()` de filtro nenhum — só entrega o estado bruto
            // de `$this->tableFilters` pro closure de `records()`
            // (`HasRecords::getTableRecords()`, ramo `! hasQuery()`,
            // conferido lendo o vendor antes de escrever isto). A
            // aplicação de fato acontece em `buildPaginator()`.
            ->filters([
                SelectFilter::make('modulo')
                    ->label(__('auditoria::filament/pages/lixeira.table.filters.modulo.label'))
                    ->options(SubjectTypeCatalog::moduloOptions()),
                SelectFilter::make('subject_type')
                    ->label(__('auditoria::filament/pages/lixeira.table.filters.subject_type.label'))
                    ->options($this->subjectTypeOptions()),
                Filter::make('excluido_em')
                    ->label(__('auditoria::filament/pages/lixeira.table.filters.excluido_em.label'))
                    ->form([
                        DatePicker::make('excluido_de')
                            ->label(__('auditoria::filament/pages/lixeira.table.filters.excluido_em.de')),
                        DatePicker::make('excluido_ate')
                            ->label(__('auditoria::filament/pages/lixeira.table.filters.excluido_em.ate')),
                    ]),
            ])
            ->recordActions([
                Action::make('restaurar')
                    ->label(__('auditoria::filament/pages/lixeira.table.actions.restore.label'))
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription(fn (array $record): string => __('auditoria::filament/pages/lixeira.table.actions.restore.confirmation', [
                        'cadastro'  => $record['subject_type_label'],
                        'registro'  => $record['subject_reference'],
                    ]))
                    ->visible(fn (array $record): bool => Gate::allows('restore', $this->resolveModel($record)))
                    ->action(fn (array $record) => $this->restoreRecord($record)),
                Action::make('excluir_definitivamente')
                    ->label(__('auditoria::filament/pages/lixeira.table.actions.force_delete.label'))
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription(fn (array $record): string => __('auditoria::filament/pages/lixeira.table.actions.force_delete.confirmation', [
                        'cadastro'  => $record['subject_type_label'],
                        'registro'  => $record['subject_reference'],
                    ]))
                    ->visible(fn (array $record): bool => Gate::allows('forceDelete', $this->resolveModel($record)))
                    ->action(fn (array $record) => $this->forceDeleteRecord($record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('restaurar_selecionados')
                        ->label(__('auditoria::filament/pages/lixeira.table.bulk-actions.restore.label'))
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('success')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records): void {
                            $this->bulkAct($records, restore: true);
                        }),
                    BulkAction::make('excluir_definitivamente_selecionados')
                        ->label(__('auditoria::filament/pages/lixeira.table.bulk-actions.force_delete.label'))
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records): void {
                            $this->bulkAct($records, restore: false);
                        }),
                ]),
            ]);
    }

    /**
     * @return array<class-string<Model>, string>
     */
    protected function subjectTypeOptions(): array
    {
        $options = [];

        foreach (TrashCatalog::models() as $model) {
            $options[$model] = SubjectTypeCatalog::label($model);
        }

        asort($options);

        return $options;
    }

    /**
     * Monta o conjunto combinado (filtrado, ordenado) das 3 fontes e
     * fatia a página pedida. Ver docblock da classe pro porquê de
     * carregar tudo antes de fatiar (correto para o volume real deste
     * sistema; não escala pra milhões de linhas).
     */
    protected function buildPaginator(array $filters, ?string $sortColumn, string $sortDirection, int $page, int $recordsPerPage): LengthAwarePaginator
    {
        $rows = $this->collectRows($filters);

        $sortColumn ??= 'deleted_at';
        $rows = $sortDirection === 'asc'
            ? $rows->sortBy($sortColumn)
            : $rows->sortByDesc($sortColumn);
        $rows = $rows->values();

        $page = max(1, $page);
        $recordsPerPage = $recordsPerPage ?: 10;

        $items = $rows->slice(($page - 1) * $recordsPerPage, $recordsPerPage)->values();

        return new LengthAwarePaginator(
            $items,
            $rows->count(),
            $recordsPerPage,
            $page,
            ['path' => request()->url(), 'pageName' => 'page'],
        );
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function collectRows(array $filters): Collection
    {
        $models = $this->modelsForFilters($filters);

        $excluidoDe = $filters['excluido_em']['excluido_de'] ?? null;
        $excluidoAte = $filters['excluido_em']['excluido_ate'] ?? null;

        $rows = collect();

        foreach ($models as $model) {
            $trashed = TrashCatalog::onlyTrashedQuery($model)
                ->when($excluidoDe, fn ($query, $date) => $query->whereDate('deleted_at', '>=', $date))
                ->when($excluidoAte, fn ($query, $date) => $query->whereDate('deleted_at', '<=', $date))
                ->get();

            if ($trashed->isEmpty()) {
                continue;
            }

            $deletedByIds = $this->deletedByFor($model, $trashed->pluck('id')->all());

            foreach ($trashed as $record) {
                $rows->push([
                    '__key'              => Str::slug(class_basename($model)) . '-' . $record->getKey(),
                    'model_type'         => $model,
                    'model_id'           => $record->getKey(),
                    'subject_type_label' => SubjectTypeCatalog::label($model),
                    'subject_reference'  => SubjectTypeCatalog::referenceFor($record)
                        ?? __('auditoria::filament/pages/lixeira.table.columns.subject_reference_unavailable'),
                    'deleted_at'         => $record->deleted_at,
                    'deleted_by'         => $deletedByIds[$record->getKey()] ?? null,
                ]);
            }
        }

        return $rows;
    }

    /**
     * @return list<class-string<Model>>
     */
    protected function modelsForFilters(array $filters): array
    {
        $models = TrashCatalog::models();

        if (filled($cadastro = $filters['subject_type']['value'] ?? null)) {
            return array_values(array_intersect($models, [$cadastro]));
        }

        if (filled($modulo = $filters['modulo']['value'] ?? null)) {
            return array_values(array_intersect($models, SubjectTypeCatalog::subjectTypesForModulo($modulo)));
        }

        return $models;
    }

    /**
     * Cruza com `activity_log` pra achar quem excluiu cada registro —
     * uma query por MODEL (não por linha): junta todos os
     * `model_id`s da página de uma vez com `whereIn`, pega o log de
     * `event = 'deleted'` mais recente de cada um. Só existe porque a
     * tarefa pediu explicitamente pra ver se dava pra cruzar com o log
     * — se um registro nunca teve um log de exclusão (ex.: apagado
     * antes da auditoria existir), some do resultado sem erro
     * (`$deletedByIds[$id] ?? null`, acima).
     *
     * @param  class-string<Model>  $model
     * @param  array<int>  $ids
     * @return array<int, string>
     */
    protected function deletedByFor(string $model, array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        return Activity::query()
            ->where('subject_type', $model)
            ->where('event', 'deleted')
            ->whereIn('subject_id', $ids)
            ->with('causer')
            ->get()
            ->groupBy('subject_id')
            ->map(fn ($logs) => $logs->sortByDesc('id')->first())
            ->filter(fn (Activity $activity) => $activity->causer !== null)
            ->map(fn (Activity $activity) => $activity->causer->name)
            ->all();
    }

    /**
     * @param  array<string, mixed>  $record
     */
    protected function resolveModel(array $record): ?Model
    {
        return TrashCatalog::onlyTrashedQuery($record['model_type'])->find($record['model_id']);
    }

    /**
     * @param  array<string, mixed>  $record
     */
    protected function restoreRecord(array $record): void
    {
        $model = $this->resolveModel($record);

        if (! $model || ! Gate::allows('restore', $model)) {
            Notification::make()
                ->danger()
                ->title(__('auditoria::filament/pages/lixeira.table.actions.restore.notification.error'))
                ->send();

            return;
        }

        $model->restore();

        Notification::make()
            ->success()
            ->title(__('auditoria::filament/pages/lixeira.table.actions.restore.notification.success'))
            ->send();
    }

    /**
     * @param  array<string, mixed>  $record
     */
    protected function forceDeleteRecord(array $record): void
    {
        $model = $this->resolveModel($record);

        if (! $model || ! Gate::allows('forceDelete', $model)) {
            Notification::make()
                ->danger()
                ->title(__('auditoria::filament/pages/lixeira.table.actions.force_delete.notification.error'))
                ->send();

            return;
        }

        $model->forceDelete();

        Notification::make()
            ->success()
            ->title(__('auditoria::filament/pages/lixeira.table.actions.force_delete.notification.success'))
            ->send();
    }

    /**
     * Ação em lote — reaproveita `restoreRecord()`/`forceDeleteRecord()`
     * linha a linha (mesma checagem de permissão individual: uma
     * seleção heterogênea pode ter, por exemplo, um Projeto que o usuário
     * pode restaurar e uma Pessoa Jurídica que não pode — cada uma é
     * autorizada separadamente, e o resultado final informa quantas
     * foram puladas por falta de permissão).
     *
     * @param  Collection<int, array<string, mixed>>  $records
     */
    protected function bulkAct(Collection $records, bool $restore): void
    {
        $sucesso = 0;
        $semPermissao = 0;

        foreach ($records as $record) {
            $model = $this->resolveModel($record);
            $abilidade = $restore ? 'restore' : 'forceDelete';

            if (! $model || ! Gate::allows($abilidade, $model)) {
                $semPermissao++;

                continue;
            }

            $restore ? $model->restore() : $model->forceDelete();
            $sucesso++;
        }

        $notification = Notification::make()->title(
            $restore
                ? __('auditoria::filament/pages/lixeira.table.bulk-actions.restore.notification.title', ['total' => $sucesso])
                : __('auditoria::filament/pages/lixeira.table.bulk-actions.force_delete.notification.title', ['total' => $sucesso])
        );

        if ($semPermissao > 0) {
            $notification->warning()->body(__('auditoria::filament/pages/lixeira.table.bulk-actions.notification.skipped', ['total' => $semPermissao]));
        } else {
            $notification->success();
        }

        $notification->send();
    }
}
