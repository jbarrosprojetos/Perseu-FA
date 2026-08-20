<?php

namespace Perseu\Pessoas\Filament\Clusters\Pessoas\Resources\PessoaJuridicaResource\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Perseu\Pessoas\Filament\Clusters\Pessoas\Resources\PessoaJuridicaResource;

class CreatePessoaJuridica extends CreateRecord
{
    protected static string $resource = PessoaJuridicaResource::class;

    protected function getCreatedNotification(): Notification
    {
        return Notification::make()
            ->success()
            ->title(__('pessoas::filament/resources/pessoa-juridica/pages/create-pessoa-juridica.notification.title'))
            ->body(__('pessoas::filament/resources/pessoa-juridica/pages/create-pessoa-juridica.notification.body'));
    }
}
