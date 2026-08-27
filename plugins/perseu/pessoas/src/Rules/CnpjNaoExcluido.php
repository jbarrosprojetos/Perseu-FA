<?php

namespace Perseu\Pessoas\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Perseu\Pessoas\Models\PessoaJuridica;

/**
 * Complementa a validação `->unique()` do campo `cnpj`: o `->unique()` em
 * si foi ajustado (ver PessoaJuridicaResource::form()) para considerar
 * só registros ATIVOS (whereNull('deleted_at')) — sem isso, um CNPJ que
 * pertence a uma Pessoa Jurídica soft-deleted bloqueava a criação com a
 * mensagem genérica "já se encontra registrado", sem explicar o motivo
 * real (o cadastro existe, só está na lixeira) nem o que fazer a
 * respeito.
 *
 * Esta regra assume esse papel especificamente: bloquear a criação
 * quando existe uma Pessoa Jurídica EXCLUÍDA (soft-deleted) com o mesmo
 * CNPJ, com mensagem própria orientando o usuário. Criada porque ainda
 * não há Lixeira/Restore funcional para Pessoa Jurídica no painel — sem
 * isso, permitir a criação de um novo cadastro com o mesmo CNPJ deixaria
 * dois registros de fato para o mesmo CNPJ (um ativo, um excluído),
 * situação a evitar até essa decisão maior ser tomada (ver CLAUDE.md).
 */
class CnpjNaoExcluido implements ValidationRule
{
    public function __construct(private readonly ?int $ignoreId = null)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $query = PessoaJuridica::onlyTrashed()->where('cnpj', $value);

        if ($this->ignoreId !== null) {
            $query->whereKeyNot($this->ignoreId);
        }

        if ($query->exists()) {
            $fail(__('pessoas::validation.rules.cnpj-excluido'));
        }
    }
}
