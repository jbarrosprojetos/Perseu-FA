<?php

return [
    'model-label' => 'Tipo de Projeto',

    'plural-model-label' => 'Tipos de Projeto',

    'navigation' => [
        'title' => 'Tipos de Projeto',
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
                    'title' => 'Tipo de projeto atualizado',
                    'body'  => 'O tipo de projeto foi atualizado com sucesso.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Tipo de projeto excluído',
                    'body'  => 'O tipo de projeto foi excluído com sucesso.',
                ],
            ],
        ],

        'bulk-actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Tipos de projeto excluídos',
                    'body'  => 'Os tipos de projeto selecionados foram excluídos com sucesso.',
                ],
            ],
        ],
    ],
];
