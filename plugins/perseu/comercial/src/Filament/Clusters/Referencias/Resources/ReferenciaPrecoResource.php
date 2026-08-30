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
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Perseu\Comercial\Filament\Clusters\Referencias;
use Perseu\Comercial\Filament\Clusters\Referencias\Resources\ReferenciaPrecoResource\Pages\ListReferenciasPrecos;
use Perseu\Comercial\Models\ReferenciaPreco;

/**
 * Cadastro de apoio do Cluster Referências (ver CLAUDE.md) — várias
 * tabelas de preços coexistindo ao mesmo tempo (NÃO é histórico/
 * versionamento), escolhidas na hora de montar uma Proposta (fora de
 * escopo aqui). Mesmo padrão de Lixeira/Auditoria de qualquer Model de
 * negócio novo (ver "Convenção para todo Model de cadastro de negócio
 * criado a partir de agora" no CLAUDE.md): sem aba de Atividades
 * própria (auditoria só pela Central), com Lixeira própria (`TrashedFilter`/
 * `RestoreAction`/`ForceDeleteAction`) além de já entrar na Lixeira
 * Central (`Perseu\Auditoria\Support\TrashCatalog`).
 *
 * Criar/editar acontece em MODAL, não em página cheia (mesmo padrão
 * técnico de `BranchesRelationManager` — ver CLAUDE.md): não há pages
 * `create`/`edit` registradas em `getPages()`, então
 * `Filament\Resources\Pages\Page::getDefaultActionUrl()` (que só
 * redireciona pra uma URL quando `hasPage('create'|'edit')` é
 * verdadeiro) nunca encontra uma URL pra usar, e `CreateAction`/
 * `EditAction` caem no comportamento padrão de Action — abrir modal.
 */
class ReferenciaPrecoResource extends Resource
{
    protected static ?string $model = ReferenciaPreco::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $cluster = Referencias::class;

    protected static ?string $slug = 'precos';

    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return __('comercial::filament/resources/referencia-preco.model-label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('comercial::filament/resources/referencia-preco.plural-model-label');
    }

    public static function getNavigationLabel(): string
    {
        return __('comercial::filament/resources/referencia-preco.navigation.title');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('descricao')
                    ->label(__('comercial::filament/resources/referencia-preco.form.descricao'))
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                Grid::make(2)
                    ->schema([
                        TextInput::make('laminacao')
                            ->label(__('comercial::filament/resources/referencia-preco.form.laminacao'))
                            ->numeric()
                            ->required()
                            ->prefix('R$')
                            ->suffix(__('comercial::filament/resources/referencia-preco.form.unidade-metro-linear')),
                        TextInput::make('corte')
                            ->label(__('comercial::filament/resources/referencia-preco.form.corte'))
                            ->numeric()
                            ->required()
                            ->prefix('R$')
                            ->suffix(__('comercial::filament/resources/referencia-preco.form.unidade-metro-linear')),
                        TextInput::make('hora_producao')
                            ->label(__('comercial::filament/resources/referencia-preco.form.hora-producao'))
                            ->numeric()
                            ->required()
                            ->prefix('R$')
                            ->suffix(__('comercial::filament/resources/referencia-preco.form.unidade-metro-quadrado')),
                        TextInput::make('hora_execucao')
                            ->label(__('comercial::filament/resources/referencia-preco.form.hora-execucao'))
                            ->numeric()
                            ->required()
                            ->prefix('R$')
                            ->suffix(__('comercial::filament/resources/referencia-preco.form.unidade-metro-quadrado')),
                        TextInput::make('retencao_tecnica')
                            ->label(__('comercial::filament/resources/referencia-preco.form.retencao-tecnica'))
                            ->numeric()
                            ->required()
                            ->suffix('%'),
                        TextInput::make('imposto')
                            ->label(__('comercial::filament/resources/referencia-preco.form.imposto'))
                            ->numeric()
                            ->required()
                            ->suffix('%'),
                        TextInput::make('despesas_variaveis')
                            ->label(__('comercial::filament/resources/referencia-preco.form.despesas-variaveis'))
                            ->numeric()
                            ->required()
                            ->suffix('%'),
                        TextInput::make('despesas_fixas')
                            ->label(__('comercial::filament/resources/referencia-preco.form.despesas-fixas'))
                            ->numeric()
                            ->required()
                            ->suffix('%'),
                    ]),
            ]);
    }

    /**
     * Reaproveitado pelas 4 colunas percentuais da tabela (retenção
     * técnica, imposto, despesas variáveis, despesas fixas) — mesma
     * lógica de formatação repetida seria a mesma linha 4 vezes.
     */
    protected static function formatPercent(?string $state): string
    {
        return filled($state) ? number_format((float) $state, 2, ',', '.') . '%' : '—';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('descricao')
                    ->label(__('comercial::filament/resources/referencia-preco.table.columns.descricao'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('laminacao')
                    ->label(__('comercial::filament/resources/referencia-preco.table.columns.laminacao'))
                    ->money('BRL')
                    ->sortable(),
                TextColumn::make('corte')
                    ->label(__('comercial::filament/resources/referencia-preco.table.columns.corte'))
                    ->money('BRL')
                    ->sortable(),
                TextColumn::make('hora_producao')
                    ->label(__('comercial::filament/resources/referencia-preco.table.columns.hora-producao'))
                    ->money('BRL')
                    ->sortable(),
                TextColumn::make('hora_execucao')
                    ->label(__('comercial::filament/resources/referencia-preco.table.columns.hora-execucao'))
                    ->money('BRL')
                    ->sortable(),
                TextColumn::make('retencao_tecnica')
                    ->label(__('comercial::filament/resources/referencia-preco.table.columns.retencao-tecnica'))
                    ->formatStateUsing(fn (?string $state) => static::formatPercent($state))
                    ->sortable(),
                TextColumn::make('imposto')
                    ->label(__('comercial::filament/resources/referencia-preco.table.columns.imposto'))
                    ->formatStateUsing(fn (?string $state) => static::formatPercent($state))
                    ->sortable(),
                TextColumn::make('despesas_variaveis')
                    ->label(__('comercial::filament/resources/referencia-preco.table.columns.despesas-variaveis'))
                    ->formatStateUsing(fn (?string $state) => static::formatPercent($state))
                    ->sortable(),
                TextColumn::make('despesas_fixas')
                    ->label(__('comercial::filament/resources/referencia-preco.table.columns.despesas-fixas'))
                    ->formatStateUsing(fn (?string $state) => static::formatPercent($state))
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('comercial::filament/resources/referencia-preco.table.columns.created-at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make()
                    ->label(__('comercial::filament/resources/referencia-preco.table.filters.trashed')),
            ])
            ->recordActions([
                EditAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('comercial::filament/resources/referencia-preco.table.actions.edit.notification.title'))
                            ->body(__('comercial::filament/resources/referencia-preco.table.actions.edit.notification.body')),
                    ),
                DeleteAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('comercial::filament/resources/referencia-preco.table.actions.delete.notification.title'))
                            ->body(__('comercial::filament/resources/referencia-preco.table.actions.delete.notification.body')),
                    ),
                RestoreAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('comercial::filament/resources/referencia-preco.table.actions.restore.notification.title'))
                            ->body(__('comercial::filament/resources/referencia-preco.table.actions.restore.notification.body')),
                    ),
                ForceDeleteAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('comercial::filament/resources/referencia-preco.table.actions.force-delete.notification.title'))
                            ->body(__('comercial::filament/resources/referencia-preco.table.actions.force-delete.notification.body')),
                    ),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReferenciasPrecos::route('/'),
        ];
    }
}
