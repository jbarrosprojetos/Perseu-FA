<?php

namespace Perseu\Comercial\Filament\Clusters\Comercial\Resources\ProjetoResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Perseu\Comercial\Filament\Clusters\Comercial\Resources\ProjetoResource;

class ListProjetos extends ListRecords
{
    protected static string $resource = ProjetoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('comercial::filament/resources/projeto/pages/list-projetos.header-actions.create.label'))
                ->icon('heroicon-o-plus-circle')
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('comercial::filament/resources/projeto/pages/list-projetos.header-actions.create.notification.title'))
                        ->body(__('comercial::filament/resources/projeto/pages/list-projetos.header-actions.create.notification.body')),
                ),
        ];
    }
}
