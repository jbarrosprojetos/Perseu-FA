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
     * Convenção REAL do nome de arquivo, confirmada pelo usuário
     * (2026-09-05): os primeiros 7 dígitos são o Número do Projeto,
     * seguidos de "<espaço>-<espaço>", seguidos de um código de 3
     * dígitos que é o Número do Item — `NUMERO_XML_TOTAL` quando esse
     * código é o XML do Projeto Geral/consolidado (só conferência, não
     * um item de verdade). O resto do nome (depois do número do item)
     * é uma descrição livre, não validada. Ex.: "2630001 - 001
     * Superior.xml" → Projeto "2630001", Item "001". Ver
     * `identificarArquivo()`.
     */
    private const NUMERO_XML_TOTAL = '000';

    /**
     * `^(\d{7})\s*-\s*(\d{3})` — 7 dígitos, hífen (com espaços
     * flexíveis ao redor, tolerando variações de digitação), 3
     * dígitos, ancorado no INÍCIO do nome (sem extensão). O resto do
     * nome (descrição livre) não é validado.
     */
    private const PADRAO_NOME_ARQUIVO = '/^(\d{7})\s*-\s*(\d{3})/';

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
            $numeroItem = static::identificarArquivo($nomeArquivo)['numero_item'];
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

    /**
     * @return array{numero_projeto: string, numero_item: string}
     */
    public static function identificarArquivo(string $nomeArquivo): array
    {
        $nomeSemExtensao = pathinfo($nomeArquivo, PATHINFO_FILENAME);

        if (preg_match(self::PADRAO_NOME_ARQUIVO, $nomeSemExtensao, $match) !== 1) {
            throw new \RuntimeException("O nome do arquivo \"{$nomeArquivo}\" não segue o padrão esperado (\"NNNNNNN - NNN descrição.xml\", 7 dígitos do Projeto + 3 dígitos do Item) — não foi possível identificar o Projeto/Item.");
        }

        return [
            'numero_projeto' => $match[1],
            'numero_item'    => $match[2],
        ];
    }

    /**
     * Parte 1 do fluxo Promob (2026-09-05): confere se TODOS os
     * arquivos enviados pertencem ao Projeto que está sendo editado —
     * lê só o NOME do arquivo, sem abrir/parsear o XML (mais barato, e
     * suficiente pra essa checagem). Decisão deliberada: rejeita o
     * LOTE INTEIRO se qualquer arquivo estiver errado (nome fora do
     * padrão OU de outro Projeto), em vez de descartar só os arquivos
     * problemáticos e seguir com os válidos — ver CLAUDE.md, "Fluxo
     * Promob", pela justificativa completa (evita uma checagem
     * "silenciosamente incompleta", que pareceria confiável sem ser).
     *
     * @param  array<int, string>  $nomesDeArquivos
     * @return array<int, string> mensagens de erro (vazio = todos os arquivos são válidos)
     */
    public static function validarNomesDeArquivos(array $nomesDeArquivos, string $numeroProjetoAtual): array
    {
        $erros = [];

        foreach ($nomesDeArquivos as $nomeArquivo) {
            try {
                $identificacao = static::identificarArquivo($nomeArquivo);
            } catch (\Throwable $e) {
                $erros[] = $e->getMessage();

                continue;
            }

            if ($identificacao['numero_projeto'] !== $numeroProjetoAtual) {
                $erros[] = "O arquivo \"{$nomeArquivo}\" pertence ao Projeto {$identificacao['numero_projeto']}, mas você está editando o Projeto {$numeroProjetoAtual}.";
            }
        }

        return $erros;
    }

    /**
     * Existe, entre os arquivos informados, um XML "000" (Projeto
     * Geral) que também pertence ao Projeto atual? Usado só pra
     * decidir se os botões "Checar Total"/"Criar Itens" ficam
     * habilitados — nomes de arquivo fora do padrão ou de outro
     * Projeto são simplesmente ignorados aqui (não é o lugar de
     * mostrar o erro pro usuário; isso é papel de
     * `validarNomesDeArquivos()`, chamado só quando o botão é
     * clicado de fato).
     *
     * @param  array<int, string>  $nomesDeArquivos
     */
    public static function possuiXmlGeralValido(array $nomesDeArquivos, string $numeroProjetoAtual): bool
    {
        foreach ($nomesDeArquivos as $nomeArquivo) {
            try {
                $identificacao = static::identificarArquivo($nomeArquivo);
            } catch (\Throwable) {
                continue;
            }

            if ($identificacao['numero_projeto'] === $numeroProjetoAtual && $identificacao['numero_item'] === self::NUMERO_XML_TOTAL) {
                return true;
            }
        }

        return false;
    }
}
