<?php

namespace Webkul\Project\Filament\Resources\ProcessoResource\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Webkul\Project\Filament\Resources\ProcessoResource;

class CreateProcesso extends CreateRecord
{
    protected static string $resource = ProcessoResource::class;

    protected function getCreatedNotification(): Notification
    {
        return Notification::make()
            ->success()
            ->title(__('projects::filament/resources/processo/pages/create-processo.notification.title'))
            ->body(__('projects::filament/resources/processo/pages/create-processo.notification.body'));
    }
}
