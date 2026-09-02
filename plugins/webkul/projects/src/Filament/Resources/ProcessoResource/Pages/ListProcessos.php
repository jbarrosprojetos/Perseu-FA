<?php

namespace Webkul\Project\Filament\Resources\ProcessoResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Webkul\Project\Filament\Resources\ProcessoResource;
use Webkul\TableViews\Filament\Components\PresetView;
use Webkul\TableViews\Filament\Concerns\HasTableViews;

class ListProcessos extends ListRecords
{
    use HasTableViews;

    protected static string $resource = ProcessoResource::class;

    public function getPresetTableViews(): array
    {
        return [
            'my_processos' => PresetView::make(__('projects::filament/resources/processo/pages/list-processos.tabs.my-processos'))
                ->icon('heroicon-s-user')
                ->favorite()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('user_id', Auth::id())),

            'my_favorite_processos' => PresetView::make(__('projects::filament/resources/processo/pages/list-processos.tabs.my-favorite-processos'))
                ->icon('heroicon-s-star')
                ->favorite()
                ->modifyQueryUsing(function (Builder $query) {
                    return $query
                        ->leftJoin('projects_user_processo_favorites', 'projects_user_processo_favorites.processo_id', '=', 'projects_processos.id')
                        ->where('projects_user_processo_favorites.user_id', Auth::id());
                }),

            'unassigned_processos' => PresetView::make(__('projects::filament/resources/processo/pages/list-processos.tabs.unassigned-processos'))
                ->icon('heroicon-s-user-minus')
                ->favorite()
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('user_id')),

            'archived_processos' => PresetView::make(__('projects::filament/resources/processo/pages/list-processos.tabs.archived-processos'))
                ->icon('heroicon-s-archive-box')
                ->favorite()
                ->modifyQueryUsing(function ($query) {
                    return $query->onlyTrashed();
                }),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('projects::filament/resources/processo/pages/list-processos.header-actions.create.label'))
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
