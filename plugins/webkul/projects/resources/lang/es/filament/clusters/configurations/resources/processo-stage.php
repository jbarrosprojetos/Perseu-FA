<?php

return [
    'model-label' => 'Etapa de proceso',

    'plural-model-label' => 'Etapas de proceso',

    'navigation' => [
        'title' => 'Etapas de proceso',
    ],

    'form' => [
        'name' => 'Nombre',
    ],

    'table' => [
        'columns' => [
            'name'       => 'Nombre',
            'created-at' => 'Creado el',
            'updated-at' => 'Actualizado el',
        ],

        'groups' => [
            'name'         => 'Nombre',
            'is-completed' => 'Está completada',
            'processo'     => 'Proceso',
            'created-at'   => 'Creado el',
        ],

        'filters' => [
            'is-completed' => 'Está completada',
            'processo'     => 'Proceso',
            'creator'      => 'Creador',
        ],

        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Etapa de proceso actualizada',
                    'body'  => 'La etapa de proceso se ha actualizado correctamente.',
                ],
            ],

            'restore' => [
                'notification' => [
                    'title' => 'Etapa de proceso restaurada',
                    'body'  => 'La etapa de proceso se ha restaurado correctamente.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Etapa de proceso eliminada',
                    'body'  => 'La etapa de proceso se ha eliminado correctamente.',
                ],
            ],

            'force-delete' => [
                'notification' => [
                    'success' => [
                        'title' => 'Etapa de proceso eliminada permanentemente',
                        'body'  => 'La etapa de proceso se ha eliminado permanentemente correctamente.',
                    ],
                    'error' => [
                        'title' => 'No se pudo eliminar la etapa de proceso',
                        'body'  => 'La etapa de proceso no se puede eliminar porque está actualmente en uso.',
                    ],
                ],
            ],
        ],

        'bulk-actions' => [
            'restore' => [
                'notification' => [
                    'title' => 'Etapas de proceso restauradas',
                    'body'  => 'Las etapas de proceso se han restaurado correctamente.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Etapas de proceso eliminadas',
                    'body'  => 'Las etapas de proceso se han eliminado correctamente.',
                ],
            ],

            'force-delete' => [
                'notification' => [
                    'title' => 'Etapas de proceso eliminadas permanentemente',
                    'body'  => 'Las etapas de proceso se han eliminado permanentemente correctamente.',
                ],
            ],
        ],
    ],
];
