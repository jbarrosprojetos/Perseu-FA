<?php

namespace Perseu\Comercial\Filament\Clusters;

use Filament\Clusters\Cluster;

class ComercialCluster extends Cluster
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-briefcase';

    public static function getNavigationLabel(): string
    {
        return __('comercial::clusters/comercial.navigation.label');
    }
}
