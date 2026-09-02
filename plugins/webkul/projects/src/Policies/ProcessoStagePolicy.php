<?php

namespace Webkul\Project\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Webkul\Project\Models\ProcessoStage;
use Webkul\Security\Models\User;

class ProcessoStagePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_project_processo::stage');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ProcessoStage $processoStage): bool
    {
        return $user->can('view_project_processo::stage');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_project_processo::stage');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ProcessoStage $processoStage): bool
    {
        return $user->can('update_project_processo::stage');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ProcessoStage $processoStage): bool
    {
        return $user->can('delete_project_processo::stage');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_project_processo::stage');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, ProcessoStage $processoStage): bool
    {
        return $user->can('force_delete_project_processo::stage');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_project_processo::stage');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, ProcessoStage $processoStage): bool
    {
        return $user->can('restore_project_processo::stage');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_project_processo::stage');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_project_processo::stage');
    }
}
