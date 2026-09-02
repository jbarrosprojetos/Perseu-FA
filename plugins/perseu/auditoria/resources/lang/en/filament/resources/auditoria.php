<?php

return [
    'model-label' => 'Activity Log',

    'plural-model-label' => 'Audit',

    'navigation' => [
        'title' => 'Audit',
    ],

    'subject_types' => [
        'projeto'          => 'Project',
        'tipo-projeto'     => 'Project Type',
        'situacao-projeto' => 'Project Status',
        'referencia-preco' => 'Price',
        'pessoa-fisica'   => 'Individual',
        'pessoa-juridica' => 'Company',
        'categoria-pessoa' => 'Person Category',
        'setor'           => 'Sector',
        'endereco'        => 'Address',
        'contato'         => 'Contact',
    ],

    'modulos' => [
        'comercial' => 'Sales',
        'pessoas'   => 'People',
    ],

    'table' => [
        'columns' => [
            'subject_type' => 'Record type',
            'subject_reference' => 'Record',
            'subject_reference_unavailable' => 'Permanently deleted record',
        ],
        'search_placeholder' => 'Search by name, company name or Project number...',
        'filters' => [
            'modulo' => [
                'label' => 'Module',
            ],
            'subject_type' => [
                'label' => 'Record type',
            ],
            'causer' => [
                'label' => 'User',
            ],
        ],
    ],
];
