<?php

namespace Perseu\Comercial\Filament\Clusters\Comercial\Resources\ProjetoResource\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Perseu\Comercial\Filament\Clusters\Comercial\Resources\ProjetoResource;

class CreateProjeto extends CreateRecord
{
    protected static string $resource = ProjetoResource::class;

    protected function getCreatedNotification(): Notification
    {
        return Notification::make()
            ->success()
            ->title(__('comercial::filament/resources/projeto/pages/create-projeto.notification.title'))
            ->body(__('comercial::filament/resources/projeto/pages/create-projeto.notification.body'));
    }
}
