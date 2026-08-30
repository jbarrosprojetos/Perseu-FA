<?php

namespace Perseu\Comercial\Filament\Clusters\Referencias\Resources\ReferenciaPrecoResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Perseu\Comercial\Filament\Clusters\Referencias\Resources\ReferenciaPrecoResource;

class ListReferenciasPrecos extends ListRecords
{
    protected static string $resource = ReferenciaPrecoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('comercial::filament/resources/referencia-preco/pages/list-referencias-precos.header-actions.create.label'))
                ->icon('heroicon-o-plus-circle')
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('comercial::filament/resources/referencia-preco/pages/list-referencias-precos.header-actions.create.notification.title'))
                        ->body(__('comercial::filament/resources/referencia-preco/pages/list-referencias-precos.header-actions.create.notification.body')),
                ),
        ];
    }
}
