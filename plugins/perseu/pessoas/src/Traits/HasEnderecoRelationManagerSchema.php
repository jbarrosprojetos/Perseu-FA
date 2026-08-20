<?php

namespace Perseu\Pessoas\Traits;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Perseu\Pessoas\Enums\TipoEndereco;
use Throwable;

/**
 * Shared form()/table() for the Endereços Relation Manager, reused by
 * both PessoaFisicaResource and PessoaJuridicaResource — the `enderecos`
 * relationship (and the "enderecos" table columns/CEP lookup) is
 * identical in both contexts; only the label/translation namespace and
 * which TipoEndereco cases are offered differ (see CLAUDE.md, "Filtro de
 * Tipo de Endereço por contexto"). `enderecos()` is a BelongsToMany with
 * `->withPivot('tipo', 'principal')`, so Filament's CreateAction/EditAction
 * already split pivot vs. Endereco attributes automatically
 * (Filament\Actions\{Create,Edit}Action check
 * $relationship->getPivotColumns()) — no custom save handling needed
 * here.
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
                    ->afterStateUpdated(fn (Set $set, ?string $state) => static::fillAddressFromCep($set, $state)),
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
                Select::make('tipo')
                    ->label(__("{$prefix}.form.tipo"))
                    ->options(static::tipoEnderecoSelectOptions())
                    ->required(),
                Toggle::make('principal')
                    ->label(__("{$prefix}.form.principal")),
            ]);
    }

    public function table(Table $table): Table
    {
        $prefix = static::translationPrefix();

        return $table
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
                TextColumn::make('pivot.tipo')
                    ->label(__("{$prefix}.table.columns.tipo"))
                    ->formatStateUsing(fn (mixed $state): ?string => TipoEndereco::tryFrom((int) $state)?->getLabel())
                    ->badge(),
                IconColumn::make('pivot.principal')
                    ->label(__("{$prefix}.table.columns.principal"))
                    ->boolean(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(__("{$prefix}.table.header-actions.create.label"))
                    ->icon('heroicon-o-plus-circle')
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__("{$prefix}.table.header-actions.create.notification.title"))
                            ->body(__("{$prefix}.table.header-actions.create.notification.body")),
                    ),
            ])
            ->recordActions([
                EditAction::make()
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

    protected static function fillAddressFromCep(Set $set, ?string $cep): void
    {
        $digits = preg_replace('/\D/', '', (string) $cep);

        if (strlen($digits) !== 8) {
            return;
        }

        try {
            $response = Http::timeout(5)->get("https://viacep.com.br/ws/{$digits}/json/");
        } catch (Throwable) {
            return;
        }

        if (! $response->successful()) {
            return;
        }

        $data = $response->json();

        if (! is_array($data) || ($data['erro'] ?? false)) {
            return;
        }

        $set('logradouro', $data['logradouro'] ?? null);
        $set('bairro', $data['bairro'] ?? null);
        $set('municipio', $data['localidade'] ?? null);
        $set('uf', $data['uf'] ?? null);
    }
}
