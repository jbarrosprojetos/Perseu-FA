<?php

namespace Perseu\Auditoria\Filament\Resources;

use Perseu\Auditoria\Filament\Resources\AuditoriaResource\Pages\ListAuditoria;
use Perseu\Auditoria\Filament\Resources\AuditoriaResource\Pages\ViewAuditoria;
use Rmsramos\Activitylog\Resources\Activitylog\ActivitylogResource;
use Webkul\Support\Filament\Clusters\Settings;

/**
 * Reaproveita o `ActivitylogResource` do rmsramos/activitylog (tabela,
 * filtros, timeline etc. já prontos — ver README do pacote) só
 * reposicionando a navegação: em vez do item de topo próprio que o
 * pacote cria por padrão, aparece dentro do cluster de Configurações
 * (`Webkul\Support\Filament\Clusters\Settings`), como "Auditoria", ao
 * lado de "Marca" — mesma técnica de `$cluster` que
 * `Webkul\Support\Filament\Resources\ActivityTypeResource` já usa
 * (Resource "de verdade" clusterizado, não uma Page de settings como
 * `ManageBranding` — ver CLAUDE.md).
 *
 * Registrada via `->resource(AuditoriaResource::class)` em
 * `ActivitylogPlugin::make()` (ver AuditoriaServiceProvider), que
 * substitui o resource padrão do pacote — só esta classe fica
 * registrada, não as duas.
 *
 * `getPages()` é sobrescrito com Pages PRÓPRIAS (ListAuditoria/
 * ViewAuditoria) em vez de herdar as do pacote: as Pages originais
 * (`Rmsramos\Activitylog\Resources\Activitylog\Pages\*`) têm o
 * `$resource`/`getResource()` fixos na classe base
 * `ActivitylogResource`, então herdar `getPages()" sem reapontar as
 * Pages faria as rotas/permissões resolverem para o resource errado
 * (o de topo do pacote, não este, clusterizado).
 */
class AuditoriaResource extends ActivitylogResource
{
    protected static ?string $cluster = Settings::class;

    public static function getNavigationLabel(): string
    {
        return __('auditoria::filament/resources/auditoria.navigation.title');
    }

    public static function getModelLabel(): string
    {
        return __('auditoria::filament/resources/auditoria.model-label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('auditoria::filament/resources/auditoria.plural-model-label');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAuditoria::route('/'),
            'view'  => ViewAuditoria::route('/{record}'),
        ];
    }
}
