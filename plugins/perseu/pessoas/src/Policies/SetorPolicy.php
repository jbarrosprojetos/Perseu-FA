<?php

namespace Perseu\Pessoas\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Perseu\Pessoas\Models\Setor;
use Webkul\Security\Models\User;

class SetorPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_pessoas_setor');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Setor $setor): bool
    {
        return $user->can('view_pessoas_setor');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_pessoas_setor');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Setor $setor): bool
    {
        return $user->can('update_pessoas_setor');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Setor $setor): bool
    {
        return $user->can('delete_pessoas_setor');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_pessoas_setor');
    }
}
