<?php

namespace Perseu\Comercial\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Perseu\Comercial\Models\TipoProjeto;
use Webkul\Security\Models\User;

class TipoProjetoPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_comercial_tipo::projeto');
    }

    public function view(User $user, TipoProjeto $tipoProjeto): bool
    {
        return $user->can('view_comercial_tipo::projeto');
    }

    public function create(User $user): bool
    {
        return $user->can('create_comercial_tipo::projeto');
    }

    public function update(User $user, TipoProjeto $tipoProjeto): bool
    {
        return $user->can('update_comercial_tipo::projeto');
    }

    public function delete(User $user, TipoProjeto $tipoProjeto): bool
    {
        return $user->can('delete_comercial_tipo::projeto');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_comercial_tipo::projeto');
    }
}
