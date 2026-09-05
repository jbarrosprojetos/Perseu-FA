<?php

return [
    'model-label' => 'Project',

    'plural-model-label' => 'Projects',

    'navigation' => [
        'title' => 'Projects',
    ],

    'form' => [
        'sections' => [
            'cabecalho' => [
                'title'       => 'Project Data',
                'description' => 'Administrative information for the Project — client, job address and status.',
            ],
            'itens' => [
                'title'       => 'Items',
                'description' => 'Items that make up the Project, from different sources.',
            ],
        ],
        'itens' => [
            'origem'            => 'Item Source',
            'inserir'           => 'Insert',
            'mobilizacao-frete' => 'Mobilization and Freight',
            'confirmar'         => 'Confirm',
            'editar'            => 'Edit',
            'excluir'           => 'Delete',
            'descricao-atalhos' => 'Use keyboard shortcuts to format the text, if needed: Ctrl+B (bold), Ctrl+I (italic), Ctrl+U (underline).',
            'referencia-tooltip'     => 'Registered Line Product Code',
            'porcentagem-tooltip'    => 'Markup or Discount Percentage',
            'custo-unitario-tooltip' => 'Cost Value Entered or Imported',
            'excluir-confirmacao' => [
                'heading'     => 'Delete item :numero?',
                'description' => 'The following items in this Project will be renumbered to close the gap in the sequence. This action cannot be undone.',
            ],
            'validacao' => [
                'descricao-obrigatoria'      => 'Fill in the item\'s Description.',
                'quantidade-obrigatoria'     => 'Enter a Quantity greater than zero.',
                'custo-unitario-obrigatorio' => 'Enter a Unit Cost greater than zero.',
            ],
            'origens'  => [
                'item-avulso'       => 'Standalone Item',
                'item-linha'        => 'Line Item',
                'promob-plus'       => 'Promob Plus',
                'promob-start'      => 'Promob Start',
                'sketchup-hellomob' => 'Sketchup Hellomob',
                'sketchup-cutlist'  => 'Sketchup CutList',
                'cortcloud'         => 'CortCloud',
            ],
            'notification' => [
                'sem-selecao'              => 'Select a source before inserting.',
                'pendente-title'           => 'Insertion not implemented yet',
                'pendente-body'            => "Insert action for ':origem' will be implemented later.",
                'projeto-nao-salvo-title'  => 'Save the Project first',
                'projeto-nao-salvo-body'   => 'The Project data (Header) must be saved before inserting items.',
                'item-avulso-confirmado'   => 'Item saved successfully.',
                'item-excluido'            => 'Item deleted successfully.',
            ],
            'cabecalho-item-avulso' => [
                'item'            => 'Item',
                'referencia'      => 'Reference',
                'descricao'       => 'Description',
                'quantidade'      => 'Qty.',
                'valor-unitario'  => 'Unit Price',
                'valor-total'     => 'Total Price',
                'porcentagem'     => '%',
                'custo-unitario'  => 'Unit Cost',
            ],
        ],
        'descricao'      => 'Job Name',
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
        'endereco-sem-tag-obra' => 'This client has no address tagged as Job. Add that tag to an address in the client\'s own record, or use the "+" above to create one here.',
        'referencia-preco'        => 'Price Reference',
        'referencia-preco-aviso'  => 'No price reference selected — required to calculate the sale value',
        'numero-projeto'          => 'Project',
        'numero-projeto-pendente' => 'Automatically generated on save',
        'revisao'                 => 'Revision',
        'data-cadastro'           => 'Registered on:',
        'data-cadastro-pendente'  => 'Automatically filled on save',
    ],

    'table' => [
        'columns' => [
            'numero-projeto' => 'Number',
            'descricao'      => 'Job Name',
            'tipo-projeto'   => 'Type',
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

            'restore' => [
                'notification' => [
                    'title' => 'Project restored',
                    'body'  => 'The project has been restored successfully.',
                ],
            ],

            'force-delete' => [
                'notification' => [
                    'title' => 'Project permanently deleted',
                    'body'  => 'The project has been permanently deleted.',
                ],
            ],
        ],
    ],
];
