<?php

return [
    'header-actions' => [
        'delete' => [
            'notification' => [
                'title' => 'Project deleted',
                'body'  => 'The project has been deleted successfully.',
            ],
        ],
    ],

    'notification' => [
        'title' => 'Project updated',
        'body'  => 'The project has been updated successfully.',
    ],

    'form-actions' => [
        'documentos' => [
            'label' => 'Documents',
            'form' => [
                'documento' => 'Template',
            ],
        ],
        'producao' => [
            'label' => 'Production',
            'form' => [
                'otimizador' => 'Optimizer',
                'otimizador-options' => [
                    'nativa'         => 'Native',
                    'packing-solver' => 'Packing Solver (experimental)',
                ],
                'otimizador-ajuda' => 'Native is Perseu\'s own algorithm. Packing Solver is an external engine under evaluation — it may fail if not configured on the server.',
                'tipo-equipamento'    => 'Cutting equipment',
                'tipo-equipamento-options' => [
                    'serra' => 'Saw',
                    'cnc'   => 'CNC',
                ],
                'espessura-serra'  => 'Saw kerf (mm)',
                'espessura-fresa'  => 'Router bit width (mm)',
                'limpeza-bordas'   => 'Sheet edge trim (mm)',
                'limpeza-bordas-ajuda' => 'Margin trimmed from the perimeter of every sheet before nesting the pieces.',
            ],
            'notification-erro' => [
                'title' => 'Could not generate the Cutting Plan',
            ],
        ],
        'atribuir-processos' => [
            'label' => 'Assign Processes',
            'notification' => [
                'title' => 'Not implemented yet',
                'body'  => 'Assigning Processes to this Project will be implemented in a future step.',
            ],
        ],
    ],
];
