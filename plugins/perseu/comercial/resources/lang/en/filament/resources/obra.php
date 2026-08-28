<?php

return [
    'model-label' => 'Job',

    'plural-model-label' => 'Jobs',

    'navigation' => [
        'title' => 'Jobs',
    ],

    'form' => [
        'descricao'      => 'Job Name',
        'tipo-obra'      => 'Job Type',
        'situacoes'      => 'Statuses',
        'tipo-contratante' => 'Client',
        'tipo-contratante-options' => [
            'pessoa-fisica'   => 'Individual',
            'pessoa-juridica' => 'Company',
        ],
        'pessoa-fisica'   => 'Client (Individual)',
        'pessoa-juridica' => 'Client (Company)',
        'contato'         => 'Contact',
        'contato-email'   => 'Email',
        'contato-telefone' => 'Phone',
        'endereco'        => 'Job Address',
        'endereco-form'   => [
            'cep'         => 'Zip Code',
            'logradouro'  => 'Street',
            'numero'      => 'Number',
            'complemento' => 'Complement',
            'bairro'      => 'Neighborhood',
            'municipio'   => 'City',
            'uf'          => 'State',
        ],
        'numero-obra'          => 'Job',
        'numero-obra-pendente' => 'Automatically generated on save',
        'revisao'                 => 'Revision',
        'data-cadastro'           => 'Registered on:',
        'data-cadastro-pendente'  => 'Automatically filled on save',
    ],

    'table' => [
        'columns' => [
            'numero-obra' => 'Number',
            'descricao'      => 'Job Name',
            'tipo-obra'      => 'Type',
            'contratante'    => 'Client',
            'situacoes'      => 'Statuses',
            'data-cadastro'  => 'Registration Date',
        ],

        'filters' => [
            'trashed' => 'Deleted',
        ],

        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Job updated',
                    'body'  => 'The job has been updated successfully.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Job deleted',
                    'body'  => 'The job has been deleted successfully.',
                ],
            ],

            'restore' => [
                'notification' => [
                    'title' => 'Job restored',
                    'body'  => 'The job has been restored successfully.',
                ],
            ],

            'force-delete' => [
                'notification' => [
                    'title' => 'Job permanently deleted',
                    'body'  => 'The job has been permanently deleted.',
                ],
            ],
        ],
    ],
];
