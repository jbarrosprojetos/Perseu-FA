<?php

namespace Perseu\Comercial\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Perseu\Comercial\Models\Obra;
use Webkul\Security\Models\User;

class ObraPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_comercial_obra');
    }

    public function view(User $user, Obra $obra): bool
    {
        return $user->can('view_comercial_obra');
    }

    public function create(User $user): bool
    {
        return $user->can('create_comercial_obra');
    }

    public function update(User $user, Obra $obra): bool
    {
        return $user->can('update_comercial_obra');
    }

    public function delete(User $user, Obra $obra): bool
    {
        return $user->can('delete_comercial_obra');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_comercial_obra');
    }

    public function restore(User $user, Obra $obra): bool
    {
        return $user->can('restore_comercial_obra');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_comercial_obra');
    }

    public function forceDelete(User $user, Obra $obra): bool
    {
        return $user->can('force_delete_comercial_obra');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_comercial_obra');
    }
}
