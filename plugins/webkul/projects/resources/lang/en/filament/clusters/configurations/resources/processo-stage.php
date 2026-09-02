<?php

return [
    'model-label' => 'Process Stage',

    'plural-model-label' => 'Process Stages',

    'navigation' => [
        'title' => 'Process Stages',
    ],

    'form' => [
        'name' => 'Name',
    ],

    'table' => [
        'columns' => [
            'name'       => 'Name',
            'created-at' => 'Created At',
            'updated-at' => 'Updated At',
        ],

        'groups' => [
            'name'         => 'Name',
            'is-completed' => 'Is Completed',
            'processo'     => 'Process',
            'created-at'   => 'Created At',
        ],

        'filters' => [
            'is-completed' => 'Is Completed',
            'processo'     => 'Process',
            'creator'      => 'Creator',
        ],

        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Process stage updated',
                    'body'  => 'The process stage has been updated successfully.',
                ],
            ],

            'restore' => [
                'notification' => [
                    'title' => 'Process stage restored',
                    'body'  => 'The process stage has been restored successfully.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Process stage deleted',
                    'body'  => 'The process stage has been deleted successfully.',
                ],
            ],

            'force-delete' => [
                'notification' => [
                    'success' => [
                        'title' => 'Process stage force deleted',
                        'body'  => 'The process stage has been force deleted successfully.',
                    ],
                    'error' => [
                        'title' => 'Process Stage  could not be deleted',
                        'body'  => 'The Process Stage  cannot be deleted because it is currently in use.',
                    ],
                ],
            ],
        ],

        'bulk-actions' => [
            'restore' => [
                'notification' => [
                    'title' => 'Process stages restored',
                    'body'  => 'The process stages has been restored successfully.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Process stages deleted',
                    'body'  => 'The process stages has been deleted successfully.',
                ],
            ],

            'force-delete' => [
                'notification' => [
                    'title' => 'Process stages force deleted',
                    'body'  => 'The process stages has been force deleted successfully.',
                ],
            ],
        ],
    ],
];
