<?php

namespace Perseu\Pessoas\Filament\Clusters\Pessoas\Resources\PessoaJuridicaResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Perseu\Pessoas\Filament\Clusters\Pessoas\Resources\PessoaJuridicaResource;

class ListPessoasJuridicas extends ListRecords
{
    protected static string $resource = PessoaJuridicaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('pessoas::filament/resources/pessoa-juridica/pages/list-pessoas-juridicas.header-actions.create.label'))
                ->icon('heroicon-o-plus-circle')
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('pessoas::filament/resources/pessoa-juridica/pages/list-pessoas-juridicas.header-actions.create.notification.title'))
                        ->body(__('pessoas::filament/resources/pessoa-juridica/pages/list-pessoas-juridicas.header-actions.create.notification.body')),
                ),
        ];
    }
}
