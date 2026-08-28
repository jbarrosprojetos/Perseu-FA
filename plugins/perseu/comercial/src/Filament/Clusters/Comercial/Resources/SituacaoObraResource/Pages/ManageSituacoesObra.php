<?php

namespace Perseu\Comercial\Filament\Clusters\Comercial\Resources\SituacaoObraResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Perseu\Comercial\Filament\Clusters\Comercial\Resources\SituacaoObraResource;

class ManageSituacoesObra extends ManageRecords
{
    protected static string $resource = SituacaoObraResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('comercial::filament/resources/situacao-obra/pages/manage-situacoes-obra.header-actions.create.label'))
                ->icon('heroicon-o-plus-circle')
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('comercial::filament/resources/situacao-obra/pages/manage-situacoes-obra.header-actions.create.notification.title'))
                        ->body(__('comercial::filament/resources/situacao-obra/pages/manage-situacoes-obra.header-actions.create.notification.body')),
                ),
        ];
    }
}
