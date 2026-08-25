<?php

namespace Webkul\Project\Filament\Clusters\Settings\Pages;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Schema;
use Webkul\Project\Settings\TimeSettings;
use Webkul\Support\Enums\NavigationGroup;
use Webkul\Support\Filament\Clusters\Settings;

class ManageTime extends SettingsPage
{
    use HasPageShield;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static string $settings = TimeSettings::class;

    protected static ?string $cluster = Settings::class;

    public static function getNavigationGroup(): string|\UnitEnum
    {
        return NavigationGroup::Project;
    }

    public function getBreadcrumbs(): array
    {
        return [
            __('projects::filament/clusters/settings/pages/manage-time.title'),
        ];
    }

    public function getTitle(): string
    {
        return __('projects::filament/clusters/settings/pages/manage-time.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('projects::filament/clusters/settings/pages/manage-time.title');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Toggle::make('enable_timesheets')
                    ->label(__('projects::filament/clusters/settings/pages/manage-time.form.enable-timesheets'))
                    ->helperText(__('projects::filament/clusters/settings/pages/manage-time.form.enable-timesheets-helper-text'))
                    ->required(),
            ]);
    }
}
