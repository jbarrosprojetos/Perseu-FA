<?php

namespace Perseu\Pessoas\Filament\Clusters\Pessoas\Resources\SetorResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Perseu\Pessoas\Filament\Clusters\Pessoas\Resources\SetorResource;

class ManageSetores extends ManageRecords
{
    protected static string $resource = SetorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('pessoas::filament/resources/setor/pages/manage-setores.header-actions.create.label'))
                ->icon('heroicon-o-plus-circle')
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('pessoas::filament/resources/setor/pages/manage-setores.header-actions.create.notification.title'))
                        ->body(__('pessoas::filament/resources/setor/pages/manage-setores.header-actions.create.notification.body')),
                ),
        ];
    }
}
