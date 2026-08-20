<?php

namespace Perseu\Pessoas\Filament\Clusters\Pessoas\Resources\PessoaFisicaResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Perseu\Pessoas\Filament\Clusters\Pessoas\Resources\PessoaFisicaResource;

class EditPessoaFisica extends EditRecord
{
    protected static string $resource = PessoaFisicaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('pessoas::filament/resources/pessoa-fisica/pages/edit-pessoa-fisica.header-actions.delete.notification.title'))
                        ->body(__('pessoas::filament/resources/pessoa-fisica/pages/edit-pessoa-fisica.header-actions.delete.notification.body')),
                ),
        ];
    }

    protected function getSavedNotification(): Notification
    {
        return Notification::make()
            ->success()
            ->title(__('pessoas::filament/resources/pessoa-fisica/pages/edit-pessoa-fisica.notification.title'))
            ->body(__('pessoas::filament/resources/pessoa-fisica/pages/edit-pessoa-fisica.notification.body'));
    }
}
