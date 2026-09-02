<?php

namespace Perseu\Comercial\Filament\Clusters;

use Filament\Clusters\Cluster;
use Webkul\Support\Enums\NavigationGroup;

class Projetos extends Cluster
{
    protected static ?string $slug = 'comercial';

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('comercial::filament/clusters/projetos.navigation.title');
    }

    public static function getClusterBreadcrumb(): ?string
    {
        return static::getNavigationLabel();
    }

    public static function getNavigationGroup(): string|\UnitEnum
    {
        return NavigationGroup::Comercial;
    }
}
