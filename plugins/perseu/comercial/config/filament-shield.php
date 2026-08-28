<?php

use Perseu\Comercial\Filament\Clusters\Comercial\Resources\ObraResource;
use Perseu\Comercial\Filament\Clusters\Comercial\Resources\SituacaoObraResource;
use Perseu\Comercial\Filament\Clusters\Comercial\Resources\TipoObraResource;

$basic = ['view_any', 'view', 'create', 'update'];
$delete = ['delete', 'delete_any'];
$forceDelete = ['force_delete', 'force_delete_any'];
$restore = ['restore', 'restore_any'];

return [
    'resources' => [
        'manage' => [
            SituacaoObraResource::class => [...$basic, ...$delete],
            TipoObraResource::class     => [...$basic, ...$delete],
            ObraResource::class         => [...$basic, ...$delete, ...$restore, ...$forceDelete],
        ],
    ],
];
