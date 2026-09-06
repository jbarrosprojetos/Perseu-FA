<?php

namespace Perseu\Comercial\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro de "Mobilização e Frete" vinculado 1-pra-1 a um
 * `ItemProjeto` de origem `OrigemItemProjeto::MobilizacaoFrete` — só os
 * campos IMPUTADOS pelo usuário (ver migration
 * `create_fretes_mobilizacao_table`). Os totais (mobilização vistoria/
 * obra, frete, geral) NÃO são coluna aqui — são calculados sob demanda
 * por `ProjetoResource::calcularTotaisMobilizacaoFrete()`, a partir dos
 * campos deste registro; decisão do usuário (ver CLAUDE.md, seção
 * "Mobilização e Frete").
 *
 * SEM `SoftDeletes` (mesmo critério do `ItemProjeto`, que este registro
 * sempre acompanha via `cascadeOnDelete()` na migration) — excluir o
 * Item exclui o Frete vinculado junto, sem rastro órfão.
 */
class FreteMobilizacao extends Model
{
    protected $table = 'fretes_mobilizacao';

    protected $fillable = [
        'projeto_id',
        'item_projeto_id',
        'prazo_obra_dias',
        'qtde_vistoria',
        'funcionarios_vistoria',
        'funcionarios_obra',
        'valor_cafe_manha',
        'valor_almoco',
        'valor_jantar',
        'valor_hotel',
        'dias_viagem',
        'valor_aviao',
        'valor_onibus',
        'km',
        'qtde_frete',
        'valor_frete_viagem',
        'valor_translado',
    ];

    protected $casts = [
        'prazo_obra_dias'       => 'integer',
        'qtde_vistoria'         => 'integer',
        'funcionarios_vistoria' => 'integer',
        'funcionarios_obra'     => 'integer',
        'valor_cafe_manha'      => 'decimal:2',
        'valor_almoco'          => 'decimal:2',
        'valor_jantar'          => 'decimal:2',
        'valor_hotel'           => 'decimal:2',
        'dias_viagem'           => 'integer',
        'valor_aviao'           => 'decimal:2',
        'valor_onibus'          => 'decimal:2',
        'km'                    => 'decimal:2',
        'qtde_frete'            => 'integer',
        'valor_frete_viagem'    => 'decimal:2',
        'valor_translado'       => 'decimal:2',
    ];

    public function projeto(): BelongsTo
    {
        return $this->belongsTo(Projeto::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(ItemProjeto::class, 'item_projeto_id');
    }
}
