<?php

return [
    'model-label' => 'Document',

    'plural-model-label' => 'Documents',

    'navigation' => [
        'title' => 'Documents',
    ],

    'form' => [
        'descricao' => 'Document Short Description',
        'arquivo'   => 'File (Excel)',
    ],

    'table' => [
        'columns' => [
            'descricao'  => 'Document Short Description',
            'created-at' => 'Created at',
        ],

        'filters' => [
            'trashed' => 'Trashed',
        ],

        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Document edited',
                    'body'  => 'The document was edited successfully.',
                ],
            ],
            'delete' => [
                'notification' => [
                    'title' => 'Document deleted',
                    'body'  => 'The document was deleted successfully.',
                ],
            ],
            'restore' => [
                'notification' => [
                    'title' => 'Document restored',
                    'body'  => 'The document was restored successfully.',
                ],
            ],
            'force-delete' => [
                'notification' => [
                    'title' => 'Document permanently deleted',
                    'body'  => 'The document was permanently deleted successfully.',
                ],
            ],
        ],
    ],
];
