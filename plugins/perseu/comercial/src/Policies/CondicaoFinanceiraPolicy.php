<?php

namespace Perseu\Comercial\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Perseu\Comercial\Models\CondicaoFinanceira;
use Webkul\Security\Models\User;

class CondicaoFinanceiraPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_comercial_condicao::financeira');
    }

    public function view(User $user, CondicaoFinanceira $condicaoFinanceira): bool
    {
        return $user->can('view_comercial_condicao::financeira');
    }

    public function create(User $user): bool
    {
        return $user->can('create_comercial_condicao::financeira');
    }

    public function update(User $user, CondicaoFinanceira $condicaoFinanceira): bool
    {
        return $user->can('update_comercial_condicao::financeira');
    }

    public function delete(User $user, CondicaoFinanceira $condicaoFinanceira): bool
    {
        return $user->can('delete_comercial_condicao::financeira');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_comercial_condicao::financeira');
    }

    public function restore(User $user, CondicaoFinanceira $condicaoFinanceira): bool
    {
        return $user->can('restore_comercial_condicao::financeira');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_comercial_condicao::financeira');
    }

    public function forceDelete(User $user, CondicaoFinanceira $condicaoFinanceira): bool
    {
        return $user->can('force_delete_comercial_condicao::financeira');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_comercial_condicao::financeira');
    }
}
