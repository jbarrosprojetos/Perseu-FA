<?php

namespace Perseu\Comercial;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Webkul\PluginManager\Package;

class ComercialPlugin implements Plugin
{
    public function getId(): string
    {
        return 'comercial';
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public function register(Panel $panel): void
    {
        if (! Package::isPluginInstalled($this->getId())) {
            return;
        }

        $panel
            ->when($panel->getId() == 'admin', function (Panel $panel) {
                $panel
                    ->discoverResources(
                        in: __DIR__.'/Filament/Resources',
                        for: 'Perseu\\Comercial\\Filament\\Resources'
                    )
                    ->discoverPages(
                        in: __DIR__.'/Filament/Pages',
                        for: 'Perseu\\Comercial\\Filament\\Pages'
                    )
                    ->discoverClusters(
                        in: __DIR__.'/Filament/Clusters',
                        for: 'Perseu\\Comercial\\Filament\\Clusters'
                    )
                    ->discoverWidgets(
                        in: __DIR__.'/Filament/Widgets',
                        for: 'Perseu\\Comercial\\Filament\\Widgets'
                    );
            });
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
