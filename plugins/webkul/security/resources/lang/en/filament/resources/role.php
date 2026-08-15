<?php

return [
    'navigation' => [
        'title' => 'Roles',
    ],

    'model-label' => 'Role',

    'plural-model-label' => 'Roles',

    'form' => [
        'fields' => [
            'web'     => 'Web',
            'sanctum' => 'Sanctum',
        ],
    ],

    'notification' => [
        'system-role-delete' => [
            'title' => 'System Role Cannot Be Deleted',
            'body'  => 'This is a system role and cannot be deleted.',
        ],
    ],
];
