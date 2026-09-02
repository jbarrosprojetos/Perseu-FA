<?php

namespace Perseu\Pessoas\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Perseu\Pessoas\Enums\TipoEndereco;

/**
 * Uma tag de finalidade de um Endereço (Comercial, Obra, Cobrança,
 * etc.) — relação N:N entre `Endereco` e os valores do enum
 * `TipoEndereco`. Um Endereço pode ter várias tags ao mesmo tempo (ex:
 * Comercial + Obra), ver CLAUDE.md de `perseu/pessoas`, "Tipo de
 * Endereço como tag".
 */
class EnderecoTipo extends Model
{
    protected $table = 'endereco_tipo';

    protected $fillable = [
        'endereco_id',
        'tipo',
    ];

    protected $casts = [
        'tipo' => TipoEndereco::class,
    ];

    public function endereco(): BelongsTo
    {
        return $this->belongsTo(Endereco::class);
    }
}
