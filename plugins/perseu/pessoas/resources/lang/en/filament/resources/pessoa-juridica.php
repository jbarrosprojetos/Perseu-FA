<?php

return [
    'model-label' => 'Legal Entity',

    'plural-model-label' => 'Legal Entities',

    'navigation' => [
        'title' => 'Legal Entities',
    ],

    'form' => [
        'categorias'          => 'Categories',
        'razao-social'        => 'Corporate Name',
        'nome-fantasia'       => 'Trade Name',
        'cnpj'                => 'CNPJ',
        'telefone'            => 'Phone',
        'email'               => 'Email',
        'inscricao-estadual'  => 'State Registration',
        'cnae'                => 'CNAE',
        'regime-tributario'   => 'Tax Regime',
        'data-abertura'       => 'Opening Date',
        'observacoes'         => 'Notes',
    ],

    'table' => [
        'columns' => [
            'nome-fantasia'      => 'Trade Name',
            'razao-social'       => 'Corporate Name',
            'cnpj'               => 'CNPJ',
            'telefone'           => 'Phone',
            'regime-tributario'  => 'Tax Regime',
            'categorias'         => 'Categories',
            'created-at'         => 'Created At',
        ],

        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Legal entity updated',
                    'body'  => 'The legal entity has been updated successfully.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Legal entity deleted',
                    'body'  => 'The legal entity has been deleted successfully.',
                ],
            ],
        ],
    ],
];
