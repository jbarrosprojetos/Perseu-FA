<?php

namespace Perseu\Pessoas\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Perseu\Auditoria\Traits\LogsBusinessActivity;

class Contato extends Model
{
    use LogsBusinessActivity;

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
