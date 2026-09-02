<?php

return [
    'model-label' => 'Processo',

    'plural-model-label' => 'Processos',

    'navigation' => [
        'title' => 'Processos',
    ],

    'global-search' => [
        'processo-manager' => 'Gerente de processo',
        'customer'         => 'Cliente',
    ],

    'form' => [
        'sections' => [
            'general' => [
                'title' => 'Geral',

                'fields' => [
                    'name'             => 'Nome',
                    'name-placeholder' => 'Nome do processo...',
                    'description'      => 'Descrição',
                ],
            ],

            'additional' => [
                'title' => 'Informações adicionais',

                'fields' => [
                    'processo-manager'            => 'Gerente de processo',
                    'customer'                    => 'Cliente',
                    'start-date'                  => 'Data de início',
                    'end-date'                    => 'Data de término',
                    'allocated-hours'             => 'Horas alocadas',
                    'allocated-hours-helper-text' => 'Em horas (Ex.: 1,5 horas significa 1 hora e 30 minutos)',
                    'tags'                        => 'Tags',
                    'company'                     => 'Empresa',
                ],
            ],

            'settings' => [
                'title' => 'Configurações',

                'fields' => [
                    'visibility'                   => 'Visibilidade',
                    'visibility-hint-tooltip'      => 'Permita que colaboradores acessem seu processo ou suas tarefas adicionando-os como seguidores. Eles terão acesso automático às tarefas atribuídas a eles.',
                    'private-description'          => 'Somente usuários internos convidados.',
                    'internal-description'         => 'Todos os usuários internos podem ver.',
                    'public-description'           => 'Usuários do portal convidados e todos os usuários internos.',
                    'time-management'              => 'Gerenciamento de tempo',
                    'allow-timesheets'             => 'Permitir apontamentos de horas',
                    'allow-timesheets-helper-text' => 'Registre tempo em tarefas e acompanhe o progresso',
                    'task-management'              => 'Gerenciamento de tarefas',
                    'allow-milestones'             => 'Permitir marcos',
                    'allow-milestones-helper-text' => 'Monitore marcos essenciais para alcançar o sucesso.',
                ],
            ],
        ],
    ],

    'table' => [
        'columns' => [
            'name'                   => 'Nome',
            'customer'               => 'Cliente',
            'start-date'             => 'Data de início',
            'end-date'               => 'Data de término',
            'planned-date'           => 'Data planejada',
            'remaining-hours'        => 'Horas restantes',
            'remaining-hours-suffix' => ' horas',
            'processo-manager'       => 'Gerente de processo',
        ],

        'groups' => [
            'stage'            => 'Etapa',
            'processo-manager' => 'Gerente de processo',
            'customer'         => 'Cliente',
            'created-at'       => 'Criado em',
        ],

        'filters' => [
            'name'             => 'Nome',
            'visibility'       => 'Visibilidade',
            'start-date'       => 'Data de início',
            'end-date'         => 'Data de término',
            'allow-timesheets' => 'Permitir apontamentos de horas',
            'allow-milestones' => 'Permitir marcos',
            'allocated-hours'  => 'Horas alocadas',
            'created-at'       => 'Criado em',
            'updated-at'       => 'Atualizado em',
            'stage'            => 'Etapa',
            'customer'         => 'Cliente',
            'processo-manager' => 'Gerente de processo',
            'company'          => 'Empresa',
            'creator'          => 'Criador',
            'tags'             => 'Tags',
        ],

        'actions' => [
            'tasks'      => ':count tarefas',
            'milestones' => ':completed marcos concluídos de :all',

            'restore' => [
                'notification' => [
                    'title' => 'Processo restaurado',
                    'body'  => 'O processo foi restaurado com sucesso.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Processo excluído',
                    'body'  => 'O processo foi excluído com sucesso.',
                ],
            ],

            'force-delete' => [

                'notification' => [

                    'success' => [
                        'title' => 'Processo excluído permanentemente',
                        'body'  => 'O processo foi excluído permanentemente com sucesso.',
                    ],

                    'error' => [
                        'title' => 'O processo não pode ser excluído permanentemente',
                        'body'  => 'O processo está associado a outros registros.',
                    ],

                ],
            ],

        ],
    ],

    'infolist' => [
        'sections' => [
            'general' => [
                'title' => 'Geral',

                'entries' => [
                    'name'             => 'Nome',
                    'name-placeholder' => 'Nome do processo...',
                    'description'      => 'Descrição',
                ],
            ],

            'additional' => [
                'title' => 'Informações adicionais',

                'entries' => [
                    'processo-manager'       => 'Gerente de processo',
                    'customer'               => 'Cliente',
                    'processo-timeline'      => 'Linha do tempo do processo',
                    'allocated-hours'        => 'Horas alocadas',
                    'allocated-hours-suffix' => ' horas',
                    'remaining-hours'        => 'Horas restantes',
                    'remaining-hours-suffix' => ' horas',
                    'current-stage'          => 'Etapa atual',
                    'tags'                   => 'Tags',
                ],
            ],

            'statistics' => [
                'title' => 'Estatísticas',

                'entries' => [
                    'total-tasks'         => 'Total de tarefas',
                    'milestones-progress' => 'Progresso dos marcos',
                ],
            ],

            'record-information' => [
                'title' => 'Informações do registro',

                'entries' => [
                    'created-at'   => 'Criado em',
                    'created-by'   => 'Criado por',
                    'last-updated' => 'Última atualização',
                ],
            ],

            'settings' => [
                'title' => 'Configurações do processo',

                'entries' => [
                    'visibility'         => 'Visibilidade',
                    'timesheets-enabled' => 'Apontamentos de horas habilitados',
                    'milestones-enabled' => 'Marcos habilitados',
                ],
            ],
        ],
    ],
];
