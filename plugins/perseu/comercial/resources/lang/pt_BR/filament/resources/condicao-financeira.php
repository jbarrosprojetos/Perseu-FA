<?php

return [
    'model-label' => 'Condição Financeira',

    'plural-model-label' => 'Condições Financeiras',

    'navigation' => [
        'title' => 'Condições Financeiras',
    ],

    'form' => [
        'descricao'                => 'Descrição da Condição',
        'created-at'               => 'Criado em',
        'created-at-pendente'      => 'Preenchido automaticamente ao salvar',
        'porcentagem-entrada'      => 'Porcentagem de Entrada',
        'qtde-parcelas'            => 'Qtde. Parcelas',
        'intervalo-dias'           => 'Intervalo em Dias',
        'unidade-dias'             => 'dias',
        'forma-pagamento'          => 'Forma de Pagamento',
        'taxa-mensal'              => 'Taxa Mensal',
        'unidade-ao-mes'           => '% a.m.',
        'taxa-mensal-ajuda'        => 'Taxa de juros ao mês, usada para calcular o fator de cada parcela pelo Sistema Price.',
    ],

    'formas-pagamento' => [
        'pix-transferencia' => 'Pix / Transferência',
        'boleto'            => 'Boleto',
        'cartao'            => 'Cartão',
    ],

    'table' => [
        'columns' => [
            'descricao'           => 'Descrição da Condição',
            'created-at'          => 'Criado em',
            'porcentagem-entrada' => 'Entrada',
            'qtde-parcelas'       => 'Parcelas',
            'intervalo-dias'      => 'Intervalo (dias)',
            'forma-pagamento'     => 'Forma de Pagamento',
            'taxa-mensal'         => 'Taxa Mensal',
        ],

        'filters' => [
            'trashed' => 'Na lixeira',
        ],

        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Condição Financeira editada',
                    'body'  => 'A condição financeira foi editada com sucesso.',
                ],
            ],
            'delete' => [
                'notification' => [
                    'title' => 'Condição Financeira excluída',
                    'body'  => 'A condição financeira foi excluída com sucesso.',
                ],
            ],
            'restore' => [
                'notification' => [
                    'title' => 'Condição Financeira restaurada',
                    'body'  => 'A condição financeira foi restaurada com sucesso.',
                ],
            ],
            'force-delete' => [
                'notification' => [
                    'title' => 'Condição Financeira excluída permanentemente',
                    'body'  => 'A condição financeira foi excluída permanentemente com sucesso.',
                ],
            ],
        ],
    ],

    'notifications' => [
        'vinculada' => [
            'title' => 'Não é possível alterar ou excluir',
            'body'  => 'Esta condição está vinculada a :count Projeto — desvincule-o antes de excluir ou editar esta condição financeira.|Esta condição está vinculada a :count Projetos — desvincule-os antes de excluir ou editar esta condição financeira.',
        ],
        'vinculada-em-massa' => [
            'body' => 'As condições ":descricoes" estão vinculadas a pelo menos um Projeto — desvincule-as antes de excluir ou editar.',
        ],
    ],
];
