<?php

namespace Perseu\Pessoas\Traits;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Perseu\Pessoas\Enums\TipoEndereco;
use Perseu\Pessoas\Models\Endereco;
use Perseu\Pessoas\Support\ViaCepLookup;

/**
 * Shared form()/table() for the Endereços Relation Manager, reused by
 * both PessoaFisicaResource and PessoaJuridicaResource — the `enderecos`
 * relationship (and the "enderecos" table columns/CEP lookup) is
 * identical in both contexts; only the label/translation namespace and
 * which TipoEndereco cases are offered differ (see CLAUDE.md, "Filtro de
 * Tipo de Endereço por contexto"). `enderecos()` is a BelongsToMany with
 * `->withPivot('principal')` — Filament's CreateAction/EditAction ainda
 * fazem o split automático de `principal` (pivot vs. atributo de
 * Endereco). O campo `tipos` NÃO é mais pivot (ver CLAUDE.md, "Tipo de
 * Endereço como tag") — é uma tag N:N entre Endereco e TipoEndereco
 * (tabela `endereco_tipo`), então precisa de sincronização manual via
 * `->after()`/`->mutateRecordDataUsing()` abaixo, fora do mecanismo
 * automático de pivot do Filament (que só conhece colunas de
 * `withPivot()`).
 *
 * The using class only needs to declare `$relationship = 'enderecos'`
 * and implement translationPrefix() + tipoEnderecoOptions().
 */
trait HasEnderecoRelationManagerSchema
{
    abstract protected static function translationPrefix(): string;

    /**
     * @return array<TipoEndereco>
     */
    abstract protected static function tipoEnderecoOptions(): array;

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __(static::translationPrefix().'.title');
    }

    public function form(Schema $schema): Schema
    {
        $prefix = static::translationPrefix();

        return $schema
            ->components([
                TextInput::make('cep')
                    ->label(__("{$prefix}.form.cep"))
                    ->mask('99999-999')
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Set $set, ?string $state) => ViaCepLookup::fill($set, $state)),
                TextInput::make('logradouro')
                    ->label(__("{$prefix}.form.logradouro")),
                TextInput::make('numero')
                    ->label(__("{$prefix}.form.numero")),
                TextInput::make('complemento')
                    ->label(__("{$prefix}.form.complemento")),
                TextInput::make('bairro')
                    ->label(__("{$prefix}.form.bairro")),
                TextInput::make('municipio')
                    ->label(__("{$prefix}.form.municipio")),
                TextInput::make('uf')
                    ->label(__("{$prefix}.form.uf"))
                    ->maxLength(2),
                // Tag, não valor único (ver CLAUDE.md, "Tipo de Endereço
                // como tag") — ao criar um endereço novo, todas as opções
                // vêm marcadas por padrão (->default() só se aplica
                // quando não há estado existente, ou seja, só no Create;
                // no Edit o estado vem de mutateRecordDataUsing() abaixo,
                // refletindo as tags reais já salvas).
                CheckboxList::make('tipos')
                    ->label(__("{$prefix}.form.tipos"))
                    ->options(static::tipoEnderecoSelectOptions())
                    ->default(array_keys(static::tipoEnderecoSelectOptions()))
                    ->columns(2)
                    ->required()
                    ->columnSpanFull(),
                Toggle::make('principal')
                    ->label(__("{$prefix}.form.principal")),
            ]);
    }

    public function table(Table $table): Table
    {
        $prefix = static::translationPrefix();

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('tipos'))
            ->recordTitleAttribute('logradouro')
            ->columns([
                TextColumn::make('logradouro')
                    ->label(__("{$prefix}.table.columns.logradouro"))
                    ->searchable(),
                TextColumn::make('numero')
                    ->label(__("{$prefix}.table.columns.numero")),
                TextColumn::make('bairro')
                    ->label(__("{$prefix}.table.columns.bairro"))
                    ->searchable(),
                TextColumn::make('municipio')
                    ->label(__("{$prefix}.table.columns.municipio"))
                    ->searchable(),
                TextColumn::make('uf')
                    ->label(__("{$prefix}.table.columns.uf")),
                TextColumn::make('tipos')
                    ->label(__("{$prefix}.table.columns.tipos"))
                    ->getStateUsing(fn (Endereco $record) => $record->tipos
                        ->pluck('tipo')
                        ->map(fn (TipoEndereco $tipo) => $tipo->getLabel())
                        ->all())
                    ->badge(),
                IconColumn::make('pivot.principal')
                    ->label(__("{$prefix}.table.columns.principal"))
                    ->boolean(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(__("{$prefix}.table.header-actions.create.label"))
                    ->icon('heroicon-o-plus-circle')
                    ->after(fn (array $data, Endereco $record) => static::syncTipos($record, $data['tipos'] ?? []))
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__("{$prefix}.table.header-actions.create.notification.title"))
                            ->body(__("{$prefix}.table.header-actions.create.notification.body")),
                    ),
            ])
            ->recordActions([
                EditAction::make()
                    ->mutateRecordDataUsing(fn (array $data, Endereco $record): array => [
                        ...$data,
                        'tipos' => $record->tipos()->pluck('tipo')->all(),
                    ])
                    ->after(fn (array $data, Endereco $record) => static::syncTipos($record, $data['tipos'] ?? []))
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__("{$prefix}.table.actions.edit.notification.title"))
                            ->body(__("{$prefix}.table.actions.edit.notification.body")),
                    ),
                DeleteAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__("{$prefix}.table.actions.delete.notification.title"))
                            ->body(__("{$prefix}.table.actions.delete.notification.body")),
                    ),
            ]);
    }

    /**
     * @return array<int, string>
     */
    protected static function tipoEnderecoSelectOptions(): array
    {
        $options = [];

        foreach (static::tipoEnderecoOptions() as $tipo) {
            $options[$tipo->value] = $tipo->getLabel();
        }

        return $options;
    }

    /**
     * Substitui o conjunto de tags do Endereço pelo enviado no
     * CheckboxList — mesma lógica serve Create (nenhuma tag existente
     * ainda, `delete()` não encontra nada) e Edit (troca o conjunto
     * antigo pelo novo).
     *
     * @param  array<int, int|string>  $tipos
     */
    protected static function syncTipos(Endereco $endereco, array $tipos): void
    {
        $endereco->tipos()->delete();

        $endereco->tipos()->createMany(
            collect($tipos)
                ->filter(fn ($tipo) => filled($tipo))
                ->unique()
                ->map(fn ($tipo) => ['tipo' => (int) $tipo])
                ->values()
                ->all()
        );
    }
}
