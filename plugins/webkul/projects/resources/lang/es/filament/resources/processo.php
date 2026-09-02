<?php

return [
    'model-label' => 'Proceso',

    'plural-model-label' => 'Procesos',

    'navigation' => [
        'title' => 'Procesos',
    ],

    'global-search' => [
        'processo-manager' => 'Responsable del proceso',
        'customer'        => 'Cliente',
    ],

    'form' => [
        'sections' => [
            'general' => [
                'title' => 'General',

                'fields' => [
                    'name'             => 'Nombre',
                    'name-placeholder' => 'Nombre del proceso...',
                    'description'      => 'Descripción',
                ],
            ],

            'additional' => [
                'title' => 'Información adicional',

                'fields' => [
                    'processo-manager'             => 'Responsable del proceso',
                    'customer'                    => 'Cliente',
                    'start-date'                  => 'Fecha de inicio',
                    'end-date'                    => 'Fecha de fin',
                    'allocated-hours'             => 'Horas asignadas',
                    'allocated-hours-helper-text' => 'En horas (ej. 1.5 horas significa 1 hora 30 minutos)',
                    'tags'                        => 'Etiquetas',
                    'company'                     => 'Empresa',
                ],
            ],

            'settings' => [
                'title' => 'Configuración',

                'fields' => [
                    'visibility'                   => 'Visibilidad',
                    'visibility-hint-tooltip'      => 'Permitir que los empleados accedan a su proceso o tareas añadiéndolos como seguidores. Obtendrán acceso automáticamente a cualquier tarea que se les asigne.',
                    'private-description'          => 'Solo usuarios internos invitados.',
                    'internal-description'         => 'Todos los usuarios internos pueden ver.',
                    'public-description'           => 'Usuarios del portal invitados y todos los usuarios internos.',
                    'time-management'              => 'Gestión del tiempo',
                    'allow-timesheets'             => 'Permitir partes de horas',
                    'allow-timesheets-helper-text' => 'Registrar tiempo en las tareas y hacer seguimiento del progreso',
                    'task-management'              => 'Gestión de tareas',
                    'allow-milestones'             => 'Permitir hitos',
                    'allow-milestones-helper-text' => 'Supervisar los hitos clave que son esenciales para alcanzar el éxito.',
                ],
            ],
        ],
    ],

    'table' => [
        'columns' => [
            'name'                   => 'Nombre',
            'customer'               => 'Cliente',
            'start-date'             => 'Fecha de inicio',
            'end-date'               => 'Fecha de fin',
            'planned-date'           => 'Fecha planificada',
            'remaining-hours'        => 'Horas restantes',
            'remaining-hours-suffix' => ' Horas',
            'processo-manager'        => 'Responsable del proceso',
        ],

        'groups' => [
            'stage'           => 'Etapa',
            'processo-manager' => 'Responsable del proceso',
            'customer'        => 'Cliente',
            'created-at'      => 'Creado el',
        ],

        'filters' => [
            'name'             => 'Nombre',
            'visibility'       => 'Visibilidad',
            'start-date'       => 'Fecha de inicio',
            'end-date'         => 'Fecha de fin',
            'allow-timesheets' => 'Permitir partes de horas',
            'allow-milestones' => 'Permitir hitos',
            'allocated-hours'  => 'Horas asignadas',
            'created-at'       => 'Creado el',
            'updated-at'       => 'Actualizado el',
            'stage'            => 'Etapa',
            'customer'         => 'Cliente',
            'processo-manager'  => 'Responsable del proceso',
            'company'          => 'Empresa',
            'creator'          => 'Creador',
            'tags'             => 'Etiquetas',
        ],

        'actions' => [
            'tasks'      => ':count tareas',
            'milestones' => ':completed hitos completados de :all',

            'restore' => [
                'notification' => [
                    'title' => 'Proceso restaurado',
                    'body'  => 'El proceso se ha restaurado correctamente.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Proceso eliminado',
                    'body'  => 'El proceso se ha eliminado correctamente.',
                ],
            ],

            'force-delete' => [

                'notification' => [

                    'success' => [
                        'title' => 'Proceso eliminado permanentemente',
                        'body'  => 'El proceso se ha eliminado permanentemente correctamente.',
                    ],

                    'error' => [
                        'title' => 'El proceso no se puede eliminar permanentemente',
                        'body'  => 'El proceso está asociado a otros registros.',
                    ],

                ],
            ],

        ],
    ],

    'infolist' => [
        'sections' => [
            'general' => [
                'title' => 'General',

                'entries' => [
                    'name'             => 'Nombre',
                    'name-placeholder' => 'Nombre del proceso...',
                    'description'      => 'Descripción',
                ],
            ],

            'additional' => [
                'title' => 'Información adicional',

                'entries' => [
                    'processo-manager'        => 'Responsable del proceso',
                    'customer'               => 'Cliente',
                    'processo-timeline'       => 'Cronograma del proceso',
                    'allocated-hours'        => 'Horas asignadas',
                    'allocated-hours-suffix' => ' Horas',
                    'remaining-hours'        => 'Horas restantes',
                    'remaining-hours-suffix' => ' Horas',
                    'current-stage'          => 'Etapa actual',
                    'tags'                   => 'Etiquetas',
                ],
            ],

            'statistics' => [
                'title' => 'Estadísticas',

                'entries' => [
                    'total-tasks'         => 'Total de tareas',
                    'milestones-progress' => 'Progreso de hitos',
                ],
            ],

            'record-information' => [
                'title' => 'Información del registro',

                'entries' => [
                    'created-at'   => 'Creado el',
                    'created-by'   => 'Creado por',
                    'last-updated' => 'Última actualización',
                ],
            ],

            'settings' => [
                'title' => 'Configuración del proceso',

                'entries' => [
                    'visibility'         => 'Visibilidad',
                    'timesheets-enabled' => 'Partes de horas habilitados',
                    'milestones-enabled' => 'Hitos habilitados',
                ],
            ],
        ],
    ],
];
