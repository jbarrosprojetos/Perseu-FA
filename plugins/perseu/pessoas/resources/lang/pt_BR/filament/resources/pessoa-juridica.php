<?php

return [
    'model-label' => 'Pessoa Jurídica',

    'plural-model-label' => 'Pessoas Jurídicas',

    'navigation' => [
        'title' => 'Pessoas Jurídicas',
    ],

    'form' => [
        'categorias'          => 'Categorias',
        'razao-social'        => 'Razão Social',
        'nome-fantasia'       => 'Nome Fantasia',
        'cnpj'                => 'CNPJ',
        'telefone'            => 'Telefone',
        'email'               => 'E-mail',
        'inscricao-estadual'  => 'Inscrição Estadual',
        'cnae'                => 'CNAE',
        'regime-tributario'   => 'Regime Tributário',
        'data-abertura'       => 'Data de Abertura',
        'observacoes'         => 'Observações',
    ],

    'table' => [
        'columns' => [
            'nome-fantasia'      => 'Nome Fantasia',
            'razao-social'       => 'Razão Social',
            'cnpj'               => 'CNPJ',
            'telefone'           => 'Telefone',
            'regime-tributario'  => 'Regime Tributário',
            'categorias'         => 'Categorias',
            'created-at'         => 'Criado em',
        ],

        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Pessoa jurídica atualizada',
                    'body'  => 'A pessoa jurídica foi atualizada com sucesso.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Pessoa jurídica excluída',
                    'body'  => 'A pessoa jurídica foi excluída com sucesso.',
                ],
            ],
        ],
    ],
];
