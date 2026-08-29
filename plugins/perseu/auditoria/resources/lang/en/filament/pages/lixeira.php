<?php

return [
    'navigation' => [
        'title' => 'Trash',
    ],

    'table' => [
        'columns' => [
            'subject_type' => 'Record type',
            'subject_reference' => 'Record',
            'subject_reference_unavailable' => 'Record without reference',
            'deleted_at' => 'Deleted at',
            'deleted_by' => 'Deleted by',
        ],
        'filters' => [
            'modulo' => [
                'label' => 'Module',
            ],
            'subject_type' => [
                'label' => 'Record type',
            ],
            'excluido_em' => [
                'label' => 'Deletion period',
                'de' => 'Deleted from',
                'ate' => 'Deleted until',
            ],
        ],
        'actions' => [
            'restore' => [
                'label' => 'Restore',
                'confirmation' => 'Restore :cadastro ":registro"? The record will appear normally in its original list again.',
                'notification' => [
                    'success' => 'Record restored successfully.',
                    'error' => 'Could not restore this record (check if you still have permission, or if it was already permanently deleted).',
                ],
            ],
            'force_delete' => [
                'label' => 'Force Delete',
                'confirmation' => 'PERMANENTLY delete :cadastro ":registro"? This cannot be undone — if this record has related data removed in cascade (e.g. Company/Individual Addresses/Contacts), it is deleted now too.',
                'notification' => [
                    'success' => 'Record permanently deleted.',
                    'error' => 'Could not permanently delete this record (check if you still have permission).',
                ],
            ],
        ],
        'bulk-actions' => [
            'restore' => [
                'label' => 'Restore selected',
                'notification' => [
                    'title' => ':total record(s) restored successfully.',
                ],
            ],
            'force_delete' => [
                'label' => 'Force Delete selected',
                'notification' => [
                    'title' => ':total record(s) permanently deleted.',
                ],
            ],
            'notification' => [
                'skipped' => ':total record(s) were not changed due to missing permission.',
            ],
        ],
    ],
];
