<?php

return [
    'header-actions' => [
        'delete' => [
            'notification' => [
                'title' => 'Project deleted',
                'body'  => 'The project has been deleted successfully.',
            ],
        ],
    ],

    'notification' => [
        'title' => 'Project updated',
        'body'  => 'The project has been updated successfully.',
    ],

    'form-actions' => [
        'documentos' => [
            'label' => 'Documents',
            'notification' => [
                'title' => 'Not implemented yet',
                'body'  => 'Generating documents from templates for this Project will be implemented in a future step.',
            ],
        ],
        'atribuir-processos' => [
            'label' => 'Assign Processes',
            'notification' => [
                'title' => 'Not implemented yet',
                'body'  => 'Assigning Processes to this Project will be implemented in a future step.',
            ],
        ],
    ],
];
