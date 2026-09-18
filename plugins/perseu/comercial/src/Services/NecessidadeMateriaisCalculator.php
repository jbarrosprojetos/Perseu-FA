<?php

namespace Perseu\Comercial\Services;

use Perseu\Comercial\Models\ItemProjetoComponente;
use Perseu\Comercial\Models\Projeto;

/**
 * Necessidade de Materiais (Lista de Compras, Aba P) do Projeto —
 * 2026-09-12, ver handoff `handoff_aba_p.md` e migration
 * `2026_09_12_100000_create_itens_projeto_componentes_table`.
 *
 * **Por que consolidar aqui, e não reaproveitar a conferência "Checar
 * Total" do fluxo Promob**: a conferência bate os XMLs de item contra
 * o XML "000" (total do Projeto) só no MOMENTO da criação — depois
 * disso os Itens passam a existir de forma independente (podem ser
 * excluídos um a um pelo fluxo normal de edição, ver
 * `ProjetoResource::excluirItemAvulso()`), o que quebraria qualquer
 * amarração fixa com o total original do "000". A decisão do usuário
 * (2026-09-12) foi: cada Item continua guardando SEUS PRÓPRIOS
 * componentes (extraídos do XML daquele item específico, ver
 * `ItemProjeto::componentes()`/`PromobXmlParser::componentesParaMateriais()`)
 * — excluir um Item já remove os componentes dele junto
 * (`cascadeOnDelete`) — e a Necessidade de Materiais do Projeto é
 * calculada AQUI, somando os componentes de todos os Itens que
 * existem NESTE MOMENTO, nunca um valor travado no dia da importação.
 *
 * **Consolidação por Referência+Descrição** (decisão do usuário,
 * 2026-09-12): o mesmo material usado em Itens diferentes (ex.: "MDF
 * Branco 18mm" no item 001 e no item 003) vira UMA linha só na lista,
 * com Peças/Quantidade/Custo/Preço somados — útil pra compra (dá pra
 * ver de uma vez quanto comprar de cada material no Projeto inteiro).
 * Perde a rastreabilidade direta "veio de qual Item" na própria linha
 * — se precisar disso, consultar `ItemProjeto::componentes()` de cada
 * Item individualmente. Chave de agrupamento usa Descrição além de
 * Referência porque nem todo componente do Promob tem `REFERENCE`
 * preenchido (ex.: `""` pra alguns tipos de material) — agrupar só por
 * Referência vazia juntaria materiais diferentes na mesma linha.
 */
class NecessidadeMateriaisCalculator
{
    /**
     * @return array<int, array{referencia: string, descricao: string, largura: float, altura: float, profundidade: float, repeticao: int, quantidade: float, custo: float, preco: float}>
     */
    public static function calcular(Projeto $projeto): array
    {
        $componentes = ItemProjetoComponente::query()
            ->whereIn('item_projeto_id', $projeto->itens()->pluck('id'))
            ->get();

        return $componentes
            ->groupBy(fn (ItemProjetoComponente $componente) => $componente->referencia . '|' . $componente->descricao)
            ->map(function ($grupo) {
                $primeiro = $grupo->first();

                return [
                    'referencia'   => (string) $primeiro->referencia,
                    'descricao'    => (string) $primeiro->descricao,
                    'largura'      => (float) $primeiro->largura,
                    'altura'       => (float) $primeiro->altura,
                    'profundidade' => (float) $primeiro->profundidade,
                    'repeticao'    => (int) $grupo->sum('repeticao'),
                    'quantidade'   => round((float) $grupo->sum('quantidade'), 4),
                    'custo'        => round((float) $grupo->sum('custo'), 2),
                    'preco'        => round((float) $grupo->sum('preco'), 2),
                ];
            })
            ->sortBy('descricao')
            ->values()
            ->all();
    }
}
