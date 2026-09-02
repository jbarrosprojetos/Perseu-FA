<?php

namespace Webkul\Project\Filament\Resources\ProcessoResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Webkul\Project\Filament\Clusters\Configurations\Resources\MilestoneResource;
use Webkul\Project\Filament\Resources\ProcessoResource;

class ManageMilestones extends ManageRelatedRecords
{
    protected static string $resource = ProcessoResource::class;

    protected static string $relationship = 'milestones';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-flag';

    /**
     * @param  array<string, mixed>  $parameters
     */
    public static function canAccess(array $parameters = []): bool
    {
        $canAccess = parent::canAccess($parameters);

        if (! $canAccess) {
            return false;
        }

        if (! static::$resource::getTaskSettings()->enable_milestones) {
            return false;
        }

        return $parameters['record']?->allow_milestones;
    }

    public static function getNavigationLabel(): string
    {
        return __('projects::filament/resources/processo/pages/manage-milestones.title');
    }

    public static function getRelationshipTitle(): string
    {
        return MilestoneResource::getPluralModelLabel();
    }

    public function form(Schema $schema): Schema
    {
        return MilestoneResource::form($schema);
    }

    public function table(Table $table): Table
    {
        return MilestoneResource::table($table)
            ->headerActions([
                CreateAction::make()
                    ->label(__('projects::filament/resources/processo/pages/manage-milestones.table.header-actions.create.label'))
                    ->icon('heroicon-o-plus-circle')
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('projects::filament/resources/processo/pages/manage-milestones.table.header-actions.create.notification.title'))
                            ->body(__('projects::filament/resources/processo/pages/manage-milestones.table.header-actions.create.notification.body')),
                    ),
            ]);
    }
}
