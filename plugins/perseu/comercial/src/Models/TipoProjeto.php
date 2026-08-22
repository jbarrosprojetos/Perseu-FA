<?php

namespace Perseu\Comercial\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoProjeto extends Model
{
    protected $table = 'tipos_projeto';

    protected $fillable = [
        'codigo',
        'descricao',
    ];

    public function projetos(): HasMany
    {
        return $this->hasMany(Projeto::class);
    }
}
