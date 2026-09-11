<?php

namespace Perseu\Comercial\Filament\Clusters\Referencias\Resources\DocumentoResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Perseu\Comercial\Filament\Clusters\Referencias\Resources\DocumentoResource;

class ListDocumentos extends ListRecords
{
    protected static string $resource = DocumentoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('comercial::filament/resources/documento/pages/list-documentos.header-actions.create.label'))
                ->icon('heroicon-o-plus-circle')
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('comercial::filament/resources/documento/pages/list-documentos.header-actions.create.notification.title'))
                        ->body(__('comercial::filament/resources/documento/pages/list-documentos.header-actions.create.notification.body')),
                ),
        ];
    }
}
