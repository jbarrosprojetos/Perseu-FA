<?php

return [
    'model-label' => 'Etapa do processo',

    'plural-model-label' => 'Etapas do processo',

    'navigation' => [
        'title' => 'Etapas do processo',
    ],

    'form' => [
        'name' => 'Nome',
    ],

    'table' => [
        'columns' => [
            'name'       => 'Nome',
            'created-at' => 'Criado em',
            'updated-at' => 'Atualizado em',
        ],

        'groups' => [
            'name'         => 'Nome',
            'is-completed' => 'Está concluído',
            'processo'     => 'Processo',
            'created-at'   => 'Criado em',
        ],

        'filters' => [
            'is-completed' => 'Está concluído',
            'processo'     => 'Processo',
            'creator'      => 'Criador',
        ],

        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Etapa do processo atualizado',
                    'body'  => 'O etapa do processo foi atualizado com sucesso.',
                ],
            ],

            'restore' => [
                'notification' => [
                    'title' => 'Etapa do processo restaurado',
                    'body'  => 'O etapa do processo foi restaurado com sucesso.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Etapa do processo excluído',
                    'body'  => 'O etapa do processo foi excluído com sucesso.',
                ],
            ],

            'force-delete' => [
                'notification' => [
                    'success' => [
                        'title' => 'Etapa do processo excluído permanentemente',
                        'body'  => 'O etapa do processo foi excluído permanentemente com sucesso.',
                    ],
                    'error' => [
                        'title' => 'Etapa do processo não pôde ser excluído',
                        'body'  => 'O etapa do processo não pode ser excluído porque está em uso no momento.',
                    ],
                ],
            ],
        ],

        'bulk-actions' => [
            'restore' => [
                'notification' => [
                    'title' => 'Etapas do processo restaurados',
                    'body'  => 'Os etapas do processo foram restaurados com sucesso.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Etapas do processo excluídos',
                    'body'  => 'Os etapas do processo foram excluídos com sucesso.',
                ],
            ],

            'force-delete' => [
                'notification' => [
                    'title' => 'Etapas do processo excluídos permanentemente',
                    'body'  => 'Os etapas do processo foram excluídos permanentemente com sucesso.',
                ],
            ],
        ],
    ],
];
