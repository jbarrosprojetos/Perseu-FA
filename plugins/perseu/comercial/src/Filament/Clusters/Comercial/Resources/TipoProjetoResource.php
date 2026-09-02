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
use Perseu\Comercial\Filament\Clusters\Comercial\Resources\TipoProjetoResource\Pages\ManageTiposProjeto;
use Perseu\Comercial\Filament\Clusters\Projetos;
use Perseu\Comercial\Models\TipoProjeto;

class TipoProjetoResource extends Resource
{
    protected static ?string $model = TipoProjeto::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static ?string $cluster = Projetos::class;

    protected static ?string $slug = 'tipo-projetos';

    protected static ?int $navigationSort = 2;

    public static function getModelLabel(): string
    {
        return __('comercial::filament/resources/tipo-projeto.model-label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('comercial::filament/resources/tipo-projeto.plural-model-label');
    }

    public static function getNavigationLabel(): string
    {
        return __('comercial::filament/resources/tipo-projeto.navigation.title');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('codigo')
                    ->label(__('comercial::filament/resources/tipo-projeto.form.codigo'))
                    ->required()
                    ->maxLength(1)
                    ->unique(ignoreRecord: true),
                TextInput::make('descricao')
                    ->label(__('comercial::filament/resources/tipo-projeto.form.descricao'))
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
                    ->label(__('comercial::filament/resources/tipo-projeto.table.columns.codigo'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('descricao')
                    ->label(__('comercial::filament/resources/tipo-projeto.table.columns.descricao'))
                    ->searchable()
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('comercial::filament/resources/tipo-projeto.table.actions.edit.notification.title'))
                            ->body(__('comercial::filament/resources/tipo-projeto.table.actions.edit.notification.body')),
                    ),
                DeleteAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('comercial::filament/resources/tipo-projeto.table.actions.delete.notification.title'))
                            ->body(__('comercial::filament/resources/tipo-projeto.table.actions.delete.notification.body')),
                    ),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title(__('comercial::filament/resources/tipo-projeto.table.bulk-actions.delete.notification.title'))
                                ->body(__('comercial::filament/resources/tipo-projeto.table.bulk-actions.delete.notification.body')),
                        ),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageTiposProjeto::route('/'),
        ];
    }
}
