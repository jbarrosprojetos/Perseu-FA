<?php

return [
    'model-label' => 'Task Stage',

    'plural-model-label' => 'Task Stages',

    'navigation' => [
        'title' => 'Task Stages',
    ],

    'form' => [
        'name'    => 'Name',
        'processo' => 'Process',
    ],

    'table' => [
        'columns' => [
            'name'       => 'Name',
            'processo'   => 'Process',
            'created-at' => 'Created At',
            'updated-at' => 'Updated At',
        ],

        'groups' => [
            'processo'   => 'Process',
            'created-at' => 'Created At',
        ],

        'filters' => [
            'processo' => 'Process',
        ],

        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Task stage updated',
                    'body'  => 'The task stage has been updated successfully.',
                ],
            ],

            'restore' => [
                'notification' => [
                    'title' => 'Task stage restored',
                    'body'  => 'The task stage has been restored successfully.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Task stage deleted',
                    'body'  => 'The task stage has been deleted successfully.',
                ],
            ],

            'force-delete' => [
                'notification' => [
                    'success' => [
                        'title' => 'Task stage force deleted',
                        'body'  => 'The Task stage has been force deleted successfully.',
                    ],
                    'error' => [
                        'title' => 'Task stage could not be deleted',
                        'body'  => 'The Task stage cannot be deleted because it is currently in use.',
                    ],
                ],
            ],
        ],

        'bulk-actions' => [
            'restore' => [
                'notification' => [
                    'title' => 'Task stages restored',
                    'body'  => 'The task stages has been restored successfully.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Task stages deleted',
                    'body'  => 'The task stages has been deleted successfully.',
                ],
            ],

            'force-delete' => [
                'notification' => [
                    'title' => 'Task stages force deleted',
                    'body'  => 'The task stages has been force deleted successfully.',
                ],
            ],
        ],
    ],
];
