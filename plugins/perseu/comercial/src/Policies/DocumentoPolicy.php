<?php

namespace Perseu\Comercial\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Perseu\Comercial\Models\Documento;
use Webkul\Security\Models\User;

class DocumentoPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_comercial_documento');
    }

    public function view(User $user, Documento $documento): bool
    {
        return $user->can('view_comercial_documento');
    }

    public function create(User $user): bool
    {
        return $user->can('create_comercial_documento');
    }

    public function update(User $user, Documento $documento): bool
    {
        return $user->can('update_comercial_documento');
    }

    public function delete(User $user, Documento $documento): bool
    {
        return $user->can('delete_comercial_documento');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_comercial_documento');
    }

    public function restore(User $user, Documento $documento): bool
    {
        return $user->can('restore_comercial_documento');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_comercial_documento');
    }

    public function forceDelete(User $user, Documento $documento): bool
    {
        return $user->can('force_delete_comercial_documento');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_comercial_documento');
    }
}
