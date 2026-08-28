<?php

return [
    'model-label' => 'Individual',

    'plural-model-label' => 'Individuals',

    'navigation' => [
        'title' => 'Individuals',
    ],

    'form' => [
        'nome'               => 'Name',
        'categorias'         => 'Categories',
        'telefone'           => 'Phone',
        'telefone-whatsapp'  => 'Is WhatsApp?',
        'email'              => 'Email',
        'cpf'                => 'CPF',
        'rg'                 => 'RG',
        'data-nascimento'    => 'Date of Birth',
        'estado-civil'       => 'Marital Status',
        'sexo'               => 'Gender',
        'profissao'          => 'Occupation',
        'observacoes'        => 'Notes',
    ],

    'table' => [
        'columns' => [
            'nome'         => 'Name',
            'telefone'     => 'Phone',
            'email'        => 'Email',
            'cpf'          => 'CPF',
            'estado-civil' => 'Marital Status',
            'categorias'   => 'Categories',
            'created-at'   => 'Created At',
        ],

        'filters' => [
            'trashed' => 'Deleted',
        ],

        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Individual updated',
                    'body'  => 'The individual has been updated successfully.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Individual deleted',
                    'body'  => 'The individual has been deleted successfully.',
                ],
            ],

            'restore' => [
                'notification' => [
                    'title' => 'Individual restored',
                    'body'  => 'The individual has been restored successfully.',
                ],
            ],

            'force-delete' => [
                'notification' => [
                    'title' => 'Individual permanently deleted',
                    'body'  => 'The individual and its related data (addresses) have been permanently deleted.',
                ],
            ],
        ],
    ],
];
