<?php

return [
    'model-label' => 'Category',

    'plural-model-label' => 'Categories',

    'navigation' => [
        'title' => 'Categories',
    ],

    'form' => [
        'descricao'  => 'Description',
        'aplica-pf'  => 'Applies to Individual',
        'aplica-pj'  => 'Applies to Company',
        'e-cliente'  => 'Is Customer category?',
    ],

    'table' => [
        'columns' => [
            'descricao'  => 'Description',
            'aplica-pf'  => 'Individual',
            'aplica-pj'  => 'Company',
            'e-cliente'  => 'Customer',
            'created-at' => 'Created At',
        ],

        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Category updated',
                    'body'  => 'The category has been updated successfully.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Category deleted',
                    'body'  => 'The category has been deleted successfully.',
                ],
            ],
        ],

        'bulk-actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Categories deleted',
                    'body'  => 'The selected categories have been deleted successfully.',
                ],
            ],
        ],
    ],
];
