<?php

namespace Perseu\Comercial\Services;

use Illuminate\Support\Facades\Process;

/**
 * Motor de encaixe ALTERNATIVO do Plano de Corte — em vez do algoritmo
 * próprio (`PlanoCorteNestingService`, INTOCADO por esta classe), delega
 * o encaixe físico ao `fontanf/packingsolver` (MIT, C++), modo
 * `rectangleguillotine` — mesma exigência de guilhotina de ponta a
 * ponta que o nosso algoritmo, com kerf/veio-travado/limpeza-de-bordas
 * nativos do próprio solver.
 *
 * ## Decisão de arquitetura (2026-09-14) — dropdown "Otimizadores"
 *
 * A pedido explícito do usuário ("podemos manter o nosso como está...
 * podemos criar um dropdown, otimizadores... Nativa... e Packing
 * Solver... ao longo do projeto podemos aprender mais e decidir qual
 * ficará"): esta é uma alternativa EXPLORATÓRIA, ativada só quando o
 * usuário escolhe "Packing Solver" no dropdown da tela de "Produção"
 * (`EditProjeto::getFormActions()`). `PlanoCorteNestingService` continua
 * sendo o padrão e não foi alterado em nada por esta tarefa. Ver
 * CLAUDE.md, "Investigação (2026-09-14): trocar o motor de nesting...",
 * pro histórico completo da pesquisa que levou a esta classe.
 *
 * Mesma assinatura e MESMO formato de retorno de
 * `PlanoCorteNestingService::gerar()` — substituto "plug-and-play" pro
 * `PlanoCorteRelatorioService`, que só decide QUAL das duas chamar
 * (nunca as duas ao mesmo tempo).
 *
 * ## Como funciona — "knapsack sequencial de 1 chapa por vez"
 *
 * O `rectangleguillotine` do packingsolver não tem um objetivo pronto
 * de "encher a 1ª chapa e deixar o resto pra 2ª" (`BinPackingWithLeftovers`
 * existe no código-fonte dele, mas só pro solver `rectangle`, não pro
 * `rectangleguillotine` — confirmado lendo `src/rectangleguillotine/
 * optimize.cpp`). Rodar `--objective bin-packing` direto MINIMIZA Nº DE
 * CHAPAS mas espalha as peças entre elas (medido: pior que o nativo).
 * A alternativa medida e validada nesta pesquisa: repetir `--objective
 * knapsack` contra 1 chapa só (`COPIES=1`) com o que sobrou de peças,
 * removendo as colocadas e repetindo até esvaziar o grupo — mesmo
 * princípio do `encaixarBlocoSequencial()` do algoritmo nativo (rodada
 * 9), só que delegado ao solver externo. `PROFIT` de cada peça não é
 * informado (o solver usa `largura×altura` como padrão quando ausente
 * — exatamente "maximizar área encaixada", o que a Knapsack já faz
 * sozinha).
 *
 * Kerf vira `--cut-thickness`; limpeza de bordas vira
 * `LEFT_TRIM`/`RIGHT_TRIM`/`BOTTOM_TRIM`/`TOP_TRIM` da própria chapa
 * (dimensão CHEIA da chapa em `bins.csv`, não mais pré-encolhida) —
 * as coordenadas X/Y que o solver devolve JÁ vêm deslocadas pela
 * margem, sem precisar somar `limpezaBordas` de volta (confirmado
 * empiricamente nesta tarefa: `LEFT_TRIM=10` fez a 1ª peça nascer em
 * `X=10`, não `X=0`). Veio travado (`pode_rotacionar=false`) vira
 * `ORIENTED=1` (peça travada, sem giro) — inverso do booleano, o
 * solver usa "1 = orientação fixa".
 *
 * ## Limitações conhecidas (honestas, não escondidas)
 * - O solver só aceita coordenadas INTEIRAS em mm (`std::stol` — trunca
 *   qualquer casa decimal) — largura/comprimento/kerf/limpeza de bordas
 *   são arredondados pra mm inteiro antes de escrever os CSVs. Peças
 *   com medidas fracionadas de mm perdem essa fração aqui (o algoritmo
 *   nativo não tem essa limitação).
 * - Cada chapa é resolvida com um `--time-limit` curto (configurável,
 *   `config('comercial.packingsolver.tempo_limite_segundos')`) — em
 *   projetos com MUITAS peças por grupo de material, o resultado pode
 *   não ser o ótimo absoluto do solver, só o melhor que ele achou no
 *   tempo dado (mesma natureza heurística/aproximada do algoritmo
 *   nativo, só que por um motivo diferente).
 * - Depende de um BINÁRIO EXTERNO compilado (`fontanf/packingsolver`,
 *   modo `rectangleguillotine`) configurado via
 *   `PACKINGSOLVER_BINARIO` no `.env` da raiz do Perseu-FA — sem isso
 *   (ou se o caminho não existir/não for executável), `gerar()` lança
 *   `\RuntimeException` ANTES de processar qualquer grupo (falha
 *   rápida, com mensagem clara pro usuário, em vez de um erro tardio
 *   no meio do processamento). O binário compilado nesta pesquisa é
 *   LINUX — o ambiente de produção real do usuário (Windows, `C:\Perseu
 *   \PerseuFA_comercial`) ainda precisa compilar/obter sua própria
 *   versão (ver CLAUDE.md, mesma seção da investigação, "Como compilar
 *   no Windows").
 */
final class PlanoCortePackingSolverService
{
    /**
     * @param  iterable<int, array{id: int, referencia: string, descricao: string, largura: float, profundidade: float, repeticao: int, material: ?string, cor: ?string, espessura: ?float, fornecedor: ?string, chapa_largura: ?float, chapa_comprimento: ?float, veio_travado: bool, veio_eixo_fixo: ?string}>  $componentes
     * @return array{
     *     grupos: array<int, array<string, mixed>>,
     *     nao_classificados: array<int, array{componente_id: int, descricao: string, motivo: string}>
     * }
     */
    public static function gerar(iterable $componentes, float $kerf, float $limpezaBordas, string $tipoEquipamento = 'serra'): array
    {
        $binario = static::binario();

        $porGrupo = [];
        $naoClassificados = [];

        // Mesma expansão/convenção de eixo do veio que o algoritmo
        // nativo (`PlanoCorteNestingService::gerar()`) — ver docblock
        // daquela classe, "Convenção de eixos — veio" e "Revisão
        // 2026-09-17" (PLATECUTTINGROTATE trava uma orientação por
        // peça — "Y" e "N" são travas OPOSTAS, nenhuma das duas é
        // livre; só `veio_eixo_fixo===null` é). Duplicado aqui (em vez
        // de reaproveitado) de propósito: o pedido do usuário foi
        // manter o nativo 100% intocado, então esta classe não
        // depende dele nem o referencia.
        foreach ($componentes as $componente) {
            if ((float) $componente['largura'] <= 0.0 || (float) $componente['profundidade'] <= 0.0) {
                continue;
            }

            if (
                $componente['material'] === null
                || $componente['cor'] === null
                || $componente['espessura'] === null
                || $componente['chapa_largura'] === null
                || $componente['chapa_comprimento'] === null
            ) {
                $naoClassificados[] = [
                    'componente_id' => $componente['id'],
                    'descricao'     => $componente['descricao'],
                    'motivo'        => 'Sem material/cor/espessura/chapa completos (componente extraído antes do enriquecimento de campos, ou XML sem REFERENCES pra essa peça) — reimporte o Promob pra corrigir.',
                ];

                continue;
            }

            $chave = implode('|', [
                $componente['material'],
                $componente['cor'],
                number_format((float) $componente['espessura'], 2, '.', ''),
                (string) $componente['fornecedor'],
                number_format((float) $componente['chapa_largura'], 2, '.', ''),
                number_format((float) $componente['chapa_comprimento'], 2, '.', ''),
            ]);

            $porGrupo[$chave]['meta'] ??= [
                'material'          => $componente['material'],
                'cor'               => $componente['cor'],
                'espessura'         => (float) $componente['espessura'],
                'fornecedor'        => $componente['fornecedor'],
                'chapa_largura'     => (float) $componente['chapa_largura'],
                'chapa_comprimento' => (float) $componente['chapa_comprimento'],
            ];

            $veioNoEixoLargura = $porGrupo[$chave]['meta']['chapa_largura'] >= $porGrupo[$chave]['meta']['chapa_comprimento'];

            $repeticao = max(1, (int) $componente['repeticao']);

            // Qual dimensão CRUA da peça fica no eixo do veio da chapa
            // — ver "Revisão 2026-09-17" no docblock de
            // `PlanoCorteNestingService`. `null` = sem veio, livre.
            $eixoFixo = $componente['veio_eixo_fixo'] ?? null;
            $dimNoEixoChapa = $eixoFixo === 'largura' ? (float) $componente['largura'] : (float) $componente['profundidade'];
            $dimNaOutraPeca = $eixoFixo === 'largura' ? (float) $componente['profundidade'] : (float) $componente['largura'];
            $podeRotacionar = $eixoFixo === null;

            for ($instancia = 1; $instancia <= $repeticao; $instancia++) {
                $porGrupo[$chave]['pecas'][] = [
                    'componente_id' => $componente['id'],
                    'instancia'     => $instancia,
                    'referencia'    => $componente['referencia'],
                    'descricao'     => $componente['descricao'],
                    'largura_natural'     => $veioNoEixoLargura ? $dimNoEixoChapa : $dimNaOutraPeca,
                    'comprimento_natural' => $veioNoEixoLargura ? $dimNaOutraPeca : $dimNoEixoChapa,
                    'pode_rotacionar'     => $podeRotacionar,
                ];
            }
        }

        $grupos = [];

        foreach ($porGrupo as $chave => $grupo) {
            $usavelLargura = $grupo['meta']['chapa_largura'] - (2 * $limpezaBordas);
            $usavelComprimento = $grupo['meta']['chapa_comprimento'] - (2 * $limpezaBordas);

            if ($usavelLargura <= 0.0 || $usavelComprimento <= 0.0) {
                foreach ($grupo['pecas'] as $peca) {
                    $naoClassificados[] = [
                        'componente_id' => $peca['componente_id'],
                        'descricao'     => $peca['descricao'],
                        'motivo'        => 'Limpeza de bordas configurada deixou a chapa sem área útil.',
                    ];
                }

                continue;
            }

            $pool = [];
            $semEncaixe = [];

            foreach ($grupo['pecas'] as $peca) {
                $cabe = ($peca['largura_natural'] <= $usavelLargura + 0.001 && $peca['comprimento_natural'] <= $usavelComprimento + 0.001)
                    || ($peca['pode_rotacionar'] && $peca['comprimento_natural'] <= $usavelLargura + 0.001 && $peca['largura_natural'] <= $usavelComprimento + 0.001);

                if ($cabe) {
                    $pool[] = $peca;
                } else {
                    $semEncaixe[] = $peca;
                }
            }

            foreach ($semEncaixe as $peca) {
                $naoClassificados[] = [
                    'componente_id' => $peca['componente_id'],
                    'descricao'     => $peca['descricao'],
                    'motivo'        => 'Peça maior que a área útil da chapa em qualquer orientação permitida.',
                ];
            }

            if ($pool === []) {
                continue;
            }

            $chapas = static::encaixarGrupoViaPackingSolver(
                $binario,
                $pool,
                $grupo['meta']['chapa_largura'],
                $grupo['meta']['chapa_comprimento'],
                $usavelLargura * $usavelComprimento,
                $kerf,
                $limpezaBordas,
                $naoClassificados,
            );

            if ($chapas === []) {
                continue;
            }

            $grupos[] = [
                'chave'             => $chave,
                'material'          => $grupo['meta']['material'],
                'cor'               => $grupo['meta']['cor'],
                'espessura'         => $grupo['meta']['espessura'],
                'fornecedor'        => $grupo['meta']['fornecedor'],
                'chapa_largura'     => $grupo['meta']['chapa_largura'],
                'chapa_comprimento' => $grupo['meta']['chapa_comprimento'],
                'chapas'            => $chapas,
            ];
        }

        return [
            'grupos'            => $grupos,
            'nao_classificados' => $naoClassificados,
        ];
    }

    private static function binario(): string
    {
        $binario = config('comercial.packingsolver.binario');

        if (! is_string($binario) || $binario === '' || ! is_file($binario) || ! is_executable($binario)) {
            throw new \RuntimeException(
                "O binário do Packing Solver não foi encontrado ou não é executável em '".($binario ?: '(não configurado)')."'. ".
                'Configure PACKINGSOLVER_BINARIO no .env do Perseu-FA apontando para o executável compilado do fontanf/packingsolver '.
                "(modo 'rectangleguillotine') — ver CLAUDE.md deste plugin, seção do Otimizador Packing Solver. Use o otimizador Nativa enquanto isso.",
            );
        }

        return $binario;
    }

    /**
     * "Knapsack sequencial de 1 chapa por vez" — ver docblock da classe.
     *
     * @param  array<int, array{componente_id: int, instancia: int, referencia: string, descricao: string, largura_natural: float, comprimento_natural: float, pode_rotacionar: bool}>  $pool
     * @param  array<int, array{componente_id: int, descricao: string, motivo: string}>  $naoClassificados  (por referência)
     * @return array<int, array{numero: int, aproveitamento: float, area_util_mm2: float, area_ocupada_mm2: float, quantidade_cortes: int, pecas: array<int, array{componente_id: int, instancia: int, referencia: string, descricao: string, x: float, y: float, largura_corte: float, comprimento_corte: float, rotacionado: bool}>}>
     */
    private static function encaixarGrupoViaPackingSolver(
        string $binario,
        array $pool,
        float $chapaLargura,
        float $chapaComprimento,
        float $areaUtilMm2,
        float $kerf,
        float $limpezaBordas,
        array &$naoClassificados,
    ): array {
        $tempoLimite = (float) config('comercial.packingsolver.tempo_limite_segundos', 8);
        $kerfInt = (int) round($kerf);
        $trimInt = (int) round($limpezaBordas);
        $chapaLarguraInt = (int) round($chapaLargura);
        $chapaComprimentoInt = (int) round($chapaComprimento);

        $dir = sys_get_temp_dir().'/plano-corte-ps-'.uniqid('', true);
        mkdir($dir, 0777, true);

        $chapas = [];
        $numero = 0;
        $maxIteracoes = count($pool) + 5;

        try {
            while ($pool !== [] && $maxIteracoes-- > 0) {
                $itemsCsv = "WIDTH,HEIGHT,ORIENTED,COPIES\n";

                foreach ($pool as $peca) {
                    $itemsCsv .= implode(',', [
                        (int) round($peca['largura_natural']),
                        (int) round($peca['comprimento_natural']),
                        $peca['pode_rotacionar'] ? 0 : 1,
                        1,
                    ])."\n";
                }

                $binsCsv = "WIDTH,HEIGHT,COPIES,LEFT_TRIM,RIGHT_TRIM,BOTTOM_TRIM,TOP_TRIM\n"
                    ."{$chapaLarguraInt},{$chapaComprimentoInt},1,{$trimInt},{$trimInt},{$trimInt},{$trimInt}\n";

                $itemsPath = $dir.'/items.csv';
                $binsPath = $dir.'/bins.csv';
                $solutionPath = $dir.'/solution.csv';

                file_put_contents($itemsPath, $itemsCsv);
                file_put_contents($binsPath, $binsCsv);
                @unlink($solutionPath);

                // SEM "--number-of-stages-unlimited" de propósito —
                // ver docblock da classe, "Limitações conhecidas":
                // confirmado nesta pesquisa (2026-09-14) que essa opção
                // faz o binário quebrar com um erro interno
                // ("wrong item dimensions") em instâncias pequenas
                // (2 peças). Sem a opção, o solver usa o próprio limite
                // de estágios padrão — testado contra os 5 grupos reais
                // da fixture sem regressão de aproveitamento nem
                // travamento.
                $resultado = Process::timeout((int) $tempoLimite + 10)->run([
                    $binario,
                    '-i', $itemsPath,
                    '-b', $binsPath,
                    '--objective', 'knapsack',
                    '--cut-thickness', (string) $kerfInt,
                    '-c', $solutionPath,
                    '-t', (string) $tempoLimite,
                ]);

                if (! $resultado->successful() || ! is_file($solutionPath)) {
                    // Motor externo falhou pra esta chapa — o resto do
                    // pool que sobrou nesta iteração vira "não
                    // classificado" (ver docblock da classe,
                    // "Limitações conhecidas") em vez de travar num
                    // loop infinito.
                    $detalheErro = trim($resultado->errorOutput()) !== '' ? trim($resultado->errorOutput()) : 'sem detalhe do erro';

                    foreach ($pool as $peca) {
                        $naoClassificados[] = [
                            'componente_id' => $peca['componente_id'],
                            'descricao'     => $peca['descricao'],
                            'motivo'        => "O motor Packing Solver falhou ao processar esta peça ({$detalheErro}).",
                        ];
                    }

                    break;
                }

                [$pecasColocadas, $cortes] = static::interpretarSolucao(file_get_contents($solutionPath), $pool);

                if ($pecasColocadas === []) {
                    // Nenhuma peça coube nesta chapa — evita loop
                    // infinito: o que sobrou vira "não classificado".
                    foreach ($pool as $peca) {
                        $naoClassificados[] = [
                            'componente_id' => $peca['componente_id'],
                            'descricao'     => $peca['descricao'],
                            'motivo'        => 'O otimizador Packing Solver não conseguiu encaixar esta peça em nenhuma chapa nova.',
                        ];
                    }

                    break;
                }

                $numero++;
                $indicesColocados = array_column($pecasColocadas, '_pool_index');
                $areaOcupada = array_sum(array_map(fn (array $p) => $p['largura_corte'] * $p['comprimento_corte'], $pecasColocadas));

                // `_pool_index` só serve pra remover do pool abaixo —
                // não faz parte do formato de retorno público (mesmas
                // chaves que `PlanoCorteNestingService` devolve).
                $pecasSemIndice = array_map(function (array $p): array {
                    unset($p['_pool_index']);

                    return $p;
                }, $pecasColocadas);

                $chapas[] = [
                    'numero'            => $numero,
                    'aproveitamento'    => $areaUtilMm2 > 0.0 ? round($areaOcupada / $areaUtilMm2 * 100, 1) : 0.0,
                    'area_util_mm2'     => $areaUtilMm2,
                    'area_ocupada_mm2'  => $areaOcupada,
                    'quantidade_cortes' => $cortes,
                    'pecas'             => $pecasSemIndice,
                ];

                $pool = array_values(array_filter($pool, fn ($peca, $i) => ! in_array($i, $indicesColocados, true), ARRAY_FILTER_USE_BOTH));
            }
        } finally {
            @unlink($dir.'/items.csv');
            @unlink($dir.'/bins.csv');
            @unlink($dir.'/solution.csv');
            @rmdir($dir);
        }

        return $chapas;
    }

    /**
     * Interpreta o `solution.csv` (formato de árvore de cortes de
     * guilhotina do packingsolver) — ver docblock da classe.
     * "Folha" = qualquer linha cujo NODE_ID nunca aparece como PARENT
     * de nenhuma outra (genérico o bastante pra cobrir TYPE>=0 [peça
     * real], -1 [sobra] e -3 [sobra reservada/"leftover"] sem precisar
     * saber de antemão quais tipos de nó são sempre folha). Nº de
     * cortes = nº de folhas - 1 (teorema de árvore de cortes de
     * guilhotina, verificado empiricamente nesta pesquisa).
     *
     * @param  array<int, array{componente_id: int, instancia: int, referencia: string, descricao: string, largura_natural: float, comprimento_natural: float, pode_rotacionar: bool}>  $pool
     * @return array{0: array<int, array{componente_id: int, instancia: int, referencia: string, descricao: string, x: float, y: float, largura_corte: float, comprimento_corte: float, rotacionado: bool, _pool_index: int}>, 1: int}
     */
    private static function interpretarSolucao(string $csv, array $pool): array
    {
        $linhas = array_values(array_filter(array_map('str_getcsv', explode("\n", trim($csv)))));

        if ($linhas === []) {
            return [[], 0];
        }

        $cabecalho = array_shift($linhas);
        $linhas = array_map(fn (array $l) => array_combine($cabecalho, $l), $linhas);

        $porChapa = [];

        foreach ($linhas as $linha) {
            $porChapa[$linha['PLATE_ID']][] = $linha;
        }

        // Só 1 chapa por vez (COPIES=1 em bins.csv) — pega a primeira
        // (única) na prática.
        $plateRows = reset($porChapa);

        if ($plateRows === false) {
            return [[], 0];
        }

        $ehPai = [];

        foreach ($plateRows as $linha) {
            if ($linha['PARENT'] !== '') {
                $ehPai[$linha['PARENT']] = true;
            }
        }

        $folhas = array_filter($plateRows, fn (array $l) => ! isset($ehPai[$l['NODE_ID']]));
        $cortes = max(0, count($folhas) - 1);

        $pecasColocadas = [];

        foreach ($folhas as $folha) {
            $tipo = (int) $folha['TYPE'];

            if ($tipo < 0 || ! isset($pool[$tipo])) {
                continue;
            }

            $peca = $pool[$tipo];
            $wColocado = (float) $folha['WIDTH'];
            $hColocado = (float) $folha['HEIGHT'];

            $rotacionado = abs($wColocado - $peca['largura_natural']) > abs($wColocado - $peca['comprimento_natural']);

            $pecasColocadas[] = [
                'componente_id' => $peca['componente_id'],
                'instancia'     => $peca['instancia'],
                'referencia'    => $peca['referencia'],
                'descricao'     => $peca['descricao'],
                'x'             => (float) $folha['X'],
                'y'             => (float) $folha['Y'],
                'largura_corte'     => $wColocado,
                'comprimento_corte' => $hColocado,
                'rotacionado'       => $rotacionado,
                '_pool_index'       => $tipo,
            ];
        }

        return [$pecasColocadas, $cortes];
    }
}
