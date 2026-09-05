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
use Illuminate\Database\Eloquent\Collection;
use Perseu\Comercial\Filament\Clusters\Comercial\Resources\ProjetoResource;
use Perseu\Comercial\Models\ItemProjeto;

class EditProjeto extends EditRecord
{
    protected static string $resource = ProjetoResource::class;

    /**
     * Itens do Projeto (Section "Itens") HIDRATADOS do banco ao abrir a
     * tela — achado real (2026-09-05): a listagem de itens já
     * inseridos dependia inteiramente de `ProjetoResource::form()`
     * reconsultar `$record->itens()` a cada avaliação do `Group`
     * dinâmico da Section "Itens" (sem nenhuma property própria) — em
     * tese sempre atualizado (nunca usava um cache obsoleto), mas o
     * usuário reportou a área "Itens" vazia ao abrir uma tela de
     * edição de verdade com itens já salvos. Hidratar explicitamente
     * aqui, no `mount()` (que já roda DEPOIS de `$this->record` ser
     * resolvido — `EditRecord::mount()`, vendor — então o Projeto já
     * existe nesse ponto), elimina qualquer dependência de timing
     * entre a resolução do registro e a avaliação do Schema dinâmico,
     * garantindo que a listagem SEMPRE reflita o banco assim que a
     * página carrega, sem depender de nenhuma interação do usuário.
     *
     * `ProjetoResource::linhasItensExistentes()` lê esta property (via
     * `$livewire` injetado no `Group::schema()`) em vez de reconsultar
     * o banco a cada render. `confirmarItemAvulso()`/
     * `excluirItemAvulso()` chamam `recarregarItens()` depois de
     * escrever no banco, pra manter esta property sincronizada dentro
     * da MESMA sessão (inserir/editar/excluir continuam refletindo na
     * tela imediatamente, sem precisar recarregar a página).
     *
     * @var Collection<int, ItemProjeto>
     */
    public Collection $itensCarregados;

    /**
     * `$this->itensCarregados = new Collection()` ANTES de `parent::
     * mount()` — achado real (2026-09-05): sem isso, "Typed property
     * ...itensCarregados must not be accessed before initialization".
     * `EditRecord::mount()` (vendor) chama `fillForm()`, que já avalia o
     * Schema INTEIRO — inclusive o `Group` dinâmico da Section "Itens"
     * (`ProjetoResource::form()`) — pra montar a árvore de componentes,
     * ANTES da linha `recarregarItens()` abaixo rodar. A property
     * precisa de algum valor válido nesse ponto intermediário (vazio
     * está OK — o `Group` dessa primeira avaliação não é o que vai pra
     * tela final, ver `recarregarItens()` logo abaixo).
     */
    public function mount(int | string $record): void
    {
        $this->itensCarregados = new Collection;

        parent::mount($record);

        $this->recarregarItens();
    }

    public function recarregarItens(): void
    {
        $this->itensCarregados = $this->getRecord()->itens()->orderBy('numero_item')->get();
    }

    /**
     * Sem isso, excluir um `ItemProjeto` (ícone de lixeira da Section
     * "Itens", `ProjetoResource::linhaExibicaoItem()`) redirecionava pra
     * `ListProjetos` — achado real (2026-09-05). Causa raiz: `Filament
     * \Resources\Pages\Concerns\InteractsWithRecord::getDefaultActionSuccessRedirectUrl()`
     * (vendor, herdado por `EditRecord`) redireciona pra
     * `$this->getResourceUrl()` sempre que a Action que acabou de rodar
     * é `instanceof DeleteAction` (ou `ForceDeleteAction`) — **sem checar
     * qual registro ela de fato excluiu**. Isso dispara automaticamente
     * pra QUALQUER `DeleteAction`, em QUALQUER lugar da página, não só
     * o botão "Excluir" do cabeçalho (`getHeaderActions()` acima) — que
     * é o único caso em que esse redirecionamento faz sentido de
     * verdade (o Projeto da própria página foi excluído, não sobra
     * nada pra mostrar). `DeleteAction::make("excluirItemProjeto{$item
     * ->id}")` (ver `linhaExibicaoItem()`) usa essa MESMA classe só
     * pelo visual/confirmação padrão (ícone de lixeira, cor "danger",
     * modal de confirmação) — ela exclui um `ItemProjeto`, não o
     * `Projeto`, então NUNCA deveria redirecionar.
     *
     * Corrigido verificando o RECORD de fato vinculado à Action
     * (`$action->getRecord()`, resolve pro `ItemProjeto` explicitamente
     * passado via `->record($item)`) em vez de confiar só na CLASSE da
     * Action — a mesma checagem cobre automaticamente qualquer Action
     * futura da Section "Itens" que algum dia use `DeleteAction`/
     * `ForceDeleteAction` sobre um `ItemProjeto`, sem precisar lembrar
     * de `->successRedirectUrl(...)` em cada uma individualmente.
     * `inserirItem`/`confirmarItemAvulso`/`editarItemProjeto{id}` NÃO
     * precisaram de correção — não são `DeleteAction`/`ForceDeleteAction`,
     * então `parent::getDefaultActionSuccessRedirectUrl()` já retorna
     * `null` (sem redirecionar) pra elas por padrão; confirmado também
     * por teste (`Livewire::test()->assertRedirect()` falha, como
     * esperado, pra `confirmarItemAvulso` em modo edição).
     */
    public function getDefaultActionSuccessRedirectUrl(Action $action): ?string
    {
        if ($action->getRecord() instanceof ItemProjeto) {
            return null;
        }

        return parent::getDefaultActionSuccessRedirectUrl($action);
    }

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
