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
use Perseu\Comercial\Filament\Clusters\Comercial\Resources\SituacaoProjetoResource\Pages\ManageSituacoesProjeto;
use Perseu\Comercial\Models\SituacaoProjeto;
use Webkul\Support\Enums\NavigationGroup;

class SituacaoProjetoResource extends Resource
{
    protected static ?string $model = SituacaoProjeto::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-flag';

    protected static ?string $slug = 'comercial/situacao-projetos';

    public static function getNavigationGroup(): string|\UnitEnum
    {
        return NavigationGroup::Comercial;
    }

    public static function getModelLabel(): string
    {
        return __('comercial::filament/resources/situacao-projeto.model-label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('comercial::filament/resources/situacao-projeto.plural-model-label');
    }

    public static function getNavigationLabel(): string
    {
        return __('comercial::filament/resources/situacao-projeto.navigation.title');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('descricao')
                    ->label(__('comercial::filament/resources/situacao-projeto.form.descricao'))
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
                    ->label(__('comercial::filament/resources/situacao-projeto.table.columns.descricao'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('comercial::filament/resources/situacao-projeto.table.columns.created-at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('comercial::filament/resources/situacao-projeto.table.actions.edit.notification.title'))
                            ->body(__('comercial::filament/resources/situacao-projeto.table.actions.edit.notification.body')),
                    ),
                DeleteAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('comercial::filament/resources/situacao-projeto.table.actions.delete.notification.title'))
                            ->body(__('comercial::filament/resources/situacao-projeto.table.actions.delete.notification.body')),
                    ),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title(__('comercial::filament/resources/situacao-projeto.table.bulk-actions.delete.notification.title'))
                                ->body(__('comercial::filament/resources/situacao-projeto.table.bulk-actions.delete.notification.body')),
                        ),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSituacoesProjeto::route('/'),
        ];
    }
}
