<?php

namespace Perseu\Pessoas\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Perseu\Pessoas\Models\CategoriaPessoa;
use Webkul\Security\Models\User;

class CategoriaPessoaPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_pessoas_categoria::pessoa');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, CategoriaPessoa $categoriaPessoa): bool
    {
        return $user->can('view_pessoas_categoria::pessoa');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_pessoas_categoria::pessoa');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, CategoriaPessoa $categoriaPessoa): bool
    {
        return $user->can('update_pessoas_categoria::pessoa');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CategoriaPessoa $categoriaPessoa): bool
    {
        return $user->can('delete_pessoas_categoria::pessoa');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_pessoas_categoria::pessoa');
    }
}
