<?php

use Perseu\Comercial\Filament\Clusters\Comercial\Resources\ProjetoResource;
use Perseu\Comercial\Filament\Clusters\Comercial\Resources\SituacaoProjetoResource;
use Perseu\Comercial\Filament\Clusters\Comercial\Resources\TipoProjetoResource;
use Perseu\Comercial\Filament\Clusters\Projetos;
use Perseu\Comercial\Filament\Clusters\Referencias;
use Perseu\Comercial\Filament\Clusters\Referencias\Resources\ReferenciaPrecoResource;

$basic = ['view_any', 'view', 'create', 'update'];
$delete = ['delete', 'delete_any'];
$forceDelete = ['force_delete', 'force_delete_any'];
$restore = ['restore', 'restore_any'];

return [
    'resources' => [
        'manage' => [
            SituacaoProjetoResource::class  => [...$basic, ...$delete],
            TipoProjetoResource::class      => [...$basic, ...$delete],
            ProjetoResource::class          => [...$basic, ...$delete, ...$restore, ...$forceDelete],
            ReferenciaPrecoResource::class => [...$basic, ...$delete, ...$restore, ...$forceDelete],
        ],
    ],

    // Projetos só agrupa Resources (nenhuma Page autônoma declara
    // $cluster = Projetos::class), então a heurística de auto-exclusão de
    // clusters do Shield (HasEntityTransformers::transformPages(), que só
    // enxerga clusters referenciados por Pages) nunca o detecta. A
    // visibilidade do Cluster no menu já é 100% controlada por
    // Cluster::canAccessClusteredComponents() (checa canAccess() de cada
    // Resource filho, ou seja, as Policies acima) — uma permissão de página
    // "Projetos Cluster" gerada aqui nunca seria checada em lugar nenhum, e
    // ficaria como um toggle morto na tela de Funções. Mesmo padrão já usado
    // pelo antigo PessoasCluster/ComercialCluster (removidos no commit
    // 26cfef4f7 por outro motivo — ver CLAUDE.md) e por outros clusters do
    // projeto (ex.: plugins/webkul/manufacturing/config/filament-shield.php).
    'pages' => [
        'exclude' => [
            Projetos::class,
            // Mesmo raciocínio acima, aplicado ao Cluster Referências.
            Referencias::class,
        ],
    ],
];
