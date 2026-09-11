<?php

namespace Perseu\Comercial\Filament\Clusters\Referencias\Resources;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Perseu\Comercial\Enums\FormaPagamento;
use Perseu\Comercial\Filament\Clusters\Referencias;
use Perseu\Comercial\Filament\Clusters\Referencias\Resources\CondicaoFinanceiraResource\Pages\ListCondicoesFinanceiras;
use Perseu\Comercial\Models\CondicaoFinanceira;

/**
 * Cadastro de apoio do Cluster Referências (ver CLAUDE.md, "Condições
 * Financeiras") — mesmo padrão técnico e de UI de
 * `ReferenciaPrecoResource` (mesma justificativa de modal em vez de
 * página cheia: sem pages `create`/`edit` em `getPages()`; mesma
 * Lixeira própria + trava de vínculo com Projeto).
 */
class CondicaoFinanceiraResource extends Resource
{
    protected static ?string $model = CondicaoFinanceira::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $cluster = Referencias::class;

    protected static ?string $slug = 'condicoes-financeiras';

    protected static ?int $navigationSort = 2;

    public static function getModelLabel(): string
    {
        return __('comercial::filament/resources/condicao-financeira.model-label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('comercial::filament/resources/condicao-financeira.plural-model-label');
    }

    public static function getNavigationLabel(): string
    {
        return __('comercial::filament/resources/condicao-financeira.navigation.title');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('descricao')
                    ->label(__('comercial::filament/resources/condicao-financeira.form.descricao'))
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                // Mesma lógica de `ReferenciaPrecoResource` — Descrição
                // sozinha não é única, Data/Hora de criação é a
                // identidade visual complementar (ver CLAUDE.md).
                Placeholder::make('created_at')
                    ->label(__('comercial::filament/resources/condicao-financeira.form.created-at'))
                    ->content(fn (?CondicaoFinanceira $record) => $record?->created_at?->format('d/m/Y H:i')
                        ?? __('comercial::filament/resources/condicao-financeira.form.created-at-pendente'))
                    ->columnSpanFull(),

                Grid::make(2)
                    ->schema([
                        TextInput::make('porcentagem_entrada')
                            ->label(__('comercial::filament/resources/condicao-financeira.form.porcentagem-entrada'))
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(0)
                            ->suffix('%'),
                        TextInput::make('qtde_parcelas')
                            ->label(__('comercial::filament/resources/condicao-financeira.form.qtde-parcelas'))
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->default(1),
                        TextInput::make('intervalo_dias')
                            ->label(__('comercial::filament/resources/condicao-financeira.form.intervalo-dias'))
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->default(30)
                            ->suffix(__('comercial::filament/resources/condicao-financeira.form.unidade-dias')),
                        Select::make('forma_pagamento')
                            ->label(__('comercial::filament/resources/condicao-financeira.form.forma-pagamento'))
                            ->options(FormaPagamento::class)
                            ->native(false),
                        // Taxa mensal (ex.: "5,00" = 5% a.m.) — usada para
                        // calcular o fator de amortização pelo Sistema
                        // Price em tempo real, de acordo com a Qtde
                        // Parcelas escolhida no Projeto (ver CLAUDE.md,
                        // "Condições Financeiras"). O cálculo do Total do
                        // Projeto em si é tarefa futura — aqui só o
                        // cadastro da taxa.
                        TextInput::make('taxa_mensal')
                            ->label(__('comercial::filament/resources/condicao-financeira.form.taxa-mensal'))
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->suffix(__('comercial::filament/resources/condicao-financeira.form.unidade-ao-mes'))
                            ->helperText(__('comercial::filament/resources/condicao-financeira.form.taxa-mensal-ajuda')),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('descricao')
                    ->label(__('comercial::filament/resources/condicao-financeira.table.columns.descricao'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('comercial::filament/resources/condicao-financeira.table.columns.created-at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('porcentagem_entrada')
                    ->label(__('comercial::filament/resources/condicao-financeira.table.columns.porcentagem-entrada'))
                    ->formatStateUsing(fn (?string $state) => filled($state) ? number_format((float) $state, 2, ',', '.') . '%' : '—')
                    ->sortable(),
                TextColumn::make('qtde_parcelas')
                    ->label(__('comercial::filament/resources/condicao-financeira.table.columns.qtde-parcelas'))
                    ->sortable(),
                TextColumn::make('intervalo_dias')
                    ->label(__('comercial::filament/resources/condicao-financeira.table.columns.intervalo-dias'))
                    ->sortable(),
                TextColumn::make('forma_pagamento')
                    ->label(__('comercial::filament/resources/condicao-financeira.table.columns.forma-pagamento'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('taxa_mensal')
                    ->label(__('comercial::filament/resources/condicao-financeira.table.columns.taxa-mensal'))
                    ->formatStateUsing(fn (?string $state) => filled($state) ? number_format((float) $state, 2, ',', '.') . '% a.m.' : '—')
                    ->sortable(),
            ])
            ->filters([
                TrashedFilter::make()
                    ->label(__('comercial::filament/resources/condicao-financeira.table.filters.trashed')),
            ])
            ->recordActions([
                EditAction::make()
                    ->before(fn (CondicaoFinanceira $record, EditAction $action) => static::bloquearSeVinculada($record, $action))
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('comercial::filament/resources/condicao-financeira.table.actions.edit.notification.title'))
                            ->body(__('comercial::filament/resources/condicao-financeira.table.actions.edit.notification.body')),
                    ),
                DeleteAction::make()
                    ->before(fn (CondicaoFinanceira $record, DeleteAction $action) => static::bloquearSeVinculada($record, $action))
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('comercial::filament/resources/condicao-financeira.table.actions.delete.notification.title'))
                            ->body(__('comercial::filament/resources/condicao-financeira.table.actions.delete.notification.body')),
                    ),
                RestoreAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('comercial::filament/resources/condicao-financeira.table.actions.restore.notification.title'))
                            ->body(__('comercial::filament/resources/condicao-financeira.table.actions.restore.notification.body')),
                    ),
                ForceDeleteAction::make()
                    ->before(fn (CondicaoFinanceira $record, ForceDeleteAction $action) => static::bloquearSeVinculada($record, $action))
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('comercial::filament/resources/condicao-financeira.table.actions.force-delete.notification.title'))
                            ->body(__('comercial::filament/resources/condicao-financeira.table.actions.force-delete.notification.body')),
                    ),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->before(fn (Collection $records, DeleteBulkAction $action) => static::bloquearSeAlgumaVinculada($records, $action)),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make()
                        ->before(fn (Collection $records, ForceDeleteBulkAction $action) => static::bloquearSeAlgumaVinculada($records, $action)),
                ]),
            ]);
    }

    /**
     * Mesma trava de `ReferenciaPrecoResource::bloquearSeVinculada()`
     * (ver CLAUDE.md, "Trava de exclusão/edição com Projeto vinculado")
     * aplicada a Condição Financeira: uma condição com pelo menos um
     * Projeto vinculado não pode ser excluída nem editada.
     */
    protected static function bloquearSeVinculada(CondicaoFinanceira $condicaoFinanceira, $action): void
    {
        $totalProjetos = $condicaoFinanceira->projetos()->count();

        if ($totalProjetos === 0) {
            return;
        }

        Notification::make()
            ->danger()
            ->title(__('comercial::filament/resources/condicao-financeira.notifications.vinculada.title'))
            ->body(trans_choice(
                'comercial::filament/resources/condicao-financeira.notifications.vinculada.body',
                $totalProjetos,
                ['count' => $totalProjetos],
            ))
            ->send();

        $action->halt();
    }

    /**
     * @param  Collection<int, CondicaoFinanceira>  $condicoesFinanceiras
     */
    protected static function bloquearSeAlgumaVinculada(Collection $condicoesFinanceiras, $action): void
    {
        $vinculadas = $condicoesFinanceiras->filter(fn (CondicaoFinanceira $condicaoFinanceira) => $condicaoFinanceira->projetos()->exists());

        if ($vinculadas->isEmpty()) {
            return;
        }

        Notification::make()
            ->danger()
            ->title(__('comercial::filament/resources/condicao-financeira.notifications.vinculada.title'))
            ->body(__('comercial::filament/resources/condicao-financeira.notifications.vinculada-em-massa.body', [
                'descricoes' => $vinculadas->pluck('descricao')->unique()->implode(', '),
            ]))
            ->send();

        $action->halt();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCondicoesFinanceiras::route('/'),
        ];
    }
}
