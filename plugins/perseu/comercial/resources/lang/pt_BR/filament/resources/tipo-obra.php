<?php

return [
    'model-label' => 'Tipo de Obra',

    'plural-model-label' => 'Tipos de Obra',

    'navigation' => [
        'title' => 'Tipos de Obra',
    ],

    'form' => [
        'codigo'    => 'Código',
        'descricao' => 'Descrição',
    ],

    'table' => [
        'columns' => [
            'codigo'    => 'Código',
            'descricao' => 'Descrição',
        ],

        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Tipo de obra atualizado',
                    'body'  => 'O tipo de obra foi atualizado com sucesso.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Tipo de obra excluído',
                    'body'  => 'O tipo de obra foi excluído com sucesso.',
                ],
            ],
        ],

        'bulk-actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Tipos de obra excluídos',
                    'body'  => 'Os tipos de obra selecionados foram excluídos com sucesso.',
                ],
            ],
        ],
    ],
];
