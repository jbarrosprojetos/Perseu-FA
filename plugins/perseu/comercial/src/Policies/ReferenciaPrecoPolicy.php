<?php

namespace Perseu\Comercial\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Perseu\Comercial\Models\ReferenciaPreco;
use Webkul\Security\Models\User;

class ReferenciaPrecoPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_comercial_referencia::preco');
    }

    public function view(User $user, ReferenciaPreco $referenciaPreco): bool
    {
        return $user->can('view_comercial_referencia::preco');
    }

    public function create(User $user): bool
    {
        return $user->can('create_comercial_referencia::preco');
    }

    public function update(User $user, ReferenciaPreco $referenciaPreco): bool
    {
        return $user->can('update_comercial_referencia::preco');
    }

    public function delete(User $user, ReferenciaPreco $referenciaPreco): bool
    {
        return $user->can('delete_comercial_referencia::preco');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_comercial_referencia::preco');
    }

    public function restore(User $user, ReferenciaPreco $referenciaPreco): bool
    {
        return $user->can('restore_comercial_referencia::preco');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_comercial_referencia::preco');
    }

    public function forceDelete(User $user, ReferenciaPreco $referenciaPreco): bool
    {
        return $user->can('force_delete_comercial_referencia::preco');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_comercial_referencia::preco');
    }
}
