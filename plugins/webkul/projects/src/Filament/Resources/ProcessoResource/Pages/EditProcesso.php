<?php

namespace Webkul\Project\Filament\Resources\ProcessoResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Webkul\Chatter\Filament\Actions\ChatterAction;
use Webkul\Project\Filament\Resources\ProcessoResource;

class EditProcesso extends EditRecord
{
    protected static string $resource = ProcessoResource::class;

    protected function getSavedNotification(): Notification
    {
        return Notification::make()
            ->success()
            ->title(__('projects::filament/resources/processo/pages/edit-processo.notification.title'))
            ->body(__('projects::filament/resources/processo/pages/edit-processo.notification.body'));
    }

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
                        ->title(__('projects::filament/resources/processo/pages/edit-processo.header-actions.delete.notification.title'))
                        ->body(__('projects::filament/resources/processo/pages/edit-processo.header-actions.delete.notification.body')),
                ),
        ];
    }
}
