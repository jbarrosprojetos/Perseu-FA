<?php

return [
    'model-label' => 'Pessoa Jurídica',

    'plural-model-label' => 'Pessoas Jurídicas',

    'navigation' => [
        'title' => 'Pessoas Jurídicas',
    ],

    'form' => [
        'categorias'                    => 'Categorias',
        'setores'                       => 'Setores',
        'razao-social'                  => 'Razão Social',
        'nome-fantasia'                 => 'Nome Fantasia',
        'cnpj'                          => 'CNPJ',
        'cnpj-nao-encontrado'           => 'CNPJ não encontrado na Receita Federal — verifique o número ou continue o cadastro manualmente.',
        'telefone'                      => 'Telefone',
        'email'                         => 'E-mail',
        'inscricao-estadual'            => 'Inscrição Estadual',
        'indicador-contribuinte-icms'   => 'Contribuinte do ICMS',
        'cnae'                          => 'CNAE',
        'situacao-cadastral'            => 'Situação Cadastral',
        'regime-tributario'             => 'Regime Tributário',
        'data-abertura'                 => 'Data de Abertura',
        'porte'                         => 'Porte',
        'observacoes'                   => 'Observações',
    ],

    'table' => [
        'columns' => [
            'nome-fantasia'      => 'Nome Fantasia',
            'razao-social'       => 'Razão Social',
            'cnpj'               => 'CNPJ',
            'telefone'           => 'Telefone',
            'regime-tributario'  => 'Regime Tributário',
            'categorias'         => 'Categorias',
            'setores'            => 'Setores',
            'created-at'         => 'Criado em',
        ],

        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Pessoa jurídica atualizada',
                    'body'  => 'A pessoa jurídica foi atualizada com sucesso.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Pessoa jurídica excluída',
                    'body'  => 'A pessoa jurídica foi excluída com sucesso.',
                ],
            ],
        ],
    ],
];
