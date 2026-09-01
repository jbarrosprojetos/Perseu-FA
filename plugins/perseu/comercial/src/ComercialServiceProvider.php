<?php

namespace Perseu\Comercial;

use Filament\Panel;
use Illuminate\Support\Facades\Gate;
use Perseu\Comercial\Models\Obra;
use Perseu\Comercial\Models\ReferenciaPreco;
use Perseu\Comercial\Models\SituacaoObra;
use Perseu\Comercial\Models\TipoObra;
use Perseu\Comercial\Policies\ObraPolicy;
use Perseu\Comercial\Policies\ReferenciaPrecoPolicy;
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
                '2026_08_30_130000_create_referencias_precos_table',
                // As 3 entradas abaixo faltavam nesta lista desde suas
                // respectivas tarefas (rodadas na época via `artisan
                // migrate --path=`, que funciona independente deste
                // array) — sem elas, `loadMigrationsFrom()` nunca
                // carregava esses arquivos (ver
                // `Webkul\PluginManager\PackageServiceProvider::packageBooted()`),
                // então uma instalação nova do plugin (`comercial:install`)
                // ou `artisan migrate` num ambiente diferente NUNCA
                // rodaria essas 3 migrations. Corrigido ao mexer neste
                // array de novo (tarefa do Cluster Propostas, depois
                // revertido — esta correção ficou).
                '2026_08_30_140000_add_imposto_despesas_to_referencias_precos_table',
                '2026_08_30_150000_add_valor_pecas_fatores_to_referencias_precos_table',
                '2026_09_01_100000_drop_revisao_from_obras_table',
                '2026_09_02_100000_add_revisao_back_to_obras_table',
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
        Gate::policy(ReferenciaPreco::class, ReferenciaPrecoPolicy::class);
    }

    public function packageRegistered(): void
    {
        Panel::configureUsing(function (Panel $panel): void {
            $panel->plugin(ComercialPlugin::make());
        });
    }
}
