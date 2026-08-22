<?php

namespace Perseu\Pessoas\Filament\Clusters\Pessoas\Resources;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Perseu\Pessoas\Filament\Clusters\Pessoas\Resources\CategoriaPessoaResource\Pages\ManageCategoriaPessoas;
use Perseu\Pessoas\Filament\Clusters\PessoasCluster;
use Perseu\Pessoas\Models\CategoriaPessoa;

class CategoriaPessoaResource extends Resource
{
    protected static ?string $model = CategoriaPessoa::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static ?string $cluster = PessoasCluster::class;

    public static function getModelLabel(): string
    {
        return __('pessoas::filament/resources/categoria-pessoa.model-label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('pessoas::filament/resources/categoria-pessoa.plural-model-label');
    }

    public static function getNavigationLabel(): string
    {
        return __('pessoas::filament/resources/categoria-pessoa.navigation.title');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('descricao')
                    ->label(__('pessoas::filament/resources/categoria-pessoa.form.descricao'))
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Toggle::make('aplica_pf')
                    ->label(__('pessoas::filament/resources/categoria-pessoa.form.aplica-pf')),
                Toggle::make('aplica_pj')
                    ->label(__('pessoas::filament/resources/categoria-pessoa.form.aplica-pj')),
                Toggle::make('e_cliente')
                    ->label(__('pessoas::filament/resources/categoria-pessoa.form.e-cliente')),
                Toggle::make('e_fornecedor')
                    ->label(__('pessoas::filament/resources/categoria-pessoa.form.e-fornecedor')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('descricao')
                    ->label(__('pessoas::filament/resources/categoria-pessoa.table.columns.descricao'))
                    ->searchable()
                    ->sortable(),
                IconColumn::make('aplica_pf')
                    ->label(__('pessoas::filament/resources/categoria-pessoa.table.columns.aplica-pf'))
                    ->boolean(),
                IconColumn::make('aplica_pj')
                    ->label(__('pessoas::filament/resources/categoria-pessoa.table.columns.aplica-pj'))
                    ->boolean(),
                IconColumn::make('e_cliente')
                    ->label(__('pessoas::filament/resources/categoria-pessoa.table.columns.e-cliente'))
                    ->boolean(),
                IconColumn::make('e_fornecedor')
                    ->label(__('pessoas::filament/resources/categoria-pessoa.table.columns.e-fornecedor'))
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label(__('pessoas::filament/resources/categoria-pessoa.table.columns.created-at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('pessoas::filament/resources/categoria-pessoa.table.actions.edit.notification.title'))
                            ->body(__('pessoas::filament/resources/categoria-pessoa.table.actions.edit.notification.body')),
                    ),
                DeleteAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('pessoas::filament/resources/categoria-pessoa.table.actions.delete.notification.title'))
                            ->body(__('pessoas::filament/resources/categoria-pessoa.table.actions.delete.notification.body')),
                    ),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title(__('pessoas::filament/resources/categoria-pessoa.table.bulk-actions.delete.notification.title'))
                                ->body(__('pessoas::filament/resources/categoria-pessoa.table.bulk-actions.delete.notification.body')),
                        ),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCategoriaPessoas::route('/'),
        ];
    }
}
