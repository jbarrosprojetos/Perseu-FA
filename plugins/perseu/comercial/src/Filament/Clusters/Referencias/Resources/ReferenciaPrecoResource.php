<?php

namespace Perseu\Comercial\Filament\Clusters\Referencias\Resources;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Perseu\Comercial\Filament\Clusters\Referencias;
use Perseu\Comercial\Filament\Clusters\Referencias\Resources\ReferenciaPrecoResource\Pages\ListReferenciasPrecos;
use Perseu\Comercial\Models\ReferenciaPreco;

/**
 * Cadastro de apoio do Cluster Referências (ver CLAUDE.md) — várias
 * tabelas de preços coexistindo ao mesmo tempo (NÃO é histórico/
 * versionamento), escolhidas na hora de montar uma Proposta (fora de
 * escopo aqui). Mesmo padrão de Lixeira/Auditoria de qualquer Model de
 * negócio novo (ver "Convenção para todo Model de cadastro de negócio
 * criado a partir de agora" no CLAUDE.md): sem aba de Atividades
 * própria (auditoria só pela Central), com Lixeira própria (`TrashedFilter`/
 * `RestoreAction`/`ForceDeleteAction`) além de já entrar na Lixeira
 * Central (`Perseu\Auditoria\Support\TrashCatalog`).
 *
 * Criar/editar acontece em MODAL, não em página cheia (mesmo padrão
 * técnico de `BranchesRelationManager` — ver CLAUDE.md): não há pages
 * `create`/`edit` registradas em `getPages()`, então
 * `Filament\Resources\Pages\Page::getDefaultActionUrl()` (que só
 * redireciona pra uma URL quando `hasPage('create'|'edit')` é
 * verdadeiro) nunca encontra uma URL pra usar, e `CreateAction`/
 * `EditAction` caem no comportamento padrão de Action — abrir modal.
 */
class ReferenciaPrecoResource extends Resource
{
    protected static ?string $model = ReferenciaPreco::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $cluster = Referencias::class;

    protected static ?string $slug = 'precos';

    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return __('comercial::filament/resources/referencia-preco.model-label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('comercial::filament/resources/referencia-preco.plural-model-label');
    }

    public static function getNavigationLabel(): string
    {
        return __('comercial::filament/resources/referencia-preco.navigation.title');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('descricao')
                    ->label(__('comercial::filament/resources/referencia-preco.form.descricao'))
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                // Descrição sozinha NÃO é única (de propósito, ver
                // CLAUDE.md — "Data/Hora de criação como identidade
                // visual") — duas referências podem ter a mesma
                // Descrição desde que criadas em momentos diferentes
                // (ex.: revisões de uma mesma tabela de preços ao longo
                // do tempo). Este Placeholder deixa isso visível pro
                // usuário logo abaixo da Descrição, sem ser uma
                // constraint de banco (created_at tem precisão de
                // segundo — duas criações simultâneas poderiam colidir
                // em teoria, então a "unicidade" aqui é só de
                // identificação visual, não uma regra de validação).
                Placeholder::make('created_at')
                    ->label(__('comercial::filament/resources/referencia-preco.form.created-at'))
                    ->content(fn (?ReferenciaPreco $record) => $record?->created_at?->format('d/m/Y H:i')
                        ?? __('comercial::filament/resources/referencia-preco.form.created-at-pendente'))
                    ->columnSpanFull(),

                Grid::make(2)
                    ->schema([
                        TextInput::make('laminacao')
                            ->label(__('comercial::filament/resources/referencia-preco.form.laminacao'))
                            ->numeric()
                            ->required()
                            ->prefix('R$')
                            ->suffix(__('comercial::filament/resources/referencia-preco.form.unidade-metro-linear')),
                        TextInput::make('corte')
                            ->label(__('comercial::filament/resources/referencia-preco.form.corte'))
                            ->numeric()
                            ->required()
                            ->prefix('R$')
                            ->suffix(__('comercial::filament/resources/referencia-preco.form.unidade-metro-linear')),
                        TextInput::make('hora_producao')
                            ->label(__('comercial::filament/resources/referencia-preco.form.hora-producao'))
                            ->numeric()
                            ->required()
                            ->prefix('R$')
                            ->suffix(__('comercial::filament/resources/referencia-preco.form.unidade-metro-quadrado')),
                        TextInput::make('hora_execucao')
                            ->label(__('comercial::filament/resources/referencia-preco.form.hora-execucao'))
                            ->numeric()
                            ->required()
                            ->prefix('R$')
                            ->suffix(__('comercial::filament/resources/referencia-preco.form.unidade-metro-quadrado')),
                        TextInput::make('retencao_tecnica')
                            ->label(__('comercial::filament/resources/referencia-preco.form.retencao-tecnica'))
                            ->numeric()
                            ->required()
                            ->suffix('%'),
                        TextInput::make('imposto')
                            ->label(__('comercial::filament/resources/referencia-preco.form.imposto'))
                            ->numeric()
                            ->required()
                            ->suffix('%'),
                        TextInput::make('despesas_variaveis')
                            ->label(__('comercial::filament/resources/referencia-preco.form.despesas-variaveis'))
                            ->numeric()
                            ->required()
                            ->suffix('%'),
                        TextInput::make('despesas_fixas')
                            ->label(__('comercial::filament/resources/referencia-preco.form.despesas-fixas'))
                            ->numeric()
                            ->required()
                            ->suffix('%'),
                        TextInput::make('valor_pecas')
                            ->label(__('comercial::filament/resources/referencia-preco.form.valor-pecas'))
                            ->numeric()
                            ->required()
                            ->prefix('R$'),
                        TextInput::make('fator_madeiras')
                            ->label(__('comercial::filament/resources/referencia-preco.form.fator-madeiras'))
                            ->numeric()
                            ->required()
                            ->suffix('%'),
                        TextInput::make('fator_ferragens_miscelanias')
                            ->label(__('comercial::filament/resources/referencia-preco.form.fator-ferragens-miscelanias'))
                            ->numeric()
                            ->required()
                            ->suffix('%'),
                        TextInput::make('fator_mao_obra')
                            ->label(__('comercial::filament/resources/referencia-preco.form.fator-mao-obra'))
                            ->numeric()
                            ->required()
                            ->suffix('%'),
                    ]),
            ]);
    }

    /**
     * Reaproveitado pelas 4 colunas percentuais da tabela (retenção
     * técnica, imposto, despesas variáveis, despesas fixas) — mesma
     * lógica de formatação repetida seria a mesma linha 4 vezes.
     */
    protected static function formatPercent(?string $state): string
    {
        return filled($state) ? number_format((float) $state, 2, ',', '.') . '%' : '—';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('descricao')
                    ->label(__('comercial::filament/resources/referencia-preco.table.columns.descricao'))
                    ->searchable()
                    ->sortable(),
                // Logo após a Descrição, visível por padrão (não mais
                // toggleable-hidden) — ver CLAUDE.md, "Data/Hora de
                // criação como identidade visual": duas referências
                // podem ter a mesma Descrição, então a Data/Hora de
                // criação precisa aparecer de cara pra diferenciá-las.
                TextColumn::make('created_at')
                    ->label(__('comercial::filament/resources/referencia-preco.table.columns.created-at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('laminacao')
                    ->label(__('comercial::filament/resources/referencia-preco.table.columns.laminacao'))
                    ->money('BRL')
                    ->sortable(),
                TextColumn::make('corte')
                    ->label(__('comercial::filament/resources/referencia-preco.table.columns.corte'))
                    ->money('BRL')
                    ->sortable(),
                TextColumn::make('hora_producao')
                    ->label(__('comercial::filament/resources/referencia-preco.table.columns.hora-producao'))
                    ->money('BRL')
                    ->sortable(),
                TextColumn::make('hora_execucao')
                    ->label(__('comercial::filament/resources/referencia-preco.table.columns.hora-execucao'))
                    ->money('BRL')
                    ->sortable(),
                TextColumn::make('retencao_tecnica')
                    ->label(__('comercial::filament/resources/referencia-preco.table.columns.retencao-tecnica'))
                    ->formatStateUsing(fn (?string $state) => static::formatPercent($state))
                    ->sortable(),
                TextColumn::make('imposto')
                    ->label(__('comercial::filament/resources/referencia-preco.table.columns.imposto'))
                    ->formatStateUsing(fn (?string $state) => static::formatPercent($state))
                    ->sortable(),
                TextColumn::make('despesas_variaveis')
                    ->label(__('comercial::filament/resources/referencia-preco.table.columns.despesas-variaveis'))
                    ->formatStateUsing(fn (?string $state) => static::formatPercent($state))
                    ->sortable(),
                TextColumn::make('despesas_fixas')
                    ->label(__('comercial::filament/resources/referencia-preco.table.columns.despesas-fixas'))
                    ->formatStateUsing(fn (?string $state) => static::formatPercent($state))
                    ->sortable(),
                // Os 4 campos abaixo (Valor por Peças + 3 fatores) ficam
                // ocultos por padrão na listagem (ainda editáveis no
                // modal) — a tabela já tinha 9 colunas de dados antes
                // deles; deixá-los todos visíveis de cara deixaria a
                // listagem larga demais pra leitura rápida. Continuam
                // acessíveis via botão de alternar colunas.
                TextColumn::make('valor_pecas')
                    ->label(__('comercial::filament/resources/referencia-preco.table.columns.valor-pecas'))
                    ->money('BRL')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('fator_madeiras')
                    ->label(__('comercial::filament/resources/referencia-preco.table.columns.fator-madeiras'))
                    ->formatStateUsing(fn (?string $state) => static::formatPercent($state))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('fator_ferragens_miscelanias')
                    ->label(__('comercial::filament/resources/referencia-preco.table.columns.fator-ferragens-miscelanias'))
                    ->formatStateUsing(fn (?string $state) => static::formatPercent($state))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('fator_mao_obra')
                    ->label(__('comercial::filament/resources/referencia-preco.table.columns.fator-mao-obra'))
                    ->formatStateUsing(fn (?string $state) => static::formatPercent($state))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make()
                    ->label(__('comercial::filament/resources/referencia-preco.table.filters.trashed')),
            ])
            ->recordActions([
                EditAction::make()
                    ->before(fn (ReferenciaPreco $record, EditAction $action) => static::bloquearSeVinculada($record, $action))
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('comercial::filament/resources/referencia-preco.table.actions.edit.notification.title'))
                            ->body(__('comercial::filament/resources/referencia-preco.table.actions.edit.notification.body')),
                    ),
                DeleteAction::make()
                    ->before(fn (ReferenciaPreco $record, DeleteAction $action) => static::bloquearSeVinculada($record, $action))
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('comercial::filament/resources/referencia-preco.table.actions.delete.notification.title'))
                            ->body(__('comercial::filament/resources/referencia-preco.table.actions.delete.notification.body')),
                    ),
                RestoreAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('comercial::filament/resources/referencia-preco.table.actions.restore.notification.title'))
                            ->body(__('comercial::filament/resources/referencia-preco.table.actions.restore.notification.body')),
                    ),
                ForceDeleteAction::make()
                    ->before(fn (ReferenciaPreco $record, ForceDeleteAction $action) => static::bloquearSeVinculada($record, $action))
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('comercial::filament/resources/referencia-preco.table.actions.force-delete.notification.title'))
                            ->body(__('comercial::filament/resources/referencia-preco.table.actions.force-delete.notification.body')),
                    ),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->before(fn (Collection $records, DeleteBulkAction $action) => static::bloquearSeAlgumaVinculada($records, $action)),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make()
                        ->before(fn (Collection $records, ForceDeleteBulkAction $action) => static::bloquearSeAlgumaVinculada($records, $action)),
                ]),
            ]);
    }

    /**
     * Trava de exclusão/edição (ver CLAUDE.md, "Trava de exclusão/edição
     * com Projeto vinculado"): uma Referência de Preços com pelo menos
     * um Projeto vinculado não pode ser excluída nem editada — evita que
     * o valor de Venda de um Projeto já calculado mude retroativamente
     * ou fique órfão. Os botões continuam visíveis/clicáveis (permissão
     * normal da Policy não muda) — o bloqueio acontece ao TENTAR
     * (`->before()`, antes do formulário salvar/do registro ser
     * excluído), com notificação clara do motivo, em vez de esconder o
     * botão sem explicação nenhuma.
     */
    protected static function bloquearSeVinculada(ReferenciaPreco $referenciaPreco, $action): void
    {
        $totalProjetos = $referenciaPreco->projetos()->count();

        if ($totalProjetos === 0) {
            return;
        }

        Notification::make()
            ->danger()
            ->title(__('comercial::filament/resources/referencia-preco.notifications.vinculada.title'))
            ->body(trans_choice(
                'comercial::filament/resources/referencia-preco.notifications.vinculada.body',
                $totalProjetos,
                ['count' => $totalProjetos],
            ))
            ->send();

        $action->halt();
    }

    /**
     * @param  Collection<int, ReferenciaPreco>  $referenciasPrecos
     */
    protected static function bloquearSeAlgumaVinculada(Collection $referenciasPrecos, $action): void
    {
        $vinculadas = $referenciasPrecos->filter(fn (ReferenciaPreco $referenciaPreco) => $referenciaPreco->projetos()->exists());

        if ($vinculadas->isEmpty()) {
            return;
        }

        Notification::make()
            ->danger()
            ->title(__('comercial::filament/resources/referencia-preco.notifications.vinculada.title'))
            ->body(__('comercial::filament/resources/referencia-preco.notifications.vinculada-em-massa.body', [
                'descricoes' => $vinculadas->pluck('descricao')->unique()->implode(', '),
            ]))
            ->send();

        $action->halt();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReferenciasPrecos::route('/'),
        ];
    }
}
