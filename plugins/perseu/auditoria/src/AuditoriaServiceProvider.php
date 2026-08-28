<?php

namespace Perseu\Auditoria;

use Filament\Panel;
use Illuminate\Support\Facades\Gate;
use Perseu\Auditoria\Filament\Resources\AuditoriaResource;
use Perseu\Auditoria\Policies\ActivityPolicy;
use Rmsramos\Activitylog\ActivitylogPlugin;
use Spatie\Activitylog\Models\Activity;
use Webkul\PluginManager\Console\Commands\InstallCommand;
use Webkul\PluginManager\Console\Commands\UninstallCommand;
use Webkul\PluginManager\Package;
use Webkul\PluginManager\PackageServiceProvider;

/**
 * As migrations do `activity_log` (spatie/laravel-activitylog) já foram
 * publicadas para `database/migrations/` do app e rodadas via
 * `artisan migrate` normal — não são declaradas aqui via
 * `hasMigrations()`/`runsMigrations()` de propósito: é infraestrutura
 * compartilhada por TODOS os plugins que auditam Models (não dado
 * específico deste plugin), então não faz sentido ficar atrelada ao
 * ciclo de instalar/desinstalar do plugin-manager (desinstalar
 * "auditoria" não deveria arriscar dropar a tabela de log de outros
 * módulos).
 */
class AuditoriaServiceProvider extends PackageServiceProvider
{
    public static string $name = 'auditoria';

    public function configureCustomPackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasTranslations()
            ->hasInstallCommand(function (InstallCommand $command) {})
            ->hasUninstallCommand(function (UninstallCommand $command) {});
    }

    public function packageBooted(): void
    {
        Gate::policy(Activity::class, ActivityPolicy::class);
    }

    public function packageRegistered(): void
    {
        Panel::configureUsing(function (Panel $panel): void {
            $panel->plugin(AuditoriaPlugin::make());

            // Auditoria é uma tela interna (log de atividade do sistema) —
            // só faz sentido no painel admin. Sem esse guard,
            // Panel::configureUsing() aplica a TODOS os painéis
            // registrados (inclusive o customer, que não deveria expor
            // isso a clientes).
            if ($panel->getId() !== 'admin') {
                return;
            }

            // ->label()/->pluralLabel() recebem Closure (não string) de
            // propósito: packageRegistered() roda durante a fase
            // register() de TODOS os providers, que sempre termina antes
            // da fase boot() de qualquer um — e é só no boot() que
            // ->hasTranslations() (via bootPackageTranslations() do
            // spatie/laravel-package-tools) registra de fato o namespace
            // de tradução "auditoria::" no Translator. Um __(...) AVALIADO
            // AQUI (fora de Closure) roda antes desse registro, retorna a
            // própria chave (tradução "não encontrada" nesse instante) e
            // — pior — o Translator cacheia esse resultado vazio por
            // namespace+grupo+locale pro resto do request
            // (`Translator::$loaded`), envenenando também chamadas
            // LEGÍTIMAS feitas bem depois (ex.:
            // AuditoriaResource::getPluralModelLabel(), chamada só na
            // hora de montar a navegação) — era exatamente o bug do menu
            // mostrando a chave crua em vez de "Auditoria". Envolver em
            // Closure adia a avaliação do __() pra quando o valor é
            // realmente lido (`evaluate()` dentro de getLabel()/
            // getPluralLabel()), bem depois do boot() de todo mundo já
            // ter terminado.
            $panel->plugin(
                ActivitylogPlugin::make()
                    ->resource(AuditoriaResource::class)
                    ->label(fn () => __('auditoria::filament/resources/auditoria.model-label'))
                    ->pluralLabel(fn () => __('auditoria::filament/resources/auditoria.plural-model-label'))
                    ->navigationIcon('heroicon-o-shield-check'),
            );
        });
    }
}
