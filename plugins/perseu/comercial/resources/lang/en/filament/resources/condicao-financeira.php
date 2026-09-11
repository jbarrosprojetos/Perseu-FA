<?php

return [
    'model-label' => 'Financial Condition',

    'plural-model-label' => 'Financial Conditions',

    'navigation' => [
        'title' => 'Financial Conditions',
    ],

    'form' => [
        'descricao'                => 'Condition Description',
        'created-at'               => 'Created at',
        'created-at-pendente'      => 'Filled in automatically on save',
        'porcentagem-entrada'      => 'Down Payment Percentage',
        'qtde-parcelas'            => 'No. of Installments',
        'intervalo-dias'           => 'Interval in Days',
        'unidade-dias'             => 'days',
        'forma-pagamento'          => 'Payment Method',
        'taxa-mensal'              => 'Monthly Rate',
        'unidade-ao-mes'           => '% per month',
        'taxa-mensal-ajuda'        => 'Monthly interest rate, used to calculate each installment factor via the Price (French) amortization system.',
    ],

    'formas-pagamento' => [
        'pix-transferencia' => 'Pix / Bank Transfer',
        'boleto'            => 'Boleto',
        'cartao'            => 'Card',
    ],

    'table' => [
        'columns' => [
            'descricao'           => 'Condition Description',
            'created-at'          => 'Created at',
            'porcentagem-entrada' => 'Down Payment',
            'qtde-parcelas'       => 'Installments',
            'intervalo-dias'      => 'Interval (days)',
            'forma-pagamento'     => 'Payment Method',
            'taxa-mensal'         => 'Monthly Rate',
        ],

        'filters' => [
            'trashed' => 'Trashed',
        ],

        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Financial Condition edited',
                    'body'  => 'The financial condition was edited successfully.',
                ],
            ],
            'delete' => [
                'notification' => [
                    'title' => 'Financial Condition deleted',
                    'body'  => 'The financial condition was deleted successfully.',
                ],
            ],
            'restore' => [
                'notification' => [
                    'title' => 'Financial Condition restored',
                    'body'  => 'The financial condition was restored successfully.',
                ],
            ],
            'force-delete' => [
                'notification' => [
                    'title' => 'Financial Condition permanently deleted',
                    'body'  => 'The financial condition was permanently deleted successfully.',
                ],
            ],
        ],
    ],

    'notifications' => [
        'vinculada' => [
            'title' => 'Cannot change or delete',
            'body'  => 'This condition is linked to :count Project — unlink it before deleting or editing this financial condition.|This condition is linked to :count Projects — unlink them before deleting or editing this financial condition.',
        ],
        'vinculada-em-massa' => [
            'body' => 'The conditions ":descricoes" are linked to at least one Project — unlink them before deleting or editing.',
        ],
    ],
];
