<?php

namespace Perseu\Comercial;

use Filament\Panel;
use Illuminate\Support\Facades\Gate;
use Perseu\Comercial\Models\Obra;
use Perseu\Comercial\Models\SituacaoObra;
use Perseu\Comercial\Models\TipoObra;
use Perseu\Comercial\Policies\ObraPolicy;
use Perseu\Comercial\Policies\SituacaoObraPolicy;
use Perseu\Comercial\Policies\TipoObraPolicy;
use Webkul\PluginManager\Console\Commands\InstallCommand;
use Webkul\PluginManager\Console\Commands\UninstallCommand;
use Webkul\PluginManager\Package;
use Webkul\PluginManager\PackageServiceProvider;

class ComercialServiceProvider extends PackageServiceProvider
{
    public static string $name = 'comercial';

    public function configureCustomPackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasTranslations()
            ->hasMigrations([
                '2026_08_22_110000_create_situacoes_projeto_table',
                '2026_08_22_110001_create_tipos_projeto_table',
                '2026_08_22_110002_create_projeto_numero_sequencias_table',
                '2026_08_22_110003_create_projetos_table',
                '2026_08_22_110004_create_projeto_situacao_table',
                '2026_08_28_120000_rename_projeto_to_obra',
            ])
            ->runsMigrations()
            ->hasDependency('auditoria')
            ->hasInstallCommand(function (InstallCommand $command) {
                $command->runsMigrations();
            })
            ->hasUninstallCommand(function (UninstallCommand $command) {});
    }

    public function packageBooted(): void
    {
        Gate::policy(SituacaoObra::class, SituacaoObraPolicy::class);
        Gate::policy(TipoObra::class, TipoObraPolicy::class);
        Gate::policy(Obra::class, ObraPolicy::class);
    }

    public function packageRegistered(): void
    {
        Panel::configureUsing(function (Panel $panel): void {
            $panel->plugin(ComercialPlugin::make());
        });
    }
}
