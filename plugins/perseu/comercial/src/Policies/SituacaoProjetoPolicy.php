<?php

namespace Perseu\Comercial\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Perseu\Comercial\Models\SituacaoProjeto;
use Webkul\Security\Models\User;

class SituacaoProjetoPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_comercial_situacao::projeto');
    }

    public function view(User $user, SituacaoProjeto $situacaoProjeto): bool
    {
        return $user->can('view_comercial_situacao::projeto');
    }

    public function create(User $user): bool
    {
        return $user->can('create_comercial_situacao::projeto');
    }

    public function update(User $user, SituacaoProjeto $situacaoProjeto): bool
    {
        return $user->can('update_comercial_situacao::projeto');
    }

    public function delete(User $user, SituacaoProjeto $situacaoProjeto): bool
    {
        return $user->can('delete_comercial_situacao::projeto');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_comercial_situacao::projeto');
    }
}
