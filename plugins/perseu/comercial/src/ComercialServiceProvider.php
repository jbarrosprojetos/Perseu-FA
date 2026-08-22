<?php

namespace Perseu\Comercial;

use Filament\Panel;
use Illuminate\Support\Facades\Gate;
use Perseu\Comercial\Models\Projeto;
use Perseu\Comercial\Models\SituacaoProjeto;
use Perseu\Comercial\Models\TipoProjeto;
use Perseu\Comercial\Policies\ProjetoPolicy;
use Perseu\Comercial\Policies\SituacaoProjetoPolicy;
use Perseu\Comercial\Policies\TipoProjetoPolicy;
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
            ])
            ->runsMigrations()
            ->hasInstallCommand(function (InstallCommand $command) {
                $command->runsMigrations();
            })
            ->hasUninstallCommand(function (UninstallCommand $command) {});
    }

    public function packageBooted(): void
    {
        Gate::policy(SituacaoProjeto::class, SituacaoProjetoPolicy::class);
        Gate::policy(TipoProjeto::class, TipoProjetoPolicy::class);
        Gate::policy(Projeto::class, ProjetoPolicy::class);
    }

    public function packageRegistered(): void
    {
        Panel::configureUsing(function (Panel $panel): void {
            $panel->plugin(ComercialPlugin::make());
        });
    }
}
