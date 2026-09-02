<?php

namespace Webkul\Project\Filament\Resources\ProcessoResource\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Webkul\Project\Filament\Clusters\Configurations\Resources\TaskStageResource;

class TaskStagesRelationManager extends RelationManager
{
    protected static string $relationship = 'taskStages';

    public static function getRelationshipTitle(): string
    {
        return __('projects::filament/resources/processo/relation-managers/task-stages.title');
    }

    public function form(Schema $schema): Schema
    {
        return TaskStageResource::form($schema);
    }

    public function table(Table $table): Table
    {
        return TaskStageResource::table($table)
            ->filters([])
            ->groups([])
            ->headerActions([
                CreateAction::make()
                    ->label(__('projects::filament/resources/processo/relation-managers/task-stages.table.header-actions.create.label'))
                    ->icon('heroicon-o-plus-circle')
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('projects::filament/resources/processo/relation-managers/task-stages.table.header-actions.create.notification.title'))
                            ->body(__('projects::filament/resources/processo/relation-managers/task-stages.table.header-actions.create.notification.body')),
                    ),
            ]);
    }
}
