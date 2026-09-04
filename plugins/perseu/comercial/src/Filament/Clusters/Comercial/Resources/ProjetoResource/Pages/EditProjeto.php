<?php

namespace Perseu\Comercial\Filament\Clusters\Comercial\Resources\ProjetoResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Group;
use Perseu\Comercial\Filament\Clusters\Comercial\Resources\ProjetoResource;

class EditProjeto extends EditRecord
{
    protected static string $resource = ProjetoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('comercial::filament/resources/projeto/pages/edit-projeto.header-actions.delete.notification.title'))
                        ->body(__('comercial::filament/resources/projeto/pages/edit-projeto.header-actions.delete.notification.body')),
                ),
        ];
    }

    /**
     * "Atribuir Processos" ao lado de Salvar/Cancelar — SÓ existe aqui em
     * `EditProjeto`, nunca em `CreateProjeto` (que não sobrescreve este
     * método, herdando o `getFormActions()` padrão do `CreateRecord`, só
     * Salvar/Cancelar). Mesmo padrão já usado por `getHeaderActions()`
     * acima (`DeleteAction` também só existe em Edit) — não precisa de
     * `->visible()` checando a página/registro, a ausência da Action na
     * subclasse já garante isso. `getFormActionsContentComponent()`
     * (chamado por `ProjetoResource::form()` via `$schema->getLivewire()`)
     * usa a instância REAL da página (Create ou Edit) para montar as
     * Actions, então a sobrescrita aqui é respeitada automaticamente —
     * ver "Section 'Itens' e reposicionamento de Salvar/Cancelar" no
     * CLAUDE.md deste plugin. Sem ação real ainda — só a notificação
     * placeholder, mesmo padrão das origens do dropdown de Itens ainda
     * não implementadas.
     *
     * @return array<Action>
     */
    protected function getFormActions(): array
    {
        return [
            ...parent::getFormActions(),
            Action::make('atribuirProcessos')
                ->label(__('comercial::filament/resources/projeto/pages/edit-projeto.form-actions.atribuir-processos.label'))
                ->color('gray')
                ->action(function (): void {
                    Notification::make()
                        ->info()
                        ->title(__('comercial::filament/resources/projeto/pages/edit-projeto.form-actions.atribuir-processos.notification.title'))
                        ->body(__('comercial::filament/resources/projeto/pages/edit-projeto.form-actions.atribuir-processos.notification.body'))
                        ->send();
                }),
        ];
    }

    protected function getSavedNotification(): Notification
    {
        return Notification::make()
            ->success()
            ->title(__('comercial::filament/resources/projeto/pages/edit-projeto.notification.title'))
            ->body(__('comercial::filament/resources/projeto/pages/edit-projeto.notification.body'));
    }

    /**
     * Igual ao `EditRecord::getFormContentComponent()` original, SEM o
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
