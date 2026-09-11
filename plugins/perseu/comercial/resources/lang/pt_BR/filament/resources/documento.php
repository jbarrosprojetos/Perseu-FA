<?php

return [
    'model-label' => 'Documento',

    'plural-model-label' => 'Documentos',

    'navigation' => [
        'title' => 'Documentos',
    ],

    'form' => [
        'descricao' => 'Descrição Breve do Documento',
        'arquivo'   => 'Arquivo (Excel)',
    ],

    'table' => [
        'columns' => [
            'descricao'  => 'Descrição Breve do Documento',
            'created-at' => 'Criado em',
        ],

        'filters' => [
            'trashed' => 'Na lixeira',
        ],

        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Documento editado',
                    'body'  => 'O documento foi editado com sucesso.',
                ],
            ],
            'delete' => [
                'notification' => [
                    'title' => 'Documento excluído',
                    'body'  => 'O documento foi excluído com sucesso.',
                ],
            ],
            'restore' => [
                'notification' => [
                    'title' => 'Documento restaurado',
                    'body'  => 'O documento foi restaurado com sucesso.',
                ],
            ],
            'force-delete' => [
                'notification' => [
                    'title' => 'Documento excluído permanentemente',
                    'body'  => 'O documento foi excluído permanentemente com sucesso.',
                ],
            ],
        ],
    ],
];
