<?php

return [
    'model-label' => 'Sector',

    'plural-model-label' => 'Sectors',

    'navigation' => [
        'title' => 'Sectors',
    ],

    'form' => [
        'descricao' => 'Description',
    ],

    'table' => [
        'columns' => [
            'descricao'  => 'Description',
            'created-at' => 'Created At',
        ],

        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Sector updated',
                    'body'  => 'The sector has been updated successfully.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Sector deleted',
                    'body'  => 'The sector has been deleted successfully.',
                ],
            ],
        ],

        'bulk-actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Sectors deleted',
                    'body'  => 'The selected sectors have been deleted successfully.',
                ],
            ],
        ],
    ],
];
