<?php

use Perseu\Comercial\Filament\Clusters\Comercial\Resources\ProjetoResource;
use Perseu\Comercial\Filament\Clusters\Comercial\Resources\SituacaoProjetoResource;
use Perseu\Comercial\Filament\Clusters\Comercial\Resources\TipoProjetoResource;
use Perseu\Comercial\Filament\Clusters\ComercialCluster;

$basic = ['view_any', 'view', 'create', 'update'];
$delete = ['delete', 'delete_any'];
$forceDelete = ['force_delete', 'force_delete_any'];
$restore = ['restore', 'restore_any'];

return [
    'resources' => [
        'manage' => [
            SituacaoProjetoResource::class => [...$basic, ...$delete],
            TipoProjetoResource::class     => [...$basic, ...$delete],
            ProjetoResource::class         => [...$basic, ...$delete, ...$restore, ...$forceDelete],
        ],
    ],

    // Mesmo ajuste já aplicado em plugins/perseu/pessoas/config/filament-shield.php
    // para PessoasCluster: ComercialCluster só agrupa Resources (nenhuma Page
    // autônoma declara $cluster = ComercialCluster::class), então a heurística
    // de auto-exclusão de clusters do Shield (HasEntityTransformers::transformPages())
    // nunca o detecta, e uma permissão de página "Comercial Cluster" gerada
    // aqui nunca seria checada em lugar nenhum — a visibilidade do Cluster já
    // é 100% controlada por Cluster::canAccessClusteredComponents() (checa
    // canAccess() de cada Resource filho, ou seja, as Policies abaixo).
    'pages' => [
        'exclude' => [
            ComercialCluster::class,
        ],
    ],
];
