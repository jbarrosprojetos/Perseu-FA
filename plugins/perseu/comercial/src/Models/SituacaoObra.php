<?php

namespace Perseu\Comercial\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Perseu\Auditoria\Traits\LogsBusinessActivity;

class SituacaoObra extends Model
{
    use LogsBusinessActivity;

    protected $table = 'situacoes_obra';

    protected $fillable = [
        'descricao',
    ];

    public function obras(): BelongsToMany
    {
        return $this->belongsToMany(Obra::class, 'obra_situacao');
    }
}
