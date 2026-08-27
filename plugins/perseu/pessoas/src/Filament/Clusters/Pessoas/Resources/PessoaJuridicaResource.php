<?php

namespace Perseu\Pessoas\Filament\Clusters\Pessoas\Resources;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rules\Unique;
use Perseu\Pessoas\Enums\IndicadorContribuinteIcms;
use Perseu\Pessoas\Enums\RegimeTributario;
use Perseu\Pessoas\Filament\Clusters\Pessoas\Resources\PessoaJuridicaResource\Pages\CreatePessoaJuridica;
use Perseu\Pessoas\Filament\Clusters\Pessoas\Resources\PessoaJuridicaResource\Pages\EditPessoaJuridica;
use Perseu\Pessoas\Filament\Clusters\Pessoas\Resources\PessoaJuridicaResource\Pages\ListPessoasJuridicas;
use Perseu\Pessoas\Filament\Clusters\Pessoas\Resources\PessoaJuridicaResource\RelationManagers\ContatosRelationManager;
use Perseu\Pessoas\Filament\Clusters\Pessoas\Resources\PessoaJuridicaResource\RelationManagers\EnderecosRelationManager;
use Perseu\Pessoas\Models\PessoaJuridica;
use Perseu\Pessoas\Rules\CnpjNaoExcluido;
use Perseu\Pessoas\Rules\CnpjValido;
use Perseu\Pessoas\Support\BrasilApiCnpjLookup;
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
                static::flexRow([
                    static::compact(
                        TextInput::make('cnpj')
                            ->label(__('pessoas::filament/resources/pessoa-juridica.form.cnpj'))
                            ->mask('99.999.999/9999-99')
                            ->rule(new CnpjValido())
                            // whereNull('deleted_at'): sem isso, um CNPJ que
                            // pertence a uma Pessoa Jurídica soft-deleted
                            // bloqueava a criação com a mensagem genérica de
                            // "já se encontra registrado" — a checagem
                            // específica de registro excluído (com mensagem
                            // própria) é a regra CnpjNaoExcluido logo abaixo.
                            ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule) => $rule->whereNull('deleted_at'))
                            ->rule(fn (?PessoaJuridica $record) => new CnpjNaoExcluido($record?->id))
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Set $set, Get $get, ?string $state) => BrasilApiCnpjLookup::fill($set, $get, $state))
                            ->hint(fn (Get $get) => $get('cnpj_lookup_erro'))
                            ->hintColor('danger'),
                        chars: 18, // "99.999.999/9999-99"
                    ),
                    static::grow(
                        TextInput::make('razao_social')
                            ->label(__('pessoas::filament/resources/pessoa-juridica.form.razao-social'))
                            ->maxLength(255)
                    ),
                ]),

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

                Select::make('setores')
                    ->label(__('pessoas::filament/resources/pessoa-juridica.form.setores'))
                    ->relationship(name: 'setores', titleAttribute: 'descricao')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->columnSpanFull(),

                static::flexRow([
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

                // Dados vindos da Receita Federal via busca de CNPJ (ver
                // BrasilApiCnpjLookup): Situação Cadastral é somente
                // leitura (badge), CNAE mostra a descrição como texto
                // auxiliar (por isso mais largo — a descrição embaixo do
                // campo pode ser bem mais longa que o código em si) e
                // Porte fica na linha seguinte, com os demais campos
                // fiscais editáveis (ver CLAUDE.md).
                static::flexRow([
                    static::compact(
                        Placeholder::make('situacao_cadastral_display')
                            ->label(__('pessoas::filament/resources/pessoa-juridica.form.situacao-cadastral'))
                            ->content(fn (Get $get) => static::situacaoCadastralBadge($get))
                            ->hidden(fn (Get $get) => blank($get('descricao_situacao_cadastral'))),
                        chars: 12,
                    ),
                    static::compact(
                        TextInput::make('cnae')
                            ->label(__('pessoas::filament/resources/pessoa-juridica.form.cnae'))
                            ->mask('9999-9/99')
                            ->helperText(fn (Get $get) => $get('cnae_descricao')),
                        chars: 45, // campo alargado para a descrição (helper text) não ficar espremida — o valor em si continua sendo só "9999-9/99"
                    ),
                ]),

                Hidden::make('situacao_cadastral'),
                Hidden::make('descricao_situacao_cadastral'),
                Hidden::make('cnae_descricao'),
                Hidden::make('descricao_porte'),

                static::flexRow([
                    static::compact(
                        DatePicker::make('data_abertura')
                            ->label(__('pessoas::filament/resources/pessoa-juridica.form.data-abertura')),
                        chars: 10, // "10/11/1971"
                        extraSlack: 2, // ícone de calendário do input
                    ),
                    static::compact(
                        TextInput::make('inscricao_estadual')
                            ->label(__('pessoas::filament/resources/pessoa-juridica.form.inscricao-estadual')),
                        chars: 14, // sem máscara fixa (varia por estado); baseado em formatos comuns de 12-14 dígitos
                    ),
                    static::compact(
                        Select::make('indicador_contribuinte_icms')
                            ->label(__('pessoas::filament/resources/pessoa-juridica.form.indicador-contribuinte-icms'))
                            ->options(IndicadorContribuinteIcms::class),
                        chars: static::maxEnumLabelChars(IndicadorContribuinteIcms::class),
                    ),
                    static::compact(
                        Select::make('regime_tributario')
                            ->label(__('pessoas::filament/resources/pessoa-juridica.form.regime-tributario'))
                            ->options(RegimeTributario::class)
                            ->default(RegimeTributario::NaoInformado->value),
                        chars: static::maxEnumLabelChars(RegimeTributario::class),
                    ),
                    static::compact(
                        TextInput::make('porte')
                            ->label(__('pessoas::filament/resources/pessoa-juridica.form.porte'))
                            ->helperText(fn (Get $get) => $get('descricao_porte')),
                        chars: 4,
                    ),
                ]),

                Textarea::make('observacoes')
                    ->label(__('pessoas::filament/resources/pessoa-juridica.form.observacoes'))
                    ->columnSpanFull(),
            ]);
    }

    private static function situacaoCadastralBadge(Get $get): ?HtmlString
    {
        $descricao = $get('descricao_situacao_cadastral');

        if (blank($descricao)) {
            return null;
        }

        return new HtmlString(Blade::render(
            '<x-filament::badge :color="$color">{{ $label }}</x-filament::badge>',
            ['color' => static::situacaoCadastralColor($get('situacao_cadastral')), 'label' => $descricao],
        ));
    }

    /**
     * Cores conforme os códigos oficiais de situação cadastral informados
     * para esta tarefa (ver CLAUDE.md): 02 Ativa, 03 Suspensa, 04 Inapta,
     * 01/08 Baixada, 05 Nula.
     */
    private static function situacaoCadastralColor(?string $codigo): string
    {
        return match ($codigo) {
            '2'      => 'success',
            '3'      => 'warning',
            '4', '5' => 'danger',
            '1', '8' => 'gray',
            default  => 'gray',
        };
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
                TextColumn::make('setores.descricao')
                    ->label(__('pessoas::filament/resources/pessoa-juridica.table.columns.setores'))
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
