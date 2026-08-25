<?php

namespace Perseu\Pessoas\Filament\Clusters\Pessoas\Resources;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Perseu\Pessoas\Enums\RegimeTributario;
use Perseu\Pessoas\Filament\Clusters\Pessoas\Resources\PessoaJuridicaResource\Pages\CreatePessoaJuridica;
use Perseu\Pessoas\Filament\Clusters\Pessoas\Resources\PessoaJuridicaResource\Pages\EditPessoaJuridica;
use Perseu\Pessoas\Filament\Clusters\Pessoas\Resources\PessoaJuridicaResource\Pages\ListPessoasJuridicas;
use Perseu\Pessoas\Filament\Clusters\Pessoas\Resources\PessoaJuridicaResource\RelationManagers\ContatosRelationManager;
use Perseu\Pessoas\Filament\Clusters\Pessoas\Resources\PessoaJuridicaResource\RelationManagers\EnderecosRelationManager;
use Perseu\Pessoas\Models\PessoaJuridica;
use Perseu\Pessoas\Rules\CnpjValido;
use Perseu\Pessoas\Traits\HasCompactFieldWidth;
use Webkul\Support\Enums\NavigationGroup;

class PessoaJuridicaResource extends Resource
{
    use HasCompactFieldWidth;

    protected static ?string $model = PessoaJuridica::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $slug = 'pessoas/pessoas-juridicas';

    public static function getNavigationGroup(): string|\UnitEnum
    {
        return NavigationGroup::Pessoas;
    }

    public static function getModelLabel(): string
    {
        return __('pessoas::filament/resources/pessoa-juridica.model-label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('pessoas::filament/resources/pessoa-juridica.plural-model-label');
    }

    public static function getNavigationLabel(): string
    {
        return __('pessoas::filament/resources/pessoa-juridica.navigation.title');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('razao_social')
                    ->label(__('pessoas::filament/resources/pessoa-juridica.form.razao-social'))
                    ->maxLength(255)
                    ->columnSpanFull(),

                TextInput::make('nome_fantasia')
                    ->label(__('pessoas::filament/resources/pessoa-juridica.form.nome-fantasia'))
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                Select::make('categorias')
                    ->label(__('pessoas::filament/resources/pessoa-juridica.form.categorias'))
                    ->relationship(
                        name: 'categorias',
                        titleAttribute: 'descricao',
                        modifyQueryUsing: fn (Builder $query) => $query->where('aplica_pj', true),
                    )
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->columnSpanFull(),

                static::flexRow([
                    static::compact(
                        TextInput::make('cnpj')
                            ->label(__('pessoas::filament/resources/pessoa-juridica.form.cnpj'))
                            ->mask('99.999.999/9999-99')
                            ->rule(new CnpjValido())
                            ->unique(ignoreRecord: true),
                        chars: 18, // "99.999.999/9999-99"
                    ),
                    static::compact(
                        TextInput::make('telefone')
                            ->label(__('pessoas::filament/resources/pessoa-juridica.form.telefone'))
                            ->required()
                            ->mask('(99) 99999-9999'),
                        chars: 15, // "(99) 99999-9999"
                    ),
                    static::grow(
                        TextInput::make('email')
                            ->label(__('pessoas::filament/resources/pessoa-juridica.form.email'))
                            ->email()
                            ->maxLength(255)
                    ),
                ]),

                static::flexRow([
                    static::compact(
                        TextInput::make('inscricao_estadual')
                            ->label(__('pessoas::filament/resources/pessoa-juridica.form.inscricao-estadual')),
                        chars: 14, // sem máscara fixa (varia por estado); baseado em formatos comuns de 12-14 dígitos
                    ),
                    static::compact(
                        TextInput::make('cnae')
                            ->label(__('pessoas::filament/resources/pessoa-juridica.form.cnae'))
                            ->mask('9999-9/99'),
                        chars: 9, // "9999-9/99"
                    ),
                    static::compact(
                        Select::make('regime_tributario')
                            ->label(__('pessoas::filament/resources/pessoa-juridica.form.regime-tributario'))
                            ->options(RegimeTributario::class),
                        chars: static::maxEnumLabelChars(RegimeTributario::class),
                    ),
                ]),

                static::flexRow([
                    static::compact(
                        DatePicker::make('data_abertura')
                            ->label(__('pessoas::filament/resources/pessoa-juridica.form.data-abertura')),
                        chars: 10, // "10/11/1971"
                        extraSlack: 2, // ícone de calendário do input
                    ),
                ]),

                Textarea::make('observacoes')
                    ->label(__('pessoas::filament/resources/pessoa-juridica.form.observacoes'))
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nome_fantasia')
                    ->label(__('pessoas::filament/resources/pessoa-juridica.table.columns.nome-fantasia'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('razao_social')
                    ->label(__('pessoas::filament/resources/pessoa-juridica.table.columns.razao-social'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('cnpj')
                    ->label(__('pessoas::filament/resources/pessoa-juridica.table.columns.cnpj'))
                    ->searchable(),
                TextColumn::make('telefone')
                    ->label(__('pessoas::filament/resources/pessoa-juridica.table.columns.telefone')),
                TextColumn::make('regime_tributario')
                    ->label(__('pessoas::filament/resources/pessoa-juridica.table.columns.regime-tributario'))
                    ->badge(),
                TextColumn::make('categorias.descricao')
                    ->label(__('pessoas::filament/resources/pessoa-juridica.table.columns.categorias'))
                    ->badge(),
                TextColumn::make('created_at')
                    ->label(__('pessoas::filament/resources/pessoa-juridica.table.columns.created-at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('pessoas::filament/resources/pessoa-juridica.table.actions.edit.notification.title'))
                            ->body(__('pessoas::filament/resources/pessoa-juridica.table.actions.edit.notification.body')),
                    ),
                DeleteAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('pessoas::filament/resources/pessoa-juridica.table.actions.delete.notification.title'))
                            ->body(__('pessoas::filament/resources/pessoa-juridica.table.actions.delete.notification.body')),
                    ),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListPessoasJuridicas::route('/'),
            'create' => CreatePessoaJuridica::route('/create'),
            'edit'   => EditPessoaJuridica::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            EnderecosRelationManager::class,
            ContatosRelationManager::class,
        ];
    }
}
