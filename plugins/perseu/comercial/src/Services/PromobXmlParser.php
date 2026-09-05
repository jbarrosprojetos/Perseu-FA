<?php

namespace Perseu\Comercial\Services;

use SimpleXMLElement;

/**
 * Lê um XML exportado pelo Promob (nó raiz `LISTING`) e extrai Custo/
 * Preço já prontos (o Promob já aplica os próprios cálculos/margens —
 * este parser só LÊ os totais, nunca recalcula nada), descendo por
 * `AMBIENT`/`CATEGORY`/`ITEM` — usado por `PromobChecagemTotal` pra
 * comparar o total somado dos XMLs de item contra o XML "000" (ver
 * CLAUDE.md deste plugin, "Fluxo Promob: upload + Checar Total").
 *
 * Estrutura confirmada lendo os XMLs reais de exemplo (260000 - 000/
 * 001/002), não presumida de documentação do Promob:
 * - Custo = `TOTALPRICES/MARGINS/ORDER/@VALUE`.
 * - Preço = `TOTALPRICES/MARGINS/BUDGET/@VALUE`.
 * - Mesma extração em `LISTING` (raiz), cada `AMBIENT` e cada
 *   `CATEGORY` (`AMBIENT/CATEGORIES/CATEGORY`) — `CATEGORY/@DESCRIPTION`
 *   traz o número do item (ex.: "001 ", com espaço à direita).
 * - Componentes (`ITEM[@COMPONENT="Y"]`) ficam aninhados em profundidade
 *   variável dentro de `CATEGORY/ITEMS/ITEM.../ITEMS/ITEM` (grupos/
 *   submontagens têm `COMPONENT="N"`) — custo base
 *   `PRICE/@TOTAL + PRICE/@TOTALCOMPONENTS`, preço final
 *   `PRICE/MARGINS/BUDGET/@TOTAL + PRICE/MARGINS/BUDGET/@TOTALCOMPONENTS`.
 */
final class PromobXmlParser
{
    /**
     * @return array{custo: float, preco: float, ambientes: array<int, array{descricao: string, custo: float, preco: float, categorias: array<int, array{numero_item: string, custo: float, preco: float, componentes: array<int, array{referencia: string, descricao: string, largura: float, altura: float, profundidade: float, custo: float, preco: float}>}>}>}
     */
    public static function parse(string $xmlContent): array
    {
        $doc = static::carregarXml($xmlContent);

        return [
            'custo'     => static::valorMargem($doc->TOTALPRICES ?? null, 'ORDER'),
            'preco'     => static::valorMargem($doc->TOTALPRICES ?? null, 'BUDGET'),
            'ambientes' => static::extrairAmbientes($doc),
        ];
    }

    /**
     * As 5 métricas do VBA (`CompararTotalGeral`/`ColetarComponentes`)
     * — Peças, m², Metro Linear, Custo (próprio) e Misc — somadas em
     * TODO o XML (todo `AMBIENT`/`CATEGORY`/`ITEM`, sem agrupar por
     * categoria/referência; aqui só o total geral do arquivo importa).
     *
     * - **Tot.Peças**: soma de `REPETITION` de cada componente.
     * - **Tot.m²**: soma de `REPETITION × QUANTITY` de cada componente.
     * - **Tot.MLinear**: soma de `(WIDTH + DEPTH) × 2 × REPETITION / 1000`
     *   de cada componente (perímetro × repetição, mm → m).
     * - **Tot.Custo**: soma de `PRICE/@TOTAL + PRICE/@TOTALCOMPONENTS`
     *   do PRÓPRIO componente — `CustoProprioItem` do VBA, NÃO a soma
     *   "com filhos" usada em `extrairComponentes()`/`parse()` acima
     *   (essa outra serve só pra exibir Custo/Preço por categoria, lido
     *   direto de `TOTALPRICES` já agregado pelo Promob, nunca somado
     *   item a item). Por isso a árvore aqui só desce por `ITEMS` de
     *   nós `COMPONENT="N"` (grupos/submontagens) — ao achar um
     *   `COMPONENT="Y"`, conta ELE e para de descer naquele ramo, sem
     *   olhar dentro dos filhos dele. Necessário pra não contar em
     *   dobro quando um componente de verdade (`COMPONENT="Y"`) tem,
     *   dentro da própria árvore, outro componente agregado também
     *   `COMPONENT="Y"` (ex.: um tampo com uma porta agregada) — o
     *   `TOTALCOMPONENTS` do pai, nesse caso, já reflete o que está
     *   "rolado" dos filhos; somar os filhos de novo, separadamente,
     *   duplicaria o valor.
     * - **Tot.Misc**: Custo do `LISTING` inteiro
     *   (`TOTALPRICES/MARGINS/ORDER/@VALUE` da raiz) MENOS Tot.Custo —
     *   tudo que não é matéria-prima "própria" de um componente (mão de
     *   obra, acessórios não componentizados, margem etc.).
     *
     * Valores retornados SEM arredondar (`m2`/`mlinear`/`custo`/`misc`
     * em precisão total de `float`) — arredondar por arquivo, antes de
     * somar os XMLs de item num total, introduzia um erro artificial
     * de até alguns centavos/centímetros na comparação final (achado
     * real: `mlinear` batia com 0,01 de "diferença" só por causa dessa
     * dupla rolagem de arredondamento, com os mesmos 3 XMLs de exemplo
     * — não é uma diferença real dos dados). Quem exibe pro usuário
     * (`PromobChecagemTotal`/`ProjetoResource::renderizarResultadoPromob()`)
     * arredonda só na hora de formatar o número final.
     *
     * @return array{pecas: int, m2: float, mlinear: float, custo: float, misc: float}
     */
    public static function metricas(string $xmlContent): array
    {
        $doc = static::carregarXml($xmlContent);

        $acumulado = ['pecas' => 0, 'm2' => 0.0, 'mlinear' => 0.0, 'custo' => 0.0];

        foreach ($doc->AMBIENTS->AMBIENT ?? [] as $ambiente) {
            foreach ($ambiente->CATEGORIES->CATEGORY ?? [] as $categoria) {
                static::acumularMetricasComponentes($categoria->ITEMS ?? null, $acumulado);
            }
        }

        $custoListing = static::valorMargem($doc->TOTALPRICES ?? null, 'ORDER');

        return [
            'pecas'   => $acumulado['pecas'],
            'm2'      => $acumulado['m2'],
            'mlinear' => $acumulado['mlinear'],
            'custo'   => $acumulado['custo'],
            'misc'    => $custoListing - $acumulado['custo'],
        ];
    }

    /**
     * @param  array{pecas: int, m2: float, mlinear: float, custo: float}  $acumulado
     */
    private static function acumularMetricasComponentes(?SimpleXMLElement $itemsNode, array &$acumulado): void
    {
        if ($itemsNode === null) {
            return;
        }

        foreach ($itemsNode->ITEM as $item) {
            if ((string) $item['COMPONENT'] === 'Y') {
                $repeticao = (float) ($item['REPETITION'] ?? 0);
                $quantidade = (float) ($item['QUANTITY'] ?? 0);
                $largura = (float) ($item['WIDTH'] ?? 0);
                $profundidade = (float) ($item['DEPTH'] ?? 0);
                $preco = $item->PRICE;

                $acumulado['pecas'] += (int) $repeticao;
                $acumulado['m2'] += $repeticao * $quantidade;
                $acumulado['mlinear'] += ($largura + $profundidade) * 2 * $repeticao / 1000;
                $acumulado['custo'] += (float) ($preco['TOTAL'] ?? 0) + (float) ($preco['TOTALCOMPONENTS'] ?? 0);

                // Para de descer neste ramo — ver docblock de `metricas()`
                // sobre por que somar os filhos de um componente
                // `COMPONENT="Y"` de novo duplicaria o custo/peças já
                // "rolados" no próprio nó.
                continue;
            }

            if (isset($item->ITEMS)) {
                static::acumularMetricasComponentes($item->ITEMS, $acumulado);
            }
        }
    }

    private static function carregarXml(string $xmlContent): SimpleXMLElement
    {
        // O Promob exporta com BOM UTF-8 — `simplexml_load_string()` lida
        // bem com isso na prática, mas removemos explicitamente por
        // segurança (alguns parsers XML tratam BOM antes da declaração
        // XML como erro de sintaxe).
        $xmlContent = preg_replace('/^\xEF\xBB\xBF/', '', $xmlContent) ?? $xmlContent;

        $doc = @simplexml_load_string($xmlContent);

        if ($doc === false) {
            throw new \RuntimeException('XML do Promob inválido ou corrompido — não foi possível interpretar o conteúdo.');
        }

        return $doc;
    }

    /**
     * @return array<int, array{descricao: string, custo: float, preco: float, categorias: array<int, array{numero_item: string, custo: float, preco: float, componentes: array<int, mixed>}>}>
     */
    private static function extrairAmbientes(SimpleXMLElement $doc): array
    {
        $ambientes = [];

        foreach ($doc->AMBIENTS->AMBIENT ?? [] as $ambiente) {
            $ambientes[] = [
                'descricao'  => trim((string) $ambiente['DESCRIPTION']),
                'custo'      => static::valorMargem($ambiente->TOTALPRICES ?? null, 'ORDER'),
                'preco'      => static::valorMargem($ambiente->TOTALPRICES ?? null, 'BUDGET'),
                'categorias' => static::extrairCategorias($ambiente),
            ];
        }

        return $ambientes;
    }

    /**
     * @return array<int, array{numero_item: string, custo: float, preco: float, componentes: array<int, mixed>}>
     */
    private static function extrairCategorias(SimpleXMLElement $ambiente): array
    {
        $categorias = [];

        foreach ($ambiente->CATEGORIES->CATEGORY ?? [] as $categoria) {
            $categorias[] = [
                'numero_item' => trim((string) $categoria['DESCRIPTION']),
                'custo'       => static::valorMargem($categoria->TOTALPRICES ?? null, 'ORDER'),
                'preco'       => static::valorMargem($categoria->TOTALPRICES ?? null, 'BUDGET'),
                'componentes' => static::extrairComponentes($categoria->ITEMS ?? null),
            ];
        }

        return $categorias;
    }

    /**
     * Percorre `ITEMS/ITEM` recursivamente — grupos/submontagens
     * (`COMPONENT="N"`) só servem pra descer mais fundo até achar as
     * peças de verdade (`COMPONENT="Y"`), que podem estar em qualquer
     * profundidade dentro da árvore.
     *
     * @return array<int, array{referencia: string, descricao: string, largura: float, altura: float, profundidade: float, custo: float, preco: float}>
     */
    private static function extrairComponentes(?SimpleXMLElement $itemsNode): array
    {
        if ($itemsNode === null) {
            return [];
        }

        $componentes = [];

        foreach ($itemsNode->ITEM as $item) {
            if ((string) $item['COMPONENT'] === 'Y') {
                $preco = $item->PRICE;

                $componentes[] = [
                    'referencia'   => (string) $item['REFERENCE'],
                    'descricao'    => (string) $item['DESCRIPTION'],
                    'largura'      => (float) ($item['WIDTH'] ?? 0),
                    'altura'       => (float) ($item['HEIGHT'] ?? 0),
                    'profundidade' => (float) ($item['DEPTH'] ?? 0),
                    'custo'        => (float) ($preco['TOTAL'] ?? 0) + (float) ($preco['TOTALCOMPONENTS'] ?? 0),
                    'preco'        => (float) ($preco->MARGINS->BUDGET['TOTAL'] ?? 0) + (float) ($preco->MARGINS->BUDGET['TOTALCOMPONENTS'] ?? 0),
                ];
            }

            if (isset($item->ITEMS)) {
                $componentes = [...$componentes, ...static::extrairComponentes($item->ITEMS)];
            }
        }

        return $componentes;
    }

    private static function valorMargem(?SimpleXMLElement $totalPrices, string $tipo): float
    {
        if ($totalPrices === null) {
            return 0.0;
        }

        $node = $totalPrices->MARGINS->{$tipo} ?? null;

        if ($node === null) {
            return 0.0;
        }

        return (float) ($node['VALUE'] ?? 0);
    }
}
