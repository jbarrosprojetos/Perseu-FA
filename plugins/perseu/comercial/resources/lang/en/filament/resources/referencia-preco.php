<?php

return [
    'model-label' => 'Price',

    'plural-model-label' => 'Prices',

    'navigation' => [
        'title' => 'Prices',
    ],

    'form' => [
        'descricao'             => 'Reference Description',
        'created-at'            => 'Created At',
        'created-at-pendente'   => 'Automatically filled on save',
        'laminacao'             => 'Lamination',
        'corte'                 => 'Cutting',
        'hora-producao'         => 'Production Hour',
        'hora-execucao'         => 'Execution Hour',
        'retencao-tecnica'      => 'Technical Retention',
        'imposto'               => 'Tax',
        'despesas-variaveis'    => 'Variable Expenses',
        'despesas-fixas'        => 'Fixed Expenses',
        'valor-pecas'           => 'Value per Pieces',
        'fator-madeiras'        => 'Wood Factor',
        'fator-ferragens-miscelanias' => 'Hardware & Miscellaneous Factor',
        'fator-mao-obra'        => 'Labor Factor',
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
            'valor-pecas'        => 'Value per Pieces',
            'fator-madeiras'     => 'Wood Factor',
            'fator-ferragens-miscelanias' => 'Hardware & Miscellaneous Factor',
            'fator-mao-obra'     => 'Labor Factor',
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

    'notifications' => [
        'vinculada' => [
            'title' => 'Cannot change or delete',
            'body'  => 'This reference is linked to :count Project — unlink it before deleting or editing this price table.|This reference is linked to :count Projects — unlink them before deleting or editing this price table.',
        ],
        'vinculada-em-massa' => [
            'body' => 'The references ":descricoes" are linked to at least one Project — unlink them before deleting or editing.',
        ],
    ],
];
