<?php

namespace Perseu\Comercial\Services;

/**
 * Soma o total de fita de borda necessário por (espessura da fita,
 * cor), pra seção "Bordas" da Lista de Compras — mesma ideia da
 * seção "Chapas"/`PlanoCorteNestingService`, mas sem precisar de
 * nesting: fita é vendida em ROLO (metro linear), então basta somar
 * comprimento de borda ativa em todas as peças, não precisa encaixar
 * em retângulo nenhum.
 *
 * Classe SEM Eloquent/DB, mesmo motivo de `PlanoCorteNestingService`
 * — arrays puros, testável isolado.
 *
 * ## Convenção de pareamento (ver CLAUDE.md, "Estudo do XML do
 * Promob" — validada empiricamente nesta tarefa contra o PDF real
 * "Lista de Compras" do projeto 24-260000, seção "Bordas")
 *
 * **Revisão 2026-09-16 (par estava trocado)**: a primeira versão
 * desta convenção tinha o pareamento invertido — `{1,2}`↔profundidade,
 * `{3,4}`↔largura — só nunca tinha sido conferido POR PEÇA (só a
 * ordem de grandeza do total, ver ressalva no fim do docblock da
 * classe), o que não seria sensível o bastante pra pegar um par
 * trocado. Usuário reportou o tracejado (`PlanoCorteRelatorioService::
 * bordasPorLado()`, mesma convenção) marcando o lado errado da peça
 * — reconferido o campo `PERIMETRO_FITA` (string tipo `"0+770+0+0"`,
 * mesma ordem de `FITA_BORDA_1..4`, valor = comprimento de fita
 * NAQUELE lado) contra `WIDTH`/`DEPTH` peça a peça, não só um caso:
 * 16 ocorrências reais (fixtures + XML "2610015 Olga 86", todas
 * "Prateleira Linear", `FITA_BORDA_2` ativo) — em TODAS, a posição 2
 * de `PERIMETRO_FITA` bateu exatamente com `WIDTH` (ex. 421, 687,
 * 770), nunca com `DEPTH` (189, 288, 513 nos mesmos itens) — prova
 * que `FITA_BORDA_2` (e por tabela todo o par `{1,2}`) tem
 * comprimento de aresta = `WIDTH` = `largura`, não `profundidade`.
 * Convenção CORRIGIDA: `FITA_BORDA_1`/`FITA_BORDA_2` (par) acompanham
 * a `largura` da peça — cada lado ativo desse par soma 1x o
 * comprimento da `largura`. `FITA_BORDA_3`/`FITA_BORDA_4` (par)
 * acompanham a `profundidade` — cada lado ativo soma 1x o comprimento
 * da `profundidade`. Os DOIS lados de cada par têm o MESMO
 * comprimento (são as duas bordas paralelas daquela dimensão) — por
 * isso um par com os dois lados ativos soma 2x aquele comprimento.
 *
 * **Simplificação deliberada**: usa sempre `fita_cor` (ignora
 * `fita_cor_frontal`) — na fixture real usada pra validar este
 * serviço, as duas colunas vinham sempre iguais em toda peça
 * (nenhum caso real de fita frontal com cor DIFERENTE da fita normal
 * na amostra disponível), então não havia como testar a separação
 * frontal/normal com dado real. Se um projeto futuro tiver as duas
 * cores diferentes na mesma peça, esta soma agrupa tudo sob
 * `fita_cor` — revisar se isso passar a importar na prática.
 */
final class PlanoCorteFitasService
{
    /**
     * @param  iterable<int, array{largura: float, profundidade: float, repeticao: int, fita_borda_1: ?float, fita_borda_2: ?float, fita_borda_3: ?float, fita_borda_4: ?float, fita_cor: ?string}>  $componentes
     * @return array<int, array{espessura: float, cor: string, metros: float}>
     */
    public static function gerar(iterable $componentes): array
    {
        $totaisEmMm = [];

        foreach ($componentes as $componente) {
            $repeticao = max(1, (int) $componente['repeticao']);
            $cor = $componente['fita_cor'] ?? '—';

            // Par {1,2} → largura; par {3,4} → profundidade (ver
            // "Revisão 2026-09-16" no docblock da classe).
            $pares = [
                ['lados' => [$componente['fita_borda_1'] ?? null, $componente['fita_borda_2'] ?? null], 'comprimento' => (float) $componente['largura']],
                ['lados' => [$componente['fita_borda_3'] ?? null, $componente['fita_borda_4'] ?? null], 'comprimento' => (float) $componente['profundidade']],
            ];

            foreach ($pares as $par) {
                foreach ($par['lados'] as $espessuraFita) {
                    if ($espessuraFita === null || (float) $espessuraFita <= 0.0) {
                        continue;
                    }

                    $chave = number_format((float) $espessuraFita, 2, '.', '').'|'.$cor;

                    $totaisEmMm[$chave] ??= ['espessura' => (float) $espessuraFita, 'cor' => $cor, 'mm' => 0.0];
                    $totaisEmMm[$chave]['mm'] += $par['comprimento'] * $repeticao;
                }
            }
        }

        return array_values(array_map(
            fn (array $t): array => ['espessura' => $t['espessura'], 'cor' => $t['cor'], 'metros' => round($t['mm'] / 1000, 3)],
            $totaisEmMm
        ));
    }
}
