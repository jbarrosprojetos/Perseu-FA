<?php

return [
    'model-label' => 'Categoria',

    'plural-model-label' => 'Categorias',

    'navigation' => [
        'title' => 'Categorias',
    ],

    'form' => [
        'descricao'  => 'Descrição',
        'aplica-pf'  => 'Aplica-se a Pessoa Física',
        'aplica-pj'  => 'Aplica-se a Pessoa Jurídica',
        'e-cliente'  => 'É categoria de Cliente?',
    ],

    'table' => [
        'columns' => [
            'descricao'  => 'Descrição',
            'aplica-pf'  => 'Pessoa Física',
            'aplica-pj'  => 'Pessoa Jurídica',
            'e-cliente'  => 'Cliente',
            'created-at' => 'Criado em',
        ],

        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Categoria atualizada',
                    'body'  => 'A categoria foi atualizada com sucesso.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Categoria excluída',
                    'body'  => 'A categoria foi excluída com sucesso.',
                ],
            ],
        ],

        'bulk-actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Categorias excluídas',
                    'body'  => 'As categorias selecionadas foram excluídas com sucesso.',
                ],
            ],
        ],
    ],
];
