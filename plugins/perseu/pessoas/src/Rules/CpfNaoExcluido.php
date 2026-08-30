<?php

namespace Perseu\Pessoas\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Perseu\Pessoas\Models\PessoaFisica;

/**
 * Equivalente a `CnpjNaoExcluido`, mesmo bug/mesma correção (ver
 * CLAUDE.md, "Excluir Pessoa Jurídica em cascata" — a mesma
 * vulnerabilidade foi confirmada em Pessoa Física numa tarefa
 * posterior, reproduzida antes de corrigir, não só suposta):
 * complementa a validação `->unique()` do campo `cpf`, que por si só
 * (mesmo com `whereNull('deleted_at')`, ver
 * `PessoaFisicaResource::form()`) só bloqueia CPF duplicado entre
 * registros ATIVOS — sem esta regra, um CPF pertencente a uma Pessoa
 * Física soft-deleted deixaria de bloquear a recriação (correto), mas
 * sem avisar o usuário que já existe um cadastro na Lixeira com esse
 * CPF, criando dois registros de fato pro mesmo CPF (um ativo, um
 * excluído) sem ele saber.
 */
class CpfNaoExcluido implements ValidationRule
{
    public function __construct(private readonly ?int $ignoreId = null)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $query = PessoaFisica::onlyTrashed()->where('cpf', $value);

        if ($this->ignoreId !== null) {
            $query->whereKeyNot($this->ignoreId);
        }

        if ($query->exists()) {
            $fail(__('pessoas::validation.rules.cpf-excluido'));
        }
    }
}
