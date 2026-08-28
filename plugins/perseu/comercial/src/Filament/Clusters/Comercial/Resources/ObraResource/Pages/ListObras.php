<?php

namespace Perseu\Comercial\Filament\Clusters\Comercial\Resources\ObraResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Perseu\Comercial\Filament\Clusters\Comercial\Resources\ObraResource;

class ListObras extends ListRecords
{
    protected static string $resource = ObraResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('comercial::filament/resources/obra/pages/list-obras.header-actions.create.label'))
                ->icon('heroicon-o-plus-circle')
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('comercial::filament/resources/obra/pages/list-obras.header-actions.create.notification.title'))
                        ->body(__('comercial::filament/resources/obra/pages/list-obras.header-actions.create.notification.body')),
                ),
        ];
    }
}
