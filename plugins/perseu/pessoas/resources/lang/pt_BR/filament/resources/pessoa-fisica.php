<?php

return [
    'model-label' => 'Pessoa Física',

    'plural-model-label' => 'Pessoas Físicas',

    'navigation' => [
        'title' => 'Pessoas Físicas',
    ],

    'form' => [
        'nome'               => 'Nome',
        'categorias'         => 'Categorias',
        'telefone'           => 'Telefone',
        'telefone-whatsapp'  => 'É WhatsApp?',
        'email'              => 'E-mail',
        'cpf'                => 'CPF',
        'rg'                 => 'RG',
        'data-nascimento'    => 'Data de Nascimento',
        'estado-civil'       => 'Estado Civil',
        'sexo'               => 'Sexo',
        'profissao'          => 'Profissão',
        'observacoes'        => 'Observações',
    ],

    'table' => [
        'columns' => [
            'nome'         => 'Nome',
            'telefone'     => 'Telefone',
            'email'        => 'E-mail',
            'cpf'          => 'CPF',
            'estado-civil' => 'Estado Civil',
            'categorias'   => 'Categorias',
            'created-at'   => 'Criado em',
        ],

        'filters' => [
            'trashed' => 'Excluídos',
        ],

        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Pessoa física atualizada',
                    'body'  => 'A pessoa física foi atualizada com sucesso.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Pessoa física excluída',
                    'body'  => 'A pessoa física foi excluída com sucesso.',
                ],
            ],

            'restore' => [
                'notification' => [
                    'title' => 'Pessoa física restaurada',
                    'body'  => 'A pessoa física foi restaurada com sucesso.',
                ],
            ],

            'force-delete' => [
                'notification' => [
                    'title' => 'Pessoa física excluída definitivamente',
                    'body'  => 'A pessoa física e seus dados relacionados (endereços) foram excluídos definitivamente.',
                ],
            ],
        ],
    ],
];
