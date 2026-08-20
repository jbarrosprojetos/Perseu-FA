<?php

namespace Perseu\Pessoas\Filament\Clusters\Pessoas\Resources;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Perseu\Pessoas\Enums\EstadoCivil;
use Perseu\Pessoas\Enums\Sexo;
use Perseu\Pessoas\Filament\Clusters\Pessoas\Resources\PessoaFisicaResource\Pages\CreatePessoaFisica;
use Perseu\Pessoas\Filament\Clusters\Pessoas\Resources\PessoaFisicaResource\Pages\EditPessoaFisica;
use Perseu\Pessoas\Filament\Clusters\Pessoas\Resources\PessoaFisicaResource\Pages\ListPessoasFisicas;
use Perseu\Pessoas\Filament\Clusters\Pessoas\Resources\PessoaFisicaResource\RelationManagers\EnderecosRelationManager;
use Perseu\Pessoas\Filament\Clusters\PessoasCluster;
use Perseu\Pessoas\Models\PessoaFisica;
use Perseu\Pessoas\Rules\CpfValido;
use Perseu\Pessoas\Traits\HasCompactFieldWidth;

class PessoaFisicaResource extends Resource
{
    use HasCompactFieldWidth;

    protected static ?string $model = PessoaFisica::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user';

    protected static ?string $cluster = PessoasCluster::class;

    protected static ?string $slug = 'pessoas-fisicas';

    public static function getModelLabel(): string
    {
        return __('pessoas::filament/resources/pessoa-fisica.model-label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('pessoas::filament/resources/pessoa-fisica.plural-model-label');
    }

    public static function getNavigationLabel(): string
    {
        return __('pessoas::filament/resources/pessoa-fisica.navigation.title');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nome')
                    ->label(__('pessoas::filament/resources/pessoa-fisica.form.nome'))
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                Select::make('categorias')
                    ->label(__('pessoas::filament/resources/pessoa-fisica.form.categorias'))
                    ->relationship(
                        name: 'categorias',
                        titleAttribute: 'descricao',
                        modifyQueryUsing: fn (Builder $query) => $query->where('aplica_pf', true),
                    )
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->columnSpanFull(),

                static::flexRow([
                    static::compact(
                        TextInput::make('telefone')
                            ->label(__('pessoas::filament/resources/pessoa-fisica.form.telefone'))
                            ->required()
                            ->mask('(99) 99999-9999'),
                        chars: 15, // "(99) 99999-9999"
                    ),
                    static::compactByLabel(
                        Toggle::make('telefone_whatsapp')
                            ->label(__('pessoas::filament/resources/pessoa-fisica.form.telefone-whatsapp'))
                            ->inline(false)
                            ->extraAttributes(['style' => 'transform: scale(0.75); transform-origin: left top; margin-top: 0.125rem;'])
                    ),
                    static::grow(
                        TextInput::make('email')
                            ->label(__('pessoas::filament/resources/pessoa-fisica.form.email'))
                            ->email()
                            ->maxLength(255)
                    ),
                ]),

                static::flexRow([
                    static::compact(
                        TextInput::make('rg')
                            ->label(__('pessoas::filament/resources/pessoa-fisica.form.rg')),
                        chars: 12, // "99.999.999-9"
                    ),
                    static::compact(
                        DatePicker::make('data_nascimento')
                            ->label(__('pessoas::filament/resources/pessoa-fisica.form.data-nascimento')),
                        chars: 10, // "10/11/1971"
                        extraSlack: 2, // ícone de calendário do input
                    ),
                    static::compact(
                        TextInput::make('cpf')
                            ->label(__('pessoas::filament/resources/pessoa-fisica.form.cpf'))
                            ->mask('999.999.999-99')
                            ->rule(new CpfValido())
                            ->unique(ignoreRecord: true),
                        chars: 14, // "999.999.999-99"
                    ),
                ]),

                static::flexRow([
                    static::compact(
                        Select::make('estado_civil')
                            ->label(__('pessoas::filament/resources/pessoa-fisica.form.estado-civil'))
                            ->options(EstadoCivil::class),
                        chars: static::maxEnumLabelChars(EstadoCivil::class),
                    ),
                    static::compact(
                        Select::make('sexo')
                            ->label(__('pessoas::filament/resources/pessoa-fisica.form.sexo'))
                            ->options(Sexo::class),
                        chars: static::maxEnumLabelChars(Sexo::class),
                    ),
                    static::grow(
                        TextInput::make('profissao')
                            ->label(__('pessoas::filament/resources/pessoa-fisica.form.profissao'))
                    ),
                ]),

                Textarea::make('observacoes')
                    ->label(__('pessoas::filament/resources/pessoa-fisica.form.observacoes'))
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nome')
                    ->label(__('pessoas::filament/resources/pessoa-fisica.table.columns.nome'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('telefone')
                    ->label(__('pessoas::filament/resources/pessoa-fisica.table.columns.telefone'))
                    ->searchable(),
                TextColumn::make('email')
                    ->label(__('pessoas::filament/resources/pessoa-fisica.table.columns.email'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('cpf')
                    ->label(__('pessoas::filament/resources/pessoa-fisica.table.columns.cpf'))
                    ->searchable(),
                TextColumn::make('estado_civil')
                    ->label(__('pessoas::filament/resources/pessoa-fisica.table.columns.estado-civil'))
                    ->badge(),
                TextColumn::make('categorias.descricao')
                    ->label(__('pessoas::filament/resources/pessoa-fisica.table.columns.categorias'))
                    ->badge(),
                TextColumn::make('created_at')
                    ->label(__('pessoas::filament/resources/pessoa-fisica.table.columns.created-at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('pessoas::filament/resources/pessoa-fisica.table.actions.edit.notification.title'))
                            ->body(__('pessoas::filament/resources/pessoa-fisica.table.actions.edit.notification.body')),
                    ),
                DeleteAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('pessoas::filament/resources/pessoa-fisica.table.actions.delete.notification.title'))
                            ->body(__('pessoas::filament/resources/pessoa-fisica.table.actions.delete.notification.body')),
                    ),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListPessoasFisicas::route('/'),
            'create' => CreatePessoaFisica::route('/create'),
            'edit'   => EditPessoaFisica::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            EnderecosRelationManager::class,
        ];
    }
}
