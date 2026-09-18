<?php

namespace Perseu\Comercial\Services;

use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Collection;
use Perseu\Comercial\Models\ItemProjetoComponente;
use Perseu\Comercial\Models\Projeto;

/**
 * Gera o PDF único de "Produção" de um Projeto (botão "Produção",
 * `EditProjeto::getFormActions()`, logo depois de "Documentos") — 3
 * seções, réplica simplificada dos 3 relatórios do Promob Cut usados
 * de referência nesta tarefa ("Lista de Compras"/"Preview de
 * Corte"/"Etiquetas" do projeto 24-260000, 2026-09-13): Materiais
 * (chapas sintetizadas pelo nesting + bordas + acessórios), Preview
 * de Corte (uma página por chapa) e Etiquetas (grade em A4).
 *
 * Decisão de arquitetura (2026-09-13, mesmo padrão de "Documentos" —
 * ver `DocumentoTemplateService`/`EditProjeto::getFormActions()`):
 * NADA fica persistido no banco. O nesting é recalculado do zero a
 * cada clique em "Produção" a partir dos `ItemProjetoComponente` já
 * salvos (baratos de reprocessar, nenhuma centena de peças chega perto
 * de ser um problema de performance) e o PDF é devolvido pra download
 * imediato (`deleteFileAfterSend`), igual ao Excel gerado por
 * "Documentos" — nenhum arquivo novo fica guardado no Perseu.
 *
 * Requer `dompdf/dompdf` no `composer.json` da RAIZ do Perseu-FA (não
 * deste plugin — mesmo padrão já usado por `phpoffice/phpspreadsheet`,
 * exigido por `DocumentoTemplateService` e também declarado só na
 * raiz, nunca no `composer.json` deste plugin).
 *
 * ## Cortes de escopo deliberados nesta primeira versão (MVP)
 * - Sem código de barras nas etiquetas (só o código em texto) — evita
 *   mais uma dependência nova só pra isso.
 * - "Aproveitamento %" e "Cortes" são do NOSSO algoritmo
 *   (`PlanoCorteNestingService`) — desde a rodada 6 (2026-09-14),
 *   "Cortes" é uma contagem REAL (geométrica) de cortes de guilhotina,
 *   não um proxy nem a numeração de etapas do Promob (que usa um
 *   algoritmo proprietário próprio pra isso) — decisão confirmada com
 *   o usuário: não precisa bater com o número exato do Promob, só ser
 *   uma contagem real e coerente do NOSSO plano de corte.
 * - Numeração de peça no PREVIEW DE CORTE segue o esquema
 *   "instância/totalLetra" (ex. "2/3A") desde a revisão "cabeçalho
 *   padrão" abaixo — a etiqueta (`montarHtmlEtiquetas()`) continua com
 *   seu próprio código "PCxxxxxx-N" (`componente_id` + instância),
 *   sem letra de grupo, escopo deliberadamente não alterado nesta
 *   revisão.
 *
 * ## Revisão 2026-09-14 (rodada 6) — margem de página EXPLÍCITA (não
 * mais o default do Dompdf) + cabeçalho compacto e determinístico
 *
 * O usuário reportou (de novo, depois da rodada 5 ter corrigido as 2
 * páginas em branco) que título e desenho da chapa voltaram a sair em
 * páginas DIFERENTES. Causa provável: a área de desenho
 * (`$larguraDisponivelMm`/`$alturaDisponivelMm`) e a altura reservada
 * pro cabeçalho eram só ESTIMATIVAS (comentário antigo: "A4 paisagem,
 * margens de ~12mm") — sem fixar a margem de página de verdade, o
 * Dompdf usa o próprio default dele (que pode ser maior que 12mm), e
 * o cabeçalho (3 linhas, `display:block`, sem limite de largura) podia
 * crescer mais que o previsto — qualquer uma das duas coisas empurra
 * cabeçalho+desenho pra além de 1 página.
 *
 * Correção: (1) `@page { margin: 8mm; }` explícito no CSS — a margem
 * real da página passa a ser CONHECIDA (277×194mm úteis em A4
 * paisagem), não mais um palpite; (2) cabeçalho da Preview de Corte
 * reduzido pra 2 linhas curtas com `white-space: nowrap; overflow:
 * hidden; text-overflow: ellipsis;` — altura do cabeçalho fica
 * PREVISÍVEL (não cresce por causa de um nome de projeto/material
 * comprido); (3) `$larguraDisponivelMm`/`$alturaDisponivelMm`
 * recalculados a partir da margem real menos o cabeçalho (com folga);
 * (4) cabeçalho + desenho da chapa agora ficam DENTRO de um único
 * `<div class="pagina-chapa">` com `page-break-inside: avoid;` (além
 * do `page-break-before: always;`) — uma segunda linha de defesa,
 * *sem* usar `<table>` (a rodada 5 já provou que tabela +
 * `page-break-before` causa páginas em branco no Dompdf real); como o
 * `.chapa-container` já tem `height` explícito (não depende dos
 * filhos `position:absolute` pra calcular a própria altura), o
 * problema antigo de "`page-break-inside:avoid` não funciona com
 * filhos `position:absolute`" não se aplica aqui. IMPORTANTE: não foi
 * possível testar com o Dompdf real neste ambiente (só
 * `wkhtmltopdf`, ver CLAUDE.md) — o item (1)+(2)+(3) (medidas
 * determinísticas) é a correção principal, que não depende do
 * `page-break-inside` funcionar; o item (4) é só reforço.
 *
 * ## Revisão 2026-09-14 — dropdown "Otimizadores" (Nativa / Packing
 * Solver)
 *
 * A pedido explícito do usuário, a tela de "Produção" ganhou um
 * dropdown pra ESCOLHER qual motor de encaixe usar — "Nativa"
 * (`PlanoCorteNestingService`, o algoritmo próprio, INTOCADO por esta
 * mudança, continua sendo o padrão) ou "Packing Solver" (motor externo
 * `fontanf/packingsolver`, `PlanoCortePackingSolverService` — ver
 * docblock daquela classe e CLAUDE.md pra todo o histórico da
 * pesquisa). Decisão explícita do usuário: "podemos manter o nosso
 * como está... ao longo do projeto podemos aprender mais e decidir
 * qual ficará" — é uma opção EXPLORATÓRIA, não uma substituição.
 * `$otimizador` só decide QUAL das duas chamar; o resto desta classe
 * (montagem do HTML/PDF) é idêntico pros dois, porque as duas devolvem
 * exatamente o mesmo formato (`grupos`/`chapas`/`pecas`).
 *
 * ## Revisão 2026-09-14 — vão do kerf pouco visível no PDF real
 *
 * Usuário reportou que o Preview de Corte não mostra claramente os
 * vãos entre peças (kerf), diferente das imagens de teste geradas
 * durante a pesquisa. Causa: numa chapa inteira desenhada em escala
 * de página A4 (escala ~0,09), poucos mm de kerf viram frações de mm
 * no papel — menor que a própria borda das caixas, então o vão
 * desaparecia visualmente. Ver comentário detalhado em
 * `montarHtmlPreviewCorte()` — correção foi um piso mínimo de vão
 * (0,8mm por lado, ver revisão abaixo) + fundo cinza no
 * `.chapa-container` pra o vão aparecer como faixa, não só um fiapo
 * branco.
 *
 * ## Revisão 2026-09-14 — cabeçalho padrão de marca, grupos de peça
 * (legenda A/B/C) e setas de corte
 *
 * Usuário pediu 3 coisas de uma vez, comparando com o cabeçalho do
 * template Excel do Perseu e com o PDF do Promob Cut:
 *
 * 1. **Cabeçalho padrão de marca** em todas as páginas (Materiais,
 *    Preview de Corte, Etiquetas) — faixa amarela (cor Primário da
 *    tela Configurações → Marca, `/admin/settings/manage-branding`)
 *    com o título da seção à esquerda e o logo à direita, + faixa
 *    Cinza (mesma tela) com os campos Obra/Projeto/Revisão, no
 *    mesmo espírito do título do template Excel de referência.
 *    `montarCabecalhoTopo()` monta as duas faixas. Decisão confirmada
 *    com o usuário (`AskUserQuestion`): cores FIXAS no código
 *    (`self::COR_PRIMARIA`/`self::COR_CINZA`, valores lidos
 *    diretamente da tela de branding — #ffc000/#52525c) em vez de
 *    ler dinamicamente do banco, porque a tela de branding fica fora
 *    da pasta deste plugin (`comercial`) — se a marca mudar, alguém
 *    precisa atualizar essas 2 constantes manualmente. O LOGO em si
 *    ainda é um FALLBACK EM TEXTO ("F.A. MARCENARIA" estilizado) —
 *    o usuário disse que ia enviar o arquivo PNG do "Logo Claro" da
 *    tela de branding; quando chegar, trocar `titulo-topo-logo` por
 *    uma tag `<img>` com o PNG embutido em base64 (`isRemoteEnabled`
 *    é `false` no Dompdf, então só path local/`data:` funciona, nunca
 *    URL remota). Campos do bloco Obra/Projeto/Revisão vêm direto do
 *    Model `Projeto`: Obra = `descricao` (mesmo campo que
 *    `DocumentoTemplateService` expõe como `%Obra_Desc%`), Projeto =
 *    `numero_projeto`, Revisão = `revisao` (existe no Model,
 *    `unsignedInteger default 0`, zero-padded em 2 dígitos — ver
 *    CLAUDE.md).
 *
 * 2. **Legenda de grupos por peça (A, B, C...)** — usuário pediu
 *    "peças do mesmo tamanho formam um grupo", mas corrigiu na hora
 *    ao ser perguntado sobre o escopo: "podemos ter uma lateral e uma
 *    porta do mesmo tamanho, aí são peças diferentes" — ou seja, o
 *    agrupamento é por `componente_id` (a peça em si, do jeito que já
 *    existe no Model), NUNCA só por width×height coincidirem.
 *    `montarGruposPorPeca()` varre o nesting inteiro (todos os grupos
 *    de material, todas as chapas) e atribui uma letra + uma cor suave
 *    ESTÁVEL por `componente_id`, na ordem de primeira aparição —
 *    GLOBAL pro projeto inteiro (não reinicia por chapa), porque é
 *    isso que faz o código "2/3A" fazer sentido (2ª de 3 unidades
 *    TOTAIS desse componente, não só desta chapa — usa `instancia` e
 *    `repeticao`, que os dois motores de nesting já devolvem, sem
 *    precisar mexer em nenhum dos dois). A escolha do escopo global
 *    (em vez de reiniciar A/B/C por chapa) não foi confirmada
 *    explicitamente pelo usuário (a pergunta original tinha 2 opções
 *    de escopo, mas a resposta focou em corrigir a definição de
 *    "grupo", não em escolher entre elas) — é a interpretação mais
 *    coerente com o pedido da numeração "1/3A, 2/3A", registrada aqui
 *    pra revisão se o usuário achar que não é isso. Cada página de
 *    Preview de Corte mostra só os grupos que aparecem NAQUELA chapa
 *    (`.legenda-col`, à esquerda do desenho) — normal a legenda ter
 *    letras "faltando" se nem todo componente cair nessa chapa
 *    específica. A área de desenho encolheu (~53mm a menos de
 *    largura) pra abrir espaço pra essa coluna.
 *
 * 3. **Seta do 1º corte (estilo Promob, "»")** — pendência registrada
 *    desde a rodada 4 ("se compensa desenhar alguma indicação visual
 *    da direção/ordem dos cortes"). Primeira tentativa (mesmo dia)
 *    desenhava uma seta em CADA fronteira entre peças vizinhas — o
 *    usuário mandou um print de referência do Promob e corrigiu: só
 *    UMA seta, do PRIMEIRO corte, e do LADO DE FORA do desenho da
 *    chapa (não em cima das peças). Reescrito: `primeiroCorte()`
 *    procura, por geometria pura (sem tocar nenhum dos 2 motores), a
 *    linha reta — horizontal ou vertical — mais próxima da borda
 *    (topo ou esquerda) que atravessa a chapa INTEIRA sem cruzar
 *    nenhuma peça (nenhuma peça com uma parte de cada lado) — essa é
 *    a aproximação de "primeiro corte" (numa construção por
 *    prateleiras/blocos, o primeiro corte real É uma linha assim,
 *    mas pode existir mais de uma linha geometricamente válida nesse
 *    sentido — ficamos com a mais próxima da borda, não com a árvore
 *    de cortes real de nenhum motor). Se nenhuma linha desse tipo
 *    existir (layout sem nenhuma fronteira "de ponta a ponta"), não
 *    desenha seta nenhuma nessa chapa — melhor não mostrar do que
 *    mostrar errado. A seta fica no MARGEM entre a legenda e o
 *    desenho (`.seta-primeiro-corte`, `left`/`top` negativos em
 *    relação a `.chapa-container`), igual à referência do usuário; a
 *    legenda ganhou uma linha fixa explicando o símbolo.
 *    **Correção 2026-09-15**: a 1ª versão do critério (só "nenhuma
 *    peça cruza a linha") deixava passar a margem da limpeza de
 *    bordas como se fosse o 1º corte — usuário apontou ("desconsiderar
 *    a limpeza da peça"). `primeiroCorte()` agora também exige peça
 *    de verdade dos DOIS lados da linha, o que exclui a margem de
 *    limpeza automaticamente, sem precisar saber o valor dela.
 *
 * IMPORTANTE (mesma ressalva das rodadas anteriores): não foi possível
 * testar esta revisão com o Dompdf real neste ambiente — validado
 * com `wkhtmltopdf` contra o mesmo HTML que a classe real produz (via
 * Reflection, sem Eloquent, mesmo script das rodadas 3/5) usando os
 * fixtures reais de Promob já usados nesta tarefa. Ainda faltam: (a) o
 * PNG real do logo; (b) confirmar com o usuário se o escopo global
 * dos grupos A/B/C é o que ele esperava.
 *
 * ## Revisão 2026-09-15 — 1º PDF real (Dompdf de verdade) testado pelo
 * usuário: 4 correções
 *
 * O usuário testou a revisão anterior com o Dompdf REAL (primeira vez
 * nesta tarefa — até aqui só validávamos com `wkhtmltopdf` como proxy,
 * ver ressalva acima) e reportou, num PDF de produção real
 * (`Producao - 2630001 3.pdf`, 9 páginas), 4 problemas/pedidos:
 *
 * 1. **Selo da seta (círculo âmbar) não é necessário** — "o realce num
 *    é necessário coloquei somente pra indicar, basta somente aquele
 *    caracter '»'". O círculo amarelo do print de referência (rodada
 *    anterior) era só a ANOTAÇÃO do usuário pra apontar a seta, nunca
 *    fez parte do pedido de design. `.seta-primeiro-corte` perdeu
 *    `background`/`border-radius`/cor de destaque — sobra só o
 *    caractere "»" em texto simples (preto, negrito), a CAIXA
 *    invisível (`width`/`height`/`line-height`) continua existindo só
 *    pra centralizar o texto na linha de corte, sem pintar nada.
 *
 * 2. **Seta na posição ERRADA em TODAS as chapas no Dompdf real**
 *    (mas correta no `wkhtmltopdf`) — causa raiz: a seta era
 *    posicionada como IRMÃ de `.chapa-container` dentro de
 *    `.chapa-col` (`position:relative`, largura automática,
 *    precedida por `.legenda-col` FLUTUANTE) — essa combinação
 *    (float + `position:relative` de largura automática + filho
 *    absoluto com offset negativo) é um ponto fraco conhecido do
 *    motor de layout do Dompdf, que não afeta navegadores nem o
 *    `wkhtmltopdf` (daí a validação anterior não ter pego o problema).
 *    As CAIXAS DAS PEÇAS (`.caixa-peca`), em contraste, sempre
 *    renderizaram na posição certa no PDF real — são filhas de
 *    `.chapa-container`, que tem `width`/`height` EXPLÍCITOS (não
 *    depende de float nem de largura automática). Correção: a seta
 *    passou a ser filha de `.chapa-container` também (mesmo
 *    posicionamento comprovadamente correto das peças), com offset
 *    negativo pra "vazar" pra fora do desenho — `.chapa-col` só
 *    continua existindo pelo `margin-left` (empurra o conjunto pra
 *    direita da legenda).
 *
 * 3. **Cores de peça/legenda saindo brancas/ocas no Dompdf real** —
 *    `corGrupo()` gerava a cor em CSS (`hsl(matiz, sat%, lum%)`), que
 *    o `wkhtmltopdf` interpreta normalmente mas o Dompdf real
 *    aparentemente não (caixas e quadrinhos da legenda saíam sem
 *    nenhum preenchimento, só a borda preta). Correção: a conversão
 *    HSL → RGB passou a ser feita em PHP (`hslParaHex()`), e
 *    `corGrupo()` devolve hex (`#rrggbb`) — mesmo formato que
 *    `COR_PRIMARIA_PADRAO`/`COR_CINZA_PADRAO` já usavam e que
 *    comprovadamente funciona no Dompdf real (o cabeçalho de marca
 *    renderizou certo no PDF de teste).
 *
 * 4. **Marca (logo + cores) fixa no código → dinâmica** — o usuário
 *    reverteu a decisão anterior ("cores fixas no código", ver revisão
 *    "cabeçalho padrão de marca" acima): "a cor tambem tem que ser
 *    dinamica exata do campo, no caso de trocar de empresa ou de cor
 *    isso fluir automaticamente" + trocar o texto "F.A. MARCENARIA"
 *    pelo PNG real de Configurações → Marca → Logo Claro. Antes
 *    inacessível (a tela de branding fica fora da pasta deste plugin);
 *    localizado nesta revisão em
 *    `App\Http\Middleware\ApplyBrandSettings` (aplica a MESMA marca no
 *    próprio painel Filament) — usa
 *    `settings(\Webkul\Support\Settings\BrandSettings::class)`
 *    (`spatie/laravel-settings`, `config/settings.php`, repositório
 *    `CompanyAwareSettingsRepository` — resolve a empresa certa
 *    automaticamente, mesmo mecanismo usado pelo painel admin) com os
 *    campos `primary_color`/`gray_color` (hex, ex. `#ffc000`) e
 *    `light_logo` (caminho relativo no disco `public`, ex.
 *    `settings/xxxx.png`). `carregarMarca()` (chamado 1x no
 *    construtor) lê esses 3 campos: cores viram `$this->corPrimaria`/
 *    `$this->corCinza` (usadas em `estilosBase()` no lugar das
 *    constantes fixas antigas); o logo é lido do disco
 *    (`Storage::disk('public')->path(...)`) e embutido como
 *    `data:image/...;base64,...` (Dompdf tem `isRemoteEnabled=false`,
 *    então só path local/`data:` funciona, nunca URL remota — mesma
 *    razão documentada desde a revisão anterior). `try/catch` amplo
 *    em volta de tudo: se a tabela de settings não existir/estiver
 *    vazia (ex. ambiente sem seed) ou o arquivo do logo não existir no
 *    disco, cai pros valores fixos antigos (`COR_PRIMARIA_PADRAO`/
 *    `COR_CINZA_PADRAO`/texto `F.A. MARCENARIA`) em vez de quebrar a
 *    geração do PDF — troca de empresa/cor na tela de branding passa
 *    a refletir automaticamente no próximo PDF gerado, sem precisar
 *    editar código. Escopo deliberadamente FORA desta revisão: o
 *    campo "Altura do logo" (`logo_height`, em `rem` — unidade de tela,
 *    sem equivalência direta com o `mm` de impressão do Dompdf) — a
 *    altura do logo no cabeçalho do PDF continua um valor fixo em
 *    `mm` (`.titulo-topo-logo-img`), pensado pro layout impresso, não
 *    lido dessa configuração.
 *
 * IMPORTANTE: assim como a revisão anterior, os itens 1-3 foram
 * validados de novo só com `wkhtmltopdf` (mesmo harness via
 * Reflection) — sem Dompdf real neste ambiente pra confirmar a
 * correção do item 2 e 3 de fato. O item 4 (marca dinâmica) não foi
 * possível validar nem com `wkhtmltopdf`: depende de
 * `settings()`/`Storage`/Eloquent reais, que o harness de teste desta
 * tarefa não simula (usa `stdClass` fake pro `Projeto`) — validação
 * real só vai acontecer quando o usuário testar no Perseu-FA de
 * verdade.
 *
 * ## Revisão 2026-09-15 (rodada 3) — 6 ajustes depois do 2º PDF real
 *
 * 1. **Seta do 1º corte só aparecia horizontal** — o usuário reportou
 *    que a marcação nunca aparecia girada quando o 1º corte era
 *    vertical. Causa provável: a versão anterior desenhava o mesmo
 *    caractere "»" com `transform: rotate(90deg)` pro caso vertical —
 *    suspeita forte (reforçada pelo item 2 da rodada anterior, onde
 *    OUTRO uso de posicionamento "esperto" também falhou só no Dompdf
 *    real) de que `transform` não é confiável no Dompdf real, mesmo
 *    funcionando no `wkhtmltopdf`. Troquei por um símbolo PRÓPRIO pro
 *    corte vertical (duplo triângulo pra baixo, "▼▼", sem nenhum
 *    `transform`) em vez de girar o mesmo glifo — `setaPrimeiroCorteHtml()`
 *    (método renomeado pra `primeiroCorteHtml()` e reescrito na rodada
 *    4 — ver mais abaixo, a técnica de ÍCONE foi abandonada).
 *    A legenda mostra o símbolo certo conforme a orientação do corte
 *    detectado.
 *
 * 2. **Grupos (legenda A/B/C) separando peças idênticas** — o usuário
 *    reparou no PDF real peças com a MESMA descrição e MESMO tamanho
 *    caindo em letras diferentes (ex. duas peças "Base 18" 350×664,
 *    uma grupo A outra grupo B). Causa: `montarGruposPorPeca())`
 *    agrupava por `componente_id` — cada `ItemProjeto` tem seus
 *    próprios `componente_id`s, então 2 móveis iguais no mesmo
 *    projeto geravam 2 IDs diferentes pra peças visualmente idênticas.
 *    Reconciliando com a decisão da rodada anterior ("lateral e porta
 *    do mesmo tamanho são peças DIFERENTES" — ou seja, nunca só
 *    tamanho): a chave de agrupamento certa é DESCRIÇÃO + TAMANHO
 *    (par de dimensões, sem importar rotação — 350×700 é a mesma peça
 *    que 700×350), não mais `componente_id` — `chaveGrupoPeca()`. Isso
 *    também exigiu recalcular `total` (agora soma quantas peças no
 *    projeto INTEIRO batem com a chave, contando direto no nesting —
 *    `montarGruposPorPeca()` deixou de precisar de `$paraServicos`) e
 *    a numeração "instância/totalLetra" (agora um contador GLOBAL por
 *    chave, `$contadorInstanciaPorChave` em `montarHtmlPreviewCorte()`
 *    — não dá mais pra reusar o `instancia` que cada motor de nesting
 *    devolve, que é por `componente_id`, não por chave nova).
 *
 * 3-4. **Tracejado deixa de significar "rotacionada" e passa a marcar
 *    o lado com fita de borda, com legenda em algarismo romano** — a
 *    versão anterior tracejava a BORDA INTEIRA da caixa quando a peça
 *    estava rotacionada (redundante com o ícone ⟲, que já mostra isso
 *    sozinho). Usuário pediu: rotação = só o ícone ⟲; tracejado = só
 *    onde vai fita de borda de verdade (`fita_borda_1..4` do
 *    componente). Esclarecimento do próprio usuário em seguida: o
 *    tracejado NÃO substitui o contorno da caixa (que continua sólido,
 *    sempre) — é uma linha tracejada ADICIONAL, ligeiramente pra
 *    DENTRO da peça (`linhaFitaHtml()`, inset de 1mm), só nos lados
 *    que têm fita ativa, com um numeral romano (I/II/III/IV = ordem
 *    de `fita_borda_1..4`) do lado de dentro da linha — porque ROTAÇÃO
 *    pode trocar qual lado FÍSICO (topo/base/esquerda/direita)
 *    corresponde a qual `fita_borda_N`, então um numeral fixo por
 *    posição não seria confiável; o numeral é resolvido por peça em
 *    `bordasPorLado()`.
 *
 *    Geometria: nem o motor "Nativa" nem o "Packing Solver" expõem no
 *    resultado da peça qual eixo (o do `largura` ou o da
 *    `profundidade` original) virou `largura_corte` — só o desenho
 *    final (`largura_corte`/`comprimento_corte`) e o flag composto
 *    `rotacionado`, que sozinho não basta (existe uma 2ª troca de eixo
 *    possível por "veio travado" dentro do próprio motor, também não
 *    exposta). Em vez de mexer nos dois motores só pra expor isso,
 *    `bordasPorLado()` descobre por COMPARAÇÃO: compara `largura_corte`
 *    da peça já desenhada com a `largura`/`profundidade` ORIGINAIS do
 *    componente (de `$paraServicos`, olhado por `componente_id`) — bate
 *    com uma delas dentro de uma tolerância, e a partir disso sabe se
 *    o par {fita_borda_3, fita_borda_4} (que acompanha `largura`, ver
 *    `PlanoCorteFitasService`) caiu no eixo topo/base ou
 *    esquerda/direita da caixa desenhada (o par {fita_borda_1,
 *    fita_borda_2}, que acompanha `profundidade`, fica sempre no eixo
 *    oposto). Não depende de saber COMO o motor decidiu girar/travar
 *    veio, só do resultado final — funciona igual pros dois motores de
 *    nesting.
 *
 *    `montarHtmlPreviewCorte()` passou a receber `$paraServicos` (só
 *    pra esse lookup por `componente_id` — `$componentesPorId`). Nova
 *    linha na legenda explica o esquema ("tracejado interno = lado com
 *    fita de borda").
 *
 * 5. **Logo achatado/desproporcional + alinhamento** — antes o `<img>`
 *    só tinha `height` fixa em CSS (`.titulo-topo-logo-img`), sem
 *    `width` correspondente — suspeita de que o Dompdf real não
 *    preserva a proporção original da imagem só com `height` setada
 *    (o navegador/`wkhtmltopdf` fazem isso automaticamente, mas não dá
 *    pra confiar nisso no Dompdf, mesmo padrão de cautela das
 *    correções anteriores desta revisão). Corrigido calculando a
 *    proporção real da imagem em PHP (`getimagesizefromstring()`, em
 *    `carregarMarca()`) e escrevendo `width`/`height` EXPLÍCITOS (em
 *    mm) direto no `style` do `<img>`, sem depender de nenhum
 *    auto-cálculo do Dompdf. Alinhamento: a 1ª faixa (amarela,
 *    título+logo) tinha 2 colunas de largura automática, sem relação
 *    nenhuma com a 2ª faixa (cinza, Obra/Projeto/Revisão) — usuário
 *    pediu o logo alinhado a partir da coluna "Projeto". `.titulo-topo-texto`
 *    ganhou a MESMA largura (62%) de `.titulo-dados-obra`, então a
 *    borda entre título/logo na faixa amarela cai exatamente onde
 *    "Projeto" começa na faixa cinza — `.titulo-topo-logo` passou de
 *    `text-align:right` (flush na borda da página) pra `text-align:left`
 *    (começa logo depois dessa borda de 62%).
 *
 * 6. **Títulos das seções em CAIXA BAIXA → Primeira Maiúscula** — a
 *    versão anterior forçava `text-transform: lowercase` (títulos
 *    "materiais"/"corte"/"etiquetas" em minúsculas) — usuário pediu
 *    Sentence case. Removido o `text-transform` (mais uma regra CSS a
 *    menos pro Dompdf interpretar) e as strings passadas pra
 *    `montarCabecalhoTopo()` já vêm capitalizadas
 *    ("Materiais"/"Corte"/"Etiquetas").
 *
 * IMPORTANTE: mesma ressalva de sempre — validado só com `wkhtmltopdf`
 * neste ambiente (sem Dompdf real). Os itens 1 e 5 (símbolo sem
 * `transform`, proporção do logo calculada em PHP) são apostas
 * DIRECIONADAS pra evitar os mesmos dois padrões de CSS que já
 * falharam no Dompdf real nesta tarefa (posicionamento "esperto"/
 * `transform` e cor calculada fora do PHP) — ainda assim, só o
 * usuário testando no Perseu-FA confirma de fato.
 *
 * ## Revisão 2026-09-15 (rodada 4) — ajustes depois do 3º PDF real
 *
 * 1. **Numeral da fita sobrepondo o título da peça** — o numeral
 *    romano (ver rodada 3, itens 3-4) ficava logo pra dentro do
 *    tracejado, mesma região onde já começa o texto do código/nome da
 *    peça (o padding da caixa é pequeno). Corrigido posicionando o
 *    numeral estritamente ENTRE o tracejado e o contorno sólido da
 *    caixa (não mais pra dentro do tracejado) — `linhaFitaHtml()`.
 *
 * 2. **Legenda da fita virou tabela por especificação real** — antes
 *    era uma nota genérica fixa ("tracejado interno = lado com fita de
 *    borda (I–IV)"), sem dizer QUAL fita cada numeral representa.
 *    Usuário pediu uma tabela tipo "I = Fita branco Tx 0,4 mm / II =
 *    Fita Preto Tx 1,0 mm". Isso exigiu trocar o esquema de numeração:
 *    antes o numeral vinha de um SLOT fixo (`fita_borda_1`→I,
 *    `fita_borda_2`→II...), que não corresponde a uma especificação
 *    única e describível (o slot 1 pode ser uma fita branca numa peça
 *    e preta noutra). Agora `bordasPorLado()` devolve `espessura`/`cor`
 *    crus por lado (não mais um numeral), e `montarHtmlPreviewCorte()`
 *    mantém um registro POR CHAPA (`$especificacoesFitaPorChapa`,
 *    reiniciado a cada chapa — diferente da letra do grupo de peça,
 *    que é global no projeto) que atribui o próximo numeral romano
 *    (`numeralRomano()`) à primeira vez que aparece cada combinação
 *    ÚNICA (espessura, cor) naquela chapa; ocorrências seguintes da
 *    mesma combinação reusam o numeral já atribuído. A legenda imprime
 *    uma linha por numeral REALMENTE usado na chapa (nada aparece se a
 *    chapa não tiver fita ativa nenhuma).
 *
 * 3. **Cores parecidas entre grupos diferentes** — o passo áureo de matiz
 *    (`~137,508°` por índice, ver "Revisão 2026-09-14") sozinho não
 *    basta: a percepção humana não distingue matiz de forma uniforme
 *    (dois verdes vizinhos parecem iguais), e ainda mais em tons
 *    pasteis (mesma saturação/luminosidade pra todo grupo). Corrigido
 *    somando 3 "bandas" de saturação/luminosidade que alternam por
 *    índice (`$indice % 3`) junto com o passo de matiz — `corGrupo()`
 *    — então grupos vizinhos no índice diferem em mais de uma
 *    dimensão de cor, não só matiz.
 *
 * 4. **Nome da peça só na legenda, não na própria caixa** — usuário
 *    pediu o nome/descrição da peça direto no desenho da caixa, não só
 *    referenciável pela letra A/B/C na legenda. Adicionado
 *    `e($peca['descricao'])` numa linha própria dentro da caixa, entre
 *    a linha do código/ícone de rotação e a linha de dimensões —
 *    `overflow:hidden` em `.caixa-peca` já recorta com segurança em
 *    peças pequenas, mesmo comportamento que as dimensões já tinham.
 *
 * 5. **Tamanho só na caixa, não na legenda por letra** — pedido em
 *    seguida do item 4: o tamanho (maior×menor, arredondado) também
 *    aparece agora em cada linha da legenda A/B/C, guardado uma vez
 *    por chave em `montarGruposPorPeca()` (`$grupos[$chave]['tamanho']`,
 *    captado na primeira aparição da peça, igual à descrição).
 *
 * 6. **Marcação do 1º corte — de ÍCONE apontando pra fora da chapa pra
 *    LINHA tracejada vermelha atravessando a chapa inteira**: depois
 *    de confirmar por imagem que o requisito era "mesma linha/coluna
 *    do corte, sempre do lado de FORA da chapa, apontando pra dentro"
 *    (o que a técnica de ícone — caixa `.seta-primeiro-corte` filha de
 *    `.chapa-container`, `left`/`top` negativos — já tentava fazer há
 *    várias revisões), o usuário testou no Dompdf real e reportou dois
 *    problemas de precisão nessa técnica: (a) no caso vertical, o
 *    ícone simplesmente não aparecia onde esperado — provável colisão
 *    com o texto "Peças: X — Cortes: Y..." logo acima de
 *    `.chapa-container` (só 2px de `margin-top`, insuficiente pros
 *    `-4mm` de deslocamento pra fora); (b) no caso horizontal, o
 *    usuário reportou o ícone "pra baixo da separação da peça, não
 *    onde tem que ser o corte exato" — mesmo com a matemática de
 *    centralização batendo exatamente no centro geométrico do vão do
 *    kerf, um caractere de texto centralizado por `line-height` está
 *    sujeito à métrica/baseline da FONTE do Dompdf real, que não é
 *    garantida bater com o centro geométrico da caixa (episódio a mais
 *    numa lista já longa de comportamentos de posicionamento "esperto"
 *    que falham só no Dompdf real nesta tarefa).
 *
 * 7. **Solução do usuário**: em vez de um ícone que aponta pra linha
 *    de corte, desenhar a PRÓPRIA linha — tracejada, vermelha,
 *    atravessando a chapa INTEIRA na posição exata do corte
 *    (`width:100%`/`height:100%` + `border-top`/`border-left: dashed`,
 *    puro box-model, sem texto/fonte/baseline nenhum envolvido). Ver
 *    `primeiroCorteHtml()` (renomeado de `setaPrimeiroCorteHtml()`) —
 *    resolve os dois problemas do item 6 de uma vez: não tem caixa
 *    auxiliar pra centralizar (a borda nasce exatamente em
 *    `posicao*escala`) e não precisa "vazar" pra fora da chapa nem de
 *    espaço reservado acima dela (a linha já está DENTRO do desenho,
 *    sobre o corte de verdade). A legenda trocou o símbolo (»/▼▼) por
 *    uma amostra da própria linha tracejada vermelha.
 *
 * IMPORTANTE: mesma ressalva de sempre — validado só com `wkhtmltopdf`
 * neste ambiente (sem Dompdf real); confirmação de verdade só quando o
 * usuário testar no Perseu-FA.
 */
final class PlanoCorteRelatorioService
{
    // Valores de marca usados só como FALLBACK — ver docblock da
    // classe, "Revisão 2026-09-15 (marca dinâmica)": entram em jogo
    // apenas se `carregarMarca()` não conseguir ler
    // `Webkul\Support\Settings\BrandSettings` (tabela de settings
    // vazia/indisponível) ou o arquivo do logo não existir no disco
    // `public`. Antes desta revisão eram os únicos valores usados
    // (fixos no código, por decisão anterior do usuário — decisão
    // revertida nesta revisão).
    private const COR_PRIMARIA_PADRAO = '#ffc000';

    private const COR_CINZA_PADRAO = '#52525c';

    private const NOME_MARCA_PADRAO = 'F.A. MARCENARIA';

    // Altura fixa (impressão, não a "Altura do logo" em `rem` da tela
    // de branding — ver docblock da classe, item 4 da revisão
    // anterior) — a LARGURA correspondente é calculada em
    // `carregarMarca()` a partir da proporção real do PNG, nunca
    // deixada pro Dompdf inferir sozinha (ver "Revisão 2026-09-15
    // (rodada 3)", item 5).
    private const LOGO_ALTURA_MM = 8.0;

    // Cor da linha tracejada que marca o 1º corte no desenho da chapa
    // — ver docblock de `primeiroCorteHtml()`, "Revisão 2026-09-15
    // (rodada 4)": vermelho puro pra contrastar com qualquer cor de
    // grupo de peça (as cores de `corGrupo()` são sempre tons claros/
    // pasteis, nunca vermelho saturado, ver `estilosBase()`).
    private const COR_PRIMEIRO_CORTE = '#dc2626';

    private readonly string $corPrimaria;

    private readonly string $corCinza;

    private readonly ?string $logoBase64;

    private readonly ?float $logoLarguraMm;

    public function __construct(
        private readonly string $tipoEquipamento,
        private readonly float $espessuraFerramenta,
        private readonly float $limpezaBordas,
        private readonly string $otimizador = 'nativa',
    ) {
        [$this->corPrimaria, $this->corCinza, $this->logoBase64, $this->logoLarguraMm] = $this->carregarMarca();
    }

    /**
     * Lê a marca (cores + logo) de Configurações → Marca
     * (`/admin/settings/manage-branding`) — ver docblock da classe,
     * "Revisão 2026-09-15 (marca dinâmica)". Mesma fonte que
     * `App\Http\Middleware\ApplyBrandSettings` usa pra pintar o
     * próprio painel Filament: `settings(BrandSettings::class)`
     * (`spatie/laravel-settings`, repositório
     * `CompanyAwareSettingsRepository` — resolve a empresa certa do
     * contexto atual automaticamente). `try/catch` amplo: qualquer
     * falha (tabela de settings indisponível, arquivo do logo
     * ausente no disco, etc.) cai pros valores fixos de
     * `self::COR_PRIMARIA_PADRAO`/`self::COR_CINZA_PADRAO` (sem logo,
     * ver `montarCabecalhoTopo()`) em vez de quebrar a geração do
     * PDF.
     *
     * @return array{0: string, 1: string, 2: ?string, 3: ?float} [corPrimaria, corCinza, logoBase64 (`data:` URI ou null), logoLarguraMm]
     */
    private function carregarMarca(): array
    {
        $corPrimaria = self::COR_PRIMARIA_PADRAO;
        $corCinza = self::COR_CINZA_PADRAO;
        $logoBase64 = null;
        $logoLarguraMm = null;

        try {
            $brand = settings(\Webkul\Support\Settings\BrandSettings::class);

            if (! empty($brand->primary_color)) {
                $corPrimaria = $brand->primary_color;
            }

            if (! empty($brand->gray_color)) {
                $corCinza = $brand->gray_color;
            }

            if (! empty($brand->light_logo) && \Illuminate\Support\Facades\Storage::disk('public')->exists($brand->light_logo)) {
                // Dompdf tem `isRemoteEnabled=false` (ver `gerar()`) —
                // só path local/`data:` URI funciona, nunca URL
                // remota, daí embutir o PNG em base64 em vez de usar
                // `Storage::disk('public')->url(...)` (que é o que
                // `ApplyBrandSettings` usa, mas serve pro navegador,
                // não pro Dompdf).
                $caminhoLogo = \Illuminate\Support\Facades\Storage::disk('public')->path($brand->light_logo);
                $conteudo = @file_get_contents($caminhoLogo);

                if ($conteudo !== false) {
                    $mime = @mime_content_type($caminhoLogo) ?: 'image/png';
                    $logoBase64 = 'data:'.$mime.';base64,'.base64_encode($conteudo);

                    // Largura correspondente à altura fixa
                    // (`self::LOGO_ALTURA_MM`), preservando a
                    // proporção REAL do arquivo — calculada aqui em
                    // PHP e escrita explícita no `style` do `<img>`
                    // (ver `montarCabecalhoTopo()`) porque só `height`
                    // em CSS não é confiável pro Dompdf manter a
                    // proporção sozinho (ver docblock da classe,
                    // "Revisão 2026-09-15 (rodada 3)", item 5).
                    $dimensoes = @getimagesizefromstring($conteudo);

                    if ($dimensoes !== false && $dimensoes[0] > 0 && $dimensoes[1] > 0) {
                        $logoLarguraMm = self::LOGO_ALTURA_MM * ($dimensoes[0] / $dimensoes[1]);
                    }
                }
            }
        } catch (\Throwable) {
            // Sem settings disponível neste contexto/ambiente — segue
            // com os valores fixos padrão (ver docblock do método).
        }

        return [$corPrimaria, $corCinza, $logoBase64, $logoLarguraMm];
    }

    /**
     * @return array{caminho: string, nome_arquivo: string}
     */
    public function gerar(Projeto $projeto): array
    {
        /** @var Collection<int, ItemProjetoComponente> $componentes */
        $componentes = $projeto->itens()->with('componentes')->get()->flatMap(fn ($item) => $item->componentes);

        $madeira = $componentes->where('componentizado', true)->values();
        $ferragens = $componentes->where('componentizado', false)->values();

        $paraServicos = $madeira->map(fn (ItemProjetoComponente $c): array => [
            'id'                => $c->id,
            'referencia'        => (string) $c->referencia,
            'descricao'         => (string) $c->descricao,
            'largura'           => (float) $c->largura,
            'profundidade'      => (float) $c->profundidade,
            'repeticao'         => (int) $c->repeticao,
            'material'          => $c->material,
            'cor'               => $c->cor,
            'espessura'         => $c->espessura !== null ? (float) $c->espessura : null,
            'fornecedor'        => $c->fornecedor,
            'chapa_largura'     => $c->chapa_largura !== null ? (float) $c->chapa_largura : null,
            'chapa_comprimento' => $c->chapa_comprimento !== null ? (float) $c->chapa_comprimento : null,
            'veio_travado'      => (bool) $c->veio_travado,
            // Ver `PromobXmlParser`/`PlanoCorteNestingService`,
            // "Revisão 2026-09-17" — campo que os motores de nesting
            // usam de fato pra travar orientação; `veio_travado` acima
            // ficou só de compatibilidade.
            'veio_eixo_fixo'    => $c->veio_eixo_fixo,
            'fita_borda_1'      => $c->fita_borda_1 !== null ? (float) $c->fita_borda_1 : null,
            'fita_borda_2'      => $c->fita_borda_2 !== null ? (float) $c->fita_borda_2 : null,
            'fita_borda_3'      => $c->fita_borda_3 !== null ? (float) $c->fita_borda_3 : null,
            'fita_borda_4'      => $c->fita_borda_4 !== null ? (float) $c->fita_borda_4 : null,
            'fita_cor'          => $c->fita_cor,
        ])->all();

        $nesting = $this->otimizador === 'packing_solver'
            ? PlanoCortePackingSolverService::gerar($paraServicos, $this->espessuraFerramenta, $this->limpezaBordas, $this->tipoEquipamento)
            : PlanoCorteNestingService::gerar($paraServicos, $this->espessuraFerramenta, $this->limpezaBordas, $this->tipoEquipamento);
        $fitas = PlanoCorteFitasService::gerar($paraServicos);

        $acessorios = $ferragens
            ->groupBy('descricao')
            ->map(function (Collection $grupo, string $descricao): array {
                $primeiro = $grupo->first();

                return [
                    'descricao'  => $descricao,
                    'referencia' => (string) $primeiro->referencia,
                    'largura'    => (float) $primeiro->largura,
                    'altura'     => (float) $primeiro->altura,
                    'quantidade' => (float) $grupo->sum(fn (ItemProjetoComponente $c) => (float) $c->repeticao * (float) $c->quantidade),
                    'custo'      => (float) $grupo->sum('custo'),
                    'preco'      => (float) $grupo->sum('preco'),
                ];
            })
            ->values()
            ->all();

        $gruposPorPeca = $this->montarGruposPorPeca($nesting);

        $html = $this->montarHtmlMateriais($projeto, $nesting, $fitas, $acessorios)
            .$this->montarHtmlPreviewCorte($projeto, $nesting, $gruposPorPeca, $paraServicos)
            .$this->montarHtmlEtiquetas($projeto, $nesting);

        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->setPaper('a4', 'landscape');
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->render();

        $nomeArquivo = 'Producao - '.($projeto->numero_projeto ?? $projeto->id).'.pdf';
        $caminho = tempnam(sys_get_temp_dir(), 'plano-corte-').'.pdf';
        file_put_contents($caminho, $dompdf->output());

        return ['caminho' => $caminho, 'nome_arquivo' => $nomeArquivo];
    }

    /**
     * Agrupa as peças por DESCRIÇÃO + TAMANHO (não por `componente_id`
     * — ver docblock da classe, "Revisão 2026-09-15 (rodada 3)", item
     * 2), atribuindo letra (A, B, C...) e cor suave ESTÁVEIS por
     * chave, válidas pro PROJETO INTEIRO. Ordem de atribuição: ordem
     * de primeira aparição varrendo o nesting (determinístico).
     *
     * NUNCA só o tamanho sozinho (decisão confirmada com o usuário
     * desde a revisão "cabeçalho padrão de marca": "lateral e porta
     * do mesmo tamanho são peças diferentes") — a chave leva descrição
     * junto (`chaveGrupoPeca()`), então só agrupa peças com a MESMA
     * descrição E o MESMO par de dimensões. `componente_id` sozinho
     * separava demais: cada `ItemProjeto` tem seus próprios IDs, então
     * 2 móveis idênticos no mesmo projeto geravam letras diferentes
     * pra peças visualmente iguais — usuário reparou isso no PDF real.
     *
     * @param  array{grupos: array<int, array<string, mixed>>, nao_classificados: array<int, array<string, mixed>>}  $nesting
     * @return array<string, array{letra: string, cor_fundo: string, cor_borda: string, total: int, descricao: string, indice: int, tamanho: string}>  indexado pela chave de `chaveGrupoPeca()`
     */
    private function montarGruposPorPeca(array $nesting): array
    {
        $totalPorChave = [];
        $primeiraAparicao = [];
        $indice = 0;

        foreach ($nesting['grupos'] as $grupoMaterial) {
            foreach ($grupoMaterial['chapas'] as $chapa) {
                foreach ($chapa['pecas'] as $peca) {
                    $chave = $this->chaveGrupoPeca($peca);
                    $totalPorChave[$chave] = ($totalPorChave[$chave] ?? 0) + 1;

                    if (! isset($primeiraAparicao[$chave])) {
                        // Tamanho pro texto da legenda (pedido do usuário,
                        // "Revisão 2026-09-15 (rodada 4)", item 4): maior
                        // dimensão primeiro, só pra exibição — não usa a
                        // ordenação da CHAVE (`chaveGrupoPeca()`), que é
                        // outra ordenação (menor/maior) feita só pra
                        // casar peça rotacionada com não-rotacionada.
                        $dimensoesTexto = [round($peca['largura_corte']), round($peca['comprimento_corte'])];
                        rsort($dimensoesTexto);

                        $primeiraAparicao[$chave] = [
                            'indice'    => $indice,
                            'descricao' => $peca['descricao'],
                            'tamanho'   => $dimensoesTexto[0].'&times;'.$dimensoesTexto[1],
                        ];
                        $indice++;
                    }
                }
            }
        }

        $grupos = [];

        foreach ($primeiraAparicao as $chave => $info) {
            $cores = $this->corGrupo($info['indice']);

            $grupos[$chave] = [
                'letra'     => $this->letraGrupo($info['indice']),
                'cor_fundo' => $cores['fundo'],
                'cor_borda' => $cores['borda'],
                'total'     => $totalPorChave[$chave],
                'descricao' => $info['descricao'],
                'indice'    => $info['indice'],
                'tamanho'   => $info['tamanho'],
            ];
        }

        return $grupos;
    }

    /**
     * Chave de agrupamento de `montarGruposPorPeca()`: descrição +
     * par de dimensões desenhadas ORDENADO (`largura_corte`/
     * `comprimento_corte`, arredondadas a 0,1mm) — ordenar o par faz
     * uma peça de 350×700 contar como a MESMA peça esteja ela
     * desenhada rotacionada ou não nesta chapa.
     */
    private function chaveGrupoPeca(array $peca): string
    {
        $dimensoes = [round($peca['largura_corte'], 1), round($peca['comprimento_corte'], 1)];
        sort($dimensoes);

        return trim($peca['descricao']).'|'.implode('x', $dimensoes);
    }

    /**
     * Letra do grupo em base 26 (A, B, ..., Z, AA, AB, ...) — mesmo
     * esquema de nome de coluna de planilha, nunca esgota mesmo em
     * projetos com muitos componentes distintos.
     */
    private function letraGrupo(int $indice): string
    {
        $letra = '';

        do {
            $letra = chr(65 + ($indice % 26)).$letra;
            $indice = intdiv($indice, 26) - 1;
        } while ($indice >= 0);

        return $letra;
    }

    /**
     * Cor suave (pastel) determinística por índice de grupo — passo de
     * ângulo áureo (~137,508°) no matiz HSL espalha bem as cores
     * mesmo com muitos grupos, sem depender de uma paleta fixa que
     * poderia se esgotar. Fundo claro (luminosidade alta) pro texto
     * da peça continuar legível por cima; borda na MESMA matiz, mais
     * escura, serve de contorno da caixa e da legenda.
     *
     * Revisão 2026-09-15: antes devolvia a cor como string CSS
     * `hsl(matiz, sat%, lum%)` — o `wkhtmltopdf` (usado pra validar
     * esta classe neste ambiente) interpreta normalmente, mas o
     * Dompdf REAL não (usuário reportou peças/legenda saindo
     * brancas/ocas num PDF de produção real, só com a borda preta
     * aparecendo). `hslParaHex()` faz a mesma conversão em PHP e
     * devolve hex — formato que o Dompdf real comprovadamente
     * suporta (é o mesmo das cores de marca, ver
     * `COR_PRIMARIA_PADRAO`).
     *
     * Revisão 2026-09-15 (rodada 4): usuário pediu pra evitar cores
     * parecidas entre peças diferentes. O passo de ângulo áureo já
     * espalha bem o MATIZ (evita matizes vizinhos ficarem perto um do
     * outro mesmo depois de muitos grupos), mas matiz sozinho não
     * basta — o olho humano não distingue matiz igualmente bem em toda
     * a roda de cores (dois tons de verde, por exemplo, confundem mais
     * fácil que vermelho vs azul), e isso piora ainda mais em pastel
     * (saturação baixa, luminosidade alta). Corrigido alternando
     * TAMBÉM saturação/luminosidade a cada índice, em 3 "bandas" —
     * grupos vizinhos (os que mais aparecem juntos numa mesma chapa)
     * passam a diferir em pelo menos 2 dimensões da cor, não só 1,
     * reduzindo bastante a chance de duas cores parecerem iguais.
     *
     * @return array{fundo: string, borda: string}
     */
    private function corGrupo(int $indice): array
    {
        $matiz = ((int) round($indice * 137.508)) % 360;

        $bandas = [
            ['sat' => 55, 'lumFundo' => 85, 'lumBorda' => 45],
            ['sat' => 70, 'lumFundo' => 80, 'lumBorda' => 38],
            ['sat' => 42, 'lumFundo' => 90, 'lumBorda' => 55],
        ];
        $banda = $bandas[$indice % count($bandas)];

        return [
            'fundo' => $this->hslParaHex($matiz, $banda['sat'], $banda['lumFundo']),
            'borda' => $this->hslParaHex($matiz, $banda['sat'], $banda['lumBorda']),
        ];
    }

    /**
     * Conversão HSL → hex (`#rrggbb`) — ver docblock de `corGrupo()`.
     * Fórmula padrão HSL→RGB (matiz em graus 0-360, saturação e
     * luminosidade em % 0-100).
     */
    private function hslParaHex(int $matiz, int $saturacaoPct, int $luminosidadePct): string
    {
        $s = $saturacaoPct / 100;
        $l = $luminosidadePct / 100;

        $c = (1 - abs(2 * $l - 1)) * $s;
        $x = $c * (1 - abs(fmod($matiz / 60, 2) - 1));
        $m = $l - $c / 2;

        [$r, $g, $b] = match (true) {
            $matiz < 60  => [$c, $x, 0.0],
            $matiz < 120 => [$x, $c, 0.0],
            $matiz < 180 => [0.0, $c, $x],
            $matiz < 240 => [0.0, $x, $c],
            $matiz < 300 => [$x, 0.0, $c],
            default      => [$c, 0.0, $x],
        };

        $paraHex = fn (float $v): string => str_pad(dechex((int) round(($v + $m) * 255)), 2, '0', STR_PAD_LEFT);

        return '#'.$paraHex($r).$paraHex($g).$paraHex($b);
    }

    /**
     * Cabeçalho de marca, igual em todas as páginas (Materiais,
     * Preview de Corte, Etiquetas) — ver docblock da classe,
     * "cabeçalho padrão de marca". Duas faixas: título da seção +
     * logo (fundo Primário) e Obra/Projeto/Revisão (fundo Cinza).
     */
    private function montarCabecalhoTopo(string $tituloSecao, Projeto $projeto): string
    {
        $revisao = str_pad((string) ($projeto->revisao ?? 0), 2, '0', STR_PAD_LEFT);

        // Logo real (PNG "Logo Claro" de Configurações → Marca,
        // embutido em base64 por `carregarMarca()`) com fallback em
        // texto — ver docblock da classe, "Revisão 2026-09-15 (marca
        // dinâmica)". `width`/`height` explícitos em mm (não só
        // `height` numa classe CSS) — ver "Revisão 2026-09-15 (rodada
        // 3)", item 5: só `height` deixava o Dompdf real achatar a
        // imagem, sem preservar a proporção original.
        if ($this->logoBase64 !== null) {
            $estiloLogo = 'height:'.self::LOGO_ALTURA_MM.'mm;'
                .($this->logoLarguraMm !== null ? ' width:'.round($this->logoLarguraMm, 1).'mm;' : '');
            $logoHtml = '<img class="titulo-topo-logo-img" style="'.$estiloLogo.'" src="'.$this->logoBase64.'" alt="'.e(self::NOME_MARCA_PADRAO).'">';
        } else {
            $logoHtml = e(self::NOME_MARCA_PADRAO);
        }

        return '<table class="titulo-topo"><tr>'
            .'<td class="titulo-topo-texto">'.e($tituloSecao).'</td>'
            .'<td class="titulo-topo-logo">'.$logoHtml.'</td>'
            .'</tr></table>'
            .'<table class="titulo-dados"><tr>'
            .'<td class="titulo-dados-obra" rowspan="2"><span class="titulo-dados-label">Obra</span><br>'.e((string) $projeto->descricao).'</td>'
            .'<td class="titulo-dados-label">Projeto</td>'
            .'<td class="titulo-dados-valor">'.e((string) ($projeto->numero_projeto ?? $projeto->id)).'</td>'
            .'</tr><tr>'
            .'<td class="titulo-dados-label">Revisão</td>'
            .'<td class="titulo-dados-valor">'.$revisao.'</td>'
            .'</tr></table>';
    }

    /**
     * Aproximação geométrica do "1º corte" — ver docblock da classe,
     * "seta do 1º corte". Procura, entre TODAS as linhas candidatas
     * (bordas de peças desta chapa), a mais próxima do topo (linha
     * horizontal) ou, se nenhuma horizontal servir, a mais próxima da
     * esquerda (linha vertical) que DIVIDE a chapa de ponta a ponta —
     * sem cruzar nenhuma peça pela metade E com peça real dos dois
     * lados da linha.
     *
     * Revisão 2026-09-15: a 1ª versão só exigia "nenhuma peça
     * cruzando" — isso deixava passar a margem da limpeza de bordas
     * (nenhuma peça cruza ali, mas também nenhuma peça existe ACIMA
     * dela, é só o vão da limpeza) como se fosse o "1º corte", o que
     * o usuário apontou como errado ("desconsiderar a limpeza da
     * peça"). Corrigido exigindo pelo menos 1 peça de CADA lado da
     * linha (`$temAntes`/`$temDepois`) — a margem de limpeza nunca
     * tem peça do lado de fora dela, então nunca mais é escolhida,
     * SEM precisar conhecer `$this->limpezaBordas` aqui (a exigência
     * geométrica já resolve isso sozinha, funciona igual pros dois
     * motores de nesting).
     *
     * Revisão 2026-09-15 (rodada 4, item 8): CHEGOU a ser trocado pra
     * marcar a borda externa do retângulo-envelope de todas as peças
     * (interpretando "deslocar mais [a direita/abaixo] após as peças"
     * como pedido pra pular pra depois do ÚLTIMO grupo, não só do
     * primeiro) — usuário corrigiu em seguida: o pedido de deslocar
     * era só pra sair de cima da PEÇA no PDF real ("aparentemente no
     * pdf ele parece estar cortando a peça"), não pra mudar qual
     * fronteira marcar. Revertido pra esta versão (marca a PRIMEIRA
     * divisão interna válida entre grupos de peça, como sempre foi).
     * A causa do "corte pela peça" no Dompdf real ainda não está
     * confirmada (a matemática de centralização aqui já garante que
     * `posicao` cai exatamente no meio do vão do kerf entre 2 peças
     * que se tocam — ver `primeiroCorteHtml()`); pendente confirmação
     * do usuário com um print do caso exato antes de mexer de novo.
     *
     * @param  array<int, array{x: float, y: float, largura_corte: float, comprimento_corte: float}>  $pecas  coordenadas REAIS em mm
     * @return array{orientacao: 'horizontal'|'vertical', posicao: float}|null  `posicao` em mm reais (não escalada)
     */
    private function primeiroCorte(array $pecas, float $chapaLargura, float $chapaComprimento): ?array
    {
        $tolerancia = 0.5;

        $candidatosY = [];

        foreach ($pecas as $p) {
            $candidatosY[] = $p['y'];
            $candidatosY[] = $p['y'] + $p['comprimento_corte'];
        }

        $candidatosY = array_unique(array_filter($candidatosY, fn (float $y): bool => $y > $tolerancia && $y < $chapaComprimento - $tolerancia));
        sort($candidatosY);

        foreach ($candidatosY as $y) {
            $cruzaAlguma = false;
            $temAntes = false;
            $temDepois = false;

            foreach ($pecas as $p) {
                $topo = $p['y'];
                $base = $p['y'] + $p['comprimento_corte'];

                if ($topo < $y - $tolerancia && $base > $y + $tolerancia) {
                    $cruzaAlguma = true;
                    break;
                }

                if ($base <= $y + $tolerancia) {
                    $temAntes = true;
                }

                if ($topo >= $y - $tolerancia) {
                    $temDepois = true;
                }
            }

            if (! $cruzaAlguma && $temAntes && $temDepois) {
                return ['orientacao' => 'horizontal', 'posicao' => $y];
            }
        }

        $candidatosX = [];

        foreach ($pecas as $p) {
            $candidatosX[] = $p['x'];
            $candidatosX[] = $p['x'] + $p['largura_corte'];
        }

        $candidatosX = array_unique(array_filter($candidatosX, fn (float $x): bool => $x > $tolerancia && $x < $chapaLargura - $tolerancia));
        sort($candidatosX);

        foreach ($candidatosX as $x) {
            $cruzaAlguma = false;
            $temAntes = false;
            $temDepois = false;

            foreach ($pecas as $p) {
                $esquerda = $p['x'];
                $direita = $p['x'] + $p['largura_corte'];

                if ($esquerda < $x - $tolerancia && $direita > $x + $tolerancia) {
                    $cruzaAlguma = true;
                    break;
                }

                if ($direita <= $x + $tolerancia) {
                    $temAntes = true;
                }

                if ($esquerda >= $x - $tolerancia) {
                    $temDepois = true;
                }
            }

            if (! $cruzaAlguma && $temAntes && $temDepois) {
                return ['orientacao' => 'vertical', 'posicao' => $x];
            }
        }

        return null;
    }

    /**
     * HTML da marcação do 1º corte — ver docblock da classe.
     *
     * Revisão 2026-09-15 (rodada 4, itens 2-3): as versões anteriores
     * usavam um CARACTERE (»/▼▼) numa caixinha de 4mm posicionada do
     * lado de fora da chapa, centralizado por `line-height`. Depois de
     * VÁRIAS rodadas de ajuste fino nessa técnica (caixa 3,6mm vs 4mm,
     * caixa estreita demais pros 2 caracteres de "▼▼", caso vertical
     * sem espaço vertical suficiente pra "vazar" acima de
     * `.chapa-container` sem colidir com o texto "Peças: X — Cortes:
     * Y..." logo acima), o usuário sugeriu uma solução mais robusta:
     * em vez de um ÍCONE apontando pra linha de corte (que depende de
     * centralização de TEXTO/FONTE — historicamente a fonte de quase
     * todo bug de posicionamento fino nesta classe, ver "esta pra
     * baixo da separação da peça" no PDF real), desenhar a PRÓPRIA
     * linha do 1º corte, tracejada e vermelha, atravessando a chapa
     * INTEIRA (`width:100%`/`height:100%` + `border-*: dashed`, puro
     * box-model, sem depender de métrica de fonte/baseline nenhuma).
     * Isso: (a) elimina de vez qualquer imprecisão de centralização de
     * glifo — a borda tracejada nasce EXATAMENTE em `posicao*escala`,
     * sem caixa auxiliar pra centralizar nada; (b) já indica a posição
     * inequivocamente, sem precisar ficar do lado de fora da chapa
     * nem preocupar com espaço pra "vazar" (resolve os pedidos
     * anteriores de "mesma linha"/"fora da chapa" por construção, já
     * que a linha ESTÁ na linha de corte, literalmente); (c) funciona
     * igual pros dois motores de nesting e não depende de símbolo
     * "próprio" pra cada orientação — só a orientação da borda
     * (`border-top` vs `border-left`) muda.
     *
     * Renderizada como FILHA de `.chapa-container` (mesmo elemento que
     * já posiciona `.caixa-peca` corretamente no Dompdf real).
     *
     * Revisão 2026-09-15 (rodada 4, item 9): usuário reportou — com
     * print do PDF real — que a linha (mesmo já na posição geométrica
     * certa, confirmada correta no `wkhtmltopdf`) aparecia "passando
     * por trás da peça": no Dompdf real, como `$htmlPrimeiroCorte` é
     * escrito no HTML ANTES de `$caixas` (mesmo pai, ambos
     * `position:absolute`, nenhum com `z-index`), a ordem de pintura
     * ficou a critério da ordem de documento — e o Dompdf pintou as
     * caixas de peça (fundo OPACO) por cima da linha onde os dois
     * ficam próximos, escondendo pedaços dela. `.linha-primeiro-corte`
     * ganhou `z-index: 10` (ver `estilosBase()`) pra garantir — de
     * forma EXPLÍCITA, não por acaso de ordem de HTML — que a linha
     * sempre pinta por cima de qualquer caixa de peça, não importa a
     * ordem no documento.
     *
     * Revisão 2026-09-17 — medida numérica do 1º corte. Usuário pediu
     * (com prints do PDF real) uma indicação do TAMANHO da medida do
     * 1º corte, não só a linha: hoje o operador precisa somar as
     * dimensões das peças de um lado + a espessura da serra "de
     * cabeça" pra saber onde bater a régua. `$corte1['posicao']` (de
     * `primeiroCorte()`) já é a distância REAL, em mm, da BORDA FÍSICA
     * da chapa até a linha de corte — confirmado revendo
     * `PlanoCorteNestingService::colocarBlocoNoRetangulo()` (as
     * coordenadas `x`/`y` de cada peça já somam `limpezaBordas`, ou
     * seja, já são relativas ao canto FÍSICO da chapa, não da área
     * útil) e `.chapa-container` (`width`/`height` = `chapa_largura`/
     * `chapa_comprimento` * escala — a chapa física INTEIRA, não só a
     * área útil). Então `round($corte1['posicao'])` já é exatamente o
     * número que o operador quer: "encoste a régua a X mm da borda".
     *
     * Etiqueta ("badge") nova, `.medida-primeiro-corte`, deliberamente
     * SEM tentar centralizar em cima da linha (nem verticalmente pro
     * caso horizontal, nem horizontalmente pro caso vertical): o
     * histórico desta classe (ver revisão 2026-09-15 acima) é de bugs
     * de posicionamento fino toda vez que a centralização dependeu de
     * métrica de fonte/baseline (`line-height` tentando centralizar um
     * glifo numa caixa de altura fixa). Uma etiqueta de texto tem
     * largura VARIÁVEL (795mm vs 1500mm), então "centralizar" exigiria
     * ou medir a largura do texto (não dá, Dompdf não expõe isso antes
     * de renderizar) ou `transform: translateX(-50%)` (suporte
     * incerto/frágil no Dompdf real, nunca testado aqui). Em vez
     * disso: a etiqueta é ancorada por um canto (topo-esquerda) com um
     * deslocamento FIXO em mm (não calculado a partir de fonte) a
     * partir do ponto onde a linha do 1º corte encontra a borda da
     * chapa mais próxima da legenda (topo-esquerda do
     * `.chapa-container`) — mesma técnica de offset fixo já usada em
     * `$folgaKerfMm` nesta classe, não a técnica que historicamente
     * deu problema. `z-index: 11` (acima da linha, que é 10) garante
     * que a etiqueta pinta por cima da linha e de qualquer
     * `.caixa-peca` que toque a linha nesse canto.
     */
    private function primeiroCorteHtml(array $corte1, float $escala): string
    {
        $rotuloMedida = number_format(round($corte1['posicao']), 0, ',', '.').'mm';

        if ($corte1['orientacao'] === 'horizontal') {
            $topMm = $corte1['posicao'] * $escala;

            return '<div class="linha-primeiro-corte" style="left:0; top:'.$topMm.'mm; width:100%; height:0; border-top:0.6mm dashed '.self::COR_PRIMEIRO_CORTE.';"></div>'
                .'<div class="medida-primeiro-corte" style="left:1mm; top:'.$topMm.'mm;">'.e($rotuloMedida).'</div>';
        }

        $leftMm = $corte1['posicao'] * $escala;

        return '<div class="linha-primeiro-corte" style="top:0; left:'.$leftMm.'mm; height:100%; width:0; border-left:0.6mm dashed '.self::COR_PRIMEIRO_CORTE.';"></div>'
            .'<div class="medida-primeiro-corte" style="top:1mm; left:'.$leftMm.'mm;">'.e($rotuloMedida).'</div>';
    }

    /**
     * Descobre, pro DESENHO desta peça específica (já considerando
     * rotação), quais dos 4 lados (topo/direita/base/esquerda) têm
     * fita de borda ativa e qual `fita_borda_N` cada um representa —
     * ver docblock da classe, "Revisão 2026-09-15 (rodada 3)", itens
     * 3-4.
     *
     * `PlanoCorteFitasService` já documenta a convenção (**Revisão
     * 2026-09-16**: pareamento estava invertido, corrigido e
     * reconferido com 16 ocorrências reais via `PERIMETRO_FITA` — ver
     * docblock daquela classe): o par {`fita_borda_1`, `fita_borda_2`}
     * acompanha a `largura` ORIGINAL do componente (cada lado do par
     * tem comprimento = largura — são os 2 lados PARALELOS a ela,
     * ficam no eixo topo/base quando a peça está na orientação
     * natural); o par {`fita_borda_3`, `fita_borda_4`} acompanha a
     * `profundidade` ORIGINAL (eixo esquerda/direita na orientação
     * natural). Nem o motor "Nativa" nem o "Packing Solver" expõem no
     * resultado da peça qual eixo original virou o eixo horizontal
     * desenhado — só o desenho final (`largura_corte`) — então a
     * forma confiável de descobrir, sem tocar em nenhum dos dois
     * motores, é comparar `largura_corte` (a dimensão desenhada no
     * eixo horizontal) com a `largura`/`profundidade` ORIGINAIS do
     * componente: bate com uma delas (dentro de uma tolerância) e a
     * partir disso sabe-se se {fita_borda_1, fita_borda_2} caiu no
     * eixo topo/base (quando `largura_corte` bate com `largura`) ou
     * no eixo esquerda/direita (quando bate com `profundidade` — a
     * peça foi desenhada com os eixos trocados em relação ao
     * original, por rotação e/ou veio travado, não importa qual dos
     * dois motivos).
     *
     * Revisão 2026-09-15 (rodada 4): deixou de devolver um numeral FIXO
     * por slot (`fita_borda_1`→I, `_2`→II...) — o numeral agora
     * representa uma ESPECIFICAÇÃO de fita (espessura + cor), não uma
     * posição, porque o usuário pediu uma tabela por chapa tipo "I =
     * Fita Branco Tx 0,4mm" (ver `montarHtmlPreviewCorte()`, onde o
     * numeral de verdade é atribuído por especificação única
     * encontrada na chapa). Este método só devolve, por lado, SE tem
     * fita ativa e qual é a espessura/cor daquele lado especificamente
     * — quem decide o numeral é o chamador.
     *
     * @param  array{largura_corte: float, comprimento_corte: float}  $peca
     * @param  array{largura: float, profundidade: float, fita_borda_1: ?float, fita_borda_2: ?float, fita_borda_3: ?float, fita_borda_4: ?float, fita_cor: ?string}  $componente
     * @return array<string, array{ativo: bool, espessura: ?float, cor: ?string}>  chaves fixas: topo, direita, base, esquerda
     */
    private function bordasPorLado(array $peca, array $componente): array
    {
        $tolerancia = 0.5;
        $larguraBateNoEixoHorizontal = abs($peca['largura_corte'] - (float) $componente['largura']) <= $tolerancia;

        // Revisão 2026-09-16: par {1,2} acompanha LARGURA (era
        // {3,4} nesta linha, invertido — ver docblock do método).
        [$campoTopo, $campoBase] = $larguraBateNoEixoHorizontal
            ? ['fita_borda_1', 'fita_borda_2']
            : ['fita_borda_3', 'fita_borda_4'];

        // O par oposto (o que NÃO caiu no eixo horizontal) preenche o
        // eixo vertical.
        [$campoEsquerda, $campoDireita] = $larguraBateNoEixoHorizontal
            ? ['fita_borda_3', 'fita_borda_4']
            : ['fita_borda_1', 'fita_borda_2'];

        $cor = $componente['fita_cor'] ?? null;

        $monta = fn (string $campo): array => [
            'ativo'     => ! empty($componente[$campo]),
            'espessura' => $componente[$campo] ?? null,
            'cor'       => $cor,
        ];

        return [
            'topo'     => $monta($campoTopo),
            'base'     => $monta($campoBase),
            'esquerda' => $monta($campoEsquerda),
            'direita'  => $monta($campoDireita),
        ];
    }

    /**
     * HTML da linha tracejada (+ numeral romano) que marca UM lado com
     * fita de borda — ver docblock da classe e de `bordasPorLado()`.
     * NUNCA substitui o contorno da caixa (que continua sólido, ver
     * `.caixa-peca` em `estilosBase()`) — é uma linha ADICIONAL,
     * ligeiramente pra DENTRO (`$inset`), pedido explícito do usuário
     * ("mantemos o contorno atual e tracejamos internamente onde vai a
     * borda"). Medidas explícitas em mm (nunca `left`+`right`/`top`+
     * `bottom` simultâneos pra "esticar" — mesma cautela desta revisão
     * com qualquer CSS que o Dompdf real possa não calcular igual ao
     * `wkhtmltopdf`).
     *
     * Revisão 2026-09-15 (rodada 4): usuário reportou o numeral
     * colidindo com o título/código da peça (que começa colado no
     * canto superior, ver `padding` de `.caixa-peca` — mesmo problema
     * pra "topo" e "base", já que o texto da peça ocupa o alto da
     * caixa inteira) — pedido: numeral fica ENTRE o tracejado e o
     * contorno sólido. Reposicionado pra ficar nessa faixa (antes do
     * `$inset`, não depois — a versão anterior colocava o numeral pra
     * DENTRO da linha tracejada, na mesma região onde o texto da peça
     * já está) E deslocado pro canto oposto ao do texto (direita, ver
     * `$folga`) pra reduzir ainda mais a chance de sobreposição.
     */
    private function linhaFitaHtml(string $lado, string $numeral, float $larguraBoxMm, float $alturaBoxMm): string
    {
        $inset = 1.0;
        $folga = 0.2;

        return match ($lado) {
            'topo' => '<div class="fita-lado-h" style="top:'.$inset.'mm; left:0mm; width:'.$larguraBoxMm.'mm;"></div>'
                .'<div class="fita-numeral" style="top:'.$folga.'mm; left:'.max(0.0, $larguraBoxMm - 3.2).'mm;">'.$numeral.'</div>',
            'base' => '<div class="fita-lado-h" style="top:'.max(0.0, $alturaBoxMm - $inset).'mm; left:0mm; width:'.$larguraBoxMm.'mm;"></div>'
                .'<div class="fita-numeral" style="top:'.max(0.0, $alturaBoxMm - 1.6).'mm; left:'.max(0.0, $larguraBoxMm - 3.2).'mm;">'.$numeral.'</div>',
            'esquerda' => '<div class="fita-lado-v" style="left:'.$inset.'mm; top:0mm; height:'.$alturaBoxMm.'mm;"></div>'
                .'<div class="fita-numeral" style="left:'.$folga.'mm; top:'.max(0.0, $alturaBoxMm - 2.0).'mm;">'.$numeral.'</div>',
            'direita' => '<div class="fita-lado-v" style="left:'.max(0.0, $larguraBoxMm - $inset).'mm; top:0mm; height:'.$alturaBoxMm.'mm;"></div>'
                .'<div class="fita-numeral" style="left:'.max(0.0, $larguraBoxMm - 3.2).'mm; top:'.max(0.0, $alturaBoxMm - 2.0).'mm;">'.$numeral.'</div>',
            default => '',
        };
    }

    /**
     * Numeral romano (I, II, III, IV...) — usado pra identificar cada
     * especificação ÚNICA de fita de borda (espessura+cor) numa chapa,
     * ver docblock de `bordasPorLado()` e `montarHtmlPreviewCorte()`.
     * Algoritmo padrão de conversão, sem limite prático (nenhuma chapa
     * real deve chegar perto de 4+ especificações distintas de fita).
     */
    private function numeralRomano(int $numero): string
    {
        $valores = [1000 => 'M', 900 => 'CM', 500 => 'D', 400 => 'CD', 100 => 'C', 90 => 'XC', 50 => 'L', 40 => 'XL', 10 => 'X', 9 => 'IX', 5 => 'V', 4 => 'IV', 1 => 'I'];
        $resultado = '';

        foreach ($valores as $valor => $simbolo) {
            while ($numero >= $valor) {
                $resultado .= $simbolo;
                $numero -= $valor;
            }
        }

        return $resultado;
    }

    private function estilosBase(): string
    {
        return '
            @page { margin: 8mm; }
            body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111827; }
            h1 { font-size: 16px; margin: 0 0 2px; }
            h2 { font-size: 13px; margin: 14px 0 4px; color: #374151; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
            th, td { padding: 3px 6px; text-align: left; font-size: 9px; border-bottom: 1px solid #e5e7eb; }
            th { background: #f3f4f6; font-weight: 600; }
            .num { text-align: right; }
            .cabecalho { margin-bottom: 8px; }
            .cabecalho .linha { display: block; }
            .nova-pagina { page-break-before: always; }
            .caixa-peca { position: absolute; border: 1px solid #111827; box-sizing: border-box; overflow: hidden; font-size: 7px; padding: 1px 2px; background: #fafafa; text-align: center; }
            .fita-lado-h { position: absolute; height: 0; border-top: 1px dashed #374151; }
            .fita-lado-v { position: absolute; width: 0; border-left: 1px dashed #374151; }
            .fita-numeral { position: absolute; font-size: 5px; line-height: 1; font-weight: 700; color: #374151; }
            .chapa-container { position: relative; border: 2px solid #111827; margin-top: 2px; background: #9ca3af; }
            .pagina-chapa { page-break-before: always; page-break-inside: avoid; }
            .chapa-info { margin-bottom: 3px; }
            .chapa-info .linha { display: block; font-size: 8px; line-height: 1.3; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
            .legenda-col { float: left; width: 51mm; margin-right: 4mm; font-size: 7px; }
            .legenda-titulo { font-weight: 700; font-size: 8px; margin-bottom: 2px; text-transform: uppercase; color: #374151; }
            .legenda-item { margin-bottom: 2px; line-height: 1.35; }
            .legenda-swatch { display: inline-block; width: 7px; height: 7px; border: 1px solid; margin-right: 3px; vertical-align: middle; }
            .legenda-amostra-corte1 { display: inline-block; width: 10px; height: 0; border-top: 0.6mm dashed '.self::COR_PRIMEIRO_CORTE.'; margin-right: 3px; vertical-align: middle; }
            .legenda-nota { margin-top: 4px; padding-top: 3px; border-top: 1px solid #d1d5db; color: #4b5563; }
            .chapa-col { margin-left: 55mm; }
            .linha-primeiro-corte { position: absolute; z-index: 10; }
            .medida-primeiro-corte { position: absolute; z-index: 11; font-size: 6px; font-weight: 700; color: #fff; background: '.self::COR_PRIMEIRO_CORTE.'; padding: 0.5mm 1mm; border-radius: 0.6mm; white-space: nowrap; }
            .limpa-float { clear: both; }
            .titulo-topo { width: 100%; border-collapse: collapse; margin: 0; background: '.$this->corPrimaria.'; }
            .titulo-topo td { border: none; padding: 2.5mm 3mm; }
            .titulo-topo-texto { width: 62%; font-size: 13px; font-weight: 700; color: #111827; }
            .titulo-topo-logo { text-align: left; font-size: 12px; font-weight: 700; letter-spacing: 1.5px; color: #111827; }
            .titulo-topo-logo-img { vertical-align: middle; max-width: 55mm; }
            .titulo-dados { width: 100%; border-collapse: collapse; background: '.$this->corCinza.'; margin-bottom: 4mm; }
            .titulo-dados td { border: 1px solid #6b7280; padding: 1.5px 4px; color: #f9fafb; font-size: 8px; }
            .titulo-dados-obra { width: 62%; vertical-align: top; }
            .titulo-dados-label { font-weight: 700; text-transform: uppercase; font-size: 7px; color: #d1d5db; }
        ';
    }

    /**
     * @param  array{grupos: array<int, array<string, mixed>>, nao_classificados: array<int, array<string, mixed>>}  $nesting
     * @param  array<int, array{espessura: float, cor: string, metros: float}>  $fitas
     * @param  array<int, array{descricao: string, referencia: string, largura: float, altura: float, quantidade: float, custo: float, preco: float}>  $acessorios
     */
    private function montarHtmlMateriais(Projeto $projeto, array $nesting, array $fitas, array $acessorios): string
    {
        $moeda = fn (float $v): string => 'R$ '.number_format($v, 2, ',', '.');

        $linhasChapas = '';

        foreach ($nesting['grupos'] as $grupo) {
            $qtd = count($grupo['chapas']);
            $custoGrupo = 0.0;

            $descricao = trim(($grupo['fornecedor'] ? $grupo['fornecedor'].'.' : '').$grupo['material'].' - '.$grupo['cor'].' - '.$grupo['espessura'].'mm');

            $cortesGrupo = array_sum(array_column($grupo['chapas'], 'quantidade_cortes'));

            $linhasChapas .= '<tr>'
                .'<td>'.e($descricao).'</td>'
                .'<td class="num">'.number_format($grupo['chapa_largura'], 0, ',', '.').'</td>'
                .'<td class="num">'.number_format($grupo['chapa_comprimento'], 0, ',', '.').'</td>'
                .'<td>'.e((string) $grupo['fornecedor']).'</td>'
                .'<td class="num">'.$qtd.'</td>'
                .'<td class="num">'.$cortesGrupo.'</td>'
                .'</tr>';
        }

        $linhasBordas = '';

        foreach ($fitas as $fita) {
            $linhasBordas .= '<tr>'
                .'<td>Fita '.number_format($fita['espessura'], 1, ',', '.').'mm — '.e($fita['cor']).'</td>'
                .'<td class="num">'.number_format($fita['metros'], 2, ',', '.').' m</td>'
                .'</tr>';
        }

        $linhasAcessorios = '';

        foreach ($acessorios as $item) {
            $linhasAcessorios .= '<tr>'
                .'<td>'.e($item['descricao']).'</td>'
                .'<td class="num">'.number_format($item['quantidade'], 2, ',', '.').'</td>'
                .'</tr>';
        }

        $avisos = '';

        if ($nesting['nao_classificados'] !== []) {
            $itens = collect($nesting['nao_classificados'])
                ->map(fn (array $nc) => '<li>'.e($nc['descricao']).' — '.e($nc['motivo']).'</li>')
                ->implode('');

            $avisos = '<h2 style="color:#b91c1c;">Peças fora do Plano de Corte ('.count($nesting['nao_classificados']).')</h2>'
                .'<ul style="font-size:9px;color:#b91c1c;">'.$itens.'</ul>';
        }

        return '<style>'.$this->estilosBase().'</style>'
            .$this->montarCabecalhoTopo('Materiais', $projeto)
            .'<div class="cabecalho">'
            .'<span class="linha">Equipamento: '.($this->tipoEquipamento === 'cnc' ? 'CNC (fresa '.number_format($this->espessuraFerramenta, 1, ',', '.').'mm)' : 'Serra (espessura '.number_format($this->espessuraFerramenta, 1, ',', '.').'mm)').' — Limpeza de bordas: '.number_format($this->limpezaBordas, 1, ',', '.').'mm — Gerado em '.now()->format('d/m/Y H:i').'</span>'
            .'</div>'
            .'<h2>Chapas</h2>'
            .'<table><thead><tr><th>Descrição</th><th class="num">Largura</th><th class="num">Comprimento</th><th>Fornecedor</th><th class="num">Qtd. chapas</th><th class="num">Cortes</th></tr></thead><tbody>'.$linhasChapas.'</tbody></table>'
            .'<h2>Bordas</h2>'
            .'<table><thead><tr><th>Descrição</th><th class="num">Qtd.</th></tr></thead><tbody>'.$linhasBordas.'</tbody></table>'
            .'<h2>Acessórios</h2>'
            .'<table><thead><tr><th>Descrição</th><th class="num">Qtd.</th></tr></thead><tbody>'.$linhasAcessorios.'</tbody></table>'
            .$avisos;
    }

    /**
     * @param  array{grupos: array<int, array<string, mixed>>, nao_classificados: array<int, array<string, mixed>>}  $nesting
     * @param  array<string, array{letra: string, cor_fundo: string, cor_borda: string, total: int, descricao: string, indice: int, tamanho: string}>  $gruposPorPeca  indexado pela chave de `chaveGrupoPeca()` — ver `montarGruposPorPeca()`
     * @param  array<int, array<string, mixed>>  $paraServicos  mesmo array passado pro nesting — usado aqui só pro lookup de `largura`/`profundidade`/`fita_borda_1..4` por `componente_id` (ver `bordasPorLado()`)
     */
    private function montarHtmlPreviewCorte(Projeto $projeto, array $nesting, array $gruposPorPeca, array $paraServicos): string
    {
        $componentesPorId = array_column($paraServicos, null, 'id');

        // Numeração "instância/totalLetra" (ex. "2/3A") — GLOBAL por
        // chave de grupo (`chaveGrupoPeca()`), não mais o `instancia`
        // que cada motor de nesting devolve (esse é por
        // `componente_id`, que não corresponde mais 1:1 à chave desde
        // a Revisão 2026-09-15 rodada 3, item 2) — declarado FORA do
        // loop de chapas/grupos de baixo pra acumular no projeto
        // inteiro.
        $contadorInstanciaPorChave = [];
        // Área útil de desenho em mm — ver docblock da classe, "Revisão
        // 2026-09-14 (rodada 6)": A4 paisagem com `@page { margin: 8mm; }`
        // EXPLÍCITO dá 277×194mm úteis. Revisão 2026-09-14 (cabeçalho
        // padrão + legenda): a reserva de altura pro cabeçalho cresceu
        // (bloco de marca de 2 faixas + linha de dados da chapa somam
        // ~34mm, contra ~20mm do cabeçalho compacto antigo) e a largura
        // de desenho encolheu (~55mm a menos, reservados pra coluna da
        // legenda à esquerda, `.legenda-col`) — ver
        // `montarCabecalhoTopo()`.
        $larguraDisponivelMm = 214.0;
        $alturaDisponivelMm = 158.0;

        $html = '';

        foreach ($nesting['grupos'] as $grupo) {
            foreach ($grupo['chapas'] as $chapa) {
                $escala = min($larguraDisponivelMm / $grupo['chapa_largura'], $alturaDisponivelMm / $grupo['chapa_comprimento']);

                $descricao = trim(($grupo['fornecedor'] ? $grupo['fornecedor'].'.' : '').$grupo['material'].' - '.$grupo['cor'].' - '.$grupo['espessura'].'mm');

                // Espessura do corte (kerf), SÓ pra desenho: cada caixa
                // de peça é encolhida meio-kerf pra dentro em cada
                // lado, então o vão entre DUAS peças vizinhas
                // quaisquer (mesma prateleira ou não, Serra ou CNC)
                // reproduz o kerf INTEIRO — sem isso o kerf real (já
                // reservado no encaixe, ver `PlanoCorteNestingService`)
                // ficava invisível no desenho porque as caixas eram
                // desenhadas encostadas. Os números impressos na caixa
                // continuam sendo `largura_corte`/`comprimento_corte`
                // (a peça real, sem encolher) — só o desenho encolhe.
                //
                // Revisão 2026-09-14 — piso mínimo de vão (usuário
                // reportou que os espaços entre peças não apareciam
                // claramente no PDF). Numa chapa inteira desenhada em
                // escala de página A4 (escala ~0,09), poucos mm de
                // kerf viram frações de mm no papel — menor que a
                // própria borda de 1px das caixas — e o vão desaparecia
                // visualmente, mesmo estando matematicamente correto.
                // Piso de 0,8mm de folga por lado (1,6mm de vão total)
                // — deixa de ser fiel à escala exata (o cabeçalho já
                // avisa que é "visual"), mas garante espaço legível
                // pras setas de corte (`montarSetasCorte()`) também.
                $folgaKerfMm = max(($this->espessuraFerramenta / 2) * $escala, 0.8);

                $caixas = '';
                $qtdPorChaveNestaChapa = [];
                // Especificações ÚNICAS de fita de borda (espessura +
                // cor) que aparecem NESTA chapa, na ordem de primeira
                // aparição — cada uma ganha um numeral romano (ver
                // `numeralRomano()`), reiniciado por chapa (diferente
                // da letra do grupo de peça, que é global no projeto)
                // porque a tabela da legenda da fita é impressa uma
                // vez por chapa — ver docblock da classe, "Revisão
                // 2026-09-15 (rodada 4)".
                $especificacoesFitaPorChapa = [];

                foreach ($chapa['pecas'] as $peca) {
                    $chave = $this->chaveGrupoPeca($peca);
                    $qtdPorChaveNestaChapa[$chave] = ($qtdPorChaveNestaChapa[$chave] ?? 0) + 1;
                    $contadorInstanciaPorChave[$chave] = ($contadorInstanciaPorChave[$chave] ?? 0) + 1;

                    $left = ($peca['x'] * $escala) + $folgaKerfMm;
                    $top = ($peca['y'] * $escala) + $folgaKerfMm;
                    $w = max(0.0, ($peca['largura_corte'] * $escala) - (2 * $folgaKerfMm));
                    $h = max(0.0, ($peca['comprimento_corte'] * $escala) - (2 * $folgaKerfMm));

                    $g = $gruposPorPeca[$chave] ?? null;
                    // Código "instância/total.Letra" (ex. "2/3.A") — ver
                    // docblock da classe, "grupos de peça". O PONTO
                    // antes da letra foi pedido pelo usuário (Revisão
                    // 2026-09-15, rodada 4): sem ele, a letra "O" cola
                    // no número e "1/2O" pode ser lido como "1/20" (O
                    // confundido com zero) — o ponto deixa claro que a
                    // letra é um sufixo separado, não faz parte do
                    // número. Fallback "#instância" no caso (não
                    // esperado) de a peça não ter entrado no mapa de
                    // grupos.
                    $codigo = $g !== null ? ($contadorInstanciaPorChave[$chave].'/'.$g['total'].'.'.$g['letra']) : ('#'.$peca['instancia']);
                    $estiloCor = $g !== null ? ' background:'.$g['cor_fundo'].'; border-color:'.$g['cor_borda'].';' : '';

                    // Lados com fita de borda (tracejado INTERNO, não
                    // mexe no contorno sólido da caixa) — ver docblock
                    // da classe e de `bordasPorLado()`. Rotação passou
                    // a ser indicada SÓ pelo ícone ⟲ (sem tracejar a
                    // borda inteira).
                    $htmlFita = '';
                    $componente = $componentesPorId[$peca['componente_id']] ?? null;

                    if ($componente !== null) {
                        foreach ($this->bordasPorLado($peca, $componente) as $lado => $info) {
                            if (! $info['ativo']) {
                                continue;
                            }

                            // Numeral por ESPECIFICAÇÃO (espessura+cor),
                            // não por lado/slot — ver docblock de
                            // `bordasPorLado()`. Primeira peça desta
                            // chapa com essa espessura+cor registra o
                            // próximo numeral; as seguintes reusam.
                            $chaveFita = number_format((float) $info['espessura'], 2, '.', '').'|'.($info['cor'] ?? '—');

                            if (! isset($especificacoesFitaPorChapa[$chaveFita])) {
                                $especificacoesFitaPorChapa[$chaveFita] = [
                                    'numeral'   => $this->numeralRomano(count($especificacoesFitaPorChapa) + 1),
                                    'espessura' => (float) $info['espessura'],
                                    'cor'       => $info['cor'] ?? '—',
                                ];
                            }

                            $htmlFita .= $this->linhaFitaHtml($lado, $especificacoesFitaPorChapa[$chaveFita]['numeral'], $w, $h);
                        }
                    }

                    // Nome/descrição da peça direto na caixa (não só
                    // na legenda por letra) — pedido do usuário, ver
                    // docblock da classe, "Revisão 2026-09-15 (rodada
                    // 4)". `overflow: hidden` em `.caixa-peca` já
                    // recorta com segurança em peças muito pequenas,
                    // mesmo comportamento que os números de dimensão
                    // já tinham.
                    $caixas .= '<div class="caixa-peca" style="left:'.$left.'mm; top:'.$top.'mm; width:'.$w.'mm; height:'.$h.'mm;'.$estiloCor.'">'
                        .$codigo.($peca['rotacionado'] ? ' &#8635;' : '').'<br>'.e($peca['descricao']).'<br>'.round($peca['largura_corte']).'&times;'.round($peca['comprimento_corte']).$htmlFita.'</div>';
                }

                // Linha do 1º corte — ver docblock da classe, item 7
                // da "Revisão 2026-09-15 (rodada 4)", e `primeiroCorte()`.
                // Usa as coordenadas REAIS (não as já encolhidas pelo
                // kerf visual acima).
                $corte1 = $this->primeiroCorte($chapa['pecas'], $grupo['chapa_largura'], $grupo['chapa_comprimento']);
                $htmlPrimeiroCorte = $corte1 !== null ? $this->primeiroCorteHtml($corte1, $escala) : '';

                // Legenda: só os grupos que aparecem NESTA chapa,
                // ordenados pela ordem de atribuição da letra (não
                // alfabética — evita "AA" aparecer antes de "B").
                $itensLegenda = [];

                foreach ($qtdPorChaveNestaChapa as $chave => $qtdNestaChapa) {
                    $g = $gruposPorPeca[$chave] ?? null;

                    if ($g === null) {
                        continue;
                    }

                    $itensLegenda[] = [
                        'indice' => $g['indice'],
                        // Tamanho na legenda — pedido do usuário, ver
                        // docblock da classe, "Revisão 2026-09-15
                        // (rodada 4)", item 5. `$g['tamanho']` já vem
                        // formatado (maior×menor) de `montarGruposPorPeca()`.
                        'html'   => '<div class="legenda-item"><span class="legenda-swatch" style="background:'.$g['cor_fundo'].'; border-color:'.$g['cor_borda'].';"></span><strong>'.e($g['letra']).'</strong> — '.e($g['descricao']).' — '.$g['tamanho'].' — '.$qtdNestaChapa.'/'.$g['total'].'</div>',
                    ];
                }

                usort($itensLegenda, fn (array $a, array $b) => $a['indice'] <=> $b['indice']);
                $htmlLegenda = implode('', array_column($itensLegenda, 'html'));

                // Notas fixas da legenda (não dependem da chapa) — ver
                // docblock da classe, itens 6-7 da "Revisão 2026-09-15
                // (rodada 4)". A amostra na legenda é a PRÓPRIA linha
                // tracejada vermelha (`.legenda-amostra-corte1`), não
                // mais um símbolo por orientação — a linha em si já
                // deixa claro se é horizontal ou vertical no desenho.
                // Rotação = só o ícone ⟲ (sem tracejado); tracejado
                // interno + numeral romano = lado com fita de borda.

                // Tabela de especificação da fita — pedido do usuário,
                // ver docblock da classe, "Revisão 2026-09-15 (rodada
                // 4)": "vai descrever a cor, modelo e espessura da
                // borda. ex: I = Fita branco Tx 0,4 mm". Uma linha por
                // numeral romano REALMENTE usado nesta chapa (registro
                // `$especificacoesFitaPorChapa`, já na ordem de
                // atribuição) — nada é mostrado se a chapa não tiver
                // nenhum lado com fita ativa.
                $htmlLegendaFita = '';

                foreach ($especificacoesFitaPorChapa as $infoFita) {
                    $corFormatada = $infoFita['cor'] !== '—' ? ucwords(mb_strtolower($infoFita['cor'])) : '—';
                    $espessuraFormatada = str_replace('.', ',', number_format($infoFita['espessura'], 1));

                    $htmlLegendaFita .= '<div class="legenda-item">'.e($infoFita['numeral']).' = Fita '.e($corFormatada).' Tx '.$espessuraFormatada.'mm</div>';
                }

                $htmlLegenda .= '<div class="legenda-nota">'
                    .($corte1 !== null ? '<div class="legenda-item"><span class="legenda-amostra-corte1"></span> 1º Corte — medida = distância da borda da chapa até a linha</div>' : '')
                    .'<div class="legenda-item">&#8635; peça rotacionada</div>'
                    .($htmlLegendaFita !== '' ? '<div class="legenda-item">tracejado interno = lado com fita de borda:</div>'.$htmlLegendaFita : '')
                    .'</div>';

                // Cabeçalho + desenho da chapa: ver docblock da classe,
                // "Revisão 2026-09-14 (rodada 6)" — os dois ficam
                // DENTRO do mesmo `<div class="pagina-chapa">`
                // (page-break-before + page-break-inside:avoid). O
                // cabeçalho de marca (`montarCabecalhoTopo()`) usa 2
                // `<table>` pequenas e FIXAS (não crescem, não carregam
                // `page-break-before` nelas mesmas) — diferente da
                // tabela de dados que causou páginas em branco na
                // rodada 5 (que crescia linha a linha); ainda assim,
                // não confirmado com o Dompdf real (ver docblock da
                // classe).
                $html .= '<style>'.$this->estilosBase().'</style>'
                    .'<div class="pagina-chapa">'
                    .$this->montarCabecalhoTopo('Corte', $projeto)
                    .'<div class="chapa-info">'
                    .'<span class="linha">Chapa '.$chapa['numero'].' — '.e($descricao).' — '.number_format($grupo['chapa_largura'], 0).'×'.number_format($grupo['chapa_comprimento'], 0).'mm</span>'
                    .'<span class="linha">Peças: '.count($chapa['pecas']).' — Cortes: '.$chapa['quantidade_cortes'].' — Aproveitamento: '.$chapa['aproveitamento'].'% — Espessura de corte: '.number_format($this->espessuraFerramenta, 1, ',', '.').'mm (vão ao redor da peça = kerf, visual)</span>'
                    .'</div>'
                    .'<div class="legenda-col"><div class="legenda-titulo">Legenda</div>'.$htmlLegenda.'</div>'
                    .'<div class="chapa-col"><div class="chapa-container" style="width:'.($grupo['chapa_largura'] * $escala).'mm; height:'.($grupo['chapa_comprimento'] * $escala).'mm;">'.$htmlPrimeiroCorte.$caixas.'</div></div>'
                    .'<div class="limpa-float"></div>'
                    .'</div>';
            }
        }

        return $html;
    }

    /**
     * @param  array{grupos: array<int, array<string, mixed>>, nao_classificados: array<int, array<string, mixed>>}  $nesting
     */
    private function montarHtmlEtiquetas(Projeto $projeto, array $nesting): string
    {
        // Grade em A4 paisagem — 4 colunas de ~65mm, altura ~32mm por
        // etiqueta, réplica simplificada (sem código de barras, ver
        // docblock da classe) da etiqueta do Promob Cut.
        $colunas = 4;

        $celulas = [];

        foreach ($nesting['grupos'] as $grupo) {
            $descricaoChapa = trim(($grupo['fornecedor'] ? $grupo['fornecedor'].'.' : '').$grupo['material'].' - '.$grupo['cor'].' - '.$grupo['espessura'].'mm');

            foreach ($grupo['chapas'] as $chapa) {
                foreach ($chapa['pecas'] as $indice => $peca) {
                    $codigo = 'PC'.str_pad((string) $peca['componente_id'], 6, '0', STR_PAD_LEFT).'-'.$peca['instancia'];

                    $celulas[] = '<td style="width:'.(100 / $colunas).'%; border: 1px solid #111827; vertical-align: top; padding: 4px;">'
                        .'<div style="font-size:8px;">Projeto: '.e((string) ($projeto->numero_projeto ?? $projeto->id)).' &nbsp; Chapa '.$chapa['numero'].'</div>'
                        .'<div style="font-size:8px;">'.e($descricaoChapa).'</div>'
                        .'<div style="font-size:10px; font-weight:600; margin-top:2px;">'.e($peca['descricao']).'</div>'
                        .'<div style="font-size:9px;">Dimensão: '.round($peca['largura_corte']).' × '.round($peca['comprimento_corte']).'mm'.($peca['rotacionado'] ? ' (rotacionada)' : '').'</div>'
                        .'<div style="font-size:8px; margin-top:2px;">Código: '.$codigo.'</div>'
                        .'</td>';
                }
            }
        }

        $linhas = '';

        foreach (array_chunk($celulas, $colunas) as $linhaCelulas) {
            $linhas .= '<tr>'.implode('', $linhaCelulas).'</tr>';
        }

        return '<style>'.$this->estilosBase().'</style>'
            .'<div class="nova-pagina">'
            .$this->montarCabecalhoTopo('Etiquetas', $projeto)
            .'<table style="table-layout: fixed;"><tbody>'.$linhas.'</tbody></table>'
            .'</div>';
    }
}
