<?php

return [
    'title' => 'Contatos',

    'form' => [
        'pessoa-fisica' => 'Pessoa Física',
        'cargo'         => 'Cargo/Função',
    ],

    'table' => [
        'columns' => [
            'nome'     => 'Nome',
            'email'    => 'E-mail',
            'telefone' => 'Telefone',
            'cargo'    => 'Cargo',
        ],

        'header-actions' => [
            'create' => [
                'label' => 'Novo contato',

                'notification' => [
                    'title' => 'Contato adicionado',
                    'body'  => 'O contato foi adicionado com sucesso.',
                ],
            ],
        ],

        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Contato atualizado',
                    'body'  => 'O contato foi atualizado com sucesso.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Contato removido',
                    'body'  => 'O contato foi removido com sucesso.',
                ],
            ],
        ],
    ],
];
