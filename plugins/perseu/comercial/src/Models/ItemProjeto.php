<?php

namespace Perseu\Comercial\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Perseu\Auditoria\Traits\LogsBusinessActivity;
use Perseu\Comercial\Enums\OrigemItemProjeto;

/**
 * SEM `SoftDeletes` (diferente da convenção padrão de Model de negócio,
 * ver CLAUDE.md da raiz) — decisão deliberada: excluir um item RENUMERA
 * os itens seguintes daquele Projeto pra fechar o buraco na sequência
 * (`numero_item`), o que exige que o número excluído fique DE VERDADE
 * livre. Uma linha soft-deleted continuaria ocupando o slot no índice
 * único `(projeto_id, numero_item)` da migration, bloqueando a
 * renumeração do item seguinte pra esse mesmo número — SoftDeletes e
 * "fechar buracos na numeração" são mutuamente incompatíveis aqui. Item
 * de Projeto também é, como a própria tarefa que introduziu a exclusão
 * descreveu, "um detalhe operacional, não um cadastro central auditado
 * como Obra/Pessoa" — sem o mesmo valor de manter um rastro de "excluído
 * mas recuperável" que `Projeto`/`PessoaFisica`/`PessoaJuridica` têm.
 * `LogsBusinessActivity` continua funcionando normalmente sem
 * `SoftDeletes` (o próprio trait já trata esse caso — só pula o listener
 * de `forceDeleted`, que não existe sem o trait; o evento `deleted`
 * padrão do Spatie já cobre a exclusão de verdade). Ver
 * `ProjetoResource::excluirItemAvulso()` pra a exclusão + renumeração em
 * si.
 */
class ItemProjeto extends Model
{
    use LogsBusinessActivity;

    protected $table = 'itens_projeto';

    // numero_item nunca é preenchido pelo usuário — gerado
    // automaticamente no evento "creating" abaixo (mesmo critério já
    // usado por Projeto::numero_projeto), por isso fora do $fillable.
    protected $fillable = [
        'projeto_id',
        'origem',
        'produto_id',
        // Label curto de origem ("Promob"/"Item Avulso", 2026-09-06,
        // revisado — NÃO nome de arquivo/data/hora, decisão original
        // revertida), exibido na coluna "Referência" da listagem de
        // Itens. Detalhe completo de origem fica em outro lugar por
        // item (ícone "Cálculos" pra Promob, a própria Descrição pra
        // Item Avulso) — não duplicado aqui pra não inchar a base à
        // toa. Reservado desde a criação da tabela pro futuro "Item de
        // Linha", que deve usar esta coluna pro código de referência
        // REAL do Produto vinculado (não mais um label estático) — "e o
        // mesmo depois" pro SketchUp. Ver migration/CLAUDE.md, "Fluxo
        // Promob".
        'referencia',
        'descricao',
        'quantidade',
        'valor_unitario',
        'valor_total',
        'porcentagem',
        'custo_unitario',
        'imposto_aplicado',
        'situacao_item_id',
        // Dados do XML Promob de origem (2026-09-12, ver migration
        // 2026_09_12_110000 e ItemProjeto::metricasPromob()) —
        // substituem a antiga NotaProjeto de sistema como registro
        // estruturado de qual arquivo gerou este Item.
        'custo_total_xml',
        'arquivo_origem',
        'arquivo_gerado_em',
    ];

    protected $casts = [
        'origem'           => OrigemItemProjeto::class,
        'quantidade'       => 'integer',
        'valor_unitario'   => 'decimal:2',
        'valor_total'      => 'decimal:2',
        'porcentagem'      => 'decimal:2',
        'custo_unitario'   => 'decimal:2',
        'imposto_aplicado' => 'decimal:2',
        'custo_total_xml'  => 'decimal:2',
    ];

    public function projeto(): BelongsTo
    {
        return $this->belongsTo(Projeto::class);
    }

    /**
     * Notas de SISTEMA vinculadas a este item especificamente (base do
     * futuro ícone "Cálculos" no menu de cada item — ver CLAUDE.md,
     * "Notas do Projeto"). Geração automática ainda não implementada;
     * só a coluna/relação já existem.
     */
    public function notas(): HasMany
    {
        return $this->hasMany(NotaProjeto::class, 'item_projeto_id');
    }

    /**
     * Registro de "Mobilização e Frete" vinculado 1-pra-1 a este item
     * (só existe pra itens de origem `OrigemItemProjeto::MobilizacaoFrete`
     * — ver `FreteMobilizacao`/`ProjetoResource::salvarMobilizacaoFrete()`).
     */
    public function freteMobilizacao(): HasOne
    {
        return $this->hasOne(FreteMobilizacao::class, 'item_projeto_id');
    }

    /**
     * Componentes (matéria-prima) extraídos do XML do Promob no
     * momento de "Criar Itens" — base da Aba P (Lista de Compras/
     * Necessidade de Materiais, 2026-09-12, ver
     * `ItemProjetoComponente`/migration
     * `2026_09_12_100000_create_itens_projeto_componentes_table` e
     * handoff `handoff_aba_p.md`). Só populado pra itens de origem
     * `OrigemItemProjeto::Promob`; itens de outra origem (Item Avulso
     * etc.) simplesmente não têm nenhuma linha aqui.
     */
    public function componentes(): HasMany
    {
        return $this->hasMany(ItemProjetoComponente::class, 'item_projeto_id');
    }

    /**
     * Recalcula AO VIVO as 5 métricas do Promob (Peças/m²/Metro Linear/
     * Custo/Misc, mesma forma de `PromobXmlParser::metricas()`) a
     * partir dos componentes JÁ PERSISTIDOS — 2026-09-12, ver migration
     * `2026_09_12_110000_add_dados_calculo_promob_to_itens_projeto_table`
     * e handoff `handoff_aba_p.md`. Substitui o texto congelado que
     * antes ficava numa `NotaProjeto` de sistema: como o cálculo agora
     * é feito aqui, ele sempre reflete os componentes atuais do Item
     * (nunca precisa reler o XML original, que já foi descartado).
     *
     * **Peças/m²/Metro Linear/Custo** somam só os componentes
     * `componentizado = true` (peças de madeira/painel, `COMPONENT="Y"`
     * no XML original) — **Misc** soma os `componentizado = false`
     * (ferragens/acessórios, `COMPONENT="N"` sem filhos). Mesma divisão
     * exata da fórmula original (`PromobXmlParser::metricas()`), só que
     * reconstruída a partir do flag persistido em cada componente (ver
     * migration `2026_09_12_120000_add_componentizado_to_itens_projeto_componentes_table`)
     * em vez de reler o XML — necessário desde que
     * `componentesParaMateriais()` passou a também extrair ferragens
     * como componentes próprios (2026-09-12): sem essa divisão, "Misc"
     * (e a linha "Ferragens/Miscelânea" do detalhamento) cairia pra
     * perto de zero, divergindo do Custo Unitário já gravado no Item.
     *
     * @return array{pecas: int, m2: float, mlinear: float, custo: float, misc: float}
     */
    public function metricasPromob(): array
    {
        $componentes = $this->componentes;
        $madeira = $componentes->where('componentizado', true);
        $ferragens = $componentes->where('componentizado', false);

        $pecas = (int) $madeira->sum('repeticao');
        $m2 = (float) $madeira->sum(fn (ItemProjetoComponente $c) => (float) $c->repeticao * (float) $c->quantidade);
        $mlinear = (float) $madeira->sum(fn (ItemProjetoComponente $c) => ((float) $c->largura + (float) $c->profundidade) * 2 * (float) $c->repeticao / 1000);
        $custo = (float) $madeira->sum('custo');
        $misc = (float) $ferragens->sum('custo');

        return [
            'pecas'   => $pecas,
            'm2'      => $m2,
            'mlinear' => $mlinear,
            'custo'   => $custo,
            'misc'    => $misc,
        ];
    }

    /**
     * `numero_item` = maior número já usado NAQUELE Projeto + 1,
     * formato `###` (`001`, `002`...), começando em `001`. Diferente de
     * `numero_projeto` (`Projeto`), números AQUI são reaproveitados —
     * excluir um item renumera os seguintes pra fechar o buraco (ver
     * `ProjetoResource::excluirItemAvulso()`), por isso não precisa
     * (nem pode, sem `SoftDeletes`) considerar registros excluídos
     * aqui: um `MAX()` simples nos registros ATUAIS já reflete a
     * sequência contígua depois de qualquer exclusão. O
     * `DB::transaction()` + `lockForUpdate()` na chamada
     * (`ProjetoResource::confirmarItemAvulso()`) cobre o caso de dois
     * cliques rápidos no mesmo Projeto; a primeira inserção de um
     * Projeto novo (sem nenhuma linha pra travar ainda) fica fora
     * dessa proteção — risco aceito, não é um fluxo multi-usuário
     * simultâneo no mesmo Projeto.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (ItemProjeto $item): void {
            if (blank($item->numero_item)) {
                $ultimoNumero = (int) static::where('projeto_id', $item->projeto_id)->max('numero_item');

                $item->numero_item = str_pad((string) ($ultimoNumero + 1), 3, '0', STR_PAD_LEFT);
            }
        });
    }
}
