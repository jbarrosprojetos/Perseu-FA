<?php

namespace Perseu\Auditoria;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Webkul\PluginManager\Package;

/**
 * Registra só a estrutura própria do plugin (Policies via
 * AuditoriaServiceProvider::packageBooted() — nenhum Resource/Page
 * autônomo aqui). O Resource de Auditoria em si
 * (`Filament\Resources\AuditoriaResource`) é registrado separadamente
 * via `Rmsramos\Activitylog\ActivitylogPlugin::make()->resource(...)`
 * (ver AuditoriaServiceProvider::packageRegistered()) — não pelo
 * `discoverResources()` deste plugin, para não arriscar registro
 * duplicado (o `ActivitylogPlugin` já registra a classe explicitamente
 * via `$panel->resources([...])`).
 */
class AuditoriaPlugin implements Plugin
{
    public function getId(): string
    {
        return 'auditoria';
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
                    ->discoverPages(
                        in: __DIR__.'/Filament/Pages',
                        for: 'Perseu\\Auditoria\\Filament\\Pages'
                    )
                    ->discoverWidgets(
                        in: __DIR__.'/Filament/Widgets',
                        for: 'Perseu\\Auditoria\\Filament\\Widgets'
                    );
            });
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
