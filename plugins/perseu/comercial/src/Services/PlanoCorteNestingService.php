<?php

namespace Perseu\Comercial\Services;

/**
 * Motor de encaixe (nesting) do Plano de Corte — algoritmo de
 * guilhotina (cada corte é uma linha reta de ponta a ponta do
 * sub-retângulo em que está cortando — exigência real de serra
 * circular/CNC de painel, não só escolha de algoritmo; ver CLAUDE.md,
 * "Estudo do XML do Promob — estrutura completa pra Plano de Corte").
 * Confirmado por fora (2026-09-13, pesquisa OpenCutList — projeto
 * open-source de lista de corte, ver CLAUDE.md): "todos os cortes
 * devem atravessar o painel ou o retalho sem parar no meio ou fazer
 * curvas" — a mesma regra, não é invenção nossa.
 *
 * NÃO copia o código-fonte proprietário do Promob Cut (nem os motores
 * de terceiro que ele pode delegar — Corte Certo, Cut
 * Planning/OptiPlanning — inacessíveis, e alguns usam heurísticas de
 * busca local/algoritmos genéticos que também não seriam praticáveis
 * de reproduzir 1:1 aqui). MAS desde a rodada 7 (2026-09-14) o
 * objetivo VOLTOU a ser chegar o mais perto possível do aproveitamento
 * real dele (decisão revista com o usuário — antes a meta era só
 * "física correta, sem perseguir %") — usando técnicas de
 * empacotamento 2D ESTABELECIDAS e públicas (não segredo de ninguém:
 * a mesma família de heurísticas de bibliotecas de bin-packing de
 * prateleira, tipo `dagmike/BinPacking`/`juj/RectangleBinPack` —
 * avaliamos usar uma dessas prontas e decidimos não usar, ver
 * "Revisão rodada 7" abaixo, porque nenhuma resolve kerf/veio
 * travado/agrupamento/guilhotina, que são o cerne do problema aqui).
 *
 * Classe SEM Eloquent/DB de propósito — recebe e devolve só arrays
 * puros, pra dar pra testar com um script PHP simples, sem precisar
 * subir o Laravel inteiro (ver script de verificação usado nesta
 * tarefa).
 *
 * ## Revisão 2026-09-14 (rodada 10) — faixa 1D como alternativa da grade
 * em CADA fatia do "recorte"
 *
 * Usuário reparou (rastreando o PDF da rodada 9 peça por peça) que uma
 * peça de um grupo de 8 idênticas ("Lateral de Gaveta 15mm") tinha ficado
 * sozinha, separada das outras 7 do mesmo grupo, e sugeriu explicitamente
 * o caminho: "ir agrupando das maiores para as menores" mantendo cada
 * grupo de peças iguais junto. Rastreando de novo (log de cada decisão
 * de `encaixarBlocoSequencial()`), achamos a causa: o "recorte" (rodada
 * 8/9) só tenta GRADE (rows×cols) pra cada fatia de tamanho K — quando a
 * grade de 8 não cabia mas a de 7 cabia, sobrava 1 peça isolada, mesmo
 * quando uma FAIXA 1D reta das 8 (mais comprida e mais fina que a grade)
 * cabia perfeitamente no mesmo lugar.
 *
 * Correção: pra CADA tamanho K testado no recorte, se o bloco original é
 * do tipo 'grade' (peças de tamanho EXATO — toda faixa 1D também é
 * válida pra elas, é só olhar pro mesmo tamanho de um jeito diferente),
 * tenta GRADE primeiro e, só se ela não couber em nenhuma sobra já
 * aberta, tenta FAIXA 1D antes de cair pro K-1. Aplicado nos dois
 * caminhos de recorte (`encaixarBloco()` e `encaixarBlocoSequencial()`).
 *
 * MEDIDO: as 8 peças "Lateral de Gaveta 15mm" que ficavam 7+1 separadas
 * agora formam 1 faixa reta de 8 peças só (confirmado visualmente,
 * `wkhtmltopdf`) — sem abrir mão do aproveitamento (Branco/15mm continua
 * 92,7%/8%, igual à rodada 9) e com 1 corte a menos no total (menos
 * fragmentação = menos seccionamento). Nenhum outro grupo mudou.
 *
 * O usuário também notou outros 2 grupos ainda fragmentados em pares
 * (Contra Frente/Posterior de Gaveta 15mm, 8 peças) e em peças soltas
 * (Sarrafo 15mm) — rastreado e confirmado: nesses casos NEM a grade NEM
 * a faixa 1D de mais de 2 peças cabem em nenhuma sobra já aberta naquele
 * ponto da sequência (já foi ocupada pelos blocos maiores processados
 * antes) — não é mais um problema de "só testar 1 forma", é a sobra
 * disponível mesmo sendo pequena/irregular ali. Resolver isso de verdade
 * precisaria de uma reordenação/otimização bem mais ampla (testar
 * ordens de processamento diferentes, ou um solver de bin-packing 2D de
 * verdade) — fica como pendência conhecida, não resolvida nesta rodada.
 *
 * ## Revisão 2026-09-14 (rodada 9) — esgota a chapa mais antiga ANTES
 * de espalhar pra outra já aberta
 *
 * Usuário reparou (olhando o resultado da rodada 8, 83,9%/16,8%) que
 * ainda dava pra ver peças da chapa 2 que cabiam na chapa 1, e sugeriu o
 * caminho certo: "começar pelas peças maiores agrupando as iguais (...)
 * depois analisando os espaços e agrupando as peças menores e encaixando
 * no aproveitamento das tiras". Conferimos com um rasterizador (grid de
 * 5mm sobre as peças já colocadas, script `diag_espaco.php`) que a chapa
 * 1 realmente tinha ~15% de área livre de verdade, e que os retângulos
 * livres que a bookkeeping de corte guardava batiam com essa área real
 * (não era bug de fragmentação/perda de retângulo) — o problema era
 * outro: `encaixarBloco()` faz busca de melhor-encaixe GLOBAL (a menor
 * sobra entre TODAS as chapas já abertas), então quando a chapa 2 já
 * tinha sido aberta pra colocar UMA peça grande que não cabia na chapa 1
 * de jeito nenhum (a "Prateleira Linear", 513×770mm — confirmado: não
 * cabe em nenhum retângulo livre real da chapa 1), toda peça MENOR
 * processada depois achava um encaixe fácil na chapa 2 (que estava quase
 * vazia) e nunca chegava a tentar o "recorte" (rodada 8) contra as
 * sobras menores que ainda existiam na chapa 1.
 *
 * `encaixarBlocoSequencial()` é uma segunda forma de encaixar um bloco:
 * em vez de buscar globalmente, ESGOTA cada chapa já aberta, NA ORDEM
 * (a mais antiga primeiro) — testa o bloco inteiro nela, se não couber
 * tenta recortar (N-1, N-2, ... peças, igual à rodada 8) SÓ nessa chapa,
 * e só passa pra próxima chapa quando nem 1 peça do bloco coube na
 * atual. `encaixarGrupo()` testa as duas formas (`'global'` e
 * `'sequencial'`) como mais uma dimensão da comparação de estratégias
 * (agora 8 combinações: grade/faixa × área/lado curto × global/
 * sequencial) e fica com a melhor. Precisou de um desempate NOVO no
 * score (`-maior aproveitamento`, além de `-soma`): a SOMA do
 * aproveitamento não muda só por redistribuir peças entre chapas já
 * abertas (mesma área ocupada no total), então sem esse desempate as
 * duas formas empatavam sempre, mesmo quando uma delas consolidava
 * muito melhor (o que o usuário pediu) e a outra espalhava.
 *
 * MEDIDO (fixtures corrigidas, kerf 4mm — o padrão real do modo Serra,
 * `espessura_serra` default no form de Produção): Branco/15mm foi de
 * 83,9%/16,8% (rodada 8) pra **92,7%/8%** — a chapa 1 ficou com TODAS as
 * 34 peças que cabem nela de algum jeito (inclusive as que a rodada 8
 * ainda mandava pra chapa 2), e a chapa 2 ficou só com a 1 peça que
 * geometricamente não cabe em lugar nenhum da chapa 1 (nem inteira, nem
 * fatiada) — exatamente o padrão "1ª chapa 90%+, 2ª só com o resto" que
 * o usuário descreveu vendo o PDF real do Promob. Nenhum outro grupo
 * regrediu (as fixtures que já tinham 1 chapa só continuam com 1 só,
 * `verificar6.php` confirma geometria OK — sem sobreposição, sem peça
 * fora da área útil). Total de chapas nas fixtures: continua 6 (a
 * consolidação não muda quantas chapas abrem, só ONDE cada peça cai).
 *
 * ## Revisão 2026-09-14 (rodada 8) — "recorte" de bloco: usa sobra já
 * aberta ANTES de abrir chapa nova
 *
 * O usuário pediu explicitamente pra olhar pras sobras: "temos de olhar
 * um pouco para sobra tentar não fracionar (...) podíamos encaixar as
 * peças menores e a sobra ficar melhor (...) agrupar peças menores que
 * possam encaixar nos vãos". Investigando a chapa Branco/15mm (a mesma
 * usada de termômetro desde a rodada 7) achamos a causa EXATA: um bloco
 * de peças idênticas coladas (grade ou faixa 1D) só é testado como
 * INTEIRO contra as sobras já abertas (`encaixarBloco()`) — se ele não
 * cabe INTEIRO em nenhuma sobra, vai pra chapa nova, mesmo quando várias
 * sobras menores, já abertas, teriam espaço de sobra pra ALGUMAS das
 * peças do bloco (só não pra todas de uma vez). Confirmado com um script
 * de rastreamento (log de cada decisão de encaixe): um bloco "lane
 * 350×1412" (4 peças "Lateral 15" coladas) abriu chapa nova mesmo a
 * chapa 1 tendo várias sobras de 400-1000mm de altura sobrando — só
 * nenhuma delas com 1412mm inteiros.
 *
 * Correção: quando um bloco inteiro não cabe em NENHUMA sobra já
 * aberta, `encaixarBloco()` agora tenta um "recorte" dele ANTES de abrir
 * chapa nova — testa as N-1 primeiras peças do bloco, depois N-2, ... até
 * 1, reconstruindo a geometria (`reconstruirBloco()`, reaproveita a
 * mesma busca de grade de `montarBlocosGrade()` via `buscarMelhorGrade()`
 * pra tipo 'grade', ou reempilha na ordem dada pra tipo 'lane') — e fica
 * com a MAIOR fatia que couber numa chapa JÁ ABERTA (nunca abre chapa
 * nova durante essa busca). O resto do bloco (se sobrar) volta
 * recursivamente pro mesmo `encaixarBloco()`, que tenta de novo (podendo
 * recortar de novo, ou só aí abrir chapa nova se for mesmo preciso).
 * Fisicamente isso é só MAIS UM corte reto dividindo a faixa/grade
 * original em duas partes menores — continua 100% guilhotina, nenhuma
 * peça sobrepõe, e cada parte ganha sua própria contagem de cortes de
 * isolamento + seccionamento (`colocarBlocoNoRetangulo()`, extraído de
 * `encaixarBloco()` pra ser reaproveitado nos dois caminhos).
 *
 * MEDIDO nas fixtures reais (chapa 2.750×1.830mm, kerf 4mm, corrigido o
 * bug de fixture de teste duplicada — ver nota abaixo): grupo
 * Branco/15mm foi de 65,4%/35,2% (2 chapas) pra 83,9%/16,8% (2 chapas) —
 * a 1ª chapa perto do "90%+ e a 2ª com o restante" que o usuário descreveu
 * vendo o PDF real do Promob. Grupo Grafite/15mm foi de 2 chapas
 * (56,9%/7,4%) pra 1 chapa só (64,3%) — 1 chapa inteira a menos. Total
 * de chapas de todos os grupos: 7 → 6. Nenhum grupo regrediu (script de
 * geometria continua OK — sem sobreposição, sem peça fora da área útil).
 * Ainda não é 90%+ na 1ª chapa (fica em ~84%) — a sobra que resta em
 * cada chapa 1 não é mais um bloco fragmentado grande, mas ainda existe;
 * "cada caso é um caso" (usuário, rodada 7) segue valendo, isso é uma
 * aproximação medida, não uma garantia de bater o Promob em todo cenário.
 *
 * (Nota à parte, não é uma mudança de algoritmo: essa investigação
 * também achou que os testes anteriores desta tarefa — não o código
 * enviado ao usuário, só os scripts de verificação do sandbox — vinham
 * contando o projeto real "260000" DUAS vezes, porque ele tinha 2
 * exports de XML do mesmo projeto (`veio vertical.xml`/`veio
 * horizontal.xml`, de um teste controlado de veio anterior). Corrigido
 * nos scripts de teste; nunca afetou o `PlanoCorteNestingService.php`
 * em produção, que sempre opera sobre os componentes reais do Projeto do
 * usuário, não sobre esses arquivos de fixture.)
 *
 * ## Revisão 2026-09-14 (rodada 7) — grade (rows×cols) pra peças
 * idênticas + 2 critérios de encaixe testados, fica o melhor
 *
 * O usuário topou perseguir o aproveitamento real do Promob (mandou
 * uma imagem de referência nova, a mesma chapa Branco/15mm já usada
 * como termômetro nesta tarefa) e pediu a lógica de "o que encaixa
 * melhor numa faixa horizontal e/ou vertical, evitando sobras
 * irregulares" — isso tem nome na literatura de bin-packing 2D
 * (Jylänki, "A Thousand Ways to Pack the Bin", de acesso público, não
 * segredo do Promob): rodar mais de uma REGRA de escolha de retângulo
 * e ficar com o resultado melhor. Duas mudanças:
 *
 * 1. **Grade pra peças de tamanho EXATO** (`montarBlocosGrade()`) — a
 *    rodada 6 sempre empilhava peças idênticas numa faixa 1D só
 *    (1×N). Isolamos que essa era a ÚNICA causa da perda de 1 chapa
 *    medida na rodada 6 (script de teste com o agrupamento por
 *    tamanho exato desligado deu o mesmo resultado de antes). Uma
 *    faixa 1×N desperdiça muito mais espaço que uma grade bem
 *    escolhida (ex. 13 peças: 1×13 é uma coluna gigante; uma grade
 *    tipo 4×4 com 3 posições vazias pode caber num espaço bem mais
 *    "quadrado" e sobrar menos resto irregular ao redor). Testamos
 *    TODAS as combinações cols×rows que cabem na área útil e ficamos
 *    com a que sobra MENOS posições vazias.
 * 2. **Dois critérios de "melhor encaixe", fica o melhor resultado**
 *    (`$criterio` em `encaixarBloco()`/`calcularSobra()`) — além do
 *    "Best Area Fit" de sempre (menor sobra de ÁREA), passamos a
 *    também rodar com "Best Short Side Fit" (menor sobra do lado MAIS
 *    CURTO — técnica clássica de bin-packing 2D pra reduzir sobras
 *    finas/irregulares, é literalmente o nome de uma das heurísticas
 *    do `RectangleBinPack`/`dagmike/BinPacking`).
 *
 * MEDIDO (não só teoria): rodar SÓ a grade (sem comparar com a faixa
 * 1D) piorou um grupo enquanto melhorava outro — grade não é sempre
 * melhor (ex. contraexemplo pequeno: 1 grupo de exatamente 2 peças
 * quadradas cabe melhor de um jeito específico que a busca de menor
 * desperdício às vezes não escolhe por causa de como a peça interage
 * com o resto da chapa). Por isso `encaixarGrupo()` roda o encaixe
 * completo em 4 COMBINAÇÕES — {grade, faixa 1D} × {Best Area Fit,
 * Best Short Side Fit} — e fica com a que abrir MENOS chapas no total
 * (empate: maior aproveitamento somado). Resultado nas fixtures: de
 * volta a 8 chapas no total (empatando com a rodada 5, que não tinha
 * agrupamento de verdade) — só que agora com o agrupamento por
 * tamanho aplicado onde ele realmente ajuda, e a faixa 1D "de
 * segurança" onde a grade não ajudava.
 *
 * Ainda NÃO é uma cópia do algoritmo do Promob (que provavelmente usa
 * uma busca bem mais sofisticada, possivelmente com múltiplas
 * tentativas/algoritmo genético — ver CLAUDE.md, pesquisa do usuário)
 * — é uma aproximação empírica, testada e ajustada contra os PDFs
 * reais que o usuário for mandando. "Cada caso é um caso" (usuário,
 * 2026-09-14): não há garantia de bater ou superar o Promob em TODO
 * cenário, só de estar mais perto que a rodada 6.
 *
 * ## Revisão 2026-09-14 (rodada 6) — blocos/lanes por tamanho ANTES de
 * encaixar (não mais reativo) + contagem real de cortes
 *
 * A rodada 5 tinha uma heurística REATIVA ("continuação por tamanho"):
 * só tentava colar a peça atual na última peça do MESMO tamanho já
 * encaixada, e só olhando os retângulos que sobraram DAQUELA peça
 * específica. Isso ainda deixava peças espalhadas quando a PRIMEIRA
 * peça de um tamanho não caía num lugar generoso o bastante pras
 * seguintes, ou quando duas peças eram parecidas mas não idênticas
 * (ex. mesma largura, comprimento diferente) — o usuário confirmou
 * isso reexaminando o mesmo caso (peças 14-21) num PDF novo, e pediu
 * explicitamente pra também agrupar quando só UM dos lados bate (se o
 * veio permitir), e pra calcular a quantidade de cortes (que não
 * existia antes — `quantidade_cortes` era só `count(pecas)`, ver nota
 * antiga removida abaixo).
 *
 * Correção: `agruparEmBlocos()` roda ANTES de qualquer encaixe,
 * juntando peças em "blocos" (faixas retas coladas, cada uma cabendo
 * inteira na chapa) em duas passadas gulosas — maior grupo possível
 * primeiro:
 *   1. Tamanho EXATO (largura×comprimento iguais, considerando as
 *      orientações permitidas pelo veio de cada peça).
 *   2. Só a LARGURA em comum, ou só o COMPRIMENTO em comum (entre as
 *      peças que sobraram do passo 1) — vira uma faixa de largura (ou
 *      altura) fixa com o outro lado variando peça a peça; ainda assim
 *      só precisa de 1 corte "de fora a fora" pra isolar a faixa
 *      inteira + (N-1) cortes de seccionamento por dentro dela, em vez
 *      de 2 cortes de isolamento POR PEÇA se elas ficassem espalhadas.
 * Toda peça acaba em exatamente um bloco — mesmo uma sem par nenhum
 * vira um "bloco" de 1 peça só (é a peça avulsa de sempre, mesmo
 * comportamento de antes). Cada bloco entra no MESMO encaixe de
 * melhor-encaixe (Best Area Fit) + `dividirRetangulo()` de sempre,
 * tratado como uma peça só (com a chance extra de girar o bloco
 * inteiro 90°, só quando TODAS as peças dele podem rotacionar) — por
 * isso o encaixe entre blocos continua com a mesma garantia de nunca
 * sobrepor (só o retângulo livre escolhido é dividido, o resto fica
 * intocado).
 *
 * `quantidade_cortes` deixou de ser um proxy (nº de peças) e passou a
 * ser uma contagem real, geométrica, de cortes de guilhotina: 1 ou 2
 * cortes pra isolar cada bloco do resto do retângulo livre (a mesma
 * régua de `dividirRetangulo()` — depende de quantos lados sobraram
 * espaço) + (N-1) cortes de seccionamento reto pra separar as N peças
 * coladas dentro do bloco. Ainda NÃO é a contagem exata do Promob (que
 * numera etapas de corte com um algoritmo proprietário próprio — ver
 * CLAUDE.md, pesquisa sobre 2D Cutting Stock Problem + Binary Trees) —
 * é a contagem real do NOSSO plano de corte, o que já é uma resposta
 * concreta à pergunta "quantos cortes isso precisa".
 *
 * ## Revisão 2026-09-13 (rodada 4) — UM SÓ algoritmo, cortes em AMBAS
 * as direções
 *
 * A rodada 2 tinha criado DOIS algoritmos — um de "prateleiras" só
 * horizontais pra Serra e outro de guilhotina livre pro CNC. O usuário
 * reexaminou o PDF REAL do Promob Cut Pro (mesmo em modo "Serra -
 * Otimizado") e mostrou isso ERRADO: o Aproveitamento do grupo
 * "Cores.Branco - MDF - 15" chega a 95,9% e o padrão de corte MISTURA
 * tiras verticais e horizontais na MESMA chapa. Os dois algoritmos
 * foram FUNDIDOS num só — cada subdivisão (`dividirRetangulo()`)
 * ESCOLHE a direção do corte (a folga que sobra maior vira um
 * retângulo "cheio", a menor vira um retângulo "estreito"), em vez de
 * fixar a direção de antemão. `$tipoEquipamento` continua recebido
 * (usado por `PlanoCorteRelatorioService` pra decidir a espessura de
 * ferramenta) mas não muda mais o algoritmo de encaixe em si.
 *
 * ## Convenção de eixos — veio (ver CLAUDE.md "Estudo do XML...",
 * documentação oficial do Promob Cut Pro)
 *
 * O veio da CHAPA corre ao longo da sua MAIOR dimensão (`max(chapa_largura,
 * chapa_comprimento)`).
 *
 * ## Revisão 2026-09-17 — `PLATECUTTINGROTATE` trava uma orientação por
 * peça, NÃO é permissão de rotação (ver `PromobXmlParser`, mesma
 * "Revisão 2026-09-17", pra evidência completa)
 *
 * Versão anterior deste docblock dizia "o veio de uma peça corre ao
 * longo da sua `profundidade`... livre pra rotacionar 90° só quando
 * `veio_travado=false`" — tratando `PLATECUTTINGROTATE="N"` como
 * "sem restrição". Errado: `"Y"` e `"N"` são duas travas DIFERENTES
 * (qual dimensão crua da peça — `largura` ou `profundidade` — fica no
 * eixo do veio da chapa), nunca "livre". Confirmado cruzando 11 peças
 * reais de Carvalho Mel Sonoma (XML + PDF real do Promob Cut Pro do
 * MESMO projeto, por `UNIQUEID`): as "Y" sempre saem com a dimensão
 * invertida da chapa em relação ao XML cru, as "N" sempre saem na
 * MESMA ordem do XML — sem exceção, incluindo duas instâncias da
 * MESMA peça (mesma descrição/medida), uma "Y" outra "N", cada uma
 * desenhada na orientação prevista por essa regra. Só `PLATECUTTINGROTATE`
 * ausente/"NONE" é realmente livre.
 *
 * `PromobXmlParser` agora expõe isso já pronto em `veio_eixo_fixo`
 * (`'largura'` | `'profundidade'` | `null`) — a dimensão crua que
 * precisa ficar no eixo do veio da chapa, ou `null` quando livre.
 * `pode_rotacionar` só é `true` quando `veio_eixo_fixo === null`;
 * quando travada (`'largura'` ou `'profundidade'`), existe exatamente
 * UMA orientação válida, nunca as duas (ver `encaixarGrupo()`, onde
 * `largura_natural`/`comprimento_natural` são montados a partir desse
 * campo).
 */
final class PlanoCorteNestingService
{
    /**
     * @param  iterable<int, array{id: int, referencia: string, descricao: string, largura: float, profundidade: float, repeticao: int, material: ?string, cor: ?string, espessura: ?float, fornecedor: ?string, chapa_largura: ?float, chapa_comprimento: ?float, veio_travado: bool, veio_eixo_fixo: ?string}>  $componentes  Componentes de madeira/painel (`componentizado=true`) do Projeto inteiro — ver `ItemProjetoComponente`.
     * @param  float  $kerf  Espessura da serra ou da fresa (mm) — espaço consumido em cada corte.
     * @param  float  $limpezaBordas  Margem (mm) descontada do perímetro de CADA chapa antes de encaixar (aparas de esquadrejamento).
     * @param  string  $tipoEquipamento  'serra' ou 'cnc' — reservado (ver docblock da classe, "Revisão 2026-09-13 (rodada 4)"): hoje não muda o algoritmo de encaixe, só é repassado por quem chama pra decidir qual espessura de ferramenta pedir.
     * @return array{
     *     grupos: array<int, array{
     *         chave: string, material: string, cor: string, espessura: float, fornecedor: ?string,
     *         chapa_largura: float, chapa_comprimento: float,
     *         chapas: array<int, array{
     *             numero: int, aproveitamento: float, area_util_mm2: float, area_ocupada_mm2: float, quantidade_cortes: int,
     *             pecas: array<int, array{componente_id: int, instancia: int, referencia: string, descricao: string, x: float, y: float, largura_corte: float, comprimento_corte: float, rotacionado: bool}>
     *         }>
     *     }>,
     *     nao_classificados: array<int, array{componente_id: int, descricao: string, motivo: string}>
     * }
     */
    public static function gerar(iterable $componentes, float $kerf, float $limpezaBordas, string $tipoEquipamento = 'serra'): array
    {
        $porGrupo = [];
        $naoClassificados = [];

        foreach ($componentes as $componente) {
            if ((float) $componente['largura'] <= 0.0 || (float) $componente['profundidade'] <= 0.0) {
                // Sem área nenhuma — não é uma peça de corte de
                // verdade (ex. algum registro manual incompleto).
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

            // Eixo do veio da CHAPA = maior dimensão (ver docblock da
            // classe). Quando `chapa_largura` é a maior (praticamente
            // sempre, nas fixtures reais), o eixo do veio da chapa
            // corresponde ao slot "largura" do desenho; quando
            // `chapa_comprimento` for maior, inverte.
            $veioNoEixoLargura = $porGrupo[$chave]['meta']['chapa_largura'] >= $porGrupo[$chave]['meta']['chapa_comprimento'];

            $repeticao = max(1, (int) $componente['repeticao']);

            // Qual dimensão CRUA da peça (`largura` ou `profundidade`)
            // precisa ficar no eixo do veio da chapa — ver
            // "Revisão 2026-09-17" no docblock da classe.
            // `veio_eixo_fixo=null` (peça sem veio/"NONE"): não há
            // restrição nenhuma — usa `largura` crua como ponto de
            // partida (não importa qual, já que as duas orientações
            // são igualmente válidas) e deixa `pode_rotacionar=true`
            // pro motor escolher a que encaixa melhor.
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
                    // Orientação NATURAL (veio da peça paralelo ao
                    // veio da chapa, ou — se livre — só um ponto de
                    // partida) — ver docblock da classe.
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

            [$chapas, $semEncaixe] = static::encaixarGrupo($grupo['pecas'], $usavelLargura, $usavelComprimento, $kerf, $limpezaBordas);

            foreach ($semEncaixe as $peca) {
                $naoClassificados[] = [
                    'componente_id' => $peca['componente_id'],
                    'descricao'     => $peca['descricao'],
                    'motivo'        => 'Peça maior que a área útil da chapa em qualquer orientação permitida.',
                ];
            }

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

    /**
     * @param  array<int, array{componente_id: int, instancia: int, referencia: string, descricao: string, largura_natural: float, comprimento_natural: float, pode_rotacionar: bool}>  $pecas
     * @return array{0: array<int, array{numero: int, aproveitamento: float, area_util_mm2: float, area_ocupada_mm2: float, quantidade_cortes: int, pecas: array<int, array{componente_id: int, instancia: int, referencia: string, descricao: string, x: float, y: float, largura_corte: float, comprimento_corte: float, rotacionado: bool}>}>, 1: array<int, array{componente_id: int, descricao: string}>}
     */
    private static function encaixarGrupo(array $pecas, float $usavelLargura, float $usavelComprimento, float $kerf, float $limpezaBordas): array
    {
        $viaveis = [];
        $semEncaixe = [];

        foreach ($pecas as $peca) {
            $cabe = false;

            foreach (static::orientacoesDaPeca($peca) as $o) {
                if ($o['largura'] <= $usavelLargura + 0.001 && $o['comprimento'] <= $usavelComprimento + 0.001) {
                    $cabe = true;

                    break;
                }
            }

            if (! $cabe) {
                $semEncaixe[] = $peca;

                continue;
            }

            $viaveis[] = $peca;
        }

        // Roda o encaixe completo com 4 COMBINAÇÕES de estratégia (ver
        // docblock da classe, "Revisão 2026-09-14 (rodada 7)") e fica
        // com a que abrir MENOS chapas — empate desempatado pelo maior
        // aproveitamento somado:
        //   - grupo EXATO vira GRADE (rows×cols) ou continua FAIXA 1D
        //     (a de antes) — a grade costuma sobrar menos espaço, mas
        //     NEM SEMPRE (medido: ajudou muito um grupo, atrapalhou
        //     outro) — testar as duas e comparar é mais seguro que
        //     substituir cegamente.
        //   - "melhor encaixe" por ÁREA ou por LADO MAIS CURTO.
        $melhorResultado = null;

        foreach (['grade', 'lane'] as $estrategiaExato) {
            $blocos = static::agruparEmBlocos($viaveis, $usavelLargura, $usavelComprimento, $kerf, $estrategiaExato);

            // Maior-primeiro, mesma heurística de sempre — agora por
            // ÁREA DO BLOCO (um bloco de várias peças coladas conta
            // pela área total dele, não peça a peça).
            usort($blocos, fn (array $a, array $b): int => ($b['largura'] * $b['comprimento']) <=> ($a['largura'] * $a['comprimento']));

            foreach (['area', 'lado_curto'] as $criterio) {
                // 'global' (busca de sempre, melhor-encaixe entre TODAS
                // as chapas já abertas) ou 'sequencial' (esgota cada
                // chapa em ORDEM antes de olhar pra próxima — ver
                // `encaixarBlocoSequencial()`, "Revisão 2026-09-14
                // rodada 9"): testa as duas, fica com a melhor.
                foreach (['global', 'sequencial'] as $ordemEncaixe) {
                    $chapasTeste = [];

                    foreach ($blocos as $bloco) {
                        if ($ordemEncaixe === 'sequencial') {
                            static::encaixarBlocoSequencial($chapasTeste, $bloco, $usavelLargura, $usavelComprimento, $kerf, $limpezaBordas, $criterio);
                        } else {
                            static::encaixarBloco($chapasTeste, $bloco, $usavelLargura, $usavelComprimento, $kerf, $limpezaBordas, $criterio);
                        }
                    }

                    $resultado = static::montarResultadoChapas($chapasTeste, $usavelLargura, $usavelComprimento);
                    $aproveitamentos = array_column($resultado, 'aproveitamento');
                    // Score pra comparar: [quantidade de chapas, -soma
                    // do aproveitamento, -MAIOR aproveitamento] — menor
                    // é melhor nos três. O 3º item é um desempate NOVO
                    // (ver docblock, "Revisão 2026-09-14 rodada 9"): a
                    // SOMA do aproveitamento não muda quando só
                    // REDISTRIBUI peças entre chapas já abertas (mesma
                    // área ocupada no total, só troca de chapa) — sem
                    // esse desempate, "1ª chapa bem cheia, resto numa
                    // 2ª pequena" (o que o usuário pediu, e é como o
                    // Promob mostra) e "espalhado mais ou menos igual
                    // entre as 2" dão o MESMO score.
                    $score = [count($resultado), -array_sum($aproveitamentos), -($aproveitamentos === [] ? 0.0 : max($aproveitamentos))];

                    if ($melhorResultado === null || $score < $melhorResultado['score']) {
                        $melhorResultado = ['resultado' => $resultado, 'score' => $score];
                    }
                }
            }
        }

        return [$melhorResultado['resultado'], $semEncaixe];
    }

    /**
     * @param  array{largura_natural: float, comprimento_natural: float, pode_rotacionar: bool}  $peca
     * @return array<int, array{largura: float, comprimento: float, rotacionado: bool}>
     */
    private static function orientacoesDaPeca(array $peca): array
    {
        $orientacoes = [
            ['largura' => $peca['largura_natural'], 'comprimento' => $peca['comprimento_natural'], 'rotacionado' => false],
        ];

        if ($peca['pode_rotacionar']) {
            $orientacoes[] = ['largura' => $peca['comprimento_natural'], 'comprimento' => $peca['largura_natural'], 'rotacionado' => true];
        }

        return $orientacoes;
    }

    /**
     * Agrupa peças em "blocos" (faixas retas de peças coladas) ANTES
     * de tentar encaixar qualquer coisa na chapa — ver docblock da
     * classe, "Revisão 2026-09-14 (rodada 6)". Toda peça acaba em
     * EXATAMENTE um bloco, mesmo que sozinha (bloco de 1 = peça
     * avulsa de sempre).
     *
     * @param  array<int, array{componente_id: int, instancia: int, referencia: string, descricao: string, largura_natural: float, comprimento_natural: float, pode_rotacionar: bool}>  $pecas
     * @param  string  $estrategiaExato  'grade' ou 'lane' — como um grupo de tamanho EXATO vira bloco (ver `montarLanesDoGrupo()`, "Revisão 2026-09-14 rodada 7").
     * @return array<int, array{largura: float, comprimento: float, tipo: string, pecas: array<int, array{peca: array, local_x: float, local_y: float, largura: float, comprimento: float, rotacionado: bool}>}>
     */
    private static function agruparEmBlocos(array $pecas, float $usavelLargura, float $usavelComprimento, float $kerf, string $estrategiaExato = 'grade'): array
    {
        $pecas = array_values($pecas);
        $n = count($pecas);
        $atribuida = array_fill(0, $n, false);

        // --- Passo 1: candidatos por tamanho EXATO (largura E
        // comprimento iguais), considerando as orientações permitidas
        // de cada peça — só entram orientações que INDIVIDUALMENTE já
        // cabem na área útil (senão um bloco poderia nascer maior que
        // a chapa).
        $porChaveExata = [];

        foreach ($pecas as $i => $peca) {
            foreach (static::orientacoesDaPeca($peca) as $o) {
                if ($o['largura'] > $usavelLargura + 0.001 || $o['comprimento'] > $usavelComprimento + 0.001) {
                    continue;
                }

                $chave = number_format($o['largura'], 1, '.', '').'x'.number_format($o['comprimento'], 1, '.', '');
                $porChaveExata[$chave][] = ['indice' => $i, 'peca' => $peca, 'orientacao' => $o];
            }
        }

        $candidatosExato = [];

        foreach ($porChaveExata as $itens) {
            $candidatosExato[] = ['tipo' => 'exato', 'itens' => $itens];
        }

        $grupos = static::formarGruposGulosos($candidatosExato, $atribuida);

        // --- Passo 2: só a LARGURA ou só o COMPRIMENTO em comum,
        // entre as peças que sobraram do passo 1 (ver docblock —
        // "basta um dos lados ser igual, se o veio permitir").
        $porLargura = [];
        $porComprimento = [];

        foreach ($pecas as $i => $peca) {
            if ($atribuida[$i]) {
                continue;
            }

            foreach (static::orientacoesDaPeca($peca) as $o) {
                if ($o['largura'] > $usavelLargura + 0.001 || $o['comprimento'] > $usavelComprimento + 0.001) {
                    continue;
                }

                $item = ['indice' => $i, 'peca' => $peca, 'orientacao' => $o];
                $porLargura[number_format($o['largura'], 1, '.', '')][] = $item;
                $porComprimento[number_format($o['comprimento'], 1, '.', '')][] = $item;
            }
        }

        $candidatosPasso2 = [];

        foreach ($porLargura as $itens) {
            $candidatosPasso2[] = ['tipo' => 'largura', 'itens' => $itens];
        }

        foreach ($porComprimento as $itens) {
            $candidatosPasso2[] = ['tipo' => 'comprimento', 'itens' => $itens];
        }

        $grupos = array_merge($grupos, static::formarGruposGulosos($candidatosPasso2, $atribuida));

        // --- Monta os BLOCOS (lanes) a partir dos grupos formados,
        // quebrando em vários blocos quando a soma não cabe numa
        // faixa só.
        $blocos = [];

        foreach ($grupos as $grupoInfo) {
            $blocos = array_merge($blocos, static::montarLanesDoGrupo($grupoInfo, $usavelLargura, $usavelComprimento, $kerf, $estrategiaExato));
        }

        // --- Sobras: peça que não caiu em nenhum grupo de 2+ vira um
        // bloco de 1 peça só (peça avulsa de sempre).
        foreach ($pecas as $i => $peca) {
            if ($atribuida[$i]) {
                continue;
            }

            $orientacao = static::orientacoesDaPeca($peca)[0];

            $blocos[] = [
                'largura'     => $orientacao['largura'],
                'comprimento' => $orientacao['comprimento'],
                'tipo'        => 'avulsa',
                'pecas'       => [[
                    'peca'        => $peca,
                    'local_x'     => 0.0,
                    'local_y'     => 0.0,
                    'largura'     => $orientacao['largura'],
                    'comprimento' => $orientacao['comprimento'],
                    'rotacionado' => $orientacao['rotacionado'],
                ]],
            ];
        }

        return $blocos;
    }

    /**
     * Busca gulosa: processa os candidatos com MAIS itens primeiro;
     * pra cada um, pega as peças ainda não atribuídas — só confirma o
     * grupo se sobrar 2 ou mais (senão devolve a reserva, pra essa
     * peça poder tentar formar grupo com outro critério depois).
     *
     * @param  array<int, array{tipo: string, itens: array<int, array{indice: int, peca: array, orientacao: array}>}>  $candidatos
     * @param  array<int, bool>  $atribuida  (por referência)
     * @return array<int, array{tipo: string, pecas: array<int, array{indice: int, peca: array, orientacao: array}>}>
     */
    private static function formarGruposGulosos(array $candidatos, array &$atribuida): array
    {
        usort($candidatos, fn (array $a, array $b): int => count($b['itens']) <=> count($a['itens']));

        $grupos = [];

        foreach ($candidatos as $candidato) {
            $membros = [];

            foreach ($candidato['itens'] as $item) {
                if ($atribuida[$item['indice']]) {
                    continue;
                }

                $membros[] = $item;
                $atribuida[$item['indice']] = true;
            }

            if (count($membros) < 2) {
                foreach ($membros as $m) {
                    $atribuida[$m['indice']] = false;
                }

                continue;
            }

            $grupos[] = ['tipo' => $candidato['tipo'], 'pecas' => $membros];
        }

        return $grupos;
    }

    /**
     * Transforma um grupo (mesmo tamanho exato, ou mesma largura, ou
     * mesmo comprimento) em um ou mais blocos/lanes — quebra em vários
     * quando a soma das peças não cabe numa faixa só.
     *
     * @param  array{tipo: string, pecas: array<int, array{indice: int, peca: array, orientacao: array}>}  $grupoInfo
     * @return array<int, array{largura: float, comprimento: float, tipo: string, pecas: array<int, array{peca: array, local_x: float, local_y: float, largura: float, comprimento: float, rotacionado: bool}>}>
     */
    private static function montarLanesDoGrupo(array $grupoInfo, float $usavelLargura, float $usavelComprimento, float $kerf, string $estrategiaExato = 'grade'): array
    {
        // Peças de tamanho EXATO iguais viram uma GRADE (rows×cols) em
        // vez de uma faixa 1D só — ver docblock da classe, "Revisão
        // 2026-09-14 (rodada 7)" e `montarBlocosGrade()`. Mantemos a
        // opção de continuar com a faixa 1D (`$estrategiaExato ===
        // 'lane'`) porque medimos que a grade nem sempre é melhor —
        // `encaixarGrupo()` testa as duas e fica com o resultado que
        // abrir menos chapas.
        if ($grupoInfo['tipo'] === 'exato' && $estrategiaExato === 'grade') {
            return static::montarBlocosGrade($grupoInfo['pecas'], $usavelLargura, $usavelComprimento, $kerf);
        }

        $membros = $grupoInfo['pecas'];
        // 'largura' empilha por COMPRIMENTO (largura fixa,
        // vira uma coluna); 'comprimento' empilha por LARGURA
        // (comprimento fixo, vira uma fileira).
        $eixoLargura = $grupoInfo['tipo'] !== 'comprimento';

        if ($eixoLargura) {
            $fixo = $membros[0]['orientacao']['largura'];
            usort($membros, fn (array $a, array $b): int => $b['orientacao']['comprimento'] <=> $a['orientacao']['comprimento']);
            $limite = $usavelComprimento;
        } else {
            $fixo = $membros[0]['orientacao']['comprimento'];
            usort($membros, fn (array $a, array $b): int => $b['orientacao']['largura'] <=> $a['orientacao']['largura']);
            $limite = $usavelLargura;
        }

        $blocos = [];
        $laneAtual = [];
        $extensaoAtual = 0.0;

        foreach ($membros as $m) {
            $extensaoPeca = $eixoLargura ? $m['orientacao']['comprimento'] : $m['orientacao']['largura'];
            $acrescimo = $extensaoPeca + ($laneAtual === [] ? 0.0 : $kerf);

            if ($laneAtual !== [] && $extensaoAtual + $acrescimo > $limite + 0.01) {
                $blocos[] = static::fecharLane($laneAtual, $fixo, $eixoLargura, $kerf);
                $laneAtual = [];
                $extensaoAtual = 0.0;
                $acrescimo = $extensaoPeca;
            }

            $laneAtual[] = $m;
            $extensaoAtual += $acrescimo;
        }

        if ($laneAtual !== []) {
            $blocos[] = static::fecharLane($laneAtual, $fixo, $eixoLargura, $kerf);
        }

        // Lane que sobrou com 1 peça só (depois de quebrar em vários
        // blocos) é só a peça avulsa de sempre — não faz sentido
        // "isolar" uma faixa de 1.
        foreach ($blocos as &$bloco) {
            if (count($bloco['pecas']) === 1) {
                $bloco['tipo'] = 'avulsa';
            }
        }

        unset($bloco);

        return $blocos;
    }

    /**
     * @param  array<int, array{indice: int, peca: array, orientacao: array}>  $membros
     * @return array{largura: float, comprimento: float, tipo: string, pecas: array<int, array{peca: array, local_x: float, local_y: float, largura: float, comprimento: float, rotacionado: bool}>}
     */
    private static function fecharLane(array $membros, float $fixo, bool $eixoLargura, float $kerf): array
    {
        $pecasBloco = [];
        $offset = 0.0;
        $extensaoTotal = 0.0;

        foreach ($membros as $m) {
            $extensaoPeca = $eixoLargura ? $m['orientacao']['comprimento'] : $m['orientacao']['largura'];

            $pecasBloco[] = [
                'peca'        => $m['peca'],
                'local_x'     => $eixoLargura ? 0.0 : $offset,
                'local_y'     => $eixoLargura ? $offset : 0.0,
                'largura'     => $eixoLargura ? $fixo : $extensaoPeca,
                'comprimento' => $eixoLargura ? $extensaoPeca : $fixo,
                'rotacionado' => $m['orientacao']['rotacionado'],
            ];

            $offset += $extensaoPeca + $kerf;
            $extensaoTotal += $extensaoPeca;
        }

        $extensaoTotal += $kerf * (count($membros) - 1);

        return [
            'largura'     => $eixoLargura ? $fixo : $extensaoTotal,
            'comprimento' => $eixoLargura ? $extensaoTotal : $fixo,
            'tipo'        => 'lane',
            'pecas'       => $pecasBloco,
        ];
    }

    /**
     * Peças de tamanho EXATO iguais (largura=comprimento fixos pra
     * TODAS) viram uma GRADE (cols colunas × rows fileiras) em vez de
     * só uma coluna 1×N — ver docblock da classe, "Revisão 2026-09-14
     * (rodada 7)": pra N peças idênticas, testamos TODAS as grades
     * cols×rows que cabem na área útil (cols de 1 até o máximo que
     * cabe lado a lado, rows = ceil(N/cols)) e ficamos com a que sobra
     * MENOS posições vazias (`cols*rows - N`) — empate desempata pela
     * menor área do retângulo delimitador. Uma faixa 1×N (a antiga
     * `fecharLane`) é só o caso cols=1 dessa busca — continua sendo
     * escolhida quando é mesmo a melhor opção, só não é mais a ÚNICA
     * testada.
     *
     * Quando N excede a maior grade que cabe na chapa inteira (raro,
     * só em grupos muito grandes), enche a maior grade possível como 1
     * bloco e repete o processo pro restante — pode gerar mais de 1
     * bloco de grade pro mesmo grupo.
     *
     * @param  array<int, array{indice: int, peca: array, orientacao: array}>  $membros
     * @return array<int, array{largura: float, comprimento: float, tipo: string, pecas: array<int, array{peca: array, local_x: float, local_y: float, largura: float, comprimento: float, rotacionado: bool}>}>
     */
    private static function montarBlocosGrade(array $membros, float $usavelLargura, float $usavelComprimento, float $kerf): array
    {
        $largura = $membros[0]['orientacao']['largura'];
        $comprimento = $membros[0]['orientacao']['comprimento'];

        $maxCols = (int) floor(($usavelLargura + $kerf + 0.001) / ($largura + $kerf));
        $maxRows = (int) floor(($usavelComprimento + $kerf + 0.001) / ($comprimento + $kerf));

        if ($maxCols < 1 || $maxRows < 1) {
            // Defesa extra (não devia acontecer — só entram aqui
            // orientações que já foram filtradas como cabíveis
            // individualmente em `agruparEmBlocos()`): cada peça vira
            // bloco avulso.
            return array_map(fn (array $m): array => [
                'largura'     => $m['orientacao']['largura'],
                'comprimento' => $m['orientacao']['comprimento'],
                'tipo'        => 'avulsa',
                'pecas'       => [[
                    'peca' => $m['peca'], 'local_x' => 0.0, 'local_y' => 0.0,
                    'largura' => $m['orientacao']['largura'], 'comprimento' => $m['orientacao']['comprimento'],
                    'rotacionado' => $m['orientacao']['rotacionado'],
                ]],
            ], $membros);
        }

        $capacidadeMax = $maxCols * $maxRows;
        $blocos = [];
        $restantes = array_values($membros);

        while ($restantes !== []) {
            $qtd = min(count($restantes), $capacidadeMax);

            $melhorGrade = static::buscarMelhorGrade($qtd, $largura, $comprimento, $usavelLargura, $usavelComprimento, $kerf);

            $cols = $melhorGrade['cols'];
            $rows = $melhorGrade['rows'];
            $pecasBloco = [];

            for ($i = 0; $i < $qtd; $i++) {
                $m = $restantes[$i];

                $pecasBloco[] = [
                    'peca'        => $m['peca'],
                    'local_x'     => ($i % $cols) * ($largura + $kerf),
                    'local_y'     => intdiv($i, $cols) * ($comprimento + $kerf),
                    'largura'     => $largura,
                    'comprimento' => $comprimento,
                    'rotacionado' => $m['orientacao']['rotacionado'],
                ];
            }

            $blocos[] = [
                'largura'     => $cols * $largura + ($cols - 1) * $kerf,
                'comprimento' => $rows * $comprimento + ($rows - 1) * $kerf,
                'tipo'        => $qtd === 1 ? 'avulsa' : 'grade',
                'pecas'       => $pecasBloco,
            ];

            $restantes = array_slice($restantes, $qtd);
        }

        return $blocos;
    }

    /**
     * Busca a melhor grade (cols×rows) pra encaixar `$qtd` peças
     * idênticas (mesma largura/comprimento) na área útil INTEIRA da
     * chapa — mesma busca usada por `montarBlocosGrade()` (extraída
     * pra ser reaproveitada também por `reconstruirBloco()`, ver
     * docblock da classe "Revisão 2026-09-14 (rodada 8)"): testa cols
     * de 1 até o máximo que cabe lado a lado, rows = ceil(qtd/cols), e
     * fica com a que sobra MENOS posições vazias (empate: menor área
     * do retângulo delimitador).
     *
     * @return null|array{cols: int, rows: int, desperdicio: int, area: float}
     */
    private static function buscarMelhorGrade(int $qtd, float $largura, float $comprimento, float $usavelLargura, float $usavelComprimento, float $kerf): ?array
    {
        $maxCols = (int) floor(($usavelLargura + $kerf + 0.001) / ($largura + $kerf));
        $maxRows = (int) floor(($usavelComprimento + $kerf + 0.001) / ($comprimento + $kerf));

        if ($maxCols < 1 || $maxRows < 1) {
            return null;
        }

        $melhorGrade = null;

        for ($cols = 1; $cols <= $maxCols; $cols++) {
            $rows = (int) ceil($qtd / $cols);

            if ($rows > $maxRows) {
                continue;
            }

            $desperdicio = ($cols * $rows) - $qtd;
            $area = ($cols * $largura + ($cols - 1) * $kerf) * ($rows * $comprimento + ($rows - 1) * $kerf);

            if (
                $melhorGrade === null
                || $desperdicio < $melhorGrade['desperdicio']
                || ($desperdicio === $melhorGrade['desperdicio'] && $area < $melhorGrade['area'])
            ) {
                $melhorGrade = ['cols' => $cols, 'rows' => $rows, 'desperdicio' => $desperdicio, 'area' => $area];
            }
        }

        return $melhorGrade;
    }

    /**
     * Reconstrói um bloco 'grade' ou 'lane' a partir de uma LISTA
     * PARCIAL de membros (um subconjunto dos originais) — usado pelo
     * "recorte" de bloco em `encaixarBloco()` (ver docblock da classe,
     * "Revisão 2026-09-14 rodada 8"): quando um bloco inteiro não cabe
     * em nenhuma chapa já aberta, tentamos uma FATIA menor dele (as N-1
     * primeiras peças, depois N-2, etc.) pra ver se ELA cabe numa sobra
     * já existente, antes de abrir chapa nova — fisicamente é só mais
     * um corte reto dividindo a faixa/grade original em duas partes
     * menores, continua 100% guilhotina.
     *
     * Pra 'grade' (peças todas do MESMO tamanho): busca a melhor
     * grade cols×rows pra essa quantidade menor. Pra 'lane' (largura OU
     * comprimento fixos, o outro lado variando): mantém a ordem dada e
     * reempilha ao longo do mesmo eixo fixo (detectado pelo próprio
     * subconjunto — a dimensão que é igual em TODAS as peças).
     *
     * @param  array<int, array{peca: array, largura: float, comprimento: float, rotacionado: bool}>  $membros
     * @return array{largura: float, comprimento: float, tipo: string, pecas: array<int, array{peca: array, local_x: float, local_y: float, largura: float, comprimento: float, rotacionado: bool}>}
     */
    private static function reconstruirBloco(string $tipo, array $membros, float $usavelLargura, float $usavelComprimento, float $kerf): array
    {
        $membros = array_values($membros);

        if (count($membros) === 1) {
            $m = $membros[0];

            return [
                'largura'     => $m['largura'],
                'comprimento' => $m['comprimento'],
                'tipo'        => 'avulsa',
                'pecas'       => [['peca' => $m['peca'], 'local_x' => 0.0, 'local_y' => 0.0, 'largura' => $m['largura'], 'comprimento' => $m['comprimento'], 'rotacionado' => $m['rotacionado']]],
            ];
        }

        if ($tipo === 'grade') {
            $largura = $membros[0]['largura'];
            $comprimento = $membros[0]['comprimento'];
            $melhorGrade = static::buscarMelhorGrade(count($membros), $largura, $comprimento, $usavelLargura, $usavelComprimento, $kerf);
            $cols = $melhorGrade['cols'];
            $rows = $melhorGrade['rows'];

            $pecasBloco = [];

            foreach ($membros as $i => $m) {
                $pecasBloco[] = [
                    'peca'        => $m['peca'],
                    'local_x'     => ($i % $cols) * ($largura + $kerf),
                    'local_y'     => intdiv($i, $cols) * ($comprimento + $kerf),
                    'largura'     => $largura,
                    'comprimento' => $comprimento,
                    'rotacionado' => $m['rotacionado'],
                ];
            }

            return [
                'largura'     => $cols * $largura + ($cols - 1) * $kerf,
                'comprimento' => $rows * $comprimento + ($rows - 1) * $kerf,
                'tipo'        => 'grade',
                'pecas'       => $pecasBloco,
            ];
        }

        // 'lane': o eixo FIXO é o que tiver a mesma medida em TODOS os
        // membros do subconjunto (continua valendo — é subconjunto de
        // um grupo que já compartilhava largura OU comprimento).
        $eixoLargura = true;

        foreach ($membros as $m) {
            if (abs($m['largura'] - $membros[0]['largura']) > 0.01) {
                $eixoLargura = false;

                break;
            }
        }

        $fixo = $eixoLargura ? $membros[0]['largura'] : $membros[0]['comprimento'];

        $pecasBloco = [];
        $offset = 0.0;
        $extensaoTotal = 0.0;

        foreach ($membros as $m) {
            $extensaoPeca = $eixoLargura ? $m['comprimento'] : $m['largura'];

            $pecasBloco[] = [
                'peca'        => $m['peca'],
                'local_x'     => $eixoLargura ? 0.0 : $offset,
                'local_y'     => $eixoLargura ? $offset : 0.0,
                'largura'     => $eixoLargura ? $fixo : $extensaoPeca,
                'comprimento' => $eixoLargura ? $extensaoPeca : $fixo,
                'rotacionado' => $m['rotacionado'],
            ];

            $offset += $extensaoPeca + $kerf;
            $extensaoTotal += $extensaoPeca;
        }

        $extensaoTotal += $kerf * (count($membros) - 1);

        return [
            'largura'     => $eixoLargura ? $fixo : $extensaoTotal,
            'comprimento' => $eixoLargura ? $extensaoTotal : $fixo,
            'tipo'        => 'lane',
            'pecas'       => $pecasBloco,
        ];
    }

    /**
     * Efetivamente coloca um bloco já POSICIONADO (retângulo livre
     * escolhido + orientação) numa chapa: separa/divide o retângulo
     * livre, grava as peças com coordenadas absolutas, soma os cortes.
     * Extraído de `encaixarBloco()` pra ser reaproveitado tanto no
     * caminho normal quanto no "recorte" de bloco (ver
     * `reconstruirBloco()`, "Revisão 2026-09-14 rodada 8").
     *
     * @param  array<int, array{livres: array<int, array{x: float, y: float, w: float, h: float}>, pecas: array<int, mixed>, cortes?: int}>  $chapas  (por referência)
     * @param  array{chapaIndex: int, retanguloIndex: int, orientacao: array{largura: float, comprimento: float, girar: bool}}  $melhor
     * @param  array{largura: float, comprimento: float, tipo: string, pecas: array<int, array{peca: array, local_x: float, local_y: float, largura: float, comprimento: float, rotacionado: bool}>}  $bloco
     */
    private static function colocarBlocoNoRetangulo(array &$chapas, array $melhor, array $bloco, float $kerf, float $limpezaBordas): void
    {
        $chapaIndex = $melhor['chapaIndex'];
        $retangulo = $chapas[$chapaIndex]['livres'][$melhor['retanguloIndex']];
        $orientacao = $melhor['orientacao'];

        array_splice($chapas[$chapaIndex]['livres'], $melhor['retanguloIndex'], 1);

        $filhos = static::dividirRetangulo($retangulo, ['largura' => $orientacao['largura'], 'comprimento' => $orientacao['comprimento'], 'rotacionado' => false], $kerf);

        foreach ($filhos as $filho) {
            $chapas[$chapaIndex]['livres'][] = $filho;
        }

        $girado = $orientacao['girar'];

        foreach ($bloco['pecas'] as $p) {
            [$localX, $localY, $larguraCorte, $comprimentoCorte] = $girado
                ? [$p['local_y'], $p['local_x'], $p['comprimento'], $p['largura']]
                : [$p['local_x'], $p['local_y'], $p['largura'], $p['comprimento']];

            $chapas[$chapaIndex]['pecas'][] = [
                'componente_id'     => $p['peca']['componente_id'],
                'instancia'         => $p['peca']['instancia'],
                'referencia'        => $p['peca']['referencia'],
                'descricao'         => $p['peca']['descricao'],
                'x'                 => $limpezaBordas + $retangulo['x'] + $localX,
                'y'                 => $limpezaBordas + $retangulo['y'] + $localY,
                'largura_corte'     => $larguraCorte,
                'comprimento_corte' => $comprimentoCorte,
                'rotacionado'       => $girado ? ! $p['rotacionado'] : $p['rotacionado'],
            ];
        }

        // Contagem real de cortes (ver docblock da classe, "Revisão
        // 2026-09-14 rodada 6"): os cortes que isolam o bloco do resto
        // do retângulo livre (0 a 2, o que `dividirRetangulo()`
        // devolveu de filhos) + (N-1) cortes de seccionamento pra
        // separar as N peças coladas dentro do bloco.
        $cortesIsolamento = count($filhos);
        $cortesInternos = max(0, count($bloco['pecas']) - 1);

        $chapas[$chapaIndex]['cortes'] = ($chapas[$chapaIndex]['cortes'] ?? 0) + $cortesIsolamento + $cortesInternos;
    }

    /**
     * Variante de `encaixarBloco()` que ESGOTA cada chapa já aberta, EM
     * ORDEM (a mais antiga primeiro), antes de sequer olhar pra
     * próxima — ao contrário da busca normal (melhor-encaixe GLOBAL
     * entre todas as chapas, que escolhe só pela menor sobra e pode
     * mandar um bloco inteiro pra uma chapa mais NOVA só porque ela tem
     * espaço de sobra, mesmo quando a chapa mais antiga já tinha lugar
     * pra PARTE dele). Usa o mesmo "recorte" de `reconstruirBloco()`
     * (testa a chapa inteira: N peças, depois N-1, ... até 1) mas
     * chapa-a-chapa em vez de globalmente. Ver docblock da classe,
     * "Revisão 2026-09-14 (rodada 9)" — foi o que fechou o aproveitamento
     * da 1ª chapa em ~93% (a 2ª só fica com o que REALMENTE não cabe em
     * nenhum lugar da 1ª, nem em fatia nenhuma).
     *
     * @param  array<int, array{livres: array<int, array{x: float, y: float, w: float, h: float}>, pecas: array<int, mixed>, cortes?: int}>  $chapas  (por referência)
     * @param  array{largura: float, comprimento: float, tipo: string, pecas: array<int, array{peca: array, local_x: float, local_y: float, largura: float, comprimento: float, rotacionado: bool}>}  $bloco
     */
    private static function encaixarBlocoSequencial(array &$chapas, array $bloco, float $usavelLargura, float $usavelComprimento, float $kerf, float $limpezaBordas, string $criterio): void
    {
        $todasGiraveis = true;

        foreach ($bloco['pecas'] as $p) {
            if (! $p['peca']['pode_rotacionar']) {
                $todasGiraveis = false;

                break;
            }
        }

        $n = count($bloco['pecas']);
        $shrinkavel = $n > 1 && in_array($bloco['tipo'], ['grade', 'lane'], true);

        // Pra cada quantidade K (do bloco inteiro, N, até 1), tenta MAIS
        // de uma forma de arrumar essas K peças antes de desistir e cair
        // pra K-1 — ver docblock, "Revisão 2026-09-14 (rodada 10)": um
        // grupo de peças EXATAS (tipo 'grade') também é sempre válido
        // como FAIXA 1D ('lane', a mesma peça olhando só pra 1 lado);
        // testar as duas formas pra CADA K evita fragmentar um grupo em
        // vários pedacinhos espalhados quando a grade não cabe mas uma
        // faixa reta do MESMO tamanho cabia inteira (ex.: 8 peças iguais
        // que não formam uma grade 4×2 na sobra disponível, mas cabem
        // inteiras numa faixa 1×8 mais comprida e mais fina).
        foreach ($chapas as $chapaIndex => $chapa) {
            $maxK = $shrinkavel ? $n : 1;

            for ($k = $maxK; $k >= 1; $k--) {
                $membros = array_map(
                    fn (array $p): array => ['peca' => $p['peca'], 'largura' => $p['largura'], 'comprimento' => $p['comprimento'], 'rotacionado' => $p['rotacionado']],
                    array_slice($bloco['pecas'], 0, $k)
                );

                $candidatosTipo = $bloco['tipo'] === 'grade' ? ['grade', 'lane'] : [$bloco['tipo']];
                $melhor = null;
                $subEscolhido = null;

                foreach ($candidatosTipo as $tipoTentativa) {
                    $sub = ($k === $n && $tipoTentativa === $bloco['tipo'])
                        ? $bloco
                        : static::reconstruirBloco($tipoTentativa, $membros, $usavelLargura, $usavelComprimento, $kerf);

                    $orientacoesSub = [['largura' => $sub['largura'], 'comprimento' => $sub['comprimento'], 'girar' => false]];

                    if ($todasGiraveis && abs($sub['largura'] - $sub['comprimento']) > 0.01) {
                        $orientacoesSub[] = ['largura' => $sub['comprimento'], 'comprimento' => $sub['largura'], 'girar' => true];
                    }

                    foreach ($chapa['livres'] as $retanguloIndex => $retangulo) {
                        foreach ($orientacoesSub as $o) {
                            if ($o['largura'] > $retangulo['w'] || $o['comprimento'] > $retangulo['h']) {
                                continue;
                            }

                            $sobra = static::calcularSobra($retangulo, $o, $criterio);

                            if ($melhor === null || $sobra < $melhor['sobra']) {
                                $melhor = ['chapaIndex' => $chapaIndex, 'retanguloIndex' => $retanguloIndex, 'orientacao' => $o, 'sobra' => $sobra];
                                $subEscolhido = $sub;
                            }
                        }
                    }

                    // Achou encaixe com este tipo — não precisa testar o
                    // outro pra este MESMO k (grade primeiro, por ser
                    // normalmente mais compacta; só cai pra lane quando
                    // a grade não serviu).
                    if ($melhor !== null) {
                        break;
                    }
                }

                if ($melhor === null) {
                    continue;
                }

                static::colocarBlocoNoRetangulo($chapas, $melhor, $subEscolhido, $kerf, $limpezaBordas);

                if ($k < $n) {
                    $membrosRestantes = array_map(
                        fn (array $p): array => ['peca' => $p['peca'], 'largura' => $p['largura'], 'comprimento' => $p['comprimento'], 'rotacionado' => $p['rotacionado']],
                        array_slice($bloco['pecas'], $k)
                    );
                    $blocoRestante = static::reconstruirBloco($bloco['tipo'], $membrosRestantes, $usavelLargura, $usavelComprimento, $kerf);

                    static::encaixarBlocoSequencial($chapas, $blocoRestante, $usavelLargura, $usavelComprimento, $kerf, $limpezaBordas, $criterio);
                }

                return;
            }
        }

        // Não coube NADA (nem 1 peça) em nenhuma chapa já aberta — abre
        // chapa nova e coloca o bloco INTEIRO nela (mesma defesa de
        // sempre, um bloco nunca é maior que a área útil).
        $chapas[] = [
            'livres' => [['x' => 0.0, 'y' => 0.0, 'w' => $usavelLargura, 'h' => $usavelComprimento]],
            'pecas'  => [],
            'cortes' => 0,
        ];

        $chapaIndex = array_key_last($chapas);
        $orientacoesBloco = [['largura' => $bloco['largura'], 'comprimento' => $bloco['comprimento'], 'girar' => false]];

        if ($todasGiraveis && abs($bloco['largura'] - $bloco['comprimento']) > 0.01) {
            $orientacoesBloco[] = ['largura' => $bloco['comprimento'], 'comprimento' => $bloco['largura'], 'girar' => true];
        }

        foreach ($orientacoesBloco as $o) {
            if ($o['largura'] <= $usavelLargura && $o['comprimento'] <= $usavelComprimento) {
                $melhor = ['chapaIndex' => $chapaIndex, 'retanguloIndex' => 0, 'orientacao' => $o, 'sobra' => static::calcularSobra(['w' => $usavelLargura, 'h' => $usavelComprimento], $o, $criterio)];
                static::colocarBlocoNoRetangulo($chapas, $melhor, $bloco, $kerf, $limpezaBordas);

                return;
            }
        }
    }

    /**
     * Encaixa um BLOCO inteiro (uma peça avulsa é só um bloco de 1) —
     * busca de melhor-encaixe entre TODOS os retângulos livres de
     * TODAS as chapas já abertas, mais a chance de girar o bloco 90°
     * inteiro quando TODAS as peças dele podem rotacionar (senão
     * giraria uma peça de veio travado).
     *
     * @param  array<int, array{livres: array<int, array{x: float, y: float, w: float, h: float}>, pecas: array<int, mixed>, cortes?: int}>  $chapas  (por referência)
     * @param  array{largura: float, comprimento: float, tipo: string, pecas: array<int, array{peca: array, local_x: float, local_y: float, largura: float, comprimento: float, rotacionado: bool}>}  $bloco
     * @param  string  $criterio  'area' (Best Area Fit) ou 'lado_curto' (Best Short Side Fit) — ver docblock da classe, "Revisão 2026-09-14 (rodada 7)".
     */
    private static function encaixarBloco(array &$chapas, array $bloco, float $usavelLargura, float $usavelComprimento, float $kerf, float $limpezaBordas, string $criterio = 'area'): void
    {
        $orientacoesBloco = [['largura' => $bloco['largura'], 'comprimento' => $bloco['comprimento'], 'girar' => false]];

        $todasGiraveis = true;

        foreach ($bloco['pecas'] as $p) {
            if (! $p['peca']['pode_rotacionar']) {
                $todasGiraveis = false;

                break;
            }
        }

        if ($todasGiraveis && abs($bloco['largura'] - $bloco['comprimento']) > 0.01) {
            $orientacoesBloco[] = ['largura' => $bloco['comprimento'], 'comprimento' => $bloco['largura'], 'girar' => true];
        }

        $melhor = null;

        foreach ($chapas as $chapaIndex => $chapa) {
            foreach ($chapa['livres'] as $retanguloIndex => $retangulo) {
                foreach ($orientacoesBloco as $o) {
                    if ($o['largura'] > $retangulo['w'] || $o['comprimento'] > $retangulo['h']) {
                        continue;
                    }

                    $sobra = static::calcularSobra($retangulo, $o, $criterio);

                    if ($melhor === null || $sobra < $melhor['sobra']) {
                        $melhor = ['chapaIndex' => $chapaIndex, 'retanguloIndex' => $retanguloIndex, 'orientacao' => $o, 'sobra' => $sobra];
                    }
                }
            }
        }

        // Bloco inteiro não coube em NENHUMA sobra já aberta — antes de
        // abrir chapa nova, tenta uma FATIA menor dele (ver docblock da
        // classe, "Revisão 2026-09-14 rodada 8" — foi o que a
        // investigação do grupo Branco/15mm mostrou: um bloco de peças
        // idênticas "colado" (lane/grade) só cabe INTEIRO em algum
        // lugar, então perde sobras já abertas que são grandes o
        // bastante pra ALGUMAS das peças, não pra todas, e força chapa
        // nova mesmo sobrando espaço usável). Testa N-1, N-2, ... até 1
        // peça e fica com a MAIOR fatia que couber numa chapa JÁ
        // ABERTA (sem abrir nenhuma aqui) — o resto (se sobrar) volta
        // recursivamente pra este mesmo método, que tenta de novo
        // (inclusive abrindo chapa nova, se for mesmo preciso).
        if ($melhor === null && count($bloco['pecas']) > 1 && in_array($bloco['tipo'], ['grade', 'lane'], true) && $chapas !== []) {
            for ($k = count($bloco['pecas']) - 1; $k >= 1; $k--) {
                $membrosParciais = array_map(
                    fn (array $p): array => ['peca' => $p['peca'], 'largura' => $p['largura'], 'comprimento' => $p['comprimento'], 'rotacionado' => $p['rotacionado']],
                    array_slice($bloco['pecas'], 0, $k)
                );

                // Testa GRADE e, se o grupo veio de tamanho EXATO, tenta
                // também FAIXA 1D pra essa mesma fatia (ver docblock,
                // "Revisão 2026-09-14 rodada 10") — evita fragmentar um
                // grupo em vários pedacinhos quando a grade não cabe mas
                // uma faixa reta do mesmo tamanho cabia inteira.
                $candidatosTipo = $bloco['tipo'] === 'grade' ? ['grade', 'lane'] : [$bloco['tipo']];
                $melhorParcial = null;
                $subBloco = null;

                foreach ($candidatosTipo as $tipoTentativa) {
                    $subTentativa = static::reconstruirBloco($tipoTentativa, $membrosParciais, $usavelLargura, $usavelComprimento, $kerf);

                    $subOrientacoes = [['largura' => $subTentativa['largura'], 'comprimento' => $subTentativa['comprimento'], 'girar' => false]];

                    if ($todasGiraveis && abs($subTentativa['largura'] - $subTentativa['comprimento']) > 0.01) {
                        $subOrientacoes[] = ['largura' => $subTentativa['comprimento'], 'comprimento' => $subTentativa['largura'], 'girar' => true];
                    }

                    foreach ($chapas as $chapaIndex => $chapa) {
                        foreach ($chapa['livres'] as $retanguloIndex => $retangulo) {
                            foreach ($subOrientacoes as $o) {
                                if ($o['largura'] > $retangulo['w'] || $o['comprimento'] > $retangulo['h']) {
                                    continue;
                                }

                                $sobra = static::calcularSobra($retangulo, $o, $criterio);

                                if ($melhorParcial === null || $sobra < $melhorParcial['sobra']) {
                                    $melhorParcial = ['chapaIndex' => $chapaIndex, 'retanguloIndex' => $retanguloIndex, 'orientacao' => $o, 'sobra' => $sobra];
                                    $subBloco = $subTentativa;
                                }
                            }
                        }
                    }

                    if ($melhorParcial !== null) {
                        break;
                    }
                }

                if ($melhorParcial === null) {
                    continue;
                }

                static::colocarBlocoNoRetangulo($chapas, $melhorParcial, $subBloco, $kerf, $limpezaBordas);

                $membrosRestantes = array_map(
                    fn (array $p): array => ['peca' => $p['peca'], 'largura' => $p['largura'], 'comprimento' => $p['comprimento'], 'rotacionado' => $p['rotacionado']],
                    array_slice($bloco['pecas'], $k)
                );

                $blocoRestante = static::reconstruirBloco($bloco['tipo'], $membrosRestantes, $usavelLargura, $usavelComprimento, $kerf);

                static::encaixarBloco($chapas, $blocoRestante, $usavelLargura, $usavelComprimento, $kerf, $limpezaBordas, $criterio);

                return;
            }
        }

        if ($melhor === null) {
            $chapas[] = [
                'livres' => [['x' => 0.0, 'y' => 0.0, 'w' => $usavelLargura, 'h' => $usavelComprimento]],
                'pecas'  => [],
                'cortes' => 0,
            ];

            $chapaIndex = array_key_last($chapas);

            foreach ($orientacoesBloco as $o) {
                if ($o['largura'] <= $usavelLargura && $o['comprimento'] <= $usavelComprimento) {
                    $melhor = ['chapaIndex' => $chapaIndex, 'retanguloIndex' => 0, 'orientacao' => $o, 'sobra' => static::calcularSobra(['w' => $usavelLargura, 'h' => $usavelComprimento], $o, $criterio)];

                    break;
                }
            }
        }

        if ($melhor === null) {
            // Defesa extra (não devia acontecer — peças inviáveis já
            // foram filtradas antes de montar blocos, e um bloco nunca
            // é maior que a área útil por construção): quebra o bloco
            // em peças avulsas e tenta cada uma sozinha.
            foreach ($bloco['pecas'] as $p) {
                static::encaixarBloco($chapas, [
                    'largura'     => $p['largura'],
                    'comprimento' => $p['comprimento'],
                    'tipo'        => 'avulsa',
                    'pecas'       => [$p],
                ], $usavelLargura, $usavelComprimento, $kerf, $limpezaBordas, $criterio);
            }

            return;
        }

        static::colocarBlocoNoRetangulo($chapas, $melhor, $bloco, $kerf, $limpezaBordas);
    }

    /**
     * "Sobra" usada pra ESCOLHER o melhor retângulo livre pra um
     * bloco/peça — ver docblock da classe, "Revisão 2026-09-14
     * (rodada 7)": 'area' é o Best Area Fit de sempre (menor sobra de
     * área); 'lado_curto' é o Best Short Side Fit (menor sobra do LADO
     * MAIS CURTO que resta) — técnica clássica de bin-packing 2D
     * (Jylänki) que tende a deixar sobras mais "quadradas"/menos
     * fatiadas que o Best Area Fit sozinho. `encaixarGrupo()` roda o
     * encaixe completo com os dois critérios e fica com o melhor
     * resultado.
     *
     * @param  array{w: float, h: float}  $retangulo
     * @param  array{largura: float, comprimento: float}  $orientacao
     */
    private static function calcularSobra(array $retangulo, array $orientacao, string $criterio): float
    {
        if ($criterio === 'lado_curto') {
            return min($retangulo['w'] - $orientacao['largura'], $retangulo['h'] - $orientacao['comprimento']);
        }

        return ($retangulo['w'] * $retangulo['h']) - ($orientacao['largura'] * $orientacao['comprimento']);
    }

    /**
     * Subdivide o retângulo livre ESCOLHIDO em até 2 filhos, depois de
     * encaixar a peça (ou bloco) no seu canto superior-esquerdo — ver
     * docblock da classe, "Revisão 2026-09-13 (rodada 4)": a peça
     * deixa uma folga à DIREITA (`$retangulo['w'] -
     * $orientacao['largura'] - $kerf`) e uma folga EMBAIXO
     * (`$retangulo['h'] - $orientacao['comprimento'] - $kerf`) — a
     * pergunta é qual das duas vira um retângulo CHEIO (aproveita a
     * dimensão toda do retângulo original, sobra melhor pra peças
     * grandes futuras) e qual vira um retângulo ESTREITO (limitado à
     * dimensão da própria peça). Escolhe sempre a folga MAIOR pra ser
     * a cheia — é o padrão observado no PDF real do Promob Cut Pro
     * (mesmo em modo Serra): o 1º corte de uma chapa pode ser tanto
     * vertical quanto horizontal, dependendo de qual dimensão sobra
     * mais naquele retângulo. Tolerância de 1mm em cada filho evita
     * retângulos residuais inúteis (frestas menores que qualquer
     * serra/fresa).
     *
     * @param  array{x: float, y: float, w: float, h: float}  $retangulo
     * @param  array{largura: float, comprimento: float, rotacionado: bool}  $orientacao
     * @return array<int, array{x: float, y: float, w: float, h: float}>
     */
    private static function dividirRetangulo(array $retangulo, array $orientacao, float $kerf): array
    {
        $folgaDireita = $retangulo['w'] - $orientacao['largura'] - $kerf;
        $folgaBaixo = $retangulo['h'] - $orientacao['comprimento'] - $kerf;

        $filhos = [];

        if ($folgaDireita >= $folgaBaixo) {
            // Corte VERTICAL primeiro: a coluna à direita aproveita a
            // ALTURA CHEIA do retângulo original; a faixa debaixo da
            // peça fica limitada à largura da própria peça.
            if ($folgaDireita > 1.0) {
                $filhos[] = [
                    'x' => $retangulo['x'] + $orientacao['largura'] + $kerf,
                    'y' => $retangulo['y'],
                    'w' => $folgaDireita,
                    'h' => $retangulo['h'],
                ];
            }

            if ($folgaBaixo > 1.0) {
                $filhos[] = [
                    'x' => $retangulo['x'],
                    'y' => $retangulo['y'] + $orientacao['comprimento'] + $kerf,
                    'w' => $orientacao['largura'],
                    'h' => $folgaBaixo,
                ];
            }
        } else {
            // Corte HORIZONTAL primeiro: a faixa debaixo aproveita a
            // LARGURA CHEIA do retângulo original; a coluna à direita
            // da peça fica limitada à altura da própria peça.
            if ($folgaBaixo > 1.0) {
                $filhos[] = [
                    'x' => $retangulo['x'],
                    'y' => $retangulo['y'] + $orientacao['comprimento'] + $kerf,
                    'w' => $retangulo['w'],
                    'h' => $folgaBaixo,
                ];
            }

            if ($folgaDireita > 1.0) {
                $filhos[] = [
                    'x' => $retangulo['x'] + $orientacao['largura'] + $kerf,
                    'y' => $retangulo['y'],
                    'w' => $folgaDireita,
                    'h' => $orientacao['comprimento'],
                ];
            }
        }

        return $filhos;
    }

    /**
     * Fecha o resultado por chapa (aproveitamento/áreas/contagens).
     *
     * @param  array<int, array{pecas: array<int, array{largura_corte: float, comprimento_corte: float}>, cortes?: int}>  $chapas
     * @return array<int, array{numero: int, aproveitamento: float, area_util_mm2: float, area_ocupada_mm2: float, quantidade_cortes: int, pecas: array<int, mixed>}>
     */
    private static function montarResultadoChapas(array $chapas, float $usavelLargura, float $usavelComprimento): array
    {
        $resultado = [];
        $areaUtil = $usavelLargura * $usavelComprimento;

        foreach ($chapas as $indice => $chapa) {
            $areaOcupada = array_sum(array_map(fn (array $p): float => $p['largura_corte'] * $p['comprimento_corte'], $chapa['pecas']));

            $resultado[] = [
                'numero'            => $indice + 1,
                'aproveitamento'    => $areaUtil > 0 ? round(($areaOcupada / $areaUtil) * 100, 1) : 0.0,
                'area_util_mm2'     => $areaUtil,
                'area_ocupada_mm2'  => $areaOcupada,
                // Contagem real de cortes de guilhotina — ver docblock
                // da classe, "Revisão 2026-09-14 (rodada 6)".
                'quantidade_cortes' => $chapa['cortes'] ?? 0,
                'pecas'             => $chapa['pecas'],
            ];
        }

        return $resultado;
    }
}
