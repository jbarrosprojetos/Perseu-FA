<?php

namespace Perseu\Comercial\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Perseu\Auditoria\Traits\LogsBusinessActivity;

/**
 * Tabela de preços de referência usada para compor Propostas
 * (funcionalidade futura, ver Cluster Referências no CLAUDE.md).
 * Várias tabelas coexistem ao mesmo tempo — NÃO é histórico/
 * versionamento, cada registro é uma tabela de preços independente
 * (ex.: "Tabela Padrão", "Tabela Cliente Corporativo"), escolhida na
 * hora de montar uma Proposta.
 */
class ReferenciaPreco extends Model
{
    use LogsBusinessActivity;
    use SoftDeletes;

    protected $table = 'referencias_precos';

    protected $fillable = [
        'descricao',
        'laminacao',
        'corte',
        'hora_producao',
        'hora_execucao',
        'retencao_tecnica',
        'imposto',
        'despesas_variaveis',
        'despesas_fixas',
    ];

    protected $casts = [
        'laminacao'          => 'decimal:2',
        'corte'              => 'decimal:2',
        'hora_producao'      => 'decimal:2',
        'hora_execucao'      => 'decimal:2',
        'retencao_tecnica'   => 'decimal:2',
        'imposto'            => 'decimal:2',
        'despesas_variaveis' => 'decimal:2',
        'despesas_fixas'     => 'decimal:2',
    ];
}
