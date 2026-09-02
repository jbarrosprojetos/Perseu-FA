<?php

namespace Webkul\Project\Filament\Resources\ProcessoResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Webkul\Chatter\Filament\Actions\ChatterAction;
use Webkul\Project\Filament\Resources\ProcessoResource;

class ViewProcesso extends ViewRecord
{
    protected static string $resource = ProcessoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ChatterAction::make()
                ->resource(static::$resource)
                ->activityPlans($this->getRecord()->activityPlans()),
            DeleteAction::make()
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('projects::filament/resources/processo/pages/view-processo.header-actions.delete.notification.title'))
                        ->body(__('projects::filament/resources/processo/pages/view-processo.header-actions.delete.notification.body')),
                ),
        ];
    }
}
