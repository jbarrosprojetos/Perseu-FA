<?php

use Perseu\Pessoas\Filament\Clusters\Pessoas\Resources\CategoriaPessoaResource;
use Perseu\Pessoas\Filament\Clusters\Pessoas\Resources\PessoaFisicaResource;
use Perseu\Pessoas\Filament\Clusters\Pessoas\Resources\PessoaJuridicaResource;
use Perseu\Pessoas\Filament\Clusters\Pessoas\Resources\SetorResource;

$basic = ['view_any', 'view', 'create', 'update'];
$delete = ['delete', 'delete_any'];
$forceDelete = ['force_delete', 'force_delete_any'];
$restore = ['restore', 'restore_any'];

return [
    'resources' => [
        'manage' => [
            CategoriaPessoaResource::class => [...$basic, ...$delete],
            SetorResource::class           => [...$basic, ...$delete],
            PessoaFisicaResource::class    => [...$basic, ...$delete, ...$restore, ...$forceDelete],
            PessoaJuridicaResource::class  => [...$basic, ...$delete, ...$restore, ...$forceDelete],
        ],
    ],
];
