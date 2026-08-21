<?php

namespace Perseu\Pessoas\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Perseu\Pessoas\Models\PessoaJuridica;
use Webkul\Security\Models\User;

class PessoaJuridicaPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_pessoas_pessoa::juridica');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PessoaJuridica $pessoaJuridica): bool
    {
        return $user->can('view_pessoas_pessoa::juridica');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_pessoas_pessoa::juridica');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PessoaJuridica $pessoaJuridica): bool
    {
        return $user->can('update_pessoas_pessoa::juridica');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PessoaJuridica $pessoaJuridica): bool
    {
        return $user->can('delete_pessoas_pessoa::juridica');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_pessoas_pessoa::juridica');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, PessoaJuridica $pessoaJuridica): bool
    {
        return $user->can('restore_pessoas_pessoa::juridica');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_pessoas_pessoa::juridica');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, PessoaJuridica $pessoaJuridica): bool
    {
        return $user->can('force_delete_pessoas_pessoa::juridica');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_pessoas_pessoa::juridica');
    }
}
