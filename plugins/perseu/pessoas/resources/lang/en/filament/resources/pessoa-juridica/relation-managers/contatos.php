<?php

return [
    'title' => 'Contacts',

    'form' => [
        'pessoa-fisica' => 'Individual',
        'cargo'         => 'Role',
    ],

    'table' => [
        'columns' => [
            'nome'     => 'Name',
            'email'    => 'Email',
            'telefone' => 'Phone',
            'cargo'    => 'Role',
        ],

        'header-actions' => [
            'create' => [
                'label' => 'New contact',

                'notification' => [
                    'title' => 'Contact added',
                    'body'  => 'The contact has been added successfully.',
                ],
            ],
        ],

        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Contact updated',
                    'body'  => 'The contact has been updated successfully.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Contact removed',
                    'body'  => 'The contact has been removed successfully.',
                ],
            ],
        ],
    ],
];
