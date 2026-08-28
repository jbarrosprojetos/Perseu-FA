<?php

namespace Perseu\Pessoas\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Perseu\Auditoria\Traits\LogsBusinessActivity;

class Setor extends Model
{
    use LogsBusinessActivity;

    protected $table = 'setores';

    protected $fillable = [
        'descricao',
    ];

    public function pessoasJuridicas(): BelongsToMany
    {
        return $this->belongsToMany(PessoaJuridica::class, 'pessoa_juridica_setor');
    }
}
