<?php

namespace Perseu\Comercial\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Perseu\Auditoria\Traits\LogsBusinessActivity;
use Perseu\Comercial\Enums\FormaPagamento;

/**
 * Catálogo de Condições Financeiras (ver CLAUDE.md, "Condições
 * Financeiras") — mesmo padrão de `ReferenciaPreco`: várias condições
 * coexistindo ao mesmo tempo (NÃO é histórico/versionamento), cada
 * registro reutilizável por vários Projetos, escolhida na hora de
 * montar/editar um Projeto.
 *
 * `taxa_mensal` alimenta o cálculo do fator de amortização pelo
 * Sistema Price em tempo real (fator = i / (1 - (1+i)^-n)), de acordo
 * com a quantidade de parcelas escolhida no Projeto — o cálculo em si
 * (e o uso no Total do Projeto) fica para uma tarefa futura.
 */
class CondicaoFinanceira extends Model
{
    use LogsBusinessActivity;
    use SoftDeletes;

    protected $table = 'condicoes_financeiras';

    protected $fillable = [
        'descricao',
        'porcentagem_entrada',
        'qtde_parcelas',
        'intervalo_dias',
        'forma_pagamento',
        'taxa_mensal',
    ];

    protected $casts = [
        'porcentagem_entrada' => 'decimal:2',
        'qtde_parcelas'       => 'integer',
        'intervalo_dias'      => 'integer',
        'forma_pagamento'     => FormaPagamento::class,
        'taxa_mensal'         => 'decimal:2',
    ];

    /**
     * Projetos vinculados a esta Condição Financeira — mesmo uso de
     * `ReferenciaPreco::projetos()`, reservado para uma futura trava de
     * exclusão/edição em `CondicaoFinanceiraPolicy` caso este catálogo
     * ganhe a mesma regra de negócio (ver CLAUDE.md).
     */
    public function projetos(): HasMany
    {
        return $this->hasMany(Projeto::class);
    }
}
