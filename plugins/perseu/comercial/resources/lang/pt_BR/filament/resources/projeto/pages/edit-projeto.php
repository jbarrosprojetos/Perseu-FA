<?php

return [
    'header-actions' => [
        'delete' => [
            'notification' => [
                'title' => 'Projeto excluído',
                'body'  => 'O projeto foi excluído com sucesso.',
            ],
        ],
    ],

    'notification' => [
        'title' => 'Projeto atualizado',
        'body'  => 'O projeto foi atualizado com sucesso.',
    ],

    'form-actions' => [
        'documentos' => [
            'label' => 'Documentos',
            'notification' => [
                'title' => 'Ainda não implementado',
                'body'  => 'A geração de documentos a partir de templates para este Projeto será implementada numa próxima etapa.',
            ],
        ],
        'atribuir-processos' => [
            'label' => 'Atribuir Processos',
            'notification' => [
                'title' => 'Ainda não implementado',
                'body'  => 'A ação de atribuir Processos a este Projeto será implementada numa próxima etapa.',
            ],
        ],
    ],
];
