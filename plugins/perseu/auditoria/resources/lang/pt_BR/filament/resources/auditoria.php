<?php

return [
    'model-label' => 'Log de Atividade',

    'plural-model-label' => 'Auditoria',

    'navigation' => [
        'title' => 'Auditoria',
    ],

    // Rótulo amigável por cadastro de origem (subject_type) — ver
    // Perseu\Auditoria\Support\SubjectTypeCatalog::label(). Mantido em
    // sincronia com os `model-label`/`plural-model-label` de cada
    // Resource do cadastro correspondente (mesmo texto, fonte
    // duplicada de propósito — a central de Auditoria não deve
    // depender de chamar Resources de outros plugins pra montar seus
    // próprios rótulos).
    'subject_types' => [
        'projeto'          => 'Projeto',
        'tipo-projeto'     => 'Tipo de Projeto',
        'situacao-projeto' => 'Situação de Projeto',
        'referencia-preco' => 'Preço',
        'pessoa-fisica'   => 'Pessoa Física',
        'pessoa-juridica' => 'Pessoa Jurídica',
        'categoria-pessoa' => 'Categoria de Pessoa',
        'setor'           => 'Setor',
        'endereco'        => 'Endereço',
        'contato'         => 'Contato',
    ],

    'modulos' => [
        'comercial' => 'Comercial',
        'pessoas'   => 'Pessoas',
    ],

    'table' => [
        'columns' => [
            'subject_type' => 'Cadastro',
            'subject_reference' => 'Registro',
            'subject_reference_unavailable' => 'Registro excluído definitivamente',
        ],
        // Caixa "Pesquisar" padrão do Filament (topo da tabela) —
        // busca unificada por nome/razão social/número, ver
        // AuditoriaResource::getSubjectReferenceColumnComponent().
        'search_placeholder' => 'Buscar por nome, razão social ou número do Projeto...',
        'filters' => [
            'modulo' => [
                'label' => 'Módulo',
            ],
            'subject_type' => [
                'label' => 'Cadastro',
            ],
            'causer' => [
                'label' => 'Usuário',
            ],
        ],
    ],
];
