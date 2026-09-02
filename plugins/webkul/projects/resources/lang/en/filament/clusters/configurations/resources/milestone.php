<?php

return [
    'model-label' => 'Milestone',

    'plural-model-label' => 'Milestones',

    'navigation' => [
        'title' => 'Milestones',
    ],

    'form' => [
        'name'         => 'Name',
        'deadline'     => 'Deadline',
        'is-completed' => 'Is Completed',
        'processo'     => 'Process',
    ],

    'table' => [
        'columns' => [
            'name'         => 'Name',
            'deadline'     => 'Deadline',
            'is-completed' => 'Is Completed',
            'completed-at' => 'Completed At',
            'processo'     => 'Process',
            'creator'      => 'Creator',
            'created-at'   => 'Created At',
            'updated-at'   => 'Updated At',
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
                    'title' => 'Milestone update',
                    'body'  => 'The milestone has been update successfully.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Milestone deleted',
                    'body'  => 'The milestone has been deleted successfully.',
                ],
            ],
        ],

        'bulk-actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Milestones deleted',
                    'body'  => 'The milestones has been deleted successfully.',
                ],
            ],
        ],
    ],
];
