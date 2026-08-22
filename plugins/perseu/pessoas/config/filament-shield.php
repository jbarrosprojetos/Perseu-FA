<?php

use Perseu\Pessoas\Filament\Clusters\Pessoas\Resources\CategoriaPessoaResource;
use Perseu\Pessoas\Filament\Clusters\Pessoas\Resources\PessoaFisicaResource;
use Perseu\Pessoas\Filament\Clusters\Pessoas\Resources\PessoaJuridicaResource;
use Perseu\Pessoas\Filament\Clusters\PessoasCluster;

$basic = ['view_any', 'view', 'create', 'update'];
$delete = ['delete', 'delete_any'];
$forceDelete = ['force_delete', 'force_delete_any'];
$restore = ['restore', 'restore_any'];

return [
    'resources' => [
        'manage' => [
            CategoriaPessoaResource::class => [...$basic, ...$delete],
            PessoaFisicaResource::class    => [...$basic, ...$delete, ...$restore, ...$forceDelete],
            PessoaJuridicaResource::class  => [...$basic, ...$delete, ...$restore, ...$forceDelete],
        ],
    ],

    // PessoasCluster só agrupa Resources (nenhuma Page autônoma declara
    // $cluster = PessoasCluster::class), então a heurística de
    // auto-exclusão de clusters do Shield (HasEntityTransformers::transformPages(),
    // que só enxerga clusters referenciados por Pages) nunca o detecta.
    // A visibilidade do Cluster no menu já é 100% controlada por
    // Cluster::canAccessClusteredComponents() (checa canAccess() de cada
    // Resource filho, ou seja, as Policies) — uma permissão de página
    // "Pessoas Cluster" gerada aqui nunca seria checada em lugar nenhum,
    // e ficaria como um toggle morto na tela de Funções. Mesmo padrão
    // já usado por outros clusters do projeto (ver
    // plugins/webkul/manufacturing/config/filament-shield.php).
    'pages' => [
        'exclude' => [
            PessoasCluster::class,
        ],
    ],
];
