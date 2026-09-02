<?php

return [
    'model-label' => 'Project Type',

    'plural-model-label' => 'Project Types',

    'navigation' => [
        'title' => 'Project Types',
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
                    'title' => 'Project type updated',
                    'body'  => 'The project type has been updated successfully.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Project type deleted',
                    'body'  => 'The project type has been deleted successfully.',
                ],
            ],
        ],

        'bulk-actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Project types deleted',
                    'body'  => 'The selected project types have been deleted successfully.',
                ],
            ],
        ],
    ],
];
