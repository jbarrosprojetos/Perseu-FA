<?php

namespace Perseu\Comercial\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Perseu\Auditoria\Traits\LogsBusinessActivity;
use Perseu\Comercial\Enums\OrigemComponenteItem;

/**
 * Componente (matéria-prima) de um `ItemProjeto` — chapa, fita,
 * ferragem, puxador etc., extraído do XML do Promob no momento de
 * "Criar Itens" (`ProjetoResource::criarTodosItensPromob()`) ou
 * adicionado manualmente pelo usuário depois. Base da Aba P (Lista de
 * Compras/Necessidade de Materiais) — ver migration
 * `2026_09_12_100000_create_itens_projeto_componentes_table` e
 * handoff `handoff_aba_p.md`.
 *
 * SEM `SoftDeletes` — mesmo motivo de `ItemProjeto` (registro
 * operacional, não um cadastro central auditado): excluído junto do
 * Item pai (`cascadeOnDelete` na migration) e o usuário precisa poder
 * excluir/reajustar linhas livremente na Aba P antes de gerar o
 * documento.
 */
class ItemProjetoComponente extends Model
{
    use LogsBusinessActivity;

    protected $table = 'itens_projeto_componentes';

    protected $fillable = [
        'item_projeto_id',
        'origem',
        // `COMPONENT` original do XML (2026-09-12, ver migration
        // 2026_09_12_120000) — `true` = peça de madeira/painel
        // ("Y"), `false` = ferragem/acessório ("N" sem filhos).
        // Necessário pra `ItemProjeto::metricasPromob()` recalcular
        // ao vivo a MESMA divisão Custo(madeira)/Misc(ferragens) da
        // fórmula original de Custo Unitário.
        'componentizado',
        // `CATEGORY/@DESCRIPTION` de origem no XML (2026-09-12, ver
        // migration 2026_09_12_130000 e docblock de
        // `PromobXmlParser::componentesParaMateriais()`) — numerado
        // ("001 "), nome de ambiente ("Cozinha", "Dormitório"...) ou
        // nome de fabricante/finalidade ("Acessórios", "Hettich",
        // "Processo de Fabricação"...), sem lista fixa. Metadado de
        // agrupamento — NÃO usado pra decidir `componentizado`.
        'categoria',
        'referencia',
        'descricao',
        'largura',
        'altura',
        'profundidade',
        'repeticao',
        'quantidade',
        'custo',
        'preco',
        // Campos do `<REFERENCES>`/`PLATECUTTINGROTATE` do XML do
        // Promob (2026-09-13, ver migration
        // `2026_09_13_100000_add_dados_plano_corte_to_itens_projeto_componentes_table`
        // e docblock de
        // `PromobXmlParser::componentesParaMateriais()`) — pra lista de
        // peças estruturada e, no futuro, o Plano de Corte. Todos
        // nullable — nem toda folha tem `<REFERENCES>` completo.
        'material',
        'cor',
        'espessura',
        'fornecedor',
        'chapa_largura',
        'chapa_comprimento',
        'fita_borda_1',
        'fita_borda_2',
        'fita_borda_3',
        'fita_borda_4',
        'fita_cor',
        'fita_cor_frontal',
        'perimetro_fita',
        'veio_travado',
        // Ver migration `2026_09_17_100000_add_veio_eixo_fixo_to_itens_projeto_componentes_table`
        // e `PromobXmlParser`, "Revisão 2026-09-17" — dimensão crua da
        // peça (`'largura'`|`'profundidade'`|`null`) que fica travada
        // no eixo do veio da chapa. Substitui `veio_travado` nos
        // serviços de nesting (mantido só por compatibilidade).
        'veio_eixo_fixo',
    ];

    protected $casts = [
        'origem'            => OrigemComponenteItem::class,
        'componentizado'    => 'boolean',
        'largura'           => 'decimal:2',
        'altura'            => 'decimal:2',
        'profundidade'      => 'decimal:2',
        'repeticao'         => 'integer',
        'quantidade'        => 'decimal:4',
        'custo'             => 'decimal:2',
        'preco'             => 'decimal:2',
        'espessura'         => 'decimal:2',
        'chapa_largura'     => 'decimal:2',
        'chapa_comprimento' => 'decimal:2',
        'fita_borda_1'      => 'decimal:2',
        'fita_borda_2'      => 'decimal:2',
        'fita_borda_3'      => 'decimal:2',
        'fita_borda_4'      => 'decimal:2',
        'veio_travado'      => 'boolean',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(ItemProjeto::class, 'item_projeto_id');
    }
}
