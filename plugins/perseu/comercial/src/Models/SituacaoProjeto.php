<?php

namespace Perseu\Comercial\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SituacaoProjeto extends Model
{
    protected $table = 'situacoes_projeto';

    protected $fillable = [
        'descricao',
    ];

    public function projetos(): BelongsToMany
    {
        return $this->belongsToMany(Projeto::class, 'projeto_situacao');
    }
}
