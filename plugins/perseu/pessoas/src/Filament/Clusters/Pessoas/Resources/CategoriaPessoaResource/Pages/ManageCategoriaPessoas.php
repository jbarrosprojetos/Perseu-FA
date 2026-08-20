<?php

namespace Perseu\Pessoas\Filament\Clusters\Pessoas\Resources\CategoriaPessoaResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Perseu\Pessoas\Filament\Clusters\Pessoas\Resources\CategoriaPessoaResource;

class ManageCategoriaPessoas extends ManageRecords
{
    protected static string $resource = CategoriaPessoaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('pessoas::filament/resources/categoria-pessoa/pages/manage-categoria-pessoas.header-actions.create.label'))
                ->icon('heroicon-o-plus-circle')
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('pessoas::filament/resources/categoria-pessoa/pages/manage-categoria-pessoas.header-actions.create.notification.title'))
                        ->body(__('pessoas::filament/resources/categoria-pessoa/pages/manage-categoria-pessoas.header-actions.create.notification.body')),
                ),
        ];
    }
}
