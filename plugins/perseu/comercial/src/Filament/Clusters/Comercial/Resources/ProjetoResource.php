<?php

namespace Perseu\Comercial\Filament\Clusters\Comercial\Resources;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Icon;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\IconSize;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Perseu\Comercial\Enums\OrigemItemProjeto;
use Perseu\Comercial\Filament\Clusters\Comercial\Resources\ProjetoResource\Pages\CreateProjeto;
use Perseu\Comercial\Filament\Clusters\Comercial\Resources\ProjetoResource\Pages\EditProjeto;
use Perseu\Comercial\Filament\Clusters\Comercial\Resources\ProjetoResource\Pages\ListProjetos;
use Perseu\Comercial\Filament\Clusters\Projetos;
use Perseu\Comercial\Models\ItemProjeto;
use Perseu\Comercial\Models\Projeto;
use Perseu\Comercial\Models\ReferenciaPreco;
use Perseu\Comercial\Services\PromobChecagemTotal;
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
                                        // `DB::transaction()` — achado real de concorrência (ver
                                        // INVESTIGACAO-TRANSACOES-CONCORRENCIA.md, risco "Endereço
                                        // criado sem tag"): sem isso, uma falha entre o attach e o
                                        // create da tag deixava um Endereço vinculado à Pessoa mas
                                        // SEM a tag "Obra" — como `enderecoObraOptionsFor()` filtra
                                        // por essa tag, o Endereço simplesmente "sumia" das opções,
                                        // sem erro nenhum pro usuário perceber a causa.
                                        return DB::transaction(function () use ($data, $get): int {
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
                                        });
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

                // Botões Salvar/Cancelar da própria página (Create/Edit),
                // reposicionados aqui — item IRMÃO das duas Sections, entre
                // "Cabeçalho" e "Itens". Por padrão o Filament os renderiza
                // FORA deste array (`getFormContentComponent()` da página
                // Create/Edit os anexa como `->footer()` do wrapper
                // `<form class="fi-sc-form">`, depois de TUDO — inclusive de
                // qualquer Section futura), o que deixou de fazer sentido
                // quando a Section "Itens" ganhou sua própria dinâmica de
                // salvar por item (ver `plugins/perseu/comercial/CLAUDE.md`)
                // — o Salvar/Cancelar aqui deve servir só aos campos
                // administrativos da Section "Cabeçalho" acima.
                //
                // `$schema->getLivewire()` retorna a própria página (Create/
                // Edit), já vinculada ao Schema neste ponto
                // (`Schema::make($this)`, ver `BelongsToLivewire`).
                // `getFormActionsContentComponent()` é público em
                // `CreateRecord`/`EditRecord` e monta o MESMO
                // `Actions::make([...])` com `getSubmitFormAction()`/
                // `getCancelFormAction()` que a página já usaria por padrão
                // — chamado aqui uma única vez, sem duplicar lógica de
                // submit. A contrapartida obrigatória é `CreateProjeto`/
                // `EditProjeto` sobrescreverem `getFormContentComponent()`
                // para NÃO chamar esse mesmo método de novo como `->footer()`
                // do form — senão os botões apareceriam duas vezes (aqui E
                // no rodapé), com o mesmo `key('form-actions')` colidindo.
                $schema->getLivewire()->getFormActionsContentComponent()
                    ->columnSpanFull(),

                // Section "Itens" — item IRMÃO da Section "Cabeçalho" acima
                // (ver comentário dela sobre a separação visual). Por ora só
                // a interface do seletor de origem + botão "Inserir": a
                // lógica de cada origem (o que o botão realmente faz) e a
                // listagem dos itens já inseridos dependem de uma tabela de
                // Itens que ainda não existe — ver
                // CONCEITO-OBRA-PROPOSTA-PROJETO.md quando essa etapa for
                // desenhada. `origem_item_selecionada` é um campo de
                // controle do formulário, não do Model (`dehydrated(false)`
                // + fora do `$fillable` de Projeto), por isso não precisa de
                // migration.
                Section::make(__('comercial::filament/resources/projeto.form.sections.itens.title'))
                    ->description(__('comercial::filament/resources/projeto.form.sections.itens.description'))
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(12)
                            ->columnSpanFull()
                            ->extraAttributes($gridGap)
                            ->schema([
                                Select::make('origem_item_selecionada')
                                    ->label(__('comercial::filament/resources/projeto.form.itens.origem'))
                                    ->options(fn () => static::origensItemOptions())
                                    ->live()
                                    ->dehydrated(false)
                                    ->columnSpan(4),

                                // Campo de controle (não persiste, não tem
                                // input visível — `columnSpan(['default' =>
                                // 'hidden'])` já vem por padrão do próprio
                                // `Hidden`) que guarda qual origem teve seu
                                // cabeçalho de colunas exibido pelo botão
                                // "Inserir" — hoje só "Item Avulso" liga essa
                                // exibição (ver Action abaixo); as outras 2
                                // origens (Promob, SketchUp) continuam de
                                // fora, com a notificação placeholder.
                                Hidden::make('origem_item_inserida')
                                    ->dehydrated(false),

                                // Campo de controle que guarda o ID do
                                // ItemProjeto atualmente em edição (ver
                                // `abrirEdicaoItemAvulso()`) — `null`
                                // quando a linha de input está inserindo
                                // um item NOVO (não editando um já
                                // existente). Só um item por vez: abrir a
                                // edição de outro item simplesmente
                                // sobrescreve este valor (e os campos
                                // `novo_item_*`), fechando a edição
                                // anterior sem persistir nada dela — a
                                // opção mais simples entre as sugeridas na
                                // tarefa, ver CLAUDE.md.
                                Hidden::make('item_em_edicao_id')
                                    ->dehydrated(false),

                                Actions::make([
                                    Action::make('inserirItem')
                                        ->label(__('comercial::filament/resources/projeto.form.itens.inserir'))
                                        // "Promob" tem seu PRÓPRIO botão
                                        // "Inserir" logo abaixo
                                        // (`inserirItemPromob`, com modal de
                                        // upload) — os dois nunca ficam
                                        // visíveis ao mesmo tempo, então na
                                        // tela sempre aparece só UM botão
                                        // "Inserir" na posição, mudando de
                                        // comportamento conforme a origem
                                        // selecionada.
                                        ->visible(fn (Get $get) => $get('origem_item_selecionada') !== 'promob')
                                        ->action(function (Get $get, Set $set): void {
                                            $origem = $get('origem_item_selecionada');

                                            if (blank($origem)) {
                                                $set('origem_item_inserida', null);

                                                Notification::make()
                                                    ->warning()
                                                    ->title(__('comercial::filament/resources/projeto.form.itens.notification.sem-selecao'))
                                                    ->send();

                                                return;
                                            }

                                            // "Item Avulso" é a única origem com
                                            // comportamento real por enquanto —
                                            // mostra o cabeçalho de colunas e a
                                            // linha de input (dois Grid::make(24)
                                            // logo abaixo). "Item de Linha" e
                                            // "SketchUp" continuam com a
                                            // notificação placeholder até
                                            // ganharem sua própria lógica numa
                                            // tarefa futura ("Promob" tem seu
                                            // próprio botão, ver acima).
                                            if ($origem === 'item_avulso') {
                                                $set('origem_item_inserida', $origem);

                                                // Cancela qualquer edição em
                                                // andamento (ver
                                                // `item_em_edicao_id` acima) —
                                                // clicar "Inserir" de novo
                                                // sempre começa uma linha NOVA
                                                // em branco, mesmo que outro
                                                // item estivesse sendo editado.
                                                $set('item_em_edicao_id', null);

                                                // Reseta a linha de input a cada
                                                // clique em "Inserir" (linha nova
                                                // sempre começa em branco) e busca
                                                // o Imp.% da Referência de Preços
                                                // ATUALMENTE selecionada no
                                                // Cabeçalho (não do registro salvo
                                                // no banco — se o usuário troca a
                                                // Referência antes de clicar
                                                // "Inserir", vale a escolha atual).
                                                // Não dá pra usar `->default()` no
                                                // campo `novo_item_imposto`: ele
                                                // vive dentro de um Grid com
                                                // `->visible()` condicional, e o
                                                // `fill()` inicial da página não
                                                // hidrata campos que começam
                                                // escondidos — confirmado via
                                                // `Livewire::test()` (o campo
                                                // ficava sempre `null` mesmo com
                                                // Referência vinculada).
                                                $referenciaPrecoId = $get('referencia_preco_id');

                                                $set('novo_item_imposto', filled($referenciaPrecoId)
                                                    ? ReferenciaPreco::find($referenciaPrecoId)?->imposto
                                                    : null);
                                                $set('novo_item_descricao', null);
                                                $set('novo_item_quantidade', null);
                                                $set('novo_item_porcentagem', null);
                                                $set('novo_item_custo_unitario', null);
                                                $set('novo_item_valor_unitario', null);
                                                $set('novo_item_valor_total', null);

                                                return;
                                            }

                                            $set('origem_item_inserida', null);

                                            Notification::make()
                                                ->info()
                                                ->title(__('comercial::filament/resources/projeto.form.itens.notification.pendente-title'))
                                                ->body(__('comercial::filament/resources/projeto.form.itens.notification.pendente-body', [
                                                    'origem' => static::origensItemOptions()[$origem] ?? $origem,
                                                ]))
                                                ->send();
                                        }),

                                    // "Inserir" de "Promob" — só visível quando
                                    // essa origem está selecionada (ver
                                    // `inserirItem` acima). Em vez da
                                    // notificação placeholder, abre um modal
                                    // de upload dos XMLs exportados pelo
                                    // Promob e roda a rotina "Checar Total"
                                    // (ver `processarUploadPromob()` e
                                    // `PromobChecagemTotal`/`PromobXmlParser`
                                    // em `Services/`) — MESMO mecanismo
                                    // técnico usado pelo "+" de "Adicionar
                                    // Endereço" (`Select::createOptionForm()`
                                    // logo acima, na Grid do Cabeçalho): por
                                    // baixo dos panos os dois são uma
                                    // `Filament\Actions\Action` com `->form()`
                                    // próprio, que o Filament abre
                                    // automaticamente como modal
                                    // (`createOptionForm()` é só um atalho
                                    // desse mesmo mecanismo, específico pra
                                    // criar uma opção de Select — não serve
                                    // aqui porque não estamos criando uma
                                    // opção de relacionamento, só rodando um
                                    // cálculo/conferência sem persistir nada).
                                    // `->mountUsing()` reseta o resultado
                                    // anterior (`$livewire->promobResultado`,
                                    // trait `HasPromobResultado`) toda vez que
                                    // o modal é reaberto. O `->action()`
                                    // SEMPRE termina com `$action->halt()` —
                                    // o modal nunca "conclui" sozinho (task
                                    // pediu explicitamente: só fecha/cancela
                                    // manualmente, sem persistir nada) — só
                                    // isso já impede o fechamento automático
                                    // que o Filament faria numa Action de
                                    // sucesso normal.
                                    Action::make('inserirItemPromob')
                                        ->label(__('comercial::filament/resources/projeto.form.itens.inserir'))
                                        ->visible(fn (Get $get) => $get('origem_item_selecionada') === 'promob')
                                        ->modalHeading(__('comercial::filament/resources/projeto.form.itens.promob.modal.heading'))
                                        ->modalDescription(__('comercial::filament/resources/projeto.form.itens.promob.modal.description'))
                                        ->modalWidth(Width::Small)
                                        ->modalSubmitActionLabel(__('comercial::filament/resources/projeto.form.itens.promob.modal.processar'))
                                        ->mountUsing(function (?Schema $schema, $livewire): void {
                                            $livewire->promobResultado = null;
                                            $schema?->fill();
                                        })
                                        ->form([
                                            FileUpload::make('arquivos_xml')
                                                ->label(__('comercial::filament/resources/projeto.form.itens.promob.modal.upload-label'))
                                                ->helperText(__('comercial::filament/resources/projeto.form.itens.promob.modal.upload-helper'))
                                                ->multiple()
                                                ->preserveFilenames()
                                                ->acceptedFileTypes(['text/xml', 'application/xml'])
                                                ->disk('local')
                                                ->directory('promob-uploads-tmp')
                                                ->required(),

                                            Text::make(fn ($livewire) => static::renderizarResultadoPromob($livewire->promobResultado))
                                                ->color(fn ($livewire) => static::corResultadoPromob($livewire->promobResultado))
                                                ->visible(fn ($livewire) => filled($livewire->promobResultado)),
                                        ])
                                        ->action(function (array $data, $livewire, Action $action): void {
                                            static::processarUploadPromob($data['arquivos_xml'] ?? [], $livewire);

                                            $action->halt();
                                        }),

                                    // Sem ação própria ainda — mesmo padrão do
                                    // botão "Inserir" quando foi criado
                                    // (notificação placeholder), reaproveitando
                                    // o mesmo par de traduções
                                    // pendente-title/pendente-body.
                                    Action::make('mobilizacaoFrete')
                                        ->label(__('comercial::filament/resources/projeto.form.itens.mobilizacao-frete'))
                                        ->color('gray')
                                        ->action(function (): void {
                                            Notification::make()
                                                ->info()
                                                ->title(__('comercial::filament/resources/projeto.form.itens.notification.pendente-title'))
                                                ->body(__('comercial::filament/resources/projeto.form.itens.notification.pendente-body', [
                                                    'origem' => __('comercial::filament/resources/projeto.form.itens.mobilizacao-frete'),
                                                ]))
                                                ->send();
                                        }),
                                ])
                                    ->verticallyAlignEnd()
                                    ->columnSpan(6),
                            ]),

                        // Cabeçalho de colunas estilo planilha (ver aba "00"
                        // do Excel de referência da F.A. Marcenaria) para a
                        // origem "Item Avulso" — só os RÓTULOS; a linha de
                        // INPUT de verdade é o próximo Grid::make(24) logo
                        // abaixo, com os MESMOS columnSpan (alinhamento
                        // coluna a coluna). Última coluna (1) fica sem
                        // rótulo — espaço reservado, sem uso definido ainda.
                        // Coluna "Imp.%" REMOVIDA da tela (2026-09-03) — o
                        // Imposto da Referência de Preços continua entrando
                        // no cálculo (ver `recalcularValoresItemAvulso()`),
                        // só não tem mais coluna própria; o espaço dela foi
                        // redistribuído entre Referência/Descrição/Valor
                        // Unitário/última coluna, ver CLAUDE.md.
                        // 1+4+7+1+3+3+1+3+1 = 24.
                        //
                        // Ícone de ajuda (2026-09-04): 4 colunas (Referência,
                        // Descrição, %, Custo Unitário) têm um ícone "?" com
                        // tooltip anexado ao PRÓPRIO `Text` do cabeçalho (via
                        // `Flex::make([Text::make(...), Icon::make(...)])`),
                        // não ao campo de input da linha de baixo. Motivo: o
                        // rótulo de cada coluna vive só aqui (a linha de
                        // input tem `->hiddenLabel()`/`Text::make('')`), e um
                        // `->hintIcon()` no campo de input se ancora ao label
                        // NATIVO desse campo — que está oculto/vazio — então
                        // o ícone ficava flutuando sozinho sobre o input, sem
                        // nenhuma relação visual com o texto do rótulo acima
                        // (achado real, corrigido nesta data). `Icon` (não
                        // `Text::make()->icon()`) porque `Text::toEmbeddedHtml()`
                        // só desenha o ícone no modo `->badge()` (pill com
                        // fundo/borda, indesejado aqui) — no modo normal
                        // (usado por todo o cabeçalho) o ícone informado via
                        // `->icon()` é simplesmente ignorado no render,
                        // confirmado lendo o Blade do componente. `Flex`
                        // porque é um Component de verdade (aceita
                        // `->columnSpan()` do Grid pai, via `CanSpanColumns`
                        // herdado de `Component`) — `->dense()` (gap-3, já
                        // compilado no CSS do Filament) no lugar do gap-6
                        // default: um `class` Tailwind arbitrário via
                        // `->extraAttributes()` (ex. `'gap-1'`) NÃO teria
                        // efeito — o painel admin usa o CSS pré-compilado do
                        // Filament (sem build Tailwind próprio escaneando
                        // este plugin, ver "FilamentAsset::register()" no
                        // CLAUDE.md da raiz), então só classes que o próprio
                        // Filament já usa (e por isso já estão no CSS
                        // publicado) têm efeito.
                        Grid::make(24)
                            ->columnSpanFull()
                            ->extraAttributes($gridGap)
                            ->visible(fn (Get $get) => $get('origem_item_inserida') === 'item_avulso')
                            ->schema([
                                Text::make(__('comercial::filament/resources/projeto.form.itens.cabecalho-item-avulso.item'))
                                    ->weight(FontWeight::Bold)
                                    ->columnSpan(1),
                                Flex::make([
                                    Text::make(__('comercial::filament/resources/projeto.form.itens.cabecalho-item-avulso.referencia'))
                                        ->weight(FontWeight::Bold),
                                    Icon::make('heroicon-o-question-mark-circle')
                                        ->size(IconSize::Small)
                                        ->color('gray')
                                        ->tooltip(__('comercial::filament/resources/projeto.form.itens.referencia-tooltip')),
                                ])
                                    ->verticallyAlignCenter()
                                    ->dense()
                                    ->columnSpan(4),
                                Flex::make([
                                    Text::make(__('comercial::filament/resources/projeto.form.itens.cabecalho-item-avulso.descricao'))
                                        ->weight(FontWeight::Bold),
                                    Icon::make('heroicon-o-question-mark-circle')
                                        ->size(IconSize::Small)
                                        ->color('gray')
                                        ->tooltip(__('comercial::filament/resources/projeto.form.itens.descricao-atalhos')),
                                ])
                                    ->verticallyAlignCenter()
                                    ->dense()
                                    ->columnSpan(7),
                                Text::make(__('comercial::filament/resources/projeto.form.itens.cabecalho-item-avulso.quantidade'))
                                    ->weight(FontWeight::Bold)
                                    ->columnSpan(1),
                                Text::make(__('comercial::filament/resources/projeto.form.itens.cabecalho-item-avulso.valor-unitario'))
                                    ->weight(FontWeight::Bold)
                                    ->columnSpan(3),
                                Text::make(__('comercial::filament/resources/projeto.form.itens.cabecalho-item-avulso.valor-total'))
                                    ->weight(FontWeight::Bold)
                                    ->columnSpan(3),
                                Flex::make([
                                    Text::make(__('comercial::filament/resources/projeto.form.itens.cabecalho-item-avulso.porcentagem'))
                                        ->weight(FontWeight::Bold),
                                    Icon::make('heroicon-o-question-mark-circle')
                                        ->size(IconSize::Small)
                                        ->color('gray')
                                        ->tooltip(__('comercial::filament/resources/projeto.form.itens.porcentagem-tooltip')),
                                ])
                                    ->verticallyAlignCenter()
                                    ->dense()
                                    ->columnSpan(1),
                                Flex::make([
                                    Text::make(__('comercial::filament/resources/projeto.form.itens.cabecalho-item-avulso.custo-unitario'))
                                        ->weight(FontWeight::Bold),
                                    Icon::make('heroicon-o-question-mark-circle')
                                        ->size(IconSize::Small)
                                        ->color('gray')
                                        ->tooltip(__('comercial::filament/resources/projeto.form.itens.custo-unitario-tooltip')),
                                ])
                                    ->verticallyAlignCenter()
                                    ->dense()
                                    ->columnSpan(3),
                                Text::make('') // Sem rótulo — espaço reservado, sem uso definido ainda.
                                    ->columnSpan(1),
                            ]),

                        // Linha de INPUT de Item Avulso — mesmos columnSpan
                        // do cabeçalho acima, pra alinhar coluna a coluna.
                        // Nenhum dado é persistido aqui ainda (todos os
                        // campos `dehydrated(false)`) — é só a interface
                        // reativa calculando em tempo real; a tabela de
                        // Itens e a ação de confirmar/salvar de fato ficam
                        // pra uma tarefa futura (ver botão "confirmar" da
                        // última coluna, ainda sem ação real).
                        Grid::make(24)
                            ->columnSpanFull()
                            ->extraAttributes($gridGap)
                            ->visible(fn (Get $get) => $get('origem_item_inserida') === 'item_avulso')
                            ->schema([
                                // Numeração automática (###) — em MODO
                                // EDIÇÃO (`item_em_edicao_id` preenchido)
                                // mostra o número REAL já gravado daquele
                                // item; em modo inserção (linha nova) mostra
                                // uma PRÉVIA do próximo número, mesma conta
                                // usada de verdade por `ItemProjeto::boot()`
                                // (`creating`) — `MAX(...) + 1` — pra nunca
                                // divergir do número que será gravado ao
                                // confirmar. Sem `withTrashed()` — `ItemProjeto`
                                // não usa `SoftDeletes` (ver Model/CLAUDE.md).
                                Placeholder::make('novo_item_numero_display')
                                    ->hiddenLabel()
                                    ->content(function (Get $get, ?Projeto $record): string {
                                        $itemEmEdicaoId = $get('item_em_edicao_id');

                                        if (filled($itemEmEdicaoId)) {
                                            $numero = $record?->itens()->find($itemEmEdicaoId)?->numero_item;

                                            if (filled($numero)) {
                                                return $numero;
                                            }
                                        }

                                        $ultimoNumero = (int) ($record?->itens()->max('numero_item') ?? 0);

                                        return str_pad((string) ($ultimoNumero + 1), 3, '0', STR_PAD_LEFT);
                                    })
                                    ->columnSpan(1),

                                // Referência: sem campo para Item Avulso, só
                                // reserva a coluna (mesma lógica das colunas
                                // vazias do cabeçalho).
                                Text::make('')
                                    ->columnSpan(4),

                                // Toolbar REMOVIDA (`->toolbarButtons([])`)
                                // — depois de investigar e descartar o modo
                                // "bubble menu" (aparece só em foco, ver
                                // CLAUDE.md), a decisão foi tirar a barra de
                                // botões de vez. O campo continua sendo um
                                // RichEditor de verdade — todas as extensões
                                // (Bold, Italic, Underline etc.) seguem
                                // carregadas e os atalhos de teclado
                                // funcionam normalmente, só o botão visual é
                                // que some (`getToolbarButtons()` vazio pula
                                // o `<div class="fi-fo-rich-editor-toolbar">`
                                // inteiro no render, ver
                                // `RichEditor::toEmbeddedHtml()`). O ícone de
                                // ajuda com os atalhos (tooltip) NÃO fica mais
                                // aqui — `->hintIcon()` se ancora ao label
                                // NATIVO do campo, que este campo não tem
                                // (`->hiddenLabel()`); o ícone vive no `Text`
                                // "Descrição" do cabeçalho acima (ver
                                // Grid::make(24) anterior), ao lado do rótulo
                                // de verdade.
                                RichEditor::make('novo_item_descricao')
                                    ->hiddenLabel()
                                    ->toolbarButtons([])
                                    ->dehydrated(false)
                                    ->columnSpan(7),

                                TextInput::make('novo_item_quantidade')
                                    ->hiddenLabel()
                                    ->numeric()
                                    ->integer()
                                    ->minValue(0)
                                    ->live(onBlur: true)
                                    ->dehydrated(false)
                                    ->extraInputAttributes(['class' => 'fi-input-no-spinner'])
                                    ->afterStateUpdated(fn (Get $get, Set $set) => static::recalcularValoresItemAvulso($get, $set))
                                    ->columnSpan(1),

                                TextInput::make('novo_item_valor_unitario')
                                    ->hiddenLabel()
                                    ->numeric()
                                    ->prefix('R$')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->columnSpan(3),

                                TextInput::make('novo_item_valor_total')
                                    ->hiddenLabel()
                                    ->numeric()
                                    ->prefix('R$')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->columnSpan(3),

                                // Coluna "Imp.%" REMOVIDA da tela (2026-09-03)
                                // — vira `Hidden` (sem `columnSpan` próprio,
                                // `Hidden::setUp()` já usa
                                // `columnSpan(['default' => 'hidden'])`, não
                                // consome espaço no Grid). Continua vindo da
                                // Referência de Preços ATUALMENTE selecionada
                                // no Cabeçalho (`referencia_preco_id`),
                                // preenchido pela própria Action "Inserir"
                                // (acima, quando "Item Avulso" é escolhido) —
                                // não por `->default()` aqui: o campo vive
                                // dentro de um Grid com `->visible()`
                                // condicional, e o `fill()` inicial da
                                // página não hidrata campos que começam
                                // escondidos (confirmado via
                                // `Livewire::test()`). Sem Referência
                                // vinculada fica em branco e é tratado como
                                // 0% no cálculo (ver
                                // `recalcularValoresItemAvulso()`) — o aviso
                                // em vermelho de "sem Referência" já existe
                                // no próprio campo lá no Cabeçalho.
                                Hidden::make('novo_item_imposto')
                                    ->dehydrated(false),

                                TextInput::make('novo_item_porcentagem')
                                    ->hiddenLabel()
                                    ->numeric()
                                    ->integer()
                                    ->live(onBlur: true)
                                    ->dehydrated(false)
                                    ->extraInputAttributes(['class' => 'fi-input-no-spinner'])
                                    ->afterStateUpdated(fn (Get $get, Set $set) => static::recalcularValoresItemAvulso($get, $set))
                                    ->columnSpan(1),

                                TextInput::make('novo_item_custo_unitario')
                                    ->hiddenLabel()
                                    ->numeric()
                                    ->minValue(0)
                                    ->prefix('R$')
                                    ->live(onBlur: true)
                                    ->dehydrated(false)
                                    // Mesmo asset já usado em Qtde./Porc.%
                                    // (`resources/css/filament/admin-input-no-spinner.css`,
                                    // ver CLAUDE.md) — reaproveitado aqui,
                                    // não duplicado, pra esconder as setas
                                    // de incremento/decremento também no
                                    // Custo Unit.
                                    ->extraInputAttributes(['class' => 'fi-input-no-spinner'])
                                    ->afterStateUpdated(fn (Get $get, Set $set) => static::recalcularValoresItemAvulso($get, $set))
                                    ->columnSpan(3),

                                // Confirmar inserção/alteração do item —
                                // grava de fato em `itens_projeto` (ver
                                // `confirmarItemAvulso()`). Mesmo ícone em
                                // modo inserção E em modo edição (task
                                // pediu explicitamente o MESMO ícone de
                                // confirmação nos dois casos) — só o
                                // resultado interno muda (`create` vs.
                                // `update`, ver `item_em_edicao_id`).
                                Actions::make([
                                    Action::make('confirmarItemAvulso')
                                        ->label(__('comercial::filament/resources/projeto.form.itens.confirmar'))
                                        ->icon('heroicon-o-check-circle')
                                        ->iconButton()
                                        ->color('success')
                                        ->action(fn (Get $get, Set $set, ?Projeto $record, $livewire) => static::confirmarItemAvulso($get, $set, $record, $livewire)),
                                ])
                                    ->alignCenter()
                                    ->verticallyAlignStart()
                                    ->columnSpan(1),
                            ]),

                        // Listagem dos itens JÁ inseridos no Projeto — TODOS
                        // eles, não só os de origem Item Avulso (única
                        // origem com persistência real até agora, mas a
                        // área de listagem é a mesma pras 7, ver CLAUDE.md).
                        // `Group` (não outro `Section`) só pra agrupar as
                        // linhas dinamicamente geradas sem nenhum wrapper
                        // visual extra. O item ATUALMENTE em edição
                        // (`item_em_edicao_id`) é OMITIDO daqui de
                        // propósito — os dados dele já estão sendo
                        // mostrados na linha de INPUT acima (ver
                        // `abrirEdicaoItemAvulso()`); mostrar as duas ao
                        // mesmo tempo duplicaria a linha na tela.
                        //
                        // Lê `$livewire->itensCarregados` (hidratado do
                        // banco no `mount()` de `EditProjeto`, ver essa
                        // classe) em vez de reconsultar `$record->itens()`
                        // aqui de novo — achado real (2026-09-05): a
                        // listagem ficava vazia ao abrir a tela de edição
                        // com itens já salvos. `$livewire instanceof
                        // EditProjeto` (não `$record` truthy) é o critério
                        // certo aqui: só `EditProjeto` declara/hidrata essa
                        // property (mesmo padrão já usado por "Atribuir
                        // Processos" — CreateProjeto simplesmente não tem
                        // a property).
                        Group::make()
                            ->columnSpanFull()
                            ->schema(function (Get $get, $livewire): array {
                                if (! $livewire instanceof EditProjeto) {
                                    return [];
                                }

                                $itemEmEdicaoId = $get('item_em_edicao_id');

                                return $livewire->itensCarregados
                                    ->reject(fn (ItemProjeto $item) => filled($itemEmEdicaoId) && ((string) $item->id === (string) $itemEmEdicaoId))
                                    ->map(fn (ItemProjeto $item) => static::linhaExibicaoItem($item))
                                    ->all();
                            }),
                    ]),
            ]);
    }

    /**
     * Opções de origem do item — só a estrutura do seletor por enquanto;
     * a lógica de inserção de cada origem é definida numa tarefa futura.
     *
     * @return array<string, string>
     */
    protected static function origensItemOptions(): array
    {
        return [
            'item_avulso' => __('comercial::filament/resources/projeto.form.itens.origens.item-avulso'),
            'item_linha'  => __('comercial::filament/resources/projeto.form.itens.origens.item-linha'),
            'promob'      => __('comercial::filament/resources/projeto.form.itens.origens.promob'),
            'sketchup'    => __('comercial::filament/resources/projeto.form.itens.origens.sketchup'),
        ];
    }

    /**
     * Lê cada arquivo enviado no modal de "Promob" (caminhos já
     * armazenados pelo `FileUpload::make('arquivos_xml')`, disco
     * `local`), roda `PromobChecagemTotal::checar()` e guarda o
     * resultado em `$livewire->promobResultado` (trait
     * `HasPromobResultado`) — o próprio `Text` do modal (ver Action
     * `inserirItemPromob`) lê essa property pra exibir o resultado sem
     * fechar o modal. Os arquivos são temporários (só servem pra este
     * cálculo) — apagados do disco logo depois de lidos, sucesso ou
     * erro, pra não acumular XML nenhum em `storage/app/promob-uploads-tmp`.
     *
     * Não cria nenhum registro em `itens_projeto` — só calcula e
     * compara (ver CLAUDE.md, "Fluxo Promob").
     *
     * @param  array<int, string>  $caminhosArquivos
     */
    protected static function processarUploadPromob(array $caminhosArquivos, $livewire): void
    {
        $xmlsPorNomeDeArquivo = [];

        foreach ($caminhosArquivos as $caminho) {
            $xmlsPorNomeDeArquivo[basename($caminho)] = Storage::disk('local')->get($caminho);
        }

        foreach ($caminhosArquivos as $caminho) {
            Storage::disk('local')->delete($caminho);
        }

        try {
            $livewire->promobResultado = PromobChecagemTotal::checar($xmlsPorNomeDeArquivo);
        } catch (\Throwable $e) {
            $livewire->promobResultado = ['erro' => $e->getMessage()];
        }
    }

    /**
     * @param  array<string, mixed>|null  $resultado
     */
    protected static function corResultadoPromob(?array $resultado): ?string
    {
        if ($resultado === null) {
            return null;
        }

        if (isset($resultado['erro'])) {
            return 'danger';
        }

        if (! $resultado['metricas']['tem_geral']) {
            return 'warning';
        }

        return static::diferencaMetricasZerada($resultado['metricas']['diferenca']) ? 'success' : 'warning';
    }

    /**
     * `!= 0` (não `!==`) de propósito — a diferença de cada métrica é
     * `int|float` (`Peças` int, as demais float, possivelmente `-0.0`
     * depois de arredondar uma subtração bem próxima de zero) e o
     * critério aqui é só "é zero pra fins de exibição", não o tipo.
     *
     * @param  array<string, int|float>  $diferenca
     */
    private static function diferencaMetricasZerada(array $diferenca): bool
    {
        foreach ($diferenca as $valor) {
            if ($valor != 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * Monta o texto exibido no modal do Promob depois de "Checar Total"
     * — `nl2br(e(...))` sobre um texto puro montado linha a linha
     * (mais simples e mais seguro contra XSS do que ir escapando pedaço
     * por pedaço com `sprintf`, já que `numero_item`/mensagens de erro
     * acabam vindo do NOME do arquivo/conteúdo do XML enviado pelo
     * usuário). Resultado PRINCIPAL: as 5 métricas do VBA (ver
     * `PromobXmlParser::metricas()`/CLAUDE.md, "Fluxo Promob") —
     * Custo/Preço com margens (checagem já existente antes desta
     * tarefa) aparece só como informação COMPLEMENTAR, ao final.
     *
     * @param  array<string, mixed>|null  $resultado
     */
    protected static function renderizarResultadoPromob(?array $resultado): ?HtmlString
    {
        if ($resultado === null) {
            return null;
        }

        if (isset($resultado['erro'])) {
            return new HtmlString(nl2br(e($resultado['erro'])));
        }

        $metricas = $resultado['metricas'];

        $linhas = [
            __('comercial::filament/resources/projeto.form.itens.promob.resultado.titulo'),
        ];

        if ($metricas['tem_geral']) {
            $linhas[] = __('comercial::filament/resources/projeto.form.itens.promob.resultado.comparacao-cabecalho', [
                'quantidade' => $metricas['quantidade_parciais'],
            ]);
            $linhas[] = '';
            $linhas = [...$linhas, ...static::linhasMetricas($metricas['diferenca'])];
        } else {
            $linhas[] = __('comercial::filament/resources/projeto.form.itens.promob.resultado.sem-geral', [
                'quantidade' => $metricas['quantidade_parciais'],
            ]);
            $linhas[] = '';
            $linhas = [...$linhas, ...static::linhasMetricas($metricas['parciais'])];
        }

        // Comparação Custo/Preço (com margens) — complementar, só
        // calculada quando o XML "000" foi enviado (`bateu` fica
        // `null` sem ele, ver `PromobChecagemTotal::compararCustoPreco()`).
        if ($resultado['bateu'] !== null) {
            $linhas[] = '';
            $linhas[] = __('comercial::filament/resources/projeto.form.itens.promob.resultado.custo_preco.titulo');
            $linhas[] = __('comercial::filament/resources/projeto.form.itens.promob.resultado.custo_preco.'.($resultado['bateu'] ? 'bateu' : 'nao-bateu'));
            $linhas[] = __('comercial::filament/resources/projeto.form.itens.promob.resultado.custo_preco.totais', [
                'custo' => number_format($resultado['custo_calculado'], 2, ',', '.'),
                'preco' => number_format($resultado['preco_calculado'], 2, ',', '.'),
            ]);

            if (! $resultado['bateu']) {
                $linhas[] = __('comercial::filament/resources/projeto.form.itens.promob.resultado.custo_preco.total-esperado', [
                    'custo' => number_format($resultado['custo_esperado'], 2, ',', '.'),
                    'preco' => number_format($resultado['preco_esperado'], 2, ',', '.'),
                ]);

                if (filled($resultado['diferencas'])) {
                    foreach ($resultado['diferencas'] as $diferenca) {
                        $linhas[] = __('comercial::filament/resources/projeto.form.itens.promob.resultado.custo_preco.diferenca-item', [
                            'item'            => $diferenca['item'],
                            'custo_esperado'  => number_format($diferenca['custo_esperado'], 2, ',', '.'),
                            'preco_esperado'  => number_format($diferenca['preco_esperado'], 2, ',', '.'),
                            'custo_calculado' => number_format($diferenca['custo_calculado'], 2, ',', '.'),
                            'preco_calculado' => number_format($diferenca['preco_calculado'], 2, ',', '.'),
                        ]);
                    }
                } else {
                    $linhas[] = __('comercial::filament/resources/projeto.form.itens.promob.resultado.custo_preco.sem-diagnostico');
                }
            }
        }

        return new HtmlString(nl2br(e(implode("\n", $linhas))));
    }

    /**
     * Uma linha por métrica, na ordem do VBA: Peças, m², Metro Linear,
     * Custo, Misc. "Peças" sem casas decimais; as demais com 2 casas —
     * padrão brasileiro de milhar/decimal em todas (`number_format`
     * com `,`/`.` invertidos do padrão americano).
     *
     * @param  array{pecas: int, m2: float, mlinear: float, custo: float, misc: float}  $metricas
     * @return array<int, string>
     */
    private static function linhasMetricas(array $metricas): array
    {
        return [
            __('comercial::filament/resources/projeto.form.itens.promob.resultado.metrica-pecas', [
                'valor' => number_format($metricas['pecas'], 0, ',', '.'),
            ]),
            __('comercial::filament/resources/projeto.form.itens.promob.resultado.metrica-m2', [
                'valor' => number_format($metricas['m2'], 2, ',', '.'),
            ]),
            __('comercial::filament/resources/projeto.form.itens.promob.resultado.metrica-mlinear', [
                'valor' => number_format($metricas['mlinear'], 2, ',', '.'),
            ]),
            __('comercial::filament/resources/projeto.form.itens.promob.resultado.metrica-custo', [
                'valor' => number_format($metricas['custo'], 2, ',', '.'),
            ]),
            __('comercial::filament/resources/projeto.form.itens.promob.resultado.metrica-misc', [
                'valor' => number_format($metricas['misc'], 2, ',', '.'),
            ]),
        ];
    }

    /**
     * Valor Unitário = Custo Unitário × (1 + Porc.%/100) × (1 + Imp.%/100)
     * Valor Total    = Valor Unitário × Quantidade
     *
     * Função PURA (sem `Get`/`Set`, sem tocar em Referência de Preços) —
     * usada tanto pela prévia reativa em tela (`recalcularValoresItemAvulso()`,
     * que lê o Imp.% já em cache em `novo_item_imposto`, só pra exibição
     * enquanto o usuário digita) quanto pela gravação de verdade
     * (`confirmarItemAvulso()`, que busca o Imp.% FRESCO do banco antes
     * de chamar esta função — ver essa subseção pro motivo, achado real
     * de concorrência em `INVESTIGACAO-TRANSACOES-CONCORRENCIA.md`).
     * Extraída à parte de propósito: sem essa separação, corrigir o
     * "Imposto obsoleto" exigiria duplicar a fórmula em vez de só trocar
     * QUAL Imp.% entra nela.
     *
     * @return array{valor_unitario: float, valor_total: float}
     */
    protected static function calcularValoresItemAvulso(float $custoUnitario, float $quantidade, float $porcentagem, float $imposto): array
    {
        $valorUnitario = $custoUnitario * (1 + ($porcentagem / 100)) * (1 + ($imposto / 100));
        $valorTotal = $valorUnitario * $quantidade;

        return [
            'valor_unitario' => round($valorUnitario, 2),
            'valor_total'    => round($valorTotal, 2),
        ];
    }

    /**
     * Recalcula a PRÉVIA em tela a cada tecla em Qtde./Porc.%/Custo
     * Unitário — usa o Imp.% já em cache (`novo_item_imposto`, carregado
     * uma vez ao abrir a linha, ver `inserirItem`/`abrirEdicaoItemAvulso()`).
     * Essa prévia PODE ficar obsoleta se a Referência de Preços mudar
     * enquanto o usuário digita — sem problema aqui, é só exibição; a
     * gravação de verdade (`confirmarItemAvulso()`) sempre busca o valor
     * FRESCO do banco antes de persistir, independente do que esta
     * prévia mostrou.
     *
     * Sem Quantidade OU Custo Unitário (vazios/zerados), os dois campos
     * calculados ficam em branco — não há erro, só nada pra calcular
     * ainda. Imp.% sem Referência de Preços vinculada ao Projeto entra
     * como 0% (ver campo `novo_item_imposto` acima).
     */
    protected static function recalcularValoresItemAvulso(Get $get, Set $set): void
    {
        $quantidade = $get('novo_item_quantidade');
        $custoUnitario = $get('novo_item_custo_unitario');

        if (blank($quantidade) || ((float) $quantidade <= 0) || blank($custoUnitario) || ((float) $custoUnitario <= 0)) {
            $set('novo_item_valor_unitario', null);
            $set('novo_item_valor_total', null);

            return;
        }

        $porcentagem = (float) ($get('novo_item_porcentagem') ?: 0);
        $imposto = (float) ($get('novo_item_imposto') ?: 0);

        $valores = static::calcularValoresItemAvulso((float) $custoUnitario, (float) $quantidade, $porcentagem, $imposto);

        $set('novo_item_valor_unitario', $valores['valor_unitario']);
        $set('novo_item_valor_total', $valores['valor_total']);
    }

    /**
     * Valida e persiste a linha de Item Avulso (`novo_item_*`) — chamada
     * pelo ícone de confirmação, tanto em modo INSERÇÃO
     * (`item_em_edicao_id` vazio, cria um `ItemProjeto` novo) quanto em
     * modo EDIÇÃO (preenchido, `update()` só se algo mudou — ver
     * `itemAvulsoMudou()`).
     *
     * Sem `$record` (página de CRIAÇÃO do Projeto, ainda sem salvar):
     * bloqueia com notificação — `itens_projeto.projeto_id` exige um
     * Projeto já existente, mesmo critério já usado pelo botão "Atribuir
     * Processos" (só em `EditProjeto`, ver CLAUDE.md).
     *
     * Validação MANUAL (`ValidationException::withMessages(['data.<campo>'
     * => ...])`), não `->required()` nos campos do Schema — os campos
     * `novo_item_*` são compartilhados por TODA a Section "Itens"
     * (inclusive quando nenhum item está sendo inserido/editado); se
     * fossem `->required()` no Schema, o botão Salvar/Cancelar do
     * CABEÇALHO (`getFormActionsContentComponent()`, formulário
     * DIFERENTE) passaria a exigi-los também sempre que a linha de Item
     * Avulso estivesse visível, mesmo sem o usuário ter clicado em
     * confirmar — efeito colateral indesejado. `ValidationException
     * ::withMessages()` é o mesmo mecanismo usado por
     * `Filament\Auth\Pages\Login::throwFailureValidationException()`
     * (vendor) pra anexar erro a um campo específico do formulário sem
     * depender de regras declaradas no Schema — confirmado lendo o
     * código-fonte, não presumido. `'data.'` é o prefixo porque
     * `EditRecord`/`CreateRecord` usam `->statePath('data')`
     * (`defaultForm()`, vendor).
     *
     * **Imposto obsoleto — corrigido em 2026-09-05** (achado real de
     * concorrência, ver `INVESTIGACAO-TRANSACOES-CONCORRENCIA.md`):
     * `novo_item_imposto` (lido uma vez ao abrir a linha, ver
     * `inserirItem`/`abrirEdicaoItemAvulso()`) fica em CACHE no estado do
     * componente Livewire por todo o tempo que o usuário leva
     * preenchendo/revendo o item — se outra sessão mudar o `imposto` da
     * Referência de Preços nesse meio-tempo, o valor gravado usaria o
     * Imp.% ANTIGO, sem ninguém perceber. Este método NÃO usa
     * `novo_item_imposto` pra gravar — busca o `imposto` FRESCO do banco
     * (`ReferenciaPreco::lockForUpdate()`) NO MOMENTO exato do clique em
     * "Confirmar", dentro da MESMA `DB::transaction()` da gravação, e
     * `lockForUpdate()` na Referência de Preços trava qualquer alteração
     * concorrente dela até esta transação terminar — fecha de vez a
     * janela de corrida (não só reduz), pelo menos entre o clique e o
     * commit. `imposto_aplicado` grava esse valor no próprio
     * `ItemProjeto`, preservando o histórico do cálculo mesmo que a
     * Referência de Preços mude depois.
     *
     * `$livewire->recarregarItens()` no final (achado real, 2026-09-05,
     * ver `EditProjeto::itensCarregados`) — a listagem de itens já
     * inseridos passou a ler uma property hidratada no `mount()` da
     * página, não mais `$record->itens()` reconsultado a cada render;
     * sem chamar isso aqui, o item recém-criado/editado só apareceria
     * na listagem depois de um reload completo da página.
     */
    protected static function confirmarItemAvulso(Get $get, Set $set, ?Projeto $record, $livewire): void
    {
        if (! $record) {
            Notification::make()
                ->warning()
                ->title(__('comercial::filament/resources/projeto.form.itens.notification.projeto-nao-salvo-title'))
                ->body(__('comercial::filament/resources/projeto.form.itens.notification.projeto-nao-salvo-body'))
                ->send();

            return;
        }

        $descricao = (string) $get('novo_item_descricao');
        $quantidade = $get('novo_item_quantidade');
        $custoUnitario = $get('novo_item_custo_unitario');

        $erros = [];

        if (blank(trim(strip_tags($descricao)))) {
            $erros['data.novo_item_descricao'] = __('comercial::filament/resources/projeto.form.itens.validacao.descricao-obrigatoria');
        }

        if (blank($quantidade) || ((float) $quantidade <= 0)) {
            $erros['data.novo_item_quantidade'] = __('comercial::filament/resources/projeto.form.itens.validacao.quantidade-obrigatoria');
        }

        if (blank($custoUnitario) || ((float) $custoUnitario <= 0)) {
            $erros['data.novo_item_custo_unitario'] = __('comercial::filament/resources/projeto.form.itens.validacao.custo-unitario-obrigatorio');
        }

        if ($erros) {
            throw ValidationException::withMessages($erros);
        }

        $porcentagem = (float) ($get('novo_item_porcentagem') ?: 0);
        $referenciaPrecoId = $get('referencia_preco_id');
        $itemEmEdicaoId = $get('item_em_edicao_id');

        DB::transaction(function () use ($record, $descricao, $quantidade, $custoUnitario, $porcentagem, $referenciaPrecoId, $itemEmEdicaoId): void {
            $impostoAplicado = filled($referenciaPrecoId)
                ? (float) (ReferenciaPreco::where('id', $referenciaPrecoId)->lockForUpdate()->value('imposto') ?? 0)
                : 0.0;

            $valores = static::calcularValoresItemAvulso((float) $custoUnitario, (float) $quantidade, $porcentagem, $impostoAplicado);

            $dados = [
                'origem'           => OrigemItemProjeto::ItemAvulso,
                'descricao'        => $descricao,
                'quantidade'       => (int) $quantidade,
                'porcentagem'      => $porcentagem,
                'custo_unitario'   => (float) $custoUnitario,
                'imposto_aplicado' => $impostoAplicado,
                'valor_unitario'   => $valores['valor_unitario'],
                'valor_total'      => $valores['valor_total'],
            ];

            if (filled($itemEmEdicaoId)) {
                $item = $record->itens()->lockForUpdate()->find($itemEmEdicaoId);

                if ($item && static::itemAvulsoMudou($item, $dados)) {
                    $item->update($dados);
                }
            } else {
                // `lockForUpdate()` trava as linhas JÁ existentes deste
                // Projeto contra outra inserção concorrente (2 cliques
                // rápidos) enquanto `ItemProjeto::boot()` calcula o próximo
                // `numero_item` (`MAX() + 1`) — sem tabela de sequência
                // própria (diferente de `numero_projeto`/
                // `GeradorNumeroProjeto`), suficiente pro uso real de "um
                // usuário editando um Projeto por vez" (ver CLAUDE.md). Sem
                // proteção no primeiro item de um Projeto (nada pra travar
                // ainda) — risco aceito, ver
                // INVESTIGACAO-TRANSACOES-CONCORRENCIA.md.
                $record->itens()->lockForUpdate()->get();
                $record->itens()->create($dados);
            }
        });

        if ($livewire instanceof EditProjeto) {
            $livewire->recarregarItens();
        }

        static::resetarLinhaItemAvulso($set);

        Notification::make()
            ->success()
            ->title(__('comercial::filament/resources/projeto.form.itens.notification.item-avulso-confirmado'))
            ->send();
    }

    /**
     * Compara os valores atuais do formulário com os já gravados no
     * registro — usada só em modo EDIÇÃO, pra não disparar nenhum
     * `update()` (nem log de auditoria "updated" vazio) quando o usuário
     * abre a edição e confirma sem mudar nada. Comparação por VALOR
     * normalizado (`round(...,2)` nos decimais, `(int)` na quantidade,
     * `trim()` na descrição) — não por igualdade estrita de string, já
     * que `$dados` vem de `$get()` (tipos soltos de input) e o Model
     * devolve os campos já com cast (`decimal:2`/`integer`).
     *
     * @param  array<string, mixed>  $dados
     */
    protected static function itemAvulsoMudou(ItemProjeto $item, array $dados): bool
    {
        if (trim((string) $item->descricao) !== trim((string) $dados['descricao'])) {
            return true;
        }

        if ((int) $item->quantidade !== (int) $dados['quantidade']) {
            return true;
        }

        foreach (['porcentagem', 'custo_unitario', 'valor_unitario', 'valor_total', 'imposto_aplicado'] as $campo) {
            if (round((float) $item->{$campo}, 2) !== round((float) $dados[$campo], 2)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Fecha a linha de Item Avulso e volta ao estado inicial da Section
     * "Itens" — chamada ao final de `confirmarItemAvulso()`. NÃO reseta
     * `origem_item_selecionada` de propósito (o Select continua com
     * "Item Avulso" escolhido) — depois de confirmar um item é comum
     * querer inserir outro da MESMA origem em seguida; resetar o Select
     * exigiria escolher a origem de novo a cada item.
     */
    protected static function resetarLinhaItemAvulso(Set $set): void
    {
        $set('origem_item_inserida', null);
        $set('item_em_edicao_id', null);
        $set('novo_item_imposto', null);
        $set('novo_item_descricao', null);
        $set('novo_item_quantidade', null);
        $set('novo_item_porcentagem', null);
        $set('novo_item_custo_unitario', null);
        $set('novo_item_valor_unitario', null);
        $set('novo_item_valor_total', null);
    }

    /**
     * Preenche a linha de INPUT com os dados de um item JÁ existente e
     * liga o modo EDIÇÃO (`item_em_edicao_id`) — chamada pelo ícone de
     * edição de uma linha da listagem (`linhaExibicaoItem()`). Recalcula
     * Valor Unitário/Valor Total a partir do Imposto ATUAL da Referência
     * de Preços do Cabeçalho (mesma regra já usada por "Inserir", não o
     * Imp.% que foi usado quando o item foi originalmente gravado — a
     * tarefa pediu explicitamente "recalculados normalmente" ao entrar
     * em edição) — se a Referência não mudou desde a criação do item, o
     * resultado bate exatamente com o valor já salvo (importante pra
     * `itemAvulsoMudou()` não acusar mudança sem o usuário ter alterado
     * nada).
     */
    protected static function abrirEdicaoItemAvulso(Get $get, Set $set, ItemProjeto $item): void
    {
        $set('origem_item_inserida', OrigemItemProjeto::ItemAvulso->value);
        $set('item_em_edicao_id', $item->id);

        $referenciaPrecoId = $get('referencia_preco_id');

        $set('novo_item_imposto', filled($referenciaPrecoId)
            ? ReferenciaPreco::find($referenciaPrecoId)?->imposto
            : null);
        $set('novo_item_descricao', $item->descricao);
        $set('novo_item_quantidade', $item->quantidade);
        $set('novo_item_porcentagem', $item->porcentagem);
        $set('novo_item_custo_unitario', $item->custo_unitario);

        static::recalcularValoresItemAvulso($get, $set);
    }

    /**
     * Uma linha de EXIBIÇÃO da listagem de itens já inseridos — mesma
     * distribuição de `columnSpan` do cabeçalho/linha de input (1,4,7,1,
     * 3,3,1,3,1), só que com `Text` somente-leitura em vez de campos, e
     * um `ActionGroup` (Editar/Excluir) na última coluna no lugar do
     * ícone de confirmação. Descrição aparece em TEXTO PURO
     * (`Str::stripTags()`) — o dado gravado é HTML (RichEditor), mas
     * exibir a formatação de verdade aqui exigiria um componente
     * `Html`/`View` em vez de `Text`, com risco de quebrar a altura/
     * alinhamento da linha (`<p>`, listas etc. dentro de uma grid
     * pensada pra uma linha só); a formatação completa continua
     * disponível ao entrar em modo edição (RichEditor de verdade).
     *
     * **`ActionGroup` (dropdown), não dois `iconButton()` lado a lado**
     * — a última coluna é `columnSpan(1)`, a MESMA largura estreita
     * usada por "Item"/"Qtde."/"%" (calibrada pra caber só UM ícone,
     * ver "Última coluna" na subseção da linha de input). Editar +
     * Excluir juntos não caberiam sem alargar essa coluna — o que
     * quebraria o alinhamento coluna-a-coluna com cabeçalho/linha de
     * input, que não precisam de duas ações. `ActionGroup` resolve sem
     * mexer no grid: um único ícone-gatilho (reticências), MESMO
     * espaço de sempre, abrindo um dropdown com as duas opções.
     */
    protected static function linhaExibicaoItem(ItemProjeto $item): Grid
    {
        $moeda = fn (mixed $valor): string => 'R$ '.number_format((float) $valor, 2, ',', '.');

        return Grid::make(24)
            ->key("item-projeto-{$item->id}")
            ->columnSpanFull()
            ->extraAttributes(['style' => 'gap: 1rem !important;'])
            ->schema([
                Text::make($item->numero_item)
                    ->columnSpan(1),
                Text::make('') // Referência — Item Avulso não usa esta coluna.
                    ->columnSpan(4),
                Text::make(Str::of((string) $item->descricao)->stripTags()->trim()->toString())
                    ->columnSpan(7),
                Text::make((string) $item->quantidade)
                    ->columnSpan(1),
                Text::make($moeda($item->valor_unitario))
                    ->columnSpan(3),
                Text::make($moeda($item->valor_total))
                    ->columnSpan(3),
                Text::make(number_format((float) $item->porcentagem, 2, ',', '.').'%')
                    ->columnSpan(1),
                Text::make($moeda($item->custo_unitario))
                    ->columnSpan(3),
                Actions::make([
                    ActionGroup::make([
                        Action::make("editarItemProjeto{$item->id}")
                            ->label(__('comercial::filament/resources/projeto.form.itens.editar'))
                            ->icon('heroicon-o-pencil-square')
                            ->action(fn (Get $get, Set $set) => static::abrirEdicaoItemAvulso($get, $set, $item)),
                        // `DeleteAction` só pelo VISUAL padrão (ícone de
                        // lixeira, cor "danger", `->requiresConfirmation()`
                        // já ligado por padrão em `setUp()`) — mesmo
                        // mecanismo de confirmação já usado em qualquer
                        // outra exclusão do sistema (ver `table()` deste
                        // Resource). `->record($item)` é OBRIGATÓRIO: sem
                        // ele a Action cairia no record do CONTAINER (o
                        // Projeto, não o Item), já que este componente não
                        // vive dentro de uma Table de verdade.
                        // `->action()` substitui o `$record->delete()`
                        // padrão pela exclusão + renumeração de verdade
                        // (`excluirItemAvulso()`) — a notificação de
                        // sucesso embutida do `DeleteAction` não dispara
                        // mais (não é chamada por esse `->action()`
                        // customizado); `excluirItemAvulso()` manda a
                        // própria.
                        DeleteAction::make("excluirItemProjeto{$item->id}")
                            ->label(__('comercial::filament/resources/projeto.form.itens.excluir'))
                            ->record($item)
                            ->modalHeading(__('comercial::filament/resources/projeto.form.itens.excluir-confirmacao.heading', ['numero' => $item->numero_item]))
                            ->modalDescription(__('comercial::filament/resources/projeto.form.itens.excluir-confirmacao.description'))
                            ->action(fn ($livewire) => static::excluirItemAvulso($item, $livewire)),
                    ])
                        ->icon('heroicon-m-ellipsis-vertical')
                        ->color('gray'),
                ])
                    ->alignCenter()
                    ->verticallyAlignStart()
                    ->columnSpan(1),
            ]);
    }

    /**
     * Exclui um Item de Projeto e RENUMERA os itens seguintes daquele
     * mesmo Projeto pra fechar o buraco na sequência (`numero_item`
     * contíguo, sem pulos) — ex.: excluir `002` de `001`/`002`/`003`
     * faz o antigo `003` virar `002`. Exclusão DEFINITIVA (sem
     * `SoftDeletes`, ver `ItemProjeto`) — a renumeração exige que o
     * número excluído fique de verdade livre pro índice único
     * `(projeto_id, numero_item)` da migration.
     *
     * `DB::transaction()` + `lockForUpdate()` nos itens seguintes: a
     * combinação exclusão+renumeração precisa ser atômica (nunca deixar
     * números duplicados/pulados se algo falhar no meio) — se a
     * renumeração de um item seguinte falhasse depois do `delete()`
     * já ter rodado, sem transação o Projeto ficaria com um buraco
     * permanente na numeração. Renumera em ORDEM CRESCENTE de
     * `numero_item` de propósito: ao processar o item seguinte ao
     * excluído primeiro, o número dele já fica LIVRE antes do próximo
     * item da lista precisar dele — sem essa ordem, dois itens
     * poderiam colidir temporariamente no mesmo `numero_item` e violar
     * o índice único no meio do laço.
     *
     * **`forceFill()`, não `update()`** — achado real (2026-09-04):
     * `numero_item` fica DE PROPÓSITO fora do `$fillable` de
     * `ItemProjeto` (só `ItemProjeto::boot()` deve escrever nele) —
     * `update(['numero_item' => ...])` respeita mass assignment e
     * IGNORA SILENCIOSAMENTE qualquer chave fora do `$fillable`, sem
     * erro nenhum. A primeira versão usava `update()` aqui: o `SELECT`
     * encontrava os itens certos, o código "renumerava" sem exceção
     * nenhuma, mas o `numero_item` no banco não mudava — só descoberto
     * rodando o método isolado (fora do Filament) e imprimindo o SQL/
     * resultado passo a passo. `forceFill()` escreve o atributo
     * ignorando o guard, exatamente a exceção deliberada que este
     * método (e só ele) precisa.
     *
     * `$livewire->recarregarItens()` depois da transação — mesmo motivo
     * de `confirmarItemAvulso()` (ver `EditProjeto::itensCarregados`):
     * sem isso, o item excluído e a renumeração dos seguintes só
     * apareceriam corretos na tela depois de um reload completo.
     */
    protected static function excluirItemAvulso(ItemProjeto $item, $livewire): void
    {
        DB::transaction(function () use ($item): void {
            $projetoId = $item->projeto_id;
            $numeroExcluido = $item->numero_item;

            $item->delete();

            ItemProjeto::where('projeto_id', $projetoId)
                ->where('numero_item', '>', $numeroExcluido)
                ->orderBy('numero_item')
                ->lockForUpdate()
                ->get()
                ->each(function (ItemProjeto $itemPosterior): void {
                    $novoNumero = str_pad((string) (((int) $itemPosterior->numero_item) - 1), 3, '0', STR_PAD_LEFT);

                    $itemPosterior->forceFill(['numero_item' => $novoNumero])->save();
                });
        });

        if ($livewire instanceof EditProjeto) {
            $livewire->recarregarItens();
        }

        Notification::make()
            ->success()
            ->title(__('comercial::filament/resources/projeto.form.itens.notification.item-excluido'))
            ->send();
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
