<?php

namespace Perseu\Pessoas\Filament\Clusters\Pessoas\Resources\PessoaJuridicaResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Perseu\Pessoas\Filament\Clusters\Pessoas\Resources\PessoaJuridicaResource;
use Perseu\Pessoas\Traits\HasRelationManagerDividers;

class EditPessoaJuridica extends EditRecord
{
    use HasRelationManagerDividers;

    protected static string $resource = PessoaJuridicaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('pessoas::filament/resources/pessoa-juridica/pages/edit-pessoa-juridica.header-actions.delete.notification.title'))
                        ->body(__('pessoas::filament/resources/pessoa-juridica/pages/edit-pessoa-juridica.header-actions.delete.notification.body')),
                ),
        ];
    }

    protected function getSavedNotification(): Notification
    {
        return Notification::make()
            ->success()
            ->title(__('pessoas::filament/resources/pessoa-juridica/pages/edit-pessoa-juridica.notification.title'))
            ->body(__('pessoas::filament/resources/pessoa-juridica/pages/edit-pessoa-juridica.notification.body'));
    }
}
