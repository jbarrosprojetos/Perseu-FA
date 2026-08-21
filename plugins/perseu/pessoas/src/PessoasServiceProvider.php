<?php

namespace Perseu\Pessoas;

use Filament\Panel;
use Illuminate\Support\Facades\Gate;
use Perseu\Pessoas\Models\CategoriaPessoa;
use Perseu\Pessoas\Models\PessoaFisica;
use Perseu\Pessoas\Models\PessoaJuridica;
use Perseu\Pessoas\Policies\CategoriaPessoaPolicy;
use Perseu\Pessoas\Policies\PessoaFisicaPolicy;
use Perseu\Pessoas\Policies\PessoaJuridicaPolicy;
use Webkul\PluginManager\Console\Commands\InstallCommand;
use Webkul\PluginManager\Console\Commands\UninstallCommand;
use Webkul\PluginManager\Package;
use Webkul\PluginManager\PackageServiceProvider;

class PessoasServiceProvider extends PackageServiceProvider
{
    public static string $name = 'pessoas';

    public function configureCustomPackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasTranslations()
            ->hasMigrations([
                '2026_08_20_100000_create_categorias_pessoa_table',
                '2026_08_20_100001_create_pessoas_fisicas_table',
                '2026_08_20_100002_create_pessoas_juridicas_table',
                '2026_08_20_100003_create_enderecos_table',
                '2026_08_20_100004_create_pessoa_fisica_categoria_table',
                '2026_08_20_100005_create_pessoa_juridica_categoria_table',
                '2026_08_20_100006_create_pessoa_fisica_endereco_table',
                '2026_08_20_100007_create_pessoa_juridica_endereco_table',
                '2026_08_20_100008_create_contatos_table',
            ])
            ->runsMigrations()
            ->hasInstallCommand(function (InstallCommand $command) {
                $command->runsMigrations();
            })
            ->hasUninstallCommand(function (UninstallCommand $command) {});
    }

    public function packageBooted(): void
    {
        Gate::policy(CategoriaPessoa::class, CategoriaPessoaPolicy::class);
        Gate::policy(PessoaFisica::class, PessoaFisicaPolicy::class);
        Gate::policy(PessoaJuridica::class, PessoaJuridicaPolicy::class);
    }

    public function packageRegistered(): void
    {
        Panel::configureUsing(function (Panel $panel): void {
            $panel->plugin(PessoasPlugin::make());
        });
    }
}
