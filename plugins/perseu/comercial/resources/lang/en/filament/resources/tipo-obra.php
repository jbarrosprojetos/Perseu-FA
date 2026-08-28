<?php

return [
    'model-label' => 'Job Type',

    'plural-model-label' => 'Job Types',

    'navigation' => [
        'title' => 'Job Types',
    ],

    'form' => [
        'codigo'    => 'Code',
        'descricao' => 'Description',
    ],

    'table' => [
        'columns' => [
            'codigo'    => 'Code',
            'descricao' => 'Description',
        ],

        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Job type updated',
                    'body'  => 'The job type has been updated successfully.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Job type deleted',
                    'body'  => 'The job type has been deleted successfully.',
                ],
            ],
        ],

        'bulk-actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Job types deleted',
                    'body'  => 'The selected job types have been deleted successfully.',
                ],
            ],
        ],
    ],
];
