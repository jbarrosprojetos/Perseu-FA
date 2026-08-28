<?php

return [
    'model-label' => 'Obra',

    'plural-model-label' => 'Obras',

    'navigation' => [
        'title' => 'Obras',
    ],

    'form' => [
        'descricao'      => 'Nome da Obra',
        'tipo-obra'      => 'Tipo de Obra',
        'situacoes'      => 'Situações',
        'tipo-contratante' => 'Contratante',
        'tipo-contratante-options' => [
            'pessoa-fisica'   => 'Pessoa Física',
            'pessoa-juridica' => 'Pessoa Jurídica',
        ],
        'pessoa-fisica'   => 'Cliente (Pessoa Física)',
        'pessoa-juridica' => 'Cliente (Pessoa Jurídica)',
        'contato'         => 'Contato',
        'contato-email'   => 'E-mail',
        'contato-telefone' => 'Telefone',
        'endereco'        => 'Endereço da Obra',
        'endereco-form'   => [
            'cep'         => 'CEP',
            'logradouro'  => 'Logradouro',
            'numero'      => 'Número',
            'complemento' => 'Complemento',
            'bairro'      => 'Bairro',
            'municipio'   => 'Município',
            'uf'          => 'UF',
        ],
        'numero-obra'          => 'Obra',
        'numero-obra-pendente' => 'Gerado automaticamente ao salvar',
        'revisao'                 => 'Revisão',
        'data-cadastro'           => 'Cadastrado em:',
        'data-cadastro-pendente'  => 'Preenchida automaticamente ao salvar',
    ],

    'table' => [
        'columns' => [
            'numero-obra' => 'Número',
            'descricao'      => 'Nome da Obra',
            'tipo-obra'      => 'Tipo',
            'contratante'    => 'Contratante',
            'situacoes'      => 'Situações',
            'data-cadastro'  => 'Data de Cadastro',
        ],

        'filters' => [
            'trashed' => 'Excluídos',
        ],

        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Obra atualizada',
                    'body'  => 'A obra foi atualizada com sucesso.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Obra excluída',
                    'body'  => 'A obra foi excluída com sucesso.',
                ],
            ],

            'restore' => [
                'notification' => [
                    'title' => 'Obra restaurada',
                    'body'  => 'A obra foi restaurada com sucesso.',
                ],
            ],

            'force-delete' => [
                'notification' => [
                    'title' => 'Obra excluída definitivamente',
                    'body'  => 'A obra foi excluída definitivamente.',
                ],
            ],
        ],
    ],
];
