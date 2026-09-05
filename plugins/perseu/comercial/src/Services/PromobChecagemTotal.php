<?php

namespace Perseu\Comercial\Services;

/**
 * Rotina "Checar Total" do modal de upload Promob (ver CLAUDE.md deste
 * plugin, "Fluxo Promob") — baseada na análise do VBA existente do
 * usuário (`CompararTotalGeral`/`EscreverResumoFixo`/`ColetarComponentes`),
 * que já lê os totais PRONTOS do próprio Promob, sem recalcular nada
 * por conta própria (`PromobXmlParser` só extrai valores já calculados
 * pelo Promob).
 *
 * Resultado PRINCIPAL: as 5 métricas do VBA (Peças, m², Metro Linear,
 * Custo, Misc — ver `PromobXmlParser::metricas()`), somadas em todos
 * os XMLs de item enviados (exceto o "000") e comparadas contra as
 * mesmas 5 métricas do XML "000" — a diferença é `000 MENOS soma das
 * parciais`, mostrada como número direto (não "bateu/não bateu" com
 * tolerância; o usuário decide se o valor é aceitável). Mantido como
 * informação COMPLEMENTAR: a comparação de Custo/Preço "bateu/não
 * bateu com diagnóstico por categoria" já existente antes desta tarefa
 * (`custo_esperado`/`preco_esperado`/`diferencas`).
 */
final class PromobChecagemTotal
{
    /**
     * Diferença de até 1 centavo é tolerada (arredondamento) — usada só
     * na comparação COMPLEMENTAR de Custo/Preço "bateu/não bateu"; a
     * comparação PRINCIPAL das 5 métricas não usa tolerância, mostra a
     * diferença numérica crua (mesmo comportamento do VBA).
     */
    private const TOLERANCIA = 0.01;

    /**
     * Identifica, pelo nome do arquivo, se é o XML "000" (total) ou um
     * XML de item — convenção já mapeada: caracteres 10-12 do nome (1-
     * indexado, ou seja `substr($nome, 9, 3)`) trazem o número de 3
     * dígitos ("000" = total, "001"/"002"/... = item). Se essa posição
     * não tiver 3 dígitos (nome fora do padrão), cai num fallback que
     * procura os primeiros 3 dígitos consecutivos em qualquer lugar do
     * nome.
     */
    private const NUMERO_XML_TOTAL = '000';

    /**
     * @param  array<string, string>  $xmlsPorNomeDeArquivo  nome do arquivo (sem diretório) => conteúdo bruto do XML
     * @return array{
     *     metricas: array{
     *         tem_geral: bool,
     *         quantidade_parciais: int,
     *         parciais: array{pecas: int, m2: float, mlinear: float, custo: float, misc: float},
     *         geral: array{pecas: int, m2: float, mlinear: float, custo: float, misc: float}|null,
     *         diferenca: array{pecas: int, m2: float, mlinear: float, custo: float, misc: float}|null,
     *     },
     *     bateu: bool|null,
     *     custo_esperado: float|null,
     *     preco_esperado: float|null,
     *     custo_calculado: float,
     *     preco_calculado: float,
     *     diferencas: array<int, array{item: string, custo_esperado: float, preco_esperado: float, custo_calculado: float, preco_calculado: float}>,
     * }
     */
    public static function checar(array $xmlsPorNomeDeArquivo): array
    {
        $total = null;
        $itens = [];
        $metricasTotal = null;
        $metricasItens = [];

        foreach ($xmlsPorNomeDeArquivo as $nomeArquivo => $conteudo) {
            $numeroItem = static::numeroItemDoArquivo($nomeArquivo);
            $dados = PromobXmlParser::parse($conteudo);
            $metricas = PromobXmlParser::metricas($conteudo);

            if ($numeroItem === self::NUMERO_XML_TOTAL) {
                $total = $dados;
                $metricasTotal = $metricas;

                continue;
            }

            $itens[$numeroItem] = $dados;
            $metricasItens[$numeroItem] = $metricas;
        }

        return [
            'metricas' => static::compararMetricas($metricasTotal, $metricasItens),
            ...static::compararCustoPreco($total, $itens),
        ];
    }

    /**
     * Comparação PRINCIPAL: as 5 métricas do VBA, soma dos XMLs de item
     * (`parciais`) contra o XML "000" (`geral`) — `diferenca` é sempre
     * `geral MENOS parciais`, métrica a métrica, SEM tolerância (valor
     * cru, igual ao VBA — quem decide se é aceitável é o usuário lendo
     * o resultado). Sem XML "000" enviado, `geral`/`diferenca` ficam
     * `null` e só a soma das parciais é retornada — mesmo comportamento
     * do VBA quando rodado sem a aba geral disponível.
     *
     * @param  array{pecas: int, m2: float, mlinear: float, custo: float, misc: float}|null  $metricasTotal
     * @param  array<string, array{pecas: int, m2: float, mlinear: float, custo: float, misc: float}>  $metricasItens
     * @return array{tem_geral: bool, quantidade_parciais: int, parciais: array{pecas: int, m2: float, mlinear: float, custo: float, misc: float}, geral: array{pecas: int, m2: float, mlinear: float, custo: float, misc: float}|null, diferenca: array{pecas: int, m2: float, mlinear: float, custo: float, misc: float}|null}
     */
    private static function compararMetricas(?array $metricasTotal, array $metricasItens): array
    {
        $chaves = ['pecas', 'm2', 'mlinear', 'custo', 'misc'];

        // Soma/diferença calculadas em cima dos valores CRUS (sem
        // arredondar), só arredondando no final — arredondar cada
        // métrica por arquivo ANTES de somar/subtrair introduzia uma
        // "diferença" artificial de até 1-2 centavos/centímetros
        // (achado real, com os mesmos 3 XMLs de exemplo o `mlinear`
        // dava 0,01 de diferença por causa dessa dupla rolagem, mesmo
        // os dados sendo idênticos).
        $parciaisCru = [];

        foreach ($chaves as $chave) {
            $parciaisCru[$chave] = array_sum(array_column($metricasItens, $chave));
        }

        $diferenca = null;

        if ($metricasTotal !== null) {
            $diferenca = [];

            foreach ($chaves as $chave) {
                $diferenca[$chave] = static::arredondarMetrica($chave, $metricasTotal[$chave] - $parciaisCru[$chave]);
            }
        }

        return [
            'tem_geral'           => $metricasTotal !== null,
            'quantidade_parciais' => count($metricasItens),
            'parciais'            => static::arredondarMetricas($chaves, $parciaisCru),
            'geral'               => $metricasTotal === null ? null : static::arredondarMetricas($chaves, $metricasTotal),
            'diferenca'           => $diferenca,
        ];
    }

    /**
     * "Peças" arredonda pro inteiro mais próximo (já deveria ser inteiro
     * pela própria soma de `REPETITION`, mas `round()` cobre qualquer
     * resíduo de ponto flutuante); as demais, 2 casas decimais.
     */
    private static function arredondarMetrica(string $chave, float $valor): int|float
    {
        return $chave === 'pecas' ? (int) round($valor) : round($valor, 2);
    }

    /**
     * @param  array<int, string>  $chaves
     * @param  array<string, float>  $valores
     * @return array<string, int|float>
     */
    private static function arredondarMetricas(array $chaves, array $valores): array
    {
        $arredondadas = [];

        foreach ($chaves as $chave) {
            $arredondadas[$chave] = static::arredondarMetrica($chave, $valores[$chave]);
        }

        return $arredondadas;
    }

    /**
     * Comparação COMPLEMENTAR já existente antes desta tarefa —
     * Custo/Preço "bateu/não bateu com tolerância", com diagnóstico por
     * CATEGORY quando não bate (ver docblock da classe). Sem XML "000",
     * não há como comparar — `bateu`/`custo_esperado`/`preco_esperado`
     * ficam `null`, sem lançar exceção (a rotina PRINCIPAL das 5
     * métricas já cobre o caso "sem XML geral").
     *
     * @param  array{custo: float, preco: float, ambientes: array<int, mixed>}|null  $total
     * @param  array<string, array{custo: float, preco: float, ambientes: array<int, mixed>}>  $itens
     * @return array{bateu: bool|null, custo_esperado: float|null, preco_esperado: float|null, custo_calculado: float, preco_calculado: float, diferencas: array<int, mixed>}
     */
    private static function compararCustoPreco(?array $total, array $itens): array
    {
        $custoCalculado = round(array_sum(array_column($itens, 'custo')), 2);
        $precoCalculado = round(array_sum(array_column($itens, 'preco')), 2);

        if ($total === null) {
            return [
                'bateu'           => null,
                'custo_esperado'  => null,
                'preco_esperado'  => null,
                'custo_calculado' => $custoCalculado,
                'preco_calculado' => $precoCalculado,
                'diferencas'      => [],
            ];
        }

        $custoEsperado = round($total['custo'], 2);
        $precoEsperado = round($total['preco'], 2);

        $bateu = abs($custoCalculado - $custoEsperado) <= self::TOLERANCIA
            && abs($precoCalculado - $precoEsperado) <= self::TOLERANCIA;

        return [
            'bateu'           => $bateu,
            'custo_esperado'  => $custoEsperado,
            'preco_esperado'  => $precoEsperado,
            'custo_calculado' => $custoCalculado,
            'preco_calculado' => $precoCalculado,
            'diferencas'      => $bateu ? [] : static::diagnosticarDiferencas($total, $itens),
        ];
    }

    /**
     * @param  array{ambientes: array<int, array{categorias: array<int, array{numero_item: string, custo: float, preco: float}>}>}  $total
     * @param  array<string, array{ambientes: array<int, array{categorias: array<int, array{numero_item: string, custo: float, preco: float}>}>}>  $itens
     * @return array<int, array{item: string, custo_esperado: float, preco_esperado: float, custo_calculado: float, preco_calculado: float}>
     */
    private static function diagnosticarDiferencas(array $total, array $itens): array
    {
        $diferencas = [];

        foreach ($total['ambientes'] as $ambiente) {
            foreach ($ambiente['categorias'] as $categoriaTotal) {
                $numeroItem = $categoriaTotal['numero_item'];

                // Categorias sem XML de item correspondente enviado (ex.:
                // "Acessórios"/"Hettich"/"Processo de Fabricação", que no
                // "000" aparecem agrupadas à parte, não por item) — sem
                // como comparar, ignora.
                if (! array_key_exists($numeroItem, $itens)) {
                    continue;
                }

                $categoriaItem = static::localizarCategoria($itens[$numeroItem], $numeroItem);

                if ($categoriaItem === null) {
                    continue;
                }

                $custoEsperado = round($categoriaTotal['custo'], 2);
                $precoEsperado = round($categoriaTotal['preco'], 2);
                $custoCalculado = round($categoriaItem['custo'], 2);
                $precoCalculado = round($categoriaItem['preco'], 2);

                $bateu = abs($custoCalculado - $custoEsperado) <= self::TOLERANCIA
                    && abs($precoCalculado - $precoEsperado) <= self::TOLERANCIA;

                if (! $bateu) {
                    $diferencas[] = [
                        'item'            => $numeroItem,
                        'custo_esperado'  => $custoEsperado,
                        'preco_esperado'  => $precoEsperado,
                        'custo_calculado' => $custoCalculado,
                        'preco_calculado' => $precoCalculado,
                    ];
                }
            }
        }

        return $diferencas;
    }

    /**
     * @param  array{ambientes: array<int, array{categorias: array<int, array{numero_item: string, custo: float, preco: float}>}>}  $dadosDoItem
     * @return array{numero_item: string, custo: float, preco: float}|null
     */
    private static function localizarCategoria(array $dadosDoItem, string $numeroItem): ?array
    {
        foreach ($dadosDoItem['ambientes'] as $ambiente) {
            foreach ($ambiente['categorias'] as $categoria) {
                if ($categoria['numero_item'] === $numeroItem) {
                    return $categoria;
                }
            }
        }

        return null;
    }

    private static function numeroItemDoArquivo(string $nomeArquivo): string
    {
        $nomeSemExtensao = pathinfo($nomeArquivo, PATHINFO_FILENAME);
        $miolo = substr($nomeSemExtensao, 9, 3);

        if (preg_match('/^\d{3}$/', $miolo) === 1) {
            return $miolo;
        }

        if (preg_match('/(\d{3})(?!\d)/', $nomeArquivo, $match) === 1) {
            return $match[1];
        }

        throw new \RuntimeException("Não foi possível identificar o número do item a partir do nome do arquivo \"{$nomeArquivo}\".");
    }
}
