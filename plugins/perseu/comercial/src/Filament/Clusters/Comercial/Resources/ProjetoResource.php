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
        // Resource::form()'s top-level Schema tem grid de 2 colunas em telas
        // "lg" por padrão (Filament\Schemas\Concerns\HasColumns::columns(),
        // default 2 — confirmado via HTML renderizado, mesmo default que
        // PessoaFisicaResource/PessoaJuridicaResource usam). Cada componente
        // de nível mais alto que NÃO chama ->columnSpanFull() ocupa só 1 das
        // 2 colunas (--col-span-default: span 1 / span 1) e o grid faz
        // auto-placement lado a lado — por isso os 3 blocos (2x flexRow() +
        // endereco_id) apareciam emparelhados horizontalmente em vez de
        // empilhados. static::flexRow() e static::compact()/compactByLabel()
        // controlam a largura dos campos DENTRO de cada linha, mas não têm
        // nenhuma relação com o columnSpan do BLOCO inteiro no grid do
        // formulário — os 3 blocos de nível mais alto precisam de
        // ->columnSpanFull() explícito para ocupar a linha inteira e forçar
        // quebra antes do próximo bloco, independente da largura interna que
        // cada flexRow já calibra.
        // Nome da Obra (Linha 1) e o Select de Cliente (Linha 2) precisam
        // da MESMA largura final para ficarem alinhados visualmente, uma
        // coluna embaixo da outra — mesma constante nos dois lugares em vez
        // de repetir o literal, pra não desalinhar se um dos dois mudar
        // sem o outro no futuro.
        $larguraDescricaoECliente = 'max-width: 48ch;';

        return $schema
            ->components([
                // Linha 1 (consolidada): numero_projeto/revisao/data_cadastro
                // (compactByLabel — são Placeholder, sem .fi-input-wrp) +
                // descricao (cresce, mas com teto — "nome da obra" não
                // precisa ocupar metade da tela) + tipo_projeto_id (compacto)
                // + situacoes (cresce, sem teto). Com dois campos crescendo
                // (descricao e situacoes) e descricao tendo max-width, o
                // algoritmo de flexbox devolve o espaço que descricao não usa
                // para situacoes assim que ela bate no teto — dá "mais
                // respiro" pro multi-select de badges sem precisar de um peso
                // de flex-grow explícito (a API grow() do Filament é só
                // booleana, não aceita peso).
                static::flexRow([
                    static::compactByLabel(
                        Placeholder::make('numero_projeto')
                            ->label(__('comercial::filament/resources/projeto.form.numero-projeto'))
                            ->content(fn (?Projeto $record) => $record?->numero_projeto
                                ?? __('comercial::filament/resources/projeto.form.numero-projeto-pendente')),
                        extraSlack: 2,
                    ),
                    static::compactByLabel(
                        Placeholder::make('revisao_display')
                            ->label(__('comercial::filament/resources/projeto.form.revisao'))
                            ->content(fn (?Projeto $record) => str_pad((string) ($record->revisao ?? 0), 2, '0', STR_PAD_LEFT)),
                    ),
                    static::compactByLabel(
                        Placeholder::make('data_cadastro')
                            ->label(__('comercial::filament/resources/projeto.form.data-cadastro'))
                            ->content(fn (?Projeto $record) => $record?->data_cadastro?->format('d/m/Y H:i')
                                ?? __('comercial::filament/resources/projeto.form.data-cadastro-pendente')),
                        extraSlack: 2,
                    ),
                    static::grow(
                        TextInput::make('descricao')
                            ->label(__('comercial::filament/resources/projeto.form.descricao'))
                            ->required()
                            ->maxLength(255),
                    )->extraAttributes(['style' => $larguraDescricaoECliente], merge: true),
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
                    static::grow(
                        Select::make('situacoes')
                            ->label(__('comercial::filament/resources/projeto.form.situacoes'))
                            ->relationship(name: 'situacoes', titleAttribute: 'descricao')
                            ->multiple()
                            ->preload()
                            ->searchable(),
                    ),
                ])->columnSpanFull(),

                // Linha 2: tudo relacionado ao contratante/contato numa
                // linha só — Radio do Contratante (horizontal, via
                // ->inline()), Select de Cliente (PF ou PJ, só um visível
                // por vez, cresce no espaço restante), Contato, E-mail e
                // Telefone (compact(), largura calibrada pelo valor
                // esperado — ver comentários abaixo).
                //
                // Radio não tem .fi-input-wrp nem largura ligada ao label
                // ("Contratante" é mais curto que as duas opções lado a lado),
                // por isso a largura é fixada manualmente em vez de usar
                // compact()/compactByLabel() — calibrada para caber "Física"/
                // "Jurídica" lado a lado sem quebrar linha dentro do próprio
                // Radio. 22ch (calibrado numa tarefa anterior, proporcional
                // aos labels curtos "Física"/"Jurídica") deixava as duas
                // opções visualmente espremidas; 30ch dá mais respiro sem
                // desalinhar o resto da linha.
                static::flexRow([
                    Radio::make('tipo_contratante')
                        ->label(__('comercial::filament/resources/projeto.form.tipo-contratante'))
                        ->options([
                            'pf' => __('comercial::filament/resources/projeto.form.tipo-contratante-options.pessoa-fisica'),
                            'pj' => __('comercial::filament/resources/projeto.form.tipo-contratante-options.pessoa-juridica'),
                        ])
                        ->inline()
                        ->grow(false)
                        ->extraFieldWrapperAttributes(['style' => 'max-width: 30ch;'], merge: true)
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

                    static::grow(
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
                    )->extraAttributes(['style' => $larguraDescricaoECliente], merge: true),

                    static::grow(
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
                    )->extraAttributes(['style' => $larguraDescricaoECliente], merge: true),

                    // Contato/E-mail/Telefone (só PJ), na mesma linha do
                    // Radio/Cliente acima. Contato usava static::grow() (sem
                    // teto) e ocupava espaço demais, sobrando pouco pros dois
                    // Placeholder ao lado — trocado para compact() com
                    // largura fixa (~metade do que ocupava antes). compact()
                    // funciona em Placeholder também (só depende de
                    // ->extraAttributes(), que ambas hierarquias de
                    // componente têm — diferente de compactByLabel(), que
                    // dependia do fallback pra extraFieldWrapperAttributes).
                    // Email/telefone também passaram de compactByLabel() (que
                    // só olha o tamanho do LABEL, "E-mail"/"Telefone", bem
                    // mais curto que o VALOR real exibido) para compact() com
                    // chars calibrados pelo conteúdo — era essa a causa da
                    // quebra de linha reportada.
                    static::compact(
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
                        // nome de pessoa — sem formato fixo, valor aproximado
                        // pra caber a maioria dos nomes sem exagerar na largura
                        // (testado com "Contato Layout Teste 2", 22 chars).
                        chars: 24,
                    ),
                    static::compact(
                        Placeholder::make('contato_email')
                            ->label(__('comercial::filament/resources/projeto.form.contato-email'))
                            ->content(fn (Get $get) => static::contatoSelecionado($get)?->email)
                            ->visible(fn (Get $get) => $get('tipo_contratante') === 'pj' && filled($get('contato_pessoa_fisica_id'))),
                        // testado com "financeiro.contato@empresateste.com.br"
                        // (39 chars, e-mail corporativo real usado em teste) —
                        // 38 cobre esse caso e a maioria dos e-mails
                        // corporativos comuns; e-mails bem mais longos que
                        // isso ainda podem quebrar linha dentro da caixa, é
                        // um teto razoável, não absoluto.
                        chars: 38,
                    ),
                    static::compact(
                        Placeholder::make('contato_telefone')
                            ->label(__('comercial::filament/resources/projeto.form.contato-telefone'))
                            ->content(fn (Get $get) => static::contatoSelecionado($get)?->telefone)
                            ->visible(fn (Get $get) => $get('tipo_contratante') === 'pj' && filled($get('contato_pessoa_fisica_id'))),
                        // "(99) 99999-9999" = 16 chars, mesmo formato usado
                        // no campo telefone de PessoaFisica/PessoaJuridica.
                        chars: 16,
                    ),
                ])->columnSpanFull(),

                Select::make('endereco_id')
                    ->label(__('comercial::filament/resources/projeto.form.endereco'))
                    ->options(function (Get $get): array {
                        return static::enderecoOptionsFor($get('pessoa_fisica_id'), $get('pessoa_juridica_id'));
                    })
                    ->columnSpanFull()
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
            ]);
    }

    protected static function contatoSelecionado(Get $get): ?PessoaFisica
    {
        $pessoaFisicaId = $get('contato_pessoa_fisica_id');

        return filled($pessoaFisicaId) ? PessoaFisica::find($pessoaFisicaId) : null;
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
