<?php

namespace Perseu\Comercial\Filament\Clusters\Comercial\Resources\ProjetoResource\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Group;
use Perseu\Comercial\Filament\Clusters\Comercial\Resources\ProjetoResource;

class CreateProjeto extends CreateRecord
{
    protected static string $resource = ProjetoResource::class;

    protected function getCreatedNotification(): Notification
    {
        return Notification::make()
            ->success()
            ->title(__('comercial::filament/resources/projeto/pages/create-projeto.notification.title'))
            ->body(__('comercial::filament/resources/projeto/pages/create-projeto.notification.body'));
    }

    /**
     * Igual ao `CreateRecord::getFormContentComponent()` original, SEM o
     * `->footer([$this->getFormActionsContentComponent()])` — o Salvar/
     * Cancelar já é chamado explicitamente dentro de
     * `ProjetoResource::form()` (entre as Sections "Cabeçalho" e "Itens"),
     * então não deve ser anexado de novo aqui, ou apareceria duplicado no
     * rodapé da página com o mesmo `key('form-actions')`.
     */
    public function getFormContentComponent(): Component
    {
        if (! $this->hasFormWrapper()) {
            return Group::make([
                EmbeddedSchema::make('form'),
            ]);
        }

        return Form::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler($this->getSubmitFormLivewireMethodName());
    }
}
