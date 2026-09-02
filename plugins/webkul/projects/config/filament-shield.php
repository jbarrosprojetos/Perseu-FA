<?php

use Webkul\Project\Filament\Clusters\Configurations;
use Webkul\Project\Filament\Clusters\Configurations\Resources\ActivityPlanResource;
use Webkul\Project\Filament\Clusters\Configurations\Resources\MilestoneResource;
use Webkul\Project\Filament\Clusters\Configurations\Resources\ProcessoStageResource;
use Webkul\Project\Filament\Clusters\Configurations\Resources\TagResource;
use Webkul\Project\Filament\Clusters\Configurations\Resources\TaskStageResource;
use Webkul\Project\Filament\Resources\ProcessoResource;
use Webkul\Project\Filament\Resources\TaskResource;

$basic = ['view_any', 'view', 'create', 'update'];
$delete = ['delete', 'delete_any'];
$forceDelete = ['force_delete', 'force_delete_any'];
$restore = ['restore', 'restore_any'];
$reorder = ['reorder'];

return [
    'resources' => [
        'manage' => [
            MilestoneResource::class    => [...$basic, ...$delete],
            TagResource::class          => [...$basic, ...$delete, ...$restore, ...$forceDelete],
            ActivityPlanResource::class => [...$basic, ...$delete, ...$restore, ...$forceDelete],
            ProcessoStageResource::class => [...$basic, ...$delete, ...$restore, ...$forceDelete, ...$reorder],
            TaskStageResource::class    => [...$basic, ...$delete, ...$restore, ...$forceDelete, ...$reorder],
            ProcessoResource::class     => [...$basic, ...$delete, ...$restore, ...$forceDelete, ...$reorder],
            TaskResource::class         => [...$basic, ...$delete, ...$restore, ...$forceDelete, ...$reorder],
        ],
        'exclude' => [],
    ],

    'pages' => [
        'exclude' => [
            Configurations::class,
        ],
    ],
];
