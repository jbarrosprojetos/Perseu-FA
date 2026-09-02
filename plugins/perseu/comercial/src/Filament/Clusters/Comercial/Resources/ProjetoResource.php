<?php

namespace Perseu\Comercial\Filament\Clusters\Comercial\Resources;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Perseu\Comercial\Filament\Clusters\Comercial\Resources\ProjetoResource\Pages\CreateProjeto;
use Perseu\Comercial\Filament\Clusters\Comercial\Resources\ProjetoResource\Pages\EditProjeto;
use Perseu\Comercial\Filament\Clusters\Comercial\Resources\ProjetoResource\Pages\ListProjetos;
use Perseu\Comercial\Filament\Clusters\Projetos;
use Perseu\Comercial\Models\Projeto;
use Perseu\Comercial\Models\ReferenciaPreco;
use Perseu\Pessoas\Enums\TipoEndereco;
use Perseu\Pessoas\Models\Contato;
use Perseu\Pessoas\Models\Endereco;
use Perseu\Pessoas\Models\PessoaFisica;
use Perseu\Pessoas\Models\PessoaJuridica;
use Perseu\Pessoas\Support\ViaCepLookup;

class ProjetoResource extends Resource
{
    protected static ?string $model = Projeto::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $cluster = Projetos::class;

    protected static ?string $slug = 'projetos';

    protected static ?int $navigationSort = 1;

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
        // PessoaFisicaResource/PessoaJuridicaResource usam). A Section
        // "Cabeçalho" abaixo é o único componente de nível mais alto do
        // Schema hoje, por isso leva ->columnSpanFull() — os campos DENTRO
        // dela (as duas Grid::make(12) e o Select de Endereço) continuam
        // precisando do próprio ->columnSpanFull(), porque a Section tem
        // sua própria grid interna de 1 coluna por padrão, independente da
        // grid externa do Schema.
        //
        // Layout por Grid::make(12) + columnSpan() numérico por campo, em
        // vez do antigo padrão Flex (static::flexRow() + HasCompactFieldWidth
        // ::compact()/compactByLabel(), calibrado por caractere/ch) — ver
        // "Grid vs. static::flexRow()" no CLAUDE.md. Como aqui NENHUM campo
        // precisa "absorver" sobra de espaço (todo campo tem uma largura
        // fixa em número de colunas, não uma largura calculada por
        // conteúdo), Grid é o mecanismo correto — a ressalva do CLAUDE.md
        // contra Grid (linha que mistura compactos com um campo de largura
        // "normal" que deve crescer) não se aplica. O trait
        // HasCompactFieldWidth deixou de ser usado neste Resource.
        //
        // Nome da Obra (Linha 1, columnSpan 4) e o Select de Cliente
        // (Linha 2, columnSpan 4) ficam alinhados verticalmente porque
        // ambos são o 2º item de cada Grid::make(12) e têm a MESMA soma de
        // columnSpan antes deles (numero_projeto+revisao+data_cadastro = 3
        // na Linha 1; tipo_contratante = 3 na Linha 2) — alinhamento vem da
        // posição na grid, não de um max-width em comum calibrado à mão
        // como antes.
        // ("Revisão" removida em 2026-09-01 e trazida de volta em
        // 2026-09-02, ver CLAUDE.md — o replanejamento da fase de
        // Proposta decidiu manter Revisão dentro do próprio cadastro de
        // Projeto por enquanto, em vez de um cadastro de Proposta
        // separado.)
        //
        // Grid::make(N) tem seu próprio gap zerado pelo Bonsai
        // (`.fi-sc.fi-sc-has-gap { gap: 0 !important; }`, que cobre o
        // "fi-sc" que TODO Schema — inclusive o schema filho de um Grid —
        // recebe; ver alerta do Bonsai no CLAUDE.md), por isso o gap é
        // restaurado aqui via extraAttributes com !important, mesma técnica
        // já usada em HasCompactFieldWidth::flexRow().
        $gridGap = ['style' => 'gap: 1rem !important;'];

        // Todos os campos atuais (Linhas 1-3 abaixo) vivem dentro de uma
        // Section "Cabeçalho" — separa visualmente os dados administrativos
        // do Projeto de uma futura Section "Itens do Projeto" (ainda não
        // implementada), que deve ser adicionada como um item IRMÃO desta
        // Section aqui em `$schema->components([...])`, sempre ANTES dos
        // botões Salvar/Cancelar (que não fazem parte deste array — são
        // renderizados pela própria página Create/Edit, fora do form()).
        // Isso evita que os dados do Projeto e os Itens pareçam um bloco
        // único quando a segunda Section existir.
        //
        // Sem espaçador manual entre o fim da Section e os botões — causa
        // raiz investigada (2026-09-02): o `<form class="fi-sc-form">` que
        // envolve TODO o conteúdo do form + o footer de Actions já tem
        // `gap-6` (1.5rem) nativo do Filament entre os dois (confirmado
        // lendo vendor/filament/schemas/resources/css/components/form.css
        // e inspecionando o HTML renderizado — a Action footer é filha
        // direta desse `<form>`, não do Schema interno). Um `Html::make()`
        // com `<div style="height:...">` usado antes (3rem, depois 1rem)
        // ficava por dentro da Section/Schema, ANTES desse gap nativo —
        // ou seja, SOMAVA ao gap-6 em vez de defini-lo, e por isso "ainda
        // ficava distante" mesmo depois de reduzir o valor. Reduzir de novo
        // não resolveria a causa; remover o spacer resolve, porque o
        // `gap-6` sozinho já é o espaçamento padrão do Filament entre
        // conteúdo e footer de Actions em qualquer página do sistema.
        return $schema
            ->components([
                Section::make(__('comercial::filament/resources/projeto.form.sections.cabecalho.title'))
                    ->description(__('comercial::filament/resources/projeto.form.sections.cabecalho.description'))
                    ->columnSpanFull()
                    ->schema([
                        // Linha 1: numero_projeto/revisao/data_cadastro (1
                        // coluna cada) + descricao "Nome da Obra" (4) +
                        // tipo_projeto_id (2) + situacoes (3) = 12 colunas.
                        Grid::make(12)
                            ->columnSpanFull()
                            ->extraAttributes($gridGap)
                            ->schema([
                                // fi-entry-bold: classe própria (ver
                                // resources/css/filament/admin-entry-content.css) pra
                                // aplicar negrito só ao VALOR desses 3 campos —
                                // contato_email/contato_telefone (abaixo, Linha 2)
                                // recebem a correção de tipografia/alinhamento do
                                // mesmo CSS, mas sem essa classe, então sem negrito.
                                Placeholder::make('numero_projeto')
                                    ->label(__('comercial::filament/resources/projeto.form.numero-projeto'))
                                    ->content(fn (?Projeto $record) => $record?->numero_projeto
                                        ?? __('comercial::filament/resources/projeto.form.numero-projeto-pendente'))
                                    ->extraAttributes(['class' => 'fi-entry-bold'])
                                    ->columnSpan(1),
                                Placeholder::make('revisao_display')
                                    ->label(__('comercial::filament/resources/projeto.form.revisao'))
                                    ->content(fn (?Projeto $record) => str_pad((string) ($record->revisao ?? 0), 2, '0', STR_PAD_LEFT))
                                    ->extraAttributes(['class' => 'fi-entry-bold'])
                                    ->columnSpan(1),
                                Placeholder::make('data_cadastro')
                                    ->label(__('comercial::filament/resources/projeto.form.data-cadastro'))
                                    ->content(fn (?Projeto $record) => $record?->data_cadastro?->format('d/m/Y')
                                        ?? __('comercial::filament/resources/projeto.form.data-cadastro-pendente'))
                                    ->extraAttributes(['class' => 'fi-entry-bold'])
                                    ->columnSpan(1),
                                TextInput::make('descricao')
                                    ->label(__('comercial::filament/resources/projeto.form.descricao'))
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(4),
                                Select::make('tipo_projeto_id')
                                    ->label(__('comercial::filament/resources/projeto.form.tipo-projeto'))
                                    ->relationship('tipoProjeto', 'descricao')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->columnSpan(2),
                                Select::make('situacoes')
                                    ->label(__('comercial::filament/resources/projeto.form.situacoes'))
                                    ->relationship(name: 'situacoes', titleAttribute: 'descricao')
                                    ->multiple()
                                    ->preload()
                                    ->searchable()
                                    ->columnSpan(3),
                            ]),

                        // Linha 2: tipo_contratante (Radio Física/Jurídica, 3) +
                        // Select de Cliente — pessoa_fisica_id OU pessoa_juridica_id,
                        // ambos no mesmo columnSpan(4), logo após o Radio; só um
                        // fica ->visible() por vez conforme o Radio, então o outro
                        // simplesmente não é renderizado e não deixa buraco na
                        // grid — + Contato (2) + Email do Contato (2) + Telefone do
                        // Contato (1) = 12 colunas.
                        Grid::make(12)
                            ->columnSpanFull()
                            ->extraAttributes($gridGap)
                            ->schema([
                                Radio::make('tipo_contratante')
                                    ->label(__('comercial::filament/resources/projeto.form.tipo-contratante'))
                                    ->options([
                                        'pf' => __('comercial::filament/resources/projeto.form.tipo-contratante-options.pessoa-fisica'),
                                        'pj' => __('comercial::filament/resources/projeto.form.tipo-contratante-options.pessoa-juridica'),
                                    ])
                                    ->inline()
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
                                    ->required()
                                    ->columnSpan(3),

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
                                    ->required(fn (Get $get) => $get('tipo_contratante') === 'pf')
                                    ->columnSpan(4),

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
                                    ->required(fn (Get $get) => $get('tipo_contratante') === 'pj')
                                    ->columnSpan(4),

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
                                    ->visible(fn (Get $get) => $get('tipo_contratante') === 'pj')
                                    ->columnSpan(2),

                                Placeholder::make('contato_email')
                                    ->label(__('comercial::filament/resources/projeto.form.contato-email'))
                                    ->content(fn (Get $get) => static::contatoSelecionado($get)?->email)
                                    ->visible(fn (Get $get) => $get('tipo_contratante') === 'pj' && filled($get('contato_pessoa_fisica_id')))
                                    ->columnSpan(2),

                                Placeholder::make('contato_telefone')
                                    ->label(__('comercial::filament/resources/projeto.form.contato-telefone'))
                                    ->content(fn (Get $get) => static::contatoSelecionado($get)?->telefone)
                                    ->visible(fn (Get $get) => $get('tipo_contratante') === 'pj' && filled($get('contato_pessoa_fisica_id')))
                                    ->columnSpan(1),
                            ]),

                        // Linha 3: Endereço da Obra (8) + Referência de Preços
                        // (4) lado a lado = 12 colunas. Referência de Preços é
                        // opcional (usada futuramente pra calcular o valor de
                        // Venda do Projeto, ver CLAUDE.md) — sem ->required(),
                        // com aviso em vermelho via ->hint()/->hintColor()
                        // quando nada está selecionado.
                        Grid::make(12)
                            ->columnSpanFull()
                            ->extraAttributes($gridGap)
                            ->schema([
                                Select::make('endereco_id')
                                    ->label(__('comercial::filament/resources/projeto.form.endereco'))
                                    ->options(function (Get $get): array {
                                        return static::enderecoObraOptionsFor($get('pessoa_fisica_id'), $get('pessoa_juridica_id'));
                                    })
                                    ->helperText(function (Get $get): ?string {
                                        // Só mostra o aviso depois de um Cliente selecionado E
                                        // sem nenhum endereço-obra — antes disso (nenhum
                                        // Cliente ainda) o campo já fica vazio por padrão, sem
                                        // precisar de explicação.
                                        if (blank($get('pessoa_fisica_id')) && blank($get('pessoa_juridica_id'))) {
                                            return null;
                                        }

                                        return filled(static::enderecoObraOptionsFor($get('pessoa_fisica_id'), $get('pessoa_juridica_id')))
                                            ? null
                                            : __('comercial::filament/resources/projeto.form.endereco-sem-tag-obra');
                                    })
                                    ->columnSpan(8)
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
                                        // opções assim que o formulário recalcular. "Obra" (a tag
                                        // do enum TipoEndereco, sem relação com o nome deste
                                        // cadastro — ver CLAUDE.md de perseu/pessoas, "Tipo de
                                        // Endereço como tag") é a mais coerente com o contexto
                                        // (endereço da obra/canteiro em execução). Tag única e
                                        // deliberada aqui, NÃO todas marcadas por padrão — essa
                                        // regra vale só para o CheckboxList do formulário manual
                                        // de Endereços; este é preenchimento automático sem
                                        // interação do usuário.
                                        if (filled($pessoaFisicaId)) {
                                            PessoaFisica::find($pessoaFisicaId)?->enderecos()->attach($endereco->id, [
                                                'principal' => false,
                                            ]);
                                        } elseif (filled($pessoaJuridicaId)) {
                                            PessoaJuridica::find($pessoaJuridicaId)?->enderecos()->attach($endereco->id, [
                                                'principal' => false,
                                            ]);
                                        }

                                        $endereco->tipos()->create(['tipo' => TipoEndereco::Obra->value]);

                                        return $endereco->id;
                                    }),

                                Select::make('referencia_preco_id')
                                    ->label(__('comercial::filament/resources/projeto.form.referencia-preco'))
                                    ->relationship(name: 'referenciaPreco', titleAttribute: 'descricao')
                                    ->getOptionLabelFromRecordUsing(fn (ReferenciaPreco $record) => trim(
                                        "{$record->descricao} — {$record->created_at?->format('d/m/Y H:i')}"
                                    ))
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->hint(fn (Get $get) => blank($get('referencia_preco_id'))
                                        ? __('comercial::filament/resources/projeto.form.referencia-preco-aviso')
                                        : null)
                                    ->hintColor('danger')
                                    ->columnSpan(4),
                            ]),
                    ]),

                // Espaço reservado para uma futura Section::make('Itens do
                // Projeto') aqui — item IRMÃO da Section "Cabeçalho" acima,
                // dentro deste mesmo array. Continuará sendo o penúltimo
                // elemento da página: os botões Salvar/Cancelar não fazem
                // parte deste array (são renderizados pela página Create/
                // Edit, fora do form()), então permanecem como último
                // elemento sem exigir nenhum ajuste aqui quando a Section de
                // Itens for adicionada.
            ]);
    }

    protected static function contatoSelecionado(Get $get): ?PessoaFisica
    {
        $pessoaFisicaId = $get('contato_pessoa_fisica_id');

        return filled($pessoaFisicaId) ? PessoaFisica::find($pessoaFisicaId) : null;
    }

    /**
     * Só endereços com a tag "Obra" ativa (ver CLAUDE.md de
     * perseu/pessoas, "Tipo de Endereço como tag") — o que interessa
     * pro Projeto é especificamente onde a obra será executada/
     * entregue/instalada, não o endereço comercial/residencial/de
     * cobrança do cliente. Um cliente pode ter mais de um endereço
     * com a tag Obra (ex: duas obras em andamento ao mesmo tempo) —
     * todos aparecem como opção, cabe ao usuário escolher qual se
     * aplica a este Projeto.
     *
     * @return array<int, string>
     */
    protected static function enderecoObraOptionsFor(?string $pessoaFisicaId, ?string $pessoaJuridicaId): array
    {
        $comTagObra = fn (Builder $query) => $query->where('tipo', TipoEndereco::Obra->value);

        $enderecos = match (true) {
            filled($pessoaFisicaId)    => PessoaFisica::find($pessoaFisicaId)?->enderecos()->whereHas('tipos', $comTagObra)->get(),
            filled($pessoaJuridicaId)  => PessoaJuridica::find($pessoaJuridicaId)?->enderecos()->whereHas('tipos', $comTagObra)->get(),
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
            ->filters([
                TrashedFilter::make()
                    ->label(__('comercial::filament/resources/projeto.table.filters.trashed')),
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
                RestoreAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('comercial::filament/resources/projeto.table.actions.restore.notification.title'))
                            ->body(__('comercial::filament/resources/projeto.table.actions.restore.notification.body')),
                    ),
                ForceDeleteAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('comercial::filament/resources/projeto.table.actions.force-delete.notification.title'))
                            ->body(__('comercial::filament/resources/projeto.table.actions.force-delete.notification.body')),
                    ),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
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
