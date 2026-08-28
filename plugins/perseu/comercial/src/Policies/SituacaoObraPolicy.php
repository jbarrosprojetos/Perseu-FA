<?php

namespace Perseu\Comercial\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Perseu\Comercial\Models\SituacaoObra;
use Webkul\Security\Models\User;

class SituacaoObraPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_comercial_situacao::obra');
    }

    public function view(User $user, SituacaoObra $situacaoObra): bool
    {
        return $user->can('view_comercial_situacao::obra');
    }

    public function create(User $user): bool
    {
        return $user->can('create_comercial_situacao::obra');
    }

    public function update(User $user, SituacaoObra $situacaoObra): bool
    {
        return $user->can('update_comercial_situacao::obra');
    }

    public function delete(User $user, SituacaoObra $situacaoObra): bool
    {
        return $user->can('delete_comercial_situacao::obra');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_comercial_situacao::obra');
    }
}
