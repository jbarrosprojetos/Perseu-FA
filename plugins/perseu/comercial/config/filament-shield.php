<?php

use Perseu\Comercial\Filament\Clusters\Comercial\Resources\ProjetoResource;
use Perseu\Comercial\Filament\Clusters\Comercial\Resources\SituacaoProjetoResource;
use Perseu\Comercial\Filament\Clusters\Comercial\Resources\TipoProjetoResource;

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
];
