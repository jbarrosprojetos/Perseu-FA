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

    /**
     * Lista "achatada" de TODOS os itens de verdade (peças de madeira
     * E ferragens/acessórios) do XML inteiro, pra persistir como
     * componentes de matéria-prima de um Item na Aba P/Necessidade de
     * Materiais (2026-09-12, ver `ItemProjetoComponente` e handoff
     * `handoff_aba_p.md`) — diferente de `extrairComponentes()`/
     * `parse()` acima (que agrupa por AMBIENT/CATEGORY só pra exibir
     * Custo/Preço agregado) e de `metricas()`/`acumularMetricasComponentes()`
     * (que só soma `COMPONENT="Y"`, pra bater com o VBA original).
     *
     * **Achado real (2026-09-12, ver fixture `tests/Fixtures/Promob`)**:
     * diferente do que a regra `COMPONENT="Y"` (usada em
     * `extrairComponentes()`/`metricas()`) presume, ferragens e
     * acessórios (dobradiças, pistões Hettich etc.) aparecem no XML
     * como `CATEGORY` PRÓPRIAS (`"Acessórios"`/`"Hettich"`, irmãs da
     * `CATEGORY` numerada do item, ex. `"001 "`) cujo `ITEM` folha vem
     * marcado `COMPONENT="N"` — mesmo tendo `REFERENCE`/`DESCRIPTION`/
     * dimensões/`PRICE` completos, um item de verdade, comprável. A
     * regra `COMPONENT="Y"` do VBA original serve só pra decidir o que
     * ENTRA no cálculo de Custo Unitário (nunca recalculada aqui — ver
     * regra de ouro do parser), não pra decidir o que é ou não um
     * "item de verdade" pra uma lista de compras.
     *
     * A regra usada AQUI, portanto, é estrutural, não pelo atributo
     * `COMPONENT`: um `ITEM` SEM filhos (`ITEMS`) é sempre uma FOLHA —
     * ou uma peça de madeira (`COMPONENT="Y"`) ou uma ferragem/
     * acessório (`COMPONENT="N"` sem descer mais); um `ITEM` COM
     * filhos é sempre um agrupador (grupo/submontagem), nunca
     * extraído, só usado pra descer mais fundo. Folhas de custo ZERO
     * (ex.: `"Processo de Fabricação"`, `PRICE/@TOTAL = 0` — não é um
     * material de verdade, é uma etapa de processo sem preço próprio)
     * são descartadas — não fazem sentido numa lista de compras.
     *
     * Campos crus do Promob, sem reformatar/converter (mesma regra de
     * ouro do parser: nunca recalcula nada) — `repeticao`/`quantidade`
     * são `REPETITION`/`QUANTITY` (ver docblock de `metricas()` pro que
     * cada um significa no cálculo de m²/metro linear).
     *
     * `categoria` (2026-09-12, achado real reforçado a pedido do
     * usuário) é o `CATEGORY/@DESCRIPTION` mais próximo (o pai direto
     * da árvore `ITEMS/ITEM...` onde a folha foi achada) — cada
     * `CATEGORY` é filha direta de `AMBIENT/CATEGORIES` (nunca aninhada
     * dentro de outra `CATEGORY`, confirmado na fixture real), então
     * este valor NUNCA muda ao descer pela árvore de `ITEM`/`ITEMS`
     * dentro da mesma categoria. Não existe uma lista fixa de nomes:
     * pode vir numerada (`"001 "`, o padrão quando o item não tem nome
     * de ambiente definido no Promob), com nome de ambiente (`"Cozinha"`,
     * `"Dormitório"`, `"Construtor de Armários"` etc.) pras peças de
     * madeira/painel do próprio item, OU com nome de
     * fabricante/finalidade (`"Acessórios"`, `"Hettich"`, `"Processo de
     * Fabricação"` etc.) pras categorias-irmãs de ferragem/processo —
     * por isso NÃO é usado aqui pra decidir `componentizado` (ver
     * campo abaixo: essa decisão continua vindo do `COMPONENT` do
     * Promob, o mesmo sinal que a fórmula original de Custo Unitário já
     * usa — nomes de categoria variam demais pra servir de regra
     * confiável). Serve só como METADADO — agrupar a lista de
     * Materiais por ambiente/fabricante na tela e, no futuro, na Aba P/
     * Plano de Corte.
     *
     * Campos adicionais (2026-09-13, estudo completo do XML — ver
     * CLAUDE.md "Estudo do XML do Promob — estrutura completa pra
     * Plano de Corte") extraídos do bloco `<REFERENCES>` de cada
     * `ITEM` folha (quando presente — nem toda folha tem, ex. processo/
     * item sem catálogo) e do atributo `PLATECUTTINGROTATE` do próprio
     * `ITEM` (fora de `<REFERENCES>`). Escopo travado pelo usuário: só
     * enriquecer os campos, sem capturar hierarquia/árvore de
     * montagem — cada componente continua uma linha achatada. Ver
     * `valorReferencia()` pro porquê de tratar string vazia como
     * ausente (`REFERENCES/SUPPLIER REFERENCE=""` é o normal quando o
     * material é de catálogo próprio, não de fabricante parceiro).
     *
     * **Revisão 2026-09-16 (XML real "2610015 - 001 Ricardo Aragoni
     * Olga 86" — usuário reportou "importei esse xml mas ele mostrou
     * somente as chapas brancas, acho que não computou todas
     * informações")**. Dois bugs reais achados PARSEANDO o XML de
     * verdade com `SimpleXMLElement` (não por busca de texto — a
     * primeira tentativa, por regex/substring, deu resultados
     * ambíguos/contraditórios e foi descartada):
     *
     * 1. **Filtro de custo zero descartava material de verdade.** A
     *    regra antiga (`custo <= 0` → descarta) presumia que só
     *    "etapas de processo" (ex. "Processo de Fabricação") tinham
     *    `PRICE/@TOTAL + @TOTALCOMPONENTS = 0`. Falso: neste Promob,
     *    108 das 267 folhas do arquivo vinham com custo próprio zero,
     *    e 91 delas eram painéis/portas de verdade — com `REFERENCES`
     *    completo (material, cor, espessura, fita) e dimensão real —
     *    cujo preço o Promob simplesmente não calculou por peça (ex.
     *    todas as portas "Porta Reta" Maragogi Matt, os
     *    "Tamponamento"/"Vista Linear"/"Painel Horizontal" Carvalho
     *    Mel Sonoma e Frevo Matt). Isso explica o sintoma relatado:
     *    só sobravam as chapas brancas porque as peças BRANCAS
     *    (bases/fundos/laterais da caixa) são as únicas que por acaso
     *    têm custo próprio > 0 neste arquivo — as coloridas (portas,
     *    tamponamentos, painéis) ficavam TODAS de fora. Uma primeira
     *    correção tentou manter um filtro mais estreito (descartar só
     *    quando `custo <= 0` **e** a dimensão vinha como um "cubo"
     *    placeholder `WIDTH=HEIGHT=DEPTH` — sinal real de etapa de
     *    processo/mão de obra tipo "Processo de Fabricação"
     *    10×10×10/"Mão de Obra..." 1×1×1, validado nas 267 folhas
     *    desta fixture sem nenhum falso positivo). O usuário, porém,
     *    pediu explicitamente pra NÃO descartar nada por custo: "mesmo
     *    itens com custos zero, temos de relacionar, até para
     *    averiguar se necessário" — `extrairComponenteFolha()` agora
     *    SEMPRE extrai, sem nenhum filtro por custo/dimensão; quem
     *    decide o que é relevante é quem consome a lista, não o
     *    parser.
     * 2. **Componente de verdade com filho agregado nunca era
     *    extraído.** O comentário já previa o caso ("um componente de
     *    verdade `COMPONENT=Y` tem, dentro da própria árvore, outro
     *    componente agregado" — a mesma nota que `metricas()` já
     *    tinha, ver docblock acima) mas a regra estrutural antiga
     *    ("tem `ITEMS` filho → nunca extrai, só desce") tratava ISSO
     *    igual a um bucket puramente organizacional (ex. "Armário 2
     *    Portas", "Caixa Armário" — nomes de montagem do Promob, sem
     *    `REFERENCES` próprio). Achado real: 2 ocorrências de
     *    "Tamponamento Inferior 18" (`COMPONENT=Y`, cor "Carvalho Mel
     *    Sonoma", dimensão e `PRICE/TOTALCOMPONENTS` reais) tinham
     *    dentro de si outros componentes agregados (uma cantoneira;
     *    ou um conjunto "Frente Gaveta"/"Frente Reta"/"Sarrafo") — e
     *    ficavam de fora inteiras, só os agregados (quando tinham
     *    custo) apareciam. Sinal estrutural que separa os dois casos,
     *    validado nas 74 ocorrências reais de `ITEM` com `ITEMS`
     *    filho desta fixture (72 buckets puros / 2 com material
     *    próprio, sem nenhum caso ambíguo): o bucket organizacional
     *    NUNCA tem `REFERENCES/MATERIAL` próprio, o componente
     *    agregador sempre tem. Nova regra: só trata como bucket puro
     *    (nunca extrai) quando `MATERIAL` está ausente; quando
     *    presente, extrai o próprio nó (como uma peça normal) E
     *    continua descendo nos filhos (que são peças distintas, de
     *    material/espessura diferentes do pai — cortadas de chapas
     *    diferentes, não é duplicidade de peça, ver
     *    `extrairComponenteFolha()`).
     *
     * Ressalva sobre `custo`/`preco` nas 2 linhas do item 2: como o
     * nó pai já soma (via `TOTALCOMPONENTS`) o custo rolado dos
     * filhos, `custo` nessas linhas pode se sobrepor ao das linhas-
     * filhas — inofensivo pro propósito desta lista (peças pra
     * cortar/comprar), mas não somar `custo` desta lista pra obter um
     * total geral (isso já é o que `metricas()` existe pra fazer,
     * com recursão própria que evita a dupla contagem).
     *
     * ## Revisão 2026-09-17 — `PLATECUTTINGROTATE="N"` NÃO é "livre pra
     * rotacionar", é travado na orientação OPOSTA de "Y"
     *
     * O experimento controlado documentado no CLAUDE.md ("Y" = veio em
     * pé/vertical, "N" = veio horizontal/girado) provava só o SENTIDO
     * do veio de cada export — nunca foi testado se "N" significa
     * "sem restrição" pro motor de encaixe. Essa leitura (`veio_travado
     * = PLATECUTTINGROTATE === "Y"`, "N"/"NONE" tratados como
     * igualmente livres) era uma suposição não verificada.
     *
     * Usuário reportou uma peça real (`Tamponamento Inferior 18`,
     * 800×220, `PLATECUTTINGROTATE="N"`) com o veio errado no nosso
     * Plano de Corte mas correto no Promob Cut Pro real — investigação
     * cruzando o XML com o PDF real do Promob Cut Pro do MESMO projeto
     * (`Lista de Cortes`, coluna "Dimensão", por `UNIQUEID`) confirmou
     * em 11 de 11 peças de Carvalho Mel Sonoma conferidas: peças "Y"
     * SEMPRE saem com a dimensão invertida (profundidade primeiro) em
     * relação ao XML cru; peças "N" SEMPRE saem na MESMA ordem do XML
     * (sem inverter) — incluindo um par perfeito, mesma peça/mesma
     * medida (1434,5×755), uma instância "Y" e outra "N", cada uma
     * desenhada numa orientação diferente e SEMPRE a mesma prevista
     * por essa regra. Ou seja: "N" não é ausência de trava, é uma
     * trava na orientação inversa de "Y" — só `PLATECUTTINGROTATE`
     * ausente/"NONE" é realmente livre (nenhuma peça amadeirada com
     * veio real observada nas fixtures veio "NONE" — só ferragem/
     * material sem padrão de veio).
     *
     * Novo campo `veio_eixo_fixo` (substitui o uso de `veio_travado`
     * nos serviços de nesting — mantido só por compatibilidade)
     * guarda diretamente QUAL dimensão crua da peça (`'largura'` ou
     * `'profundidade'`) precisa ficar no eixo do veio da chapa:
     * "Y" → `'profundidade'` (igual à convenção antiga, já validada
     * com as 2 portas reais do CLAUDE.md — nada muda pra peças "Y");
     * "N" → `'largura'` (a correção: antes era tratada como livre e
     * podia sair na orientação errada); `null` quando "NONE"/ausente
     * (única situação realmente livre pro motor escolher).
     *
     * @return array<int, array{categoria: string, referencia: string, descricao: string, largura: float, altura: float, profundidade: float, repeticao: int, quantidade: float, custo: float, preco: float, componentizado: bool, material: ?string, cor: ?string, espessura: ?float, fornecedor: ?string, chapa_largura: ?float, chapa_comprimento: ?float, fita_borda_1: ?float, fita_borda_2: ?float, fita_borda_3: ?float, fita_borda_4: ?float, fita_cor: ?string, fita_cor_frontal: ?string, perimetro_fita: ?string, veio_travado: bool}>
     */
    public static function componentesParaMateriais(string $xmlContent): array
    {
        $doc = static::carregarXml($xmlContent);

        $componentes = [];

        foreach ($doc->AMBIENTS->AMBIENT ?? [] as $ambiente) {
            foreach ($ambiente->CATEGORIES->CATEGORY ?? [] as $categoria) {
                static::coletarComponentesMateriais($categoria->ITEMS ?? null, trim((string) $categoria['DESCRIPTION']), $componentes);
            }
        }

        return $componentes;
    }

    /**
     * @param  array<int, array{categoria: string, referencia: string, descricao: string, largura: float, altura: float, profundidade: float, repeticao: int, quantidade: float, custo: float, preco: float, componentizado: bool, material: ?string, cor: ?string, espessura: ?float, fornecedor: ?string, chapa_largura: ?float, chapa_comprimento: ?float, fita_borda_1: ?float, fita_borda_2: ?float, fita_borda_3: ?float, fita_borda_4: ?float, fita_cor: ?string, fita_cor_frontal: ?string, perimetro_fita: ?string, veio_travado: bool}>  $componentes
     */
    private static function coletarComponentesMateriais(?SimpleXMLElement $itemsNode, string $categoria, array &$componentes): void
    {
        if ($itemsNode === null) {
            return;
        }

        foreach ($itemsNode->ITEM as $item) {
            $temFilhos = isset($item->ITEMS);

            // Tem filhos (`ITEMS`) E não tem `MATERIAL` próprio? É um
            // bucket puramente organizacional (ex. "Armário 2 Portas",
            // "Caixa Armário" — nomes de montagem do Promob, nunca um
            // material de verdade) — desce, nunca extrai ele mesmo.
            // Quando TEM `MATERIAL` próprio mesmo tendo filhos, é um
            // componente de verdade com outro(s) componente(s)
            // agregado(s) dentro (ver "Revisão 2026-09-16, item 2" no
            // docblock de `componentesParaMateriais()`) — nesse caso
            // cai pro extração normal abaixo e DEPOIS ainda desce nos
            // filhos. `$categoria` não muda ao descer — sempre a mesma
            // `CATEGORY` raiz de onde a recursão começou.
            if ($temFilhos && static::valorReferencia($item->REFERENCES ?? null, 'MATERIAL') === null) {
                static::coletarComponentesMateriais($item->ITEMS, $categoria, $componentes);

                continue;
            }

            $componentes[] = static::extrairComponenteFolha($item, $categoria);

            if ($temFilhos) {
                static::coletarComponentesMateriais($item->ITEMS, $categoria, $componentes);
            }
        }
    }

    /**
     * Extrai um `ITEM` (folha, ou componente de verdade com agregados
     * dentro — ver "Revisão 2026-09-16, item 2" no docblock de
     * `componentesParaMateriais()`) pra uma linha achatada de material.
     *
     * Sempre extrai, mesmo custo zero e mesmo "etapa de processo" tipo
     * "Processo de Fabricação"/"Mão de Obra..." — **Revisão 2026-09-16
     * (pedido explícito do usuário)**: "mesmo itens com custos zero,
     * temos de relacionar, até para averiguar se necessário". A
     * primeira versão desta função descartava quando `custo <= 0` E a
     * dimensão vinha como um "cubo" placeholder (`WIDTH=HEIGHT=DEPTH`,
     * ex. 10×10×10/1×1×1 — sinal real de etapa de processo/mão de
     * obra sem preço, validado nas 267 folhas do XML "2610015 Olga
     * 86"), mas o usuário prefere ver TUDO na lista — inclusive essas
     * poucas etapas de processo/mão de obra — e decidir manualmente o
     * que é relevante, em vez do parser descartar por conta própria
     * (regra de ouro do parser: nunca decide/recalcula por conta
     * própria o que o Promob não decidiu).
     *
     * @return array{categoria: string, referencia: string, descricao: string, largura: float, altura: float, profundidade: float, repeticao: int, quantidade: float, custo: float, preco: float, componentizado: bool, material: ?string, cor: ?string, espessura: ?float, fornecedor: ?string, chapa_largura: ?float, chapa_comprimento: ?float, fita_borda_1: ?float, fita_borda_2: ?float, fita_borda_3: ?float, fita_borda_4: ?float, fita_cor: ?string, fita_cor_frontal: ?string, perimetro_fita: ?string, veio_travado: bool, veio_eixo_fixo: ?string}
     */
    private static function extrairComponenteFolha(SimpleXMLElement $item, string $categoria): array
    {
        $preco = $item->PRICE;
        $custo = (float) ($preco['TOTAL'] ?? 0) + (float) ($preco['TOTALCOMPONENTS'] ?? 0);

        $largura = (float) ($item['WIDTH'] ?? 0);
        $altura = (float) ($item['HEIGHT'] ?? 0);
        $profundidade = (float) ($item['DEPTH'] ?? 0);

        // Bloco `<REFERENCES>` — pode estar ausente (folhas sem
        // catálogo, ex. processo). Sub-nós no formato
        // `<TAG REFERENCE="valor" />`, lidos por
        // `valorReferencia()`.
        $referencias = $item->REFERENCES ?? null;

        // Fabricante — unifica `SUPPLIER` (material/chapa, ex.
        // "Duratex") e `FORNECEDOR` (ferragem de plugin parceiro,
        // ex. "Hettich"): nunca vêm os dois preenchidos na mesma
        // peça nas fixtures reais.
        $fornecedor = static::valorReferencia($referencias, 'SUPPLIER') ?? static::valorReferencia($referencias, 'FORNECEDOR');

        // `PLATECUTTINGROTATE` — ver "Revisão 2026-09-17" no docblock
        // da classe: NÃO é permissão de rotação, é a dimensão da peça
        // que fica travada no eixo do veio da chapa. "Y" trava
        // `profundidade` nesse eixo, "N" trava `largura` (a OUTRA
        // dimensão, orientação oposta) — "NONE"/qualquer outro valor
        // = sem veio, livre de verdade.
        $plateCuttingRotate = strtoupper(trim((string) ($item['PLATECUTTINGROTATE'] ?? '')));
        $veioEixoFixo = match ($plateCuttingRotate) {
            'Y'     => 'profundidade',
            'N'     => 'largura',
            default => null,
        };

        return [
            'categoria'    => $categoria,
            'referencia'   => (string) $item['REFERENCE'],
            'descricao'    => (string) $item['DESCRIPTION'],
            'largura'      => $largura,
            'altura'       => $altura,
            'profundidade' => $profundidade,
            'repeticao'    => (int) ($item['REPETITION'] ?? 0),
            'quantidade'   => (float) ($item['QUANTITY'] ?? 0),
            'custo'        => $custo,
            'preco'        => (float) ($preco->MARGINS->BUDGET['TOTAL'] ?? 0) + (float) ($preco->MARGINS->BUDGET['TOTALCOMPONENTS'] ?? 0),
            // `COMPONENT` ORIGINAL do XML — `true` ("Y") = peça de
            // madeira/painel, `false` ("N") = ferragem/acessório.
            // Guardado pra `ItemProjeto::metricasPromob()` conseguir
            // reconstruir ao vivo a MESMA divisão Custo(madeira)/
            // Misc(ferragens) da fórmula original — ver migration
            // `2026_09_12_120000_add_componentizado_to_itens_projeto_componentes_table`.
            'componentizado' => (string) $item['COMPONENT'] === 'Y',
            // Campos do `<REFERENCES>`/`PLATECUTTINGROTATE` pro
            // Plano de Corte (2026-09-13) — ver docblock de
            // `componentesParaMateriais()`.
            'material'          => static::valorReferencia($referencias, 'MATERIAL'),
            'cor'               => static::valorReferencia($referencias, 'MODEL_DESCRIPTION'),
            'espessura'         => static::valorReferenciaFloat($referencias, 'THICKNESS'),
            'fornecedor'        => $fornecedor,
            'chapa_largura'     => static::valorReferenciaFloat($referencias, 'MAXWIDTH'),
            'chapa_comprimento' => static::valorReferenciaFloat($referencias, 'MAXDEPTH'),
            'fita_borda_1'      => static::valorReferenciaFloat($referencias, 'FITA_BORDA_1'),
            'fita_borda_2'      => static::valorReferenciaFloat($referencias, 'FITA_BORDA_2'),
            'fita_borda_3'      => static::valorReferenciaFloat($referencias, 'FITA_BORDA_3'),
            'fita_borda_4'      => static::valorReferenciaFloat($referencias, 'FITA_BORDA_4'),
            'fita_cor'          => static::valorReferencia($referencias, 'MODEL_DESCRIPTION_FITA'),
            'fita_cor_frontal'  => static::valorReferencia($referencias, 'MODEL_DESCRIPTION_FITA_FRO'),
            'perimetro_fita'    => static::valorReferencia($referencias, 'PERIMETRO_FITA'),
            // `PLATECUTTINGROTATE` é atributo do próprio `ITEM`, FORA
            // de `<REFERENCES>`. `veio_travado` mantido só por
            // compatibilidade (true sempre que há veio, "Y" OU "N" —
            // ver `veio_eixo_fixo` abaixo pra saber QUAL orientação);
            // `veio_eixo_fixo` é o campo que os serviços de nesting
            // devem usar de fato. Ver "Revisão 2026-09-17" no
            // docblock da classe.
            'veio_travado'   => $veioEixoFixo !== null,
            'veio_eixo_fixo' => $veioEixoFixo,
        ];
    }

    /**
     * Lê `REFERENCES/<TAG>/@REFERENCE` (formato `<TAG REFERENCE="valor" />`
     * de cada sub-nó de `<REFERENCES>`). Trata string vazia como ausente
     * (`""` é o normal quando o campo não se aplica àquele material, ex.
     * `SUPPLIER=""` em material de catálogo próprio — ver docblock de
     * `componentesParaMateriais()`) — nunca retorna string vazia, só o
     * valor de verdade ou `null`.
     */
    private static function valorReferencia(?SimpleXMLElement $referencias, string $tag): ?string
    {
        if ($referencias === null || ! isset($referencias->{$tag})) {
            return null;
        }

        $valor = trim((string) $referencias->{$tag}['REFERENCE']);

        return $valor === '' ? null : $valor;
    }

    private static function valorReferenciaFloat(?SimpleXMLElement $referencias, string $tag): ?float
    {
        $valor = static::valorReferencia($referencias, $tag);

        return $valor === null ? null : (float) $valor;
    }

    /**
     * `DATE`/`HOUR` do nó raiz `<LISTING>` — data/hora em que o Promob
     * GEROU o XML (não a data de upload/processamento no sistema). Usado
     * pra identificar o arquivo em `NotaProjeto.texto` (ver
     * `ProjetoResource`, "Criar Itens" do fluxo Promob/CLAUDE.md) — tanto
     * na Nota geral de checagem (XML "000") quanto na Nota de cada item.
     * Formato bruto do Promob, sem reformatar (`"16/07/2026"`/`"11:43:19"`,
     * confirmado nos XMLs de exemplo) — já vem no formato brasileiro
     * esperado, não precisa de `Carbon::parse()`/reformatação.
     *
     * @return array{data: string, hora: string}
     */
    public static function dataHora(string $xmlContent): array
    {
        $doc = static::carregarXml($xmlContent);

        return [
            'data' => trim((string) ($doc['DATE'] ?? '')),
            'hora' => trim((string) ($doc['HOUR'] ?? '')),
        ];
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
