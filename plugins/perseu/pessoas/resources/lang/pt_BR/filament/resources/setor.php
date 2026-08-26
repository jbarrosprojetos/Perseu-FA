<?php

return [
    'model-label' => 'Setor',

    'plural-model-label' => 'Setores',

    'navigation' => [
        'title' => 'Setores',
    ],

    'form' => [
        'descricao' => 'Descrição',
    ],

    'table' => [
        'columns' => [
            'descricao'  => 'Descrição',
            'created-at' => 'Criado em',
        ],

        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Setor atualizado',
                    'body'  => 'O setor foi atualizado com sucesso.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Setor excluído',
                    'body'  => 'O setor foi excluído com sucesso.',
                ],
            ],
        ],

        'bulk-actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Setores excluídos',
                    'body'  => 'Os setores selecionados foram excluídos com sucesso.',
                ],
            ],
        ],
    ],
];
