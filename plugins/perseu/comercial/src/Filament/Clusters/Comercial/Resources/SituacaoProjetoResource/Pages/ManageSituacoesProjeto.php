<?php

namespace Perseu\Comercial\Filament\Clusters\Comercial\Resources\SituacaoProjetoResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Perseu\Comercial\Filament\Clusters\Comercial\Resources\SituacaoProjetoResource;

class ManageSituacoesProjeto extends ManageRecords
{
    protected static string $resource = SituacaoProjetoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('comercial::filament/resources/situacao-projeto/pages/manage-situacoes-projeto.header-actions.create.label'))
                ->icon('heroicon-o-plus-circle')
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('comercial::filament/resources/situacao-projeto/pages/manage-situacoes-projeto.header-actions.create.notification.title'))
                        ->body(__('comercial::filament/resources/situacao-projeto/pages/manage-situacoes-projeto.header-actions.create.notification.body')),
                ),
        ];
    }
}
