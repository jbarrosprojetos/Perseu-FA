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
            'descricao-atalhos'  => 'Use atalhos de teclado para formatar o texto, se necessário: Ctrl+B (negrito), Ctrl+I (itálico), Ctrl+U (sublinhado).',
            'referencia-tooltip'      => 'Código do Produto de Linha Cadastrado',
            'porcentagem-tooltip'     => 'Porcentagem Acréscimo ou Desconto',
            'custo-unitario-tooltip'  => 'Valor de Custo Digitado ou Importado',
            'origens'  => [
                'item-avulso'       => 'Item Avulso',
                'item-linha'        => 'Item de Linha',
                'promob-plus'       => 'Promob Plus',
                'promob-start'      => 'Promob Start',
                'sketchup-hellomob' => 'Sketchup Hellomob',
                'sketchup-cutlist'  => 'Sketchup CutList',
                'cortcloud'         => 'CortCloud',
            ],
            'notification' => [
                'sem-selecao'              => 'Selecione uma origem antes de inserir.',
                'pendente-title'           => 'Inserção ainda não implementada',
                'pendente-body'            => "Ação de inserção para ':origem' ainda será implementada.",
                'confirmar-pendente-title' => 'Confirmação ainda não implementada',
                'confirmar-pendente-body'  => 'A ação de confirmar/salvar este item será implementada numa próxima etapa.',
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
