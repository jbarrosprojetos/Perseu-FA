<?php

namespace Perseu\Comercial\Filament\Clusters\Comercial\Resources\ObraResource\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Perseu\Comercial\Filament\Clusters\Comercial\Resources\ObraResource;

class CreateObra extends CreateRecord
{
    protected static string $resource = ObraResource::class;

    protected function getCreatedNotification(): Notification
    {
        return Notification::make()
            ->success()
            ->title(__('comercial::filament/resources/obra/pages/create-obra.notification.title'))
            ->body(__('comercial::filament/resources/obra/pages/create-obra.notification.body'));
    }
}
