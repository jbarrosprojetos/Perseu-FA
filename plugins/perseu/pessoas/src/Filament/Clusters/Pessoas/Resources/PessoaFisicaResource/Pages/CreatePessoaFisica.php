<?php

namespace Perseu\Pessoas\Filament\Clusters\Pessoas\Resources\PessoaFisicaResource\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Perseu\Pessoas\Filament\Clusters\Pessoas\Resources\PessoaFisicaResource;

class CreatePessoaFisica extends CreateRecord
{
    protected static string $resource = PessoaFisicaResource::class;

    protected function getCreatedNotification(): Notification
    {
        return Notification::make()
            ->success()
            ->title(__('pessoas::filament/resources/pessoa-fisica/pages/create-pessoa-fisica.notification.title'))
            ->body(__('pessoas::filament/resources/pessoa-fisica/pages/create-pessoa-fisica.notification.body'));
    }
}
