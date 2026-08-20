<?php

namespace Perseu\Pessoas\Filament\Clusters\Pessoas\Resources\PessoaFisicaResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Perseu\Pessoas\Filament\Clusters\Pessoas\Resources\PessoaFisicaResource;

class ListPessoasFisicas extends ListRecords
{
    protected static string $resource = PessoaFisicaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('pessoas::filament/resources/pessoa-fisica/pages/list-pessoas-fisicas.header-actions.create.label'))
                ->icon('heroicon-o-plus-circle')
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('pessoas::filament/resources/pessoa-fisica/pages/list-pessoas-fisicas.header-actions.create.notification.title'))
                        ->body(__('pessoas::filament/resources/pessoa-fisica/pages/list-pessoas-fisicas.header-actions.create.notification.body')),
                ),
        ];
    }
}
