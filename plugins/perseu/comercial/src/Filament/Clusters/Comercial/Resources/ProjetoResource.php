<?php

namespace Perseu\Comercial\Filament\Clusters\Comercial\Resources;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Perseu\Comercial\Filament\Clusters\Comercial\Resources\ProjetoResource\Pages\CreateProjeto;
use Perseu\Comercial\Filament\Clusters\Comercial\Resources\ProjetoResource\Pages\EditProjeto;
use Perseu\Comercial\Filament\Clusters\Comercial\Resources\ProjetoResource\Pages\ListProjetos;
use Perseu\Comercial\Filament\Clusters\ComercialCluster;
use Perseu\Comercial\Models\Projeto;
use Perseu\Pessoas\Enums\TipoEndereco;
use Perseu\Pessoas\Models\Contato;
use Perseu\Pessoas\Models\Endereco;
use Perseu\Pessoas\Models\PessoaFisica;
use Perseu\Pessoas\Models\PessoaJuridica;
use Perseu\Pessoas\Support\ViaCepLookup;
use Perseu\Pessoas\Traits\HasCompactFieldWidth;

class ProjetoResource extends Resource
{
    use HasCompactFieldWidth;

    protected static ?string $model = Projeto::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $cluster = ComercialCluster::class;

    public static function getModelLabel(): string
    {
        return __('comercial::filament/resources/projeto.model-label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('comercial::filament/resources/projeto.plural-model-label');
    }

    public static function getNavigationLabel(): string
    {
        return __('comercial::filament/resources/projeto.navigation.title');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('descricao')
                    ->label(__('comercial::filament/resources/projeto.form.descricao'))
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                static::compact(
                    Select::make('tipo_projeto_id')
                        ->label(__('comercial::filament/resources/projeto.form.tipo-projeto'))
                        ->relationship('tipoProjeto', 'descricao')
                        ->required()
                        ->searchable()
                        ->preload(),
                    // sem enum para calcular dinamicamente (descrição livre) — revisar
                    // se os tipos cadastrados passarem a ter descrições bem mais longas.
                    chars: 24,
                ),

                Select::make('situacoes')
                    ->label(__('comercial::filament/resources/projeto.form.situacoes'))
                    ->relationship(name: 'situacoes', titleAttribute: 'descricao')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->columnSpanFull(),

                Radio::make('tipo_contratante')
                    ->label(__('comercial::filament/resources/projeto.form.tipo-contratante'))
                    ->options([
                        'pf' => __('comercial::filament/resources/projeto.form.tipo-contratante-options.pessoa-fisica'),
                        'pj' => __('comercial::filament/resources/projeto.form.tipo-contratante-options.pessoa-juridica'),
                    ])
                    ->live()
                    ->dehydrated(false)
                    ->afterStateHydrated(function (Radio $component, ?Model $record): void {
                        if (! $record) {
                            return;
                        }

                        $component->state(match (true) {
                            filled($record->pessoa_juridica_id) => 'pj',
                            filled($record->pessoa_fisica_id) => 'pf',
                            default => null,
                        });
                    })
                    ->afterStateUpdated(function (Set $set, ?string $state): void {
                        if ($state !== 'pf') {
                            $set('pessoa_fisica_id', null);
                        }

                        if ($state !== 'pj') {
                            $set('pessoa_juridica_id', null);
                            $set('contato_pessoa_fisica_id', null);
                        }

                        $set('endereco_id', null);
                    })
                    ->required(),

                Select::make('pessoa_fisica_id')
                    ->label(__('comercial::filament/resources/projeto.form.pessoa-fisica'))
                    ->relationship(
                        name: 'pessoaFisica',
                        titleAttribute: 'nome',
                        modifyQueryUsing: fn (Builder $query) => $query->whereHas(
                            'categorias',
                            fn (Builder $query) => $query->where('e_cliente', true),
                        ),
                    )
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(fn (Set $set) => $set('endereco_id', null))
                    ->visible(fn (Get $get) => $get('tipo_contratante') === 'pf')
                    ->required(fn (Get $get) => $get('tipo_contratante') === 'pf'),

                Select::make('pessoa_juridica_id')
                    ->label(__('comercial::filament/resources/projeto.form.pessoa-juridica'))
                    ->relationship(
                        name: 'pessoaJuridica',
                        titleAttribute: 'nome_fantasia',
                        modifyQueryUsing: fn (Builder $query) => $query->whereHas(
                            'categorias',
                            fn (Builder $query) => $query->where('e_cliente', true),
                        ),
                    )
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function (Set $set): void {
                        $set('contato_pessoa_fisica_id', null);
                        $set('endereco_id', null);
                    })
                    ->visible(fn (Get $get) => $get('tipo_contratante') === 'pj')
                    ->required(fn (Get $get) => $get('tipo_contratante') === 'pj'),

                Select::make('contato_pessoa_fisica_id')
                    ->label(__('comercial::filament/resources/projeto.form.contato'))
                    ->options(function (Get $get): array {
                        $pessoaJuridicaId = $get('pessoa_juridica_id');

                        if (blank($pessoaJuridicaId)) {
                            return [];
                        }

                        return Contato::query()
                            ->where('pessoa_juridica_id', $pessoaJuridicaId)
                            ->with('pessoaFisica')
                            ->get()
                            ->mapWithKeys(fn (Contato $contato) => [
                                $contato->pessoa_fisica_id => $contato->pessoaFisica?->nome,
                            ])
                            ->filter()
                            ->toArray();
                    })
                    ->searchable()
                    ->live()
                    ->visible(fn (Get $get) => $get('tipo_contratante') === 'pj'),

                Select::make('endereco_id')
                    ->label(__('comercial::filament/resources/projeto.form.endereco'))
                    ->options(function (Get $get): array {
                        return static::enderecoOptionsFor($get('pessoa_fisica_id'), $get('pessoa_juridica_id'));
                    })
                    ->searchable()
                    ->live()
                    ->createOptionForm([
                        TextInput::make('cep')
                            ->label(__('comercial::filament/resources/projeto.form.endereco-form.cep'))
                            ->mask('99999-999')
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Set $set, ?string $state) => ViaCepLookup::fill($set, $state)),
                        TextInput::make('logradouro')
                            ->label(__('comercial::filament/resources/projeto.form.endereco-form.logradouro')),
                        TextInput::make('numero')
                            ->label(__('comercial::filament/resources/projeto.form.endereco-form.numero')),
                        TextInput::make('complemento')
                            ->label(__('comercial::filament/resources/projeto.form.endereco-form.complemento')),
                        TextInput::make('bairro')
                            ->label(__('comercial::filament/resources/projeto.form.endereco-form.bairro')),
                        TextInput::make('municipio')
                            ->label(__('comercial::filament/resources/projeto.form.endereco-form.municipio')),
                        TextInput::make('uf')
                            ->label(__('comercial::filament/resources/projeto.form.endereco-form.uf'))
                            ->maxLength(2),
                    ])
                    ->createOptionUsing(function (array $data, Get $get): int {
                        $endereco = Endereco::create($data);

                        $pessoaFisicaId = $get('pessoa_fisica_id');
                        $pessoaJuridicaId = $get('pessoa_juridica_id');

                        // O endereço só serve pra algo aqui se ficar vinculado ao
                        // contratante selecionado — senão desaparece da lista de
                        // opções assim que o formulário recalcular. "Obra" é o
                        // tipo mais coerente com o contexto (endereço de Projeto).
                        if (filled($pessoaFisicaId)) {
                            PessoaFisica::find($pessoaFisicaId)?->enderecos()->attach($endereco->id, [
                                'tipo'      => TipoEndereco::Obra->value,
                                'principal' => false,
                            ]);
                        } elseif (filled($pessoaJuridicaId)) {
                            PessoaJuridica::find($pessoaJuridicaId)?->enderecos()->attach($endereco->id, [
                                'tipo'      => TipoEndereco::Obra->value,
                                'principal' => false,
                            ]);
                        }

                        return $endereco->id;
                    }),

                Placeholder::make('numero_projeto')
                    ->label(__('comercial::filament/resources/projeto.form.numero-projeto'))
                    ->content(fn (?Projeto $record) => $record?->numero_projeto
                        ?? __('comercial::filament/resources/projeto.form.numero-projeto-pendente')),

                Placeholder::make('revisao_display')
                    ->label(__('comercial::filament/resources/projeto.form.revisao'))
                    ->content(fn (?Projeto $record) => 'R'.str_pad((string) ($record->revisao ?? 0), 2, '0', STR_PAD_LEFT)),

                Placeholder::make('data_cadastro')
                    ->label(__('comercial::filament/resources/projeto.form.data-cadastro'))
                    ->content(fn (?Projeto $record) => $record?->data_cadastro?->format('d/m/Y H:i')
                        ?? __('comercial::filament/resources/projeto.form.data-cadastro-pendente')),
            ]);
    }

    /**
     * @return array<int, string>
     */
    protected static function enderecoOptionsFor(?string $pessoaFisicaId, ?string $pessoaJuridicaId): array
    {
        $enderecos = match (true) {
            filled($pessoaFisicaId)    => PessoaFisica::find($pessoaFisicaId)?->enderecos,
            filled($pessoaJuridicaId)  => PessoaJuridica::find($pessoaJuridicaId)?->enderecos,
            default                    => null,
        };

        if (blank($enderecos)) {
            return [];
        }

        return $enderecos
            ->mapWithKeys(fn (Endereco $endereco) => [$endereco->id => static::formatEnderecoLabel($endereco)])
            ->toArray();
    }

    protected static function formatEnderecoLabel(Endereco $endereco): string
    {
        $linha1 = trim($endereco->logradouro.($endereco->numero ? ", {$endereco->numero}" : ''));
        $linha2 = collect([$endereco->bairro, trim("{$endereco->municipio}/{$endereco->uf}", '/')])
            ->filter()
            ->implode(' - ');

        return collect([$linha1, $linha2])->filter()->implode(' - ') ?: "#{$endereco->id}";
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['pessoaFisica', 'pessoaJuridica', 'tipoProjeto', 'situacoes']))
            ->columns([
                TextColumn::make('numero_projeto')
                    ->label(__('comercial::filament/resources/projeto.table.columns.numero-projeto'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('descricao')
                    ->label(__('comercial::filament/resources/projeto.table.columns.descricao'))
                    ->searchable(),
                TextColumn::make('tipoProjeto.descricao')
                    ->label(__('comercial::filament/resources/projeto.table.columns.tipo-projeto')),
                TextColumn::make('contratante')
                    ->label(__('comercial::filament/resources/projeto.table.columns.contratante'))
                    ->getStateUsing(fn (Projeto $record) => $record->pessoaFisica?->nome ?? $record->pessoaJuridica?->nome_fantasia),
                TextColumn::make('situacoes.descricao')
                    ->label(__('comercial::filament/resources/projeto.table.columns.situacoes'))
                    ->badge(),
                TextColumn::make('data_cadastro')
                    ->label(__('comercial::filament/resources/projeto.table.columns.data-cadastro'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('comercial::filament/resources/projeto.table.actions.edit.notification.title'))
                            ->body(__('comercial::filament/resources/projeto.table.actions.edit.notification.body')),
                    ),
                DeleteAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('comercial::filament/resources/projeto.table.actions.delete.notification.title'))
                            ->body(__('comercial::filament/resources/projeto.table.actions.delete.notification.body')),
                    ),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListProjetos::route('/'),
            'create' => CreateProjeto::route('/create'),
            'edit'   => EditProjeto::route('/{record}/edit'),
        ];
    }
}
