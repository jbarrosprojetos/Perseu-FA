<?php

return [
    'model-label' => 'Status',

    'plural-model-label' => 'Statuses',

    'navigation' => [
        'title' => 'Statuses',
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
                    'title' => 'Status updated',
                    'body'  => 'The status has been updated successfully.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Status deleted',
                    'body'  => 'The status has been deleted successfully.',
                ],
            ],
        ],

        'bulk-actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Statuses deleted',
                    'body'  => 'The selected statuses have been deleted successfully.',
                ],
            ],
        ],
    ],
];
