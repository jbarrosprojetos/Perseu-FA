<?php

return [
    'model-label' => 'Legal Entity',

    'plural-model-label' => 'Legal Entities',

    'navigation' => [
        'title' => 'Legal Entities',
    ],

    'form' => [
        'categorias'                    => 'Categories',
        'setores'                       => 'Sectors',
        'razao-social'                  => 'Corporate Name',
        'nome-fantasia'                 => 'Trade Name',
        'cnpj'                          => 'CNPJ',
        'cnpj-nao-encontrado'           => 'CNPJ not found in the Federal Revenue database — check the number or continue filling in the registration manually.',
        'telefone'                      => 'Phone',
        'email'                         => 'Email',
        'inscricao-estadual'            => 'State Registration',
        'indicador-contribuinte-icms'   => 'ICMS Taxpayer Status',
        'cnae'                          => 'CNAE',
        'situacao-cadastral'            => 'Registration Status',
        'regime-tributario'             => 'Tax Regime',
        'data-abertura'                 => 'Opening Date',
        'porte'                         => 'Company Size',
        'observacoes'                   => 'Notes',
    ],

    'table' => [
        'columns' => [
            'nome-fantasia'      => 'Trade Name',
            'razao-social'       => 'Corporate Name',
            'cnpj'               => 'CNPJ',
            'telefone'           => 'Phone',
            'regime-tributario'  => 'Tax Regime',
            'categorias'         => 'Categories',
            'setores'            => 'Sectors',
            'created-at'         => 'Created At',
        ],

        'filters' => [
            'trashed' => 'Deleted',
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

            'restore' => [
                'notification' => [
                    'title' => 'Legal entity restored',
                    'body'  => 'The legal entity has been restored successfully.',
                ],
            ],

            'force-delete' => [
                'notification' => [
                    'title' => 'Legal entity permanently deleted',
                    'body'  => 'The legal entity and its related data (addresses, contacts) have been permanently deleted.',
                ],
            ],
        ],
    ],
];
