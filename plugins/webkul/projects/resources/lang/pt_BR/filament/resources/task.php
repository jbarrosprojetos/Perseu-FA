<?php

return [
    'title' => 'Tarefas',

    'model-label' => 'Tarefa',

    'plural-model-label' => 'Tarefas',

    'navigation' => [
        'title' => 'Tarefas',
    ],

    'global-search' => [
        'processo'  => 'Processo',
        'customer'  => 'Cliente',
        'milestone' => 'Marco',
    ],

    'form' => [
        'sections' => [
            'general' => [
                'title' => 'Geral',

                'fields' => [
                    'title'             => 'Título',
                    'title-placeholder' => 'Título da tarefa...',
                    'tags'              => 'Tags',
                    'name'              => 'Nome',
                    'color'             => 'Cor',
                    'description'       => 'Descrição',
                    'processo'          => 'Processo',
                    'status'            => 'Status',
                    'start_date'        => 'Data de início',
                    'end_date'          => 'Data de término',
                ],
            ],

            'additional' => [
                'title' => 'Informações adicionais',
            ],

            'settings' => [
                'title' => 'Configurações',

                'fields' => [
                    'processo'                    => 'Processo',
                    'milestone'                   => 'Marco',
                    'milestone-hint-text'         => 'Entregue automaticamente seus serviços ao atingir um marco vinculando-o a um item do pedido de venda.',
                    'name'                        => 'Nome',
                    'deadline'                    => 'Prazo final',
                    'is-completed'                => 'Está concluído',
                    'customer'                    => 'Cliente',
                    'assignees'                   => 'Responsáveis',
                    'allocated-hours'             => 'Horas alocadas',
                    'allocated-hours-helper-text' => 'Em horas (Ex.: 1,5 horas significa 1 hora e 30 minutos)',
                ],
            ],
        ],
    ],

    'table' => [
        'columns' => [
            'id'                  => 'ID',
            'priority'            => 'Prioridade',
            'state'               => 'Estado',
            'new-state'           => 'Novo estado',
            'update-state'        => 'Atualizar estado',
            'title'               => 'Título',
            'processo'            => 'Processo',
            'processo-placeholder' => 'Tarefa privada',
            'milestone'           => 'Marco',
            'customer'            => 'Cliente',
            'assignees'           => 'Responsáveis',
            'allocated-time'      => 'Tempo alocado',
            'time-spent'          => 'Tempo gasto',
            'time-remaining'      => 'Tempo restante',
            'progress'            => 'Progresso',
            'deadline'            => 'Prazo final',
            'tags'                => 'Tags',
            'stage'               => 'Etapa',
        ],

        'groups' => [
            'state'      => 'Estado',
            'processo'   => 'Processo',
            'milestone'  => 'Marco',
            'customer'   => 'Cliente',
            'deadline'   => 'Prazo final',
            'stage'      => 'Etapa',
            'created-at' => 'Criado em',
        ],

        'filters' => [
            'title'             => 'Título',
            'priority'          => 'Prioridade',
            'low'               => 'Baixo',
            'high'              => 'Alto',
            'state'             => 'Estado',
            'tags'              => 'Tags',
            'allocated-hours'   => 'Horas alocadas',
            'total-hours-spent' => 'Total de horas gastas',
            'remaining-hours'   => 'Horas restantes',
            'overtime'          => 'Hora extra',
            'progress'          => 'Progresso',
            'deadline'          => 'Prazo final',
            'created-at'        => 'Criado em',
            'updated-at'        => 'Atualizado em',
            'assignees'         => 'Responsáveis',
            'customer'          => 'Cliente',
            'processo'          => 'Processo',
            'stage'             => 'Etapa',
            'milestone'         => 'Marco',
            'company'           => 'Empresa',
            'creator'           => 'Criador',
        ],

        'actions' => [
            'update-state' => [
                'modal-heading' => 'Atualizar estado da tarefa',
            ],

            'restore' => [
                'notification' => [
                    'title' => 'Tarefa restaurada',
                    'body'  => 'A tarefa foi restaurada com sucesso.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Tarefa excluída',
                    'body'  => 'A tarefa foi excluída com sucesso.',
                ],
            ],

            'force-delete' => [
                'notification' => [
                    'title' => 'Tarefa excluída permanentemente',
                    'body'  => 'A tarefa foi excluída permanentemente com sucesso.',
                ],
            ],
        ],

        'bulk-actions' => [
            'restore' => [
                'notification' => [
                    'title' => 'Tarefas restauradas',
                    'body'  => 'As tarefas foram restauradas com sucesso.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Tarefas excluídas',
                    'body'  => 'As tarefas foram excluídas com sucesso.',
                ],
            ],

            'force-delete' => [
                'notification' => [
                    'title' => 'Tarefas excluídas permanentemente',
                    'body'  => 'As tarefas foram excluídas permanentemente com sucesso.',
                ],
            ],
        ],
    ],

    'infolist' => [
        'sections' => [
            'general' => [
                'title' => 'Geral',

                'entries' => [
                    'title'       => 'Título',
                    'state'       => 'Estado',
                    'tags'        => 'Tags',
                    'priority'    => 'Prioridade',
                    'description' => 'Descrição',
                ],
            ],

            'processo-information' => [
                'title' => 'Informações do processo',

                'entries' => [
                    'processo'  => 'Processo',
                    'milestone' => 'Marco',
                    'customer'  => 'Cliente',
                    'assignees' => 'Responsáveis',
                    'deadline'  => 'Prazo final',
                    'stage'     => 'Etapa',
                ],
            ],

            'time-tracking' => [
                'title' => 'Controle de tempo',

                'entries' => [
                    'allocated-time'        => 'Tempo alocado',
                    'allocated-time-suffix' => ' horas',
                    'time-spent'            => 'Tempo gasto',
                    'time-spent-suffix'     => ' horas',
                    'time-remaining'        => 'Tempo restante',
                    'time-remaining-suffix' => ' horas',
                    'progress'              => 'Progresso',
                ],
            ],

            'additional-information' => [
                'title' => 'Informações adicionais',
            ],

            'record-information' => [
                'title' => 'Informações do registro',

                'entries' => [
                    'created-at'   => 'Criado em',
                    'created-by'   => 'Criado por',
                    'last-updated' => 'Última atualização',
                ],
            ],

            'statistics' => [
                'title' => 'Estatísticas',

                'entries' => [
                    'sub-tasks'         => 'Subtarefas',
                    'timesheet-entries' => 'Entradas de apontamento de horas',
                ],
            ],
        ],
    ],
];
