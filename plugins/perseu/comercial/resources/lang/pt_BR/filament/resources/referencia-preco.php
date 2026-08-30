<?php

return [
    'model-label' => 'Preço',

    'plural-model-label' => 'Preços',

    'navigation' => [
        'title' => 'Preços',
    ],

    'form' => [
        'descricao'             => 'Descrição da Referência',
        'laminacao'             => 'Laminação',
        'corte'                 => 'Corte',
        'hora-producao'         => 'Hora de Produção',
        'hora-execucao'         => 'Hora de Execução',
        'retencao-tecnica'      => 'Retenção Técnica',
        'imposto'               => 'Imposto',
        'despesas-variaveis'    => 'Despesas Variáveis',
        'despesas-fixas'        => 'Despesas Fixas',
        'unidade-metro-linear'  => '/m linear',
        'unidade-metro-quadrado' => '/m²',
    ],

    'table' => [
        'columns' => [
            'descricao'          => 'Descrição da Referência',
            'laminacao'          => 'Laminação (m linear)',
            'corte'              => 'Corte (m linear)',
            'hora-producao'      => 'Hora de Produção (m²)',
            'hora-execucao'      => 'Hora de Execução (m²)',
            'retencao-tecnica'   => 'Retenção Técnica',
            'imposto'            => 'Imposto',
            'despesas-variaveis' => 'Despesas Variáveis',
            'despesas-fixas'     => 'Despesas Fixas',
            'created-at'         => 'Criado em',
        ],

        'filters' => [
            'trashed' => 'Na lixeira',
        ],

        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Preço editado',
                    'body'  => 'A tabela de preços foi editada com sucesso.',
                ],
            ],
            'delete' => [
                'notification' => [
                    'title' => 'Preço excluído',
                    'body'  => 'A tabela de preços foi excluída com sucesso.',
                ],
            ],
            'restore' => [
                'notification' => [
                    'title' => 'Preço restaurado',
                    'body'  => 'A tabela de preços foi restaurada com sucesso.',
                ],
            ],
            'force-delete' => [
                'notification' => [
                    'title' => 'Preço excluído permanentemente',
                    'body'  => 'A tabela de preços foi excluída permanentemente com sucesso.',
                ],
            ],
        ],
    ],
];
