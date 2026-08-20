<?php

namespace Perseu\Pessoas\Filament\Clusters\Pessoas\Resources\PessoaJuridicaResource\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ContatosRelationManager extends RelationManager
{
    protected static string $relationship = 'contatos';

    protected static ?string $recordTitleAttribute = 'cargo';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('pessoas::filament/resources/pessoa-juridica/relation-managers/contatos.title');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('pessoa_fisica_id')
                    ->label(__('pessoas::filament/resources/pessoa-juridica/relation-managers/contatos.form.pessoa-fisica'))
                    ->relationship(name: 'pessoaFisica', titleAttribute: 'nome')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('cargo')
                    ->label(__('pessoas::filament/resources/pessoa-juridica/relation-managers/contatos.form.cargo')),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('cargo')
            ->columns([
                TextColumn::make('pessoaFisica.nome')
                    ->label(__('pessoas::filament/resources/pessoa-juridica/relation-managers/contatos.table.columns.nome'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('pessoaFisica.email')
                    ->label(__('pessoas::filament/resources/pessoa-juridica/relation-managers/contatos.table.columns.email')),
                TextColumn::make('pessoaFisica.telefone')
                    ->label(__('pessoas::filament/resources/pessoa-juridica/relation-managers/contatos.table.columns.telefone')),
                TextColumn::make('cargo')
                    ->label(__('pessoas::filament/resources/pessoa-juridica/relation-managers/contatos.table.columns.cargo')),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(__('pessoas::filament/resources/pessoa-juridica/relation-managers/contatos.table.header-actions.create.label'))
                    ->icon('heroicon-o-plus-circle')
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('pessoas::filament/resources/pessoa-juridica/relation-managers/contatos.table.header-actions.create.notification.title'))
                            ->body(__('pessoas::filament/resources/pessoa-juridica/relation-managers/contatos.table.header-actions.create.notification.body')),
                    ),
            ])
            ->recordActions([
                EditAction::make()
                    ->form([
                        TextInput::make('cargo')
                            ->label(__('pessoas::filament/resources/pessoa-juridica/relation-managers/contatos.form.cargo')),
                    ])
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('pessoas::filament/resources/pessoa-juridica/relation-managers/contatos.table.actions.edit.notification.title'))
                            ->body(__('pessoas::filament/resources/pessoa-juridica/relation-managers/contatos.table.actions.edit.notification.body')),
                    ),
                DeleteAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('pessoas::filament/resources/pessoa-juridica/relation-managers/contatos.table.actions.delete.notification.title'))
                            ->body(__('pessoas::filament/resources/pessoa-juridica/relation-managers/contatos.table.actions.delete.notification.body')),
                    ),
            ]);
    }
}
