<?php

namespace Perseu\Comercial\Services;

use Perseu\Comercial\Models\CondicaoFinanceira;
use Perseu\Comercial\Models\Projeto;

/**
 * Extraído de `ProjetoResource::calcularFatorPrice()` /
 * `calcularCondicoesFinanceirasProjeto()` (ver CLAUDE.md, "Condições
 * Financeiras") para ser reaproveitado fora do form — usado pela
 * geração de Documentos (marcador `%Condicoes_Financeiras%`, ver
 * CLAUDE.md "Documentos — templates de Excel com marcadores de
 * texto"). Diferença proposital em relação à versão do form: aqui lê
 * SEMPRE o `condicao_financeira_id` já PERSISTIDO no Projeto
 * (`$projeto->condicao_financeira_id`), nunca um `Get $get` de estado
 * de formulário ainda não salvo — faz sentido porque a geração de
 * documento só acontece depois que o Projeto já foi salvo (o botão
 * "Documentos" só aparece com o Projeto já existente).
 *
 * IMPORTANTE: se um dia a fórmula do form (`ProjetoResource`) mudar,
 * replicar a mudança aqui também — não há um único ponto de verdade
 * hoje porque a versão do form depende de `Get $get` (estado de tela)
 * e esta aqui depende só do Model, então não dá pra simplesmente
 * chamar uma a partir da outra sem introduzir acoplamento estranho
 * entre a Resource e este Service.
 */
class CondicoesFinanceirasCalculator
{
    public static function calcularFatorPrice(float $taxaMensalPercentual, int $qtdeParcelas): float
    {
        if ($qtdeParcelas <= 0) {
            return 0.0;
        }

        $i = $taxaMensalPercentual / 100;

        if ($i <= 0) {
            return 1 / $qtdeParcelas;
        }

        return $i / (1 - (1 + $i) ** (-$qtdeParcelas));
    }

    /**
     * @return array{total_geral: float, condicao: ?CondicaoFinanceira, qtde_parcelas: ?int, valor_entrada: ?float, valor_parcela: ?float}
     */
    public static function calcular(Projeto $projeto): array
    {
        $totalGeral = (float) ($projeto->itens()->sum('valor_total') ?? 0);
        $condicao = $projeto->condicao_financeira_id
            ? CondicaoFinanceira::find($projeto->condicao_financeira_id)
            : null;

        if (! $condicao) {
            return [
                'total_geral'   => $totalGeral,
                'condicao'      => null,
                'qtde_parcelas' => null,
                'valor_entrada' => null,
                'valor_parcela' => null,
            ];
        }

        $valorEntrada = round($totalGeral * ((float) $condicao->porcentagem_entrada / 100), 2);
        $valorFinanciado = $totalGeral - $valorEntrada;
        $qtdeParcelas = (int) ($condicao->qtde_parcelas ?? 1);
        $fatorPrice = self::calcularFatorPrice((float) $condicao->taxa_mensal, $qtdeParcelas);

        return [
            'total_geral'   => $totalGeral,
            'condicao'      => $condicao,
            'qtde_parcelas' => $qtdeParcelas,
            'valor_entrada' => $valorEntrada,
            'valor_parcela' => round($valorFinanciado * $fatorPrice, 2),
        ];
    }
}
