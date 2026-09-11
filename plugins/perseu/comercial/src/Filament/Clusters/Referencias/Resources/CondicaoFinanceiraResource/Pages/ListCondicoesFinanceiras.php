<?php

namespace Perseu\Comercial\Filament\Clusters\Referencias\Resources\CondicaoFinanceiraResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Perseu\Comercial\Filament\Clusters\Referencias\Resources\CondicaoFinanceiraResource;

class ListCondicoesFinanceiras extends ListRecords
{
    protected static string $resource = CondicaoFinanceiraResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('comercial::filament/resources/condicao-financeira/pages/list-condicoes-financeiras.header-actions.create.label'))
                ->icon('heroicon-o-plus-circle')
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('comercial::filament/resources/condicao-financeira/pages/list-condicoes-financeiras.header-actions.create.notification.title'))
                        ->body(__('comercial::filament/resources/condicao-financeira/pages/list-condicoes-financeiras.header-actions.create.notification.body')),
                ),
        ];
    }
}
