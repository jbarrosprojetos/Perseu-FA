<?php

namespace Perseu\Comercial\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Perseu\Auditoria\Traits\LogsBusinessActivity;

class TipoProjeto extends Model
{
    use LogsBusinessActivity;

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
