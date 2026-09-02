<?php

namespace Webkul\Project\Filament\Clusters\Configurations\Resources\ProcessoStageResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Webkul\Project\Filament\Clusters\Configurations\Resources\ProcessoStageResource;
use Webkul\Project\Models\ProcessoStage;

class ManageProcessoStages extends ManageRecords
{
    protected static string $resource = ProcessoStageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('projects::filament/clusters/configurations/resources/processo-stage/pages/manage-processo-stages.header-actions.create.label'))
                ->icon('heroicon-o-plus-circle')
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('projects::filament/clusters/configurations/resources/processo-stage/pages/manage-processo-stages.header-actions.create.notification.title'))
                        ->body(__('projects::filament/clusters/configurations/resources/processo-stage/pages/manage-processo-stages.header-actions.create.notification.body')),
                ),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make(__('projects::filament/clusters/configurations/resources/processo-stage/pages/manage-processo-stages.tabs.all'))
                ->badge(ProcessoStage::count()),
            'archived' => Tab::make(__('projects::filament/clusters/configurations/resources/processo-stage/pages/manage-processo-stages.tabs.archived'))
                ->badge(ProcessoStage::onlyTrashed()->count())
                ->modifyQueryUsing(function ($query) {
                    return $query->onlyTrashed();
                }),
        ];
    }
}
