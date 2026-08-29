<?php

return [
    'model-label' => 'Activity Log',

    'plural-model-label' => 'Audit',

    'navigation' => [
        'title' => 'Audit',
    ],

    'subject_types' => [
        'obra'            => 'Work',
        'tipo-obra'       => 'Work Type',
        'situacao-obra'   => 'Work Status',
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
        'filters' => [
            'modulo' => [
                'label' => 'Module',
            ],
            'subject_type' => [
                'label' => 'Record type',
            ],
            'busca' => [
                'label' => 'Search record',
                'valor' => 'Name, company name, number...',
            ],
        ],
    ],
];
