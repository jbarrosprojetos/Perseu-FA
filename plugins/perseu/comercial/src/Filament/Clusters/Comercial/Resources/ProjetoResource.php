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
use Filament\Schemas\Components\Html;
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
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Perseu\Comercial\Enums\OrigemItemProjeto;
use Perseu\Comercial\Filament\Clusters\Comercial\Resources\ProjetoResource\Pages\CreateProjeto;
use Perseu\Comercial\Filament\Clusters\Comercial\Resources\ProjetoResource\Pages\EditProjeto;
use Perseu\Comercial\Filament\Clusters\Comercial\Resources\ProjetoResource\Pages\ListProjetos;
use Perseu\Comercial\Filament\Clusters\Projetos;
use Perseu\Comercial\Models\FreteMobilizacao;
use Perseu\Comercial\Models\ItemProjeto;
use Perseu\Comercial\Models\NotaProjeto;
use Perseu\Comercial\Models\Projeto;
use Perseu\Comercial\Models\ReferenciaPreco;
use Perseu\Comercial\Services\PromobChecagemTotal;
use Perseu\Comercial\Services\PromobXmlParser;
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
                    // Ícone de "Notas do Projeto" no canto direito do
                    // título da Section — `Section::headerActions()`
                    // (não um componente dentro do `schema()`) é o
                    // mecanismo nativo do Filament pra isso, confirmado
                    // lendo `vendor/filament/schemas/src/Components/
                    // Section.php` (`afterHeader()` já monta
                    // `getHeaderActions()` automaticamente). `->visible()`
                    // só quando o Projeto já foi salvo pelo menos uma vez
                    // — mesmo critério já usado por "Atribuir Processos"
                    // (`EditProjeto::getFormActions()`): sem um
                    // `projeto_id`, não há onde gravar uma nota ainda.
                    // Ver CLAUDE.md, "Notas do Projeto", pro mecanismo
                    // completo (Action aninhada dentro de Action, mesmo
                    // padrão já usado por editar/excluir de Item Avulso,
                    // só que um nível mais profundo).
                    ->headerActions([
                        Action::make('notasProjeto')
                            ->label(__('comercial::filament/resources/projeto.form.notas.acao'))
                            ->icon('heroicon-o-document-text')
                            ->color('gray')
                            ->visible(fn (?Projeto $record) => $record !== null)
                            ->modalHeading(__('comercial::filament/resources/projeto.form.notas.modal.heading'))
                            ->modalWidth(Width::Large)
                            // SEM botão de submit automático — este modal
                            // é só um CONTAINER (lista + formulário de
                            // nova nota + ações por nota), mesmo papel do
                            // modal Promob (`inserirItemPromob`); toda
                            // interação real acontece via as Actions
                            // internas (`adicionarNota`/`editarNota{id}`/
                            // `excluirNota{id}`), nunca pelo submit padrão.
                            ->modalSubmitAction(false)
                            // Reset SEMPRE ao abrir, nunca ao fechar —
                            // mesma lição de Item Avulso/Promob: o botão
                            // "Cancelar" fecha via Alpine puro, sem
                            // nenhuma requisição ao servidor.
                            ->mountUsing(fn (?Schema $schema) => $schema?->fill(['nova_nota' => null]))
                            ->form(fn (?Projeto $record): array => static::camposModalNotasProjeto($record)),
                    ])
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
                                        // selecionada. "Item Avulso" TAMBÉM
                                        // tem seu próprio botão
                                        // (`inserirItemAvulso`, abre modal —
                                        // ver abaixo, mesmo padrão do
                                        // Promob) — este aqui cobre só as
                                        // origens SEM comportamento real
                                        // ainda ("Item de Linha", "SketchUp")
                                        // + o aviso de "sem seleção".
                                        ->visible(fn (Get $get) => ! in_array($get('origem_item_selecionada'), ['promob', 'item_avulso', 'mobilizacao_frete'], true))
                                        ->action(function (Get $get): void {
                                            $origem = $get('origem_item_selecionada');

                                            if (blank($origem)) {
                                                Notification::make()
                                                    ->warning()
                                                    ->title(__('comercial::filament/resources/projeto.form.itens.notification.sem-selecao'))
                                                    ->send();

                                                return;
                                            }

                                            Notification::make()
                                                ->info()
                                                ->title(__('comercial::filament/resources/projeto.form.itens.notification.pendente-title'))
                                                ->body(__('comercial::filament/resources/projeto.form.itens.notification.pendente-body', [
                                                    'origem' => static::origensItemOptions()[$origem] ?? $origem,
                                                ]))
                                                ->send();
                                        }),

                                    // "Inserir" de "Item Avulso" — abre um
                                    // FORM MODAL (mesmo padrão técnico do
                                    // Promob: `Action::make()->form()`, que o
                                    // Filament abre automaticamente como
                                    // modal) no lugar da antiga linha de
                                    // input INLINE (ver "Item Avulso: migração
                                    // de linha inline pra Form Modal" no
                                    // CLAUDE.md pro histórico da decisão).
                                    // Diferente do Promob, este modal É a
                                    // Action de SUBMIT normal do Filament
                                    // (sem `->modalSubmitAction(false)`) —
                                    // os campos aqui são valores escalares
                                    // simples (não upload de arquivo), então
                                    // a desidratação padrão do Schema
                                    // funciona sem nenhum workaround, e
                                    // `->action(function (array $data, ...))`
                                    // já recebe tudo pronto/validado.
                                    // `->mountUsing()` SEMPRE reseta o form
                                    // (criação: tudo em branco; edição:
                                    // preenchido com os dados atuais do item,
                                    // recalculados a partir do Imposto ATUAL
                                    // da Referência de Preços) — aprendido da
                                    // correção do bug de estado do modal
                                    // Promob: nunca confiar em "resetar ao
                                    // fechar", só ao ABRIR.
                                    Action::make('inserirItemAvulso')
                                        ->label(__('comercial::filament/resources/projeto.form.itens.inserir'))
                                        ->visible(fn (Get $get) => $get('origem_item_selecionada') === 'item_avulso')
                                        ->modalHeading(__('comercial::filament/resources/projeto.form.itens.item-avulso-modal.heading-criar'))
                                        ->modalSubmitActionLabel(__('comercial::filament/resources/projeto.form.itens.item-avulso-modal.criar'))
                                        ->mountUsing(fn (?Schema $schema, Get $get, ?Projeto $record) => static::preencherFormularioItemAvulso($schema, $get, $record, null))
                                        ->form(static::camposFormularioItemAvulso())
                                        ->action(function (array $data, Get $get, ?Projeto $record, $livewire): void {
                                            static::salvarItemAvulso($data, $get, $record, $livewire);
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
                                        // SEM botão de submit automático —
                                        // achado real (2026-09-05): o botão
                                        // gerado por `modalSubmitAction()`
                                        // NUNCA passa por `prepareModalAction()`
                                        // (só `getExtraModalFooterActions()`
                                        // passa), então `schemaContainer()`
                                        // fica `null` nele — qualquer `Get`/
                                        // `$record` injetado num
                                        // `->disabled()` desse botão quebra
                                        // com "Call to a member function
                                        // makeGetUtility() on null". "Checar
                                        // Total" e "Criar Itens" (abaixo) são
                                        // os DOIS `extraModalFooterActions`
                                        // deste modal — nenhum submit padrão.
                                        ->modalSubmitAction(false)
                                        ->mountUsing(function (?Schema $schema, $livewire): void {
                                            $livewire->promobResultado = null;
                                            // Reset de "Criar Itens" (2026-09-06) — mesma lição de
                                            // sempre: nunca confiar em estado de uma sessão
                                            // anterior do modal, inclusive "Checar Total já rodou".
                                            $livewire->promobChecagemFeitaComSucesso = false;
                                            $schema?->fill();
                                        })
                                        ->form([
                                            // SEM `->disk()`/`->directory()`
                                            // — como nenhum botão deste modal
                                            // é a Action de "submit"
                                            // (`->modalSubmitAction(false)`
                                            // acima), o Schema nunca desidrata
                                            // e o Filament NUNCA chega a mover
                                            // o upload pro disco configurado
                                            // (ver `arquivosXmlPromobAtuais()`
                                            // pra o porquê disso é
                                            // intencional, não um bug) — os
                                            // arquivos ficam só no
                                            // armazenamento temporário do
                                            // PRÓPRIO Livewire, que o pacote
                                            // já limpa sozinho.
                                            FileUpload::make('arquivos_xml')
                                                ->label(__('comercial::filament/resources/projeto.form.itens.promob.modal.upload-label'))
                                                ->helperText(__('comercial::filament/resources/projeto.form.itens.promob.modal.upload-helper'))
                                                ->multiple()
                                                ->live()
                                                ->acceptedFileTypes(['text/xml', 'application/xml'])
                                                ->required(),

                                            Text::make(fn ($livewire) => static::renderizarResultadoPromob($livewire->promobResultado))
                                                ->color(fn ($livewire) => static::corResultadoPromob($livewire->promobResultado))
                                                ->visible(fn ($livewire) => filled($livewire->promobResultado)),
                                        ])
                                        ->extraModalFooterActions([
                                            // "Checar Total" — começa
                                            // DESABILITADO (nenhum arquivo
                                            // ainda) e só libera quando
                                            // existir, entre os arquivos
                                            // selecionados, um XML "000"
                                            // válido (do Projeto atual — ver
                                            // `promobTemXmlGeralValido()`).
                                            // Sem ele não há contra o que
                                            // comparar as 5 métricas; com só
                                            // o "000" (sem nenhum parcial), o
                                            // resultado mostra os totais do
                                            // "000" "sem subtração" de
                                            // verdade (soma das parciais =
                                            // 0), já documentado assim no
                                            // CLAUDE.md. **SEM `$action->halt()`**
                                            // — achado real (2026-09-06):
                                            // esta Action não tem `->form()`
                                            // nem `requiresConfirmation()`
                                            // (é "chata"/flat), então
                                            // `halt()` não tem NADA próprio
                                            // pra manter aberto — só deixa a
                                            // Action PRESA em
                                            // `$livewire->mountedActions`
                                            // pra sempre (nunca desmonta),
                                            // o que confundia o mecanismo de
                                            // fechar modal do Filament a
                                            // ponto de nem "Cancelar" nem Esc
                                            // conseguirem fechar mais o modal
                                            // PAI depois de clicar aqui uma
                                            // vez (ver CLAUDE.md, "Fluxo
                                            // Promob"). Sem `halt()`, a
                                            // Action conclui normalmente
                                            // (unmount), e o modal do PAI
                                            // (`inserirItemPromob`) continua
                                            // aberto de qualquer forma —
                                            // terminar uma Action ANINHADA
                                            // só remove ELA da pilha, nunca
                                            // fecha quem a chamou.
                                            Action::make('checarTotalPromob')
                                                ->label(__('comercial::filament/resources/projeto.form.itens.promob.modal.processar'))
                                                ->disabled(fn (?Projeto $record, $livewire) => ! static::promobTemXmlGeralValido(static::arquivosXmlPromobAtuais($livewire), $record))
                                                ->action(function (?Projeto $record, $livewire): void {
                                                    $resultado = static::calcularResultadoPromob(static::arquivosXmlPromobAtuais($livewire), $record);
                                                    $livewire->promobResultado = $resultado;
                                                    // "Criar Itens" (2026-09-06) só libera depois
                                                    // de "Checar Total" ter rodado SEM erro fatal
                                                    // pelo menos uma vez nesta sessão do modal —
                                                    // ver `HasPromobResultado::$promobChecagemFeitaComSucesso`.
                                                    // "Sucesso" aqui é "não deu erro" (nome de
                                                    // arquivo inválido, XML corrompido etc.), NÃO
                                                    // "sem divergência" — divergência é permitida,
                                                    // só exige confirmação (ver `criarItensPromob`).
                                                    $livewire->promobChecagemFeitaComSucesso = ! isset($resultado['erro']);
                                                }),

                                            // "Criar Itens" (2026-09-06) — mesma condição de
                                            // habilitação do "Checar Total" acima MAIS a
                                            // exigência de "Checar Total" já ter rodado com
                                            // sucesso nesta sessão (`$promobChecagemFeitaComSucesso`).
                                            // Roda a MESMA checagem internamente mesmo que o
                                            // usuário não tenha clicado "Checar Total" de novo
                                            // depois de trocar os arquivos (`calcularResultadoPromob()`
                                            // reaproveitado) — com qualquer uma das 5 métricas de
                                            // diferença fora de zero, pede confirmação antes de
                                            // seguir (Parte 2 do enunciado); sem nenhuma
                                            // divergência, segue direto pra
                                            // `criarTodosItensPromob()`, que cria a Nota geral
                                            // de checagem e TODOS os Itens de uma vez, sem
                                            // modal por item (2026-09-06, decisão do usuário
                                            // de simplificar o fluxo — ver essa função e
                                            // CLAUDE.md, "Fluxo Promob").
                                            Action::make('criarItensPromob')
                                                ->label(__('comercial::filament/resources/projeto.form.itens.promob.modal.criar-itens'))
                                                ->color('gray')
                                                ->disabled(fn (?Projeto $record, $livewire) => ! static::promobTemXmlGeralValido(static::arquivosXmlPromobAtuais($livewire), $record)
                                                    || ! $livewire->promobChecagemFeitaComSucesso)
                                                ->requiresConfirmation(fn (?Projeto $record, $livewire) => static::promobPrecisaConfirmarCriacao($livewire, $record))
                                                // `modalHeading`/`modalDescription` também PRECISAM
                                                // ser condicionais (não strings fixas) — achado real:
                                                // `Action::shouldOpenModal()` (vendor) abre o modal
                                                // se `hasCustomModalHeading()` OU
                                                // `hasModalDescription()` forem verdadeiros,
                                                // INDEPENDENTE do resultado de `isConfirmationRequired()`
                                                // — com heading/description fixos, o modal de
                                                // confirmação aparecia SEMPRE, mesmo quando
                                                // `requiresConfirmation()` calculava `false` (sem
                                                // divergência nenhuma). Retornar `null` quando não
                                                // precisa confirmar faz `hasCustomModalHeading()`/
                                                // `hasModalDescription()` voltarem `false`
                                                // (`filled()` por baixo dos panos), e `shouldOpenModal()`
                                                // finalmente reflete o resultado real de
                                                // `requiresConfirmation()`.
                                                ->modalHeading(fn (?Projeto $record, $livewire) => static::promobPrecisaConfirmarCriacao($livewire, $record)
                                                    ? __('comercial::filament/resources/projeto.form.itens.promob.modal.confirmar-criacao-heading')
                                                    : null)
                                                ->modalDescription(fn (?Projeto $record, $livewire) => static::promobPrecisaConfirmarCriacao($livewire, $record)
                                                    ? __('comercial::filament/resources/projeto.form.itens.promob.modal.confirmar-criacao-description')
                                                    : null)
                                                ->action(function (?Projeto $record, $livewire): void {
                                                    static::criarTodosItensPromob($record, $livewire);
                                                }),
                                        ]),
                                    // O "Cancelar" nativo do modal continua
                                    // sendo o único jeito de fechar (nenhuma
                                    // das duas Actions acima é um submit) —
                                    // label padrão do Filament, já traduzida
                                    // pt_BR pelo próprio pacote, sem precisar
                                    // sobrescrever.

                                    // "Mobilização e Frete" (2026-09-06) — deixou
                                    // de ser um botão dedicado fora do dropdown e
                                    // virou mais uma origem do Select "Origem do
                                    // Item" (ver `origensItemOptions()`/
                                    // `OrigemItemProjeto`), com Action própria
                                    // (ganhou regra de negócio real, por isso não
                                    // usa mais o botão genérico `inserirItem` —
                                    // mesmo padrão técnico de `inserirItemAvulso`/
                                    // `inserirItemPromob` acima: form modal +
                                    // gravação em `salvarMobilizacaoFrete()`, que
                                    // grava TANTO o `ItemProjeto` quanto o
                                    // `FreteMobilizacao` vinculado, na mesma
                                    // transação).
                                    Action::make('inserirMobilizacaoFrete')
                                        ->label(__('comercial::filament/resources/projeto.form.itens.inserir'))
                                        ->visible(fn (Get $get) => $get('origem_item_selecionada') === 'mobilizacao_frete')
                                        ->modalHeading(__('comercial::filament/resources/projeto.form.itens.mobilizacao-frete-modal.heading-criar'))
                                        ->modalSubmitActionLabel(__('comercial::filament/resources/projeto.form.itens.mobilizacao-frete-modal.criar'))
                                        // O DOBRO da largura de um modal comum
                                        // (`Width::Large`, usado pelo resto do
                                        // sistema) — pedido do usuário, pra
                                        // caber os 15 campos de input em Grid
                                        // de 5 colunas (3 linhas) em vez de 4.
                                        ->modalWidth(Width::FiveExtraLarge)
                                        ->mountUsing(fn (?Schema $schema, Get $get, ?Projeto $record) => static::preencherFormularioMobilizacaoFrete($schema, $get, $record, null))
                                        ->form(static::camposFormularioMobilizacaoFrete())
                                        ->action(function (array $data, Get $get, ?Projeto $record, $livewire): void {
                                            static::salvarMobilizacaoFrete($data, $get, $record, $livewire);
                                        }),
                                ])
                                    ->verticallyAlignEnd()
                                    ->columnSpan(6),
                            ]),

                        // Cabeçalho de colunas estilo planilha (ver aba "00"
                        // do Excel de referência da F.A. Marcenaria) — SEMPRE
                        // visível (2026-09-06: desde a migração de Item
                        // Avulso pra Form Modal, este Grid deixou de ser o
                        // cabeçalho de uma linha de INPUT inline condicional
                        // e passou a ser só o cabeçalho FIXO da tabela de
                        // itens já inseridos, listados logo abaixo — ver
                        // CLAUDE.md, "Item Avulso: migração de linha inline
                        // pra Form Modal"). Última coluna (1) fica sem
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
                        // `Flex::make([Text::make(...), Icon::make(...)])`).
                        // `Icon` (não `Text::make()->icon()`) porque
                        // `Text::toEmbeddedHtml()` só desenha o ícone no modo
                        // `->badge()` (pill com fundo/borda, indesejado
                        // aqui) — no modo normal (usado por todo o
                        // cabeçalho) o ícone informado via `->icon()` é
                        // simplesmente ignorado no render, confirmado lendo
                        // o Blade do componente. `Flex` porque é um
                        // Component de verdade (aceita `->columnSpan()` do
                        // Grid pai, via `CanSpanColumns` herdado de
                        // `Component`) — `->dense()` (gap-3, já compilado no
                        // CSS do Filament) no lugar do gap-6 default: um
                        // `class` Tailwind arbitrário via
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

                        // Listagem dos itens JÁ inseridos no Projeto — TODOS
                        // eles, não só os de origem Item Avulso (única
                        // origem com persistência real até agora, mas a
                        // área de listagem é a mesma pras 7, ver CLAUDE.md).
                        // `Group` (não outro `Section`) só pra agrupar as
                        // linhas dinamicamente geradas sem nenhum wrapper
                        // visual extra.
                        //
                        // Desde a migração de Item Avulso pra Form Modal
                        // (2026-09-06), o item em EDIÇÃO não precisa mais
                        // ser omitido daqui — o modal de edição flutua POR
                        // CIMA da listagem (não substitui uma linha inline
                        // como antes), então mostrar a linha normalmente
                        // por baixo do modal aberto não duplica/conflita
                        // com nada.
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
                            ->schema(function ($livewire): array {
                                if (! $livewire instanceof EditProjeto) {
                                    return [];
                                }

                                return $livewire->itensCarregados
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
            'item_avulso'       => __('comercial::filament/resources/projeto.form.itens.origens.item-avulso'),
            'item_linha'        => __('comercial::filament/resources/projeto.form.itens.origens.item-linha'),
            'promob'            => __('comercial::filament/resources/projeto.form.itens.origens.promob'),
            'sketchup'          => __('comercial::filament/resources/projeto.form.itens.origens.sketchup'),
            'mobilizacao_frete' => __('comercial::filament/resources/projeto.form.itens.origens.mobilizacao-frete'),
        ];
    }

    /**
     * Lê cada arquivo enviado no modal de "Promob" (caminhos já
     * armazenados pelo `FileUpload::make('arquivos_xml')`, disco
     * `local`), valida se todos pertencem ao PROJETO ATUAL (Parte 1 —
     * ver `PromobChecagemTotal::validarNomesDeArquivos()`) e, se sim,
     * roda `PromobChecagemTotal::checar()`. Função PURA (sem
     * `$livewire`) — devolve o resultado em vez de gravar direto,
     * porque tanto "Checar Total" quanto "Criar Itens" (Action
     * `criarItensPromob`, ver abaixo) precisam rodar exatamente essa
     * mesma checagem.
     *
     * **Decisão: rejeita o LOTE INTEIRO se qualquer arquivo for
     * inválido** (de outro Projeto ou nome fora do padrão), em vez de
     * descartar só os arquivos problemáticos e seguir com os válidos —
     * ver CLAUDE.md, "Fluxo Promob", pela justificativa completa.
     *
     * Sem `$record`/`numero_projeto` (Projeto ainda não salvo — só
     * possível em `CreateProjeto`, já que Promob não exige o mesmo
     * "salve primeiro" de Item Avulso pra ABRIR o modal, só pra
     * processar de fato, já que precisa de um `numero_projeto` real
     * pra validar contra), bloqueia com erro — sem como validar contra
     * um Projeto que não tem número ainda.
     *
     * @param  array<int, \Illuminate\Http\UploadedFile>  $arquivos
     * @return array<string, mixed>
     */
    protected static function calcularResultadoPromob(array $arquivos, ?Projeto $record): array
    {
        if (! $record || blank($record->numero_projeto)) {
            return ['erro' => __('comercial::filament/resources/projeto.form.itens.promob.erros.projeto-nao-salvo')];
        }

        $nomesDeArquivos = array_map(fn ($arquivo) => $arquivo->getClientOriginalName(), $arquivos);
        $errosValidacao = PromobChecagemTotal::validarNomesDeArquivos($nomesDeArquivos, $record->numero_projeto);

        if (filled($errosValidacao)) {
            return ['erro' => implode("\n", $errosValidacao)];
        }

        $xmlsPorNomeDeArquivo = [];

        foreach ($arquivos as $arquivo) {
            $xmlsPorNomeDeArquivo[$arquivo->getClientOriginalName()] = $arquivo->get();
        }

        try {
            return PromobChecagemTotal::checar($xmlsPorNomeDeArquivo);
        } catch (\Throwable $e) {
            return ['erro' => $e->getMessage()];
        }
    }

    /**
     * Lê os arquivos ATUALMENTE selecionados no `FileUpload::make(
     * 'arquivos_xml')` DIRETO de `$livewire->mountedActions` — achado
     * real (2026-09-05): `Get $get` injetado nas Actions
     * `checarTotalPromob`/`criarItensPromob`
     * (`extraModalFooterActions()` de `inserirItemPromob`) resolve pro
     * schema da PÁGINA (`data.*`), não pro schema PRÓPRIO da Action mãe
     * montada (`mountedActions.{n}.data.*`, onde `arquivos_xml` de fato
     * mora) — `$get('arquivos_xml')` sempre voltava `null`, mesmo com
     * arquivos já enviados. `extraModalFooterActions()` chama
     * `prepareModalAction()` em cada action extra
     * (`schemaContainer($this->getSchemaContainer())`), mas o
     * `getSchemaContainer()` da Action MÃE (`inserirItemPromob`) aponta
     * pro container de onde ELA está declarada (`Actions::make([...])`
     * dentro do form da PÁGINA), não pro schema dedicado que
     * `getMountedActionSchema()` cria com `statePath("mountedActions.
     * {n}.data")` — os dois nunca ficam conectados um ao outro por
     * baixo dos panos, apesar da aparência de que deveriam.
     * **NÃO usa um índice fixo (`0`) — achado real (2026-09-06)**: o
     * botão "Cancelar"/"X" do modal fecha via `Action::close()`, que
     * troca completamente o mecanismo de clique — `getJsClickHandler()`
     * (vendor) retorna `null` quando `shouldClose()` é `true`, então
     * `getLivewireClickHandler()` também fica `null` e NENHUM
     * `wire:click` é renderizado; o botão vira PURO Alpine
     * (`x-on:click="close()"`), só escondendo o modal no NAVEGADOR, sem
     * fazer NENHUMA requisição ao servidor. Ou seja, `unmountAction()`
     * NUNCA roda ao cancelar — o array `$livewire->mountedActions`
     * (uma property PÚBLICA do Livewire, persistida entre requisições
     * no snapshot da página) fica com uma entrada "fantasma" de
     * `inserirItemPromob` de cada abertura anterior cancelada, todas
     * empilhadas. Reabrir o modal empurra uma entrada NOVA (`mountAction()`
     * sempre dá `$this->mountedActions[] = [...]`, sem checar se já
     * existe uma com o mesmo nome) — ou seja, o índice de
     * `inserirItemPromob` cresce a cada ciclo abrir→cancelar→reabrir, e
     * um índice fixo `0` passava a apontar pra uma entrada CADA VEZ
     * MAIS ANTIGA, nunca a atual (causa raiz dos 3 bugs relatados:
     * upload/resultado "grudados" entre aberturas, "Checar Total"
     * habilitado à toa, confirmação de "Criar Itens" não disparando —
     * todos liam o `arquivos_xml` da sessão ERRADA). Correção: busca a
     * ÚLTIMA entrada cujo `name` seja `inserirItemPromob` (`array_key_last()`
     * sobre as chaves filtradas) — sempre a mais recente, mesmo com
     * `criarItensPromob` aninhando sua própria entrada por cima pra
     * exibir a confirmação. Resetar em `mountUsing()` continua correto
     * E suficiente (o pedido original de "resetar só na entrada"): o
     * problema nunca foi o reset em si, e sim ler a entrada errada
     * depois.
     *
     * **Retorna os objetos `UploadedFile` CRUS (Livewire
     * `TemporaryUploadedFile`), não caminhos de disco** — segundo
     * achado real: como nenhuma das duas Actions do rodapé
     * (`checarTotalPromob`/`criarItensPromob`) é a Action de "submit"
     * do modal (`->modalSubmitAction(false)`, ver `inserirItemPromob`),
     * o Schema do FORM nunca passa por `getState()`/dehydração — e é
     * só na dehydração que o Filament move um upload de "temporário no
     * Livewire" pra "arquivo de verdade no disco configurado"
     * (`FileUpload::saveUploadedFiles()`). Sem isso, o valor em
     * `mountedActions.0.data.arquivos_xml` fica pra sempre como
     * `TemporaryUploadedFile` (nunca vira string de caminho) — então em
     * vez de esperar por uma dehydração que nunca vai rolar aqui, lemos
     * o NOME ORIGINAL (`getClientOriginalName()`) e o CONTEÚDO
     * (`get()`) direto do objeto cru, que já funcionam plenamente sem
     * precisar de nenhum "save" — não precisamos mais nem configurar
     * `disk()`/`directory()` no campo, nem limpar nada depois (o
     * Livewire cuida da própria limpeza do seu diretório de uploads
     * temporários).
     *
     * @return array<int, \Illuminate\Http\UploadedFile>
     */
    protected static function arquivosXmlPromobAtuais($livewire): array
    {
        $indice = static::indiceMountedActionInserirItemPromob($livewire);

        if ($indice === null) {
            return [];
        }

        $arquivos = data_get($livewire->mountedActions, "{$indice}.data.arquivos_xml", []);

        if (! is_array($arquivos)) {
            return [];
        }

        return array_values(array_filter(
            $arquivos,
            fn ($arquivo) => $arquivo instanceof \Illuminate\Http\UploadedFile,
        ));
    }

    /**
     * Índice, em `$livewire->mountedActions`, da entrada MAIS RECENTE
     * chamada `inserirItemPromob` — ver docblock de
     * `arquivosXmlPromobAtuais()` pro porquê de não poder ser um
     * índice fixo. `null` se o modal nunca foi montado nesta sessão
     * (não deveria acontecer, já que só chamamos isto de dentro de
     * Actions que só existem quando ele já está montado, mas fica o
     * `null` por segurança).
     */
    private static function indiceMountedActionInserirItemPromob($livewire): ?int
    {
        $indices = array_keys(array_filter(
            $livewire->mountedActions ?? [],
            fn (array $mounted) => ($mounted['name'] ?? null) === 'inserirItemPromob',
        ));

        return $indices === [] ? null : max($indices);
    }

    /**
     * Só olha os NOMES dos arquivos atualmente selecionados no upload
     * (sem ler conteúdo — mais barato, chamado a cada render pra
     * decidir se "Checar Total"/"Criar Itens" ficam habilitados) — ver
     * `PromobChecagemTotal::possuiXmlGeralValido()`.
     *
     * @param  array<int, \Illuminate\Http\UploadedFile>  $arquivos
     */
    protected static function promobTemXmlGeralValido(array $arquivos, ?Projeto $record): bool
    {
        if (! $record || blank($record->numero_projeto) || blank($arquivos)) {
            return false;
        }

        return PromobChecagemTotal::possuiXmlGeralValido(
            array_map(fn ($arquivo) => $arquivo->getClientOriginalName(), $arquivos),
            $record->numero_projeto,
        );
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
     * Só o super usuário (mesmo critério de `NotaProjeto::
     * ehSuperUsuario()`, role `Admin`/guard `web` — ver CLAUDE.md,
     * "Notas do Projeto — regra de 24h e super usuário") pode confirmar
     * e seguir com a criação dos Itens do Promob HAVENDO divergência
     * nas 5 métricas. Instanciar `NotaProjeto` só pra chamar esse
     * método é o mesmo padrão já usado por `podeSerEditadaPor()`/
     * `podeSerExcluidaPor()` neste Resource — `ehSuperUsuario()` não
     * depende de nenhum atributo da nota em si, só do usuário
     * informado.
     */
    protected static function promobUsuarioPodeConfirmarDivergencia(): bool
    {
        $usuario = auth()->user();

        return $usuario !== null && (new NotaProjeto())->ehSuperUsuario($usuario);
    }

    /**
     * "Criar Itens" só pede confirmação quando existe um XML "000" pra
     * comparar E pelo menos uma das 5 métricas de diferença é != 0 —
     * chamado a partir de `requiresConfirmation()`/`modalHeading()`/
     * `modalDescription()` da Action `criarItensPromob` (as três
     * PRECISAM concordar entre si, ver comentário ali sobre
     * `shouldOpenModal()`).
     *
     * **Só pede confirmação pro super usuário** (`promobUsuarioPodeConfirmarDivergencia()`)
     * — o padrão esperado é diferença = 0; havendo diferença, a criação
     * é uma exceção mantida exclusivamente pro super usuário (pra não
     * travar o processo inteiro numa situação anômala). Pra qualquer
     * outro usuário havendo divergência, não faz sentido abrir um modal
     * de confirmação que ele não tem permissão de confirmar — a
     * criação é bloqueada direto em `criarTodosItensPromob()`, sem
     * modal nenhum.
     */
    protected static function promobPrecisaConfirmarCriacao($livewire, ?Projeto $record): bool
    {
        if (! static::promobUsuarioPodeConfirmarDivergencia()) {
            return false;
        }

        $resultado = static::calcularResultadoPromob(static::arquivosXmlPromobAtuais($livewire), $record);

        return isset($resultado['metricas'])
            && $resultado['metricas']['tem_geral']
            && ! static::diferencaMetricasZerada($resultado['metricas']['diferenca']);
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

    // ========================================================================
    // "Criar Itens" do Promob (2026-09-06, reformulado no mesmo dia) —
    // geração real dos Itens do Projeto a partir dos XMLs processados,
    // ver CLAUDE.md, "Fluxo Promob". `criarItensPromob` chama
    // `criarTodosItensPromob()`, que cria a Nota geral de checagem E
    // TODOS os Itens (+ suas Notas de cálculo vinculadas) de uma vez,
    // numa ÚNICA `DB::transaction()` — SEM modal por item, SEM
    // confirmação individual.
    //
    // **Decisão do usuário (2026-09-06) que substituiu a versão
    // anterior**: a primeira versão desta tarefa abria um Form Modal
    // por item (Referência/Descrição/Quantidade/% editáveis ANTES de
    // confirmar cada um, com o próximo modal se auto-abrindo depois de
    // cada "Criar"). Esse mecanismo ("montar uma Action de dentro do
    // `->action()` de outra Action do mesmo nome, repetidamente, sem
    // nunca desmontar a anterior") causou uma sequência de bugs reais
    // (índice fixo de `schemaComponent`, depois campos "atrasados" um
    // item — ver histórico completo no CLAUDE.md) sem uma causa raiz
    // clara pro segundo. O usuário preferiu simplificar: criar tudo
    // automaticamente (com a MESMA fórmula/regras já validadas) e, se
    // algum item precisar de ajuste, editar DEPOIS pelo ícone de lápis
    // já existente na listagem (`editarItemAvulso{id}`, que abre o
    // MESMO modal usado por Item Avulso — funciona pra qualquer origem,
    // já validado). Isso eliminou inteiramente a classe de bugs do
    // mecanismo antigo (nenhum modal remontado programaticamente) e boa
    // parte do código (removidas ~10 funções/uma trait de estado de
    // fila — ver diff do commit).
    // ========================================================================

    /**
     * Único passo de "Criar Itens" (chamado pelo `->action()` de
     * `criarItensPromob`, já depois de qualquer confirmação de
     * divergência necessária): cria a Nota geral de checagem
     * (`tipo_sistema = true`, `item_projeto_id = null`, nome do "000" +
     * `DATE`/`HOUR` do próprio XML + resumo das 5 métricas) e, na
     * sequência, TODOS os Itens (na ordem de
     * `PromobChecagemTotal::ordenarNomesDeArquivosDeItens()`), cada um
     * com sua `NotaProjeto` de cálculo vinculada — tudo numa ÚNICA
     * `DB::transaction()` (2026-09-06: sem mais interação do usuário no
     * meio do processo, não faz sentido mais deixar itens parciais
     * criados se algo falhar no meio — atômico, tudo ou nada).
     *
     * Cada Item usa os DEFAULTS que antes só apareciam pré-preenchidos
     * no modal removido: Descrição = texto do nome do arquivo
     * (`PromobChecagemTotal::descricaoDoArquivo()`), Quantidade = 1,
     * % = 0, Referência = nome do arquivo + data/hora do XML (agora
     * gravada na coluna `referencia` de `itens_projeto` — ver migration
     * `2026_09_06_110000` — exibida na coluna "Referência" da listagem,
     * reservada desde sempre pro "Item de Linha" mas nunca usada até
     * agora). Ajustes finos (Descrição/Quantidade/%/Custo Unitário)
     * ficam pro fluxo de edição já existente (ícone de lápis de cada
     * linha), 1 item por vez.
     *
     * **Trava: exige `referencia_preco_id` no Projeto** — a fórmula de
     * Custo Unitário (`calcularDetalhamentoCustoPromob()`) depende
     * inteiramente dos Fatores/Valores da Referência de Preços; sem
     * ela, TODO Item sairia com Custo Unitário zerado, um erro
     * silencioso e consequente (diferente do Imposto do Item Avulso,
     * que degrada pra 0% sem bloquear).
     */
    protected static function criarTodosItensPromob(?Projeto $record, $livewire): void
    {
        if (! $record) {
            Notification::make()
                ->warning()
                ->title(__('comercial::filament/resources/projeto.form.itens.notification.projeto-nao-salvo-title'))
                ->body(__('comercial::filament/resources/projeto.form.itens.notification.projeto-nao-salvo-body'))
                ->send();

            return;
        }

        if (blank($record->referencia_preco_id)) {
            Notification::make()
                ->danger()
                ->title(__('comercial::filament/resources/projeto.form.itens.promob.criar-itens.notification.sem-referencia-title'))
                ->body(__('comercial::filament/resources/projeto.form.itens.promob.criar-itens.notification.sem-referencia-body'))
                ->send();

            return;
        }

        $arquivos = static::arquivosXmlPromobAtuais($livewire);
        $resultado = static::calcularResultadoPromob($arquivos, $record);

        if (isset($resultado['erro'])) {
            Notification::make()
                ->danger()
                ->title(__('comercial::filament/resources/projeto.form.itens.promob.criar-itens.notification.erro-title'))
                ->body($resultado['erro'])
                ->send();

            return;
        }

        // Trava: havendo divergência em qualquer uma das 5 métricas,
        // só o super usuário pode confirmar e seguir com a criação
        // (`promobUsuarioPodeConfirmarDivergencia()`, mesmo critério de
        // `NotaProjeto::ehSuperUsuario()`) — pra qualquer outro
        // usuário, a criação é BLOQUEADA aqui, não apenas avisada. O
        // padrão esperado é diferença = 0; a exceção existe só pro
        // super usuário resolver casos muito atípicos sem travar o
        // processo inteiro (ver CLAUDE.md, "Fluxo Promob"). Redundante
        // com `promobPrecisaConfirmarCriacao()` (que já evita abrir o
        // modal de confirmação nesse caso pra quem não é super
        // usuário), mas repetido aqui como validação de segurança no
        // backend — nunca confiar só em esconder/pular o modal na
        // tela, mesmo padrão de `salvarEdicaoNota()`/`excluirNotaProjeto()`.
        if (isset($resultado['metricas'])
            && $resultado['metricas']['tem_geral']
            && ! static::diferencaMetricasZerada($resultado['metricas']['diferenca'])
            && ! static::promobUsuarioPodeConfirmarDivergencia()) {
            Notification::make()
                ->danger()
                ->title(__('comercial::filament/resources/projeto.form.itens.promob.criar-itens.notification.divergencia-bloqueada-title'))
                ->body(__('comercial::filament/resources/projeto.form.itens.promob.criar-itens.notification.divergencia-bloqueada-body'))
                ->send();

            return;
        }

        $conteudoPorNome = [];

        foreach ($arquivos as $arquivo) {
            $conteudoPorNome[$arquivo->getClientOriginalName()] = $arquivo->get();
        }

        $nomeGeral = null;

        foreach (array_keys($conteudoPorNome) as $nome) {
            if (PromobChecagemTotal::ehArquivoGeral($nome)) {
                $nomeGeral = $nome;

                break;
            }
        }

        // Defensivo — `criarItensPromob->disabled()` já exige um XML
        // "000" válido pra sequer habilitar o botão; não deveria chegar
        // aqui sem ele.
        if ($nomeGeral === null) {
            Notification::make()
                ->danger()
                ->title(__('comercial::filament/resources/projeto.form.itens.promob.criar-itens.notification.erro-title'))
                ->body(__('comercial::filament/resources/projeto.form.itens.promob.erros.projeto-nao-salvo'))
                ->send();

            return;
        }

        $dataHoraGeral = PromobXmlParser::dataHora($conteudoPorNome[$nomeGeral]);
        $nomesItens = PromobChecagemTotal::ordenarNomesDeArquivosDeItens(array_keys($conteudoPorNome));
        $referenciaPrecoId = $record->referencia_preco_id;

        $criados = DB::transaction(function () use ($record, $resultado, $nomeGeral, $dataHoraGeral, $nomesItens, $conteudoPorNome, $referenciaPrecoId): int {
            // Nota GERAL de checagem só quando há alguma divergência
            // (`! diferencaMetricasZerada()`) — diferença = 0 é o caso
            // COMUM/esperado e não precisa de registro; a nota existe
            // pra documentar justamente a exceção (inclusive porque,
            // havendo divergência, quem chegou até aqui necessariamente
            // é o super usuário confirmando a criação mesmo assim — ver
            // trava logo acima).
            if (! static::diferencaMetricasZerada($resultado['metricas']['diferenca'])) {
                $record->notas()->create([
                    'usuario_id'   => auth()->id(),
                    'tipo_sistema' => true,
                    'texto'        => static::renderizarResumoNotaGeralPromob($nomeGeral, $dataHoraGeral, $resultado['metricas']),
                ]);
            }

            // `lockForUpdate()` na Referência de Preços UMA VEZ pra todo
            // o lote (não por item) — mesma disciplina de concorrência
            // de sempre (nunca confiar em Fatores/Imposto já em cache),
            // mas sem sentido relê-la item a item aqui: tudo acontece
            // na MESMA transação, sem interação do usuário no meio que
            // pudesse deixar o valor ficar obsoleto entre um item e o
            // próximo.
            $referencia = ReferenciaPreco::where('id', $referenciaPrecoId)->lockForUpdate()->first();
            $impostoAplicado = (float) ($referencia->imposto ?? 0);

            // `lockForUpdate()` nas linhas já existentes deste Projeto
            // UMA VEZ, antes do loop — mesma disciplina de concorrência
            // de `salvarItemAvulso()` pro `numero_item`
            // (`ItemProjeto::boot()`), cobrindo o LOTE inteiro.
            $record->itens()->lockForUpdate()->get();

            $totalCriados = 0;

            foreach ($nomesItens as $nomeArquivo) {
                $conteudo = $conteudoPorNome[$nomeArquivo];
                $metricas = PromobXmlParser::metricas($conteudo);
                $dataHoraItem = PromobXmlParser::dataHora($conteudo);
                $detalhamento = static::calcularDetalhamentoCustoPromob($metricas, $referencia);
                $custoUnitario = round($detalhamento['custo_unitario'], 2);
                $valores = static::calcularValoresItemAvulso($custoUnitario, 1, 0, $impostoAplicado);
                $novoItem = $record->itens()->create([
                    'origem'           => OrigemItemProjeto::Promob,
                    // Referência (2026-09-06, revisado) — só um LABEL
                    // curto de origem, NÃO o nome do arquivo + data/hora
                    // (decisão original, revertida pelo usuário): essa
                    // informação detalhada já fica visível por item no
                    // ícone "Cálculos" (`renderizarResumoCalculoItemPromob()`
                    // → `NotaProjeto`), duplicar tudo aqui só encheria a
                    // coluna/base à toa. Mesmo padrão do rótulo "Item
                    // Avulso" gravado em `salvarItemAvulso()` — ver
                    // CLAUDE.md, "Fluxo Promob".
                    'referencia'       => 'Promob',
                    'descricao'        => PromobChecagemTotal::descricaoDoArquivo($nomeArquivo),
                    'quantidade'       => 1,
                    'porcentagem'      => 0,
                    'custo_unitario'   => $custoUnitario,
                    'imposto_aplicado' => $impostoAplicado,
                    'valor_unitario'   => $valores['valor_unitario'],
                    'valor_total'      => $valores['valor_total'],
                ]);

                // `$novoItem->notas()` já seta `item_projeto_id` sozinho
                // (é a FK da própria relação) — só `projeto_id` precisa
                // ser passado explicitamente aqui (não é a FK desta
                // relação).
                $novoItem->notas()->create([
                    'projeto_id'   => $record->id,
                    'usuario_id'   => auth()->id(),
                    'tipo_sistema' => true,
                    'texto'        => static::renderizarResumoCalculoItemPromob($nomeArquivo, $dataHoraItem, $detalhamento),
                ]);

                $totalCriados++;
            }

            return $totalCriados;
        });

        if ($livewire instanceof EditProjeto) {
            $livewire->recarregarItens();
        }

        Notification::make()
            ->success()
            ->title(__('comercial::filament/resources/projeto.form.itens.promob.criar-itens.notification.concluido-title'))
            ->body(trans_choice(
                'comercial::filament/resources/projeto.form.itens.promob.criar-itens.notification.concluido-body',
                $criados,
                ['count' => $criados],
            ))
            ->send();
    }

    /**
     * Calcula o detalhamento COMPLETO do Custo Unitário de um item do
     * Promob (Parte 4.2 do enunciado) — fórmula confirmada:
     *
     * ```
     * Madeira              = Tot.Custo(item) × FatorMadeira
     * FerragensMiscelanea  = Tot.Misc(item) × FatorFerragensMiscelanea
     * Laminacao            = Tot.MLinear(item) × ValorLaminacao
     * Corte                = Tot.MLinear(item) × ValorCorte
     * PecasDoItem          = Tot.Peças(item) × ValorPorPeca
     * AcabamentoCorte      = (Laminacao + Corte + PecasDoItem) × FatorAcabamentoCorte
     * MaoObraProducao      = Tot.m²(item) × ValorHoraProducao
     * MaoObraExecucao      = Tot.m²(item) × ValorHoraExecucao
     * MaoDeObra            = (MaoObraProducao + MaoObraExecucao) × FatorMaoDeObra
     * CustoUnitario        = Madeira + FerragensMiscelanea + AcabamentoCorte + MaoDeObra
     * ```
     *
     * "× Fator" é sempre uma multiplicação pura — os Fatores são
     * percentuais gravados como número cru (ex.: `200.00` = 200%),
     * então cada um entra na fórmula dividido por 100 primeiro (`×2.0`,
     * não `×200`). `?ReferenciaPreco $referencia` aceita `null` de
     * propósito (todos os Fatores/Valores caem pra `0`, resultando em
     * Custo Unitário `0`) só como salvaguarda defensiva — o CAMINHO
     * normal já bloqueia mais cedo sem Referência vinculada (ver
     * `criarTodosItensPromob()`).
     *
     * Função PURA (sem `Get`/`Set`) — mesma separação já usada por
     * `calcularValoresItemAvulso()`.
     *
     * @param  array{pecas: int, m2: float, mlinear: float, custo: float, misc: float}  $metricasItem
     * @return array<string, mixed>
     */
    protected static function calcularDetalhamentoCustoPromob(array $metricasItem, ?ReferenciaPreco $referencia): array
    {
        $fatorMadeira = (float) ($referencia->fator_madeiras ?? 0);
        $fatorFerragensMiscelania = (float) ($referencia->fator_ferragens_miscelanias ?? 0);
        $fatorAcabamentoCorte = (float) ($referencia->fator_acabamento_corte ?? 0);
        $fatorMaoObra = (float) ($referencia->fator_mao_obra ?? 0);
        $valorLaminacao = (float) ($referencia->laminacao ?? 0);
        $valorCorte = (float) ($referencia->corte ?? 0);
        $valorPorPeca = (float) ($referencia->valor_pecas ?? 0);
        $valorHoraProducao = (float) ($referencia->hora_producao ?? 0);
        $valorHoraExecucao = (float) ($referencia->hora_execucao ?? 0);

        $custoItem = $metricasItem['custo'];
        $miscItem = $metricasItem['misc'];
        $mlinearItem = $metricasItem['mlinear'];
        $pecasItem = (float) $metricasItem['pecas'];
        $m2Item = $metricasItem['m2'];

        $madeira = $custoItem * ($fatorMadeira / 100);
        $ferragensMiscelania = $miscItem * ($fatorFerragensMiscelania / 100);

        $laminacao = $mlinearItem * $valorLaminacao;
        $corte = $mlinearItem * $valorCorte;
        $pecasValor = $pecasItem * $valorPorPeca;
        $acabamentoCorteBase = $laminacao + $corte + $pecasValor;
        $acabamentoCorte = $acabamentoCorteBase * ($fatorAcabamentoCorte / 100);

        $maoObraProducao = $m2Item * $valorHoraProducao;
        $maoObraExecucao = $m2Item * $valorHoraExecucao;
        $maoObraBase = $maoObraProducao + $maoObraExecucao;
        $maoDeObra = $maoObraBase * ($fatorMaoObra / 100);

        $custoUnitario = $madeira + $ferragensMiscelania + $acabamentoCorte + $maoDeObra;

        return [
            'metricas_item'              => $metricasItem,
            'fator_madeira'              => $fatorMadeira,
            'fator_ferragens_miscelania' => $fatorFerragensMiscelania,
            'fator_acabamento_corte'     => $fatorAcabamentoCorte,
            'fator_mao_obra'             => $fatorMaoObra,
            'valor_laminacao'            => $valorLaminacao,
            'valor_corte'                => $valorCorte,
            'valor_por_peca'             => $valorPorPeca,
            'valor_hora_producao'        => $valorHoraProducao,
            'valor_hora_execucao'        => $valorHoraExecucao,
            'madeira'                    => $madeira,
            'ferragens_miscelania'       => $ferragensMiscelania,
            'laminacao'                  => $laminacao,
            'corte'                      => $corte,
            'pecas_valor'                => $pecasValor,
            'acabamento_corte_base'      => $acabamentoCorteBase,
            'acabamento_corte'           => $acabamentoCorte,
            'mao_obra_producao'          => $maoObraProducao,
            'mao_obra_execucao'          => $maoObraExecucao,
            'mao_obra_base'              => $maoObraBase,
            'mao_de_obra'                => $maoDeObra,
            'custo_unitario'             => $custoUnitario,
        ];
    }

    /**
     * Formata um valor monetário no padrão brasileiro (`R$ 1.234,56`).
     */
    private static function formatarMoedaPromob(float $valor): string
    {
        return 'R$ '.number_format($valor, 2, ',', '.');
    }

    /**
     * Formata um percentual no padrão brasileiro (`200,00%`).
     */
    private static function formatarPercentualPromob(float $valor): string
    {
        return number_format($valor, 2, ',', '.').'%';
    }

    /**
     * Tabela HTML compacta do detalhamento de Custo Unitário de UM item
     * — gravada como `texto` da `NotaProjeto` vinculada a esse item
     * (`criarTodosItensPromob()`), acessível depois pelo ícone
     * "Cálculos" da listagem. Estilo COMPACTO por
     * pedido explícito da tarefa ("Nota sobre estilo visual — sem
     * fundo colorido por linha, só bordas leves, alinhamento numérico à
     * direita") — `style=` INLINE em vez de classes Tailwind
     * (`text-xs`), pra não depender de quais utilitários o CSS
     * pré-compilado do Filament inclui (ver CLAUDE.md da raiz,
     * "FilamentAsset::register()", sobre classes arbitrárias não
     * usadas pelo próprio Filament não terem efeito nenhum) — o mesmo
     * HTML também precisa renderizar OK fora do painel admin (dentro da
     * Nota, via `Html::make()` no modal de Notas do Projeto).
     *
     * @param  array{data: string, hora: string}  $dataHora
     * @param  array<string, mixed>  $detalhamento
     */
    protected static function renderizarResumoCalculoItemPromob(string $nomeArquivo, array $dataHora, array $detalhamento): string
    {
        $m = $detalhamento['metricas_item'];
        $th = 'text-align:left;padding:2px 6px;border-bottom:1px solid #d1d5db;';
        $thDir = 'text-align:right;padding:2px 6px;border-bottom:1px solid #d1d5db;';
        $td = 'padding:2px 6px;border-bottom:1px solid #e5e7eb;';
        $tdDir = $td.'text-align:right;';
        $tdSub = 'padding:2px 6px 2px 20px;border-bottom:1px solid #e5e7eb;color:#6b7280;';
        $tdSubDir = $tdSub.'text-align:right;';

        $linha = fn (string $rotulo, string $base, string $fator, string $total, bool $sub = false) => '<tr>'
            .'<td style="'.($sub ? $tdSub : $td).'">'.e($rotulo).'</td>'
            .'<td style="'.($sub ? $tdSubDir : $tdDir).'">'.e($base).'</td>'
            .'<td style="'.($sub ? $tdSubDir : $tdDir).'">'.e($fator).'</td>'
            .'<td style="'.($sub ? $tdSubDir : $tdDir).'">'.e($total).'</td>'
            .'</tr>';

        $linhas = $linha(
            __('comercial::filament/resources/projeto.form.itens.promob.criar-itens.tabela.madeira'),
            static::formatarMoedaPromob($m['custo']),
            static::formatarPercentualPromob($detalhamento['fator_madeira']),
            static::formatarMoedaPromob($detalhamento['madeira']),
        );
        $linhas .= $linha(
            __('comercial::filament/resources/projeto.form.itens.promob.criar-itens.tabela.ferragens-miscelanea'),
            static::formatarMoedaPromob($m['misc']),
            static::formatarPercentualPromob($detalhamento['fator_ferragens_miscelania']),
            static::formatarMoedaPromob($detalhamento['ferragens_miscelania']),
        );
        $linhas .= $linha(
            __('comercial::filament/resources/projeto.form.itens.promob.criar-itens.tabela.acabamento-corte'),
            static::formatarMoedaPromob($detalhamento['acabamento_corte_base']),
            static::formatarPercentualPromob($detalhamento['fator_acabamento_corte']),
            static::formatarMoedaPromob($detalhamento['acabamento_corte']),
        );
        $linhas .= $linha(
            __('comercial::filament/resources/projeto.form.itens.promob.criar-itens.tabela.laminacao'),
            number_format($m['mlinear'], 2, ',', '.').' m',
            static::formatarMoedaPromob($detalhamento['valor_laminacao']).'/m',
            static::formatarMoedaPromob($detalhamento['laminacao']),
            sub: true,
        );
        $linhas .= $linha(
            __('comercial::filament/resources/projeto.form.itens.promob.criar-itens.tabela.corte'),
            number_format($m['mlinear'], 2, ',', '.').' m',
            static::formatarMoedaPromob($detalhamento['valor_corte']).'/m',
            static::formatarMoedaPromob($detalhamento['corte']),
            sub: true,
        );
        $linhas .= $linha(
            __('comercial::filament/resources/projeto.form.itens.promob.criar-itens.tabela.pecas'),
            number_format($m['pecas'], 0, ',', '.'),
            static::formatarMoedaPromob($detalhamento['valor_por_peca']).'/pç',
            static::formatarMoedaPromob($detalhamento['pecas_valor']),
            sub: true,
        );
        $linhas .= $linha(
            __('comercial::filament/resources/projeto.form.itens.promob.criar-itens.tabela.mao-de-obra'),
            static::formatarMoedaPromob($detalhamento['mao_obra_base']),
            static::formatarPercentualPromob($detalhamento['fator_mao_obra']),
            static::formatarMoedaPromob($detalhamento['mao_de_obra']),
        );
        $linhas .= $linha(
            __('comercial::filament/resources/projeto.form.itens.promob.criar-itens.tabela.producao'),
            number_format($m['m2'], 2, ',', '.').' m²',
            static::formatarMoedaPromob($detalhamento['valor_hora_producao']).'/m²',
            static::formatarMoedaPromob($detalhamento['mao_obra_producao']),
            sub: true,
        );
        $linhas .= $linha(
            __('comercial::filament/resources/projeto.form.itens.promob.criar-itens.tabela.execucao'),
            number_format($m['m2'], 2, ',', '.').' m²',
            static::formatarMoedaPromob($detalhamento['valor_hora_execucao']).'/m²',
            static::formatarMoedaPromob($detalhamento['mao_obra_execucao']),
            sub: true,
        );

        $totalTd = 'padding:4px 6px;border-top:1px solid #9ca3af;font-weight:600;';

        return '<p style="margin:0 0 4px;font-size:11px;color:#6b7280;">'.e("{$nomeArquivo} — {$dataHora['data']} {$dataHora['hora']}").'</p>'
            .'<table style="width:100%;border-collapse:collapse;font-size:11px;">'
            .'<thead><tr>'
            .'<th style="'.$th.'">'.e(__('comercial::filament/resources/projeto.form.itens.promob.criar-itens.tabela.categoria')).'</th>'
            .'<th style="'.$thDir.'">'.e(__('comercial::filament/resources/projeto.form.itens.promob.criar-itens.tabela.base')).'</th>'
            .'<th style="'.$thDir.'">'.e(__('comercial::filament/resources/projeto.form.itens.promob.criar-itens.tabela.fator')).'</th>'
            .'<th style="'.$thDir.'">'.e(__('comercial::filament/resources/projeto.form.itens.promob.criar-itens.tabela.total')).'</th>'
            .'</tr></thead><tbody>'
            .$linhas
            .'<tr><td colspan="3" style="'.$totalTd.'">'.e(__('comercial::filament/resources/projeto.form.itens.promob.criar-itens.tabela.custo-unitario')).'</td>'
            .'<td style="'.$totalTd.'text-align:right;">'.e(static::formatarMoedaPromob($detalhamento['custo_unitario'])).'</td></tr>'
            .'</tbody></table>';
    }

    /**
     * Tabela HTML compacta da Nota geral de checagem (Parte 3 do
     * enunciado) — MESMO estilo visual de `renderizarResumoCalculoItemPromob()`.
     *
     * @param  array{data: string, hora: string}  $dataHora
     * @param  array{tem_geral: bool, quantidade_parciais: int, parciais: array<string, int|float>, geral: array<string, int|float>|null, diferenca: array<string, int|float>|null}  $metricas
     */
    protected static function renderizarResumoNotaGeralPromob(string $nomeArquivoGeral, array $dataHora, array $metricas): string
    {
        $th = 'text-align:left;padding:2px 6px;border-bottom:1px solid #d1d5db;';
        $thDir = 'text-align:right;padding:2px 6px;border-bottom:1px solid #d1d5db;';
        $td = 'padding:2px 6px;border-bottom:1px solid #e5e7eb;';
        $tdDir = $td.'text-align:right;';

        $formatar = fn (string $chave, $valor) => $chave === 'pecas'
            ? number_format((float) $valor, 0, ',', '.')
            : number_format((float) $valor, 2, ',', '.');

        $rotulos = [
            'pecas'   => __('comercial::filament/resources/projeto.form.itens.promob.criar-itens.tabela.metrica-pecas'),
            'm2'      => __('comercial::filament/resources/projeto.form.itens.promob.criar-itens.tabela.metrica-m2'),
            'mlinear' => __('comercial::filament/resources/projeto.form.itens.promob.criar-itens.tabela.metrica-mlinear'),
            'custo'   => __('comercial::filament/resources/projeto.form.itens.promob.criar-itens.tabela.metrica-custo'),
            'misc'    => __('comercial::filament/resources/projeto.form.itens.promob.criar-itens.tabela.metrica-misc'),
        ];

        $linhas = '';

        foreach ($rotulos as $chave => $rotulo) {
            $linhas .= '<tr>'
                .'<td style="'.$td.'">'.e($rotulo).'</td>'
                .'<td style="'.$tdDir.'">'.e($metricas['tem_geral'] ? $formatar($chave, $metricas['geral'][$chave]) : '—').'</td>'
                .'<td style="'.$tdDir.'">'.e($formatar($chave, $metricas['parciais'][$chave])).'</td>'
                .'<td style="'.$tdDir.'">'.e($metricas['tem_geral'] ? $formatar($chave, $metricas['diferenca'][$chave]) : '—').'</td>'
                .'</tr>';
        }

        return '<p style="margin:0 0 4px;font-size:11px;color:#6b7280;">'.e("{$nomeArquivoGeral} — {$dataHora['data']} {$dataHora['hora']}").'</p>'
            .'<table style="width:100%;border-collapse:collapse;font-size:11px;">'
            .'<thead><tr>'
            .'<th style="'.$th.'">'.e(__('comercial::filament/resources/projeto.form.itens.promob.criar-itens.tabela.metrica')).'</th>'
            .'<th style="'.$thDir.'">'.e(__('comercial::filament/resources/projeto.form.itens.promob.criar-itens.tabela.coluna-geral')).'</th>'
            .'<th style="'.$thDir.'">'.e(__('comercial::filament/resources/projeto.form.itens.promob.criar-itens.tabela.coluna-soma-itens', ['quantidade' => $metricas['quantidade_parciais']])).'</th>'
            .'<th style="'.$thDir.'">'.e(__('comercial::filament/resources/projeto.form.itens.promob.criar-itens.tabela.coluna-diferenca')).'</th>'
            .'</tr></thead><tbody>'
            .$linhas
            .'</tbody></table>';
    }

    /**
     * Valor Unitário = Custo Unitário × (1 + Porc.%/100) × (1 + Imp.%/100)
     * Valor Total    = Valor Unitário × Quantidade
     *
     * Função PURA (sem `Get`/`Set`, sem tocar em Referência de Preços) —
     * usada tanto pela prévia reativa em tela (`recalcularValoresItemAvulso()`,
     * que lê o Imp.% já em cache no campo `imposto` do Form Modal, só
     * pra exibição enquanto o usuário digita) quanto pela gravação de
     * verdade (`salvarItemAvulso()`, que busca o Imp.% FRESCO do banco
     * antes de chamar esta função — ver essa subseção pro motivo,
     * achado real de concorrência em
     * `INVESTIGACAO-TRANSACOES-CONCORRENCIA.md`).
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
     * Campos do FORM MODAL de "Item Avulso" — reaproveitado pelas DUAS
     * Actions que abrem esse modal (`inserirItemAvulso`, criação, e
     * `editarItemAvulso{id}` de cada linha da listagem, edição), pra
     * não duplicar a definição dos campos entre as duas. Nomes de campo
     * SEM o prefixo `novo_item_*` da versão anterior (linha inline) —
     * não faz mais sentido, já que agora vivem DENTRO do Schema PRÓPRIO
     * de cada Action (`mountedActions.{n}.data`), nunca mais
     * compartilhado com o resto da Section "Itens" (ver
     * `preencherFormularioItemAvulso()`/`salvarItemAvulso()` sobre como
     * isso se conecta ao restante do formulário do Cabeçalho).
     *
     * **Validação NATIVA do Schema (`->required()`/`->rules(['gt:0'])`),
     * não mais `ValidationException::withMessages()` manual** — achado
     * que só passou a valer com a migração pra modal: a razão original
     * pra validação manual era que os campos `novo_item_*` (nomes da
     * versão anterior, linha inline) viviam no MESMO Schema da Section "Itens"
     * inteira, e um `->required()` ali faria o Salvar/Cancelar do
     * CABEÇALHO exigir esses campos também mesmo sem o usuário estar
     * inserindo/editando um item. Isso não existe mais: cada Action com
     * `->form()` tem seu PRÓPRIO Schema dedicado (mesmo mecanismo do
     * Promob, `getMountedActionSchema()`), então `->required()` aqui só
     * afeta a VALIDAÇÃO DESTE MODAL, nunca o formulário do Cabeçalho. A
     * única regra que a validação nativa não cobre sozinha é "RichEditor
     * não pode ser só HTML vazio tipo `<p></p>`" (`->required()` trata
     * isso como PREENCHIDO, já que a string não é vazia) — por isso o
     * `->rule()` customizado na Descrição, com a MESMA checagem
     * (`blank(trim(strip_tags($valor)))`) que a validação manual
     * antiga já fazia.
     *
     * @return array<int, \Filament\Schemas\Components\Component>
     */
    /**
     * Extrai o texto puro de um valor de `RichEditor` pra checar se está
     * "visualmente vazio" — usada pela regra de validação de `descricao`
     * em `camposFormularioItemAvulso()`.
     *
     * **Achado real**: o `->rule()` de um componente do Schema roda por
     * cima do ESTADO BRUTO do Livewire (`Filament\Schemas\Concerns\
     * CanBeValidated::validate()` chama `$livewire->validate($rules,
     * ...)` direto, sem passar pelo `getState()`/dehydrate do
     * componente) — pra um `RichEditor`, esse estado bruto NUNCA é a
     * string HTML final, é sempre o documento TipTap em ARRAY
     * (`RichEditorStateCast::set()`, chamado ao hidratar/preencher o
     * campo, sempre devolve `$editor->getDocument()`; só `get()`
     * — chamado na DESIDRATAÇÃO, depois da validação passar — devolve a
     * string HTML). Um `(string) $value` direto nesse ponto lança
     * "Array to string conversion" (convertido pelo handler de erros do
     * Laravel em `ErrorException`, 500 sem nenhuma mensagem de validação
     * visível — é exatamente esse bug que esta função corrige). Recebe
     * `mixed` de propósito (cobre os dois formatos possíveis) em vez de
     * assumir sempre array.
     */
    protected static function textoPlanoRichEditor(mixed $value): string
    {
        if (is_string($value)) {
            return trim(strip_tags($value));
        }

        if (! is_array($value)) {
            return '';
        }

        $texto = '';

        $percorrer = function (array $node) use (&$percorrer, &$texto): void {
            if (is_string($node['text'] ?? null)) {
                $texto .= $node['text'];
            }

            foreach ($node['content'] ?? [] as $filho) {
                if (is_array($filho)) {
                    $percorrer($filho);
                }
            }
        };

        $percorrer($value);

        return trim($texto);
    }

    protected static function camposFormularioItemAvulso(): array
    {
        return [
            Placeholder::make('numero_item_preview')
                ->label(__('comercial::filament/resources/projeto.form.itens.item-avulso-modal.item-label'))
                ->content(function (Get $get, ?Projeto $record): string {
                    $itemId = $get('item_id');

                    if (filled($itemId)) {
                        $numero = $record?->itens()->find($itemId)?->numero_item;

                        if (filled($numero)) {
                            return $numero;
                        }
                    }

                    $ultimoNumero = (int) ($record?->itens()->max('numero_item') ?? 0);

                    return str_pad((string) ($ultimoNumero + 1), 3, '0', STR_PAD_LEFT);
                }),

            // Controle interno — qual `ItemProjeto` está sendo editado
            // (`null` = criação de um item novo). Preenchido só por
            // `preencherFormularioItemAvulso()` (`mountUsing()` das duas
            // Actions), nunca editável pelo usuário.
            Hidden::make('item_id'),

            // Controle interno (2026-09-06) — `OrigemItemProjeto` do item
            // sendo editado (`null` em modo criação, sempre vira Item
            // Avulso nesse caso). Só existe pra decidir, em tela, se
            // "Custo Unitário" abaixo fica bloqueado — ver esse campo e
            // `salvarItemAvulso()`, que também usa a origem real do
            // registro (não este valor, que é só CACHE de exibição) pra
            // decidir se persiste um novo Custo Unitário ou preserva o já
            // gravado.
            Hidden::make('origem_atual')
                ->dehydrated(false),

            RichEditor::make('descricao')
                ->label(__('comercial::filament/resources/projeto.form.itens.item-avulso-modal.descricao-label'))
                ->required()
                ->rule(fn () => function (string $attribute, $value, \Closure $fail): void {
                    if (blank(static::textoPlanoRichEditor($value))) {
                        $fail(__('comercial::filament/resources/projeto.form.itens.validacao.descricao-obrigatoria'));
                    }
                })
                ->validationMessages([
                    'required' => __('comercial::filament/resources/projeto.form.itens.validacao.descricao-obrigatoria'),
                ])
                // Toolbar REABILITADA (2026-09-06) — dentro do Form Modal
                // sobra espaço de sobra (diferente da linha de input
                // inline de antes da migração pro modal, ver CLAUDE.md,
                // "Toolbar do RichEditor de 'Item Avulso' reabilitada"),
                // então voltou a fazer sentido um botão visual em vez de
                // só atalho de teclado. SEM `->toolbarButtons()` custom
                // (2026-09-06) — usa o conjunto DEFAULT completo do
                // Filament (`RichEditor::getDefaultToolbarButtons()`) de
                // propósito: decisão explícita do usuário de começar com
                // tudo disponível e só reduzir depois, com uso real,
                // caso algum botão se mostre desnecessário pra uma
                // descrição de item.
                ->columnSpanFull(),

            Grid::make(3)
                ->schema([
                    TextInput::make('quantidade')
                        ->label(__('comercial::filament/resources/projeto.form.itens.item-avulso-modal.quantidade-label'))
                        ->numeric()
                        ->integer()
                        ->required()
                        ->rules(['gt:0'])
                        ->validationMessages([
                            'required' => __('comercial::filament/resources/projeto.form.itens.validacao.quantidade-obrigatoria'),
                            'gt'       => __('comercial::filament/resources/projeto.form.itens.validacao.quantidade-obrigatoria'),
                        ])
                        ->live(onBlur: true)
                        ->extraInputAttributes(['class' => 'fi-input-no-spinner'])
                        ->afterStateUpdated(fn (Get $get, Set $set) => static::recalcularValoresItemAvulso($get, $set)),

                    TextInput::make('porcentagem')
                        ->label(__('comercial::filament/resources/projeto.form.itens.item-avulso-modal.porcentagem-label'))
                        ->helperText(__('comercial::filament/resources/projeto.form.itens.porcentagem-tooltip'))
                        ->numeric()
                        ->integer()
                        ->live(onBlur: true)
                        ->extraInputAttributes(['class' => 'fi-input-no-spinner'])
                        ->afterStateUpdated(fn (Get $get, Set $set) => static::recalcularValoresItemAvulso($get, $set)),

                    TextInput::make('custo_unitario')
                        ->label(__('comercial::filament/resources/projeto.form.itens.item-avulso-modal.custo-unitario-label'))
                        ->helperText(__('comercial::filament/resources/projeto.form.itens.custo-unitario-tooltip'))
                        ->numeric()
                        ->minValue(0)
                        ->required()
                        ->rules(['gt:0'])
                        ->validationMessages([
                            'required' => __('comercial::filament/resources/projeto.form.itens.validacao.custo-unitario-obrigatorio'),
                            'gt'       => __('comercial::filament/resources/projeto.form.itens.validacao.custo-unitario-obrigatorio'),
                        ])
                        ->prefix('R$')
                        ->live(onBlur: true)
                        ->extraInputAttributes(['class' => 'fi-input-no-spinner'])
                        ->afterStateUpdated(fn (Get $get, Set $set) => static::recalcularValoresItemAvulso($get, $set))
                        // Bloqueado pra edição de item de origem Promob
                        // (2026-09-06) — o Custo Unitário desses itens é
                        // CALCULADO pela importação do XML
                        // (`calcularDetalhamentoCustoPromob()`), não um
                        // valor livre digitado pelo usuário; permitir
                        // editar aqui destruiria essa rastreabilidade sem
                        // reprocessar o XML original. `->dehydrated(false)`
                        // junto pra nem chegar em `$data` quando bloqueado —
                        // `salvarItemAvulso()` então preserva o valor já
                        // gravado no registro (dupla proteção, já que o
                        // `disabled()` sozinho pode ser burlado no DOM).
                        ->disabled(fn (Get $get): bool => $get('origem_atual') === OrigemItemProjeto::Promob->value)
                        ->dehydrated(fn (Get $get): bool => $get('origem_atual') !== OrigemItemProjeto::Promob->value),
                ]),

            Grid::make(2)
                ->schema([
                    TextInput::make('valor_unitario')
                        ->label(__('comercial::filament/resources/projeto.form.itens.item-avulso-modal.valor-unitario-label'))
                        ->numeric()
                        ->prefix('R$')
                        ->disabled()
                        ->dehydrated(false),

                    TextInput::make('valor_total')
                        ->label(__('comercial::filament/resources/projeto.form.itens.item-avulso-modal.valor-total-label'))
                        ->numeric()
                        ->prefix('R$')
                        ->disabled()
                        ->dehydrated(false),
                ]),

            // Imp.% da Referência de Preços ATUALMENTE selecionada no
            // Cabeçalho — cache só pra PRÉVIA em tela
            // (`recalcularValoresItemAvulso()`); a gravação de verdade
            // (`salvarItemAvulso()`) sempre busca o valor FRESCO do
            // banco no momento do clique em Criar/Salvar (ver essa
            // função pro motivo, achado de concorrência já corrigido
            // antes da migração pra modal).
            Hidden::make('imposto')
                ->dehydrated(false),
        ];
    }

    /**
     * Preenche o Form Modal de Item Avulso — chamado por
     * `->mountUsing()` das DUAS Actions que abrem esse modal
     * (`inserirItemAvulso`/`editarItemAvulso{id}`), SEMPRE, toda vez
     * que o modal é aberto. Sem `$itemId` (criação): tudo em branco,
     * só o Imp.% pré-buscado. Com `$itemId` (edição): preenchido com os
     * dados atuais do item, recalculando Valor Unitário/Total a partir
     * do Imposto ATUAL da Referência de Preços do Cabeçalho (não o
     * Imp.% que foi usado quando o item foi originalmente gravado — a
     * regra de "recalcular normalmente" ao entrar em edição já valia
     * antes da migração pra modal, continua igual).
     *
     * **Resetar SEMPRE ao abrir, nunca confiar em resetar ao fechar** —
     * lição aprendida corrigindo o bug de estado do modal do Promob
     * (ver CLAUDE.md, "Fluxo Promob": o botão Cancelar de um modal
     * fecha via Alpine puro, sem nenhuma requisição ao servidor, então
     * qualquer reset colocado num caminho de "fechar" nunca rodaria de
     * verdade). Aqui nem é preciso se preocupar com isso na prática:
     * `mountUsing()` roda incondicionalmente a cada `mountAction()`
     * (toda vez que o modal é aberto, criação OU edição), e como este
     * modal usa a Action de SUBMIT normal do Filament (diferente do
     * Promob), cada abertura já ganha um Schema NOVO — mas preencher
     * tudo explicitamente aqui (em vez de confiar em algum estado
     * anterior) deixa o comportamento óbvio e à prova de qualquer
     * mudança futura na forma como o modal é montado.
     */
    protected static function preencherFormularioItemAvulso(?Schema $schema, Get $get, ?Projeto $record, ?string $itemId): void
    {
        $referenciaPrecoId = $get('referencia_preco_id');
        $imposto = filled($referenciaPrecoId)
            ? (float) (ReferenciaPreco::find($referenciaPrecoId)?->imposto ?? 0)
            : 0.0;

        $item = filled($itemId) ? $record?->itens()->find($itemId) : null;

        $quantidade = $item?->quantidade;
        $porcentagem = $item?->porcentagem;
        $custoUnitario = $item?->custo_unitario;

        $valores = (filled($quantidade) && ((float) $quantidade > 0) && filled($custoUnitario) && ((float) $custoUnitario > 0))
            ? static::calcularValoresItemAvulso((float) $custoUnitario, (float) $quantidade, (float) ($porcentagem ?? 0), $imposto)
            : ['valor_unitario' => null, 'valor_total' => null];

        $schema?->fill([
            'item_id'        => $itemId,
            'origem_atual'   => $item?->origem?->value,
            'descricao'      => $item?->descricao,
            'quantidade'     => $quantidade,
            'porcentagem'    => $porcentagem,
            'custo_unitario' => $custoUnitario,
            'imposto'        => $imposto,
            'valor_unitario' => $valores['valor_unitario'],
            'valor_total'    => $valores['valor_total'],
        ]);
    }

    /**
     * Recalcula a PRÉVIA em tela a cada tecla em Quantidade/%/Custo
     * Unitário — usa o Imp.% já em cache (`imposto`, carregado uma vez
     * ao abrir o modal, ver `preencherFormularioItemAvulso()`). Essa
     * prévia PODE ficar obsoleta se a Referência de Preços mudar
     * enquanto o usuário preenche o modal — sem problema aqui, é só
     * exibição; a gravação de verdade (`salvarItemAvulso()`) sempre
     * busca o valor FRESCO do banco antes de persistir, independente
     * do que esta prévia mostrou.
     *
     * Sem Quantidade OU Custo Unitário (vazios/zerados), os dois campos
     * calculados ficam em branco — não há erro, só nada pra calcular
     * ainda. Imp.% sem Referência de Preços vinculada ao Projeto entra
     * como 0% (ver campo `imposto` acima).
     */
    protected static function recalcularValoresItemAvulso(Get $get, Set $set): void
    {
        $quantidade = $get('quantidade');
        $custoUnitario = $get('custo_unitario');

        if (blank($quantidade) || ((float) $quantidade <= 0) || blank($custoUnitario) || ((float) $custoUnitario <= 0)) {
            $set('valor_unitario', null);
            $set('valor_total', null);

            return;
        }

        $porcentagem = (float) ($get('porcentagem') ?: 0);
        $imposto = (float) ($get('imposto') ?: 0);

        $valores = static::calcularValoresItemAvulso((float) $custoUnitario, (float) $quantidade, $porcentagem, $imposto);

        $set('valor_unitario', $valores['valor_unitario']);
        $set('valor_total', $valores['valor_total']);
    }

    /**
     * Valida (nativamente pelo Schema, ver `camposFormularioItemAvulso()`)
     * e persiste o Form Modal de Item Avulso — chamada pelo `->action()`
     * das DUAS Actions que abrem esse modal, tanto em modo INSERÇÃO
     * (`item_id` vazio, cria um `ItemProjeto` novo) quanto em modo
     * EDIÇÃO (preenchido, `update()` só se algo mudou — ver
     * `itemAvulsoMudou()`).
     *
     * Sem `$record` (página de CRIAÇÃO do Projeto, ainda sem salvar):
     * bloqueia com notificação — `itens_projeto.projeto_id` exige um
     * Projeto já existente, mesmo critério já usado pelo botão "Atribuir
     * Processos" (só em `EditProjeto`, ver CLAUDE.md).
     *
     * **Imposto obsoleto — corrigido antes da migração pra modal, ver
     * `INVESTIGACAO-TRANSACOES-CONCORRENCIA.md`, continua valendo
     * igual**: `imposto` (lido uma vez ao abrir o modal) fica em CACHE
     * só pra prévia em tela — este método NÃO usa esse valor pra
     * gravar, busca o `imposto` FRESCO do banco
     * (`ReferenciaPreco::lockForUpdate()`) NO MOMENTO exato do clique em
     * "Criar"/"Salvar", dentro da MESMA `DB::transaction()` da
     * gravação, e `lockForUpdate()` na Referência de Preços trava
     * qualquer alteração concorrente dela até esta transação terminar.
     * `imposto_aplicado` grava esse valor no próprio `ItemProjeto`,
     * preservando o histórico do cálculo mesmo que a Referência de
     * Preços mude depois.
     *
     * `$livewire->recarregarItens()` no final — a listagem de itens já
     * inseridos lê uma property hidratada no `mount()` da página, não
     * `$record->itens()` reconsultado a cada render; sem chamar isso
     * aqui, o item recém-criado/editado só apareceria na listagem
     * depois de um reload completo da página.
     *
     * @param  array<string, mixed>  $data
     */
    protected static function salvarItemAvulso(array $data, Get $get, ?Projeto $record, $livewire): void
    {
        if (! $record) {
            Notification::make()
                ->warning()
                ->title(__('comercial::filament/resources/projeto.form.itens.notification.projeto-nao-salvo-title'))
                ->body(__('comercial::filament/resources/projeto.form.itens.notification.projeto-nao-salvo-body'))
                ->send();

            return;
        }

        $itemId = $data['item_id'] ?? null;
        $descricao = (string) $data['descricao'];
        $quantidade = $data['quantidade'];
        $custoUnitarioDigitado = $data['custo_unitario'] ?? null;
        $porcentagem = (float) ($data['porcentagem'] ?? 0);
        $referenciaPrecoId = $get('referencia_preco_id');

        DB::transaction(function () use ($record, $descricao, $quantidade, $custoUnitarioDigitado, $porcentagem, $referenciaPrecoId, $itemId): void {
            $item = filled($itemId) ? $record->itens()->lockForUpdate()->find($itemId) : null;

            // Custo Unitário — pra item de origem Promob o campo chega
            // BLOQUEADO no form (ver `camposFormularioItemAvulso()`,
            // `->disabled()`/`->dehydrated(false)` condicionados a
            // `origem_atual`), então nem entra em `$data`; preserva o
            // valor já gravado (calculado pela importação) em vez de
            // tentar ler um `$data['custo_unitario']` inexistente. Item
            // Avulso (ou criação de item novo) usa normalmente o valor
            // digitado no form.
            $ehOrigemPromob = $item?->origem === OrigemItemProjeto::Promob;
            $custoUnitario = $ehOrigemPromob ? (float) $item->custo_unitario : (float) $custoUnitarioDigitado;

            $impostoAplicado = filled($referenciaPrecoId)
                ? (float) (ReferenciaPreco::where('id', $referenciaPrecoId)->lockForUpdate()->value('imposto') ?? 0)
                : 0.0;

            $valores = static::calcularValoresItemAvulso($custoUnitario, (float) $quantidade, $porcentagem, $impostoAplicado);

            $dados = [
                'descricao'        => $descricao,
                'quantidade'       => (int) $quantidade,
                'porcentagem'      => $porcentagem,
                'custo_unitario'   => $custoUnitario,
                'imposto_aplicado' => $impostoAplicado,
                'valor_unitario'   => $valores['valor_unitario'],
                'valor_total'      => $valores['valor_total'],
            ];

            if ($item) {
                // `origem`/`referencia` NUNCA são reescritos aqui em modo
                // EDIÇÃO (2026-09-06, achado real) — este mesmo form/Action
                // é reaproveitado pra editar item de QUALQUER origem
                // (`editarItemAvulso{id}`, apesar do nome, ver
                // `linhaExibicaoItem()`), e antes desta correção `origem`
                // vinha hardcoded como `ItemAvulso` em TODO `update()`,
                // convertendo silenciosamente um item Promob editado em
                // Item Avulso (e junto, perdendo o rótulo "Promob" da
                // coluna Referência). Os dois campos ficam como já estavam
                // gravados no registro.
                if (static::itemAvulsoMudou($item, $dados)) {
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
                $dados['origem'] = OrigemItemProjeto::ItemAvulso;
                // Referência (2026-09-06) — só um LABEL curto de origem
                // ("Item Avulso"), não dado variável nenhum: a
                // rastreabilidade detalhada de um Item Avulso é a própria
                // Descrição preenchida pelo usuário, não precisa duplicar
                // nada aqui. Mesmo padrão do rótulo "Promob" gravado em
                // `criarTodosItensPromob()` — ver CLAUDE.md, "Fluxo
                // Promob".
                $dados['referencia'] = 'Item Avulso';
                $record->itens()->create($dados);
            }
        });

        if ($livewire instanceof EditProjeto) {
            $livewire->recarregarItens();
        }

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
     * que `$dados` vem do FORM MODAL (tipos soltos de input) e o Model
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
     * Campos do FORM MODAL de "Mobilização e Frete" — reaproveitado
     * pelas DUAS Actions que abrem esse modal (`inserirMobilizacaoFrete`,
     * criação, e `editarMobilizacaoFrete{id}` de cada linha da listagem,
     * edição), mesmo padrão de `camposFormularioItemAvulso()`.
     *
     * Ordem: Descrição → campos imputados (`fretes_mobilizacao`, ver
     * migration) → totalizações calculadas ao vivo (só exibição, NÃO
     * persistidas — decisão do usuário) → Quantidade/Acréscimo/Custo
     * Unitário (este último DESABILITADO, alimentado pelo
     * `total_geral` calculado acima) → Valor Unitário/Valor Total,
     * calculados pela MESMA regra (`calcularValoresItemAvulso()`) que
     * qualquer outro Item do Projeto.
     *
     * @return array<int, \Filament\Schemas\Components\Component>
     */
    protected static function camposFormularioMobilizacaoFrete(): array
    {
        return [
            // Mesmo Placeholder de `camposFormularioItemAvulso()` (ver
            // esse campo lá) — o número exibido aqui é só uma PRÉVIA:
            // `numero_item` continua sendo gerado de verdade só no
            // momento da inserção (`ItemProjeto::boot()`, evento
            // "creating"), independente do que esta prévia mostrar.
            Placeholder::make('numero_item_preview')
                ->label(__('comercial::filament/resources/projeto.form.itens.item-avulso-modal.item-label'))
                ->content(function (Get $get, ?Projeto $record): string {
                    $itemId = $get('item_id');

                    if (filled($itemId)) {
                        $numero = $record?->itens()->find($itemId)?->numero_item;

                        if (filled($numero)) {
                            return $numero;
                        }
                    }

                    $ultimoNumero = (int) ($record?->itens()->max('numero_item') ?? 0);

                    return str_pad((string) ($ultimoNumero + 1), 3, '0', STR_PAD_LEFT);
                }),

            Hidden::make('item_id'),

            RichEditor::make('descricao')
                ->label(__('comercial::filament/resources/projeto.form.itens.item-avulso-modal.descricao-label'))
                ->required()
                ->rule(fn () => function (string $attribute, $value, \Closure $fail): void {
                    if (blank(static::textoPlanoRichEditor($value))) {
                        $fail(__('comercial::filament/resources/projeto.form.itens.validacao.descricao-obrigatoria'));
                    }
                })
                ->validationMessages([
                    'required' => __('comercial::filament/resources/projeto.form.itens.validacao.descricao-obrigatoria'),
                ])
                ->columnSpanFull(),

            Section::make(__('comercial::filament/resources/projeto.form.itens.mobilizacao-frete-modal.secao-dados'))
                ->columnSpanFull()
                ->schema([
                    // 5 colunas (não 4) — redistribuído (2026-09-06) pra
                    // aproveitar o modal alargado (`Width::FiveExtraLarge`,
                    // ver `inserirMobilizacaoFrete`/`editarMobilizacaoFrete{id}`)
                    // e reduzir de 4 pra 3 linhas os 15 campos de input.
                    Grid::make(5)
                        ->schema(collect([
                            'prazo_obra_dias', 'qtde_vistoria', 'funcionarios_vistoria', 'funcionarios_obra',
                            'valor_cafe_manha', 'valor_almoco', 'valor_jantar', 'valor_hotel',
                            'dias_viagem', 'valor_aviao', 'valor_onibus', 'km',
                            'qtde_frete', 'valor_frete_viagem', 'valor_translado',
                        ])->map(fn (string $campo) => TextInput::make($campo)
                            ->label(__("comercial::filament/resources/projeto.form.itens.mobilizacao-frete-modal.campos.{$campo}"))
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->live(onBlur: true)
                            ->extraInputAttributes(['class' => 'fi-input-no-spinner'])
                            ->afterStateUpdated(fn (Get $get, Set $set) => static::recalcularTotaisMobilizacaoFrete($get, $set)))
                            ->all()),
                ]),

            Section::make(__('comercial::filament/resources/projeto.form.itens.mobilizacao-frete-modal.secao-totais'))
                ->columnSpanFull()
                ->schema([
                    Grid::make(4)
                        ->schema(collect([
                            'total_mobilizacao_vistoria', 'total_mobilizacao_obra', 'total_frete', 'total_geral',
                        ])->map(fn (string $campo) => TextInput::make($campo)
                            ->label(__("comercial::filament/resources/projeto.form.itens.mobilizacao-frete-modal.campos.{$campo}"))
                            ->prefix('R$')
                            ->disabled()
                            ->dehydrated(false))
                            ->all()),
                ]),

            Grid::make(3)
                ->schema([
                    TextInput::make('quantidade')
                        ->label(__('comercial::filament/resources/projeto.form.itens.item-avulso-modal.quantidade-label'))
                        ->numeric()
                        ->integer()
                        ->required()
                        ->default(1)
                        ->rules(['gt:0'])
                        ->validationMessages([
                            'required' => __('comercial::filament/resources/projeto.form.itens.validacao.quantidade-obrigatoria'),
                            'gt'       => __('comercial::filament/resources/projeto.form.itens.validacao.quantidade-obrigatoria'),
                        ])
                        ->live(onBlur: true)
                        ->extraInputAttributes(['class' => 'fi-input-no-spinner'])
                        ->afterStateUpdated(fn (Get $get, Set $set) => static::recalcularValoresItemAvulso($get, $set)),

                    TextInput::make('porcentagem')
                        ->label(__('comercial::filament/resources/projeto.form.itens.item-avulso-modal.porcentagem-label'))
                        ->helperText(__('comercial::filament/resources/projeto.form.itens.porcentagem-tooltip'))
                        ->numeric()
                        ->integer()
                        ->live(onBlur: true)
                        ->extraInputAttributes(['class' => 'fi-input-no-spinner'])
                        ->afterStateUpdated(fn (Get $get, Set $set) => static::recalcularValoresItemAvulso($get, $set)),

                    // Alimentado pelo `total_geral` calculado acima
                    // (`recalcularTotaisMobilizacaoFrete()`) — DESABILITADO
                    // (nunca oculto, ver CLAUDE.md), mesmo esquema já usado
                    // pelo Custo Unitário de item de origem Promob: valor
                    // CALCULADO, não digitado livremente pelo usuário.
                    TextInput::make('custo_unitario')
                        ->label(__('comercial::filament/resources/projeto.form.itens.item-avulso-modal.custo-unitario-label'))
                        ->numeric()
                        ->prefix('R$')
                        ->disabled()
                        // `->disabled()` sozinho já faria o Filament NÃO
                        // desidratar este campo (mesmo padrão observado no
                        // Custo Unitário de item Promob, ver
                        // `camposFormularioItemAvulso()`) — aqui, diferente
                        // de lá, o valor calculado (`total_geral`) PRECISA
                        // chegar em `$data['custo_unitario']` pra
                        // `salvarMobilizacaoFrete()` gravar, por isso
                        // `->dehydrated()` explícito reativa o envio.
                        ->dehydrated()
                        ->live()
                        ->afterStateUpdated(fn (Get $get, Set $set) => static::recalcularValoresItemAvulso($get, $set)),
                ]),

            Grid::make(2)
                ->schema([
                    TextInput::make('valor_unitario')
                        ->label(__('comercial::filament/resources/projeto.form.itens.item-avulso-modal.valor-unitario-label'))
                        ->numeric()
                        ->prefix('R$')
                        ->disabled()
                        ->dehydrated(false),

                    TextInput::make('valor_total')
                        ->label(__('comercial::filament/resources/projeto.form.itens.item-avulso-modal.valor-total-label'))
                        ->numeric()
                        ->prefix('R$')
                        ->disabled()
                        ->dehydrated(false),
                ]),

            // Imp.% da Referência de Preços — mesmo mecanismo de cache
            // pra prévia em tela de `camposFormularioItemAvulso()` (ver
            // esse campo lá pro porquê da gravação de verdade nunca usar
            // este valor, só o fresco do banco).
            Hidden::make('imposto')
                ->dehydrated(false),
        ];
    }

    /**
     * Soma os 15 campos de input (`fretes_mobilizacao`) num único array
     * associativo — usada tanto por
     * `preencherFormularioMobilizacaoFrete()` (ler do registro
     * existente) quanto por `salvarMobilizacaoFrete()` (ler do `$data`
     * do form) — evita repetir a lista de nomes de campo nos dois
     * lugares.
     *
     * @return array<int, string>
     */
    protected static function camposInputMobilizacaoFrete(): array
    {
        return [
            'prazo_obra_dias', 'qtde_vistoria', 'funcionarios_vistoria', 'funcionarios_obra',
            'valor_cafe_manha', 'valor_almoco', 'valor_jantar', 'valor_hotel',
            'dias_viagem', 'valor_aviao', 'valor_onibus', 'km',
            'qtde_frete', 'valor_frete_viagem', 'valor_translado',
        ];
    }

    /**
     * Totais de "Mobilização e Frete" — função PURA (mesmo espírito de
     * `calcularValoresItemAvulso()`), recebe os 15 campos de input já
     * resolvidos (não lê `Get`/Model diretamente) pra poder ser
     * reaproveitada tanto pela prévia em tela quanto pela gravação de
     * verdade.
     *
     * - Mobilização (vistoria/obra) = dias x funcionários x
     *   (hotel+café+almoço+jantar) + dias de viagem x funcionários x
     *   (avião+ônibus) x2 + valor de translado — MESMA verba de
     *   translado somada nas duas fases (comportamento herdado da
     *   planilha original, ver CLAUDE.md).
     * - Frete = quantidade de viagens x valor do frete por viagem —
     *   modelo simplificado (2026-09-06, pedido do usuário): sem tentar
     *   identificar região, um único valor por viagem.
     * - `km` é só informativo neste modelo (sem campo de valor por km) —
     *   não entra em nenhum total.
     *
     * @param  array<string, float|int>  $dados
     * @return array{total_mobilizacao_vistoria: float, total_mobilizacao_obra: float, total_frete: float, total_geral: float}
     */
    protected static function calcularTotaisMobilizacaoFrete(array $dados): array
    {
        $get = fn (string $campo): float => (float) ($dados[$campo] ?? 0);

        $custoDiario = $get('valor_hotel') + $get('valor_cafe_manha') + $get('valor_almoco') + $get('valor_jantar');
        $custoViagem = ($get('valor_aviao') + $get('valor_onibus')) * 2;

        $totalVistoria = ($get('qtde_vistoria') * $get('funcionarios_vistoria') * $custoDiario)
            + ($get('dias_viagem') * $get('funcionarios_vistoria') * $custoViagem)
            + $get('valor_translado');

        $totalObra = ($get('prazo_obra_dias') * $get('funcionarios_obra') * $custoDiario)
            + ($get('dias_viagem') * $get('funcionarios_obra') * $custoViagem)
            + $get('valor_translado');

        $totalFrete = $get('qtde_frete') * $get('valor_frete_viagem');

        return [
            'total_mobilizacao_vistoria' => round($totalVistoria, 2),
            'total_mobilizacao_obra'     => round($totalObra, 2),
            'total_frete'                => round($totalFrete, 2),
            'total_geral'                => round($totalVistoria + $totalObra + $totalFrete, 2),
        ];
    }

    /**
     * Preenche o Form Modal de "Mobilização e Frete" — mesmo critério
     * de `preencherFormularioItemAvulso()`: SEMPRE roda em `mountUsing()`
     * (nunca confia em resetar ao fechar). Sem `$itemId` (criação): tudo
     * em branco/zerado. Com `$itemId` (edição): lê o `ItemProjeto` E o
     * `FreteMobilizacao` vinculado (`->freteMobilizacao`).
     */
    protected static function preencherFormularioMobilizacaoFrete(?Schema $schema, Get $get, ?Projeto $record, ?string $itemId): void
    {
        $referenciaPrecoId = $get('referencia_preco_id');
        $imposto = filled($referenciaPrecoId)
            ? (float) (ReferenciaPreco::find($referenciaPrecoId)?->imposto ?? 0)
            : 0.0;

        $item = filled($itemId) ? $record?->itens()->find($itemId) : null;
        $frete = $item?->freteMobilizacao;

        $inputs = collect(static::camposInputMobilizacaoFrete())
            ->mapWithKeys(fn (string $campo) => [$campo => $frete?->{$campo} ?? 0])
            ->all();

        $totais = static::calcularTotaisMobilizacaoFrete($inputs);

        $quantidade = $item?->quantidade ?? 1;
        $porcentagem = $item?->porcentagem ?? 0;
        $custoUnitario = $item ? (float) $item->custo_unitario : $totais['total_geral'];

        $valores = ($quantidade > 0 && $custoUnitario > 0)
            ? static::calcularValoresItemAvulso($custoUnitario, (float) $quantidade, (float) $porcentagem, $imposto)
            : ['valor_unitario' => null, 'valor_total' => null];

        $schema?->fill([
            ...$inputs,
            ...$totais,
            'item_id'        => $itemId,
            'descricao'      => $item?->descricao,
            'quantidade'     => $quantidade,
            'porcentagem'    => $porcentagem,
            'custo_unitario' => $custoUnitario,
            'imposto'        => $imposto,
            'valor_unitario' => $valores['valor_unitario'],
            'valor_total'    => $valores['valor_total'],
        ]);
    }

    /**
     * Recalcula, a cada tecla num dos 15 campos de input, tanto as
     * totalizações em tela (`total_mobilizacao_vistoria`/`_obra`/
     * `total_frete`/`total_geral`) quanto o Custo Unitário — que fica
     * DESABILITADO pro usuário digitar, mas precisa ser atualizado
     * programaticamente (`$set()`) pra refletir o novo `total_geral`, o
     * que por sua vez dispara `recalcularValoresItemAvulso()` (mesmo
     * `afterStateUpdated()` do campo, ver `camposFormularioMobilizacaoFrete()`)
     * pra também atualizar Valor Unitário/Valor Total.
     */
    protected static function recalcularTotaisMobilizacaoFrete(Get $get, Set $set): void
    {
        $inputs = collect(static::camposInputMobilizacaoFrete())
            ->mapWithKeys(fn (string $campo) => [$campo => $get($campo) ?? 0])
            ->all();

        $totais = static::calcularTotaisMobilizacaoFrete($inputs);

        foreach ($totais as $campo => $valor) {
            $set($campo, $valor);
        }

        $set('custo_unitario', $totais['total_geral']);

        static::recalcularValoresItemAvulso($get, $set);
    }

    /**
     * Valida e persiste o Form Modal de "Mobilização e Frete" — mesmo
     * critério geral de `salvarItemAvulso()` (bloqueia sem `$record`,
     * transação com `lockForUpdate()` na Referência de Preços pro
     * Imp.% fresco), mas grava em DUAS tabelas na MESMA transação:
     * `itens_projeto` (origem `MobilizacaoFrete`) e `fretes_mobilizacao`
     * (`updateOrCreate` pelo `item_projeto_id`, 1-pra-1).
     *
     * @param  array<string, mixed>  $data
     */
    protected static function salvarMobilizacaoFrete(array $data, Get $get, ?Projeto $record, $livewire): void
    {
        if (! $record) {
            Notification::make()
                ->warning()
                ->title(__('comercial::filament/resources/projeto.form.itens.notification.projeto-nao-salvo-title'))
                ->body(__('comercial::filament/resources/projeto.form.itens.notification.projeto-nao-salvo-body'))
                ->send();

            return;
        }

        $itemId = $data['item_id'] ?? null;
        $descricao = (string) $data['descricao'];
        $quantidade = $data['quantidade'];
        $custoUnitario = (float) ($data['custo_unitario'] ?? 0);
        $porcentagem = (float) ($data['porcentagem'] ?? 0);
        $referenciaPrecoId = $get('referencia_preco_id');

        $inputs = collect(static::camposInputMobilizacaoFrete())
            ->mapWithKeys(fn (string $campo) => [$campo => $data[$campo] ?? 0])
            ->all();

        DB::transaction(function () use ($record, $descricao, $quantidade, $custoUnitario, $porcentagem, $referenciaPrecoId, $itemId, $inputs): void {
            $item = filled($itemId) ? $record->itens()->lockForUpdate()->find($itemId) : null;

            $impostoAplicado = filled($referenciaPrecoId)
                ? (float) (ReferenciaPreco::where('id', $referenciaPrecoId)->lockForUpdate()->value('imposto') ?? 0)
                : 0.0;

            $valores = static::calcularValoresItemAvulso($custoUnitario, (float) $quantidade, $porcentagem, $impostoAplicado);

            $dadosItem = [
                'descricao'        => $descricao,
                'quantidade'       => (int) $quantidade,
                'porcentagem'      => $porcentagem,
                'custo_unitario'   => $custoUnitario,
                'imposto_aplicado' => $impostoAplicado,
                'valor_unitario'   => $valores['valor_unitario'],
                'valor_total'      => $valores['valor_total'],
            ];

            if ($item) {
                $item->update($dadosItem);
            } else {
                $record->itens()->lockForUpdate()->get();
                $dadosItem['origem'] = OrigemItemProjeto::MobilizacaoFrete;
                $dadosItem['referencia'] = __('comercial::filament/resources/projeto.form.itens.origens.mobilizacao-frete');
                $item = $record->itens()->create($dadosItem);
            }

            FreteMobilizacao::updateOrCreate(
                ['item_projeto_id' => $item->id],
                ['projeto_id' => $record->id, ...$inputs],
            );
        });

        if ($livewire instanceof EditProjeto) {
            $livewire->recarregarItens();
        }

        Notification::make()
            ->success()
            ->title(__('comercial::filament/resources/projeto.form.itens.notification.item-avulso-confirmado'))
            ->send();
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
                // Referência (2026-09-06, revisado) — LABEL curto de
                // origem ("Promob"/"Item Avulso", ver
                // `criarTodosItensPromob()`/`salvarItemAvulso()`), NÃO
                // dado variável (nome de arquivo, data/hora etc.) — isso
                // já fica disponível por item no ícone "Cálculos"
                // (Promob) ou na própria Descrição (Item Avulso).
                // Futuro: "Item de Linha" (ainda não implementado) deve
                // trazer aqui o código de referência real do Produto
                // vinculado (não um label estático) — "e o mesmo depois"
                // pro SketchUp, quando implementado.
                Text::make((string) ($item->referencia ?? ''))
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
                    ActionGroup::make(array_values(array_filter([
                        // "Cálculos" (2026-09-06, ver CLAUDE.md, "Fluxo
                        // Promob") — só aparece pra Itens com pelo menos
                        // uma `NotaProjeto` de SISTEMA vinculada
                        // (`item_projeto_id`), ou seja, Itens criados via
                        // Promob; Item Avulso nunca tem nota vinculada,
                        // então nunca mostra este ícone. Modal
                        // SOMENTE LEITURA — sem editar/excluir por aqui
                        // (a edição de nota de sistema, quando permitida,
                        // é só pelo super usuário, no modal geral de
                        // Notas do Projeto).
                        $item->notas()->where('tipo_sistema', true)->exists()
                            ? static::acaoVerCalculosItem($item)
                            : null,
                        // Abre o MESMO Form Modal de "Inserir" (ver
                        // `inserirItemAvulso`/`camposFormularioItemAvulso()`),
                        // preenchido com os dados atuais deste item —
                        // migração de linha inline pra modal (2026-09-06,
                        // ver CLAUDE.md). `$item->id` fica FECHADO no
                        // Closure (não precisa de `->arguments()`/`->record()`
                        // pra identificar QUAL item, já que
                        // `linhaExibicaoItem()` já roda uma vez por item,
                        // com `$item` capturado normalmente).
                        //
                        // "Mobilização e Frete" (2026-09-06) tem seu PRÓPRIO
                        // modal de edição (`editarMobilizacaoFrete{id}`,
                        // abaixo) — o de "Item Avulso" só aparece pras
                        // demais origens que ainda reaproveitam esse form
                        // (Item Avulso propriamente dito, e as origens sem
                        // lógica própria ainda).
                        $item->origem !== OrigemItemProjeto::MobilizacaoFrete
                            ? Action::make("editarItemAvulso{$item->id}")
                                ->label(__('comercial::filament/resources/projeto.form.itens.editar'))
                                ->icon('heroicon-o-pencil-square')
                                ->modalHeading(__('comercial::filament/resources/projeto.form.itens.item-avulso-modal.heading-editar'))
                                ->modalSubmitActionLabel(__('comercial::filament/resources/projeto.form.itens.item-avulso-modal.salvar'))
                                ->mountUsing(fn (?Schema $schema, Get $get, ?Projeto $record) => static::preencherFormularioItemAvulso($schema, $get, $record, (string) $item->id))
                                ->form(static::camposFormularioItemAvulso())
                                ->action(function (array $data, Get $get, ?Projeto $record, $livewire): void {
                                    static::salvarItemAvulso($data, $get, $record, $livewire);
                                })
                            : null,
                        $item->origem === OrigemItemProjeto::MobilizacaoFrete
                            ? Action::make("editarMobilizacaoFrete{$item->id}")
                                ->label(__('comercial::filament/resources/projeto.form.itens.editar'))
                                ->icon('heroicon-o-pencil-square')
                                ->modalHeading(__('comercial::filament/resources/projeto.form.itens.mobilizacao-frete-modal.heading-editar'))
                                ->modalSubmitActionLabel(__('comercial::filament/resources/projeto.form.itens.mobilizacao-frete-modal.salvar'))
                                ->modalWidth(Width::FiveExtraLarge)
                                ->mountUsing(fn (?Schema $schema, Get $get, ?Projeto $record) => static::preencherFormularioMobilizacaoFrete($schema, $get, $record, (string) $item->id))
                                ->form(static::camposFormularioMobilizacaoFrete())
                                ->action(function (array $data, Get $get, ?Projeto $record, $livewire): void {
                                    static::salvarMobilizacaoFrete($data, $get, $record, $livewire);
                                })
                            : null,
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
                    ])))
                        ->icon('heroicon-m-ellipsis-vertical')
                        ->color('gray'),
                ])
                    ->alignCenter()
                    ->verticallyAlignStart()
                    ->columnSpan(1),
            ]);
    }

    /**
     * "Cálculos" (Parte 5 do enunciado da tarefa "Criar Itens do
     * Promob") — modal SOMENTE LEITURA com a(s) Nota(s) de sistema
     * vinculadas a este Item (`item_projeto_id`), mais recente
     * primeiro. Reaproveita `linhaExibicaoNotaSomenteLeitura()` (mesmo
     * visual de número/autor/data/badge/texto de `linhaExibicaoNota()`
     * do modal geral de Notas, SEM os ícones de editar/excluir — a
     * edição de nota de sistema, quando permitida, é só pelo super
     * usuário, no modal geral).
     */
    protected static function acaoVerCalculosItem(ItemProjeto $item): Action
    {
        return Action::make("verCalculosItem{$item->id}")
            ->label(__('comercial::filament/resources/projeto.form.itens.calculos.acao'))
            ->icon('heroicon-o-calculator')
            ->color('gray')
            ->modalHeading(__('comercial::filament/resources/projeto.form.itens.calculos.modal.heading', ['numero' => $item->numero_item]))
            ->modalWidth(Width::Large)
            ->modalSubmitAction(false)
            ->form([
                Group::make()
                    ->schema(fn () => $item->notas()
                        ->where('tipo_sistema', true)
                        ->orderByDesc('numero_nota')
                        ->get()
                        ->map(fn (NotaProjeto $nota) => static::linhaExibicaoNotaSomenteLeitura($nota))
                        ->all()),
            ]);
    }

    /**
     * Linha de exibição SOMENTE LEITURA de uma nota — mesmo cabeçalho
     * (número/autor/data-hora/badge "Sistema") de `linhaExibicaoNota()`
     * (modal geral de Notas do Projeto), sem a coluna de ações
     * (editar/excluir nunca aparecem aqui, independente de quem esteja
     * vendo — ver `acaoVerCalculosItem()`).
     */
    protected static function linhaExibicaoNotaSomenteLeitura(NotaProjeto $nota): Group
    {
        $autor = $nota->usuario?->name ?? __('comercial::filament/resources/projeto.form.notas.autor-sistema');

        return Group::make()
            ->key("nota-calculo-{$nota->id}")
            ->extraAttributes(['style' => 'padding-bottom: .75rem; margin-bottom: .75rem; border-bottom: 1px solid rgba(0,0,0,.08);'])
            ->schema([
                Flex::make(array_values(array_filter([
                    Text::make('#'.$nota->numero_nota)->weight(FontWeight::Bold),
                    Text::make($autor),
                    Text::make($nota->created_at?->format('d/m/Y H:i')),
                    $nota->tipo_sistema
                        ? Text::make(__('comercial::filament/resources/projeto.form.notas.badge-sistema'))->badge()->color('gray')
                        : null,
                ])))
                    ->dense(),

                Html::make(new HtmlString((string) $nota->texto)),
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

            // Exclui junto a(s) `NotaProjeto` de cálculo vinculada(s)
            // a este item (`item_projeto_id`, criada em
            // `criarTodosItensPromob()` via
            // `renderizarResumoCalculoItemPromob()`) — sem isso, a
            // nota ficava órfã (o registro continuava existindo com
            // `item_projeto_id` apontando pra um item já excluído).
            // `$item->notas()` já filtra por `item_projeto_id`, então
            // isso não afeta a Nota GERAL do Projeto (`item_projeto_id
            // = null`).
            $item->notas()->delete();

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

    /**
     * Campos do modal "Notas do Projeto" (ícone no cabeçalho da Section
     * "Cabeçalho", ver `form()` acima) — lista das notas já existentes
     * (mais recente primeiro), campo de nova nota e o botão "Adicionar
     * Nota". Sem `$record` (Projeto ainda não salvo): o ícone nem
     * aparece (`->visible()` do próprio `notasProjeto`), então este
     * método só roda de fato com um Projeto já existente — o `?Projeto`
     * aqui é só pra manter a mesma assinatura defensiva usada em outros
     * métodos deste Resource.
     *
     * @return array<int, \Filament\Schemas\Components\Component>
     */
    protected static function camposModalNotasProjeto(?Projeto $record): array
    {
        return [
            // Lista das notas já existentes — lida DIRETO do banco a
            // cada avaliação do Closure (sem property de cache, ao
            // contrário de `EditProjeto::$itensCarregados`): este Group
            // vive dentro do Schema PRÓPRIO da Action `notasProjeto`
            // (`mountedActions.{n}.data`), construído só quando o modal
            // é montado (`mountAction()`), não no `mount()` da PÁGINA —
            // o achado de timing que motivou `$itensCarregados` (Schema
            // avaliado cedo demais por `fillForm()` durante o `mount()`
            // da página, antes da property ser hidratada) não se aplica
            // aqui. Confirmado por precedente já validado: o `Text` do
            // resultado do Promob (`renderizarResultadoPromob()`) já lê
            // um valor atualizado a cada nova interação DENTRO do mesmo
            // modal aberto, prova de que Closures de um Schema de Action
            // já montada são reavaliadas a cada re-render do Livewire.
            Group::make()
                ->schema(fn () => $record
                    ? $record->notas()
                        ->orderByDesc('numero_nota')
                        ->get()
                        ->map(fn (NotaProjeto $nota) => static::linhaExibicaoNota($nota))
                        ->all()
                    : []),

            RichEditor::make('nova_nota')
                ->label(__('comercial::filament/resources/projeto.form.notas.nova-nota-label'))
                // SEM `->toolbarButtons()` — mesmo padrão já decidido pra
                // Descrição do Item Avulso: começa com o conjunto DEFAULT
                // completo do Filament, só reduzir depois com uso real.
                ->columnSpanFull(),

            // Action FLAT (sem `->form()` próprio) declarada como IRMÃ do
            // `RichEditor::make('nova_nota')` acima, dentro do MESMO
            // array retornado por este método — por isso `Get`/`Set`
            // injetados no `->action()` dela resolvem pro MESMO Schema
            // de `nova_nota` (`$action->getSchemaContainer()` aponta pro
            // container onde a Action está DECLARADA, não pra um Schema
            // próprio — só existe Schema próprio quando a Action tem
            // `->form()`). Mesmo mecanismo já usado por `inserirItem`
            // lendo `$get('origem_item_selecionada')`, campo IRMÃO dele
            // na mesma `Actions::make([...])`.
            Actions::make([
                Action::make('adicionarNota')
                    ->label(__('comercial::filament/resources/projeto.form.notas.adicionar'))
                    ->action(function (Get $get, Set $set, ?Projeto $record): void {
                        static::adicionarNotaProjeto($get, $set, $record);
                    }),
            ]),
        ];
    }

    /**
     * Valida (mínimo: não vazio, reaproveitando `textoPlanoRichEditor()`
     * já usado pela Descrição de Item Avulso) e persiste uma nova nota
     * de USUÁRIO (`tipo_sistema = false`, `usuario_id` = autenticado).
     * `DB::transaction()` + `lockForUpdate()` nas notas já existentes
     * daquele Projeto — mesma disciplina de concorrência já usada por
     * `salvarItemAvulso()` pro `numero_item` (ver `NotaProjeto::boot()`
     * pro `MAX()+1` de `numero_nota`).
     *
     * Sem `$record` (Projeto ainda sem salvar — só possível se alguém
     * forçar a Action por fora da UI, já que o ícone nem aparece nesse
     * caso): bloqueia com a mesma notificação de "salve primeiro" já
     * usada por Item Avulso.
     *
     * `$set('nova_nota', null)` ao final — reseta o campo pra poder
     * adicionar outra nota em seguida SEM fechar o modal (o modal só
     * fecha pelo "Cancelar" nativo, ver `notasProjeto` acima).
     */
    protected static function adicionarNotaProjeto(Get $get, Set $set, ?Projeto $record): void
    {
        if (! $record) {
            Notification::make()
                ->warning()
                ->title(__('comercial::filament/resources/projeto.form.notas.notification.projeto-nao-salvo-title'))
                ->body(__('comercial::filament/resources/projeto.form.notas.notification.projeto-nao-salvo-body'))
                ->send();

            return;
        }

        $texto = (string) $get('nova_nota');

        if (blank(static::textoPlanoRichEditor($texto))) {
            Notification::make()
                ->warning()
                ->title(__('comercial::filament/resources/projeto.form.notas.validacao.texto-obrigatorio'))
                ->send();

            return;
        }

        DB::transaction(function () use ($record, $texto): void {
            $record->notas()->lockForUpdate()->get();

            $record->notas()->create([
                'usuario_id'   => auth()->id(),
                'texto'        => $texto,
                'tipo_sistema' => false,
            ]);
        });

        $set('nova_nota', null);

        Notification::make()
            ->success()
            ->title(__('comercial::filament/resources/projeto.form.notas.notification.nota-adicionada'))
            ->send();
    }

    /**
     * Uma linha de EXIBIÇÃO de uma nota já existente, dentro do modal
     * "Notas do Projeto" — número/autor/data-hora (+ badge "Sistema"
     * quando `tipo_sistema`) numa linha, o texto (HTML do RichEditor)
     * renderizado de verdade logo abaixo via `Html::make()` (diferente
     * da listagem de Item Avulso, que mostra só texto puro — aqui não
     * há a mesma restrição de altura de uma grid de planilha, então a
     * formatação completa aparece direto, sem precisar entrar em modo
     * edição pra ver negrito/listas/etc.). Ícones de editar/excluir só
     * aparecem quando `podeSerEditadaPor()`/`podeSerExcluidaPor()` (ver
     * `NotaProjeto`) são verdadeiros PRO USUÁRIO ATUAL — dono da nota
     * dentro do prazo de 24h, OU super usuário (Role `Admin`, guard
     * `web`) sempre, mesmo de nota de outro usuário/de sistema/fora do
     * prazo. `auth()->user()` lido direto (mesmo padrão já usado por
     * `adicionarNotaProjeto()` com `auth()->id()`) — sem usuário
     * autenticado (não deveria acontecer numa tela protegida), trata
     * como sem permissão nenhuma.
     */
    protected static function linhaExibicaoNota(NotaProjeto $nota): Group
    {
        $usuarioAtual = auth()->user();
        $podeEditar = $usuarioAtual && $nota->podeSerEditadaPor($usuarioAtual);
        $podeExcluir = $usuarioAtual && $nota->podeSerExcluidaPor($usuarioAtual);
        $autor = $nota->usuario?->name ?? __('comercial::filament/resources/projeto.form.notas.autor-sistema');

        return Group::make()
            ->key("nota-projeto-{$nota->id}")
            ->extraAttributes(['style' => 'padding-bottom: .75rem; margin-bottom: .75rem; border-bottom: 1px solid rgba(0,0,0,.08);'])
            ->schema([
                Grid::make(12)
                    ->extraAttributes(['style' => 'gap: 1rem !important;'])
                    ->schema([
                        Flex::make(array_values(array_filter([
                            Text::make('#'.$nota->numero_nota)->weight(FontWeight::Bold),
                            Text::make($autor),
                            Text::make($nota->created_at?->format('d/m/Y H:i')),
                            $nota->tipo_sistema
                                ? Text::make(__('comercial::filament/resources/projeto.form.notas.badge-sistema'))->badge()->color('gray')
                                : null,
                        ])))
                            ->dense()
                            ->columnSpan(9),

                        // `ActionGroup` (dropdown), não dois ícones lado a
                        // lado — mesmo critério já usado em
                        // `linhaExibicaoItem()`. Vazio (`[]`) quando nem
                        // editar nem excluir são permitidos — a coluna
                        // fica sem nenhum ícone, não com um dropdown vazio.
                        Actions::make(
                            ($podeEditar || $podeExcluir)
                                ? [
                                    ActionGroup::make(array_values(array_filter([
                                        $podeEditar ? static::acaoEditarNota($nota) : null,
                                        $podeExcluir ? static::acaoExcluirNota($nota) : null,
                                    ])))
                                        ->icon('heroicon-m-ellipsis-vertical')
                                        ->color('gray'),
                                ]
                                : []
                        )
                            ->alignEnd()
                            ->columnSpan(3),
                    ]),

                Html::make(new HtmlString((string) $nota->texto)),
            ]);
    }

    /**
     * Action de EDITAR uma nota — mesmo padrão técnico de
     * `editarItemAvulso{id}` (Action aninhada com `->form()` próprio,
     * `->mountUsing()` preenche com o texto atual), só que aninhada UM
     * NÍVEL A MAIS: aqui ela vive dentro do Schema da Action
     * `notasProjeto` (o modal "Notas do Projeto"), não direto no Schema
     * da página. `$nota` fica fechado no Closure — a mesma nota pra qual
     * `linhaExibicaoNota()` já está rodando.
     */
    protected static function acaoEditarNota(NotaProjeto $nota): Action
    {
        return Action::make("editarNota{$nota->id}")
            ->label(__('comercial::filament/resources/projeto.form.notas.editar'))
            ->icon('heroicon-o-pencil-square')
            ->modalHeading(__('comercial::filament/resources/projeto.form.notas.modal-editar.heading', ['numero' => $nota->numero_nota]))
            ->modalSubmitActionLabel(__('comercial::filament/resources/projeto.form.notas.salvar'))
            ->mountUsing(fn (?Schema $schema) => $schema?->fill(['texto' => $nota->texto]))
            ->form([
                RichEditor::make('texto')
                    ->label(__('comercial::filament/resources/projeto.form.notas.texto-label'))
                    ->required()
                    ->rule(fn () => function (string $attribute, $value, \Closure $fail): void {
                        if (blank(static::textoPlanoRichEditor($value))) {
                            $fail(__('comercial::filament/resources/projeto.form.notas.validacao.texto-obrigatorio'));
                        }
                    })
                    ->validationMessages([
                        'required' => __('comercial::filament/resources/projeto.form.notas.validacao.texto-obrigatorio'),
                    ])
                    ->columnSpanFull(),
            ])
            ->action(fn (array $data) => static::salvarEdicaoNota($nota, $data));
    }

    /**
     * `DeleteAction` só pelo visual/confirmação padrão (mesmo critério
     * de `excluirItemProjeto{id}`) — `->record($nota)` obrigatório (este
     * componente não vive dentro de uma Table de verdade).
     */
    protected static function acaoExcluirNota(NotaProjeto $nota): DeleteAction
    {
        return DeleteAction::make("excluirNota{$nota->id}")
            ->label(__('comercial::filament/resources/projeto.form.notas.excluir'))
            ->record($nota)
            ->modalHeading(__('comercial::filament/resources/projeto.form.notas.excluir-confirmacao.heading', ['numero' => $nota->numero_nota]))
            ->modalDescription(__('comercial::filament/resources/projeto.form.notas.excluir-confirmacao.description'))
            ->action(fn () => static::excluirNotaProjeto($nota));
    }

    /**
     * Releitura fresca da nota (`NotaProjeto::find()`, não o `$nota`
     * fechado no Closure) + `podeSerEditadaPor($usuarioAtual)` checado
     * de novo aqui — nunca confiar só em esconder o ícone na tela (ver
     * CLAUDE.md, regra de 24h/super usuário): entre a hora em que a
     * listagem foi montada e o clique em "Salvar", o prazo pode ter
     * expirado ou o usuário logado pode ter mudado (mesmo que pouco
     * provável, é o mesmo cuidado já validado por
     * `salvarItemAvulso()`/Imposto obsoleto).
     *
     * Só grava o campo `texto` — mesmo um super usuário editando NUNCA
     * altera `usuario_id`/`numero_nota`/`tipo_sistema` (o form deste
     * modal, `acaoEditarNota()`, só expõe o RichEditor de texto; não há
     * como esses campos chegarem em `$data`).
     *
     * **`created_at` só é atualizado quando quem edita é o PRÓPRIO
     * autor da nota, dentro do prazo de 24h** (`dentroDoPrazoDeEdicao()`
     * + `usuario_id === $usuarioAtual->id`) — "reinicia" a janela de
     * edição, mas NÃO reordena a listagem (ordenada por `numero_nota`,
     * que não muda numa edição): a nota mantém sua posição, só a
     * data/hora exibida avança. Um super usuário editando a nota de
     * OUTRO usuário, uma nota já fora do prazo, ou uma nota de sistema
     * NUNCA atualiza `created_at` — seria forjar uma data de criação
     * falsa num registro que é edição de manutenção, não uma nota nova
     * (decisão registrada no CLAUDE.md). Se o PRÓPRIO super usuário
     * editar uma nota SUA, recém-criada (dentro do prazo), o
     * comportamento cai na mesma regra de "autor dentro do prazo" —
     * consistente, não é "forjar" nada, é a mesma nota sendo re-tocada
     * pelo mesmo autor.
     *
     * `forceFill()` (não `update()`) — `created_at` fica DE PROPÓSITO
     * fora do `$fillable` (é gerido pelo Eloquent), então um
     * `update(['created_at' => ...])` seria ignorado silenciosamente,
     * mesmo achado já documentado pra `numero_item`/`ItemProjeto
     * ::excluirItemAvulso()`.
     */
    protected static function salvarEdicaoNota(NotaProjeto $nota, array $data): void
    {
        $usuarioAtual = auth()->user();
        $notaAtual = NotaProjeto::find($nota->id);

        if (! $notaAtual || ! $usuarioAtual || ! $notaAtual->podeSerEditadaPor($usuarioAtual)) {
            Notification::make()
                ->danger()
                ->title(__('comercial::filament/resources/projeto.form.notas.notification.sem-permissao'))
                ->send();

            return;
        }

        $texto = (string) $data['texto'];

        if (trim((string) $notaAtual->texto) === trim($texto)) {
            Notification::make()
                ->success()
                ->title(__('comercial::filament/resources/projeto.form.notas.notification.nota-atualizada'))
                ->send();

            return;
        }

        $dadosParaGravar = ['texto' => $texto];

        if ($notaAtual->usuario_id === $usuarioAtual->id && $notaAtual->dentroDoPrazoDeEdicao()) {
            $dadosParaGravar['created_at'] = now();
        }

        $notaAtual->forceFill($dadosParaGravar)->save();

        Notification::make()
            ->success()
            ->title(__('comercial::filament/resources/projeto.form.notas.notification.nota-atualizada'))
            ->send();
    }

    /**
     * Exclusão SIMPLES (sem renumeração — ver CLAUDE.md/`NotaProjeto`),
     * dentro de `DB::transaction()` (pedido explícito da tarefa, mesmo
     * sendo uma única escrita hoje). Mesma releitura fresca +
     * `podeSerExcluidaPor($usuarioAtual)` de `salvarEdicaoNota()` acima,
     * pelo mesmo motivo.
     */
    protected static function excluirNotaProjeto(NotaProjeto $nota): void
    {
        $usuarioAtual = auth()->user();
        $notaAtual = NotaProjeto::find($nota->id);

        if (! $notaAtual || ! $usuarioAtual || ! $notaAtual->podeSerExcluidaPor($usuarioAtual)) {
            Notification::make()
                ->danger()
                ->title(__('comercial::filament/resources/projeto.form.notas.notification.sem-permissao'))
                ->send();

            return;
        }

        DB::transaction(function () use ($notaAtual): void {
            $notaAtual->delete();
        });

        Notification::make()
            ->success()
            ->title(__('comercial::filament/resources/projeto.form.notas.notification.nota-excluida'))
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
