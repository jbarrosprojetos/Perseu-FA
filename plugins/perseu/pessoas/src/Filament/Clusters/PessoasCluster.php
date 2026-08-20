<?php

namespace Perseu\Pessoas\Filament\Clusters;

use Filament\Clusters\Cluster;

class PessoasCluster extends Cluster
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    public static function getNavigationLabel(): string
    {
        return __('pessoas::clusters/pessoas.navigation.label');
    }
}
