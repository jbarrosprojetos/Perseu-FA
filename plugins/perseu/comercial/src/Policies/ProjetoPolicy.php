<?php

namespace Perseu\Comercial\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Perseu\Comercial\Models\Projeto;
use Webkul\Security\Models\User;

class ProjetoPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_comercial_projeto');
    }

    public function view(User $user, Projeto $projeto): bool
    {
        return $user->can('view_comercial_projeto');
    }

    public function create(User $user): bool
    {
        return $user->can('create_comercial_projeto');
    }

    public function update(User $user, Projeto $projeto): bool
    {
        return $user->can('update_comercial_projeto');
    }

    public function delete(User $user, Projeto $projeto): bool
    {
        return $user->can('delete_comercial_projeto');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_comercial_projeto');
    }

    public function restore(User $user, Projeto $projeto): bool
    {
        return $user->can('restore_comercial_projeto');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_comercial_projeto');
    }

    public function forceDelete(User $user, Projeto $projeto): bool
    {
        return $user->can('force_delete_comercial_projeto');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_comercial_projeto');
    }
}
