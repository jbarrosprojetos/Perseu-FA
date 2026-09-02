<?php

return [
    'model-label' => 'Etapa de tarea',

    'plural-model-label' => 'Etapas de tarea',

    'navigation' => [
        'title' => 'Etapas de tarea',
    ],

    'form' => [
        'name'    => 'Nombre',
        'processo' => 'Proceso',
    ],

    'table' => [
        'columns' => [
            'name'       => 'Nombre',
            'processo'   => 'Proceso',
            'created-at' => 'Creado el',
            'updated-at' => 'Actualizado el',
        ],

        'groups' => [
            'processo'   => 'Proceso',
            'created-at' => 'Creado el',
        ],

        'filters' => [
            'processo' => 'Proceso',
        ],

        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Etapa de tarea actualizada',
                    'body'  => 'La etapa de tarea se ha actualizado correctamente.',
                ],
            ],

            'restore' => [
                'notification' => [
                    'title' => 'Etapa de tarea restaurada',
                    'body'  => 'La etapa de tarea se ha restaurado correctamente.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Etapa de tarea eliminada',
                    'body'  => 'La etapa de tarea se ha eliminado correctamente.',
                ],
            ],

            'force-delete' => [
                'notification' => [
                    'success' => [
                        'title' => 'Etapa de tarea eliminada permanentemente',
                        'body'  => 'La etapa de tarea se ha eliminado permanentemente correctamente.',
                    ],
                    'error' => [
                        'title' => 'No se pudo eliminar la etapa de tarea',
                        'body'  => 'La etapa de tarea no se puede eliminar porque está actualmente en uso.',
                    ],
                ],
            ],
        ],

        'bulk-actions' => [
            'restore' => [
                'notification' => [
                    'title' => 'Etapas de tarea restauradas',
                    'body'  => 'Las etapas de tarea se han restaurado correctamente.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Etapas de tarea eliminadas',
                    'body'  => 'Las etapas de tarea se han eliminado correctamente.',
                ],
            ],

            'force-delete' => [
                'notification' => [
                    'title' => 'Etapas de tarea eliminadas permanentemente',
                    'body'  => 'Las etapas de tarea se han eliminado permanentemente correctamente.',
                ],
            ],
        ],
    ],
];
