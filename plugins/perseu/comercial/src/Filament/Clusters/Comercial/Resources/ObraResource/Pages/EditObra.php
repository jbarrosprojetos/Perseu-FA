<?php

namespace Perseu\Comercial\Filament\Clusters\Comercial\Resources\ObraResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Perseu\Comercial\Filament\Clusters\Comercial\Resources\ObraResource;

class EditObra extends EditRecord
{
    protected static string $resource = ObraResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('comercial::filament/resources/obra/pages/edit-obra.header-actions.delete.notification.title'))
                        ->body(__('comercial::filament/resources/obra/pages/edit-obra.header-actions.delete.notification.body')),
                ),
        ];
    }

    protected function getSavedNotification(): Notification
    {
        return Notification::make()
            ->success()
            ->title(__('comercial::filament/resources/obra/pages/edit-obra.notification.title'))
            ->body(__('comercial::filament/resources/obra/pages/edit-obra.notification.body'));
    }
}
