<?php

namespace Perseu\Comercial\Filament\Clusters;

use Filament\Clusters\Cluster;
use Webkul\Support\Enums\NavigationGroup;

/**
 * Mesmo padrão técnico do Cluster `Projetos` (ver CLAUDE.md — investigação
 * do commit 26cfef4f7 e criação do Cluster Obras/Projetos) — sub-área com
 * sidebar própria, item único no dropdown "Comercial" da topbar.
 *
 * Reúne cadastros de apoio usados para compor Propostas/Contratos no
 * futuro: Preços (implementado nesta tarefa), Propostas (modelo/
 * template), Contratos, Termos de Entrega, Termos de Garantia — estes
 * últimos quatro apenas citados/planejados, sem Resource criado ainda
 * (ver "Roadmap" no CLAUDE.md).
 */
class Referencias extends Cluster
{
    protected static ?string $slug = 'comercial/referencias';

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return __('comercial::filament/clusters/referencias.navigation.title');
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
