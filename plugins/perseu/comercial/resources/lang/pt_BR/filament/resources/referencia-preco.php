<?php

return [
    'model-label' => 'Preço',

    'plural-model-label' => 'Preços',

    'navigation' => [
        'title' => 'Preços',
    ],

    'form' => [
        'descricao'             => 'Descrição da Referência',
        'created-at'            => 'Criado em',
        'created-at-pendente'   => 'Preenchido automaticamente ao salvar',
        'laminacao'             => 'Laminação',
        'corte'                 => 'Corte',
        'hora-producao'         => 'Hora de Produção',
        'hora-execucao'         => 'Hora de Execução',
        'retencao-tecnica'      => 'Retenção Técnica',
        'imposto'               => 'Imposto',
        'despesas-variaveis'    => 'Despesas Variáveis',
        'despesas-fixas'        => 'Despesas Fixas',
        'valor-pecas'           => 'Valor por Peças',
        'fator-madeiras'        => 'Fator Madeiras',
        'fator-ferragens-miscelanias' => 'Fator Ferragens e Miscelânias',
        'fator-mao-obra'        => 'Fator Mão de Obra',
        'unidade-metro-linear'  => '/m linear',
        'unidade-metro-quadrado' => '/m²',
    ],

    'table' => [
        'columns' => [
            'descricao'          => 'Descrição da Referência',
            'laminacao'          => 'Laminação (m linear)',
            'corte'              => 'Corte (m linear)',
            'hora-producao'      => 'Hora de Produção (m²)',
            'hora-execucao'      => 'Hora de Execução (m²)',
            'retencao-tecnica'   => 'Retenção Técnica',
            'imposto'            => 'Imposto',
            'despesas-variaveis' => 'Despesas Variáveis',
            'despesas-fixas'     => 'Despesas Fixas',
            'valor-pecas'        => 'Valor por Peças',
            'fator-madeiras'     => 'Fator Madeiras',
            'fator-ferragens-miscelanias' => 'Fator Ferragens e Miscelânias',
            'fator-mao-obra'     => 'Fator Mão de Obra',
            'created-at'         => 'Criado em',
        ],

        'filters' => [
            'trashed' => 'Na lixeira',
        ],

        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Preço editado',
                    'body'  => 'A tabela de preços foi editada com sucesso.',
                ],
            ],
            'delete' => [
                'notification' => [
                    'title' => 'Preço excluído',
                    'body'  => 'A tabela de preços foi excluída com sucesso.',
                ],
            ],
            'restore' => [
                'notification' => [
                    'title' => 'Preço restaurado',
                    'body'  => 'A tabela de preços foi restaurada com sucesso.',
                ],
            ],
            'force-delete' => [
                'notification' => [
                    'title' => 'Preço excluído permanentemente',
                    'body'  => 'A tabela de preços foi excluída permanentemente com sucesso.',
                ],
            ],
        ],
    ],

    'notifications' => [
        'vinculada' => [
            'title' => 'Não é possível alterar ou excluir',
            'body'  => 'Esta referência está vinculada a :count Projeto — desvincule-o antes de excluir ou editar esta tabela de preços.|Esta referência está vinculada a :count Projetos — desvincule-os antes de excluir ou editar esta tabela de preços.',
        ],
        'vinculada-em-massa' => [
            'body' => 'As referências ":descricoes" estão vinculadas a pelo menos um Projeto — desvincule-as antes de excluir ou editar.',
        ],
    ],
];
