<?php

namespace Perseu\Comercial\Filament\Clusters\Comercial\Resources\TipoObraResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Perseu\Comercial\Filament\Clusters\Comercial\Resources\TipoObraResource;

class ManageTiposObra extends ManageRecords
{
    protected static string $resource = TipoObraResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('comercial::filament/resources/tipo-obra/pages/manage-tipos-obra.header-actions.create.label'))
                ->icon('heroicon-o-plus-circle')
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('comercial::filament/resources/tipo-obra/pages/manage-tipos-obra.header-actions.create.notification.title'))
                        ->body(__('comercial::filament/resources/tipo-obra/pages/manage-tipos-obra.header-actions.create.notification.body')),
                ),
        ];
    }
}
