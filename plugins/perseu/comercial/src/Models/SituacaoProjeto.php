<?php

namespace Perseu\Comercial\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Perseu\Auditoria\Traits\LogsBusinessActivity;

class SituacaoProjeto extends Model
{
    use LogsBusinessActivity;

    protected $table = 'situacoes_projeto';

    protected $fillable = [
        'descricao',
    ];

    public function projetos(): BelongsToMany
    {
        return $this->belongsToMany(Projeto::class, 'projeto_situacao');
    }
}
