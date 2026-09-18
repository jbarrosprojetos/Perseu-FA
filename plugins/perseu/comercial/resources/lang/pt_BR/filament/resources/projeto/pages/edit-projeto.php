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
            'form' => [
                'documento' => 'Template',
            ],
        ],
        'producao' => [
            'label' => 'Produção',
            'form' => [
                'otimizador' => 'Otimizador',
                'otimizador-options' => [
                    'nativa'         => 'Nativa',
                    'packing-solver' => 'Packing Solver (experimental)',
                ],
                'otimizador-ajuda' => 'Nativa é o algoritmo próprio do Perseu. Packing Solver é um motor externo em avaliação — pode falhar se não estiver configurado no servidor.',
                'tipo-equipamento'    => 'Equipamento de corte',
                'tipo-equipamento-options' => [
                    'serra' => 'Serra',
                    'cnc'   => 'CNC',
                ],
                'espessura-serra'  => 'Espessura da serra (mm)',
                'espessura-fresa'  => 'Espessura da fresa (mm)',
                'limpeza-bordas'   => 'Limpeza das bordas da chapa (mm)',
                'limpeza-bordas-ajuda' => 'Margem descontada do perímetro de cada chapa antes de encaixar as peças (aparas de esquadrejamento).',
            ],
            'notification-erro' => [
                'title' => 'Não foi possível gerar o Plano de Corte',
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
