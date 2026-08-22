<?php

return [
    'model-label' => 'Project',

    'plural-model-label' => 'Projects',

    'navigation' => [
        'title' => 'Projects',
    ],

    'form' => [
        'descricao'      => 'Project Name',
        'tipo-projeto'   => 'Project Type',
        'situacoes'      => 'Statuses',
        'tipo-contratante' => 'Client',
        'tipo-contratante-options' => [
            'pessoa-fisica'   => 'Individual',
            'pessoa-juridica' => 'Company',
        ],
        'pessoa-fisica'   => 'Client (Individual)',
        'pessoa-juridica' => 'Client (Company)',
        'contato'         => 'Contact',
        'endereco'        => 'Address',
        'endereco-form'   => [
            'cep'         => 'Zip Code',
            'logradouro'  => 'Street',
            'numero'      => 'Number',
            'complemento' => 'Complement',
            'bairro'      => 'Neighborhood',
            'municipio'   => 'City',
            'uf'          => 'State',
        ],
        'numero-projeto'          => 'Project Number',
        'numero-projeto-pendente' => 'Automatically generated on save',
        'revisao'                 => 'Revision',
        'data-cadastro'           => 'Registration Date',
        'data-cadastro-pendente'  => 'Automatically filled on save',
    ],

    'table' => [
        'columns' => [
            'numero-projeto' => 'Number',
            'descricao'      => 'Project Name',
            'tipo-projeto'   => 'Type',
            'contratante'    => 'Client',
            'situacoes'      => 'Statuses',
            'data-cadastro'  => 'Registration Date',
        ],

        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Project updated',
                    'body'  => 'The project has been updated successfully.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Project deleted',
                    'body'  => 'The project has been deleted successfully.',
                ],
            ],
        ],
    ],
];
