<?php

return [
    'model-label' => 'Projeto',

    'plural-model-label' => 'Projetos',

    'navigation' => [
        'title' => 'Projetos',
    ],

    'form' => [
        'descricao'      => 'Nome da Obra',
        'tipo-projeto'   => 'Tipo de Projeto',
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
        'numero-projeto'          => 'Projeto',
        'numero-projeto-pendente' => 'Gerado automaticamente ao salvar',
        'revisao'                 => 'Revisão',
        'data-cadastro'           => 'Cadastrado em:',
        'data-cadastro-pendente'  => 'Preenchida automaticamente ao salvar',
    ],

    'table' => [
        'columns' => [
            'numero-projeto' => 'Número',
            'descricao'      => 'Nome da Obra',
            'tipo-projeto'   => 'Tipo',
            'contratante'    => 'Contratante',
            'situacoes'      => 'Situações',
            'data-cadastro'  => 'Data de Cadastro',
        ],

        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Projeto atualizado',
                    'body'  => 'O projeto foi atualizado com sucesso.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Projeto excluído',
                    'body'  => 'O projeto foi excluído com sucesso.',
                ],
            ],
        ],
    ],
];
