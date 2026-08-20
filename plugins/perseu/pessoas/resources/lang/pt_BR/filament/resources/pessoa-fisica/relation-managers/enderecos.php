<?php

return [
    'title' => 'Endereços',

    'form' => [
        'cep'         => 'CEP',
        'logradouro'  => 'Logradouro',
        'numero'      => 'Número',
        'complemento' => 'Complemento',
        'bairro'      => 'Bairro',
        'municipio'   => 'Município',
        'uf'          => 'UF',
        'tipo'        => 'Tipo',
        'principal'   => 'Endereço principal?',
    ],

    'table' => [
        'columns' => [
            'logradouro' => 'Logradouro',
            'numero'     => 'Número',
            'bairro'     => 'Bairro',
            'municipio'  => 'Município',
            'uf'         => 'UF',
            'tipo'       => 'Tipo',
            'principal'  => 'Principal',
        ],

        'header-actions' => [
            'create' => [
                'label' => 'Novo endereço',

                'notification' => [
                    'title' => 'Endereço adicionado',
                    'body'  => 'O endereço foi adicionado com sucesso.',
                ],
            ],
        ],

        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Endereço atualizado',
                    'body'  => 'O endereço foi atualizado com sucesso.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Endereço removido',
                    'body'  => 'O endereço foi removido com sucesso.',
                ],
            ],
        ],
    ],
];
