<?php

namespace Perseu\Comercial\Services;

use Illuminate\Support\Facades\Storage;
use Perseu\Comercial\Models\Documento;
use Perseu\Comercial\Models\Projeto;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Webkul\Support\Models\Company;

/**
 * Geração de documentos a partir de um template Excel com marcadores de
 * texto `%Campo%` (ver CLAUDE.md, "Documentos — templates de Excel com
 * marcadores de texto"). Chamado pelo botão "Documentos" no form de
 * Projeto (`EditProjeto::getFormActions()`).
 *
 * Varre TODAS as abas do template em busca de 3 tipos de marcador:
 *
 * 1. Escalares (`%Obra_Desc%`, `%Cliente_Nome%` etc.) — substituição
 *    simples célula a célula, via `substituirEscalares()`.
 * 2. Linha de Itens (`%Itens_Num%`, `%Itens_Desc%` etc. numa mesma
 *    linha) — a linha inteira é tratada como "molde": duplicada uma
 *    vez pra cada Item do Projeto, preservando o estilo original, via
 *    `localizarLinhaItens()` + `expandirItens()`.
 * 3. Bloco de Condições Financeiras (`%Condicoes_Financeiras%`, uma
 *    célula só) — expande pro texto multi-linha com os mesmos 5
 *    valores já calculados na Section "Condições Financeiras" do form
 *    de Projeto (ver `CondicoesFinanceirasCalculator`), via
 *    `preencherCondicoesFinanceiras()`.
 *
 * NENHUM desses 3 mecanismos depende de qual aba está sendo
 * processada — a varredura é igual em todas as abas do arquivo, então
 * novas abas com os mesmos marcadores funcionam automaticamente sem
 * precisar mexer neste Service (só o template precisa ganhar os
 * marcadores).
 *
 * O arquivo GERADO é sempre salvo como `.xlsx` (não preserva macro
 * nem o formato binário `.xls` antigo), independente da extensão do
 * template de origem (`.xls`/`.xlsx`/`.xlsm`) — decisão deliberada:
 * o PhpSpreadsheet não tem suporte confiável pra reescrever um
 * `.xlsm` preservando as macros VBA, e como o arquivo gerado é só
 * dados preenchidos (não precisa rodar macro nenhuma), `.xlsx` puro é
 * suficiente e mais seguro. Fica registrado aqui porque o usuário
 * pediu inicialmente o nome de arquivo terminando em `.xls` — o nome
 * final usa `.xlsx`.
 *
 * O arquivo gerado é sempre TEMPORÁRIO — fica em
 * `storage/app/temp/documentos-gerados/`, é oferecido pra download e
 * apagado logo em seguida (`deleteFileAfterSend`, ver EditProjeto).
 * Não fica guardado em lugar nenhum do Perseu depois disso (decisão
 * confirmada com o usuário).
 */
class DocumentoTemplateService
{
    /**
     * Marcador `%Itens_<sufixo>%` -> atributo do Model `ItemProjeto`.
     */
    private const MAPA_CAMPOS_ITEM = [
        'Num'      => 'numero_item',
        'Desc'     => 'descricao',
        'Qtde'     => 'quantidade',
        'ValUnit'  => 'valor_unitario',
        'ValTotal' => 'valor_total',
    ];

    /**
     * Sufixos de campo de Item que são valores monetários (formatados
     * como "R$ 1.234,56" na célula, diferente de Num/Desc/Qtde que
     * vão sem formatação especial).
     */
    private const CAMPOS_ITEM_MONETARIOS = ['ValUnit', 'ValTotal'];

    /**
     * Gera o documento e retorna o caminho do arquivo temporário + o
     * nome de arquivo sugerido pro download.
     *
     * @return array{caminho: string, nome_arquivo: string}
     */
    public function gerar(Projeto $projeto, Documento $documento): array
    {
        $caminhoOrigem = Storage::disk('local')->path($documento->arquivo);

        $spreadsheet = IOFactory::load($caminhoOrigem);
        $valoresEscalares = $this->construirMarcadoresEscalares($projeto);
        $itens = $projeto->itens()->orderBy('numero_item')->get();

        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $linhaItens = $this->localizarLinhaItens($sheet);

            if ($linhaItens !== null && $itens->isNotEmpty()) {
                $this->expandirItens($sheet, $linhaItens, $itens);
            }

            $this->preencherCondicoesFinanceiras($sheet, $projeto);
            $this->substituirEscalares($sheet, $valoresEscalares);
        }

        // O template de origem pode ser .xlsm (com macro VBA), mas o
        // arquivo GERADO é sempre .xlsx puro (ver doc da classe) -- se
        // não desligar as macros aqui, o PhpSpreadsheet grava o
        // xl/vbaProject.bin e marca o Content Type do pacote como
        // "macroEnabled" mesmo escrevendo com o Writer 'Xlsx', e o
        // Excel recusa abrir o arquivo por causa da incompatibilidade
        // entre esse Content Type e a extensão .xlsx ("o Excel não
        // pode abrir o arquivo... verifique se a extensão corresponde
        // ao formato"). Bug encontrado em 2026-09-11.
        $spreadsheet->setHasMacros(false);

        $nomeArquivo = $this->montarNomeArquivo($projeto);
        $diretorioTemp = storage_path('app/temp/documentos-gerados');

        if (! is_dir($diretorioTemp)) {
            mkdir($diretorioTemp, 0755, true);
        }

        $caminhoTemporario = $diretorioTemp . '/' . uniqid('documento_', true) . '.xlsx';

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($caminhoTemporario);

        return [
            'caminho'      => $caminhoTemporario,
            'nome_arquivo' => $nomeArquivo,
        ];
    }

    /**
     * Monta o mapa `NomeDoMarcador => valor` pra todos os marcadores
     * ESCALARES (cabeçalho Obra/Projeto, Cliente, Contratada) — não
     * inclui `Itens_*` (tratados à parte, linha a linha) nem
     * `Condicoes_Financeiras` (tratado à parte, bloco multi-linha).
     *
     * Cliente: Projeto tem `pessoaFisica()` OU `pessoaJuridica()`,
     * nunca os dois ao mesmo tempo (confirmado com o usuário) — o
     * endereço do Cliente vem de `Projeto::endereco()` (Model
     * `Endereco` próprio, não amarrado à Pessoa), e o nome do contato
     * vem de `Projeto::contatoPessoaFisica()`.
     *
     * Contratada: SEMPRE a Company raiz (`parent_id` nulo) — "por
     * enquanto usaremos a razão social da empresa principal", decisão
     * explícita do usuário (2026-09). Se um dia precisar diferenciar
     * matriz/filial por Projeto, este é o ponto a revisitar.
     */
    protected function construirMarcadoresEscalares(Projeto $projeto): array
    {
        $pessoaJuridica = $projeto->pessoaJuridica;
        $pessoaFisica = $projeto->pessoaFisica;
        $endereco = $projeto->endereco;
        $contato = $projeto->contatoPessoaFisica;

        $company = Company::query()
            ->whereNull('parent_id')
            ->orderBy('sort')
            ->first();

        return [
            'Obra_Desc'       => (string) $projeto->descricao,
            'Projeto_Num'     => (string) $projeto->numero_projeto,
            'Projeto_Revisao' => (string) $projeto->revisao,

            'Cliente_Nome'        => $pessoaJuridica ? (string) $pessoaJuridica->razao_social : (string) $pessoaFisica?->nome,
            'Cliente_Documento'   => $pessoaJuridica ? (string) $pessoaJuridica->cnpj : (string) $pessoaFisica?->cpf,
            'Cliente_Email'       => $pessoaJuridica ? (string) $pessoaJuridica->email : (string) $pessoaFisica?->email,
            'Cliente_Contato'     => (string) $contato?->nome,
            'Contato_Email'       => (string) $contato?->email,
            'Cliente_Endereco'    => (string) $endereco?->logradouro,
            'Cliente_Numero'      => (string) $endereco?->numero,
            'Cliente_Complemento' => (string) $endereco?->complemento,
            'Cliente_CEP'         => (string) $endereco?->cep,
            'Cliente_Bairro'      => (string) $endereco?->bairro,
            'Cliente_Cidade'      => (string) $endereco?->municipio,
            'Cliente_Estado'      => (string) $endereco?->uf,

            'Contratada_Nome'        => (string) $company?->name,
            'Contratada_CNPJ'        => (string) $company?->tax_id,
            'Contratada_Email'       => (string) $company?->email,
            'Contratada_Telefone'    => (string) $company?->phone,
            'Contratada_Endereco'    => (string) $company?->street1,
            'Contratada_Numero'      => (string) $company?->numero,
            'Contratada_Complemento' => (string) $company?->street2,
            'Contratada_CEP'         => (string) $company?->zip,
            'Contratada_Bairro'      => (string) $company?->bairro,
            'Contratada_Cidade'      => (string) $company?->city,
            'Contratada_Estado'      => (string) $company?->state?->code,
        ];
    }

    /**
     * Substitui, célula a célula, qualquer marcador `%NomeDoCampo%`
     * presente no mapa `$valores` — usa regex (não simples
     * `str_replace`) pra não confundir com "%" usado como porcentagem
     * solta em texto normal (ex.: "1% de juros moratórios", visto nas
     * cláusulas da aba `00`). Marcadores fora do mapa (ex.:
     * `Itens_*`/`Condicoes_Financeiras`, já tratados antes desta
     * chamada) ficam como estão.
     */
    protected function substituirEscalares(Worksheet $sheet, array $valores): void
    {
        foreach ($sheet->getRowIterator() as $row) {
            foreach ($row->getCellIterator() as $cell) {
                $valorCelula = $cell->getValue();

                if (! is_string($valorCelula) || ! str_contains($valorCelula, '%')) {
                    continue;
                }

                $novoValor = preg_replace_callback(
                    '/%([A-Za-z0-9_]+)%/',
                    fn (array $m) => array_key_exists($m[1], $valores) ? $valores[$m[1]] : $m[0],
                    $valorCelula,
                );

                if ($novoValor !== $valorCelula) {
                    $cell->setValue($novoValor);
                }
            }
        }
    }

    /**
     * Procura a linha-molde de Itens: a PRIMEIRA linha da aba que
     * tenha pelo menos uma célula com um marcador `%Itens_<algo>%`
     * (sozinho na célula, sem mais nada em volta). Retorna a linha e
     * o mapa `Coluna => sufixo do campo` (ex.: `['A' => 'Num', 'B' =>
     * 'Desc', ...]`), ou `null` se a aba não tiver linha de Itens.
     */
    protected function localizarLinhaItens(Worksheet $sheet): ?array
    {
        foreach ($sheet->getRowIterator() as $row) {
            $colunas = [];

            foreach ($row->getCellIterator() as $cell) {
                $valorCelula = $cell->getValue();

                if (is_string($valorCelula) && preg_match('/^%Itens_([A-Za-z0-9]+)%$/', trim($valorCelula), $m)) {
                    $colunas[$cell->getColumn()] = $m[1];
                }
            }

            if ($colunas !== []) {
                return [
                    'linha'   => $row->getRowIndex(),
                    'colunas' => $colunas,
                ];
            }
        }

        return null;
    }

    /**
     * Duplica a linha-molde uma vez pra cada Item (preservando o
     * estilo original de cada célula da linha-molde) e preenche cada
     * linha com os dados do Item correspondente. Com só 1 Item, não
     * duplica nada — só preenche a própria linha-molde.
     *
     * @param array{linha: int, colunas: array<string, string>} $linhaItens
     * @param \Illuminate\Support\Collection<int, \Perseu\Comercial\Models\ItemProjeto> $itens
     */
    protected function expandirItens(Worksheet $sheet, array $linhaItens, $itens): void
    {
        $linhaBase = $linhaItens['linha'];
        $colunas = $linhaItens['colunas'];
        $qtdeItens = $itens->count();

        if ($qtdeItens > 1) {
            $sheet->insertNewRowBefore($linhaBase + 1, $qtdeItens - 1);

            $colunaInicial = 'A';
            // Coluna mais alta DAQUELA LINHA (a linha-molde), não
            // `$sheet->getHighestColumn()` sem argumento (coluna mais
            // alta da planilha INTEIRA, que em algumas abas ia bem mais
            // longe por causa de formatação de coluna sem relação com a
            // tabela de Itens). Bug encontrado em 2026-09-11.
            $colunaFinal = $sheet->getHighestColumn($linhaBase);
            $colunaFinalIndice = Coordinate::columnIndexFromString($colunaFinal);

            // duplicateStyle() só copia formatação (fonte, borda, cor,
            // formato de número) -- NÃO recria mesclagem de célula, que
            // é uma propriedade estrutural da planilha, não da célula.
            // Sem isso, linhas inseridas (17+) ficam com cada coluna
            // separada em vez de replicar a mescla da linha-molde (ex.:
            // "W16:Z16", "AA16:AD16"), e o valor aparece cortado/"###".
            // Bug encontrado em 2026-09-11.
            $mesclagensLinhaBase = [];

            foreach ($sheet->getMergeCells() as $intervaloMesclado) {
                [$inicio, $fim] = explode(':', $intervaloMesclado);
                [$colInicio, $linhaInicio] = Coordinate::coordinateFromString($inicio);
                [$colFim, $linhaFim] = Coordinate::coordinateFromString($fim);

                if ((int) $linhaInicio === $linhaBase && (int) $linhaFim === $linhaBase) {
                    $mesclagensLinhaBase[] = [$colInicio, $colFim];
                }
            }

            for ($i = 1; $i < $qtdeItens; $i++) {
                $linhaDestino = $linhaBase + $i;

                // duplicateStyle() com um intervalo de VÁRIAS colunas
                // como origem (ex.: "A18:AD18") NÃO preserva o estilo de
                // cada coluna -- ele aplica o estilo de uma única célula
                // (a primeira do intervalo) em TODO o destino, tomando o
                // lugar da formatação própria de cada coluna. Por isso
                // uma coluna com estilo "de destaque" (cinza + borda, ex.
                // a coluna do número do item) acabava vazando pra linha
                // inteira nas linhas recém-inseridas. Duplicar
                // coluna-a-coluna (célula única -> célula única) resolve.
                // Bug encontrado em 2026-09-11.
                for ($col = 1; $col <= $colunaFinalIndice; $col++) {
                    $colunaLetra = Coordinate::stringFromColumnIndex($col);
                    $sheet->duplicateStyle(
                        $sheet->getStyle("{$colunaLetra}{$linhaBase}"),
                        "{$colunaLetra}{$linhaDestino}",
                    );
                }

                foreach ($mesclagensLinhaBase as [$colInicio, $colFim]) {
                    $sheet->mergeCells("{$colInicio}{$linhaDestino}:{$colFim}{$linhaDestino}");
                }
            }
        }

        foreach ($itens->values() as $index => $item) {
            $linha = $linhaBase + $index;

            foreach ($colunas as $coluna => $sufixo) {
                $atributo = self::MAPA_CAMPOS_ITEM[$sufixo] ?? null;

                if ($atributo === null) {
                    continue;
                }

                $valor = $item->{$atributo};

                // Descrição vem do RichEditor do Filament, que grava
                // HTML ("<p>item avulso</p>") -- na célula do Excel
                // queremos só o texto. Bug encontrado em 2026-09-11.
                if ($sufixo === 'Desc' && is_string($valor)) {
                    $valor = trim(html_entity_decode(strip_tags($valor)));
                }

                $celula = "{$coluna}{$linha}";

                // Campo monetário (ValUnit/ValTotal): grava como NÚMERO
                // real com formatação de moeda na célula, NUNCA como
                // texto "R$ 1.234,56" -- o template (aba "00") tem uma
                // fórmula de total somando esta coluna (ex.:
                // "=SUM(AA16:AA21)"), e SUM() de texto sempre retorna 0.
                // Bug encontrado em 2026-09-11 num arquivo real de teste
                // do usuário (ver CLAUDE.md, "Documentos").
                if (in_array($sufixo, self::CAMPOS_ITEM_MONETARIOS, true)) {
                    $sheet->setCellValue($celula, (float) $valor);
                    $sheet->getStyle($celula)->getNumberFormat()->setFormatCode(
                        '_-[$R$-416]\\ * #,##0.00_-;\\-[$R$-416]\\ * #,##0.00_-;_-[$R$-416]\\ * "-"??_-;_-@_-'
                    );

                    continue;
                }

                $sheet->setCellValue($celula, $valor);
            }
        }

        if ($qtdeItens > 1) {
            $this->ajustarFormulasDeTotal($sheet, $colunas, $linhaBase, $qtdeItens);
        }
    }

    /**
     * Depois de expandir a linha de Itens pra N linhas, qualquer
     * fórmula `=SUM(...)` que já existia logo abaixo do bloco
     * (normalmente uma linha de "total", ex.: aba "00") continua
     * somando só a linha-molde original -- ajusta pra cobrir
     * exatamente as N linhas de Itens recém-inseridas. Só mexe em
     * fórmulas cujo range começa numa das colunas de Itens (ex.:
     * "AA"), pra não arriscar tocar em fórmula não relacionada que por
     * acaso esteja na mesma linha. Bug encontrado em 2026-09-11 (a
     * fórmula original do template somava só a linha-molde, ex.:
     * "=SUM(AA16:AD16)", nunca a coluna inteira de itens -- ver
     * CLAUDE.md, "Documentos").
     */
    protected function ajustarFormulasDeTotal(Worksheet $sheet, array $colunas, int $linhaBase, int $qtdeItens): void
    {
        $linhaTotal = $linhaBase + $qtdeItens;

        // Abas sem linha de total (ex.: "V", vistoria sem valores) nem
        // sempre têm conteúdo até $linhaTotal -- `getRowIterator()`
        // lança exceção ("Start row X is beyond highest row Y") se a
        // linha pedida não existir na planilha. Usar `getCellByColumnAndRow(...,
        // false)` (não cria a célula se não existir) evita o crash e
        // simplesmente não acha fórmula nenhuma pra ajustar, que é o
        // comportamento certo quando não há total. Bug encontrado em
        // 2026-09-11.
        if ($linhaTotal > $sheet->getHighestRow()) {
            return;
        }

        $ultimaLinhaItem = $linhaBase + $qtdeItens - 1;
        $colunasItens = array_keys($colunas);
        $colunaMaisAlta = Coordinate::columnIndexFromString($sheet->getHighestColumn($linhaTotal));

        for ($col = 1; $col <= $colunaMaisAlta; $col++) {
            $cell = $sheet->getCellByColumnAndRow($col, $linhaTotal, false);

            if ($cell === null) {
                continue;
            }

            $formula = $cell->getValue();

            if (! is_string($formula) || ! str_starts_with($formula, '=SUM(')) {
                continue;
            }

            if (! preg_match('/^=SUM\(([A-Z]+)\d+:[A-Z]+\d+\)$/', $formula, $m)) {
                continue;
            }

            $colunaFormula = $m[1];

            if (! in_array($colunaFormula, $colunasItens, true)) {
                continue;
            }

            $cell->setValue("=SUM({$colunaFormula}{$linhaBase}:{$colunaFormula}{$ultimaLinhaItem})");
        }
    }

    /**
     * Procura a célula com o marcador `%Condicoes_Financeiras%`
     * (sozinho na célula) e substitui pelo bloco de texto multi-linha
     * com os mesmos 5 valores já mostrados na Section "Condições
     * Financeiras" do form de Projeto (via
     * `CondicoesFinanceirasCalculator`), habilitando quebra de linha
     * automática na célula (`WrapText`).
     */
    protected function preencherCondicoesFinanceiras(Worksheet $sheet, Projeto $projeto): void
    {
        foreach ($sheet->getRowIterator() as $row) {
            foreach ($row->getCellIterator() as $cell) {
                $valorCelula = $cell->getValue();

                if (! is_string($valorCelula) || trim($valorCelula) !== '%Condicoes_Financeiras%') {
                    continue;
                }

                $dados = CondicoesFinanceirasCalculator::calcular($projeto);
                $cell->setValue($this->formatarBlocoCondicoes($dados));
                $sheet->getStyle($cell->getCoordinate())->getAlignment()->setWrapText(true);
            }
        }
    }

    protected function formatarBlocoCondicoes(array $dados): string
    {
        $linhas = [
            'Total do Projeto: R$ ' . number_format($dados['total_geral'], 2, ',', '.'),
            'Condição: ' . ($dados['condicao']?->descricao ?? 'Sem condição selecionada'),
            'Valor de Entrada: ' . (filled($dados['valor_entrada']) ? 'R$ ' . number_format($dados['valor_entrada'], 2, ',', '.') : '—'),
            'Quantidade de Parcelas: ' . ($dados['qtde_parcelas'] ?? '—'),
            'Valor da Parcela: ' . (filled($dados['valor_parcela']) ? 'R$ ' . number_format($dados['valor_parcela'], 2, ',', '.') : '—'),
        ];

        return implode("\n", $linhas);
    }

    /**
     * Nome de arquivo sugerido pro download: "Projeto_Num - Obra_Desc
     * - Projeto_Revisao.xlsx" (formato pedido pelo usuário — a
     * extensão real é sempre `.xlsx`, ver nota no topo da classe).
     * Caracteres inválidos em nome de arquivo Windows são trocados
     * por hífen; espaços e o separador " - " são preservados de
     * propósito.
     */
    protected function montarNomeArquivo(Projeto $projeto): string
    {
        $nome = sprintf(
            '%s - %s - %s',
            $projeto->numero_projeto,
            $projeto->descricao,
            $projeto->revisao,
        );

        $nome = preg_replace('/[\\\\\/:*?"<>|]+/', '-', $nome);

        return trim($nome) . '.xlsx';
    }
}
