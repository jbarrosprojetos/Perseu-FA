<?php

namespace Perseu\Comercial\Filament\Clusters\Comercial\Resources;

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
use Perseu\Comercial\Filament\Clusters\Comercial\Resources\TipoObraResource\Pages\ManageTiposObra;
use Perseu\Comercial\Models\TipoObra;
use Webkul\Support\Enums\NavigationGroup;

class TipoObraResource extends Resource
{
    protected static ?string $model = TipoObra::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static ?string $slug = 'comercial/tipo-obras';

    public static function getNavigationGroup(): string|\UnitEnum
    {
        return NavigationGroup::Comercial;
    }

    public static function getModelLabel(): string
    {
        return __('comercial::filament/resources/tipo-obra.model-label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('comercial::filament/resources/tipo-obra.plural-model-label');
    }

    public static function getNavigationLabel(): string
    {
        return __('comercial::filament/resources/tipo-obra.navigation.title');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('codigo')
                    ->label(__('comercial::filament/resources/tipo-obra.form.codigo'))
                    ->required()
                    ->maxLength(1)
                    ->unique(ignoreRecord: true),
                TextInput::make('descricao')
                    ->label(__('comercial::filament/resources/tipo-obra.form.descricao'))
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('codigo')
                    ->label(__('comercial::filament/resources/tipo-obra.table.columns.codigo'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('descricao')
                    ->label(__('comercial::filament/resources/tipo-obra.table.columns.descricao'))
                    ->searchable()
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('comercial::filament/resources/tipo-obra.table.actions.edit.notification.title'))
                            ->body(__('comercial::filament/resources/tipo-obra.table.actions.edit.notification.body')),
                    ),
                DeleteAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('comercial::filament/resources/tipo-obra.table.actions.delete.notification.title'))
                            ->body(__('comercial::filament/resources/tipo-obra.table.actions.delete.notification.body')),
                    ),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title(__('comercial::filament/resources/tipo-obra.table.bulk-actions.delete.notification.title'))
                                ->body(__('comercial::filament/resources/tipo-obra.table.bulk-actions.delete.notification.body')),
                        ),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageTiposObra::route('/'),
        ];
    }
}
