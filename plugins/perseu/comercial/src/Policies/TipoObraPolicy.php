<?php

namespace Perseu\Comercial\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Perseu\Comercial\Models\TipoObra;
use Webkul\Security\Models\User;

class TipoObraPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_comercial_tipo::obra');
    }

    public function view(User $user, TipoObra $tipoObra): bool
    {
        return $user->can('view_comercial_tipo::obra');
    }

    public function create(User $user): bool
    {
        return $user->can('create_comercial_tipo::obra');
    }

    public function update(User $user, TipoObra $tipoObra): bool
    {
        return $user->can('update_comercial_tipo::obra');
    }

    public function delete(User $user, TipoObra $tipoObra): bool
    {
        return $user->can('delete_comercial_tipo::obra');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_comercial_tipo::obra');
    }
}
