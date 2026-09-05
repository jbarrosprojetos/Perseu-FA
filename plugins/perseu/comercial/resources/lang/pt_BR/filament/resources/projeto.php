<?php

return [
    'model-label' => 'Projeto',

    'plural-model-label' => 'Projetos',

    'navigation' => [
        'title' => 'Projetos',
    ],

    'form' => [
        'sections' => [
            'cabecalho' => [
                'title'       => 'Dados do Projeto',
                'description' => 'Informações administrativas do Projeto — cliente, endereço da obra e situação.',
            ],
            'itens' => [
                'title'       => 'Itens',
                'description' => 'Itens que compõem o Projeto, a partir de diferentes origens.',
            ],
        ],
        'itens' => [
            'origem'             => 'Origem do Item',
            'inserir'            => 'Inserir',
            'mobilizacao-frete'  => 'Mobilização e Frete',
            'confirmar'          => 'Confirmar',
            'editar'             => 'Editar',
            'excluir'            => 'Excluir',
            'descricao-atalhos'  => 'Use atalhos de teclado para formatar o texto, se necessário: Ctrl+B (negrito), Ctrl+I (itálico), Ctrl+U (sublinhado).',
            'referencia-tooltip'      => 'Código do Produto de Linha Cadastrado',
            'porcentagem-tooltip'     => 'Porcentagem Acréscimo ou Desconto',
            'custo-unitario-tooltip'  => 'Valor de Custo Digitado ou Importado',
            'excluir-confirmacao' => [
                'heading'     => 'Excluir item :numero?',
                'description' => 'Os itens seguintes deste Projeto serão renumerados para fechar o espaço na sequência. Esta ação não pode ser desfeita.',
            ],
            'validacao' => [
                'descricao-obrigatoria'      => 'Preencha a Descrição do item.',
                'quantidade-obrigatoria'     => 'Informe uma Quantidade maior que zero.',
                'custo-unitario-obrigatorio' => 'Informe um Custo Unitário maior que zero.',
            ],
            'origens'  => [
                'item-avulso' => 'Item Avulso',
                'item-linha'  => 'Item de Linha',
                'promob'      => 'Promob',
                'sketchup'    => 'SketchUp',
            ],
            'notification' => [
                'sem-selecao'              => 'Selecione uma origem antes de inserir.',
                'pendente-title'           => 'Inserção ainda não implementada',
                'pendente-body'            => "Ação de inserção para ':origem' ainda será implementada.",
                'projeto-nao-salvo-title'  => 'Salve o Projeto primeiro',
                'projeto-nao-salvo-body'   => 'É preciso salvar os dados do Projeto (Cabeçalho) antes de inserir itens.',
                'item-avulso-confirmado'   => 'Item salvo com sucesso.',
                'item-excluido'            => 'Item excluído com sucesso.',
            ],
            'promob' => [
                'modal' => [
                    'heading'                        => 'Checagem do Promob',
                    'description'                    => 'Envie o XML "000" (total do projeto) e os XMLs de cada item exportados pelo Promob para conferir se os totais batem.',
                    'upload-label'                   => 'Arquivos XML',
                    'upload-helper'                  => 'Selecione o XML "000" (total) e os XMLs de cada item, todos de uma vez.',
                    'processar'                      => 'Checar Total',
                    'criar-itens'                    => 'Criar Itens',
                    'confirmar-criacao-heading'      => 'Divergência de valores',
                    'confirmar-criacao-description'  => 'Arquivos com divergência de valores. Confirma a criação dos Itens?',
                ],
                'erros' => [
                    'projeto-nao-salvo' => 'É preciso que o Projeto já tenha um número (salvo pelo menos uma vez) antes de enviar XMLs do Promob.',
                ],
                'resultado' => [
                    'titulo'                => 'Comparação Total Geral',
                    'comparacao-cabecalho'  => 'Comparação: XML "000" menos soma dos XMLs parciais (:quantidade):',
                    'sem-geral'             => 'Nenhum XML "000" (Geral) enviado — mostrando só a soma dos XMLs parciais (:quantidade), sem diferença calculada.',
                    'metrica-pecas'         => 'Tot. Peças: :valor',
                    'metrica-m2'            => 'Tot. m²: :valor',
                    'metrica-mlinear'       => 'Tot. Metro Linear: :valor',
                    'metrica-custo'         => 'Tot. Custo: R$ :valor',
                    'metrica-misc'          => 'Tot. Misc: R$ :valor',
                    'custo_preco' => [
                        'titulo'           => 'Custo/Preço total (com margens):',
                        'bateu'            => 'Total conferido com sucesso.',
                        'nao-bateu'        => 'Encontrada diferença entre os XMLs de item e o total do XML "000".',
                        'totais'           => 'Custo somado: R$ :custo | Preço somado: R$ :preco',
                        'total-esperado'   => 'Total esperado (XML "000"): Custo R$ :custo | Preço R$ :preco',
                        'diferenca-item'   => 'Item :item — esperado: Custo R$ :custo_esperado / Preço R$ :preco_esperado — calculado: Custo R$ :custo_calculado / Preço R$ :preco_calculado',
                        'sem-diagnostico'  => 'Não foi possível localizar a categoria exata da diferença — confira se todos os XMLs de item foram enviados.',
                    ],
                ],
            ],
            'cabecalho-item-avulso' => [
                'item'            => 'Item',
                'referencia'      => 'Referência',
                'descricao'       => 'Descrição',
                'quantidade'      => 'Qtde.',
                'valor-unitario'  => 'Valor Unit.',
                'valor-total'     => 'Valor Total',
                'porcentagem'     => '%',
                'custo-unitario'  => 'Custo Unit.',
            ],
            'item-avulso-modal' => [
                'heading-criar'          => 'Novo Item Avulso',
                'heading-editar'         => 'Editar Item Avulso',
                'criar'                  => 'Criar',
                'salvar'                 => 'Salvar',
                'item-label'             => 'Item',
                'descricao-label'        => 'Descrição',
                'quantidade-label'       => 'Quantidade',
                'porcentagem-label'      => '% (Acréscimo ou Desconto)',
                'custo-unitario-label'   => 'Custo Unitário',
                'valor-unitario-label'   => 'Valor Unitário',
                'valor-total-label'      => 'Valor Total',
            ],
        ],
        'notas' => [
            'acao'            => 'Notas do Projeto',
            'nova-nota-label' => 'Nova Nota',
            'adicionar'       => 'Adicionar Nota',
            'editar'          => 'Editar',
            'excluir'         => 'Excluir',
            'salvar'          => 'Salvar',
            'texto-label'     => 'Nota',
            'autor-sistema'   => 'Sistema',
            'badge-sistema'   => 'Sistema',
            'modal' => [
                'heading' => 'Notas do Projeto',
            ],
            'modal-editar' => [
                'heading' => 'Editar nota :numero',
            ],
            'excluir-confirmacao' => [
                'heading'     => 'Excluir nota :numero?',
                'description' => 'Esta ação não pode ser desfeita.',
            ],
            'validacao' => [
                'texto-obrigatorio' => 'Escreva o texto da nota antes de salvar.',
            ],
            'notification' => [
                'projeto-nao-salvo-title' => 'Salve o Projeto primeiro',
                'projeto-nao-salvo-body'  => 'É preciso salvar os dados do Projeto (Cabeçalho) antes de adicionar notas.',
                'nota-adicionada'         => 'Nota adicionada com sucesso.',
                'nota-atualizada'         => 'Nota atualizada com sucesso.',
                'nota-excluida'           => 'Nota excluída com sucesso.',
                'sem-permissao'           => 'Você não tem permissão para editar ou excluir esta nota (prazo de 24 horas encerrado, nota de outro usuário, ou nota do sistema).',
            ],
        ],
        'descricao'      => 'Nome da Obra',
        'tipo-projeto'   => 'Tipo de Projeto',
        'situacoes'      => 'Situações',
        'tipo-contratante' => 'Contratante',
        'tipo-contratante-options' => [
            'pessoa-fisica'   => 'Pessoa Física',
            'pessoa-juridica' => 'Pessoa Jurídica',
        ],
        'pessoa-fisica'   => 'Cliente (Pessoa Física)',
        'pessoa-juridica' => 'Cliente (Pessoa Jurídica)',
        'contato'         => 'Contato',
        'contato-email'   => 'E-mail',
        'contato-telefone' => 'Telefone',
        'endereco'        => 'Endereço da Obra',
        'endereco-form'   => [
            'cep'         => 'CEP',
            'logradouro'  => 'Logradouro',
            'numero'      => 'Número',
            'complemento' => 'Complemento',
            'bairro'      => 'Bairro',
            'municipio'   => 'Município',
            'uf'          => 'UF',
        ],
        'endereco-sem-tag-obra' => 'Este cliente não tem nenhum endereço marcado com a tag Obra. Cadastre um endereço com essa tag no cadastro do cliente, ou use o "+" acima para criar um novo aqui.',
        'referencia-preco'        => 'Referência de Preços',
        'referencia-preco-aviso'  => 'Nenhuma referência de preços selecionada — necessária para calcular o valor de venda',
        'numero-projeto'          => 'Projeto',
        'numero-projeto-pendente' => 'Gerado automaticamente ao salvar',
        'revisao'                 => 'Revisão',
        'data-cadastro'           => 'Cadastrado em:',
        'data-cadastro-pendente'  => 'Preenchida automaticamente ao salvar',
    ],

    'table' => [
        'columns' => [
            'numero-projeto' => 'Número',
            'descricao'      => 'Nome da Obra',
            'tipo-projeto'   => 'Tipo',
            'contratante'    => 'Contratante',
            'situacoes'      => 'Situações',
            'data-cadastro'  => 'Data de Cadastro',
        ],

        'filters' => [
            'trashed' => 'Excluídos',
        ],

        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Projeto atualizado',
                    'body'  => 'O projeto foi atualizado com sucesso.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Projeto excluído',
                    'body'  => 'O projeto foi excluído com sucesso.',
                ],
            ],

            'restore' => [
                'notification' => [
                    'title' => 'Projeto restaurado',
                    'body'  => 'O projeto foi restaurado com sucesso.',
                ],
            ],

            'force-delete' => [
                'notification' => [
                    'title' => 'Projeto excluído definitivamente',
                    'body'  => 'O projeto foi excluído definitivamente.',
                ],
            ],
        ],
    ],
];
