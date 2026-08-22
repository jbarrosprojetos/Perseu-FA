<?php

namespace Perseu\Comercial\Filament\Clusters\Comercial\Resources\ProjetoResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Perseu\Comercial\Filament\Clusters\Comercial\Resources\ProjetoResource;

class EditProjeto extends EditRecord
{
    protected static string $resource = ProjetoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('comercial::filament/resources/projeto/pages/edit-projeto.header-actions.delete.notification.title'))
                        ->body(__('comercial::filament/resources/projeto/pages/edit-projeto.header-actions.delete.notification.body')),
                ),
        ];
    }

    protected function getSavedNotification(): Notification
    {
        return Notification::make()
            ->success()
            ->title(__('comercial::filament/resources/projeto/pages/edit-projeto.notification.title'))
            ->body(__('comercial::filament/resources/projeto/pages/edit-projeto.notification.body'));
    }
}
