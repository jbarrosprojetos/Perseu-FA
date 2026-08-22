<?php

return [
    'model-label' => 'Situação',

    'plural-model-label' => 'Situações',

    'navigation' => [
        'title' => 'Situações',
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
                    'title' => 'Situação atualizada',
                    'body'  => 'A situação foi atualizada com sucesso.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Situação excluída',
                    'body'  => 'A situação foi excluída com sucesso.',
                ],
            ],
        ],

        'bulk-actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Situações excluídas',
                    'body'  => 'As situações selecionadas foram excluídas com sucesso.',
                ],
            ],
        ],
    ],
];
