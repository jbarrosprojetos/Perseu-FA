<?php

namespace Perseu\Pessoas\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contato extends Model
{
    protected $table = 'contatos';

    protected $fillable = [
        'pessoa_fisica_id',
        'pessoa_juridica_id',
        'cargo',
    ];

    public function pessoaFisica(): BelongsTo
    {
        return $this->belongsTo(PessoaFisica::class);
    }

    public function pessoaJuridica(): BelongsTo
    {
        return $this->belongsTo(PessoaJuridica::class);
    }
}
