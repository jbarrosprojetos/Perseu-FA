<?php

return [
    'model-label' => 'Pessoa Física',

    'plural-model-label' => 'Pessoas Físicas',

    'navigation' => [
        'title' => 'Pessoas Físicas',
    ],

    'form' => [
        'nome'               => 'Nome',
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

        'sections' => [
            'enderecos' => [
                'title'       => 'Endereços',
                'description' => 'Disponível após salvar o cadastro',
            ],
        ],
    ],

    'table' => [
        'columns' => [
            'nome'         => 'Nome',
            'telefone'     => 'Telefone',
            'email'        => 'E-mail',
            'cpf'          => 'CPF',
            'estado-civil' => 'Estado Civil',
            'created-at'   => 'Criado em',
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
        ],
    ],
];
