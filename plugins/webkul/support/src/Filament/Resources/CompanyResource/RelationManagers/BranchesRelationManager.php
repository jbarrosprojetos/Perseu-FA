<?php

namespace Webkul\Support\Filament\Resources\CompanyResource\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Perseu\Pessoas\Enums\IndicadorContribuinteIcms;
use Perseu\Pessoas\Enums\RegimeTributario;
use Perseu\Pessoas\Rules\CnpjValido;
use Perseu\Pessoas\Support\BrasilApiCnpjLookup;
use Webkul\Support\Models\Country;
use Webkul\Support\Models\Currency;
use Webkul\Support\Support\CompanyCnpjLookup;
use Webkul\Support\Support\SituacaoCadastralBadge;

class BranchesRelationManager extends RelationManager
{
    protected static string $relationship = 'branches';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('support::filament/resources/company/relation-managers/manage-branch.title');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make()
                    ->tabs([
                        Tab::make(__('support::filament/resources/company/relation-managers/manage-branch.form.tabs.general-information.title'))
                            ->schema([
                                Section::make(__('support::filament/resources/company/relation-managers/manage-branch.form.tabs.general-information.sections.branch-information.title'))
                                    ->schema([
                                        TextInput::make('tax_id')
                                            // Reaproveita `tax_id` como CNPJ, mesmo tratamento de
                                            // CompanyResource (ver CLAUDE.md) — a Filial é uma Pessoa
                                            // Jurídica própria (CNPJ diferente da Matriz), mesma busca
                                            // automática via BrasilAPI.
                                            ->label(__('support::filament/resources/company/relation-managers/manage-branch.form.tabs.general-information.sections.branch-information.fields.tax-id'))
                                            ->mask('99.999.999/9999-99')
                                            ->rule(new CnpjValido())
                                            ->unique(ignoreRecord: true)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function (Set $set, Get $get, ?string $state) {
                                                BrasilApiCnpjLookup::fill($set, $get, $state, razaoSocialField: 'name');
                                                CompanyCnpjLookup::fillEndereco($set, $get, $state);
                                            })
                                            ->hint(fn (Get $get) => $get('cnpj_lookup_erro'))
                                            ->hintColor('danger'),
                                        TextInput::make('name')
                                            ->label(__('support::filament/resources/company/relation-managers/manage-branch.form.tabs.general-information.sections.branch-information.fields.company-name'))
                                            ->required()
                                            ->maxLength(255)
                                            ->unique(table: 'companies', ignoreRecord: true)
                                            ->validationMessages([
                                                'unique' => 'Branch name already exists. Please use a unique name.',
                                            ])
                                            ->live(onBlur: true),
                                        TextInput::make('nome_fantasia')
                                            ->label(__('support::filament/resources/company/relation-managers/manage-branch.form.tabs.general-information.sections.branch-information.fields.nome-fantasia'))
                                            ->maxLength(255),
                                        ColorPicker::make('color')
                                            ->label(__('support::filament/resources/company/relation-managers/manage-branch.form.tabs.general-information.sections.branch-information.fields.color'))
                                            ->hexColor(),
                                        // "Número de registro"/"ID da empresa" — mesma decisão de
                                        // CompanyResource: escondidos, não removidos (ver CLAUDE.md).
                                        TextInput::make('registration_number')
                                            ->label(__('support::filament/resources/company/relation-managers/manage-branch.form.tabs.general-information.sections.branch-information.fields.registration-number'))
                                            ->hidden(),
                                        TextInput::make('company_id')
                                            ->label(__('support::filament/resources/company/relation-managers/manage-branch.form.tabs.general-information.sections.branch-information.fields.company-id'))
                                            ->unique(ignoreRecord: true)
                                            ->hidden(),
                                    ])
                                    ->columns(2),
                                Section::make(__('support::filament/resources/company/relation-managers/manage-branch.form.tabs.general-information.sections.fiscal-information.title'))
                                    ->schema([
                                        Placeholder::make('situacao_cadastral_display')
                                            ->label(__('support::filament/resources/company/relation-managers/manage-branch.form.tabs.general-information.sections.fiscal-information.fields.situacao-cadastral'))
                                            ->content(fn (Get $get) => SituacaoCadastralBadge::render($get('situacao_cadastral'), $get('descricao_situacao_cadastral')))
                                            ->hidden(fn (Get $get) => blank($get('descricao_situacao_cadastral'))),
                                        TextInput::make('cnae')
                                            ->label(__('support::filament/resources/company/relation-managers/manage-branch.form.tabs.general-information.sections.fiscal-information.fields.cnae'))
                                            ->mask('9999-9/99')
                                            ->helperText(fn (Get $get) => $get('cnae_descricao')),
                                        DatePicker::make('founded_date')
                                            ->native(false)
                                            ->label(__('support::filament/resources/company/relation-managers/manage-branch.form.tabs.general-information.sections.fiscal-information.fields.data-abertura')),
                                        Select::make('indicador_contribuinte_icms')
                                            ->label(__('support::filament/resources/company/relation-managers/manage-branch.form.tabs.general-information.sections.fiscal-information.fields.indicador-contribuinte-icms'))
                                            ->options(IndicadorContribuinteIcms::class),
                                        Select::make('regime_tributario')
                                            ->label(__('support::filament/resources/company/relation-managers/manage-branch.form.tabs.general-information.sections.fiscal-information.fields.regime-tributario'))
                                            ->options(RegimeTributario::class)
                                            ->default(RegimeTributario::NaoInformado->value),
                                        TextInput::make('porte')
                                            ->label(__('support::filament/resources/company/relation-managers/manage-branch.form.tabs.general-information.sections.fiscal-information.fields.porte'))
                                            ->helperText(fn (Get $get) => $get('descricao_porte')),
                                        Hidden::make('situacao_cadastral'),
                                        Hidden::make('descricao_situacao_cadastral'),
                                        Hidden::make('cnae_descricao'),
                                        Hidden::make('descricao_porte'),
                                    ])
                                    ->columns(3),
                                Section::make(__('support::filament/resources/company/relation-managers/manage-branch.form.tabs.general-information.sections.branding.title'))
                                    ->relationship('partner', 'avatar')
                                    ->schema([
                                        FileUpload::make('avatar')
                                            ->label(__('support::filament/resources/company/relation-managers/manage-branch.form.tabs.general-information.sections.branding.fields.branch-logo'))
                                            ->image()
                                            ->directory('company-logos')
                                            ->visibility('public'),
                                    ]),
                            ])
                            ->columnSpanFull(),
                        Tab::make(__('support::filament/resources/company/relation-managers/manage-branch.form.tabs.address-information.title'))
                            ->schema([
                                Section::make(__('support::filament/resources/company/relation-managers/manage-branch.form.tabs.address-information.sections.address-information.title'))
                                    ->schema([
                                        Group::make()
                                            ->schema([
                                                TextInput::make('street1')
                                                    ->label(__('support::filament/resources/company/relation-managers/manage-branch.form.tabs.address-information.sections.address-information.fields.street1')),
                                                TextInput::make('numero')
                                                    ->label(__('support::filament/resources/company/relation-managers/manage-branch.form.tabs.address-information.sections.address-information.fields.numero')),
                                                TextInput::make('street2')
                                                    ->label(__('support::filament/resources/company/relation-managers/manage-branch.form.tabs.address-information.sections.address-information.fields.street2')),
                                                TextInput::make('bairro')
                                                    ->label(__('support::filament/resources/company/relation-managers/manage-branch.form.tabs.address-information.sections.address-information.fields.bairro')),
                                                TextInput::make('city')
                                                    ->label(__('support::filament/resources/company/relation-managers/manage-branch.form.tabs.address-information.sections.address-information.fields.city')),
                                                TextInput::make('zip')
                                                    ->live()
                                                    ->label(__('support::filament/resources/company/relation-managers/manage-branch.form.tabs.address-information.sections.address-information.fields.zip-code')),
                                                Select::make('country_id')
                                                    ->label(__('support::filament/resources/company/relation-managers/manage-branch.form.tabs.address-information.sections.address-information.fields.country'))
                                                    ->relationship(name: 'country', titleAttribute: 'name')
                                                    ->default(fn () => Country::where('code', 'BR')->value('id'))
                                                    ->afterStateUpdated(fn (Set $set) => $set('state_id', null))
                                                    ->searchable()
                                                    ->preload()
                                                    ->live(),
                                                Select::make('state_id')
                                                    ->label(__('support::filament/resources/company/relation-managers/manage-branch.form.tabs.address-information.sections.address-information.fields.state'))
                                                    ->relationship(
                                                        name: 'state',
                                                        titleAttribute: 'name',
                                                        modifyQueryUsing: fn (Get $get, Builder $query) => $query->where('country_id', $get('country_id')),
                                                    )
                                                    ->searchable()
                                                    ->preload()
                                                    ->createOptionForm(function (Schema $schema, Get $get, Set $set) {
                                                        return $schema
                                                            ->components([
                                                                TextInput::make('name')
                                                                    ->label(__('support::filament/resources/company/relation-managers/manage-branch.form.tabs.address-information.sections.address-information.fields.state-name'))
                                                                    ->required(),
                                                                TextInput::make('code')
                                                                    ->label(__('support::filament/resources/company/relation-managers/manage-branch.form.tabs.address-information.sections.address-information.fields.state-code'))
                                                                    ->required()
                                                                    ->unique('states'),
                                                                Select::make('country_id')
                                                                    ->label(__('support::filament/resources/company/relation-managers/manage-branch.form.tabs.address-information.sections.address-information.fields.country'))
                                                                    ->relationship('country', 'name')
                                                                    ->searchable()
                                                                    ->preload()
                                                                    ->live()
                                                                    ->default($get('country_id'))
                                                                    ->afterStateUpdated(function (Get $get) use ($set) {
                                                                        $set('country_id', $get('country_id'));
                                                                    }),
                                                            ]);
                                                    }),
                                            ])
                                            ->columns(2),
                                    ]),
                                Section::make(__('support::filament/resources/company/relation-managers/manage-branch.form.tabs.address-information.sections.additional-information.title'))
                                    ->schema([
                                        Select::make('currency_id')
                                            ->relationship(
                                                name: 'currency',
                                                titleAttribute: 'name',
                                                modifyQueryUsing: fn (Builder $query) => $query->active(),
                                            )
                                            ->label(__('support::filament/resources/company/relation-managers/manage-branch.form.tabs.address-information.sections.additional-information.fields.default-currency'))
                                            ->searchable()
                                            ->required()
                                            ->live()
                                            ->preload()
                                            ->default(Currency::active()->first()?->id)
                                            ->createOptionForm([
                                                Section::make()
                                                    ->schema([
                                                        TextInput::make('name')
                                                            ->label(__('support::filament/resources/company/relation-managers/manage-branch.form.tabs.address-information.sections.additional-information.fields.currency-name'))
                                                            ->required()
                                                            ->maxLength(255)
                                                            ->unique('currencies', 'name', ignoreRecord: true),
                                                        TextInput::make('full_name')
                                                            ->label(__('support::filament/resources/company/relation-managers/manage-branch.form.tabs.address-information.sections.additional-information.fields.currency-full-name'))
                                                            ->required()
                                                            ->maxLength(255)
                                                            ->unique('currencies', 'full_name', ignoreRecord: true),
                                                        TextInput::make('symbol')
                                                            ->label(__('support::filament/resources/company/relation-managers/manage-branch.form.tabs.address-information.sections.additional-information.fields.currency-symbol'))
                                                            ->required(),
                                                        TextInput::make('iso_numeric')
                                                            ->label(__('support::filament/resources/company/relation-managers/manage-branch.form.tabs.address-information.sections.additional-information.fields.currency-iso-numeric'))
                                                            ->numeric()
                                                            ->required(),
                                                        TextInput::make('decimal_places')
                                                            ->numeric()
                                                            ->label(__('support::filament/resources/company/relation-managers/manage-branch.form.tabs.address-information.sections.additional-information.fields.currency-decimal-places'))
                                                            ->required()
                                                            ->rules('min:0', 'max:10'),
                                                        TextInput::make('rounding')
                                                            ->numeric()
                                                            ->label(__('support::filament/resources/company/relation-managers/manage-branch.form.tabs.address-information.sections.additional-information.fields.currency-rounding'))
                                                            ->required(),
                                                        Toggle::make('active')
                                                            ->label(__('support::filament/resources/company/relation-managers/manage-branch.form.tabs.address-information.sections.additional-information.fields.currency-status'))
                                                            ->default(true),
                                                    ])->columns(2),
                                            ])
                                            ->createOptionAction(
                                                fn (Action $action) => $action
                                                    ->modalHeading(__('support::filament/resources/company/relation-managers/manage-branch.form.tabs.address-information.sections.additional-information.fields.currency-create'))
                                                    ->modalSubmitActionLabel(__('support::filament/resources/company/relation-managers/manage-branch.form.tabs.address-information.sections.additional-information.fields.currency-create'))
                                                    ->modalWidth('lg')
                                            ),
                                        // "Data de fundação" (founded_date) foi movida pra dentro da
                                        // seção "Informações Fiscais", reaproveitada como "Data de
                                        // Abertura" (mesma coluna, mesmo padrão de CompanyResource).
                                        Toggle::make('is_active')
                                            ->label(__('support::filament/resources/company/relation-managers/manage-branch.form.tabs.address-information.sections.additional-information.fields.status'))
                                            ->default(true),
                                    ]),
                            ])
                            ->columnSpanFull(),
                        Tab::make(__('support::filament/resources/company/relation-managers/manage-branch.form.tabs.contact-information.title'))
                            ->schema([
                                Section::make(__('support::filament/resources/company/relation-managers/manage-branch.form.tabs.contact-information.sections.contact-information.title'))
                                    ->schema([
                                        TextInput::make('phone')
                                            ->label(__('support::filament/resources/company/relation-managers/manage-branch.form.tabs.contact-information.sections.contact-information.fields.phone-number'))
                                            ->tel(),
                                        TextInput::make('mobile')
                                            ->label(__('support::filament/resources/company/relation-managers/manage-branch.form.tabs.contact-information.sections.contact-information.fields.mobile-number'))
                                            ->tel(),
                                        TextInput::make('email')
                                            ->label(__('support::filament/resources/company/relation-managers/manage-branch.form.tabs.contact-information.sections.contact-information.fields.email-address'))
                                            ->email(),
                                    ])
                                    ->columns(2),
                            ])
                            ->columnSpanFull(),
                    ]),
            ])
            ->columns(1);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('partner.avatar')
                    ->size(50)
                    ->label(__('support::filament/resources/company/relation-managers/manage-branch.table.columns.logo')),
                TextColumn::make('name')
                    ->label(__('support::filament/resources/company/relation-managers/manage-branch.table.columns.company-name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label(__('support::filament/resources/company/relation-managers/manage-branch.table.columns.email'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('city')
                    ->label(__('support::filament/resources/company/relation-managers/manage-branch.table.columns.city'))
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('country.name')
                    ->label(__('support::filament/resources/company/relation-managers/manage-branch.table.columns.country'))
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('currency.full_name')
                    ->label(__('support::filament/resources/company/relation-managers/manage-branch.table.columns.currency'))
                    ->sortable()
                    ->searchable(),
                IconColumn::make('is_active')
                    ->sortable()
                    ->label(__('support::filament/resources/company/relation-managers/manage-branch.table.columns.status'))
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label(__('support::filament/resources/company/relation-managers/manage-branch.table.columns.created-at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('support::filament/resources/company/relation-managers/manage-branch.table.columns.updated-at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->columnToggleFormColumns(2)
            ->groups([
                Tables\Grouping\Group::make('name')
                    ->label(__('support::filament/resources/company/relation-managers/manage-branch.table.groups.company-name'))
                    ->collapsible(),
                Tables\Grouping\Group::make('city')
                    ->label(__('support::filament/resources/company/relation-managers/manage-branch.table.groups.city'))
                    ->collapsible(),
                Tables\Grouping\Group::make('country.name')
                    ->label(__('support::filament/resources/company/relation-managers/manage-branch.table.groups.country'))
                    ->collapsible(),
                Tables\Grouping\Group::make('state.name')
                    ->label(__('support::filament/resources/company/relation-managers/manage-branch.table.groups.state'))
                    ->collapsible(),
                Tables\Grouping\Group::make('email')
                    ->label(__('support::filament/resources/company/relation-managers/manage-branch.table.groups.email'))
                    ->collapsible(),
                Tables\Grouping\Group::make('phone')
                    ->label(__('support::filament/resources/company/relation-managers/manage-branch.table.groups.phone'))
                    ->collapsible(),
                Tables\Grouping\Group::make('currency_id')
                    ->label(__('support::filament/resources/company/relation-managers/manage-branch.table.groups.currency'))
                    ->collapsible(),
                Tables\Grouping\Group::make('created_at')
                    ->label(__('support::filament/resources/company/relation-managers/manage-branch.table.groups.created-at'))
                    ->collapsible(),
                Tables\Grouping\Group::make('updated_at')
                    ->label(__('support::filament/resources/company/relation-managers/manage-branch.table.groups.updated-at'))
                    ->date()
                    ->collapsible(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->icon('heroicon-o-plus-circle')
                    ->mutateDataUsing(function ($livewire, array $data): array {
                        $data['user_id'] = Auth::user()->id;

                        $data['parent_id'] = $livewire->ownerRecord->id;

                        return $data;
                    })
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title((__('support::filament/resources/company/relation-managers/manage-branch.table.header-actions.create.notification.title')))
                            ->body(__('support::filament/resources/company/relation-managers/manage-branch.table.header-actions.create.notification.body')),
                    ),
            ])
            ->filters([
                TrashedFilter::make()
                    ->label(__('support::filament/resources/company/relation-managers/manage-branch.table.filters.trashed')),
                SelectFilter::make('country')
                    ->label(__('support::filament/resources/company/relation-managers/manage-branch.table.filters.country'))
                    ->multiple()
                    ->options(function () {
                        return Country::pluck('name', 'name');
                    }),
            ])
            ->filtersFormColumns(2)
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title((__('support::filament/resources/company/relation-managers/manage-branch.table.actions.edit.notification.title')))
                                ->body(__('support::filament/resources/company/relation-managers/manage-branch.table.actions.edit.notification.body')),
                        ),
                    DeleteAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title((__('support::filament/resources/company/relation-managers/manage-branch.table.actions.delete.notification.title')))
                                ->body(__('support::filament/resources/company/relation-managers/manage-branch.table.actions.delete.notification.body')),
                        ),
                    RestoreAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title((__('support::filament/resources/company/relation-managers/manage-branch.table.actions.restore.notification.title')))
                                ->body(__('support::filament/resources/company/relation-managers/manage-branch.table.actions.restore.notification.body')),
                        ),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title((__('support::filament/resources/company/relation-managers/manage-branch.table.bulk-actions.delete.notification.title')))
                                ->body(__('support::filament/resources/company/relation-managers/manage-branch.table.bulk-actions.delete.notification.body')),
                        ),
                    ForceDeleteBulkAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title((__('support::filament/resources/company/relation-managers/manage-branch.table.bulk-actions.force-delete.notification.title')))
                                ->body(__('support::filament/resources/company/relation-managers/manage-branch.table.bulk-actions.force-delete.notification.body')),
                        ),
                    RestoreBulkAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title((__('support::filament/resources/company/relation-managers/manage-branch.table.bulk-actions.restore.notification.title')))
                                ->body(__('support::filament/resources/company/relation-managers/manage-branch.table.bulk-actions.restore.notification.body')),
                        ),
                ]),
            ])
            ->reorderable('sort');
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Branch Information')
                    ->tabs([
                        Tab::make(__('support::filament/resources/company/relation-managers/manage-branch.infolist.tabs.general-information.title'))
                            ->schema([
                                Section::make(__('support::filament/resources/company/relation-managers/manage-branch.infolist.tabs.general-information.sections.branch-information.title'))
                                    ->schema([
                                        TextEntry::make('tax_id')
                                            ->icon('heroicon-o-identification')
                                            ->placeholder('—')
                                            ->label(__('support::filament/resources/company/relation-managers/manage-branch.infolist.tabs.general-information.sections.branch-information.entries.tax-id')),
                                        TextEntry::make('name')
                                            ->icon('heroicon-o-building-office')
                                            ->placeholder('—')
                                            ->label(__('support::filament/resources/company/relation-managers/manage-branch.infolist.tabs.general-information.sections.branch-information.entries.company-name')),
                                        TextEntry::make('nome_fantasia')
                                            ->icon('heroicon-o-tag')
                                            ->placeholder('—')
                                            ->label(__('support::filament/resources/company/relation-managers/manage-branch.infolist.tabs.general-information.sections.branch-information.entries.nome-fantasia')),
                                        TextEntry::make('color')
                                            ->icon('heroicon-o-swatch')
                                            ->placeholder('—')
                                            ->badge()
                                            ->color(fn ($record) => $record->color ?? 'gray')
                                            ->label(__('support::filament/resources/company/relation-managers/manage-branch.infolist.tabs.general-information.sections.branch-information.entries.color')),
                                    ])
                                    ->columns(2),

                                Section::make(__('support::filament/resources/company/relation-managers/manage-branch.form.tabs.general-information.sections.fiscal-information.title'))
                                    ->schema([
                                        TextEntry::make('descricao_situacao_cadastral')
                                            ->icon('heroicon-o-shield-check')
                                            ->placeholder('—')
                                            ->label(__('support::filament/resources/company/relation-managers/manage-branch.form.tabs.general-information.sections.fiscal-information.fields.situacao-cadastral')),
                                        TextEntry::make('cnae')
                                            ->placeholder('—')
                                            ->label(__('support::filament/resources/company/relation-managers/manage-branch.form.tabs.general-information.sections.fiscal-information.fields.cnae')),
                                        TextEntry::make('founded_date')
                                            ->icon('heroicon-o-calendar')
                                            ->placeholder('—')
                                            ->date()
                                            ->label(__('support::filament/resources/company/relation-managers/manage-branch.form.tabs.general-information.sections.fiscal-information.fields.data-abertura')),
                                        TextEntry::make('indicador_contribuinte_icms')
                                            ->placeholder('—')
                                            ->label(__('support::filament/resources/company/relation-managers/manage-branch.form.tabs.general-information.sections.fiscal-information.fields.indicador-contribuinte-icms')),
                                        TextEntry::make('regime_tributario')
                                            ->placeholder('—')
                                            ->label(__('support::filament/resources/company/relation-managers/manage-branch.form.tabs.general-information.sections.fiscal-information.fields.regime-tributario')),
                                        TextEntry::make('descricao_porte')
                                            ->placeholder('—')
                                            ->label(__('support::filament/resources/company/relation-managers/manage-branch.form.tabs.general-information.sections.fiscal-information.fields.porte')),
                                    ])
                                    ->columns(2),

                                Section::make(__('support::filament/resources/company/relation-managers/manage-branch.infolist.tabs.general-information.sections.branding.title'))
                                    ->schema([
                                        ImageEntry::make('partner.avatar')
                                            ->hiddenLabel()
                                            ->label(__('support::filament/resources/company/relation-managers/manage-branch.infolist.tabs.general-information.sections.branding.entries.branch-logo'))
                                            ->placeholder('—'),
                                    ]),
                            ]),

                        Tab::make(__('support::filament/resources/company/relation-managers/manage-branch.infolist.tabs.address-information.title'))
                            ->schema([
                                Section::make(__('support::filament/resources/company/relation-managers/manage-branch.infolist.tabs.address-information.sections.address-information.title'))
                                    ->schema([
                                        // Corrigido de `address.street1` etc. pra `street1` etc.:
                                        // `Company` NUNCA teve uma relação/accessor `address` (bug
                                        // pré-existente, achado ao investigar esta tarefa — ver
                                        // CLAUDE.md) — a aba sempre mostrou só "—" vazio, mesmo com
                                        // os campos preenchidos no formulário.
                                        TextEntry::make('street1')
                                            ->icon('heroicon-o-map-pin')
                                            ->placeholder('—')
                                            ->label(__('support::filament/resources/company/relation-managers/manage-branch.infolist.tabs.address-information.sections.address-information.entries.street1')),
                                        TextEntry::make('numero')
                                            ->placeholder('—')
                                            ->label(__('support::filament/resources/company/relation-managers/manage-branch.infolist.tabs.address-information.sections.address-information.entries.numero')),
                                        TextEntry::make('street2')
                                            ->placeholder('—')
                                            ->label(__('support::filament/resources/company/relation-managers/manage-branch.infolist.tabs.address-information.sections.address-information.entries.street2')),
                                        TextEntry::make('bairro')
                                            ->placeholder('—')
                                            ->label(__('support::filament/resources/company/relation-managers/manage-branch.infolist.tabs.address-information.sections.address-information.entries.bairro')),
                                        TextEntry::make('city')
                                            ->icon('heroicon-o-building-library')
                                            ->placeholder('—')
                                            ->label(__('support::filament/resources/company/relation-managers/manage-branch.infolist.tabs.address-information.sections.address-information.entries.city')),
                                        TextEntry::make('zip')
                                            ->placeholder('—')
                                            ->label(__('support::filament/resources/company/relation-managers/manage-branch.infolist.tabs.address-information.sections.address-information.entries.zip-code')),
                                        TextEntry::make('country.name')
                                            ->icon('heroicon-o-globe-alt')
                                            ->placeholder('—')
                                            ->label(__('support::filament/resources/company/relation-managers/manage-branch.infolist.tabs.address-information.sections.address-information.entries.country')),
                                        TextEntry::make('state.name')
                                            ->placeholder('—')
                                            ->label(__('support::filament/resources/company/relation-managers/manage-branch.infolist.tabs.address-information.sections.address-information.entries.state')),
                                    ])
                                    ->columns(2),

                                Section::make(__('support::filament/resources/company/relation-managers/manage-branch.infolist.tabs.address-information.sections.additional-information.title'))
                                    ->schema([
                                        TextEntry::make('currency.full_name')
                                            ->icon('heroicon-o-currency-dollar')
                                            ->placeholder('—')
                                            ->label(__('support::filament/resources/company/relation-managers/manage-branch.infolist.tabs.address-information.sections.additional-information.entries.default-currency')),
                                        IconEntry::make('is_active')
                                            ->label(__('support::filament/resources/company/relation-managers/manage-branch.infolist.tabs.address-information.sections.additional-information.entries.status'))
                                            ->boolean(),
                                    ])
                                    ->columns(2),
                            ]),

                        Tab::make(__('support::filament/resources/company/relation-managers/manage-branch.infolist.tabs.contact-information.title'))
                            ->schema([
                                Section::make(__('support::filament/resources/company/relation-managers/manage-branch.infolist.tabs.contact-information.sections.contact-information.title'))
                                    ->schema([
                                        TextEntry::make('phone')
                                            ->icon('heroicon-o-phone')
                                            ->placeholder('—')
                                            ->label(__('support::filament/resources/company/relation-managers/manage-branch.infolist.tabs.contact-information.sections.contact-information.entries.phone-number')),
                                        TextEntry::make('mobile')
                                            ->icon('heroicon-o-device-phone-mobile')
                                            ->placeholder('—')
                                            ->label(__('support::filament/resources/company/relation-managers/manage-branch.infolist.tabs.contact-information.sections.contact-information.entries.mobile-number')),
                                        TextEntry::make('email')
                                            ->icon('heroicon-o-envelope')
                                            ->placeholder('—')
                                            ->copyable()
                                            ->copyMessage('Email copied')
                                            ->copyMessageDuration(1500)
                                            ->label(__('support::filament/resources/company/relation-managers/manage-branch.infolist.tabs.contact-information.sections.contact-information.entries.email-address')),
                                    ])
                                    ->columns(2),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
