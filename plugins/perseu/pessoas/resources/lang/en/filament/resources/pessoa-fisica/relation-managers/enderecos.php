<?php

return [
    'title' => 'Addresses',

    'form' => [
        'cep'         => 'ZIP Code',
        'logradouro'  => 'Street',
        'numero'      => 'Number',
        'complemento' => 'Complement',
        'bairro'      => 'Neighborhood',
        'municipio'   => 'City',
        'uf'          => 'State',
        'tipos'       => 'Types',
        'principal'   => 'Main address?',
    ],

    'table' => [
        'columns' => [
            'logradouro' => 'Street',
            'numero'     => 'Number',
            'bairro'     => 'Neighborhood',
            'municipio'  => 'City',
            'uf'         => 'State',
            'tipos'      => 'Types',
            'principal'  => 'Main',
        ],

        'header-actions' => [
            'create' => [
                'label' => 'New address',

                'notification' => [
                    'title' => 'Address added',
                    'body'  => 'The address has been added successfully.',
                ],
            ],
        ],

        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Address updated',
                    'body'  => 'The address has been updated successfully.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Address removed',
                    'body'  => 'The address has been removed successfully.',
                ],
            ],
        ],
    ],
];
