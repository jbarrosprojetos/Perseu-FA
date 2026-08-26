<?php

namespace Perseu\Pessoas\Filament\Clusters\Pessoas\Resources;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Perseu\Pessoas\Filament\Clusters\Pessoas\Resources\SetorResource\Pages\ManageSetores;
use Perseu\Pessoas\Models\Setor;
use Webkul\Support\Enums\NavigationGroup;

class SetorResource extends Resource
{
    protected static ?string $model = Setor::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $slug = 'pessoas/setores';

    public static function getNavigationGroup(): string|\UnitEnum
    {
        return NavigationGroup::Pessoas;
    }

    public static function getModelLabel(): string
    {
        return __('pessoas::filament/resources/setor.model-label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('pessoas::filament/resources/setor.plural-model-label');
    }

    public static function getNavigationLabel(): string
    {
        return __('pessoas::filament/resources/setor.navigation.title');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('descricao')
                    ->label(__('pessoas::filament/resources/setor.form.descricao'))
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('descricao')
                    ->label(__('pessoas::filament/resources/setor.table.columns.descricao'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('pessoas::filament/resources/setor.table.columns.created-at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('pessoas::filament/resources/setor.table.actions.edit.notification.title'))
                            ->body(__('pessoas::filament/resources/setor.table.actions.edit.notification.body')),
                    ),
                DeleteAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('pessoas::filament/resources/setor.table.actions.delete.notification.title'))
                            ->body(__('pessoas::filament/resources/setor.table.actions.delete.notification.body')),
                    ),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title(__('pessoas::filament/resources/setor.table.bulk-actions.delete.notification.title'))
                                ->body(__('pessoas::filament/resources/setor.table.bulk-actions.delete.notification.body')),
                        ),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSetores::route('/'),
        ];
    }
}
