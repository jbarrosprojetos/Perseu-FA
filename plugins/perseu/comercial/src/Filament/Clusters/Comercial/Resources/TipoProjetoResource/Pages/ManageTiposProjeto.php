<?php

namespace Perseu\Comercial\Filament\Clusters\Comercial\Resources\TipoProjetoResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Perseu\Comercial\Filament\Clusters\Comercial\Resources\TipoProjetoResource;

class ManageTiposProjeto extends ManageRecords
{
    protected static string $resource = TipoProjetoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('comercial::filament/resources/tipo-projeto/pages/manage-tipos-projeto.header-actions.create.label'))
                ->icon('heroicon-o-plus-circle')
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('comercial::filament/resources/tipo-projeto/pages/manage-tipos-projeto.header-actions.create.notification.title'))
                        ->body(__('comercial::filament/resources/tipo-projeto/pages/manage-tipos-projeto.header-actions.create.notification.body')),
                ),
        ];
    }
}
