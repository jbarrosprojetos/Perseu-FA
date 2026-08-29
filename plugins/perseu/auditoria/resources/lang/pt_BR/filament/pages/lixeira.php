<?php

return [
    'navigation' => [
        'title' => 'Lixeira',
    ],

    'table' => [
        'columns' => [
            'subject_type' => 'Cadastro',
            'subject_reference' => 'Registro',
            'subject_reference_unavailable' => 'Registro sem referência',
            'deleted_at' => 'Excluído em',
            'deleted_by' => 'Excluído por',
        ],
        'filters' => [
            'modulo' => [
                'label' => 'Módulo',
            ],
            'subject_type' => [
                'label' => 'Cadastro',
            ],
            'excluido_em' => [
                'label' => 'Período de exclusão',
                'de' => 'Excluído de',
                'ate' => 'Excluído até',
            ],
        ],
        'actions' => [
            'restore' => [
                'label' => 'Restaurar',
                'confirmation' => 'Restaurar :cadastro ":registro"? O registro volta a aparecer normalmente no cadastro de origem.',
                'notification' => [
                    'success' => 'Registro restaurado com sucesso.',
                    'error' => 'Não foi possível restaurar este registro (verifique se você ainda tem permissão, ou se ele já foi excluído definitivamente).',
                ],
            ],
            'force_delete' => [
                'label' => 'Excluir Permanentemente',
                'confirmation' => 'Excluir :cadastro ":registro" DEFINITIVAMENTE? Esta ação não pode ser desfeita — se este cadastro tiver relacionamentos removidos em cascata (ex.: Endereços/Contatos de Pessoa Jurídica/Física), eles também são apagados agora.',
                'notification' => [
                    'success' => 'Registro excluído definitivamente.',
                    'error' => 'Não foi possível excluir este registro definitivamente (verifique se você ainda tem permissão).',
                ],
            ],
        ],
        'bulk-actions' => [
            'restore' => [
                'label' => 'Restaurar selecionados',
                'notification' => [
                    'title' => ':total registro(s) restaurado(s) com sucesso.',
                ],
            ],
            'force_delete' => [
                'label' => 'Excluir Permanentemente selecionados',
                'notification' => [
                    'title' => ':total registro(s) excluído(s) definitivamente.',
                ],
            ],
            'notification' => [
                'skipped' => ':total registro(s) não foram alterados por falta de permissão.',
            ],
        ],
    ],
];
