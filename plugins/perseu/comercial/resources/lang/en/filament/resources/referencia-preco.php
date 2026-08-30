<?php

return [
    'model-label' => 'Price',

    'plural-model-label' => 'Prices',

    'navigation' => [
        'title' => 'Prices',
    ],

    'form' => [
        'descricao'             => 'Reference Description',
        'laminacao'             => 'Lamination',
        'corte'                 => 'Cutting',
        'hora-producao'         => 'Production Hour',
        'hora-execucao'         => 'Execution Hour',
        'retencao-tecnica'      => 'Technical Retention',
        'imposto'               => 'Tax',
        'despesas-variaveis'    => 'Variable Expenses',
        'despesas-fixas'        => 'Fixed Expenses',
        'unidade-metro-linear'  => '/linear m',
        'unidade-metro-quadrado' => '/m²',
    ],

    'table' => [
        'columns' => [
            'descricao'          => 'Reference Description',
            'laminacao'          => 'Lamination (linear m)',
            'corte'              => 'Cutting (linear m)',
            'hora-producao'      => 'Production Hour (m²)',
            'hora-execucao'      => 'Execution Hour (m²)',
            'retencao-tecnica'   => 'Technical Retention',
            'imposto'            => 'Tax',
            'despesas-variaveis' => 'Variable Expenses',
            'despesas-fixas'     => 'Fixed Expenses',
            'created-at'         => 'Created At',
        ],

        'filters' => [
            'trashed' => 'Trashed',
        ],

        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Price edited',
                    'body'  => 'The price table has been edited successfully.',
                ],
            ],
            'delete' => [
                'notification' => [
                    'title' => 'Price deleted',
                    'body'  => 'The price table has been deleted successfully.',
                ],
            ],
            'restore' => [
                'notification' => [
                    'title' => 'Price restored',
                    'body'  => 'The price table has been restored successfully.',
                ],
            ],
            'force-delete' => [
                'notification' => [
                    'title' => 'Price permanently deleted',
                    'body'  => 'The price table has been permanently deleted successfully.',
                ],
            ],
        ],
    ],
];
