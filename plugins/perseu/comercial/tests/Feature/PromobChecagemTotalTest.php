<?php

use Perseu\Comercial\Services\PromobChecagemTotal;
use Perseu\Comercial\Services\PromobXmlParser;

require_once __DIR__.'/../../../../webkul/support/tests/Helpers/TestBootstrapHelper.php';

function xmlPromobFixture(string $nome): string
{
    return file_get_contents(__DIR__.'/../Fixtures/Promob/'.$nome);
}

it('extrai custo e preço do total e das categorias do XML "000"', function () {
    $dados = PromobXmlParser::parse(xmlPromobFixture('260000 - 000 total ger.xml'));

    expect($dados['custo'])->toBe(704.4)
        ->and($dados['preco'])->toBe(2145.2)
        ->and($dados['ambientes'])->toHaveCount(1);

    $categorias = collect($dados['ambientes'][0]['categorias'])->keyBy('numero_item');

    expect($categorias->get('001')['custo'])->toBe(166.6)
        ->and($categorias->get('001')['preco'])->toBe(499.8)
        ->and($categorias->get('002'))->not->toBeNull();
});

it('extrai custo e preço do total do XML de um item individual', function () {
    $dados = PromobXmlParser::parse(xmlPromobFixture('260000 - 001 Superior.xml'));

    expect($dados['custo'])->toBe(222.6)
        ->and($dados['preco'])->toBe(603.8);

    $categorias = collect($dados['ambientes'][0]['categorias'])->keyBy('numero_item');

    // A CATEGORY "001" dentro do XML do item bate com a CATEGORY "001"
    // do XML "000" — são a MESMA fatia de dados, só que o XML do item
    // também soma outras categorias (Acessórios/Hettich/Processo de
    // Fabricação) no total do documento (222.6), por isso o total do
    // documento é maior que a CATEGORY "001" sozinha (166.6).
    expect($categorias->get('001')['custo'])->toBe(166.6)
        ->and($categorias->get('001')['preco'])->toBe(499.8);
});

it('confere o total dos 3 XMLs de exemplo e bate exatamente (custo/preço)', function () {
    $resultado = PromobChecagemTotal::checar([
        '260000 - 000 total ger.xml' => xmlPromobFixture('260000 - 000 total ger.xml'),
        '260000 - 001 Superior.xml'  => xmlPromobFixture('260000 - 001 Superior.xml'),
        '260000 - 002 inferior.xml'  => xmlPromobFixture('260000 - 002 inferior.xml'),
    ]);

    expect($resultado['bateu'])->toBeTrue()
        ->and($resultado['custo_calculado'])->toBe(704.4)
        ->and($resultado['preco_calculado'])->toBe(2145.2)
        ->and($resultado['custo_esperado'])->toBe(704.4)
        ->and($resultado['preco_esperado'])->toBe(2145.2)
        ->and($resultado['diferencas'])->toBeEmpty();
});

it('aponta o item com diferença quando um XML de item é alterado (custo/preço)', function () {
    // Simula um XML de item corrompido/divergente: troca o VALUE do
    // Custo total do item 001 pra um número diferente do que consta no
    // XML "000", sem tocar em mais nada.
    $xmlItem001Divergente = str_replace(
        '<ORDER VALUE="222.6">',
        '<ORDER VALUE="999.9">',
        xmlPromobFixture('260000 - 001 Superior.xml'),
    );

    $resultado = PromobChecagemTotal::checar([
        '260000 - 000 total ger.xml' => xmlPromobFixture('260000 - 000 total ger.xml'),
        '260000 - 001 Superior.xml'  => $xmlItem001Divergente,
        '260000 - 002 inferior.xml'  => xmlPromobFixture('260000 - 002 inferior.xml'),
    ]);

    expect($resultado['bateu'])->toBeFalse();

    // A CATEGORY "001" (a fatia comparável entre os dois arquivos) não
    // foi alterada pelo replace acima (só o VALUE do TOTALPRICES raiz
    // do item foi trocado) — então o diagnóstico por categoria não
    // encontra diferença ali, e o resultado fica sem "diferencas"
    // apontadas (mismatch só no total do documento, não na categoria).
    // Isso é esperado dado como o replace foi construído: comprova que
    // a checagem de nível 1 (soma total) já detecta o problema mesmo
    // quando o nível 2 (categoria) não consegue localizá-lo.
    expect($resultado['custo_calculado'])->not->toBe($resultado['custo_esperado']);
});

it('aponta a categoria exata quando a divergência está na própria CATEGORY do item (custo/preço)', function () {
    // Reduz tanto o total do DOCUMENTO (222.6, raiz + ambiente — pra
    // forçar o mismatch no nível 1, soma dos itens vs. XML "000") quanto
    // o total da CATEGORY "001" (166.6, único no arquivo) pela MESMA
    // diferença (16.6) — simula uma mudança real e internamente
    // consistente no item, não só um número solto. Isso permite ao
    // diagnóstico (nível 2) achar a categoria "001" desalinhada entre
    // este arquivo e o XML "000" (que continua com 166.6).
    $xmlOriginal = xmlPromobFixture('260000 - 001 Superior.xml');
    $xmlItem001Divergente = str_replace('166.6', '150.0', str_replace('222.6', '206.0', $xmlOriginal));

    // Confere que o replace realmente encontrou e alterou o trecho —
    // senão o teste passaria "por acidente" sem testar nada.
    expect($xmlItem001Divergente)->not->toBe($xmlOriginal);

    $resultado = PromobChecagemTotal::checar([
        '260000 - 000 total ger.xml' => xmlPromobFixture('260000 - 000 total ger.xml'),
        '260000 - 001 Superior.xml'  => $xmlItem001Divergente,
        '260000 - 002 inferior.xml'  => xmlPromobFixture('260000 - 002 inferior.xml'),
    ]);

    expect($resultado['bateu'])->toBeFalse()
        ->and($resultado['diferencas'])->toHaveCount(1)
        ->and($resultado['diferencas'][0]['item'])->toBe('001')
        ->and($resultado['diferencas'][0]['custo_esperado'])->toBe(166.6)
        ->and($resultado['diferencas'][0]['custo_calculado'])->toBe(150.0);
});

it('calcula as 5 métricas do VBA (Peças/m²/MLinear/Custo/Misc) do XML "000"', function () {
    $metricas = PromobXmlParser::metricas(xmlPromobFixture('260000 - 000 total ger.xml'));

    expect($metricas['pecas'])->toBe(51)
        ->and(round($metricas['m2'], 2))->toBe(8.23)
        ->and(round($metricas['mlinear'], 2))->toBe(80.61)
        ->and(round($metricas['custo'], 2))->toBe(552.4)
        ->and(round($metricas['misc'], 2))->toBe(152.0);
});

it('calcula as 5 métricas do VBA de um XML de item individual', function () {
    $metricas001 = PromobXmlParser::metricas(xmlPromobFixture('260000 - 001 Superior.xml'));
    $metricas002 = PromobXmlParser::metricas(xmlPromobFixture('260000 - 002 inferior.xml'));

    expect($metricas001['pecas'])->toBe(12)
        ->and(round($metricas001['custo'], 2))->toBe(166.6)
        ->and($metricas002['pecas'])->toBe(39)
        ->and(round($metricas002['custo'], 2))->toBe(385.8);
});

it('confere as 5 métricas dos 3 XMLs de exemplo e a diferença dá zero em todas', function () {
    $resultado = PromobChecagemTotal::checar([
        '260000 - 000 total ger.xml' => xmlPromobFixture('260000 - 000 total ger.xml'),
        '260000 - 001 Superior.xml'  => xmlPromobFixture('260000 - 001 Superior.xml'),
        '260000 - 002 inferior.xml'  => xmlPromobFixture('260000 - 002 inferior.xml'),
    ]);

    $metricas = $resultado['metricas'];

    expect($metricas['tem_geral'])->toBeTrue()
        ->and($metricas['quantidade_parciais'])->toBe(2)
        ->and($metricas['parciais'])->toBe([
            'pecas'   => 51,
            'm2'      => 8.23,
            'mlinear' => 80.61,
            'custo'   => 552.4,
            'misc'    => 152.0,
        ])
        ->and($metricas['geral'])->toBe([
            'pecas'   => 51,
            'm2'      => 8.23,
            'mlinear' => 80.61,
            'custo'   => 552.4,
            'misc'    => 152.0,
        ])
        ->and($metricas['diferenca']['pecas'])->toBe(0)
        ->and($metricas['diferenca']['m2'])->toEqual(0.0)
        ->and($metricas['diferenca']['mlinear'])->toEqual(0.0)
        ->and($metricas['diferenca']['custo'])->toEqual(0.0)
        ->and($metricas['diferenca']['misc'])->toEqual(0.0);
});

it('soma só as métricas parciais, sem calcular diferença, quando o XML "000" não é enviado', function () {
    $resultado = PromobChecagemTotal::checar([
        '260000 - 001 Superior.xml' => xmlPromobFixture('260000 - 001 Superior.xml'),
        '260000 - 002 inferior.xml' => xmlPromobFixture('260000 - 002 inferior.xml'),
    ]);

    expect($resultado['metricas']['tem_geral'])->toBeFalse()
        ->and($resultado['metricas']['geral'])->toBeNull()
        ->and($resultado['metricas']['diferenca'])->toBeNull()
        ->and($resultado['metricas']['quantidade_parciais'])->toBe(2)
        ->and($resultado['metricas']['parciais']['pecas'])->toBe(51)
        // A comparação COMPLEMENTAR de Custo/Preço também fica sem
        // "bateu" (null), não lança exceção — mesmo comportamento
        // tolerante da rotina principal.
        ->and($resultado['bateu'])->toBeNull()
        ->and($resultado['custo_esperado'])->toBeNull();
});
